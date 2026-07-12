<?php
// Unit tests for sub-orbital decay: craft still at a Sub-Orbital node at the
// end of the round must touch down — safely with a passive lander (parachute
// on Earth, airbags if uncrewed, a Lander payload, or Landing Legs + Engine),
// otherwise they crash. Also checks the deploy restriction at sub-orbital nodes.
// Usage: php webGame/tools/test_suborbital.php

require_once __DIR__ . '/../api/engine/bootstrap.php';

$fails = 0;
function check(bool $cond, string $label): void {
    global $fails;
    if ($cond) { echo "  ok  $label\n"; }
    else { $fails++; echo "  FAIL $label\n"; }
}

function fresh_game(): array {
    $g = sar_new_game('SUBTEST', 'hotseat', 'tok');
    sar_add_player($g, 'Ada', 'tok');
    sar_add_player($g, 'Bo', 'tok');
    sar_start_game($g);
    $g['missions'] = []; // isolate from random mission claims
    return $g;
}

function put_craft(array &$g, array $cards, string $node): string {
    $id = sar_new_craft($g, 0, $cards, $node);
    $g['crafts'][$id]['launchRound'] = $g['round'];
    return $id;
}

echo "sub-orbital decay:\n";

// 1. Parachute at Sub-Orbital Earth: auto-lands, chute expended, +1 recovery Credit.
$g = fresh_game();
$credits = $g['players'][0]['credits'];
$id = put_craft($g, ['S02#t1'], 'subEarth');
sar_suborbital_decay($g);
check(isset($g['crafts'][$id]) && $g['crafts'][$id]['node'] === 'earth', 'parachute craft touches down on Earth');
check(in_array('S02#t1', $g['decks']['componentDiscard'], true), 'Recovery Chutes are expended');
check($g['players'][0]['credits'] === $credits + 1, 'recovery credit paid');

// 2. Reusable parafoil preferred over expending anything.
$g = fresh_game();
$id = put_craft($g, ['S04#t1', 'S02#t2'], 'subEarth');
sar_suborbital_decay($g);
check(in_array('S04#t1', $g['crafts'][$id]['cards'], true) && in_array('S02#t2', $g['crafts'][$id]['cards'], true),
    'reusable parafoil lands the craft, nothing is discarded');

// 3. No landing device, no legs: the craft crashes and is destroyed.
$g = fresh_game();
$id = put_craft($g, ['E02#t1', 'T01#t1'], 'subEarth'); // engine but no legs = needed a command turn
sar_suborbital_decay($g);
check(!isset($g['crafts'][$id]), 'engine-only craft crashes (propulsive landing needed a command turn)');
check(in_array('E02#t1', $g['decks']['componentDiscard'], true) && in_array('T01#t1', $g['decks']['componentDiscard'], true),
    'crashed cards go to the discard pile');

// 4. Landing Legs + Engine: hands-off propulsive touchdown, nothing expended.
$g = fresh_game();
$id = put_craft($g, ['E02#t1', 'S14#t1'], 'subEarth');
sar_suborbital_decay($g);
check(isset($g['crafts'][$id]) && $g['crafts'][$id]['node'] === 'earth', 'legs + engine auto-land safely');
check(count($g['crafts'][$id]['cards']) === 2, 'legs landing expends nothing');

// 5. Legs without an engine cannot brake.
$g = fresh_game();
$id = put_craft($g, ['S14#t1'], 'subEarth');
sar_suborbital_decay($g);
check(!isset($g['crafts'][$id]), 'legs without an engine crash');

// 6. Parachutes are Earth-only: at Sub-Orbital Mars they do not save the craft.
$g = fresh_game();
$id = put_craft($g, ['S02#t1'], 'subMars');
sar_suborbital_decay($g);
check(!isset($g['crafts'][$id]), 'parachute-only craft crashes at Mars (air too thin)');

// 7. Airbags land an uncrewed craft on Mars…
$g = fresh_game();
$id = put_craft($g, ['S16#t1', 'P05#t1'], 'subMars');
sar_suborbital_decay($g);
check(isset($g['crafts'][$id]) && $g['crafts'][$id]['node'] === 'mars', 'airbags land uncrewed craft on Mars');
check(in_array('S16#t1', $g['decks']['componentDiscard'], true), 'airbag shell is expended');

// 8. …but not a crewed one.
$g = fresh_game();
$id = put_craft($g, ['S16#t1', 'P04#t1'], 'subEarth'); // Crew Capsule aboard
sar_suborbital_decay($g);
check(!isset($g['crafts'][$id]), 'airbags cannot save a crewed craft');

// 9. Moon: no atmosphere — legs + engine land, chutes/airbags do not.
$g = fresh_game();
$a = put_craft($g, ['E02#t1', 'S14#t1'], 'subMoon');
$b = put_craft($g, ['S02#t2', 'S16#t2'], 'subMoon');
sar_suborbital_decay($g);
check(isset($g['crafts'][$a]) && $g['crafts'][$a]['node'] === 'moon', 'legs + engine set down on the Moon');
check(!isset($g['crafts'][$b]), 'chute/airbag craft crashes on the airless Moon');

// 10. Lander payload sets the craft down (Earth/Mars without engine; Moon needs engine too).
$g = fresh_game();
$a = put_craft($g, ['P06#t1'], 'subMars');
$b = put_craft($g, ['P06#t2'], 'subMoon');
$c = put_craft($g, ['P06#t3', 'E02#t3'], 'subMoon');
sar_suborbital_decay($g);
check(isset($g['crafts'][$a]) && $g['crafts'][$a]['node'] === 'mars', 'Lander payload sets down on Mars');
check(!isset($g['crafts'][$b]), 'Lander without an engine cannot land on the Moon (propulsive only)');
check(isset($g['crafts'][$c]) && $g['crafts'][$c]['node'] === 'moon', 'Lander + engine sets down on the Moon');

// 11. Craft at stable nodes are untouched.
$g = fresh_game();
$a = put_craft($g, ['P01#t1'], 'leo');
$b = put_craft($g, ['P01#t2'], 'moonOrbit');
sar_suborbital_decay($g);
check($g['crafts'][$a]['node'] === 'leo' && $g['crafts'][$b]['node'] === 'moonOrbit', 'stable orbits are unaffected');

// 12. Maintenance integration: a chute craft comes down and is recovered on Earth.
$g = fresh_game();
$g['phase'] = 'action';
foreach ($g['players'] as &$p) $p['passed'] = true;
unset($p);
$id = put_craft($g, ['S04#t9'], 'subEarth');
sar_maintenance($g);
check(!isset($g['crafts'][$id]), 'landed craft is recovered during the same Maintenance');
check(in_array('S04#t9', $g['players'][0]['hand'], true), 'reusable parafoil returns to hand after recovery');

// 13. Deploying a persistent asset at a sub-orbital node is rejected.
$g = fresh_game();
$id = put_craft($g, ['P01#t1'], 'subEarth');
try {
    sar_deploy($g, $id, 'P01#t1', [], false);
    check(false, 'deploy at Sub-Orbital Earth is rejected');
} catch (SarError $e) {
    check(str_contains($e->getMessage(), 'sub-orbital'), 'deploy at Sub-Orbital Earth is rejected');
}

echo $fails ? "\n$fails FAILURES\n" : "\nALL SUBORBITAL TESTS PASSED\n";
exit($fails ? 1 : 0);
