<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s = ag_current_session();

if (!$s) {
    http_response_code(401);
    echo json_encode([
        'ok'    => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

if (!ag_is_admin($s)) {
    http_response_code(403);
    echo json_encode([
        'ok'    => false,
        'error' => 'Grid Owner access required'
    ]);
    exit;
}

$raw = (string)($_POST['items'] ?? '');
$items = json_decode($raw, true);
if (!is_array($items) || !$items) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'No backup files selected']); exit; }
if (count($items) > 100) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Too many files selected']); exit; }

$root = ag_dg_autobackup_root();
$rootReal = realpath($root);
$jobDir = __DIR__ . DIRECTORY_SEPARATOR . 'jobs';

function v53InsideBackupRoot($path, $rootReal) {
    if ($rootReal === false) return false;
    $real = realpath($path);
    if ($real === false) return false;
    $prefix = rtrim(strtolower(str_replace('\\','/',$rootReal)), '/') . '/';
    $candidate = strtolower(str_replace('\\','/',$real));
    return strpos($candidate, $prefix) === 0 ? $real : false;
}

$targets = [];
$seen = [];
foreach ($items as $item) {
    if (!is_array($item)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid selection']); exit; }
    $type = strtolower(trim((string)($item['type'] ?? '')));
    $path = false; $metaFile = null;

    if ($type === 'iar') {
        $job = trim((string)($item['job'] ?? ''));
        if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $job)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid IAR reference']); exit; }
        $metaFile = $jobDir . DIRECTORY_SEPARATOR . 'iar_meta_' . $job . '.json';
        $m = json_decode((string)@file_get_contents($metaFile), true);
        if (!is_array($m)) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'IAR record not found']); exit; }
        $path = v53InsideBackupRoot((string)($m['path'] ?? ''), $rootReal);
    } elseif ($type === 'oar') {
        $rel = str_replace('\\','/',trim((string)($item['file'] ?? '')));
        if ($rel === '' || strpos($rel, '..') !== false || !preg_match('/\.oar$/i', $rel)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid OAR reference']); exit; }
        $path = v53InsideBackupRoot($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel), $rootReal);
    } else { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid backup type']); exit; }

    if ($path === false || !is_file($path)) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'A selected backup file was not found']); exit; }
    $key = strtolower(str_replace('\\','/',$path));
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $targets[] = ['path'=>$path,'meta'=>$metaFile,'name'=>basename($path),'size'=>(int)@filesize($path)];
}

// Validation above completes before the first unlink, so a bad reference cannot cause a partial delete.
$deleted = []; $bytes = 0; $failed = [];
foreach ($targets as $t) {
    if (@unlink($t['path'])) {
        if ($t['meta'] && is_file($t['meta'])) @unlink($t['meta']);
        $deleted[] = $t['name']; $bytes += $t['size'];
    } else $failed[] = $t['name'];
}

if ($failed) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Some files could not be deleted','deleted'=>$deleted,'failed'=>$failed,'bytesFreed'=>$bytes]); exit;
}
echo json_encode(['ok'=>true,'deleted'=>$deleted,'count'=>count($deleted),'bytesFreed'=>$bytes]);
