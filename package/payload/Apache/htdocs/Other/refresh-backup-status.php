<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s = ag_current_session();
if (!$s) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Not logged in']);
    exit;
}

if (!ag_is_admin($s)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']);
    exit;
}

$region = trim($_GET['region'] ?? '');
if ($region === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Region name is required']);
    exit;
}

$regionsRoot = ag_dg_regions_root();

if (!is_dir($regionsRoot)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'OpenSim Regions folder was not found']);
    exit;
}

function readHttpPort($iniFile) {
    if (!is_file($iniFile)) return 0;

    $text = @file_get_contents($iniFile);
    if ($text === false) return 0;

    if (preg_match('/^\s*http_listener_port\s*=\s*(\d+)\s*$/mi', $text, $m)) {
        return (int)$m[1];
    }

    return 0;
}

function fetchSStats($port) {
    $url = ag_web_local_base((int)$port) . '/SStats/default.report';

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 6,
            'ignore_errors' => true,
            'header' => "Connection: close\r\nCache-Control: no-cache\r\n"
        ]
    ]);

    return @file_get_contents($url, false, $ctx);
}

$matches = [];

/*
 * DreamGrid region folder names usually match the region name, but not always
 * (this grid has "Offical Region" on disk while the live region is
 * "Official Region").  To avoid hard-coding that typo, inspect each running
 * region's own SStats page and choose the page that identifies the requested
 * region.
 */
foreach (glob($regionsRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $dir) {
    $ini = $dir . DIRECTORY_SEPARATOR . 'Opensim.ini';
    $port = readHttpPort($ini);
    if ($port <= 0) continue;

    $html = fetchSStats($port);
    if ($html === false || $html === '') continue;

    $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plain = preg_replace('/\s+/', ' ', $plain);

    if (stripos($plain, $region) !== false) {
        $matches[] = [
            'port' => $port,
            'folder' => basename($dir)
        ];
    }
}

if (!$matches) {
    http_response_code(404);
    echo json_encode([
        'ok'=>false,
        'error'=>'Could not locate the SStats page for ' . $region
    ]);
    exit;
}

/* A region should resolve to one SStats page. Use the first confirmed match. */
$match = $matches[0];

/* Fetch once more to reproduce the manual "open SStats" refresh action. */
$response = fetchSStats($match['port']);
if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'ok'=>false,
        'error'=>'Region SStats refresh did not respond'
    ]);
    exit;
}

echo json_encode([
    'ok'=>true,
    'region'=>$region,
    'port'=>$match['port'],
    'folder'=>$match['folder']
]);
