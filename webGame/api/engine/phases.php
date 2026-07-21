<?php
// Space Agency Race - phase flow (planning -> action -> maintenance),
// command-turn bookkeeping, maintenance/scoring, and pending decisions.

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/state.php';

// ---------------------------------------------------------------------------
// Phase flow

function sar_begin_planning(array &$g): void {
    $g['phase'] = 'planning';
    $g['missionDoneThisRound'] = false;
    $g['ev04Used'] = false;
    foreach ($g['players'] as &$p) {
        $p['planningDone'] = false;
        $p['turnsUsed'] = 0;
        $p['passed'] = false;
        $p['flushedTurn'] = null;
        $p['techOrbVpRound'] = 0;
        if ($p['pendingLevel'] !== null) { // new agency level takes effect now
            $p['level'] = $p['pendingLevel'];
            $p['pendingLevel'] = null;
            sar_log($g, 'level', $p['name'] . " operates at Agency Level {$p['level']} from this round.");
        }
    }
    unset($p);
    foreach ($g['crafts'] as &$c) { $c['relayUsedRound'] = false; }
    unset($c);

    sar_log($g, 'phase', "Round {$g['round']} — Planning Phase.", ['phase' => 'planning', 'round' => $g['round']]);

    // 1. Reveal Event. Round 1 uses the Starter Event revealed at setup
    // (v0.5.1); the round event deck is drawn from round 2 on.
    if ($g['round'] === 1 && !empty($g['starterEvent'])) {
        $g['event'] = $g['starterEvent'];
    } elseif ($g['decks']['event']) {
        $g['event'] = array_shift($g['decks']['event']);
    } else {
        $g['event'] = null;
    }
    if ($g['event']) {
        $ev = sar_event_id($g);
        $card = sar_card($g['event']);
        sar_log($g, 'event', "Event: {$card['name']} — {$card['text']}", ['card' => $g['event']]);
        if ($ev === 'EV02' || $ev === 'EV15') { // Funding Boost / Founding Grant: +3 Credits, immediately
            foreach ($g['players'] as &$p) $p['credits'] += 3;
            unset($p);
            sar_log($g, 'gain', "{$card['name']}: every agency gains 3 Credits.");
        }
        if ($ev === 'EV16') { // Crash Program: +1 command turn this round
            sar_log($g, 'event', 'Crash Program: every agency has 1 additional command turn this round.');
        }
        if ($ev === 'EV13' && $g['strandedCrew'] === null) {
            $g['strandedCrew'] = 'unclaimed';
            sar_log($g, 'event', 'A crew is stranded at LEO! First Crewed craft to visit LEO and then return to Earth rescues them for 5 VP.');
        }
    }

    // 2. Advance Transfer Window (not on round 1 — marker starts on the first space).
    if ($g['round'] > 1) {
        $g['twIdx'] = ($g['twIdx'] + 1) % count(SAR_TW_CYCLE);
    }
    sar_log($g, 'tw', 'Transfer Window cost is now ' . SAR_TW_CYCLE[$g['twIdx']] .
        (in_array(sar_event_id($g), ['EV06', 'EV07'], true) ? ' (before event modifier)' : '') . '.',
        ['tw' => SAR_TW_CYCLE[$g['twIdx']]]);

    // 3. Draw 2 cards each
    foreach ($g['players'] as &$p) {
        $drawn = sar_draw_component($g, 2);
        $p['hand'] = array_merge($p['hand'], $drawn);
    }
    unset($p);
    sar_log($g, 'draw', 'Each agency draws 2 component cards.');
}

function sar_draw_component(array &$g, int $n): array {
    $out = [];
    for ($i = 0; $i < $n; $i++) {
        if (!$g['decks']['component']) {
            if (!$g['decks']['componentDiscard']) break;
            $g['decks']['component'] = $g['decks']['componentDiscard'];
            $g['decks']['componentDiscard'] = [];
            shuffle($g['decks']['component']);
            sar_log($g, 'deck', 'Component discard pile reshuffled into a new deck.');
        }
        $out[] = array_shift($g['decks']['component']);
    }
    return $out;
}

// Player finishes planning: may sell up to 2 cards (1 Credit each) and must
// end at or below the hand limit.
function sar_action_planning_done(array &$g, int $seat, array $a): void {
    if ($g['phase'] !== 'planning') throw new SarError('Not in Planning Phase');
    $p = &$g['players'][$seat];
    if ($p['planningDone']) throw new SarError('Already ready');
    $sell = $a['sell'] ?? [];
    $discard = $a['discard'] ?? [];
    if (count($sell) > 2) throw new SarError('You may sell at most 2 cards');
    foreach (array_merge($sell, $discard) as $uid) {
        if (!in_array($uid, $p['hand'], true)) throw new SarError('Card not in hand');
    }
    if (count(array_unique(array_merge($sell, $discard))) !== count($sell) + count($discard)) {
        throw new SarError('Duplicate card selection');
    }
    if (count($p['hand']) - count($sell) - count($discard) > sar_hand_limit($g)) {
        throw new SarError('Hand would still exceed the limit of ' . sar_hand_limit($g));
    }
    foreach ($sell as $uid) {
        $p['hand'] = array_values(array_diff($p['hand'], [$uid]));
        $g['decks']['componentDiscard'][] = $uid;
        $p['credits'] += 1;
    }
    foreach ($discard as $uid) {
        $p['hand'] = array_values(array_diff($p['hand'], [$uid]));
        $g['decks']['componentDiscard'][] = $uid;
    }
    if ($sell) sar_log($g, 'sell', $p['name'] . ' emergency-sells ' . count($sell) . ' card(s) for ' . count($sell) . ' Credit(s).');
    $p['planningDone'] = true;
    unset($p);

    foreach ($g['players'] as $pl) if (!$pl['planningDone']) return;
    sar_begin_action_phase($g);
}

function sar_begin_action_phase(array &$g): void {
    $g['phase'] = 'action';
    // Refill every craft's Energy to its Power.
    foreach ($g['crafts'] as &$c) {
        $c['energy'] = sar_craft_power($g, $c);
        $c['activated'] = false;
    }
    unset($c);
    $g['turnSeat'] = $g['firstSeat'];
    sar_log($g, 'phase', "Round {$g['round']} — Action Phase. " . sar_pname($g, $g['firstSeat']) . ' acts first.',
        ['phase' => 'action']);
    sar_skip_to_next_actor($g, true);
}

// Advance turnSeat to the next player who still has command turns.
// $stay: if true, keep current seat when it can still act.
function sar_skip_to_next_actor(array &$g, bool $stay = false): void {
    $np = count($g['players']);
    $start = $g['turnSeat'];
    for ($i = 0; $i < $np; $i++) {
        $seat = ($start + ($stay ? $i : $i + 1)) % $np;
        $p = $g['players'][$seat];
        if (!$p['passed'] && $p['turnsUsed'] < sar_command_turns($g, $seat)) {
            $g['turnSeat'] = $seat;
            return;
        }
    }
    sar_maintenance($g);
}

function sar_require_turn(array $g, int $seat): void {
    if ($g['phase'] !== 'action') throw new SarError('Not in the Action Phase');
    if ($g['pending']) throw new SarError('A decision is pending');
    if ($g['turnSeat'] !== $seat) throw new SarError('Not your command turn');
}

function sar_end_command_turn(array &$g, int $seat): void {
    $g['players'][$seat]['turnsUsed']++;
    if ($g['pending']) return; // wait for the decision before moving on
    sar_skip_to_next_actor($g);
}

// ---------------------------------------------------------------------------
// Maintenance & end of round

// Recover a craft standing on Earth: unstaged Reusable parts return to the
// owner's hand (every unstaged part under Recovery Trials, EV14), the rest is
// discarded, and the craft marker is freed.
function sar_recover_earth_craft(array &$g, string $id): void {
    $craft = $g['crafts'][$id];
    $ev14 = sar_event_id($g) === 'EV14';
    $seat = $craft['owner'];
    $p = &$g['players'][$seat];
    $recovered = []; $lost = []; $refurb = 0;
    foreach ($craft['cards'] as $uid) {
        if ($ev14 || sar_has_tag($uid, 'Reusable')) {
            $p['hand'][] = $uid;
            $recovered[] = sar_card($uid)['name'];
            // The refurb credit stays tied to genuinely Reusable parts.
            if (sar_has_tech($g, $seat, 'C01') && sar_has_tag($uid, 'Reusable')) { $p['credits'] += 1; $refurb++; }
        } else {
            $g['decks']['componentDiscard'][] = $uid;
            $lost[] = sar_card($uid)['name'];
        }
    }
    // A jury-rigged card is never recovered — not even by Recovery Trials.
    if ($craft['sideways'] !== null) {
        $g['decks']['componentDiscard'][] = $craft['sideways'];
        $lost[] = sar_card($craft['sideways'])['name'] . ' (jury-rigged)';
    }
    $msg = $p['name'] . "'s {$craft['name']} is recovered on Earth.";
    if ($recovered) $msg .= ' Returned to hand: ' . implode(', ', $recovered) . ($ev14 ? ' (Recovery Trials)' : '') . '.';
    if ($lost) $msg .= ' Expended: ' . implode(', ', $lost) . '.';
    if ($refurb) $msg .= " Reusable Refurb pays $refurb Credit(s).";
    sar_log($g, 'recover', $msg, ['seat' => $seat]);
    unset($p);
    unset($g['crafts'][$id]);
}

function sar_maintenance(array &$g): void {
    $g['phase'] = 'maintenance';
    sar_log($g, 'phase', "Round {$g['round']} — Maintenance Phase.", ['phase' => 'maintenance']);

    // 0. Sub-orbital decay: arcs are not orbits — anything still on one touches down.
    require_once __DIR__ . '/flight.php';
    sar_suborbital_decay($g);

    // 1. Recover craft still standing on Earth. Craft that landed during the
    // round were already recovered on touchdown (sar_recover_if_landed); this
    // sweep catches rockets left on the pad and sub-orbital decay landings.
    foreach ($g['crafts'] as $id => $craft) {
        if ($craft['node'] !== 'earth' || $craft['deployed']) continue;
        if ($craft['launchRound'] === null) continue; // never launched
        sar_recover_earth_craft($g, $id);
    }

    // 2. Asset Operations: persistent assets harvest income automatically.
    sar_asset_operations($g);

    // 3. Discard the event.
    if ($g['event']) {
        $g['decks']['eventDiscard'][] = $g['event'];
        $g['event'] = null;
    }

    // 4. Mission sweep if nothing was completed this round.
    if (!$g['missionDoneThisRound'] && $g['missions']) {
        $old = array_shift($g['missions']);
        $g['decks']['missionDiscard'][] = $old;
        sar_log($g, 'mission', 'No missions were completed this round — ' . sar_card($old)['name'] . ' expires and leaves the display.');
    }

    // 5. Refill mission display and market.
    while (count($g['missions']) < 3 && $g['decks']['mission']) {
        $g['missions'][] = array_shift($g['decks']['mission']);
    }
    foreach ($g['market'] as $i => $slot) {
        if ($slot === null) {
            $d = sar_draw_component($g, 1);
            $g['market'][$i] = $d[0] ?? null;
        }
    }

    // 6. Next round or game end.
    if ($g['round'] >= SAR_ROUNDS) {
        sar_final_scoring($g);
        return;
    }
    $g['round']++;
    sar_begin_planning($g);
}

function sar_asset_operations(array &$g): void {
    $ev10 = sar_event_id($g) === 'EV10';
    foreach ($g['crafts'] as $id => &$craft) {
        if (!$craft['deployed']) continue;
        $seat = $craft['owner'];
        $p = &$g['players'][$seat];
        foreach ($craft['cards'] as $uid) {
            $cid = explode('#', $uid)[0];
            $gain = null; // [credits, vp, label]
            switch ($cid) {
                case 'P01': case 'P02': // Comm Satellite / Imaging Probe income
                    $gain = sar_beyond_zoi($craft['node']) ? [0, 1, 'deep-space data'] : [1, 0, 'near-Earth services'];
                    break;
                case 'P08': // Station Hub ops (needs designated station)
                    if ($craft['isStation']) $gain = [1, 0, 'station operations'];
                    break;
                case 'S13': // Microgravity Lab on a station in GEO
                    if ($craft['isStation'] && $craft['node'] === 'geo') $gain = [0, 1, 'station research'];
                    break;
                case 'P09': // Rover on a surface
                    if (in_array($craft['node'], ['moon', 'mars'], true)) $gain = [0, 1, 'surface science'];
                    break;
                case 'P10': // Space Telescope
                    $vp = sar_storm_active($g) ? 2 : 1;
                    $gain = [0, $vp, 'telescope observations'];
                    break;
            }
            if ($gain === null) continue;
            if ($craft['energy'] < 1) {
                sar_log($g, 'income', "{$craft['name']} has no Energy — its income ability idles. (Attach a Power card such as a Solar Panel or RTG.)");
                continue;
            }
            $craft['energy'] -= 1;
            [$cr, $vp, $label] = $gain;
            if ($ev10) $cr += 1; // Broadcast Rights
            $p['credits'] += $cr;
            $p['vp'] += $vp;
            $bits = [];
            if ($cr) $bits[] = "$cr Credit" . ($cr > 1 ? 's' : '');
            if ($vp) $bits[] = "$vp VP";
            sar_log($g, 'income', $p['name'] . "'s {$craft['name']} earns " . implode(' and ', $bits) . " from $label.",
                ['seat' => $seat, 'craft' => $id, 'credits' => $cr, 'vp' => $vp]);
        }
        unset($p);
    }
    unset($craft);
}

function sar_final_scoring(array &$g): void {
    $g['phase'] = 'finished';
    $g['status'] = 'finished';
    $scores = [];
    foreach ($g['players'] as $seat => &$p) {
        $assets = 0;
        foreach ($g['crafts'] as $c) if ($c['owner'] === $seat && $c['deployed']) $assets++;
        if ($assets) {
            $p['vp'] += $assets;
            sar_log($g, 'score', $p['name'] . " scores +$assets VP for persistent assets still deployed.", ['seat' => $seat, 'vp' => $assets]);
        }
        $scores[] = ['seat' => $seat, 'name' => $p['name'], 'vp' => $p['vp'],
                     'missions' => $p['missionsCompleted'], 'credits' => $p['credits'], 'assets' => $assets];
    }
    unset($p);
    usort($scores, function ($a, $b) {
        return [$b['vp'], $b['missions'], $b['credits']] <=> [$a['vp'], $a['missions'], $a['credits']];
    });
    $g['finalScores'] = $scores;
    $g['winner'] = $scores[0]['seat'];
    sar_log($g, 'score', '🏆 ' . $scores[0]['name'] . ' wins with ' . $scores[0]['vp'] . ' VP!');
}

// ---------------------------------------------------------------------------
// Pending decisions (e.g. Launch Abort System reroll)

function sar_action_decision(array &$g, int $seat, array $a): void {
    if (!$g['pending']) throw new SarError('No decision pending');
    if ($g['pending']['seat'] !== $seat) throw new SarError('Not your decision');
    $type = $g['pending']['type'];
    if ($type === 'reroll') {
        require_once __DIR__ . '/flight.php';
        sar_resolve_reroll($g, $seat, !empty($a['accept']));
    } else {
        throw new SarError('Unknown pending decision');
    }
}
