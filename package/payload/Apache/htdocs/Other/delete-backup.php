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

$root = ag_dg_autobackup_root();
$rootReal = realpath($root);

function insideBackupRoot($path, $rootReal) {
    if ($rootReal === false) return false;
    $real = realpath($path);
    if ($real === false) return false;
    $prefix = rtrim(strtolower(str_replace('\\','/',$rootReal)), '/') . '/';
    $candidate = strtolower(str_replace('\\','/',$real));
    return strpos($candidate, $prefix) === 0 ? $real : false;
}

$type = strtolower(trim($_POST['type'] ?? ''));
$path = false;
$metaFile = null;

if ($type === 'iar') {
    $job = trim($_POST['job'] ?? '');
    if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $job)) {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid IAR reference']); exit;
    }
    $metaFile = __DIR__ . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'iar_meta_' . $job . '.json';
    $m = json_decode((string)@file_get_contents($metaFile), true);
    if (!is_array($m)) {
        http_response_code(404); echo json_encode(['ok'=>false,'error'=>'IAR record not found']); exit;
    }
    $path = insideBackupRoot((string)($m['path'] ?? ''), $rootReal);
} elseif ($type === 'oar') {
    $rel = str_replace('\\','/',trim($_POST['file'] ?? ''));
    if ($rel === '' || strpos($rel, '..') !== false || !preg_match('/\.oar$/i', $rel)) {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid OAR reference']); exit;
    }
    $path = insideBackupRoot($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel), $rootReal);
} else {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid backup type']); exit;
}

if ($path === false || !is_file($path)) {
    http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Backup file not found']); exit;
}

$name = basename($path);
if (!@unlink($path)) {
    http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Could not delete backup file']); exit;
}

if ($metaFile && is_file($metaFile)) @unlink($metaFile);

echo json_encode(['ok'=>true,'deleted'=>$name]);
