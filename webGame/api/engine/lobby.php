<?php
// Space Agency Race - game creation, lobby, and game start (deck build & deal).

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/state.php';

function sar_new_game(string $room, string $mode, string $hostToken): array {
    return [
        'room' => $room, 'mode' => $mode, 'status' => 'lobby', 'hostToken' => $hostToken,
        'players' => [], 'firstSeat' => 0,
        'round' => 0, 'phase' => 'lobby',
        'twIdx' => 0, 'twEventMod' => 0,
        'event' => null,
        'decks' => [], 'market' => [], 'missions' => [],
        'tier2Unlocked' => false, 'tier3Unlocked' => false,
        'milestones' => ['moon' => null, 'mars' => null, 'level3' => null,
                         'secondTech' => null, 'fourthTech' => null],
        'frontier' => [],       // exploration race: group => [seats in arrival order]
        'standing' => [],       // standing contract ids in play (set at start)
        'crafts' => [], 'craftSeq' => 0,
        'turnSeat' => 0,
        'missionDoneThisRound' => false,
        'ev04Used' => false,
        'strandedCrew' => null, // null | 'unclaimed' | 'claimed'
        'pending' => null,      // blocking decision: {type, seat, data}
        'log' => [], 'logSeq' => 0,
        'winner' => null, 'finalScores' => null,
        'version' => 1, 'created' => time(),
    ];
}

function sar_add_player(array &$g, string $name, string $token): int {
    if ($g['status'] !== 'lobby') throw new SarError('Game already started');
    if (count($g['players']) >= 4) throw new SarError('Room is full (4 players max)');
    $seat = count($g['players']);
    $g['players'][] = [
        'seat' => $seat, 'name' => $name, 'color' => SAR_COLORS[$seat], 'token' => $token,
        'credits' => 0, 'vp' => 0, 'level' => 1, 'pendingLevel' => null,
        'hand' => [], 'tableau' => [],
        'planningDone' => false, 'turnsUsed' => 0, 'passed' => false, 'flushedTurn' => null,
        'missionsCompleted' => 0, 'techOrbVpRound' => 0,
        'visited' => [],        // nodes this agency has reached (personal exploration floor)
        'standingDone' => [],   // standing contract ids this agency has completed
        'connected' => true,
    ];
    return $seat;
}

// Build a deck of instance uids for a card id.
function sar_copies(string $cid, int $n): array {
    $out = [];
    for ($i = 1; $i <= $n; $i++) $out[] = "$cid#$i";
    return $out;
}

// Exploration rewards on node arrival: a personal near-Earth Credit floor plus a
// global race ladder that pays the 1st/2nd/3rd/4th agency to reach each frontier.
function sar_award_exploration(array &$g, int $seat, string $node): void {
    // Personal floor: first time this agency reaches orbit (LEO), +1 Credit.
    if ($node === 'leo' && !in_array('leo', $g['players'][$seat]['visited'], true)) {
        $g['players'][$seat]['visited'][] = 'leo';
        $g['players'][$seat]['credits'] += 1;
        sar_log($g, 'gain', sar_pname($g, $seat) . ' reaches orbit (LEO) for the first time: +1 Credit.',
            ['seat' => $seat, 'credits' => 1]);
    }
    // Global exploration race ladder (diminishing by arrival order).
    $group = null;
    if ($node === 'geo') $group = 'geo';
    elseif ($node === 'earthZoi') $group = 'earthZoi';
    elseif (in_array($node, SAR_MOON_BRANCH, true)) $group = 'moon';
    elseif (in_array($node, SAR_MARS_BRANCH, true)) $group = 'mars';
    if ($group === null) return;
    if (!isset($g['frontier'][$group])) $g['frontier'][$group] = [];
    if (in_array($seat, $g['frontier'][$group], true)) return; // already scored this frontier
    $order = count($g['frontier'][$group]);         // 0 = first to arrive
    $g['frontier'][$group][] = $seat;
    if ($group === 'moon' && $g['milestones']['moon'] === null) $g['milestones']['moon'] = $seat;
    if ($group === 'mars' && $g['milestones']['mars'] === null) $g['milestones']['mars'] = $seat;
    [$cr, $vp] = SAR_EXPLORE_LADDER[$group][min($order, 3)];
    if ($cr === 0 && $vp === 0) return;
    $g['players'][$seat]['credits'] += $cr;
    $g['players'][$seat]['vp'] += $vp;
    $ord = ['first', 'second', 'third', 'fourth'][min($order, 3)];
    $label = ['geo' => 'High Orbit', 'earthZoi' => 'Earth ZOI',
              'moon' => 'the Moon branch', 'mars' => 'the Mars branch'][$group];
    $bits = [];
    if ($vp) $bits[] = "$vp VP";
    if ($cr) $bits[] = "$cr Credit" . ($cr > 1 ? 's' : '');
    sar_log($g, 'milestone', sar_pname($g, $seat) . " is the $ord agency to reach $label: +" .
        implode(' +', $bits) . '.', ['seat' => $seat, 'vp' => $vp, 'credits' => $cr]);
}

function sar_start_game(array &$g): void {
    if ($g['status'] !== 'lobby') throw new SarError('Already started');
    $np = count($g['players']);
    if ($np < 2) throw new SarError('Need at least 2 players');

    $cards = sar_cards_data();
    $component = []; $missionT = [1 => [], 2 => [], 3 => []]; $events = [];
    foreach ($cards as $cid => $c) {
        $copies = $c['copies'];
        if (in_array($c['type'], ['Engine', 'Tank', 'Payload', 'Support', 'Tech'], true)) {
            // Deck scaling: 3p remove 1 copy of every card with 3+ copies;
            // 2p remove 1 copy of every card and a 2nd of every card with 5+ copies.
            if ($np === 3 && $copies >= 3) $copies -= 1;
            if ($np === 2) { $copies -= 1; if ($c['copies'] >= 5) $copies -= 1; }
            $component = array_merge($component, sar_copies($cid, max(0, $copies)));
        } elseif ($c['type'] === 'Mission') {
            if (in_array($cid, SAR_STANDING_CONTRACTS, true)) continue; // standing contracts are not in the deck
            $tier = (int)substr($c['tier'], -1);
            $missionT[$tier][] = "$cid#1";
        } elseif ($c['type'] === 'Event') {
            $events[] = "$cid#1";
        }
    }
    shuffle($component); shuffle($missionT[1]); shuffle($events);
    // Tier 2/3 stay face-down until unlocked (shuffled when merged in).
    $g['decks'] = [
        'component' => $component, 'componentDiscard' => [],
        'event' => $events, 'eventDiscard' => [],
        'mission' => $missionT[1], 'missionT2' => $missionT[2], 'missionT3' => $missionT[3],
        'missionDiscard' => [],
    ];

    // Random first player, seats keep join order.
    $g['firstSeat'] = random_int(0, $np - 1);
    foreach ($g['players'] as $i => &$p) {
        $orderPos = ($i - $g['firstSeat'] + $np) % $np; // 0 = first player
        $p['credits'] = SAR_START_CREDITS[$orderPos];
        $p['hand'] = ['E02#s' . $i, 'T01#s' . $i, 'S01#s' . $i]; // starting Basic kit
    }
    unset($p);

    $g['market'] = array_splice($g['decks']['component'], 0, SAR_MARKET_SIZE);
    $g['missions'] = array_splice($g['decks']['mission'], 0, 3);
    $g['standing'] = SAR_STANDING_CONTRACTS;

    // Opening guarantee: make sure at least one easy Tier-1 mission (payload-only /
    // deploy) is in the opening display so round 1 is never a dead hand.
    $easy = ['M01', 'M10', 'M14'];
    $hasEasy = false;
    foreach ($g['missions'] as $mu) if (in_array(explode('#', $mu)[0], $easy, true)) { $hasEasy = true; break; }
    if (!$hasEasy) {
        foreach ($g['decks']['mission'] as $k => $mu) {
            if (in_array(explode('#', $mu)[0], $easy, true)) {
                $displaced = $g['missions'][0];
                $g['missions'][0] = $mu;
                array_splice($g['decks']['mission'], $k, 1);
                $g['decks']['mission'][] = $displaced;
                break;
            }
        }
    }

    $g['status'] = 'playing';
    $g['round'] = 1;
    sar_log($g, 'setup', 'Game started with ' . $np . ' agencies. ' .
        sar_pname($g, $g['firstSeat']) . ' is the first player.');
    sar_begin_planning($g);
}
