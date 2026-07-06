<?php
// CLI fuzz/smoke test for the rules engine. Plays full games with a simple
// bot through the public sar_apply() interface and checks invariants.
// Usage: php webGame/tools/test_engine.php [games=20] [-v]

require_once __DIR__ . '/../api/engine/engine.php';
require_once __DIR__ . '/../api/engine/flight.php';
require_once __DIR__ . '/../api/engine/missions.php';

$games = (int)($argv[1] ?? 20);
$verbose = in_array('-v', $argv, true);

function invariants(array $g): void {
    foreach ($g['players'] as $p) {
        assert($p['credits'] >= 0, "negative credits for {$p['name']}");
        assert($p['vp'] >= 0, 'negative vp');
        if ($p['credits'] < 0 || $p['vp'] < 0) throw new Exception('negative resources');
    }
    foreach ($g['crafts'] as $c) {
        if ($c['range'] < 0) throw new Exception('negative range on ' . $c['id']);
        if ($c['energy'] < 0) throw new Exception('negative energy on ' . $c['id']);
        if ($c['node'] !== 'assembly' && !isset(SAR_NODES[$c['node']])) throw new Exception('bad node ' . $c['node']);
    }
    // No card duplication: every uid appears at most once across all zones.
    $seen = [];
    $zones = [$g['decks']['component'], $g['decks']['componentDiscard'], $g['market']];
    foreach ($g['players'] as $p) { $zones[] = $p['hand']; $zones[] = $p['tableau']; }
    foreach ($g['crafts'] as $c) $zones[] = $c['cards'];
    foreach ($zones as $zone) {
        foreach ((array)$zone as $uid) {
            if ($uid === null) continue;
            if (isset($seen[$uid])) throw new Exception("card duplicated: $uid");
            $seen[$uid] = true;
        }
    }
}

function try_apply(array &$g, int $seat, array $action): bool {
    try {
        sar_apply($g, $seat, $action);
        invariants($g);
        return true;
    } catch (SarError $e) {
        return false; // legal rejection
    }
}

function bot_planning(array &$g, int $seat): void {
    $p = $g['players'][$seat];
    $over = count($p['hand']) - sar_hand_limit($g);
    $discard = [];
    $sell = [];
    if ($over > 0) {
        $extra = array_slice($p['hand'], 0, $over);
        $sell = array_slice($extra, 0, 2);
        $discard = array_slice($extra, 2);
    } elseif (random_int(0, 4) === 0 && $p['hand']) {
        $sell = [$p['hand'][0]];
    }
    if (!try_apply($g, $seat, ['type' => 'planning_done', 'sell' => $sell, 'discard' => $discard])) {
        sar_apply($g, $seat, ['type' => 'planning_done', 'sell' => [], 'discard' => array_slice($p['hand'], 0, max(0, $over))]);
        invariants($g);
    }
}

function hand_pick(array $g, int $seat, string $type, ?string $tag = null): ?string {
    foreach ($g['players'][$seat]['hand'] as $uid) {
        $c = sar_card($uid);
        if ($c['type'] === $type && ($tag === null || in_array($tag, $c['tags'], true))) return $uid;
    }
    return null;
}

function bot_action(array &$g, int $seat): void {
    $p = $g['players'][$seat];
    // occasionally flush the market first (free action — should not end the turn)
    if ($p['credits'] >= 4 && random_int(0, 5) === 0) {
        $turn = $g['turnSeat'];
        if (try_apply($g, $seat, ['type' => 'flush_market'])) {
            if ($g['turnSeat'] !== $turn) throw new Exception('flush_market consumed the command turn');
            $p = $g['players'][$seat];
        }
    }
    $choices = ['launch', 'acquire', 'develop', 'expand', 'activate', 'launch', 'acquire', 'activate'];
    shuffle($choices);
    foreach ($choices as $choice) {
        switch ($choice) {
            case 'acquire':
                foreach ($g['market'] as $i => $uid) {
                    if ($uid && sar_card($uid)['cost'] <= $p['credits'] && random_int(0, 1)) {
                        if (try_apply($g, $seat, ['type' => 'acquire', 'slot' => $i])) return;
                    }
                }
                if ($p['credits'] >= 3 && try_apply($g, $seat, ['type' => 'acquire', 'basic' => 'E02'])) return;
                break;
            case 'develop':
                $t = hand_pick($g, $seat, 'Tech');
                if ($t && try_apply($g, $seat, ['type' => 'develop', 'card' => $t])) return;
                break;
            case 'expand':
                if ($p['credits'] >= 8 && random_int(0, 2) === 0 && try_apply($g, $seat, ['type' => 'expand'])) return;
                break;
            case 'launch': {
                $eng = hand_pick($g, $seat, 'Engine');
                $tank = hand_pick($g, $seat, 'Tank');
                if (!$eng || !$tank) break;
                $components = [$eng, $tank];
                $pl = hand_pick($g, $seat, 'Payload');
                if ($pl) $components[] = $pl;
                $reentry = hand_pick($g, $seat, 'Support', 'Reentry');
                if ($reentry) $components[] = $reentry;
                $power = hand_pick($g, $seat, 'Support', 'Power');
                if ($power && $power !== $reentry) $components[] = $power;
                $range = sar_card($tank)['range'];

                $plans = [];
                if ($reentry) {
                    $plans[] = ['path' => ['earth', 'subEarth', 'earth'],
                        'landing' => [2 => ['method' => 'reentry', 'card' => $reentry]]];
                }
                $far = ['earth', 'subEarth', 'leo'];
                if ($range >= 3) $far[] = 'geo';
                if ($range >= 4) $far[] = 'earthZoi';
                if ($range >= 5) $far[] = 'moonOrbit';
                $plan = ['path' => array_slice($far, 0, min(count($far), 1 + $range))];
                // maybe deploy a satellite at the last node
                if ($pl && sar_has_tag($pl, 'Satellite') && count($plan['path']) >= 3) {
                    $plan['deploys'] = [['step' => count($plan['path']) - 1, 'payload' => $pl,
                        'supports' => $power ? [$power] : []]];
                }
                $plans[] = $plan;
                shuffle($plans);
                foreach ($plans as $pn) {
                    if (try_apply($g, $seat, ['type' => 'launch', 'components' => $components, 'plan' => $pn])) return;
                }
                break;
            }
            case 'activate':
                foreach ($g['crafts'] as $id => $c) {
                    if ($c['owner'] !== $seat || $c['node'] === 'assembly' || $c['activated'] || $c['deployed']) continue;
                    if ($c['range'] >= 1 && sar_craft_engine($c)) {
                        foreach (SAR_EDGES as $e) {
                            $to = null;
                            if ($e[0] === $c['node']) $to = $e[1];
                            if ($e[1] === $c['node']) $to = $e[0];
                            if (!$to || sar_is_surface($to)) continue;
                            if (try_apply($g, $seat, ['type' => 'activate', 'craft' => $id,
                                'plan' => ['path' => [$c['node'], $to]]])) return;
                        }
                    }
                }
                break;
        }
    }
    sar_apply($g, $seat, ['type' => 'pass']);
    invariants($g);
}

$fails = 0;
for ($run = 1; $run <= $games; $run++) {
    $np = 2 + ($run % 3);
    $g = sar_new_game('TEST' . $run, 'hotseat', 'tok');
    $names = ['Ada', 'Bo', 'Cy', 'Dee'];
    for ($i = 0; $i < $np; $i++) sar_add_player($g, $names[$i], 'tok');
    sar_start_game($g);
    invariants($g);

    $guard = 0;
    try {
        while ($g['status'] === 'playing' && ++$guard < 2000) {
            if ($g['pending']) {
                sar_apply($g, $g['pending']['seat'], ['type' => 'decision', 'accept' => (bool)random_int(0, 1)]);
                invariants($g);
                continue;
            }
            if ($g['phase'] === 'planning') {
                foreach ($g['players'] as $p) {
                    if (!$p['planningDone']) { bot_planning($g, $p['seat']); break; }
                }
            } elseif ($g['phase'] === 'action') {
                bot_action($g, $g['turnSeat']);
            } else {
                throw new Exception('stuck in phase ' . $g['phase']);
            }
        }
        if ($g['status'] !== 'finished') throw new Exception("game did not finish (guard=$guard, phase={$g['phase']})");
        $w = $g['finalScores'][0];
        echo "game $run ({$np}p): OK — {$w['name']} wins with {$w['vp']} VP; " .
             count($g['log']) . " log entries, " . $g['version'] . " versions\n";
        if ($verbose) {
            foreach ($g['log'] as $l) echo '   [' . $l['type'] . '] ' . $l['text'] . "\n";
        }
    } catch (Throwable $e) {
        $fails++;
        echo "game $run ({$np}p): FAIL — " . get_class($e) . ': ' . $e->getMessage() .
             ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo '   last log: ' . json_encode(array_slice(array_column($g['log'], 'text'), -4)) . "\n";
    }
}
echo $fails ? "\n$fails FAILURES\n" : "\nALL GAMES PASSED\n";
exit($fails ? 1 : 0);
