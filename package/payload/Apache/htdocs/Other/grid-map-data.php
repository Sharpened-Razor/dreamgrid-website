<?php
require_once __DIR__ . '/login/session.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$session = dreamGridCurrentSession();

if (!$session) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

if ((int)$session['level'] < 200) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Grid Owner access required'
    ]);
    exit;
}

$australiaDreamGridRoot = null;
$australiaPathProbe = __DIR__;

while (true) {

    $hasSettings =
        is_file(
            $australiaPathProbe .
            DIRECTORY_SEPARATOR .
            'Settings.ini'
        );

    $hasOpenSim =
        is_dir(
            $australiaPathProbe .
            DIRECTORY_SEPARATOR .
            'Opensim'
        );

    if ($hasSettings && $hasOpenSim) {

        $resolved =
            realpath(
                $australiaPathProbe
            );

        $australiaDreamGridRoot =
            $resolved !== false
            ? $resolved
            : $australiaPathProbe;

        break;
    }

    $australiaPathParent =
        dirname(
            $australiaPathProbe
        );

    if (
        $australiaPathParent ===
        $australiaPathProbe
    ) {
        break;
    }

    $australiaPathProbe =
        $australiaPathParent;
}

$root =
    $australiaDreamGridRoot === null
    ? ''
    :
        $australiaDreamGridRoot .
        DIRECTORY_SEPARATOR .
        'Opensim' .
        DIRECTORY_SEPARATOR .
        'bin' .
        DIRECTORY_SEPARATOR .
        'Regions';

$regions = [];

if (!is_dir($root)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Regions folder not found'
    ]);
    exit;
}

$folders = glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

foreach ($folders as $folder) {

    $regionDir = $folder . DIRECTORY_SEPARATOR . 'Region';

    if (!is_dir($regionDir)) {
        continue;
    }

    $iniFiles = glob($regionDir . DIRECTORY_SEPARATOR . '*.ini') ?: [];

    foreach ($iniFiles as $iniFile) {

        if (preg_match('/\.bak$/i', $iniFile)) {
            continue;
        }

        $lines = @file($iniFile, FILE_IGNORE_NEW_LINES);

        if (!is_array($lines)) {
            continue;
        }

        $name = '';
        $location = '';
        $sizeX = 256;
        $sizeY = 256;
        $port = 0;
        $externalHost = '';

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line === '' || str_starts_with($line, ';')) {
                continue;
            }

            if ($name === '' && preg_match('/^\[(.+)\]$/', $line, $m)) {
                $name = trim($m[1]);
                continue;
            }

            if (preg_match('/^Location\s*=\s*(.+)$/i', $line, $m)) {
                $location = trim($m[1]);
            }
            elseif (preg_match('/^SizeX\s*=\s*(\d+)/i', $line, $m)) {
                $sizeX = (int)$m[1];
            }
            elseif (preg_match('/^SizeY\s*=\s*(\d+)/i', $line, $m)) {
                $sizeY = (int)$m[1];
            }
            elseif (preg_match('/^InternalPort\s*=\s*(\d+)/i', $line, $m)) {
                $port = (int)$m[1];
            }
            elseif (preg_match('/^ExternalHostName\s*=\s*(.+)$/i', $line, $m)) {
                $externalHost = trim($m[1]);
            }
        }

        if ($name === '' || $location === '') {
            continue;
        }

        $parts = array_map('trim', explode(',', $location));

        if (count($parts) !== 2) {
            continue;
        }

        $x = (int)$parts[0];
        $y = (int)$parts[1];

        $regions[] = [
            'RegionName' => $name,
            'X' => $x,
            'Y' => $y,
            'SizeX' => $sizeX,
            'SizeY' => $sizeY,
            'CellsX' => max(1, (int)round($sizeX / 256)),
            'CellsY' => max(1, (int)round($sizeY / 256)),
            'Port' => $port,
            'ExternalHostName' => $externalHost
        ];

        break;
    }
}

usort($regions, function($a, $b) {
    if ($a['Y'] === $b['Y']) {
        return $a['X'] <=> $b['X'];
    }
    return $a['Y'] <=> $b['Y'];
});

echo json_encode([
    'ok' => true,
    'regions' => $regions
]);