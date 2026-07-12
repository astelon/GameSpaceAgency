<?php
// Deterministic scenario tests for the rules engine: missions, deploys,
// incomes, stations, maintenance and end-game scoring.
// Usage: php webGame/tools/test_scenarios.php

require_once __DIR__ . '/../api/engine/bootstrap.php';

$pass = 0; $fail = 0;
function ok(bool $cond, string $label): void {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ✔ $label\n"; }
    else { $fail++; echo "  ✘ FAIL: $label\n"; }
}

function fresh(int $np = 2): array {
    $g = sar_new_game('SCN', 'hotseat', 'tok');
    $names = ['Ada', 'Bo', 'Cy', 'Dee'];
    for ($i = 0; $i < $np; $i++) sar_add_player($g, $names[$i], 'tok');
    sar_start_game($g);
    // strip randomness: no event, clear market influence
    $g['event'] = null;
    $g['firstSeat'] = 0;
    // finish planning
    foreach ($g['players'] as $p) {
        if (!$p['planningDone']) {
            $over = count($g['players'][$p['seat']]['hand']) - 5;
            $discard = $over > 0 ? array_slice($g['players'][$p['seat']]['hand'], 0, $over) : [];
            sar_apply($g, $p['seat'], ['type' => 'planning_done', 'sell' => [], 'discard' => $discard]);
        }
    }
    $g['event'] = null; // in case planning_done kept one
    $g['turnSeat'] = 0;
    return $g;
}

// Give a player specific cards (fresh instances).
function give(array &$g, int $seat, array $cids): array {
    $uids = [];
    static $n = 100;
    foreach ($cids as $cid) { $uids[] = "$cid#t" . (++$n); }
    $g['players'][$seat]['hand'] = array_merge($g['players'][$seat]['hand'], $uids);
    return $uids;
}

echo "— Scenario 1: LEO Deployment (M01) + satellite deploy + income + endgame asset VP\n";
$g = fresh();
$g['missions'] = ['M01#1', 'M14#1', 'M07#1'];
[$eng, $tank, $sat, $solar] = give($g, 0, ['E02', 'T01', 'P01', 'S07']);
$g['players'][0]['hand'] = [$eng, $tank, $sat, $solar]; // exact hand
$vp0 = $g['players'][0]['vp']; $cr0 = $g['players'][0]['credits'];
// launch to LEO, deploy Comm Satellite there with the solar panel
srand(1);
$tries = 0;
do { // reliability 9: retry the whole launch if the 10% failure hits
    $snapshot = $g;
    try {
        sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $tank, $sat, $solar],
            'plan' => ['path' => ['earth', 'subEarth', 'leo'],
                       'deploys' => [['step' => 2, 'payload' => $sat, 'supports' => [$solar]]]]]);
    } catch (SarError $e) { echo '  error: ' . $e->getMessage() . "\n"; break; }
    $launched = false;
    foreach ($g['crafts'] as $c) if ($c['node'] === 'leo') $launched = true;
    if (!$launched) $g = $snapshot;
} while (!$launched && ++$tries < 20);

$asset = null; $rocket = null;
foreach ($g['crafts'] as $c) {
    if ($c['deployed']) $asset = $c;
    elseif ($c['node'] === 'leo') $rocket = $c;
}
ok($asset !== null, 'satellite deployed at LEO');
ok($asset && $asset['energy'] === 2, 'deployed satellite has 2 Energy from Solar Panel');
ok(!in_array('M01#1', $g['missions'], true), 'M01 LEO Deployment auto-claimed');
ok($g['players'][0]['vp'] === $vp0 + 2, 'M01 pays 2 VP');
ok($g['players'][0]['credits'] === $cr0 + 5 + 1, 'M01 pays 5 Credits (+1 first-LEO exploration floor)');
// run maintenance income
$creditsBefore = $g['players'][0]['credits'];
sar_asset_operations($g);
ok($g['players'][0]['credits'] === $creditsBefore + 1, 'Comm Satellite pays 1 Credit at LEO during Asset Operations');

echo "— Scenario 2: Crewed mission gate — Tourist Hop needs Pressurized tank\n";
$g = fresh();
$g['missions'] = ['M13#1'];
$g['players'][0]['standingDone'] = ['M21']; // isolate: M13's route also satisfies the M21 standing contract
[$eng, $tank, $crew, $chute, $bat] = give($g, 0, ['E02', 'T05', 'P04', 'S02', 'S09']); // S02 parachute (crews can't use airbags)
$g['players'][0]['hand'] = [$eng, $tank, $crew, $chute, $bat];
$vp0 = $g['players'][0]['vp'];
do {
    $snapshot = $g;
    sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $tank, $crew, $chute, $bat],
        'plan' => ['path' => ['earth', 'subEarth', 'earth'],
                   'landing' => [2 => ['method' => 'reentry', 'card' => $chute]]]]);
    $done = !in_array('M13#1', $g['missions'], true);
    if (!$done) { $g = $snapshot; }
} while (!$done);
ok($g['players'][0]['vp'] === $vp0 + 2, 'Tourist Hop claimed with Pressurized tank + Crew Capsule (parachute landing)');

// A heat shield alone cannot land the craft.
$gh = fresh();
[$e2, $t2, $p2, $s2] = give($gh, 0, ['E02', 'T01', 'P05', 'S01']);
$gh['players'][0]['hand'] = [$e2, $t2, $p2, $s2];
$err = null;
try {
    sar_apply($gh, 0, ['type' => 'launch', 'components' => [$e2, $t2, $p2, $s2],
        'plan' => ['path' => ['earth', 'subEarth', 'earth'],
                   'landing' => [2 => ['method' => 'reentry', 'card' => $s2]]]]);
} catch (SarError $e) { $err = $e->getMessage(); }
ok($err !== null && strpos($err, 'cannot land') !== false, 'Heat Shield cannot land the craft (needs a parachute/airbags/propulsive)');

$g2 = fresh();
$g2['missions'] = ['M13#1'];
[$eng, $tank, $crew, $chute, $bat] = give($g2, 0, ['E02', 'T01', 'P04', 'S02', 'S09']); // T01: NOT pressurized
$g2['players'][0]['hand'] = [$eng, $tank, $crew, $chute, $bat];
do {
    $snap = $g2;
    sar_apply($g2, 0, ['type' => 'launch', 'components' => [$eng, $tank, $crew, $chute, $bat],
        'plan' => ['path' => ['earth', 'subEarth', 'earth'],
                   'landing' => [2 => ['method' => 'reentry', 'card' => $chute]]]]);
    $flew = false;
    foreach ($g2['crafts'] as $c) if ($c['node'] === 'earth' && $c['launchRound']) $flew = true;
    if (!$flew) $g2 = $snap;
} while (!$flew);
ok(in_array('M13#1', $g2['missions'], true), 'Tourist Hop NOT claimed without a Pressurized tank');

echo "— Scenario 3: Maintenance recovery of Reusable parts\n";
$p0hand = count($g2['players'][0]['hand']);
$g2['players'][1]['passed'] = false;
// force maintenance
foreach ($g2['players'] as &$pp) { $pp['passed'] = true; }
unset($pp);
sar_maintenance($g2);
$hand = $g2['players'][0]['hand'];
$hasCrew = false;
foreach ($hand as $u) if (explode('#', $u)[0] === 'P04') $hasCrew = true;
ok($hasCrew, 'Reusable Crew Capsule returned to hand after Earth recovery');
$engBack = false;
foreach ($hand as $u) if (explode('#', $u)[0] === 'E02') $engBack = true;
ok(!$engBack, 'single-use Sterling Booster was expended (not returned)');
ok($g2['round'] === 2, 'round advanced to 2 after maintenance');
ok($g2['phase'] === 'planning', 'back in planning phase');

echo "— Scenario 4: Transfer Window cost + Trajectory Planning tech\n";
$g = fresh();
$g['twIdx'] = 7; // TW = 4
ok(sar_tw_cost($g, 0) === 4, 'TW base cost 4 at cycle position 7');
$g['players'][0]['tableau'][] = 'C05#t1';
ok(sar_tw_cost($g, 0) === 3, 'Trajectory Planning reduces TW to 3');
$g['event'] = 'EV07#1';
ok(sar_tw_cost($g, 0) === 1, 'Launch Window event -2 stacks (4-2-1=1)');
$g['event'] = 'EV06#1';
ok(sar_tw_cost($g, 0) === 4, 'TW Storm: min(5, 4+2)=5, then Trajectory Planning -1 = 4');

echo "— Scenario 5: Station designation + M18 + Microgravity Lab income\n";
$g = fresh();
$g['missions'] = ['M18#1'];
[$eng, $tank, $tank2, $hub, $solar, $hab, $lab] = give($g, 0, ['E06', 'T06', 'T01', 'P08', 'S07', 'S12', 'S13']);
$g['players'][0]['hand'] = [$eng, $tank, $tank2, $hub, $solar, $hab, $lab];
$vp0 = $g['players'][0]['vp'];
do {
    $snap = $g;
    try {
        sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $tank, $hub, $solar, $hab, $lab],
            'plan' => ['path' => ['earth', 'subEarth', 'leo', 'geo'],
                       'deploys' => [['step' => 3, 'payload' => $hub, 'supports' => [$solar, $hab, $lab]]]]]);
    } catch (SarError $e) { echo '  error: ' . $e->getMessage() . "\n"; break; }
    $station = null;
    foreach ($g['crafts'] as $c) if ($c['isStation']) $station = $c;
    if (!$station) $g = $snap;
} while (!$station);
ok($station !== null, 'On-Orbit Station auto-designated at GEO');
ok(!in_array('M18#1', $g['missions'], true), 'M18 Station Assembly auto-claimed');
$vpBefore = $g['players'][0]['vp'];
$crBefore = $g['players'][0]['credits'];
sar_asset_operations($g);
ok($g['players'][0]['vp'] === $vpBefore + 1, 'Microgravity Lab pays 1 VP at maintenance');
ok($g['players'][0]['credits'] === $crBefore + 1, 'Station Hub pays 1 Credit at maintenance');

echo "— Scenario 6: staging + aerobrake range math (dry run via probe)\n";
$g = fresh();
[$eng, $tank, $pod, $shield] = give($g, 0, ['E02', 'T01', 'T03', 'S01']);
$g['players'][0]['hand'] = [$eng, $tank, $pod, $shield];
// T01(5) + T03(3) = 8 range; stage T03 mid-flight +1 → exactly 9 crossings to Sub-Moon and back to GEO
do {
    $snap = $g;
    sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $tank, $pod, $shield],
        'plan' => ['path' => ['earth', 'subEarth', 'leo', 'geo', 'earthZoi', 'moonOrbit', 'subMoon', 'moonOrbit', 'earthZoi', 'geo'],
                   'midStages' => [6 => $pod]]]);
    $flew = false;
    foreach ($g['crafts'] as $c) if (($c['node'] ?? '') === 'geo' && $c['launchRound']) $flew = true;
    if (!$flew) $g = $snap;
} while (!$flew);
$geoCraft = null;
foreach ($g['crafts'] as $c) if ($c['node'] === 'geo') $geoCraft = $c;
ok($geoCraft !== null, '9-hop flight with mid-flight staging completed');
ok($geoCraft && $geoCraft['range'] === 8 + 1 - 9, 'range math: 8 + 1 stage - 9 hops = ' . ($geoCraft ? $geoCraft['range'] : '?'));
ok($g['milestones']['moon'] === 0, 'Moon branch milestone awarded');
ok($g['players'][0]['vp'] >= 2, 'milestone +2 VP paid');

echo "— Scenario 7: Flush the Market (free action, 2 Cr, once per command turn)\n";
$g = fresh();
$g['players'][0]['credits'] = 10;
$before = $g['market'];
$deckBefore = count($g['decks']['component']);
sar_apply($g, 0, ['type' => 'flush_market']);
ok($g['players'][0]['credits'] === 8, 'flush costs 2 Credits');
ok($g['turnSeat'] === 0 && $g['players'][0]['turnsUsed'] === 0, 'flush is a free action — turn not consumed');
ok(count(array_filter($g['market'])) === 7, 'market refilled to 7 cards');
ok(!array_intersect($g['market'], $before), 'all 7 market cards replaced');
ok(count($g['decks']['componentDiscard']) >= 7, 'old market cards went to the component discard');
ok(count($g['decks']['component']) === $deckBefore - 7, '7 new cards drawn from the component deck');
$err = null;
try { sar_apply($g, 0, ['type' => 'flush_market']); } catch (SarError $e) { $err = $e->getMessage(); }
ok($err !== null, 'second flush in the same command turn is rejected');
$g['players'][0]['hand'] = []; // make room, then spend the turn
sar_apply($g, 0, ['type' => 'acquire', 'basic' => 'S01']);
$g['turnSeat'] = 0; // force seat 0 to act again regardless of player count
sar_apply($g, 0, ['type' => 'flush_market']);
ok(true, 'flush allowed again on the next command turn');

echo "— Scenario 8: Basic Battery (S15) — always available, Mass 1, 1-Energy burst\n";
$g = fresh();
$g['players'][0]['credits'] = 10;
$g['players'][0]['hand'] = [];
sar_apply($g, 0, ['type' => 'acquire', 'basic' => 'S15']);
$bat = $g['players'][0]['hand'][0] ?? null;
ok($bat && explode('#', $bat)[0] === 'S15', 'Basic Battery purchasable from the Basic supply');
ok($g['players'][0]['credits'] === 9, 'Basic Battery costs 1 Credit');
$g['turnSeat'] = 0;
[$eng, $tank, $caps, $ptank] = give($g, 0, ['E02', 'T05', 'P04', 'T01']);
$g['players'][0]['hand'] = [$eng, $ptank, $caps, $bat];
// Crew Capsule launch needs 1 Energy — no generator aboard, so the battery must burst.
do {
    $snap = $g;
    sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $ptank, $caps, $bat],
        'plan' => ['path' => ['earth', 'subEarth', 'leo']]]);
    $flew = false;
    foreach ($g['crafts'] as $c) if ($c['node'] === 'leo') $flew = true;
    if (!$flew) $g = $snap;
} while (!$flew);
$craft = null;
foreach ($g['crafts'] as $c) if ($c['node'] === 'leo') $craft = $c;
ok($craft !== null, 'launch with Basic Battery powering the Crew Capsule succeeded');
ok($craft && !in_array($bat, $craft['cards'], true), 'Basic Battery expended by the 1-Energy burst');
ok(in_array($bat, $g['decks']['componentDiscard'], true), 'expended battery went to the discard pile');
$mass = sar_craft_mass($g, ['owner' => 0, 'cards' => [$eng, $ptank, $caps, $bat]]);
ok($mass === 2 + 2 + 1, 'Basic Battery Mass 1 counts toward launch mass (2+2+1)');

echo "— Scenario 9: Launch Abort System reroll (regression for review §1.1/§1.2/§1.4)\n";
$g = fresh();
$g['players'][0]['tableau'][] = 'C06#t1'; // Launch Abort System
$g['players'][0]['credits'] = 200; // cover many reroll retries below
[$eng, $tank] = give($g, 0, ['E06', 'T01']); // Raptor-X: reliability 6 (~40% fail chance)
$g['players'][0]['hand'] = [$eng, $tank];

// Retry the launch until the reliability roll fails and LAS offers a reroll.
$pendingType = null; $tries = 0;
do {
    $snap = $g;
    try {
        sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $tank],
            'plan' => ['path' => ['earth', 'subEarth', 'leo']]]);
    } catch (SarError $e) { echo '  error: ' . $e->getMessage() . "\n"; break; }
    $pendingType = $g['pending']['type'] ?? null;
    if ($pendingType !== 'reroll') $g = $snap;
} while ($pendingType !== 'reroll' && ++$tries < 60);
ok($pendingType === 'reroll', 'reliability roll failed and the Launch Abort System offers a reroll');

// --- §1.2: declining the reroll must still spend the command turn.
$declineSnap = $g;
$turnsBefore = $g['players'][0]['turnsUsed'];
sar_apply($g, 0, ['type' => 'decision', 'accept' => false]);
ok($g['players'][0]['turnsUsed'] === $turnsBefore + 1, 'declining the reroll spends the command turn (§1.2)');
ok($g['pending'] === null, 'pending decision cleared after decline');

// --- §1.1/§1.4: an accepted reroll that succeeds must not roll the check
// again (only ever the original roll + one reroll), and each decision
// action must advance version by exactly 1.
$g = $declineSnap; // restore to just before the decision
$craftId = null;
foreach ($g['crafts'] as $id => $c) if ($c['owner'] === 0) $craftId = $id;
$rerollOk = false; $versionOk = true; $tries = 0;
do {
    $snap = $g;
    $verBefore = $g['version'];
    sar_apply($g, 0, ['type' => 'decision', 'accept' => true]);
    if ($g['version'] !== $verBefore + 1) $versionOk = false;
    $rerollOk = isset($g['crafts'][$craftId]) && $g['crafts'][$craftId]['node'] === 'leo';
    if (!$rerollOk) $g = $snap;
} while (!$rerollOk && ++$tries < 60);
ok($rerollOk, 'accepted reroll eventually succeeds and the craft reaches LEO');
ok($versionOk, 'each decision action advances version by exactly 1 (§1.4)');
$rollLogs = array_values(array_filter($g['log'], fn($e) => $e['type'] === 'roll' && ($e['data']['craft'] ?? null) === $craftId));
ok(count($rollLogs) === 2, 'exactly the original roll + one reroll — the check was not re-rolled on resume (§1.1)');

echo "— Scenario 10: staged Kick Stage engine doesn't grant free maneuvering in a later round (regression for review §1.3)\n";
$g = fresh();
[$eng, $tank] = give($g, 0, ['E07', 'T01']); // Kick Stage: Stageable Engine, reliability 7
$g['players'][0]['hand'] = [$eng, $tank];
$flew = false; $tries = 0;
do {
    $snap = $g;
    try {
        sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $tank],
            'plan' => ['path' => ['earth', 'subEarth', 'leo'], 'midStages' => [2 => $eng]]]);
    } catch (SarError $e) { echo '  error: ' . $e->getMessage() . "\n"; break; }
    $craftId = null;
    foreach ($g['crafts'] as $id => $c) if ($c['owner'] === 0) $craftId = $id;
    $flew = $craftId !== null && $g['crafts'][$craftId]['node'] === 'leo';
    if (!$flew) $g = $snap;
} while (!$flew && ++$tries < 60);
ok($flew, 'craft stages away its only Engine mid-flight and still reaches LEO');
ok($flew && sar_craft_engine($g['crafts'][$craftId]) === null, 'the Kick Stage is discarded — no Engine remains on the craft');
ok($flew && $g['crafts'][$craftId]['stagedEngineFlight'] === false, 'stagedEngineFlight is cleared once the flight finishes (§1.3)');

// Advance to round 2 and try to maneuver the now-engineless craft.
foreach ($g['players'] as &$pp) { $pp['passed'] = true; }
unset($pp);
sar_maintenance($g);
foreach ($g['players'] as $p) {
    if (!$p['planningDone']) {
        $over = count($g['players'][$p['seat']]['hand']) - 5;
        $discard = $over > 0 ? array_slice($g['players'][$p['seat']]['hand'], 0, $over) : [];
        sar_apply($g, $p['seat'], ['type' => 'planning_done', 'sell' => [], 'discard' => $discard]);
    }
}
ok($g['round'] === 2 && $g['phase'] === 'action', 'round 2 action phase reached');
ok(isset($g['crafts'][$craftId]) && !$g['crafts'][$craftId]['activated'], 'craft is fresh for round 2 (not activated)');
$threw = false;
try {
    sar_apply($g, 0, ['type' => 'activate', 'craft' => $craftId, 'plan' => ['path' => ['leo', 'geo']]]);
} catch (SarError $e) { $threw = str_contains($e->getMessage(), 'without an Engine'); }
ok($threw, 'an engineless craft cannot maneuver in a later round — stagedEngineFlight no longer bypasses the Engine gate (§1.3)');

echo "— Scenario 11: sar_apply() is transactional — a rejected action leaves state untouched (regression for review §2.3)\n";
$g = fresh();
[$eng, $tank] = give($g, 0, ['E02', 'T01']);
$g['players'][0]['hand'] = [$eng, $tank];
$before = $g;
$threw = false;
try {
    // sar_action_launch mutates the craft (node → earth, range, launchRound,
    // history) *before* the dry-run probe validates the plan. 'moon' is not
    // directly connected to 'earth', so the probe throws "Invalid route" —
    // if sar_apply() were not transactional, the half-launched craft would
    // leak into $g even though the whole action was rejected.
    sar_apply($g, 0, ['type' => 'launch', 'components' => [$eng, $tank],
        'plan' => ['path' => ['earth', 'moon']]]);
} catch (SarError $e) { $threw = str_contains($e->getMessage(), 'Invalid route'); }
ok($threw, 'the malformed launch plan is rejected with "Invalid route"');
ok($g == $before, 'state is byte-for-byte identical to before the rejected action — no half-launched craft leaked (§2.3)');

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
