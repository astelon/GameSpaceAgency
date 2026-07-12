<?php
// Generates PHP<->JS parity fixtures: for each rule-preview function the
// frontend re-implements (craftReliability, passiveLanding, checkMission,
// simulatePlan — see webGame/js/data.js), this script builds a battery of
// inputs, runs the *real* PHP engine function against them, and records the
// input + the PHP-authoritative output as JSON. webGame/tools/test_parity.mjs
// then replays the same inputs through the JS mirror and asserts the two
// agree — turning silent PHP<->JS rules drift into a failing test.
// Usage: php webGame/tools/gen_parity_fixtures.php > webGame/tests/fixtures/parity.json

require __DIR__ . '/../api/engine/bootstrap.php';

function baseGame(): array {
    $g = sar_new_game('FX', 'hotseat', 'tok');
    sar_add_player($g, 'Ada', 'tok');
    sar_add_player($g, 'Bo', 'tok');
    sar_start_game($g);
    $g['event'] = null;
    $g['twIdx'] = 0;
    return $g;
}

// Build a craft directly (bypassing hand/acquire mechanics — these are pure
// state-in/state-out preview functions, they don't care how the craft got here).
function mkCraft(array &$g, int $seat, array $cards, array $overrides = []): array {
    $node = $overrides['node'] ?? 'earth';
    $id = sar_new_craft($g, $seat, $cards, $node);
    foreach ($overrides as $k => $v) $g['crafts'][$id][$k] = $v;
    return $g['crafts'][$id];
}

// Minimal g-snapshot each JS mirror function actually reads.
function gSnap(array $g): array {
    return [
        'event' => $g['event'],
        'round' => $g['round'],
        'twIdx' => $g['twIdx'],
        'players' => array_map(fn($p) => ['tableau' => $p['tableau']], $g['players']),
        'crafts' => $g['crafts'],
        'missions' => $g['missions'],
    ];
}

$out = ['reliability' => [], 'landing' => [], 'missions' => [], 'plan' => []];

// --------------------------------------------------------------- reliability
function relFixture(array &$out, string $label, array $g, array $craft, bool $useFc): void {
    [$rel, $mods] = sar_craft_reliability($g, $craft, $useFc);
    $out['reliability'][] = ['label' => $label, 'g' => gSnap($g), 'craft' => $craft, 'useFc' => $useFc,
        'expect' => ['rel' => $rel, 'mods' => $mods]];
}

$g = baseGame();
relFixture($out, 'no engine', $g, mkCraft($g, 0, ['T01#1']), false);
relFixture($out, 'basic engine, no mods', $g, mkCraft($g, 0, ['E02#1']), false);
relFixture($out, 'reusable engine + Reusable Refurb tech', $g,
    (function () use (&$g) { $g['players'][0]['tableau'][] = 'C01#1'; return mkCraft($g, 0, ['E01#1']); })(), false);
$g = baseGame();
relFixture($out, 'cryo engine+tank + Cryo Handling tech', $g,
    (function () use (&$g) { $g['players'][0]['tableau'][] = 'C02#1'; return mkCraft($g, 0, ['E03#1', 'T02#1']); })(), false);
$g = baseGame();
$g['players'][0]['tableau'][] = 'C03#1';
relFixture($out, 'Precision Guidance tech', $g, mkCraft($g, 0, ['E02#1']), false);
$g = baseGame();
relFixture($out, 'Flight Computer assist', $g, mkCraft($g, 0, ['E02#1']), true);
$g = baseGame();
$g['event'] = 'EV01#1';
relFixture($out, 'Solar Storm event', $g, mkCraft($g, 0, ['E02#1']), false);
$g = baseGame();
$g['event'] = 'EV09#1';
relFixture($out, 'Solar Flare Watch event', $g, mkCraft($g, 0, ['E02#1']), false);
$g = baseGame();
$g['players'][0]['tableau'] = ['C01#1', 'C03#1'];
relFixture($out, 'stacked mods: Reusable Refurb + Precision Guidance + FC', $g,
    mkCraft($g, 0, ['E01#1']), true);

// -------------------------------------------------------------------- landing
// sar_passive_landing() returns a mechanical {method,uid} for execution, while
// the JS mirror returns a human label for the UI — different shapes by
// design. The outcome that actually matters for parity is (a) does the craft
// survive automatically, and (b) for the single-candidate-card fixtures
// below, does JS attribute it to the same card.
function landFixture(array &$out, string $label, array $craft, string $surface): void {
    $result = sar_passive_landing($craft, $surface);
    $cardName = ($result && $result['uid']) ? sar_card($result['uid'])['name'] : null;
    $out['landing'][] = ['label' => $label, 'craft' => $craft, 'surface' => $surface,
        'expect' => ['crashes' => $result === null, 'cardName' => $cardName]];
}

$g = baseGame();
landFixture($out, 'moon: legs + engine', mkCraft($g, 0, ['E02#1', 'S14#1'], ['node' => 'moon']), 'moon');
landFixture($out, 'moon: lander + engine', mkCraft($g, 0, ['E02#1', 'P06#1'], ['node' => 'moon']), 'moon');
landFixture($out, 'moon: no engine crashes', mkCraft($g, 0, ['P06#1'], ['node' => 'moon']), 'moon');
landFixture($out, 'earth: reusable chute', mkCraft($g, 0, ['S04#1'], ['node' => 'subEarth']), 'earth');
landFixture($out, 'earth: single-use chute', mkCraft($g, 0, ['S02#1'], ['node' => 'subEarth']), 'earth');
landFixture($out, 'earth: legs + engine, no chute', mkCraft($g, 0, ['E02#1', 'S14#1'], ['node' => 'subEarth']), 'earth');
landFixture($out, 'earth: uncrewed airbags', mkCraft($g, 0, ['S16#1'], ['node' => 'subEarth']), 'earth');
landFixture($out, 'earth: crewed airbags cannot save it',
    mkCraft($g, 0, ['P04#1', 'T05#1', 'S16#1'], ['node' => 'subEarth']), 'earth');
landFixture($out, 'mars: lander, no engine, no chutes checked off-earth',
    mkCraft($g, 0, ['P06#1'], ['node' => 'subMars']), 'mars');
landFixture($out, 'mars: nothing crashes', mkCraft($g, 0, ['T01#1'], ['node' => 'subMars']), 'mars');

// ------------------------------------------------------------------- missions
function missionFixture(array &$out, string $label, string $mid, array $g, array $craft): void {
    $check = sar_mission_check($g, $mid, $craft);
    $out['missions'][] = ['label' => $label, 'mid' => $mid, 'g' => gSnap($g), 'craft' => $craft,
        'expect' => $check !== null];
}

$g = baseGame();
missionFixture($out, 'M01 LEO Deployment: uncrewed payload at LEO', 'M01', $g,
    mkCraft($g, 0, ['P01#1'], ['node' => 'leo']));
missionFixture($out, 'M01 negative: crewed payload does not count', 'M01', $g,
    mkCraft($g, 0, ['P04#1', 'T05#1'], ['node' => 'leo']));
missionFixture($out, 'M02 Lunar Flyby: Moon Orbit then back to Earth', 'M02', $g,
    mkCraft($g, 0, [], ['node' => 'earth', 'history' => ['earth', 'moonOrbit', 'earth']]));
missionFixture($out, 'M03 Lunar Landing: Lander payload, no engine', 'M03', $g,
    mkCraft($g, 0, ['P06#1'], ['node' => 'moon']));
missionFixture($out, 'M03 negative: no lander, no engine', 'M03', $g,
    mkCraft($g, 0, ['T01#1'], ['node' => 'moon']));
missionFixture($out, 'M04 Mars Orbit Insertion: Mass 2+ payload at Mars High Orbit', 'M04', $g,
    mkCraft($g, 0, ['P01#1'], ['node' => 'marsHigh']));
missionFixture($out, 'M05 Deep Space Probe: Scientific Mass2+ + Sensor Array + 2 Energy', 'M05', $g,
    mkCraft($g, 0, ['P03#1', 'S11#1'], ['node' => 'marsZoi', 'history' => ['marsZoi'], 'energy' => 2]));
missionFixture($out, 'M06 Crewed Station Visit: docked, crewed, Docking card, engine', 'M06', $g,
    mkCraft($g, 0, ['P04#1', 'T05#1', 'S05#1', 'E01#1'], ['node' => 'earth', 'docked' => true]));
missionFixture($out, 'M07 Emergency Resupply: visited LEO, Mass2+ payload, back at Earth', 'M07', $g,
    mkCraft($g, 0, ['P01#1'], ['node' => 'earth', 'history' => ['leo', 'earth']]));
missionFixture($out, 'M08 Science Relay: visited GEO, Scientific/Electronics payload, 1 Energy', 'M08', $g,
    mkCraft($g, 0, ['P02#1'], ['node' => 'earth', 'history' => ['geo', 'earth'], 'energy' => 1]));
$m09g = baseGame();
mkCraft($m09g, 0, ['P01#1'], ['node' => 'geo', 'deployed' => true]); // deployed Satellite in orbit
missionFixture($out, 'M09 Orbital Service Check: LEO-GEO-LEO seq, engine, deployed Satellite', 'M09', $m09g,
    mkCraft($m09g, 0, ['E01#1'], ['node' => 'leo', 'history' => ['leo', 'geo', 'leo']]));
missionFixture($out, 'M10 Capsule Recovery: landed at Earth via reentry, Mass1+ payload', 'M10', $g,
    mkCraft($g, 0, ['P01#1'], ['node' => 'earth', 'usedReentry' => true]));
missionFixture($out, 'M11 Reusable Flight Test: sub-orbital hop, reusable payload + reusable reentry', 'M11', $g,
    mkCraft($g, 0, ['P07#1'], ['node' => 'earth', 'history' => ['earth', 'subEarth', 'earth'], 'usedReusableReentry' => true]));
missionFixture($out, 'M12 Lunar Sample Return: Moon visited, Cargo Return Capsule, reentry', 'M12', $g,
    mkCraft($g, 0, ['P07#1'], ['node' => 'earth', 'history' => ['moon', 'earth'], 'usedReentry' => true]));
missionFixture($out, 'M13 Tourist Hop: sub-orbital hop, crewed payload, reentry', 'M13', $g,
    mkCraft($g, 0, ['P04#1', 'T05#1'], ['node' => 'earth', 'history' => ['earth', 'subEarth', 'earth'], 'usedReentry' => true]));
missionFixture($out, 'M14 Weather Satellite: deployed Satellite at GEO', 'M14', $g,
    mkCraft($g, 0, ['P01#1'], ['node' => 'geo', 'deployed' => true]));
missionFixture($out, 'M15 Sounding Flight: sub-orbital hop, Scientific payload, 1 Energy', 'M15', $g,
    mkCraft($g, 0, ['P02#1'], ['node' => 'earth', 'history' => ['earth', 'subEarth', 'earth'], 'energy' => 1]));
missionFixture($out, 'M16 Lunar Orbiter: deployed Satellite at Moon Orbit', 'M16', $g,
    mkCraft($g, 0, ['P05#1'], ['node' => 'moonOrbit', 'deployed' => true]));
missionFixture($out, 'M17 Crewed Lunar Flyby: Moon Orbit visited, crewed, reentry', 'M17', $g,
    mkCraft($g, 0, ['P04#1', 'T05#1'], ['node' => 'earth', 'history' => ['moonOrbit', 'earth'], 'usedReentry' => true]));
missionFixture($out, 'M18 Station Assembly: On-Orbit Station at GEO', 'M18', $g,
    mkCraft($g, 0, ['P08#1'], ['node' => 'geo', 'isStation' => true]));
missionFixture($out, 'M19 Mars Landing: Lander payload, no engine', 'M19', $g,
    mkCraft($g, 0, ['P06#1'], ['node' => 'mars']));
missionFixture($out, 'M20 Asteroid Rendezvous: Sun Orbit visited, Scientific Mass2+, Sensor Array, 2 Energy', 'M20', $g,
    mkCraft($g, 0, ['P03#1', 'S11#1'], ['node' => 'sunOrbit', 'history' => ['sunOrbit'], 'energy' => 2]));
missionFixture($out, 'M21 Suborbital Test Flight (standing contract): sub-orbital hop, no payload', 'M21', $g,
    mkCraft($g, 0, [], ['node' => 'earth', 'history' => ['earth', 'subEarth', 'earth']]));

// ----------------------------------------------------------------------- plan
// Full-plan fixtures: dry-run sar_run_flight() through the real engine is the
// ground truth for what simulatePlan() should predict.
function planFixture(array &$out, string $label, array $g, array $craft, array $plan): void {
    $craftId = $craft['id'];
    $probe = $g;
    $ok = true;
    try {
        sar_run_flight($probe, $craftId, $plan, 1, true);
    } catch (SarError $e) {
        $ok = false;
    }
    $expect = ['ok' => $ok];
    if ($ok) {
        $final = $probe['crafts'][$craftId];
        $expect['finalCraft'] = ['node' => $final['node'], 'range' => $final['range'], 'energy' => $final['energy'],
            'history' => $final['history'], 'docked' => $final['docked']];
    }
    $out['plan'][] = ['label' => $label, 'g' => gSnap($g), 'craft' => $craft, 'plan' => $plan, 'expect' => $expect];
}

$g = baseGame();
$craft = mkCraft($g, 0, ['E02#1', 'T01#1'], ['node' => 'earth', 'range' => 5]);
planFixture($out, 'simple hop: Earth -> Sub-Orbital Earth', $g, $craft,
    ['path' => ['earth', 'subEarth']]);

$g = baseGame();
$g['twIdx'] = 1; // SAR_TW_CYCLE[1] = 2
$craft = mkCraft($g, 0, ['E04#1'], ['node' => 'sunOrbit', 'range' => 5]);
planFixture($out, 'Transfer Window crossing: Sun Orbit -> Mars ZOI', $g, $craft,
    ['path' => ['sunOrbit', 'marsZoi']]);

$g = baseGame();
$station = mkCraft($g, 0, ['P08#1', 'S12#1'], ['node' => 'geo', 'isStation' => true, 'deployed' => true]);
$craft = mkCraft($g, 0, ['E01#1', 'S05#1'], ['node' => 'leo', 'range' => 5, 'energy' => 2]);
planFixture($out, 'launch + dock at GEO with a Habitation Ring station', $g, $craft,
    ['path' => ['leo', 'geo'], 'dock' => 1]);

$g = baseGame();
$craft = mkCraft($g, 0, ['E01#1', 'P01#1', 'S07#1'], ['node' => 'leo', 'range' => 5]);
planFixture($out, 'deploy a Comm Satellite (+ Solar Panel support) at LEO', $g, $craft,
    ['path' => ['leo'], 'deploys' => [['step' => 0, 'payload' => 'P01#1', 'supports' => ['S07#1']]]]);

$g = baseGame();
$craft = mkCraft($g, 0, ['E02#1', 'S02#1'], ['node' => 'subEarth', 'range' => 5]);
planFixture($out, 'land at Earth using single-use Recovery Chutes', $g, $craft,
    ['path' => ['subEarth', 'earth'], 'landing' => [1 => ['method' => 'reentry', 'card' => 'S02#1']]]);

$g = baseGame();
$craft = mkCraft($g, 0, ['E02#1', 'T01#1'], ['node' => 'earth', 'range' => 1]);
planFixture($out, 'failure: not enough Range for a 2-hop plan', $g, $craft,
    ['path' => ['earth', 'subEarth', 'leo']]);

fwrite(STDOUT, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
