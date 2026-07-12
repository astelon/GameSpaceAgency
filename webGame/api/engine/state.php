<?php
// Space Agency Race - derived game values, craft helpers, and the state schema.
//
// Every field a player or craft record carries is initialized once in
// sar_add_player() / sar_new_craft() below; callers can rely on the field
// being present rather than guarding with ?? or empty(). sar_validate_state()
// documents and enforces that schema — it is not called by sar_apply() itself
// (that per-action cost is not worth paying in production) but tests call it
// after every accepted action.

require_once __DIR__ . '/constants.php';

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
        'docked' => false, 'dockedHab' => false, 'visitedLeoAfterStranded' => false,
        'depotUsedRound' => 0, 'tugUsedTurn' => false,
        // Per-card operated-ability / one-shot flags, bolted on by whichever
        // card first uses them — kept here so every craft has the full field
        // set from creation instead of relying on isset()/?? at each site.
        'relayUsedRound' => false, 'p03Round' => 0, 's11Round' => 0,
        'ceramicAeroUsed' => false, 'stagedEngineFlight' => false,
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
// State validation

const SAR_PLAYER_FIELDS = ['seat', 'name', 'color', 'token', 'credits', 'vp', 'level',
    'pendingLevel', 'hand', 'tableau', 'planningDone', 'turnsUsed', 'passed', 'flushedTurn',
    'missionsCompleted', 'techOrbVpRound', 'visited', 'standingDone', 'connected'];

const SAR_CRAFT_FIELDS = ['id', 'owner', 'name', 'node', 'cards', 'range', 'energy',
    'deployed', 'isStation', 'activated', 'history', 'launchRound',
    'usedReentry', 'usedReusableReentry', 'docked', 'dockedHab', 'visitedLeoAfterStranded',
    'depotUsedRound', 'tugUsedTurn', 'relayUsedRound', 'p03Round', 's11Round',
    'ceramicAeroUsed', 'stagedEngineFlight'];

function sar_validate_state(array $g): void {
    $np = count($g['players']);
    foreach ($g['players'] as $seat => $p) {
        foreach (SAR_PLAYER_FIELDS as $f) {
            if (!array_key_exists($f, $p)) throw new SarInvariantError("player $seat missing field '$f'");
        }
        if ($p['seat'] !== $seat) throw new SarInvariantError("player $seat has mismatched seat {$p['seat']}");
        if ($p['credits'] < 0) throw new SarInvariantError("player $seat has negative credits");
        if ($p['vp'] < 0) throw new SarInvariantError("player $seat has negative vp");
        if ($p['level'] < 1 || $p['level'] > 3) throw new SarInvariantError("player $seat has invalid level {$p['level']}");
    }
    foreach ($g['crafts'] as $id => $craft) {
        foreach (SAR_CRAFT_FIELDS as $f) {
            if (!array_key_exists($f, $craft)) throw new SarInvariantError("craft $id missing field '$f'");
        }
        if ($craft['id'] !== $id) throw new SarInvariantError("craft $id has mismatched id {$craft['id']}");
        if ($craft['owner'] < 0 || $craft['owner'] >= $np) throw new SarInvariantError("craft $id has invalid owner {$craft['owner']}");
        if ($craft['node'] !== 'assembly' && !isset(SAR_NODES[$craft['node']])) throw new SarInvariantError("craft $id has invalid node {$craft['node']}");
        if ($craft['range'] < 0) throw new SarInvariantError("craft $id has negative range");
        if ($craft['energy'] < 0) throw new SarInvariantError("craft $id has negative energy");
    }
    // No card duplication: every instance uid appears in at most one zone.
    $seen = [];
    $zones = [$g['decks']['component'] ?? [], $g['decks']['componentDiscard'] ?? [],
        $g['decks']['event'] ?? [], $g['decks']['eventDiscard'] ?? [],
        $g['decks']['mission'] ?? [], $g['decks']['missionT2'] ?? [], $g['decks']['missionT3'] ?? [],
        $g['decks']['missionDiscard'] ?? [], $g['market'], $g['missions']];
    foreach ($g['players'] as $p) { $zones[] = $p['hand']; $zones[] = $p['tableau']; }
    foreach ($g['crafts'] as $c) $zones[] = $c['cards'];
    foreach ($zones as $zone) {
        foreach ($zone as $uid) {
            if ($uid === null) continue;
            if (isset($seen[$uid])) throw new SarInvariantError("card duplicated across zones: $uid");
            $seen[$uid] = true;
        }
    }
}
