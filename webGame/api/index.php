<?php
// Space Agency Race - HTTP API. All requests: POST JSON {op, ...} → JSON.
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/engine/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Last line of defence against an *uncatchable* fatal (out-of-memory,
// max_execution_time, stack overflow) killing the request before out() runs.
// try/catch cannot catch those, so the script would otherwise die with an
// empty 200 body — which the client reports as "Server returned an invalid
// response", forever, on whichever action is heaviest for that room (usually
// launch/activate, which simulate the whole flight). The shutdown handler
// turns that empty body into a proper JSON error the client can show and
// retry. A small pre-allocated buffer is released first so the handler still
// has room to run even right after an OOM fatal.
$GLOBALS['sar_responded'] = false;
$GLOBALS['sar_mem_reserve'] = str_repeat('*', 262144); // 256 KB headroom
register_shutdown_function(function (): void {
    if (!empty($GLOBALS['sar_responded'])) return; // out() already sent a full body
    $GLOBALS['sar_mem_reserve'] = null;            // free headroom for this handler
    $e = error_get_last();
    if ($e === null || !in_array($e['type'],
        [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;
    error_log('SAR fatal (shutdown): ' . $e['message'] . ' @ ' . $e['file'] . ':' . $e['line']);
    if (headers_sent()) return; // a partial body already went out — nothing clean to do
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo '{"error":"The server ran low on resources handling that action — please try again."}';
});

function out(array $data, int $code = 200): void {
    $GLOBALS['sar_responded'] = true;
    http_response_code($code);
    // JSON_INVALID_UTF8_SUBSTITUTE: a stray invalid byte anywhere in the state
    // must degrade to U+FFFD, not turn every response into an empty body
    // (the client would report "Server returned an invalid response" forever).
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        error_log('SAR json_encode failed: ' . json_last_error_msg());
        http_response_code(500);
        $json = '{"error":"Server error. Please try again."}';
    }
    // Content-Length lets the browser detect a truncated body as a network
    // error instead of handing the client half a JSON document. Skip it when
    // PHP itself compresses the output (the length would then be wrong).
    if (!ini_get('zlib.output_compression')) {
        header('Content-Length: ' . strlen($json));
    }
    echo $json;
    exit;
}

function fail(string $msg, int $code = 400): void {
    out(['error' => $msg], $code);
}

$raw = file_get_contents('php://input');
$req = $raw ? json_decode($raw, true) : null;
if (!is_array($req)) $req = $_POST ?: $_GET;
$op = $req['op'] ?? '';

// Deployment self-check: open api/index.php?op=health in a browser.
if ($op === 'health') {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $checks = [
        'php' => PHP_VERSION,
        'phpOk' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'dataDirExists' => is_dir($dir),
        'dataDirWritable' => is_dir($dir) && is_writable($dir),
        'pdoSqlite' => class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true),
        'mbstring' => function_exists('mb_substr'),
        'json' => function_exists('json_encode'),
    ];
    $checks['storage'] = $checks['pdoSqlite'] && !getenv('SAR_FORCE_JSON') ? 'sqlite' : 'json-files';
    // Is the saves folder protected from direct download? (Apache honors the
    // shipped .htaccess; on nginx/other servers a deny rule must be added.)
    $checks['htaccessPresent'] = file_exists($dir . '/.htaccess');
    $problems = [];
    if (!$checks['phpOk']) $problems[] = 'PHP 7.4+ required (found ' . PHP_VERSION . ').';
    if (!$checks['dataDirWritable']) $problems[] = 'api/data is missing or not writable by the web server user — create it and chmod 775 (or 777 on a private test box).';
    if (!$checks['pdoSqlite']) $problems[] = 'pdo_sqlite not available — the game will use JSON-file storage (works fine).';
    if (!$checks['mbstring']) $problems[] = 'mbstring not enabled — not required, a fallback is used.';
    $checks['ok'] = $checks['phpOk'] && $checks['dataDirWritable'];
    $checks['notes'] = $problems;
    $checks['game'] = 'Space Agency Race';
    out($checks, $checks['ok'] ? 200 : 500);
}

try {
    $store = new SarStorage();
} catch (Throwable $e) {
    error_log('SAR storage init failed: ' . $e->getMessage());
    fail('Server error: storage is not configured correctly (open api/index.php?op=health for a full diagnosis)', 500);
}

function room_code(): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // no easily-confused glyphs
    $s = '';
    for ($i = 0; $i < 5; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
    return $s;
}

function make_token(): string { return bin2hex(random_bytes(16)); }

// UTF-8-safe name truncation that works without the mbstring extension.
// Invalid UTF-8 is rejected outright: once inside the state it would poison
// json_encode() for every future response of the room (see out()).
function clip_name($raw, int $max = 20): string {
    $name = trim((string)$raw);
    if (!preg_match('//u', $name)) fail('Name contains invalid characters', 400);
    if (function_exists('mb_substr')) return mb_substr($name, 0, $max);
    if (preg_match('/^.{0,' . $max . '}/us', $name, $m)) return $m[0]; // PCRE UTF-8 mode
    return substr($name, 0, $max);
}

// Room codes are used as storage keys/file names — validate strictly.
function clean_room($raw): string {
    $room = strtoupper(trim((string)$raw));
    if (!preg_match('/^[A-Z0-9]{4,8}$/', $room)) fail('Invalid room code', 400);
    return $room;
}

// Which seats does this token control?
function seats_for(array $g, string $token): array {
    if ($g['mode'] === 'hotseat' && hash_equals($g['hostToken'], $token)) {
        return array_column($g['players'], 'seat');
    }
    $seats = [];
    foreach ($g['players'] as $p) if (hash_equals($p['token'], $token)) $seats[] = $p['seat'];
    return $seats;
}

// Strip secrets: other players' hands and the deck contents.
function filter_state(array $g, array $mySeats): array {
    $v = $g;
    unset($v['hostToken'], $v['lastAid']);
    // The client renders at most the last 120 log lines; sending the whole
    // capped log (400 entries) triples the payload for nothing and makes a
    // truncated response on a flaky mobile connection more likely.
    if (count($v['log'] ?? []) > 150) $v['log'] = array_slice($v['log'], -150);
    foreach ($v['players'] as $i => &$p) {
        $p['handCount'] = count($p['hand']);
        $p['isYou'] = in_array($p['seat'], $mySeats, true);
        // Derived server-side so events (Crash Program) are reflected in the UI.
        $p['commandTurns'] = sar_command_turns($g, $p['seat']);
        unset($p['token']);
        if (!in_array($p['seat'], $mySeats, true)) $p['hand'] = null;
    }
    unset($p);
    $v['decks'] = [
        'component' => count($g['decks']['component'] ?? []),
        'componentDiscard' => count($g['decks']['componentDiscard'] ?? []),
        'event' => count($g['decks']['event'] ?? []),
        'mission' => count($g['decks']['mission'] ?? []),
        'missionT2' => count($g['decks']['missionT2'] ?? []),
        'missionT3' => count($g['decks']['missionT3'] ?? []),
    ];
    $v['mySeats'] = $mySeats;
    if (!empty($v['pending'])) {
        $v['pending'] = ['type' => $v['pending']['type'], 'seat' => $v['pending']['seat'],
                         'rel' => $v['pending']['data']['rel'] ?? null];
    }
    return $v;
}

try {
    switch ($op) {
        case 'create': {
            $name = clip_name($req['name'] ?? '');
            $mode = ($req['mode'] ?? 'online') === 'hotseat' ? 'hotseat' : 'online';
            if ($mode === 'online' && $name === '') fail('Enter a name');
            $token = make_token();
            // Generate the code inside the lock and retry on collision —
            // otherwise a (rare) repeat would upsert over a running room.
            $room = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = room_code();
                $store->lock($candidate);
                if ($store->load($candidate) === null) { $room = $candidate; break; }
                $store->unlock();
            }
            if ($room === null) fail('Server busy, please try again', 503);
            $g = sar_new_game($room, $mode, $token);
            if ($mode === 'hotseat') {
                $names = array_values(array_filter(array_map('trim', (array)($req['names'] ?? []))));
                if (count($names) < 2 || count($names) > 4) fail('Hot-seat needs 2-4 player names');
                foreach ($names as $n) sar_add_player($g, clip_name($n), $token);
            } else {
                sar_add_player($g, $name, $token);
            }
            $g['version']++;
            $store->save($g);
            $store->unlock();
            $store->cleanup();
            out(['room' => $room, 'token' => $token, 'seat' => 0, 'mode' => $mode]);
        }
        case 'join': {
            $room = clean_room($req['room'] ?? '');
            $name = clip_name($req['name'] ?? '');
            if ($name === '') fail('Enter a name');
            $store->lock($room);
            $g = $store->load($room);
            if (!$g) { $store->unlock(); fail('Room not found', 404); }
            if ($g['mode'] !== 'online') { $store->unlock(); fail('That room is a hot-seat game'); }
            $token = make_token();
            try {
                $seat = sar_add_player($g, $name, $token);
            } catch (SarError $e) {
                $store->unlock();
                fail($e->getMessage());
            }
            $g['version']++;
            $store->save($g);
            $store->unlock();
            out(['room' => $room, 'token' => $token, 'seat' => $seat, 'mode' => 'online']);
        }
        case 'start': {
            $room = clean_room($req['room'] ?? '');
            $token = (string)($req['token'] ?? '');
            $store->lock($room);
            $g = $store->load($room);
            if (!$g) { $store->unlock(); fail('Room not found', 404); }
            if (!hash_equals($g['hostToken'], $token)) { $store->unlock(); fail('Only the host can start the game', 403); }
            try {
                sar_start_game($g);
            } catch (SarError $e) {
                $store->unlock();
                fail($e->getMessage());
            }
            $g['version']++;
            $store->save($g);
            $store->unlock();
            out(['ok' => true]);
        }
        case 'state': {
            $room = clean_room($req['room'] ?? '');
            $token = (string)($req['token'] ?? '');
            $since = (int)($req['since'] ?? 0);
            $current = $store->version($room);
            if ($current === null) fail('Room not found', 404);
            if ($current === $since) out(['version' => $current, 'unchanged' => true]);
            $g = $store->load($room);
            $seats = seats_for($g, $token);
            if (!$seats && $g['status'] !== 'lobby') fail('You are not in this game', 403);
            out(['version' => $g['version'], 'state' => filter_state($g, $seats)]);
        }
        case 'action': {
            $room = clean_room($req['room'] ?? '');
            $token = (string)($req['token'] ?? '');
            $action = $req['action'] ?? null;
            if (!is_array($action)) fail('Missing action');
            $store->lock($room);
            $g = $store->load($room);
            if (!$g) { $store->unlock(); fail('Room not found', 404); }
            $seats = seats_for($g, $token);
            if (!$seats) { $store->unlock(); fail('You are not in this game', 403); }
            $seat = isset($req['seat']) ? (int)$req['seat'] : $seats[0];
            if (!in_array($seat, $seats, true)) { $store->unlock(); fail('You do not control that seat', 403); }
            // Idempotency: the client sends a random id (aid) with every
            // action and retries with the same aid when the response is lost
            // (timeout, truncated body). If this seat's previous action
            // carried the same aid it has already been applied — answer with
            // the current state instead of a spurious rule error such as
            // "Not your command turn".
            $aid = substr((string)($req['aid'] ?? ''), 0, 64);
            if ($aid !== '' && ($g['lastAid'][$seat] ?? null) === $aid) {
                $store->unlock();
                out(['version' => $g['version'], 'state' => filter_state($g, $seats), 'replayed' => true]);
            }
            try {
                sar_apply($g, $seat, $action);
            } catch (SarError $e) {
                $store->unlock();
                fail($e->getMessage());
            }
            if ($aid !== '') $g['lastAid'][$seat] = $aid;
            $store->save($g);
            $store->unlock();
            out(['version' => $g['version'], 'state' => filter_state($g, $seats)]);
        }
        default:
            fail('Unknown op', 404);
    }
} catch (SarError $e) {
    fail($e->getMessage());
} catch (SarStorageBusy $e) {
    fail('The room is busy right now — please try again', 503);
} catch (Throwable $e) {
    error_log('SAR fatal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fail('Server error. Please try again.', 500);
}
