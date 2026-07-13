<?php
// Space Agency Race - simple command-turn actions (acquire, develop, expand,
// pass, engineering) that don't need the flight resolver.

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/state.php';
require_once __DIR__ . '/phases.php';

// ---------------------------------------------------------------------------
// Simple actions

function sar_action_pass(array &$g, int $seat): void {
    sar_require_turn($g, $seat);
    $g['players'][$seat]['passed'] = true;
    sar_log($g, 'turn', sar_pname($g, $seat) . ' passes for the rest of the round.');
    sar_skip_to_next_actor($g);
}

function sar_basic_cost(array $g, int $seat, array $card): int {
    $cost = $card['cost'];
    if (in_array('Basic', $card['tags'], true) && sar_has_tech($g, $seat, 'C08')) {
        $cost = max(1, $cost - 1); // Mass Production
    }
    return $cost;
}

function sar_action_acquire(array &$g, int $seat, array $a): void {
    sar_require_turn($g, $seat);
    $p = &$g['players'][$seat];
    if (count($p['hand']) >= sar_hand_limit($g)) {
        throw new SarError('Hand is full — you cannot acquire another card (limit ' . sar_hand_limit($g) . ')');
    }
    if (isset($a['slot'])) {
        $slot = (int)$a['slot'];
        if (!isset($g['market'][$slot]) || $g['market'][$slot] === null) throw new SarError('Empty market slot');
        $uid = $g['market'][$slot];
        if (isset($a['uid']) && $a['uid'] !== $uid) {
            throw new SarError('That market slot changed — someone else bought it first');
        }
        $cost = sar_basic_cost($g, $seat, sar_card($uid));
        if ($p['credits'] < $cost) throw new SarError("Not enough Credits (need $cost)");
        $p['credits'] -= $cost;
        $p['hand'][] = $uid;
        $refill = sar_draw_component($g, 1);
        $g['market'][$slot] = $refill[0] ?? null;
        sar_log($g, 'acquire', $p['name'] . ' acquires ' . sar_card($uid)['name'] . " for $cost Credits.",
            ['card' => $uid, 'seat' => $seat, 'slot' => $slot]);
    } elseif (isset($a['basic'])) {
        $cid = $a['basic'];
        $card = sar_cards_data()[$cid] ?? null;
        if (!$card || !in_array('Basic', $card['tags'], true)) throw new SarError('Not a Basic card');
        $cost = sar_basic_cost($g, $seat, $card);
        if ($p['credits'] < $cost) throw new SarError("Not enough Credits (need $cost)");
        $p['credits'] -= $cost;
        $uid = $cid . '#b' . $g['version']; // basic supply is unlimited
        $p['hand'][] = $uid;
        sar_log($g, 'acquire', $p['name'] . " buys a {$card['name']} from the Basic supply for $cost Credits.", ['card' => $uid, 'seat' => $seat]);
    } else {
        throw new SarError('Choose a market slot or a Basic card');
    }
    unset($p);
    sar_end_command_turn($g, $seat);
}

// Flush the Market: free action (does not consume the command turn), once per
// command turn. Pay Credits to discard the whole market and reveal 5 new cards.
function sar_action_flush_market(array &$g, int $seat): void {
    sar_require_turn($g, $seat);
    $p = &$g['players'][$seat];
    if (($p['flushedTurn'] ?? null) === $p['turnsUsed']) {
        throw new SarError('You may flush the market only once per command turn');
    }
    if ($p['credits'] < SAR_FLUSH_COST) throw new SarError('Not enough Credits (need ' . SAR_FLUSH_COST . ')');
    $p['credits'] -= SAR_FLUSH_COST;
    $p['flushedTurn'] = $p['turnsUsed'];
    foreach ($g['market'] as $uid) {
        if ($uid !== null) $g['decks']['componentDiscard'][] = $uid;
    }
    $g['market'] = array_pad(sar_draw_component($g, SAR_MARKET_SIZE), SAR_MARKET_SIZE, null);
    sar_log($g, 'flush', $p['name'] . ' pays ' . SAR_FLUSH_COST .
        ' Credits to flush the Card Market — ' . SAR_MARKET_SIZE . ' new cards are revealed.', ['seat' => $seat]);
    unset($p);
}

function sar_action_develop(array &$g, int $seat, array $a): void {
    sar_require_turn($g, $seat);
    $p = &$g['players'][$seat];
    $uid = $a['card'] ?? '';
    if (!in_array($uid, $p['hand'], true)) throw new SarError('Card not in hand');
    $card = sar_card($uid);
    if ($card['type'] !== 'Tech') throw new SarError('Not a Technology card');
    foreach ($p['tableau'] as $t) {
        if (sar_card($t)['name'] === $card['name']) throw new SarError('You already developed ' . $card['name']);
    }
    if ($p['credits'] < $card['cost']) throw new SarError("Not enough Credits (need {$card['cost']})");
    $p['credits'] -= $card['cost'];
    $p['hand'] = array_values(array_diff($p['hand'], [$uid]));
    $p['tableau'][] = $uid;
    sar_log($g, 'tech', $p['name'] . ' develops ' . $card['name'] . '.', ['card' => $uid, 'seat' => $seat]);

    // Technology milestones. The second-tech bonus is per-player (v0.5):
    // every agency gains +1 VP the first time it reaches two Technologies.
    $n = count($p['tableau']);
    if ($n === 2) {
        if ($g['milestones']['secondTech'] === null) $g['milestones']['secondTech'] = $seat;
        $p['vp'] += 1;
        sar_log($g, 'milestone', $p['name'] . ' develops a second Technology: +1 VP.', ['seat' => $seat, 'vp' => 1]);
    }
    if ($n === 4 && $g['milestones']['fourthTech'] === null) {
        $g['milestones']['fourthTech'] = $seat;
        $p['vp'] += 2;
        sar_log($g, 'milestone', $p['name'] . ' is first to a fourth Technology: +2 VP.', ['seat' => $seat, 'vp' => 2]);
    }
    // +1 VP if controlling an on-orbit Satellite or Station (max once per round)
    if ($p['techOrbVpRound'] < 1) {
        foreach ($g['crafts'] as $c) {
            if ($c['owner'] === $seat && $c['deployed'] && sar_in_space($c['node'])) {
                $p['techOrbVpRound']++;
                $p['vp'] += 1;
                sar_log($g, 'milestone', $p['name'] . ' develops tech while operating an orbital asset: +1 VP.', ['seat' => $seat, 'vp' => 1]);
                break;
            }
        }
    }
    unset($p);
    sar_end_command_turn($g, $seat);
}

function sar_action_expand(array &$g, int $seat): void {
    sar_require_turn($g, $seat);
    $p = &$g['players'][$seat];
    $current = $p['pendingLevel'] ?? $p['level'];
    if ($current >= 3) throw new SarError('Agency is already at maximum Level 3');
    $next = $current + 1;
    $cost = SAR_LEVEL_COST[$next];
    if ($p['credits'] < $cost) throw new SarError("Not enough Credits (need $cost)");
    $p['credits'] -= $cost;
    $p['pendingLevel'] = $next;
    sar_log($g, 'level', $p['name'] . " expands to Agency Level $next (effective next round).", ['seat' => $seat]);
    unset($p);

    if ($next === 2 && !$g['tier2Unlocked']) {
        $g['tier2Unlocked'] = true;
        $add = $g['decks']['missionT2'];
        $g['decks']['missionT2'] = [];
        $g['decks']['mission'] = array_merge($g['decks']['mission'], $add);
        shuffle($g['decks']['mission']);
        sar_log($g, 'unlock', 'Tier 2 Missions join the mission deck!');
        sar_catchup_grant($g, 3);
    }
    if ($next === 3 && !$g['tier3Unlocked']) {
        $g['tier3Unlocked'] = true;
        $add = $g['decks']['missionT3'];
        $g['decks']['missionT3'] = [];
        $g['decks']['mission'] = array_merge($g['decks']['mission'], $add);
        shuffle($g['decks']['mission']);
        sar_log($g, 'unlock', 'Tier 3 Missions join the mission deck!');
        sar_catchup_grant($g, 4);
    }
    if ($next === 3 && $g['milestones']['level3'] === null) {
        $g['milestones']['level3'] = $seat;
        $g['players'][$seat]['vp'] += 2;
        sar_log($g, 'milestone', sar_pname($g, $seat) . ' is the first Level 3 agency: +2 VP.', ['seat' => $seat, 'vp' => 2]);
    }
    sar_end_command_turn($g, $seat);
}

function sar_catchup_grant(array &$g, int $amount): void {
    $minVp = min(array_column($g['players'], 'vp'));
    $tied = array_values(array_filter($g['players'], fn($p) => $p['vp'] === $minVp));
    if (count($tied) > 1) {
        $minCr = min(array_column($tied, 'credits'));
        $tied = array_values(array_filter($tied, fn($p) => $p['credits'] === $minCr));
    }
    if (count($tied) === 1) {
        $g['players'][$tied[0]['seat']]['credits'] += $amount;
        sar_log($g, 'gain', 'Government Catch-Up Grant: ' . $tied[0]['name'] . " receives $amount Credits.");
    } else {
        foreach ($tied as $t) $g['players'][$t['seat']]['credits'] += 2;
        sar_log($g, 'gain', 'Government Catch-Up Grant: tied agencies each receive 2 Credits.');
    }
}

// Engineering: build or modify an assembly-area rocket from hand cards.
// Jury-Rigging (v0.5.1 §9): 'sideways' straps one card from hand onto the
// rocket as improvised hardware; 'unrig' takes the current sideways card back
// to hand (assembly only — once launched it flies until the craft is
// discarded or recovered, and it is then always scrapped).
function sar_apply_engineering(array &$g, int $seat, array $a, bool $asAction): ?string {
    $p = &$g['players'][$seat];
    $craftId = $a['craft'] ?? null;
    $add = $a['add'] ?? [];
    $remove = $a['remove'] ?? [];
    $sidewaysAdd = $a['sideways'] ?? null;
    $unrig = !empty($a['unrig']);
    if ($craftId !== null) {
        if (!isset($g['crafts'][$craftId])) throw new SarError('No such craft');
        $craft = $g['crafts'][$craftId];
        if ($craft['owner'] !== $seat) throw new SarError('Not your craft');
        if ($craft['node'] !== 'assembly') throw new SarError('Craft is already in flight — components cannot be changed');
    } else {
        if (sar_player_craft_count($g, $seat) >= SAR_MAX_CRAFT) throw new SarError('All 6 craft markers are in use');
        if (!$add) throw new SarError('Select components to assemble');
    }
    foreach ($add as $uid) {
        if (!in_array($uid, $p['hand'], true)) throw new SarError('Card not in hand: ' . $uid);
        $t = sar_card($uid)['type'];
        if (!in_array($t, ['Engine', 'Tank', 'Payload', 'Support'], true)) {
            throw new SarError(sar_card($uid)['name'] . ' cannot be mounted on a rocket');
        }
    }
    // Resolve the jury-rig slot (any card type is allowed sideways).
    $curSideways = $craftId !== null ? $g['crafts'][$craftId]['sideways'] : null;
    if ($unrig && $curSideways === null) throw new SarError('No jury-rigged card on this rocket');
    if ($sidewaysAdd !== null) {
        if (!in_array($sidewaysAdd, $p['hand'], true)) throw new SarError('Card not in hand: ' . $sidewaysAdd);
        if (in_array($sidewaysAdd, $add, true)) throw new SarError('A card cannot be mounted and jury-rigged at once');
        if ($curSideways !== null && !$unrig) throw new SarError('Only one jury-rigged card per rocket');
    }
    $sideways = $sidewaysAdd ?? ($unrig ? null : $curSideways);

    $cards = $craftId !== null ? $g['crafts'][$craftId]['cards'] : [];
    foreach ($remove as $uid) {
        if (!in_array($uid, $cards, true)) throw new SarError('Component not on craft');
        $cards = array_values(array_diff($cards, [$uid]));
    }
    $cards = array_merge($cards, $add);
    // composition limits (v0.5: 0-2 engine cluster, uncapped tanks — Thrust
    // is the real limit — and 0-2 rideshare payloads; a jury-rigged mass
    // simulator occupies one of the two payload slots)
    $count = ['Engine' => 0, 'Tank' => 0, 'Payload' => 0, 'Support' => 0];
    foreach ($cards as $uid) $count[sar_card($uid)['type']]++;
    if ($sideways !== null && !in_array(sar_card($sideways)['type'], ['Engine', 'Tank'], true)) $count['Payload']++;
    if ($count['Engine'] > 2) throw new SarError('A rocket may mount at most 2 Engines (a cluster)');
    if ($count['Payload'] > 2) throw new SarError('A rocket may carry at most 2 Payloads (rideshare)');
    if ($count['Support'] > 3) throw new SarError('A rocket may have at most 3 Support cards');

    $toHand = count($remove) + ($unrig ? 1 : 0);
    $fromHand = count($add) + ($sidewaysAdd !== null ? 1 : 0);
    if (count($p['hand']) - $fromHand + $toHand > sar_hand_limit($g)) {
        throw new SarError('Removing those components would exceed your hand limit');
    }
    if (!$cards && $sideways !== null) {
        throw new SarError('A jury-rigged card cannot fly alone — keep at least one real component or remove it too');
    }
    $p['hand'] = array_values(array_diff($p['hand'], $add));
    if ($sidewaysAdd !== null) $p['hand'] = array_values(array_diff($p['hand'], [$sidewaysAdd]));
    $p['hand'] = array_merge($p['hand'], $remove);
    if ($unrig) $p['hand'][] = $curSideways;
    if ($craftId !== null) {
        if (!$cards) { // fully disassembled
            unset($g['crafts'][$craftId]);
            sar_log($g, 'engineering', $p['name'] . ' disassembles a rocket.');
            unset($p);
            return null;
        }
        $g['crafts'][$craftId]['cards'] = $cards;
        $g['crafts'][$craftId]['sideways'] = $sideways;
    } else {
        $craftId = sar_new_craft($g, $seat, $cards, 'assembly');
        $g['crafts'][$craftId]['sideways'] = $sideways;
    }
    $names = implode(' + ', array_map(fn($u) => sar_card($u)['name'], $cards));
    if ($sideways !== null) $names .= ' + ' . sar_card($sideways)['name'] . ' (jury-rigged)';
    sar_log($g, 'engineering', $p['name'] . " configures a rocket: $names.", ['seat' => $seat, 'craft' => $craftId]);
    unset($p);
    return $craftId;
}

function sar_action_engineering(array &$g, int $seat, array $a): void {
    sar_require_turn($g, $seat);
    sar_apply_engineering($g, $seat, $a, true);
    sar_end_command_turn($g, $seat);
}
