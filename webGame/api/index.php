<?php
// Space Agency Race - HTTP API. All requests: POST JSON {op, ...} → JSON.
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/engine/engine.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $msg, int $code = 400): void {
    out(['error' => $msg], $code);
}

$raw = file_get_contents('php://input');
$req = $raw ? json_decode($raw, true) : null;
if (!is_array($req)) $req = $_POST ?: $_GET;
$op = $req['op'] ?? '';

$store = new SarStorage();

function room_code(): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // no easily-confused glyphs
    $s = '';
    for ($i = 0; $i < 5; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
    return $s;
}

function make_token(): string { return bin2hex(random_bytes(16)); }

// Which seats does this token control?
function seats_for(array $g, string $token): array {
    if ($g['mode'] === 'hotseat' && $token === $g['hostToken']) {
        return array_column($g['players'], 'seat');
    }
    $seats = [];
    foreach ($g['players'] as $p) if (hash_equals($p['token'], $token)) $seats[] = $p['seat'];
    return $seats;
}

// Strip secrets: other players' hands and the deck contents.
function filter_state(array $g, array $mySeats): array {
    $v = $g;
    unset($v['hostToken']);
    foreach ($v['players'] as $i => &$p) {
        $p['handCount'] = count($p['hand']);
        $p['isYou'] = in_array($p['seat'], $mySeats, true);
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
            $name = trim((string)($req['name'] ?? ''));
            $mode = ($req['mode'] ?? 'online') === 'hotseat' ? 'hotseat' : 'online';
            if ($mode === 'online' && ($name === '' || mb_strlen($name) > 20)) fail('Enter a name (max 20 chars)');
            $token = make_token();
            $room = room_code();
            $store->lock($room);
            $g = sar_new_game($room, $mode, $token);
            if ($mode === 'hotseat') {
                $names = array_values(array_filter(array_map('trim', (array)($req['names'] ?? []))));
                if (count($names) < 2 || count($names) > 4) fail('Hot-seat needs 2-4 player names');
                foreach ($names as $n) sar_add_player($g, mb_substr($n, 0, 20), $token);
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
            $room = strtoupper(trim((string)($req['room'] ?? '')));
            $name = trim((string)($req['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 20) fail('Enter a name (max 20 chars)');
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
            $room = strtoupper(trim((string)($req['room'] ?? '')));
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
            $room = strtoupper(trim((string)($req['room'] ?? '')));
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
            $room = strtoupper(trim((string)($req['room'] ?? '')));
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
            try {
                sar_apply($g, $seat, $action);
            } catch (SarError $e) {
                $store->unlock();
                fail($e->getMessage());
            }
            $store->save($g);
            $store->unlock();
            out(['version' => $g['version'], 'state' => filter_state($g, $seats)]);
        }
        default:
            fail('Unknown op', 404);
    }
} catch (SarError $e) {
    fail($e->getMessage());
} catch (Throwable $e) {
    error_log('SAR fatal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fail('Server error: ' . $e->getMessage(), 500);
}
