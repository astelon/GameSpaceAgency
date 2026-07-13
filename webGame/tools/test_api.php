<?php
// API integration test: boots the real HTTP layer (api/index.php +
// storage.php) with PHP's built-in server against an isolated temp copy of
// api/, then drives create/join/start/state/action over real HTTP requests.
// Covers what the engine-level test suites can't: auth (seats_for), hand
// hiding (filter_state), lobby lifecycle, and that two concurrent writers to
// one room don't lose an update.
// Usage: php webGame/tools/test_api.php

$pass = 0; $fail = 0;
function ok(bool $cond, string $label): void {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  \xE2\x9C\x94 $label\n"; }
    else { $fail++; echo "  \xE2\x9C\x98 FAIL: $label\n"; }
}

// --- Isolate a copy of api/ so this run's data/ dir never touches the real one.
$apiSrc = __DIR__ . '/../api';
$root = sys_get_temp_dir() . '/sar_api_test_' . bin2hex(random_bytes(6));

function copy_dir(string $src, string $dst): void {
    mkdir($dst, 0775, true);
    foreach (scandir($src) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $s = "$src/$entry"; $d = "$dst/$entry";
        if (is_dir($s)) copy_dir($s, $d);
        else copy($s, $d);
    }
}
function rm_dir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $p = "$dir/$entry";
        is_dir($p) ? rm_dir($p) : @unlink($p);
    }
    @rmdir($dir);
}
copy_dir($apiSrc, $root);

// --- Boot php -S with a couple of worker processes so concurrent requests
// are actually handled in parallel (the built-in server is single-threaded
// by default, which would make the race test a no-op).
putenv('PHP_CLI_SERVER_WORKERS=4');
$port = 8100 + random_int(0, 900);
$cmd = sprintf('%s -d display_errors=0 -S 127.0.0.1:%d -t %s',
    escapeshellarg(PHP_BINARY), $port, escapeshellarg($root));
$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open($cmd, $descriptors, $pipes, null, null);
if (!is_resource($proc)) { fwrite(STDERR, "Could not start php -S\n"); exit(1); }
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

function shutdown_server($proc, string $root): void {
    if (is_resource($proc)) proc_terminate($proc);
    rm_dir($root);
}
register_shutdown_function('shutdown_server', $proc, $root);

function api_call(int $port, array $payload): array {
    $ch = curl_init("http://127.0.0.1:$port/index.php");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    if ($body === false) throw new RuntimeException('curl error: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($body, true)];
}

// Form-encoded POST (the $_POST fallback path used when the body is not JSON).
function api_call_form(int $port, array $fields): array {
    $ch = curl_init("http://127.0.0.1:$port/index.php");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    if ($body === false) throw new RuntimeException('curl error: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'data' => json_decode($body, true)];
}

// Fire several requests truly concurrently via curl_multi.
function api_call_concurrent(int $port, array $payloads): array {
    $mh = curl_multi_init();
    $handles = [];
    foreach ($payloads as $i => $payload) {
        $ch = curl_init("http://127.0.0.1:$port/index.php");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = $ch;
    }
    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running > 0) curl_multi_select($mh);
    } while ($running > 0 && $status === CURLM_OK);
    $results = [];
    foreach ($handles as $i => $ch) {
        $body = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        $results[$i] = ['code' => $code, 'data' => json_decode($body, true)];
    }
    curl_multi_close($mh);
    return $results;
}

// Wait for the server to accept connections (health op needs no room).
$ready = false;
for ($i = 0; $i < 50; $i++) {
    try {
        $r = api_call($port, ['op' => 'health']);
        if ($r['code'] === 200 || $r['code'] === 500) { $ready = true; break; }
    } catch (Throwable $e) {
        // Server not accepting connections yet — keep retrying.
    }
    usleep(100_000);
}
if (!$ready) { fwrite(STDERR, "php -S never came up on port $port\n"); exit(1); }

echo "— API: lobby lifecycle, auth, hand hiding\n";

// Two players join an online room.
$create = api_call($port, ['op' => 'create', 'mode' => 'online', 'name' => 'Alice']);
ok($create['code'] === 200, 'create room succeeds');
$room = $create['data']['room'] ?? null;
$hostToken = $create['data']['token'] ?? null;
ok(is_string($room) && strlen($room) >= 4, 'create returns a room code');

$join = api_call($port, ['op' => 'join', 'room' => $room, 'name' => 'Bob']);
ok($join['code'] === 200, 'second player joins');
$bobToken = $join['data']['token'] ?? null;

// A stranger token must not see anything.
$strangerState = api_call($port, ['op' => 'state', 'room' => $room, 'token' => 'not-a-real-token']);
ok($strangerState['code'] === 200 && ($strangerState['data']['state']['mySeats'] ?? null) === [],
    'unknown token gets no seats while still in lobby');

$start = api_call($port, ['op' => 'start', 'room' => $room, 'token' => $hostToken]);
ok($start['code'] === 200, 'host starts the game');

$badStart = api_call($port, ['op' => 'start', 'room' => $room, 'token' => 'wrong']);
ok($badStart['code'] === 403, 'a non-host token cannot start the game (403)');

// Hand hiding: Alice's own view shows her hand, hides Bob's.
$aliceState = api_call($port, ['op' => 'state', 'room' => $room, 'token' => $hostToken]);
$aliceView = $aliceState['data']['state'];
$p0 = $aliceView['players'][0]; $p1 = $aliceView['players'][1];
ok($p0['isYou'] === true && is_array($p0['hand']), "Alice sees her own hand");
ok($p1['isYou'] === false && $p1['hand'] === null && is_int($p1['handCount']),
    "Alice cannot see Bob's hand, only its count");
ok(!array_key_exists('hostToken', $aliceView), 'hostToken is stripped from the response');

// Seat authorization: Bob cannot act as Alice's seat.
$wrongSeat = api_call($port, ['op' => 'action', 'room' => $room, 'token' => $bobToken, 'seat' => 0,
    'action' => ['type' => 'planning_done', 'sell' => [], 'discard' => []]]);
ok($wrongSeat['code'] === 403, "Bob cannot submit an action for Alice's seat (403)");

echo "— API: idempotent action replay (lost-response retry)\n";

// The client retries an action with the same aid when the response is lost.
// The replay must answer with the current state, not a rule error.
$act = ['op' => 'action', 'room' => $room, 'token' => $hostToken, 'seat' => 0, 'aid' => 'retry-aid-1',
    'action' => ['type' => 'planning_done', 'sell' => [], 'discard' => []]];
$first = api_call($port, $act);
ok($first['code'] === 200, 'action carrying an aid succeeds');
$replay = api_call($port, $act);
ok($replay['code'] === 200 && ($replay['data']['replayed'] ?? null) === true
    && ($replay['data']['version'] ?? null) === ($first['data']['version'] ?? -1),
    'replaying the same aid returns the current state (same version) instead of a rule error');
ok(!array_key_exists('lastAid', $replay['data']['state']), 'lastAid bookkeeping is stripped from the response');
$again = api_call($port, array_merge($act, ['aid' => 'retry-aid-2']));
ok($again['code'] === 400, 'the same action under a fresh aid is validated normally (400 Already ready)');

echo "— API: malformed input never breaks the JSON response contract\n";

// The form-encoded fallback path works for well-formed input…
$formOk = api_call_form($port, ['op' => 'create', 'mode' => 'online', 'name' => 'Carol']);
ok($formOk['code'] === 200 && is_string($formOk['data']['room'] ?? null),
    'form-encoded create (the $_POST fallback) still works');

// …but invalid UTF-8 in a name must be rejected with a JSON 400, not stored.
// (Stored, it would make json_encode() fail on every later response of the
// room — the "Server returned an invalid response" brick.)
$badName = "Ev\xC3\x28l"; // \xC3 starts a 2-byte sequence, \x28 cannot finish it
$bad = api_call_form($port, ['op' => 'create', 'mode' => 'online', 'name' => $badName]);
ok($bad['code'] === 400 && is_array($bad['data']) && isset($bad['data']['error']),
    'invalid-UTF-8 name is rejected with a JSON 400 body');

$badJoin = api_call_form($port, ['op' => 'join', 'room' => $room, 'name' => $badName]);
ok($badJoin['code'] === 400 && is_array($badJoin['data']) && isset($badJoin['data']['error']),
    'invalid-UTF-8 join name is rejected with a JSON 400 body');

// The room the bad join targeted must still serve parseable state.
$afterBad = api_call($port, ['op' => 'state', 'room' => $room, 'token' => $hostToken]);
ok($afterBad['code'] === 200 && is_array($afterBad['data']['state'] ?? null),
    'room state still parses as JSON after the rejected join');

echo "— API: two racing actions to one room serialize correctly\n";

// Both players ready up in the same instant, repeated across several fresh
// rooms to give a regression a real chance to land: if the storage lock
// does not actually serialize the two requests, one write can clobber the
// other and the room ends up with only one player marked ready (a lost
// update) — a race that a single trial can easily miss.
$raceRounds = 8;
$raceOk = true;
for ($round = 0; $round < $raceRounds; $round++) {
    $rc = api_call($port, ['op' => 'create', 'mode' => 'online', 'name' => 'Alice']);
    $rRoom = $rc['data']['room']; $rHost = $rc['data']['token'];
    $rj = api_call($port, ['op' => 'join', 'room' => $rRoom, 'name' => 'Bob']);
    $rBob = $rj['data']['token'];
    api_call($port, ['op' => 'start', 'room' => $rRoom, 'token' => $rHost]);

    $results = api_call_concurrent($port, [
        ['op' => 'action', 'room' => $rRoom, 'token' => $rHost, 'seat' => 0,
         'action' => ['type' => 'planning_done', 'sell' => [], 'discard' => []]],
        ['op' => 'action', 'room' => $rRoom, 'token' => $rBob, 'seat' => 1,
         'action' => ['type' => 'planning_done', 'sell' => [], 'discard' => []]],
    ]);
    $final = api_call($port, ['op' => 'state', 'room' => $rRoom, 'token' => $rHost])['data']['state'];
    $roundOk = $results[0]['code'] === 200 && $results[1]['code'] === 200
        && $final['players'][0]['planningDone'] === true
        && $final['players'][1]['planningDone'] === true
        && $final['phase'] === 'action';
    if (!$roundOk) $raceOk = false;
}
ok($raceOk, "all $raceRounds concurrent ready-up races serialize correctly (both requests always succeed, no update lost)");

echo "\n$pass passed, $fail failed.\n";
exit($fail > 0 ? 1 : 0);
