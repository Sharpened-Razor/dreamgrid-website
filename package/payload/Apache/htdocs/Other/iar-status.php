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

$name = basename(trim($_GET['name'] ?? ''));
$folder = basename(trim($_GET['folder'] ?? ''));
$job = trim((string)($_GET['job'] ?? ''));
$isAdmin = ag_is_admin($s);
$avatar = trim((string)$s['avatar']);

if ($name === '' || !preg_match('/^[A-Za-z0-9_-]+_\d{4}-\d{2}-\d{2}_\d{2}_\d{2}_\d{2}\.iar$/', $name)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid IAR filename']);
    exit;
}

if ($folder === '' || !preg_match('/^AutoBackup-\d{4}-\d{2}-\d{2}$/', $folder)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid Autobackup folder']);
    exit;
}


if (!$isAdmin) {
    if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $job)) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'IAR ownership reference required']);
        exit;
    }

    $metaFile = __DIR__ . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'iar_meta_' . $job . '.json';
    $meta = json_decode((string)@file_get_contents($metaFile), true);

    if (!is_array($meta)) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'IAR ownership could not be verified']);
        exit;
    }

    $owner = trim((string)($meta['avatar'] ?? ''));
    $metaPath = (string)($meta['path'] ?? '');

    if ($owner === '' || strcasecmp($owner, $avatar) !== 0) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'Permission denied']);
        exit;
    }

    if ($metaPath !== '') {
        $expectedName = basename($metaPath);
        $expectedFolder = basename(dirname($metaPath));

        if (strcasecmp($expectedName, $name) !== 0 || strcasecmp($expectedFolder, $folder) !== 0) {
            http_response_code(403);
            echo json_encode(['ok'=>false,'error'=>'IAR reference does not belong to this job']);
            exit;
        }
    }
}

$root = ag_dg_autobackup_root();
$file = $root . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $name;

if (!is_file($file)) {
    echo json_encode([
        'ok'=>true,
        'found'=>false,
        'name'=>$name,
        'folder'=>$folder,
        'size'=>0,
        'mtime'=>0
    ]);
    exit;
}

clearstatcache(true, $file);
echo json_encode([
    'ok'=>true,
    'found'=>true,
    'name'=>$name,
    'folder'=>$folder,
    'size'=>@filesize($file) ?: 0,
    'mtime'=>@filemtime($file) ?: 0
]);
