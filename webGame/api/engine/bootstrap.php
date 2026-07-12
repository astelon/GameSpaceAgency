<?php
// Space Agency Race - server-authoritative rules engine, bootstrap & public
// entry point. State is a plain associative array (JSON-serializable). Every
// mutation goes through sar_apply() which validates against the rules in
// Space_Agency.md.

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/state.php';
require_once __DIR__ . '/lobby.php';
require_once __DIR__ . '/phases.php';
require_once __DIR__ . '/actions.php';
require_once __DIR__ . '/flight.php';
require_once __DIR__ . '/missions.php';

// ---------------------------------------------------------------------------
// Public entry point
//
// Transactional: every mutation happens on a local copy ($tmp) that is only
// assigned back to the caller's $g reference once the action completes
// without throwing. A thrown SarError therefore always leaves $g untouched —
// callers never need to snapshot/restore state themselves around sar_apply().

function sar_apply(array &$g, int $seat, array $action): void {
    if ($g['status'] === 'finished') throw new SarError('The game is over');
    if ($g['status'] !== 'playing') throw new SarError('The game has not started');
    $tmp = $g; // PHP arrays are copy-on-write — cheap until mutated
    $type = $action['type'] ?? '';
    switch ($type) {
        case 'planning_done': sar_action_planning_done($tmp, $seat, $action); break;
        case 'acquire':       sar_action_acquire($tmp, $seat, $action); break;
        case 'flush_market':  sar_action_flush_market($tmp, $seat); break;
        case 'develop':       sar_action_develop($tmp, $seat, $action); break;
        case 'engineering':   sar_action_engineering($tmp, $seat, $action); break;
        case 'launch':        sar_action_launch($tmp, $seat, $action); break;
        case 'activate':      sar_action_activate($tmp, $seat, $action); break;
        case 'expand':        sar_action_expand($tmp, $seat); break;
        case 'pass':          sar_action_pass($tmp, $seat); break;
        case 'decision':      sar_action_decision($tmp, $seat, $action); break;
        default: throw new SarError("Unknown action: $type");
    }
    $tmp['version']++;
    $g = $tmp;
}
