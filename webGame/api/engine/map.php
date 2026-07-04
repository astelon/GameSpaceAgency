<?php
// Orbital node map for Space Agency Race.

const SAR_NODES = [
    'earth'     => ['name' => 'Earth',             'dist' => 0,  'surface' => true,  'atmo' => true],
    'subEarth'  => ['name' => 'Sub-Orbital Earth', 'dist' => 1,  'surface' => false, 'atmo' => true],
    'leo'       => ['name' => 'LEO',               'dist' => 2,  'surface' => false, 'atmo' => false],
    'geo'       => ['name' => 'High Orbit (GEO)',  'dist' => 3,  'surface' => false, 'atmo' => false],
    'earthZoi'  => ['name' => 'Earth ZOI',         'dist' => 4,  'surface' => false, 'atmo' => false],
    'moonOrbit' => ['name' => 'Moon Orbit',        'dist' => 5,  'surface' => false, 'atmo' => false],
    'subMoon'   => ['name' => 'Sub-Orbital Moon',  'dist' => 6,  'surface' => false, 'atmo' => false],
    'moon'      => ['name' => 'Moon',              'dist' => 7,  'surface' => true,  'atmo' => false],
    'sunOrbit'  => ['name' => 'Sun Orbit',         'dist' => 5,  'surface' => false, 'atmo' => false],
    'marsZoi'   => ['name' => 'Mars ZOI',          'dist' => 6,  'surface' => false, 'atmo' => false],
    'marsHigh'  => ['name' => 'Mars High Orbit',   'dist' => 7,  'surface' => false, 'atmo' => false],
    'marsLow'   => ['name' => 'Mars Low Orbit',    'dist' => 8,  'surface' => false, 'atmo' => false],
    'subMars'   => ['name' => 'Sub-Orbital Mars',  'dist' => 9,  'surface' => false, 'atmo' => true],
    'mars'      => ['name' => 'Mars Surface',      'dist' => 10, 'surface' => true,  'atmo' => true],
];

// Undirected edges. 'tw' edges cost the current Transfer Window value.
const SAR_EDGES = [
    ['earth', 'subEarth'], ['subEarth', 'leo'], ['leo', 'geo'], ['geo', 'earthZoi'],
    ['earthZoi', 'moonOrbit'], ['moonOrbit', 'subMoon'], ['subMoon', 'moon'],
    ['earthZoi', 'sunOrbit'], ['sunOrbit', 'marsZoi', 'tw'],
    ['marsZoi', 'marsHigh'], ['marsHigh', 'marsLow'], ['marsLow', 'subMars'], ['subMars', 'mars'],
];

// Nodes that count as "beyond Earth ZOI" for income scaling / relay triggers.
function sar_beyond_zoi(string $node): bool {
    return !in_array($node, ['earth', 'subEarth', 'leo', 'geo', 'earthZoi'], true);
}

// True if the two nodes are adjacent; returns edge info array or null.
function sar_edge(string $a, string $b): ?array {
    foreach (SAR_EDGES as $e) {
        if (($e[0] === $a && $e[1] === $b) || ($e[0] === $b && $e[1] === $a)) {
            return ['tw' => isset($e[2]) && $e[2] === 'tw'];
        }
    }
    return null;
}

// 'assembly' (the launch-pad area for unlaunched rockets) counts as ground.
function sar_is_surface(string $node): bool { return !isset(SAR_NODES[$node]) || SAR_NODES[$node]['surface']; }
function sar_is_atmo(string $node): bool { return isset(SAR_NODES[$node]) ? SAR_NODES[$node]['atmo'] : true; }

// "In space" = any node that is not a surface (used for deploys and solar panels).
function sar_in_space(string $node): bool { return isset(SAR_NODES[$node]) && !SAR_NODES[$node]['surface']; }

const SAR_MOON_BRANCH = ['moonOrbit', 'subMoon', 'moon'];
const SAR_MARS_BRANCH = ['marsZoi', 'marsHigh', 'marsLow', 'subMars', 'mars'];

// Transfer Window printed cycle; marker starts on index 0.
const SAR_TW_CYCLE = [3, 2, 1, 0, 1, 2, 3, 4];
