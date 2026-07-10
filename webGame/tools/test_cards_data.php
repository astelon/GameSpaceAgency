<?php
// CLI test for the card database that feeds card rendering (hand, market,
// mission offers). Checks that the PHP engine data and the frontend
// data/cards.json are identical, that every card carries the full schema the
// renderer relies on, and that every referenced asset exists.
// Usage: php webGame/tools/test_cards_data.php

require_once __DIR__ . '/../api/engine/cards_data.php';

$fails = 0;
$pass = 0;
function check(bool $ok, string $msg): void {
    global $fails, $pass;
    if ($ok) { $pass++; }
    else { $fails++; echo "FAIL: $msg\n"; }
}

$web = dirname(__DIR__);
$php = sar_cards_data();
$jsonRaw = file_get_contents($web . '/data/cards.json');
check($jsonRaw !== false, 'data/cards.json is readable');
$json = json_decode($jsonRaw ?: 'null', true);
check(is_array($json) && $json, 'data/cards.json parses to a non-empty object');
check(is_array($php) && $php, 'sar_cards_data() returns a non-empty array');

// 1. Engine and frontend must play/render the exact same cards.
check(array_keys($php) === array_keys($json ?: []), 'PHP and JSON databases list the same card ids in the same order');
foreach ($php as $cid => $card) {
    if (!isset($json[$cid])) { check(false, "$cid missing from cards.json"); continue; }
    check($card == $json[$cid], "$cid is identical in cards_data.php and cards.json (rerun webGame/tools/build_data.py)");
}

// 2. Every card must carry the full schema renderCard() relies on, so every
//    card can be drawn with the same uniform layout/size.
$schema = ['id', 'type', 'name', 'cost', 'thrust', 'range', 'mass', 'energy',
           'energyMode', 'reliability', 'vp', 'tags', 'text', 'flavor', 'tier',
           'rewardCredits', 'copies', 'art'];
$types = ['Engine', 'Tank', 'Payload', 'Support', 'Tech', 'Mission', 'Event'];
foreach ($php as $cid => $c) {
    $missing = array_diff($schema, array_keys($c));
    check(!$missing, "$cid has all schema fields (missing: " . implode(',', $missing) . ')');
    check($c['id'] === $cid, "$cid: id field matches its key");
    check(in_array($c['type'], $types, true), "$cid: type '{$c['type']}' is a known card type");
    check(is_string($c['name']) && $c['name'] !== '', "$cid: has a name");
    check(is_int($c['cost']) && $c['cost'] >= 0, "$cid: cost is a non-negative int");
    check(is_int($c['vp']) && $c['vp'] >= 0, "$cid: vp is a non-negative int");
    check(is_int($c['rewardCredits']) && $c['rewardCredits'] >= 0, "$cid: rewardCredits is a non-negative int");
    check(is_int($c['copies']) && $c['copies'] >= 0, "$cid: copies is a non-negative int");
    check(is_array($c['tags']), "$cid: tags is a list");
    check(is_string($c['text']), "$cid: text is a string");
    check(in_array($c['energyMode'], ['', 'Gen', 'Use', 'Burst'], true), "$cid: energyMode '{$c['energyMode']}' is valid");
    foreach (['thrust', 'range', 'mass', 'energy', 'reliability'] as $f) {
        check($c[$f] === null || is_int($c[$f]), "$cid: $f is int or null");
    }
    if ($c['type'] === 'Mission') {
        check(preg_match('/^Tier [123]$/', $c['tier']) === 1, "$cid: mission tier '{$c['tier']}' is Tier 1..3");
    }
    // Referenced art must exist, or the market/hand shows a broken frame.
    if ($c['art'] !== null) {
        check(is_file($web . '/' . $c['art']), "$cid: art file {$c['art']} exists");
    }
}

// 3. Icons the uniform card layout uses (type icon + stat chips) must exist.
foreach (['engine', 'tank', 'payload', 'support', 'tech', 'mission', 'event',
          'thrust', 'range', 'mass', 'reliability', 'energy', 'class', 'credits'] as $icon) {
    check(is_file($web . "/assets/icons/$icon.png"), "icon assets/icons/$icon.png exists");
}

echo $fails ? "\n$pass passed, $fails FAILURES\n" : "\nALL CARD DATA TESTS PASSED ($pass checks)\n";
exit($fails ? 1 : 0);
