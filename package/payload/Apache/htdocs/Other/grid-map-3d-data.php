<?php
require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/core/dreamgrid-env.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$session = dreamGridCurrentSession();

if (!$session) {
    http_response_code(401);
    echo json_encode(
        [
            'ok' => false,
            'error' => 'Not logged in'
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

function ag3d_normalise_name($value) {
    return strtolower(trim((string)$value));
}

function ag3d_region_status($regionName) {
    $url =
        ag_dg_diagnostics_base() . '/?command=RegionStatus&RegionName=' .
        rawurlencode($regionName);

    $ctx = stream_context_create(
        [
            'http' => [
                'method' => 'GET',
                'timeout' => 3,
                'ignore_errors' => true
            ]
        ]
    );

    $response = @file_get_contents($url, false, $ctx);

    if ($response === false) {
        return 'Unknown';
    }

    $status = trim((string)$response);

    return ($status !== '') ? $status : 'Unknown';
}

function ag3d_status_class($status) {
    $status = strtolower(trim((string)$status));

    if (
        strpos($status, 'booted') !== false ||
        strpos($status, 'online') !== false ||
        strpos($status, 'running') !== false
    ) {
        return 'online';
    }

    if (
        strpos($status, 'starting') !== false ||
        strpos($status, 'stopping') !== false ||
        strpos($status, 'backup') !== false ||
        strpos($status, 'busy') !== false ||
        strpos($status, 'warning') !== false
    ) {
        return 'warning';
    }

    return 'offline';
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

if (!is_dir($root)) {
    echo json_encode(
        [
            'ok' => false,
            'error' => 'Regions folder not found'
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

$metaLookup = [];
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

            $line = trim((string)$line);

            if ($line === '' || strpos($line, ';') === 0) {
                continue;
            }

            if ($name === '' && preg_match('/^\[(.+)\]$/', $line, $m)) {
                $name = trim((string)$m[1]);
                continue;
            }

            if (preg_match('/^Location\s*=\s*(.+)$/i', $line, $m)) {
                $location = trim((string)$m[1]);
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
                $externalHost = trim((string)$m[1]);
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

        $metaLookup[ag3d_normalise_name($name)] = [
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

$liveLookup = [];

$url =
    ag_dg_diagnostics_base() . '/?command=regionlist&page=1&rp=500&sortorder=asc';

$json = @file_get_contents($url);

if ($json !== false) {

    $data = json_decode($json, true);

    if (is_array($data)) {

        $rows = (isset($data['rows']) && is_array($data['rows']))
            ? $data['rows']
            : [];

        foreach ($rows as $row) {

            $cell = (isset($row['cell']) && is_array($row['cell']))
                ? $row['cell']
                : [];

            $regionName = trim((string)($cell['RegionName'] ?? ''));

            if ($regionName === '') {
                continue;
            }

            $key = ag3d_normalise_name($regionName);

            $liveLookup[$key] = [
                'EstateName'  => (string)($cell['EstateName'] ?? ''),
                'EstateOwner' => (string)($cell['EstateOwner'] ?? ''),
                'AvatarCount' => (int)($cell['AvatarCount'] ?? 0),
                'PrimCount'   => (int)($cell['PrimCount'] ?? 0),
                'Ram'         => (string)($cell['Ram'] ?? ''),
                'Size'        => (string)($cell['Size'] ?? ''),
                'Status'      => ag3d_region_status($regionName)
            ];
        }
    }
}

$regions = [];
$hostFallback = trim((string)($_SERVER['HTTP_HOST'] ?? ag_dg_hostname()));

foreach ($metaLookup as $key => $meta) {

    $live = $liveLookup[$key] ?? [];

    $host = trim((string)($meta['ExternalHostName'] ?? ''));
    if ($host === '') {
        $host = $hostFallback;
    }

    $hopUrl =
        'hop://' .
        $host . '/' .
        rawurlencode((string)$meta['RegionName']) .
        '/128/128/25';

    $status = (string)($live['Status'] ?? 'Unknown');

    $regions[] = [
        'RegionName'       => (string)$meta['RegionName'],
        'X'                => (int)$meta['X'],
        'Y'                => (int)$meta['Y'],
        'SizeX'            => (int)$meta['SizeX'],
        'SizeY'            => (int)$meta['SizeY'],
        'CellsX'           => (int)$meta['CellsX'],
        'CellsY'           => (int)$meta['CellsY'],
        'Port'             => (int)$meta['Port'],
        'ExternalHostName' => (string)$host,
        'EstateName'       => (string)($live['EstateName'] ?? ''),
        'EstateOwner'      => (string)($live['EstateOwner'] ?? ''),
        'AvatarCount'      => (int)($live['AvatarCount'] ?? 0),
        'PrimCount'        => (int)($live['PrimCount'] ?? 0),
        'Ram'              => (string)($live['Ram'] ?? ''),
        'Status'           => $status,
        'StatusClass'      => ag3d_status_class($status),
        'HopUrl'           => $hopUrl
    ];
}

usort(
    $regions,
    function ($a, $b) {
        if ($a['Y'] === $b['Y']) {
            return $a['X'] <=> $b['X'];
        }
        return $a['Y'] <=> $b['Y'];
    }
);

$minX = null;
$maxX = null;
$minY = null;
$maxY = null;

foreach ($regions as $region) {
    $left   = (int)$region['X'];
    $right  = (int)$region['X'] + (int)$region['CellsX'];
    $bottom = (int)$region['Y'];
    $top    = (int)$region['Y'] + (int)$region['CellsY'];

    $minX = ($minX === null) ? $left : min($minX, $left);
    $maxX = ($maxX === null) ? $right : max($maxX, $right);
    $minY = ($minY === null) ? $bottom : min($minY, $bottom);
    $maxY = ($maxY === null) ? $top : max($maxY, $top);
}

$payload = [
    'ok' => true,
    'regions' => $regions,
    'bounds' => [
        'minX' => $minX,
        'maxX' => $maxX,
        'minY' => $minY,
        'maxY' => $maxY
    ]
];

$encoded = json_encode(
    $payload,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_INVALID_UTF8_SUBSTITUTE
);

if ($encoded === false) {
    http_response_code(500);

    echo json_encode(
        [
            'ok' => false,
            'error' => '3D map JSON encoding failed: ' . json_last_error_msg()
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

echo $encoded;