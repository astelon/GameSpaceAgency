<?php
// Space Agency Race - core error types, game/card constants, and logging.
// No dependencies beyond the static card/map data.

require_once __DIR__ . '/cards_data.php';
require_once __DIR__ . '/map.php';

class SarError extends Exception {} // rejected player action (expected; caller shows the message)
class SarInvariantError extends Exception {} // broken engine state (a bug; never expected in production)

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
const SAR_MARKET_SIZE = 7;
// Standing contracts: always available, completable once per agency per game;
// never shuffled into the mission display deck.
const SAR_STANDING_CONTRACTS = ['M21'];
// Exploration race ladder: reward [Credits, VP] for the 1st/2nd/3rd/4th agency
// to first reach each frontier. Near-Earth pays Credits, deep space pays VP.
const SAR_EXPLORE_LADDER = [
    'geo'      => [[2, 0], [1, 0], [1, 0], [0, 0]],
    'earthZoi' => [[1, 1], [1, 0], [0, 0], [0, 0]],
    'moon'     => [[0, 2], [0, 1], [0, 0], [0, 0]],
    'mars'     => [[0, 4], [0, 2], [0, 1], [0, 0]],
];
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
