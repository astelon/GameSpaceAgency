<?php
// Mission requirement scripting and automatic claiming.
// Missions are public contracts: the first craft/asset that satisfies a
// mission's printed conditions claims it immediately (energy costs are paid
// automatically, Battery Packs included).

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/state.php';

// True if $needle appears as an ordered subsequence of node history.
function sar_history_seq(array $history, array $needle): bool {
    $i = 0;
    foreach ($history as $node) {
        if ($node === $needle[$i]) {
            $i++;
            if ($i === count($needle)) return true;
        }
    }
    return false;
}

function sar_payload_info(array $craft): array {
    foreach ($craft['cards'] as $uid) {
        $c = sar_card($uid);
        if ($c['type'] === 'Payload') return ['uid' => $uid, 'card' => $c];
    }
    return ['uid' => null, 'card' => null];
}

function sar_craft_has_engine_or_staged(array $craft): bool {
    return sar_craft_engine($craft) !== null || $craft['stagedEngineFlight'];
}

function sar_player_has_deployed(array $g, int $seat, string $tag, bool $inSpace = false): bool {
    foreach ($g['crafts'] as $c) {
        if ($c['owner'] !== $seat || !$c['deployed']) continue;
        if ($inSpace && !sar_in_space($c['node'])) continue;
        foreach ($c['cards'] as $uid) {
            if (sar_has_tag($uid, $tag)) return true;
        }
    }
    return false;
}

// Evaluate one mission against a craft. Returns null if not completable,
// otherwise ['energy' => cost to pay from the craft].
function sar_mission_check(array $g, string $mid, array $craft): ?array {
    $seat = $craft['owner'];
    $node = $craft['node'];
    $hist = $craft['history'];
    $pl = sar_payload_info($craft);
    $pm = $pl['card']['mass'] ?? 0;                 // printed payload mass
    // Crewed missions also require a Pressurized tank on the craft.
    $crewed = $pl['card'] && in_array('Crewed', $pl['card']['tags'], true)
        && (bool)sar_craft_cards($craft, 'Tank', 'Pressurized');
    $atEarth = $node === 'earth';

    switch ($mid) {
        case 'M01': // LEO Deployment: reach LEO, uncrewed payload
            return ($node === 'leo' && $pl['uid'] && !$crewed) ? ['energy' => 0] : null;
        case 'M02': // Lunar Flyby: Moon Orbit + return to Earth
            return ($atEarth && in_array('moonOrbit', $hist, true)) ? ['energy' => 0] : null;
        case 'M03': // Lunar Landing (one-way): Moon surface, Lander or Rocket-as-Lander
            $lander = sar_craft_cards($craft, null, 'Lander') || sar_craft_has_engine_or_staged($craft);
            return ($node === 'moon' && $lander) ? ['energy' => 0] : null;
        case 'M04': // Mars Orbit Insertion: Mars High Orbit, payload Mass 2+
            return ($node === 'marsHigh' && $pm >= 2) ? ['energy' => 0] : null;
        case 'M05': // Deep Space Probe: Mars ZOI, Scientific payload Mass 2+, Sensor Array, 2 Energy
            $sci = $pl['card'] && in_array('Scientific', $pl['card']['tags'], true) && $pm >= 2;
            $sensor = (bool)array_filter($craft['cards'], fn($u) => explode('#', $u)[0] === 'S11');
            return (in_array('marsZoi', $hist, true) && $sci && $sensor && sar_can_pay_energy($craft, 2))
                ? ['energy' => 2] : null;
        case 'M06': // Crewed Station Visit: GEO, dock with station, return; Crewed + Docking + Engine
            $dockCard = (bool)sar_craft_cards($craft, null, 'Docking');
            return ($atEarth && $craft['docked'] && $crewed && $dockCard && sar_craft_has_engine_or_staged($craft))
                ? ['energy' => 0] : null;
        case 'M07': // Emergency Resupply: LEO + return, payload Mass 2+
            return ($atEarth && in_array('leo', $hist, true) && $pm >= 2) ? ['energy' => 0] : null;
        case 'M08': // Science Relay: High Orbit + return, Scientific/Electronics payload, 1 Energy
            $ok = $pl['card'] && (in_array('Scientific', $pl['card']['tags'], true) || in_array('Electronics', $pl['card']['tags'], true));
            return ($atEarth && in_array('geo', $hist, true) && $ok && sar_can_pay_energy($craft, 1))
                ? ['energy' => 1] : null;
        case 'M09': // Orbital Service Check: LEO → GEO → LEO; deployed Satellite in orbit; Engine
            return ($node === 'leo' && sar_history_seq($hist, ['leo', 'geo', 'leo'])
                && sar_craft_has_engine_or_staged($craft)
                && sar_player_has_deployed($g, $seat, 'Satellite', true)) ? ['energy' => 0] : null;
        case 'M10': // Capsule Recovery: land at Earth from Sub-Orbital, payload Mass 1+, Reentry support
            return ($atEarth && $craft['usedReentry'] && $pm >= 1) ? ['energy' => 0] : null;
        case 'M11': // Reusable Flight Test: Earth→Sub-Orbital→Earth, Reusable payload + Reusable Reentry
            $reusablePayload = $pl['uid'] && in_array('Reusable', $pl['card']['tags'], true);
            return ($atEarth && sar_history_seq($hist, ['earth', 'subEarth', 'earth'])
                && $reusablePayload && $craft['usedReusableReentry']) ? ['energy' => 0] : null;
        case 'M12': // Lunar Sample Return: Moon surface + return; Cargo Return Capsule; Reentry for Earth
            $cargo = (bool)array_filter($craft['cards'], fn($u) => explode('#', $u)[0] === 'P07');
            return ($atEarth && in_array('moon', $hist, true) && $cargo && $craft['usedReentry'])
                ? ['energy' => 0] : null;
        case 'M13': // Tourist Hop: Earth→Sub-Orbital→Earth, Crewed payload + Reentry support
            return ($atEarth && sar_history_seq($hist, ['earth', 'subEarth', 'earth'])
                && $crewed && $craft['usedReentry']) ? ['energy' => 0] : null;
        case 'M14': // Weather Satellite: deploy a Satellite payload at High Orbit (GEO)
            return ($craft['deployed'] && $node === 'geo' && $pl['uid'] && in_array('Satellite', $pl['card']['tags'], true))
                ? ['energy' => 0] : null;
        case 'M15': // Sounding Flight: Earth→Sub-Orbital→Earth, Scientific payload, 1 Energy
            $sci = $pl['card'] && in_array('Scientific', $pl['card']['tags'], true);
            return ($atEarth && sar_history_seq($hist, ['earth', 'subEarth', 'earth']) && $sci
                && sar_can_pay_energy($craft, 1)) ? ['energy' => 1] : null;
        case 'M16': // Lunar Orbiter: deploy a Satellite payload at Moon Orbit
            return ($craft['deployed'] && $node === 'moonOrbit' && $pl['uid'] && in_array('Satellite', $pl['card']['tags'], true))
                ? ['energy' => 0] : null;
        case 'M17': // Crewed Lunar Flyby: Moon Orbit + return, Crewed + Reentry support
            return ($atEarth && in_array('moonOrbit', $hist, true) && $crewed && $craft['usedReentry'])
                ? ['energy' => 0] : null;
        case 'M18': // Station Assembly: designate an On-Orbit Station
            return ($craft['isStation'] && $node === 'geo') ? ['energy' => 0] : null;
        case 'M19': // Mars Landing: Mars Surface, Lander or Rocket-as-Lander
            $lander = sar_craft_cards($craft, null, 'Lander') || sar_craft_has_engine_or_staged($craft);
            return ($node === 'mars' && $lander) ? ['energy' => 0] : null;
        case 'M20': // Asteroid Rendezvous: Sun Orbit, Scientific payload Mass 2+, Sensor Array, 2 Energy
            $sci = $pl['card'] && in_array('Scientific', $pl['card']['tags'], true) && $pm >= 2;
            $sensor = (bool)array_filter($craft['cards'], fn($u) => explode('#', $u)[0] === 'S11');
            return (in_array('sunOrbit', $hist, true) && $sci && $sensor && sar_can_pay_energy($craft, 2))
                ? ['energy' => 2] : null;
        case 'M21': // Suborbital Test Flight (standing contract): Earth -> Sub-Orbital -> Earth, land safely
            return ($atEarth && sar_history_seq($hist, ['earth', 'subEarth', 'earth'])) ? ['energy' => 0] : null;
    }
    return null;
}

// Try to claim standing contracts (always available, once per agency per game).
function sar_check_standing(array &$g, string $craftId): void {
    if (!isset($g['crafts'][$craftId])) return;
    $craft = $g['crafts'][$craftId];
    $seat = $craft['owner'];
    foreach (SAR_STANDING_CONTRACTS as $mid) {
        if (in_array($mid, $g['players'][$seat]['standingDone'], true)) continue;
        $craft = $g['crafts'][$craftId];
        $check = sar_mission_check($g, $mid, $craft);
        if ($check === null) continue;
        if ($check['energy'] > 0 && !sar_spend_energy($g, $craftId, $check['energy'], 'mission operations')) continue;
        $card = sar_card($mid);
        $vp = $card['vp'];
        $credits = $card['rewardCredits'];
        if (sar_has_tech($g, $seat, 'C07') && in_array('Commercial', $card['tags'], true)) $credits += 1;
        $p = &$g['players'][$seat];
        $p['vp'] += $vp;
        $p['credits'] += $credits;
        $p['missionsCompleted']++;
        $p['standingDone'][] = $mid;
        unset($p);
        sar_log($g, 'missionDone', '🏁 ' . sar_pname($g, $seat) . ' completes the standing contract ' .
            $card['name'] . "! Rewards: $vp VP, $credits Credits.",
            ['seat' => $seat, 'card' => $mid, 'vp' => $vp, 'credits' => $credits]);
    }
}

// Try to claim every display mission with this craft. Auto-claims on success.
function sar_check_missions(array &$g, string $craftId): void {
    if (!isset($g['crafts'][$craftId])) return;
    sar_check_standing($g, $craftId);
    foreach ($g['missions'] as $i => $muid) {
        if ($muid === null) continue;
        $craft = $g['crafts'][$craftId]; // re-read: a prior claim may have spent energy
        $mid = explode('#', $muid)[0];
        $check = sar_mission_check($g, $mid, $craft);
        if ($check === null) continue;
        $seat = $craft['owner'];

        // Comms Blackout: need a deployed Electronics asset, or pay 1 Credit to someone who has one.
        if (sar_event_id($g) === 'EV12' && !sar_player_has_deployed($g, $seat, 'Electronics')) {
            $provider = null;
            foreach ($g['players'] as $other) {
                if ($other['seat'] !== $seat && sar_player_has_deployed($g, $other['seat'], 'Electronics')) {
                    $provider = $other['seat'];
                    break;
                }
            }
            if ($provider === null || $g['players'][$seat]['credits'] < 1) {
                sar_log($g, 'event', 'Comms Blackout: ' . sar_pname($g, $seat) .
                    ' cannot complete ' . sar_card($muid)['name'] . ' without comms relay access this round.');
                continue;
            }
            $g['players'][$seat]['credits'] -= 1;
            $g['players'][$provider]['credits'] += 1;
            sar_log($g, 'event', 'Comms Blackout: ' . sar_pname($g, $seat) . ' pays 1 Credit to ' .
                sar_pname($g, $provider) . ' for relay access.');
        }

        if ($check['energy'] > 0 && !sar_spend_energy($g, $craftId, $check['energy'], 'mission operations')) {
            continue;
        }

        $card = sar_card($muid);
        $vp = $card['vp'];
        $credits = $card['rewardCredits'];
        $bonusBits = [];
        if (sar_event_id($g) === 'EV11' && in_array('Prestige', $card['tags'], true)) {
            $vp += 1;
            $bonusBits[] = 'Media Frenzy +1 VP';
        }
        if (sar_has_tech($g, $seat, 'C07') && in_array('Commercial', $card['tags'], true)) {
            $credits += 1;
            $bonusBits[] = 'Contracting Office +1 Credit';
        }
        // Habitation Ring: crewed missions that dock at the ring's station grant +1 VP.
        if ($mid === 'M06' && $craft['dockedHab']) {
            $vp += 1;
            $bonusBits[] = 'Habitation Ring +1 VP';
        }

        $p = &$g['players'][$seat];
        $p['vp'] += $vp;
        $p['credits'] += $credits;
        $p['missionsCompleted']++;
        unset($p);
        $g['missionDoneThisRound'] = true;
        array_splice($g['missions'], $i, 1);
        $g['decks']['missionDiscard'][] = $muid;

        sar_log($g, 'missionDone', '🏁 ' . sar_pname($g, $seat) . ' completes ' . $card['name'] .
            "! Rewards: $vp VP, $credits Credits" . ($bonusBits ? ' (' . implode(', ', $bonusBits) . ')' : '') . '.',
            ['seat' => $seat, 'card' => $muid, 'vp' => $vp, 'credits' => $credits]);

        sar_check_missions($g, $craftId); // indexes shifted — re-scan for further claims
        return;
    }
}
