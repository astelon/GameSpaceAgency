<?php
// Space Agency Race - server-authoritative rules engine.
// State is a plain associative array (JSON-serializable). Every mutation goes
// through sar_apply() which validates against the rules in Space_Agency.md.

require_once __DIR__ . '/cards_data.php';
require_once __DIR__ . '/map.php';

class SarError extends Exception {}

function sar_card(string $uid): array {
    static $cards = null;
    if ($cards === null) $cards = sar_cards_data();
    $cid = explode('#', $uid)[0];
    if (!isset($cards[$cid])) throw new SarError("Unknown card $cid");
    return $cards[$cid];
}

function sar_has_tag(string $uid, string $tag): bool {
    return in_array($tag, sar_card($uid)['tags'], true);
}

const SAR_COLORS = ['#e4572e', '#2e86ab', '#57a773', '#f3a712'];
const SAR_START_CREDITS = [5, 6, 7, 8];
const SAR_LEVEL_TURNS = [1 => 2, 2 => 3, 3 => 4];
const SAR_LEVEL_COST = [2 => 6, 3 => 14];
const SAR_HAND_LIMIT = 5;
const SAR_MAX_CRAFT = 6;
const SAR_FLUSH_COST = 2;
const SAR_ROUNDS = 8;
const SAR_STORM_EVENTS = ['EV01', 'EV06', 'EV09'];

// ---------------------------------------------------------------------------
// Logging / animation events. Client animates entries that carry `data`.
function sar_log(array &$g, string $type, string $text, array $data = []): void {
    $g['logSeq']++;
    $g['log'][] = ['seq' => $g['logSeq'], 'round' => $g['round'], 'type' => $type,
                   'text' => $text, 'data' => $data ?: null];
    if (count($g['log']) > 400) array_splice($g['log'], 0, count($g['log']) - 400);
}

function sar_pname(array $g, int $seat): string { return $g['players'][$seat]['name']; }

// ---------------------------------------------------------------------------
// Game creation & lobby

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

    $g['market'] = array_splice($g['decks']['component'], 0, 5);
    $g['missions'] = array_splice($g['decks']['mission'], 0, 3);
    $g['status'] = 'playing';
    $g['round'] = 1;
    sar_log($g, 'setup', 'Game started with ' . $np . ' agencies. ' .
        sar_pname($g, $g['firstSeat']) . ' is the first player.');
    sar_begin_planning($g);
}

// ---------------------------------------------------------------------------
// Derived values

function sar_hand_limit(array $g): int {
    return sar_event_id($g) === 'EV08' ? 7 : SAR_HAND_LIMIT;
}

function sar_event_id(array $g): ?string {
    return $g['event'] ? explode('#', $g['event'])[0] : null;
}

function sar_storm_active(array $g): bool {
    return in_array(sar_event_id($g), SAR_STORM_EVENTS, true);
}

function sar_has_tech(array $g, int $seat, string $cid): bool {
    foreach ($g['players'][$seat]['tableau'] as $uid) {
        if (explode('#', $uid)[0] === $cid) return true;
    }
    return false;
}

// Current Transfer Window cost for a given player (tech + event modifiers).
function sar_tw_cost(array $g, int $seat): int {
    $tw = SAR_TW_CYCLE[$g['twIdx']];
    $ev = sar_event_id($g);
    if ($ev === 'EV06') $tw = min(5, $tw + 2);
    if ($ev === 'EV07') $tw = max(0, $tw - 2);
    if (sar_has_tech($g, $seat, 'C05')) $tw = max(0, $tw - 1);
    return $tw;
}

function sar_command_turns(array $g, int $seat): int {
    return SAR_LEVEL_TURNS[$g['players'][$seat]['level']];
}

// Craft helpers -------------------------------------------------------------

function sar_craft_cards(array $craft, ?string $type = null, ?string $tag = null): array {
    $out = [];
    foreach ($craft['cards'] as $uid) {
        $c = sar_card($uid);
        if ($type !== null && $c['type'] !== $type) continue;
        if ($tag !== null && !in_array($tag, $c['tags'], true)) continue;
        $out[] = $uid;
    }
    return $out;
}

function sar_craft_engine(array $craft): ?string {
    $e = sar_craft_cards($craft, 'Engine');
    return $e[0] ?? null;
}

function sar_craft_payload(array $craft): ?string {
    $p = sar_craft_cards($craft, 'Payload');
    return $p[0] ?? null;
}

// Total rocket mass for launch capability checks (tanks + payload + support with mass).
function sar_craft_mass(array $g, array $craft): int {
    $mass = 0; $seat = $craft['owner'];
    foreach ($craft['cards'] as $uid) {
        $c = sar_card($uid);
        if ($c['mass'] === null) continue;
        $m = $c['mass'];
        if ($c['type'] === 'Payload' && sar_has_tech($g, $seat, 'C04')) $m = max(1, $m - 1); // Modular Payloads
        if ($c['type'] === 'Engine') continue; // engines are massless for lift
        $mass += $m;
    }
    return $mass;
}

function sar_craft_thrust(array $g, array $craft): int {
    $eng = sar_craft_engine($craft);
    if (!$eng) return 0;
    $t = sar_card($eng)['thrust'] ?? 0;
    // Hybrid Cycle: +1 Thrust while the rocket includes a Cryogenic tank
    if (explode('#', $eng)[0] === 'E05' && sar_craft_cards($craft, null, 'Cryogenic')) $t += 1;
    return $t;
}

// Effective reliability for a launch/relaunch check (before d10 roll).
function sar_craft_reliability(array $g, array $craft, bool $useFlightComputer): array {
    $eng = sar_craft_engine($craft);
    if (!$eng) return [0, ['no engine']];
    $c = sar_card($eng);
    $rel = $c['reliability'] ?? 5;
    $mods = ["base {$rel}"];
    $seat = $craft['owner'];
    if (sar_has_tech($g, $seat, 'C01') && in_array('Reusable', $c['tags'], true)) { $rel += 1; $mods[] = 'Reusable Refurb +1'; }
    if (sar_has_tech($g, $seat, 'C02') && sar_craft_cards($craft, null, 'Cryogenic')) { $rel += 1; $mods[] = 'Cryo Handling +1'; }
    if (sar_has_tech($g, $seat, 'C03')) { $rel += 1; $mods[] = 'Precision Guidance +1'; }
    if ($useFlightComputer) { $rel += 1; $mods[] = 'Flight Computer +1'; }
    $ev = sar_event_id($g);
    if ($ev === 'EV01') { $rel -= 2; $mods[] = 'Solar Storm -2'; }
    if ($ev === 'EV09') { $rel -= 1; $mods[] = 'Solar Flare Watch -1'; }
    return [$rel, $mods];
}

// Power = sum of generator outputs available at the craft's node.
function sar_craft_power(array $g, array $craft): int {
    $power = 0;
    foreach ($craft['cards'] as $uid) {
        $c = sar_card($uid);
        if ($c['energyMode'] !== 'Gen') continue;
        $cid = explode('#', $uid)[0];
        if ($cid === 'S07' && !sar_in_space($craft['node'])) continue; // Solar Panel: space only
        $power += $c['energy'];
    }
    // Deep Space Network: deployed assets beyond Earth ZOI get +1 Power
    if ($power > 0 && !empty($craft['deployed']) && sar_beyond_zoi($craft['node'])
        && sar_has_tech($g, $craft['owner'], 'C10')) $power += 1;
    return $power;
}

// Spend energy from a craft, automatically discharging Battery Packs if needed.
// Returns false (no mutation) if the craft cannot pay.
function sar_spend_energy(array &$g, string $craftId, int $n, string $why): bool {
    $craft = &$g['crafts'][$craftId];
    $avail = $craft['energy'];
    $batteries = sar_craft_cards($craft, null, 'Power');
    $batteries = array_values(array_filter($batteries, fn($u) => sar_card($u)['energyMode'] === 'Burst'));
    $potential = $avail;
    foreach ($batteries as $b) $potential += sar_card($b)['energy'];
    if ($potential < $n) return false;
    while ($craft['energy'] < $n && $batteries) {
        $b = array_shift($batteries);
        $craft['energy'] += sar_card($b)['energy'];
        $craft['cards'] = array_values(array_diff($craft['cards'], [$b]));
        $g['decks']['componentDiscard'][] = $b;
        $bc = sar_card($b);
        sar_log($g, 'battery', sar_pname($g, $craft['owner']) . " discharges a {$bc['name']} (+{$bc['energy']} Energy) on {$craft['name']}.",
            ['craft' => $craftId]);
    }
    $craft['energy'] -= $n;
    sar_log($g, 'energy', "{$craft['name']} spends $n Energy ($why).",
        ['craft' => $craftId, 'energy' => -$n]);
    return true;
}

// Can the craft pay n energy (counting batteries) without mutating state?
function sar_can_pay_energy(array $craft, int $n): bool {
    $potential = $craft['energy'];
    foreach ($craft['cards'] as $uid) {
        $c = sar_card($uid);
        if ($c['energyMode'] === 'Burst') $potential += $c['energy'];
    }
    return $potential >= $n;
}

function sar_new_craft(array &$g, int $seat, array $cards, string $node): string {
    $g['craftSeq']++;
    $id = 'c' . $g['craftSeq'];
    $names = array_map(fn($u) => sar_card($u)['name'], $cards);
    $payload = null;
    foreach ($cards as $u) if (sar_card($u)['type'] === 'Payload') $payload = sar_card($u)['name'];
    $engine = null;
    foreach ($cards as $u) if (sar_card($u)['type'] === 'Engine') $engine = sar_card($u)['name'];
    $name = $payload ?: ($engine ? "$engine stack" : ($names[0] ?? 'Craft'));
    $g['crafts'][$id] = [
        'id' => $id, 'owner' => $seat, 'name' => $name, 'node' => $node,
        'cards' => array_values($cards), 'range' => 0, 'energy' => 0,
        'deployed' => false, 'isStation' => false, 'activated' => false,
        'history' => [$node === 'assembly' ? null : $node],
        'launchRound' => null,
        'usedReentry' => false, 'usedReusableReentry' => false,
        'docked' => false, 'visitedLeoAfterStranded' => false,
        'depotUsedRound' => 0, 'tugUsedTurn' => false,
    ];
    if ($node === 'assembly') $g['crafts'][$id]['history'] = [];
    return $id;
}

function sar_player_craft_count(array $g, int $seat): int {
    $n = 0;
    foreach ($g['crafts'] as $c) if ($c['owner'] === $seat) $n++;
    return $n;
}

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

    // 1. Reveal Event
    if ($g['decks']['event']) {
        $g['event'] = array_shift($g['decks']['event']);
        $ev = sar_event_id($g);
        $card = sar_card($g['event']);
        sar_log($g, 'event', "Event: {$card['name']} — {$card['text']}", ['card' => $g['event']]);
        if ($ev === 'EV02') { // Funding Boost: +3 Credits to everyone, immediately
            foreach ($g['players'] as &$p) $p['credits'] += 3;
            unset($p);
            sar_log($g, 'gain', 'Funding Boost: every agency gains 3 Credits.');
        }
        if ($ev === 'EV13' && $g['strandedCrew'] === null) {
            $g['strandedCrew'] = 'unclaimed';
            sar_log($g, 'event', 'A crew is stranded at LEO! First Crewed craft to visit LEO and then return to Earth rescues them for 5 VP.');
        }
    } else {
        $g['event'] = null;
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
    $g['market'] = array_pad(sar_draw_component($g, 5), 5, null);
    sar_log($g, 'flush', $p['name'] . ' pays ' . SAR_FLUSH_COST .
        ' Credits to flush the Card Market — 5 new cards are revealed.', ['seat' => $seat]);
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

    // Technology milestones
    $n = count($p['tableau']);
    if ($n === 2 && $g['milestones']['secondTech'] === null) {
        $g['milestones']['secondTech'] = $seat;
        $p['vp'] += 1;
        sar_log($g, 'milestone', $p['name'] . ' is first to a second Technology: +1 VP.', ['seat' => $seat, 'vp' => 1]);
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
function sar_apply_engineering(array &$g, int $seat, array $a, bool $asAction): ?string {
    $p = &$g['players'][$seat];
    $craftId = $a['craft'] ?? null;
    $add = $a['add'] ?? [];
    $remove = $a['remove'] ?? [];
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
    $cards = $craftId !== null ? $g['crafts'][$craftId]['cards'] : [];
    foreach ($remove as $uid) {
        if (!in_array($uid, $cards, true)) throw new SarError('Component not on craft');
        $cards = array_values(array_diff($cards, [$uid]));
    }
    $cards = array_merge($cards, $add);
    // composition limits
    $count = ['Engine' => 0, 'Tank' => 0, 'Payload' => 0, 'Support' => 0];
    foreach ($cards as $uid) $count[sar_card($uid)['type']]++;
    if ($count['Engine'] > 1) throw new SarError('A rocket may have at most 1 Engine');
    if ($count['Tank'] > 3) throw new SarError('A rocket may have at most 3 Fuel Tanks');
    if ($count['Payload'] > 1) throw new SarError('A rocket may have at most 1 Payload');
    if ($count['Support'] > 3) throw new SarError('A rocket may have at most 3 Support cards');

    if (count($p['hand']) - count($add) + count($remove) > sar_hand_limit($g)) {
        throw new SarError('Removing those components would exceed your hand limit');
    }
    $p['hand'] = array_values(array_diff($p['hand'], $add));
    $p['hand'] = array_merge($p['hand'], $remove);
    if ($craftId !== null) {
        if (!$cards) { // fully disassembled
            unset($g['crafts'][$craftId]);
            sar_log($g, 'engineering', $p['name'] . ' disassembles a rocket.');
            unset($p);
            return null;
        }
        $g['crafts'][$craftId]['cards'] = $cards;
    } else {
        $craftId = sar_new_craft($g, $seat, $cards, 'assembly');
    }
    $names = implode(' + ', array_map(fn($u) => sar_card($u)['name'], $cards));
    sar_log($g, 'engineering', $p['name'] . " configures a rocket: $names.", ['seat' => $seat, 'craft' => $craftId]);
    unset($p);
    return $craftId;
}

function sar_action_engineering(array &$g, int $seat, array $a): void {
    sar_require_turn($g, $seat);
    sar_apply_engineering($g, $seat, $a, true);
    sar_end_command_turn($g, $seat);
}

// ---------------------------------------------------------------------------
// Maintenance & end of round

function sar_maintenance(array &$g): void {
    $g['phase'] = 'maintenance';
    sar_log($g, 'phase', "Round {$g['round']} — Maintenance Phase.", ['phase' => 'maintenance']);

    // 1. Recover craft that returned to Earth.
    foreach ($g['crafts'] as $id => $craft) {
        if ($craft['node'] !== 'earth' || $craft['deployed']) continue;
        if ($craft['launchRound'] === null) continue; // never launched
        $seat = $craft['owner'];
        $p = &$g['players'][$seat];
        $recovered = []; $lost = [];
        foreach ($craft['cards'] as $uid) {
            if (sar_has_tag($uid, 'Reusable')) {
                $p['hand'][] = $uid;
                $recovered[] = sar_card($uid)['name'];
                if (sar_has_tech($g, $seat, 'C01')) { $p['credits'] += 1; }
            } else {
                $g['decks']['componentDiscard'][] = $uid;
                $lost[] = sar_card($uid)['name'];
            }
        }
        $msg = $p['name'] . "'s {$craft['name']} is recovered on Earth.";
        if ($recovered) $msg .= ' Returned to hand: ' . implode(', ', $recovered) . '.';
        if ($lost) $msg .= ' Expended: ' . implode(', ', $lost) . '.';
        if ($recovered && sar_has_tech($g, $seat, 'C01')) $msg .= ' Reusable Refurb pays ' . count($recovered) . ' Credit(s).';
        sar_log($g, 'recover', $msg, ['seat' => $seat]);
        unset($p);
        unset($g['crafts'][$id]);
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

// ---------------------------------------------------------------------------
// Public entry point

function sar_apply(array &$g, int $seat, array $action): void {
    if ($g['status'] === 'finished') throw new SarError('The game is over');
    if ($g['status'] !== 'playing') throw new SarError('The game has not started');
    require_once __DIR__ . '/flight.php';
    require_once __DIR__ . '/missions.php';
    $type = $action['type'] ?? '';
    switch ($type) {
        case 'planning_done': sar_action_planning_done($g, $seat, $action); break;
        case 'acquire':       sar_action_acquire($g, $seat, $action); break;
        case 'flush_market':  sar_action_flush_market($g, $seat); break;
        case 'develop':       sar_action_develop($g, $seat, $action); break;
        case 'engineering':   sar_action_engineering($g, $seat, $action); break;
        case 'launch':        sar_action_launch($g, $seat, $action); break;
        case 'activate':      sar_action_activate($g, $seat, $action); break;
        case 'expand':        sar_action_expand($g, $seat); break;
        case 'pass':          sar_action_pass($g, $seat); break;
        case 'decision':      sar_action_decision($g, $seat, $action); break;
        default: throw new SarError("Unknown action: $type");
    }
    $g['version']++;
}
