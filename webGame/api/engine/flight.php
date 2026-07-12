<?php
// Flight resolution: Launch New Craft & Activate Craft actions.
//
// The client submits a complete flight *plan*; the server validates it with a
// dry run (forced launch success, state copy discarded) and then executes it
// for real. The only randomness is the d10 reliability roll on launches and
// surface relaunches; a failed roll can pause into a pending Launch Abort
// System reroll decision.

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/state.php';
require_once __DIR__ . '/lobby.php';
require_once __DIR__ . '/phases.php';
require_once __DIR__ . '/actions.php';
require_once __DIR__ . '/missions.php';

// ---------------------------------------------------------------------------
// Actions

function sar_action_launch(array &$g, int $seat, array $a): void {
    sar_require_turn($g, $seat);
    $p = &$g['players'][$seat];

    // Optionally combine Engineering + Launch in one command turn.
    if (!empty($a['components'])) {
        $craftId = sar_apply_engineering($g, $seat, ['craft' => $a['craft'] ?? null,
            'add' => $a['components'], 'remove' => $a['remove'] ?? []]);
    } else {
        $craftId = $a['craft'] ?? '';
    }
    if (!isset($g['crafts'][$craftId])) throw new SarError('No such rocket');
    $craft = $g['crafts'][$craftId];
    if ($craft['owner'] !== $seat) throw new SarError('Not your rocket');
    if ($craft['node'] !== 'assembly') throw new SarError('That craft is already in flight — use Activate instead');
    if (!sar_craft_engine($craft)) throw new SarError('A rocket needs an Engine to launch from Earth');

    if (sar_event_id($g) === 'EV03') { // Supply Delay
        if ($p['credits'] < 1) throw new SarError('Supply Delay: launching costs 1 Credit this round and you have none');
        $p['credits'] -= 1;
        sar_log($g, 'event', $p['name'] . ' pays 1 Credit to launch during the Supply Delay.');
    }
    unset($p);

    $plan = $a['plan'] ?? [];
    $plan['path'] = $plan['path'] ?? ['earth'];
    if (($plan['path'][0] ?? '') !== 'earth') throw new SarError('Launch path must start at Earth');

    // Move rocket to the pad: range = sum of tank Range values.
    $range = 0;
    foreach (sar_craft_cards($g['crafts'][$craftId], 'Tank') as $uid) $range += sar_card($uid)['range'];
    $g['crafts'][$craftId]['node'] = 'earth';
    $g['crafts'][$craftId]['range'] = $range;
    $g['crafts'][$craftId]['launchRound'] = $g['round'];
    $g['crafts'][$craftId]['history'] = ['earth'];

    // Dry-run validation on a copy (reliability forced to succeed).
    $probe = $g;
    sar_run_flight($probe, $craftId, $plan, 1, true);

    sar_log($g, 'launchStart', sar_pname($g, $seat) . " launches {$g['crafts'][$craftId]['name']}!",
        ['craft' => $craftId, 'seat' => $seat]);

    // Tech Breakthrough: first launch attempt of the round digs for a Technology.
    if (sar_event_id($g) === 'EV04' && !$g['ev04Used']) {
        $g['ev04Used'] = true;
        $revealed = [];
        while ($g['decks']['component'] || $g['decks']['componentDiscard']) {
            $d = sar_draw_component($g, 1);
            if (!$d) break;
            $card = sar_card($d[0]);
            if ($card['type'] === 'Tech') {
                $g['players'][$seat]['hand'][] = $d[0];
                sar_log($g, 'event', 'Tech Breakthrough: ' . sar_pname($g, $seat) . " reveals and keeps {$card['name']}.");
                break;
            }
            $revealed[] = $d[0];
        }
        foreach ($revealed as $uid) $g['decks']['componentDiscard'][] = $uid;
    }

    $result = sar_run_flight($g, $craftId, $plan, 1, false);
    if ($result !== 'pending') sar_finish_flight($g, $craftId, $plan);
}

function sar_action_activate(array &$g, int $seat, array $a): void {
    sar_require_turn($g, $seat);
    $craftId = $a['craft'] ?? '';
    if (!isset($g['crafts'][$craftId])) throw new SarError('No such craft');
    $craft = $g['crafts'][$craftId];
    if ($craft['owner'] !== $seat) throw new SarError('Not your craft');
    if ($craft['node'] === 'assembly') throw new SarError('That rocket has not launched yet');
    if ($craft['activated']) throw new SarError('That craft was already activated this Action Phase');

    $plan = $a['plan'] ?? [];
    $plan['path'] = $plan['path'] ?? [$craft['node']];
    if (($plan['path'][0] ?? '') !== $craft['node']) throw new SarError('Path must start at the craft\'s current node');
    if (count($plan['path']) > 1 && !sar_craft_engine($craft) && !$craft['stagedEngineFlight']) {
        throw new SarError('A craft without an Engine cannot maneuver');
    }

    $probe = $g;
    sar_run_flight($probe, $craftId, $plan, 1, true);

    sar_log($g, 'activate', sar_pname($g, $seat) . " activates {$craft['name']}.", ['craft' => $craftId, 'seat' => $seat]);
    $result = sar_run_flight($g, $craftId, $plan, 1, false);
    if ($result !== 'pending') sar_finish_flight($g, $craftId, $plan);
}

// ---------------------------------------------------------------------------
// The step executor. Returns 'done' | 'pending' | 'failed'.

function sar_run_flight(array &$g, string $craftId, array $plan, int $fromStep, bool $dry, int $skipCheckStep = -1): string {
    $path = $plan['path'];
    $seat = $g['crafts'][$craftId]['owner'];

    // Step-0 extras (before any movement).
    if ($fromStep === 1) {
        // Orbital Tug boost: spend 1 Energy when activating in orbit → +1 Range.
        if (!empty($plan['tug'])) {
            $craft = $g['crafts'][$craftId];
            $tug = null;
            foreach (sar_craft_cards($craft, 'Support', 'Docking') as $uid) {
                if (explode('#', $uid)[0] === 'S06') $tug = $uid;
            }
            if (!$tug) throw new SarError('No Orbital Tug on this craft');
            if (!sar_in_space($craft['node'])) throw new SarError('The Orbital Tug only works in orbit');
            if (!sar_spend_energy($g, $craftId, 1, 'Orbital Tug boost')) throw new SarError('Not enough Energy for the Orbital Tug');
            $g['crafts'][$craftId]['range'] += 1;
            if (!$dry) sar_log($g, 'ability', "{$craft['name']}'s Orbital Tug adds +1 Range.", ['craft' => $craftId]);
        }
        // Fuel Depot at the same node: +2 Range for 1 of the depot's Energy.
        if (!empty($plan['depot'])) {
            $depotId = $plan['depot'];
            if (!isset($g['crafts'][$depotId])) throw new SarError('No such Fuel Depot');
            $depot = $g['crafts'][$depotId];
            if ($depot['owner'] !== $seat) throw new SarError('You can only refuel at your own Fuel Depot');
            $isFuelDepot = (bool)array_filter($depot['cards'], fn($u) => explode('#', $u)[0] === 'P11');
            if (!$depot['deployed'] || !$isFuelDepot) throw new SarError('That asset is not a deployed Fuel Depot');
            if ($depot['node'] !== $g['crafts'][$craftId]['node']) throw new SarError('The Fuel Depot is not at this node');
            if ($depot['depotUsedRound'] === $g['round']) throw new SarError('That Fuel Depot was already used this round');
            if ($depot['energy'] < 1) throw new SarError('The Fuel Depot has no Energy');
            $g['crafts'][$depotId]['energy'] -= 1;
            $g['crafts'][$depotId]['depotUsedRound'] = $g['round'];
            $g['crafts'][$craftId]['range'] += 2;
            if (!$dry) sar_log($g, 'ability', "Fuel Depot transfers propellant: +2 Range for {$g['crafts'][$craftId]['name']}.", ['craft' => $craftId]);
        }
        // Pre-flight staging (only when starting from a surface launch).
        if (!empty($plan['preStage'])) {
            sar_stage_card($g, $craftId, $plan['preStage'], $dry, 'pre-flight');
        }
        // Deploy at the starting node.
        sar_plan_deploys($g, $craftId, $plan, 0, $dry);
    }

    for ($k = max(1, $fromStep); $k < count($path); $k++) {
        $craft = $g['crafts'][$craftId]; // fresh copy each step
        $from = $craft['node'];
        $to = $path[$k];
        $edge = sar_edge($from, $to);
        if (!$edge) throw new SarError("Invalid route: " . SAR_NODES[$from]['name'] . ' is not connected to ' . SAR_NODES[$to]['name']);

        // Leaving a surface = a launch: capability + reliability checks.
        // (Skipped when resuming a step whose check already passed via a
        // successful Launch Abort System reroll — it was already charged
        // and rolled once; see sar_resolve_reroll.)
        if (sar_is_surface($from) && $k !== $skipCheckStep) {
            $res = sar_launch_checks($g, $craftId, $plan, $k, $dry);
            if ($res !== 'ok') return $res; // 'pending' or 'failed'
        }

        // Mid-flight staging before paying for this crossing.
        if (!empty($plan['midStages'][$k])) {
            sar_stage_card($g, $craftId, $plan['midStages'][$k], $dry, 'mid-flight');
        }
        // Aerobraking toward an atmosphere body.
        if (!empty($plan['aerobrake'][$k])) {
            sar_aerobrake($g, $craftId, $plan['aerobrake'][$k], $from, $to, $dry);
        }

        $cost = $edge['tw'] ? sar_tw_cost($g, $seat) : 1;
        $landing = null;
        if (sar_is_surface($to)) {
            $landing = sar_validate_landing($g, $craftId, $to, $plan['landing'][$k] ?? [], $dry);
            $cost += $landing['extraRange'];
        }

        if ($g['crafts'][$craftId]['range'] < $cost) {
            throw new SarError('Not enough Range: crossing to ' . SAR_NODES[$to]['name'] . " costs $cost, craft has {$g['crafts'][$craftId]['range']}");
        }
        $g['crafts'][$craftId]['range'] -= $cost;
        $g['crafts'][$craftId]['node'] = $to;
        $g['crafts'][$craftId]['history'][] = $to;

        if ($landing) sar_apply_landing($g, $craftId, $to, $landing, $dry);

        if (!$dry) {
            sar_log($g, 'move', $g['crafts'][$craftId]['name'] . ' — ' . SAR_NODES[$from]['name'] . ' → ' . SAR_NODES[$to]['name'] .
                " (−$cost Range" . ($edge['tw'] ? ', Transfer Window' : '') . ').',
                ['craft' => $craftId, 'from' => $from, 'to' => $to, 'cost' => $cost]);
            sar_on_arrival($g, $craftId, $to);
        }

        sar_plan_deploys($g, $craftId, $plan, $k, $dry);

        // Docking at a station node.
        if (!empty($plan['dock']) && (int)$plan['dock'] === $k) {
            sar_dock($g, $craftId, $dry);
        }
    }

    // Dock / operate without moving.
    if (count($path) === 1) {
        if (!empty($plan['dock']) && (int)$plan['dock'] === 0) sar_dock($g, $craftId, $dry);
    }
    sar_operate_abilities($g, $craftId, $plan, $dry);
    return 'done';
}

// Capability + reliability checks when leaving surface node. 'ok'|'pending'|'failed'
function sar_launch_checks(array &$g, string $craftId, array $plan, int $step, bool $dry): string {
    $craft = $g['crafts'][$craftId];
    $seat = $craft['owner'];
    $surface = $craft['node'];

    if (!sar_craft_engine($craft)) throw new SarError('No Engine — the craft cannot launch from ' . SAR_NODES[$surface]['name']);
    $eng = sar_craft_engine($craft);
    if (explode('#', $eng)[0] === 'E03' && !sar_craft_cards($craft, 'Tank', 'Cryogenic')) {
        throw new SarError('The Hydrogen Core engine requires at least one Cryo Tank');
    }
    $thrust = sar_craft_thrust($g, $craft);
    $mass = sar_craft_mass($g, $craft);
    if ($thrust < $mass) {
        throw new SarError("Launch Capability Check failed: Thrust $thrust < Mass $mass. Stage away tanks or use a stronger engine.");
    }

    // Crew Capsule energy cost on (re)launch.
    foreach (sar_craft_cards($craft, 'Payload', 'Crewed') as $uid) {
        if (explode('#', $uid)[0] === 'P04') {
            if (!sar_spend_energy($g, $craftId, 1, 'Crew Capsule launch systems')) {
                throw new SarError('The Crew Capsule needs 1 Energy to launch (attach a Battery or RTG)');
            }
        }
    }

    $useFc = false;
    if (!empty($plan['flightComputer'])) {
        $fc = null;
        foreach (sar_craft_cards($craft, 'Support', 'Electronics') as $uid) {
            if (explode('#', $uid)[0] === 'S10') $fc = $uid;
        }
        if ($fc && sar_spend_energy($g, $craftId, 1, 'Flight Computer assist')) $useFc = true;
    }

    [$rel, $mods] = sar_craft_reliability($g, $g['crafts'][$craftId], $useFc);
    if ($dry) return 'ok'; // dry run assumes success

    $roll = random_int(1, 10);
    $ok = $roll <= $rel;
    sar_log($g, 'roll', 'Reliability check for ' . $craft['name'] . ': rolled ' . $roll . ' vs ' . $rel .
        ' (' . implode(', ', $mods) . ') — ' . ($ok ? 'SUCCESS' : 'FAILURE') . '.',
        ['craft' => $craftId, 'roll' => $roll, 'need' => $rel, 'ok' => $ok]);
    if ($ok) return 'ok';

    // Launch Abort System: offer a paid reroll.
    if (sar_has_tech($g, $seat, 'C06') && $g['players'][$seat]['credits'] >= 2) {
        $g['pending'] = ['type' => 'reroll', 'seat' => $seat,
            'data' => ['craft' => $craftId, 'plan' => $plan, 'step' => $step, 'rel' => $rel]];
        sar_log($g, 'pending', sar_pname($g, $seat) . ' may pay 2 Credits for a Launch Abort System reroll.');
        return 'pending';
    }
    sar_launch_failure($g, $craftId);
    return 'failed';
}

function sar_launch_failure(array &$g, string $craftId): void {
    $craft = &$g['crafts'][$craftId];
    $eng = sar_craft_engine($craft);
    if ($eng && !sar_has_tag($eng, 'Reusable')) {
        $craft['cards'] = array_values(array_diff($craft['cards'], [$eng]));
        $g['decks']['componentDiscard'][] = $eng;
        sar_log($g, 'fail', $craft['name'] . ' fails to launch — the ' . sar_card($eng)['name'] . ' is destroyed.',
            ['craft' => $craftId]);
    } else {
        sar_log($g, 'fail', $craft['name'] . ' aborts the launch — the Reusable engine survives intact.', ['craft' => $craftId]);
    }
    // A never-launched rocket returns to the assembly area.
    if (count($craft['history']) <= 1 && ($craft['history'][0] ?? '') === 'earth') {
        $craft['node'] = 'assembly';
        $craft['history'] = [];
        $craft['launchRound'] = null;
        $craft['range'] = 0;
    }
    // The flight this staged Kick Stage was covering for is over.
    $craft['stagedEngineFlight'] = false;
    unset($craft);
}

function sar_resolve_reroll(array &$g, int $seat, bool $accept): void {
    $data = $g['pending']['data'];
    $g['pending'] = null;
    $craftId = $data['craft'];
    if (!$accept) {
        sar_launch_failure($g, $craftId);
        sar_end_command_turn($g, $seat);
        return;
    }
    $p = &$g['players'][$seat];
    if ($p['credits'] < 2) throw new SarError('Not enough Credits for the reroll');
    $p['credits'] -= 2;
    unset($p);
    $roll = random_int(1, 10);
    $ok = $roll <= $data['rel'];
    sar_log($g, 'roll', 'Launch Abort System reroll: ' . $roll . ' vs ' . $data['rel'] . ' — ' . ($ok ? 'SUCCESS' : 'FAILURE') . '.',
        ['craft' => $craftId, 'roll' => $roll, 'need' => $data['rel'], 'ok' => $ok]);
    if ($ok) {
        // The reliability check for this step already succeeded (once, paid
        // for) — resume without rolling or re-charging it again.
        $result = sar_run_flight($g, $craftId, $data['plan'], $data['step'], false, $data['step']);
        if ($result !== 'pending') sar_finish_flight($g, $craftId, $data['plan']);
    } else {
        sar_launch_failure($g, $craftId);
        sar_end_command_turn($g, $seat);
    }
}

// ---------------------------------------------------------------------------
// Staging / aerobraking / landing

function sar_stage_card(array &$g, string $craftId, string $uid, bool $dry, string $when): void {
    $craft = &$g['crafts'][$craftId];
    if (!in_array($uid, $craft['cards'], true)) throw new SarError('Stage card is not on the craft');
    if (!sar_has_tag($uid, 'Stageable')) throw new SarError(sar_card($uid)['name'] . ' is not Stageable');
    $card = sar_card($uid);
    $cid = explode('#', $uid)[0];
    $bonus = ['E07' => 2, 'T03' => 1, 'T04' => 2, 'S01' => 0, 'S02' => 0][$cid] ?? 0;
    if (sar_has_tech($g, $craft['owner'], 'C09') && $bonus > 0) $bonus += 1; // Staging Simulations
    if ($card['type'] === 'Engine') $craft['stagedEngineFlight'] = true; // Kick Stage still counts as the engine
    $craft['cards'] = array_values(array_diff($craft['cards'], [$uid]));
    $g['decks']['componentDiscard'][] = $uid;
    $craft['range'] += $bonus;
    if (!$dry) sar_log($g, 'stage', $craft['name'] . " stages {$card['name']} ($when): +$bonus Range.",
        ['craft' => $craftId, 'card' => $uid, 'bonus' => $bonus]);
    unset($craft);
}

function sar_aerobrake(array &$g, string $craftId, string $uid, string $from, string $to, bool $dry): void {
    $craft = &$g['crafts'][$craftId];
    if (!in_array($uid, $craft['cards'], true)) throw new SarError('Aerobrake card is not on the craft');
    if (!sar_has_tag($uid, 'Reentry')) throw new SarError('Aerobraking needs a Reentry card');
    $earthChain = ['earthZoi', 'geo', 'leo', 'subEarth', 'earth'];
    $marsChain = ['marsZoi', 'marsHigh', 'marsLow', 'subMars', 'mars'];
    $descEarth = in_array($to, $earthChain, true) && array_search($to, $earthChain, true) > array_search($from, $earthChain, true);
    $descMars = in_array($to, $marsChain, true) && in_array($from, $marsChain, true)
        && array_search($to, $marsChain, true) > array_search($from, $marsChain, true);
    if (!$descEarth && !$descMars) throw new SarError('Aerobraking only works while descending toward Earth or Mars');
    $cid = explode('#', $uid)[0];
    $keep = $cid === 'S03' && !$craft['ceramicAeroUsed']; // Ceramic Tile Shield survives once per flight
    if ($keep) {
        $craft['ceramicAeroUsed'] = true;
    } else {
        $craft['cards'] = array_values(array_diff($craft['cards'], [$uid]));
        $g['decks']['componentDiscard'][] = $uid;
    }
    $craft['range'] += 2;
    if (!$dry) sar_log($g, 'stage', $craft['name'] . ' aerobrakes with ' . sar_card($uid)['name'] . ': +2 Range' .
        ($keep ? ' (ceramic tiles survive)' : ' (card expended)') . '.', ['craft' => $craftId]);
    unset($craft);
}

// Returns ['method'=>..., 'uid'=>?, 'extraRange'=>n]; throws if the landing is illegal.
function sar_validate_landing(array &$g, string $craftId, string $to, array $choice, bool $dry): array {
    $craft = $g['crafts'][$craftId];
    $method = $choice['method'] ?? null;
    $uid = $choice['card'] ?? null;
    $hasLegs = false;
    foreach ($craft['cards'] as $u) if (explode('#', $u)[0] === 'S14') $hasLegs = true;
    $engine = sar_craft_engine($craft) || $craft['stagedEngineFlight'];

    if ($to === 'moon') {
        // No atmosphere: always propulsive. A Lander payload or the rocket itself serves as lander.
        $hasLander = (bool)sar_craft_cards($craft, null, 'Lander');
        if (!$engine && !$hasLander) throw new SarError('Moon landing needs an Engine (propulsive) or a Lander');
        if (!$engine) throw new SarError('Moon landing is propulsive — the craft needs an Engine');
        return ['method' => 'propulsive', 'uid' => null, 'extraRange' => 0];
    }
    // Card-based landing device (parachute / airbags). A heat shield (Reentry only)
    // survives the heat but does NOT slow the craft for touchdown.
    if ($method === 'reentry') {
        if (!$uid || !in_array($uid, $craft['cards'], true)) throw new SarError('Choose a landing device (parachute or airbags)');
        $isChute = sar_has_tag($uid, 'Parachute');
        $isAirbag = sar_has_tag($uid, 'Airbag');
        if (!$isChute && !$isAirbag) {
            if (sar_has_tag($uid, 'Reentry')) {
                throw new SarError(sar_card($uid)['name'] . ' shields against reentry heat but cannot land the craft. Use a parachute, airbags, a Lander, or a propulsive landing.');
            }
            throw new SarError(sar_card($uid)['name'] . ' is not a landing device');
        }
        if ($isChute && $to !== 'earth') {
            throw new SarError(sar_card($uid)['name'] . ' only works in Earth\'s thick atmosphere.');
        }
        if ($isAirbag && (bool)sar_craft_cards($craft, 'Payload', 'Crewed')) {
            throw new SarError('Airbags are uncrewed-only — a crew cannot survive the impact. Use a parachute or a propulsive landing.');
        }
        return ['method' => 'reentry', 'uid' => $uid, 'extraRange' => 0];
    }
    if ($method === 'lander') {
        if (!sar_craft_cards($craft, null, 'Lander')) throw new SarError('No Lander on this craft');
        return ['method' => 'lander', 'uid' => null, 'extraRange' => 0];
    }
    if ($method === 'propulsive') {
        if (!$engine) throw new SarError('A propulsive landing requires an Engine');
        return ['method' => 'propulsive', 'uid' => null, 'extraRange' => $hasLegs ? 0 : 1];
    }
    throw new SarError('Landing at ' . SAR_NODES[$to]['name'] . ' needs a landing method (parachute, airbags, a Lander, or propulsive).');
}

function sar_apply_landing(array &$g, string $craftId, string $to, array $landing, bool $dry): void {
    $craft = &$g['crafts'][$craftId];
    if ($landing['method'] === 'reentry') {
        $uid = $landing['uid'];
        $cid = explode('#', $uid)[0];
        $craft['usedReentry'] = true;
        if (sar_has_tag($uid, 'Reusable')) {
            $craft['usedReusableReentry'] = true;
            if (!$dry) sar_log($g, 'land', $craft['name'] . ' lands using ' . sar_card($uid)['name'] . ' (reusable — kept).', ['craft' => $craftId]);
        } else {
            $craft['cards'] = array_values(array_diff($craft['cards'], [$uid]));
            $g['decks']['componentDiscard'][] = $uid;
            if (!$dry) sar_log($g, 'land', $craft['name'] . ' lands using ' . sar_card($uid)['name'] . ' (expended).', ['craft' => $craftId]);
        }
        // Recovery bonus credits (Recovery Chutes / Guided Parafoil / Splashdown Kit), Earth landings.
        if ($to === 'earth' && in_array($cid, ['S02', 'S04', 'S17'], true)) {
            $g['players'][$craft['owner']]['credits'] += 1;
            if (!$dry) sar_log($g, 'gain', sar_pname($g, $craft['owner']) . ' recovers hardware cleanly: +1 Credit.', ['seat' => $craft['owner'], 'credits' => 1]);
        }
    } elseif ($landing['method'] === 'lander') {
        if (!$dry) sar_log($g, 'land', $craft['name'] . ' sets down using its Lander on ' . SAR_NODES[$to]['name'] . '.', ['craft' => $craftId]);
    } else {
        if (!$dry) sar_log($g, 'land', $craft['name'] . ' performs a propulsive landing on ' . SAR_NODES[$to]['name'] . '.', ['craft' => $craftId]);
    }
    unset($craft);
}

// ---------------------------------------------------------------------------
// Sub-orbital decay (Maintenance step 0). A sub-orbital node is a ballistic
// arc, not a stable orbit: any craft still on one at the end of the round
// comes down on the body below. With a passive lander aboard (parachute on
// Earth, airbags if uncrewed, a Lander payload, or Landing Legs + Engine) it
// touches down safely on its own; otherwise the owner had to spend a command
// turn during the round to land it propulsively — and now it crashes.

function sar_suborbital_decay(array &$g): void {
    foreach (array_keys($g['crafts']) as $id) {
        $craft = $g['crafts'][$id];
        $surface = SAR_SUBORBITAL[$craft['node']] ?? null;
        if ($surface === null) continue;
        $landing = sar_passive_landing($craft, $surface);
        if ($landing === null) {
            foreach ($craft['cards'] as $uid) $g['decks']['componentDiscard'][] = $uid;
            unset($g['crafts'][$id]);
            sar_log($g, 'fail', $craft['name'] . "'s arc over " . SAR_NODES[$surface]['name'] .
                ' decays with no way to brake — it crashes and is destroyed. (Land during the round with a command turn, or carry a parachute, airbags, a Lander, or Landing Legs.)',
                ['craft' => $id, 'node' => $surface]);
            continue;
        }
        $g['crafts'][$id]['node'] = $surface;
        $g['crafts'][$id]['history'][] = $surface;
        sar_log($g, 'move', $craft['name'] . "'s sub-orbital arc ends — it descends from " .
            SAR_NODES[$craft['node']]['name'] . ' to ' . SAR_NODES[$surface]['name'] . '.',
            ['craft' => $id, 'from' => $craft['node'], 'to' => $surface, 'cost' => 0]);
        sar_apply_landing($g, $id, $surface, $landing, false);
        sar_on_arrival($g, $id, $surface);
    }
}

// Best hands-off landing available to a decaying sub-orbital craft, or null (crash).
// Prefers options that expend nothing, then the ones that pay a recovery credit.
function sar_passive_landing(array $craft, string $surface): ?array {
    $engine = sar_craft_engine($craft) !== null;
    $legs = (bool)array_filter($craft['cards'], fn($u) => explode('#', $u)[0] === 'S14');
    $lander = (bool)sar_craft_cards($craft, 'Payload', 'Lander');
    $crewed = (bool)sar_craft_cards($craft, 'Payload', 'Crewed');

    if ($surface === 'moon') {
        // No atmosphere: only a propulsive touchdown works, and hands-off only
        // with deployed Landing Legs or a dedicated Lander to set down on.
        return $engine && ($legs || $lander) ? ['method' => 'propulsive', 'uid' => null, 'extraRange' => 0] : null;
    }
    $chutes = $surface === 'earth' ? sar_craft_cards($craft, null, 'Parachute') : [];
    foreach ($chutes as $uid) { // a reusable canopy is kept and still pays its recovery credit
        if (sar_has_tag($uid, 'Reusable')) return ['method' => 'reentry', 'uid' => $uid, 'extraRange' => 0];
    }
    if ($engine && $legs) return ['method' => 'propulsive', 'uid' => null, 'extraRange' => 0];
    if ($chutes) return ['method' => 'reentry', 'uid' => $chutes[0], 'extraRange' => 0];
    if ($lander) return ['method' => 'lander', 'uid' => null, 'extraRange' => 0];
    if (!$crewed) {
        $bags = sar_craft_cards($craft, null, 'Airbag');
        if ($bags) return ['method' => 'reentry', 'uid' => $bags[0], 'extraRange' => 0];
    }
    return null;
}

// ---------------------------------------------------------------------------
// Deploys, docking, ability operation, arrival triggers

function sar_plan_deploys(array &$g, string $craftId, array $plan, int $step, bool $dry): void {
    foreach ($plan['deploys'] ?? [] as $d) {
        if ((int)($d['step'] ?? -1) !== $step) continue;
        sar_deploy($g, $craftId, $d['payload'] ?? '', $d['supports'] ?? [], $dry);
    }
}

function sar_deploy(array &$g, string $craftId, string $payloadUid, array $supportUids, bool $dry): void {
    $craft = &$g['crafts'][$craftId];
    $seat = $craft['owner'];
    if (!in_array($payloadUid, $craft['cards'], true)) throw new SarError('Payload is not on this craft');
    $card = sar_card($payloadUid);
    $cid = explode('#', $payloadUid)[0];
    $node = $craft['node'];
    $isRover = $cid === 'P09';
    if ($card['type'] !== 'Payload') throw new SarError('Only payloads can be deployed');
    if (!in_array('Satellite', $card['tags'], true) && !in_array('Station', $card['tags'], true)) {
        throw new SarError($card['name'] . ' is not a deployable Satellite or Station payload');
    }
    if ($isRover) {
        if (!in_array($node, ['moon', 'mars'], true)) throw new SarError('The Rover deploys on the Moon or Mars surface');
    } else {
        if (!sar_in_space($node) || $node === 'earth') throw new SarError($card['name'] . ' must be deployed at an orbital node');
        if (isset(SAR_SUBORBITAL[$node])) {
            throw new SarError('A sub-orbital arc is not a stable orbit — the asset would fall by the end of the round. Deploy at an orbital node.');
        }
        if ($cid === 'P10' && !in_array($node, ['geo', 'earthZoi', 'moonOrbit', 'subMoon', 'sunOrbit', 'marsZoi', 'marsHigh', 'marsLow', 'subMars'], true) && SAR_NODES[$node]['dist'] < 3) {
            throw new SarError('The Space Telescope deploys at High Orbit (GEO) or beyond');
        }
    }
    if (sar_player_craft_count($g, $seat) >= SAR_MAX_CRAFT) throw new SarError('All 6 craft markers are in use — cannot deploy another asset');

    $assetCards = [$payloadUid];
    foreach ($supportUids as $su) {
        if (!in_array($su, $craft['cards'], true)) throw new SarError('Support card not on craft');
        if (sar_card($su)['type'] !== 'Support') throw new SarError('Only Support cards can stay with a deployed asset');
        $assetCards[] = $su;
    }
    $craft['cards'] = array_values(array_diff($craft['cards'], $assetCards));
    unset($craft);

    $assetId = sar_new_craft($g, $seat, $assetCards, $node);
    $asset = &$g['crafts'][$assetId];
    $asset['deployed'] = true;
    $asset['launchRound'] = $g['round'];
    // Freshly deployed assets come online immediately (energy = their power output).
    $asset['energy'] = sar_craft_power($g, $asset);
    unset($asset);
    if (!$dry) {
        sar_log($g, 'deploy', sar_pname($g, $seat) . ' deploys ' . $card['name'] . ' at ' . SAR_NODES[$node]['name'] . '.',
            ['craft' => $assetId, 'from' => $craftId, 'node' => $node, 'seat' => $seat]);
        sar_check_station($g, $assetId);
        sar_check_missions($g, $assetId);
    }
}

function sar_dock(array &$g, string $craftId, bool $dry): void {
    $craft = &$g['crafts'][$craftId];
    if ($craft['node'] !== 'geo') throw new SarError('Docking happens at the station node, High Orbit (GEO)');
    if (!sar_craft_cards($craft, null, 'Docking')) throw new SarError('Docking requires a Docking support card');
    $station = null;
    foreach ($g['crafts'] as $c) {
        if ($c['isStation'] && $c['node'] === 'geo' && $c['id'] !== $craftId) { $station = $c; break; }
    }
    if (!$station) throw new SarError('There is no On-Orbit Station at High Orbit to dock with');
    if (!sar_spend_energy($g, $craftId, 1, 'docking maneuver')) throw new SarError('Docking needs 1 Energy');
    $craft['docked'] = true;
    $craft['dockedHab'] = (bool)array_filter($station['cards'], fn($u) => explode('#', $u)[0] === 'S12');
    unset($craft);
    if (!$dry) {
        sar_log($g, 'dock', $g['crafts'][$craftId]['name'] . ' docks with ' . $station['name'] . '.', ['craft' => $craftId]);
        if (sar_event_id($g) === 'EV05') {
            $seat = $g['crafts'][$craftId]['owner'];
            $g['players'][$seat]['vp'] += 2;
            sar_log($g, 'gain', 'Docking Opportunity: ' . sar_pname($g, $seat) . ' gains +2 VP.', ['seat' => $seat, 'vp' => 2]);
        }
    }
}

function sar_operate_abilities(array &$g, string $craftId, array $plan, bool $dry): void {
    foreach ($plan['operate'] ?? [] as $op) {
        $uid = $op['card'] ?? '';
        $craft = $g['crafts'][$craftId];
        if (!in_array($uid, $craft['cards'], true)) throw new SarError('Ability card is not on the craft');
        $cid = explode('#', $uid)[0];
        $seat = $craft['owner'];
        switch ($cid) {
            case 'P03': // Science Module
                if ($craft['p03Round'] === $g['round']) throw new SarError('Science Module already ran this round');
                if (!sar_beyond_zoi($craft['node'])) throw new SarError('The Science Module needs Moon Orbit or beyond');
                if (!sar_spend_energy($g, $craftId, 2, 'Science Module research')) throw new SarError('The Science Module needs 2 Energy');
                $g['crafts'][$craftId]['p03Round'] = $g['round'];
                $vp = sar_storm_active($g) ? 2 : 1;
                $g['players'][$seat]['vp'] += $vp;
                if (!$dry) sar_log($g, 'ability', sar_pname($g, $seat) . "'s Science Module returns data: +$vp VP.", ['seat' => $seat, 'vp' => $vp]);
                break;
            case 'S11': // Sensor Array
                if ($craft['s11Round'] === $g['round']) throw new SarError('Sensor Array already ran this round');
                $deep = in_array($craft['node'], ['sunOrbit', 'marsZoi', 'marsHigh', 'marsLow', 'subMars', 'mars'], true);
                if (!sar_storm_active($g) && !$deep) throw new SarError('The Sensor Array pays out during a storm Event or at Sun Orbit and beyond');
                if (!sar_spend_energy($g, $craftId, 1, 'Sensor Array sweep')) throw new SarError('The Sensor Array needs 1 Energy');
                $g['crafts'][$craftId]['s11Round'] = $g['round'];
                $vp = sar_event_id($g) === 'EV09' ? 2 : 1; // Solar Flare Watch doubles rewards
                $g['players'][$seat]['vp'] += $vp;
                if (!$dry) sar_log($g, 'ability', sar_pname($g, $seat) . "'s Sensor Array gathers readings: +$vp VP.", ['seat' => $seat, 'vp' => $vp]);
                break;
            default:
                throw new SarError(sar_card($uid)['name'] . ' has no operable ability');
        }
    }
}

// Triggered on every real (non-dry) node arrival.
function sar_on_arrival(array &$g, string $craftId, string $node): void {
    $craft = $g['crafts'][$craftId];
    $seat = $craft['owner'];
    $n = count($craft['history']);
    $from = $n >= 2 ? $craft['history'][$n - 2] : $node;

    // Solar panels burn up when entering atmosphere from space (not on ascent).
    if (sar_is_atmo($node) && !sar_is_atmo($from)) {
        foreach (sar_craft_cards($craft, 'Support', 'Power') as $uid) {
            if (explode('#', $uid)[0] === 'S07') {
                $g['crafts'][$craftId]['cards'] = array_values(array_diff($g['crafts'][$craftId]['cards'], [$uid]));
                $g['decks']['componentDiscard'][] = $uid;
                sar_log($g, 'discard', $craft['name'] . "'s Solar Panel is torn away by the atmosphere.", ['craft' => $craftId]);
            }
        }
    }

    // Exploration rewards: personal near-Earth floor + global race ladder
    // (includes the first-to-Moon/Mars milestones as the top rung).
    sar_award_exploration($g, $seat, $node);

    // Stranded Crew (EV13): visit LEO with a Crewed payload, then return to Earth.
    if ($g['strandedCrew'] === 'unclaimed') {
        if ($node === 'leo' && sar_craft_cards($craft, 'Payload', 'Crewed')) {
            $g['crafts'][$craftId]['visitedLeoAfterStranded'] = true;
            sar_log($g, 'event', $craft['name'] . ' takes the stranded crew aboard at LEO — bring them home!');
        }
        if ($node === 'earth' && $g['crafts'][$craftId]['visitedLeoAfterStranded']) {
            $g['strandedCrew'] = 'claimed';
            $g['players'][$seat]['vp'] += 5;
            sar_log($g, 'milestone', sar_pname($g, $seat) . ' rescues the stranded crew: +5 VP!', ['seat' => $seat, 'vp' => 5]);
        }
    }

    // Rival Comm Satellites relay traffic when a craft moves beyond Earth ZOI.
    if (sar_beyond_zoi($node)) {
        foreach ($g['crafts'] as $sid => $sat) {
            if ($sat['owner'] === $seat || !$sat['deployed'] || $sat['relayUsedRound']) continue;
            $isComm = (bool)array_filter($sat['cards'], fn($u) => explode('#', $u)[0] === 'P01');
            if (!$isComm || $sat['energy'] < 1) continue;
            $g['crafts'][$sid]['energy'] -= 1;
            $g['crafts'][$sid]['relayUsedRound'] = true;
            $g['players'][$sat['owner']]['credits'] += 1;
            sar_log($g, 'income', sar_pname($g, $sat['owner']) . "'s Comm Satellite relays for the deep-space flight: +1 Credit.",
                ['seat' => $sat['owner'], 'credits' => 1]);
        }
    }

    sar_check_station($g, $craftId);
    sar_check_missions($g, $craftId);
}

// On-Orbit Station qualification (auto-designated when conditions are met).
function sar_check_station(array &$g, string $craftId): void {
    if (!isset($g['crafts'][$craftId])) return;
    $craft = $g['crafts'][$craftId];
    if ($craft['isStation'] || $craft['node'] !== 'geo') return;
    $hasHub = (bool)array_filter($craft['cards'], fn($u) => explode('#', $u)[0] === 'P08');
    if (!$hasHub) return;
    $power = false; $life = false; $extra = 0;
    foreach ($craft['cards'] as $uid) {
        $cid = explode('#', $uid)[0];
        $c = sar_card($uid);
        if (in_array('Power', $c['tags'], true) && $c['energyMode'] === 'Gen') $power = true;
        if (in_array('LifeSupport', $c['tags'], true)) $life = true;
        if ($cid !== 'P08' && (in_array('Scientific', $c['tags'], true) || in_array('Electronics', $c['tags'], true))) $extra++;
    }
    if ($power && $life && $extra >= 1) {
        $g['crafts'][$craftId]['isStation'] = true;
        $g['crafts'][$craftId]['deployed'] = true; // stations are persistent assets
        $g['crafts'][$craftId]['name'] = sar_pname($g, $craft['owner']) . "'s Station";
        sar_log($g, 'station', sar_pname($g, $craft['owner']) . ' designates an On-Orbit Station at High Orbit (GEO)!',
            ['craft' => $craftId, 'seat' => $craft['owner']]);
        sar_check_missions($g, $craftId);
    }
}

function sar_finish_flight(array &$g, string $craftId, array $plan): void {
    if (isset($g['crafts'][$craftId])) {
        $craft = &$g['crafts'][$craftId];
        $craft['activated'] = true;
        // The flight this staged Kick Stage was covering for is over.
        $craft['stagedEngineFlight'] = false;
        $seat = $craft['owner'];
        unset($craft);
        sar_check_missions($g, $craftId);
    } else {
        $seat = $g['turnSeat'];
    }
    sar_end_command_turn($g, $seat);
}
