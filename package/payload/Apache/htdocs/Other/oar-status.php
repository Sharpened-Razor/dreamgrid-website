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
$started = (int)($_GET['started'] ?? 0);

if ($region === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Region required']);
    exit;
}

$root = ag_dg_autobackup_root();

if (!is_dir($root)) {
    echo json_encode(['ok'=>false,'error'=>'Autobackup folder not found']);
    exit;
}

/* Fast: inspect only top-level AutoBackup-* folders. */
$backupDirs = [];
$dh = @opendir($root);
if ($dh === false) {
    echo json_encode(['ok'=>false,'error'=>'Unable to open Autobackup folder']);
    exit;
}

while (($name = readdir($dh)) !== false) {
    if ($name === '.' || $name === '..') continue;
    if (stripos($name, 'AutoBackup-') !== 0) continue;

    $full = $root . DIRECTORY_SEPARATOR . $name;
    if (is_dir($full)) {
        $backupDirs[] = ['name'=>$name, 'path'=>$full];
    }
}
closedir($dh);

if (!$backupDirs) {
    echo json_encode(['ok'=>true,'found'=>false,'status'=>'waiting','region'=>$region]);
    exit;
}

/* Folder names sort correctly by YYYY-MM-DD. */
usort($backupDirs, function($a,$b){ return strcmp($b['name'], $a['name']); });

$oarDir = $backupDirs[0]['path'] . DIRECTORY_SEPARATOR . 'OAR';

if (!is_dir($oarDir)) {
    echo json_encode([
        'ok'=>true,
        'found'=>false,
        'status'=>'waiting',
        'region'=>$region,
        'backupFolder'=>$backupDirs[0]['name']
    ]);
    exit;
}

/* Fast: inspect only files directly in the current OAR folder. */
$matches = [];
$od = @opendir($oarDir);
if ($od === false) {
    echo json_encode(['ok'=>false,'error'=>'Unable to open current OAR folder']);
    exit;
}

$prefix = $region . '_';

while (($name = readdir($od)) !== false) {
    if ($name === '.' || $name === '..') continue;
    if (stripos($name, $prefix) !== 0) continue;
    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'oar') continue;

    $full = $oarDir . DIRECTORY_SEPARATOR . $name;
    if (!is_file($full)) continue;

    clearstatcache(true, $full);
    $mtime = @filemtime($full) ?: 0;

    if ($started > 0 && $mtime < ($started - 10)) continue;

    $matches[] = [
        'name'=>$name,
        'size'=>@filesize($full) ?: 0,
        'mtime'=>$mtime
    ];
}
closedir($od);

if (!$matches) {
    echo json_encode([
        'ok'=>true,
        'found'=>false,
        'status'=>'waiting',
        'region'=>$region,
        'backupFolder'=>$backupDirs[0]['name']
    ]);
    exit;
}

usort($matches, function($a,$b){ return $b['mtime'] <=> $a['mtime']; });
$f = $matches[0];

echo json_encode([
    'ok'=>true,
    'found'=>true,
    'region'=>$region,
    'status'=>$f['size'] > 0 ? 'writing' : 'created',
    'name'=>$f['name'],
    'size'=>$f['size'],
    'mtime'=>$f['mtime'],
    'backupFolder'=>$backupDirs[0]['name']
]);
