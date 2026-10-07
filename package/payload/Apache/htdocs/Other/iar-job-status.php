<?php
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s = ag_current_session();
if (!$s) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Not logged in']);
    exit;
}

$job = trim($_GET['job'] ?? '');
if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $job)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid IAR job']);
    exit;
}


$isAdmin = ag_is_admin($s);
$avatar = trim((string)$s['avatar']);

$metaFile = __DIR__ . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'iar_meta_' . $job . '.json';
$meta = json_decode((string)@file_get_contents($metaFile), true);

if (!$isAdmin) {
    if (!is_array($meta)) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'IAR job ownership could not be verified']);
        exit;
    }

    $owner = trim((string)($meta['avatar'] ?? ''));
    if ($owner === '' || strcasecmp($owner, $avatar) !== 0) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'Permission denied']);
        exit;
    }
}

$log = __DIR__ . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'iar_' . $job . '.log';
if (!is_file($log)) {
    echo json_encode(['ok'=>true,'found'=>false,'error'=>'']);
    exit;
}

$text = @file_get_contents($log);
if ($text === false) $text = '';

$error = '';
if (preg_match('/^ERROR\s+.*?\s(.+)$/mi', $text, $m)) {
    $error = trim($m[1]);
}

$connectionClosed =
    stripos(
        $text,
        'CONNECTION_CLOSED '
    ) !== false;

$finished =
    strpos(
        $text,
        "\nEND "
    ) !== false
    ||
    strpos(
        $text,
        "END "
    ) === 0;

$successResponse =
    strpos(
        $text,
        '<name>success</name><value><boolean>1</boolean>'
    ) !== false;

$archivePath =
    is_array($meta)
    ? trim(
        (string)(
            $meta['path']
            ??
            ''
        )
    )
    : '';

$archiveSize =
    (
        $archivePath !== ''
        &&
        is_file($archivePath)
    )
    ?
    @filesize($archivePath)
    :
    false;

$archiveExists =
    $archiveSize !== false
    &&
    $archiveSize > 0;

$failed =
    $error !== ''
    ||
    (
        $connectionClosed
        &&
        !$successResponse
    )
    ||
    (
        $finished
        &&
        !$successResponse
    );

$verified =
    $finished
    &&
    $successResponse
    &&
    $archiveExists;

$complete =
    $verified;

if ($verified) {

    $message =
        'IAR backup completed successfully.';

} elseif ($failed) {

    $message =
        $error !== ''
        ?
        $error
        :
        'IAR backup failed.';

} elseif (
    $finished
    &&
    $successResponse
    &&
    !$archiveExists
) {

    $message =
        'IAR command completed. Waiting for archive verification.';

} else {

    $message =
        'IAR backup processing...';
}

echo json_encode([
    'ok'=>true,
    'found'=>true,
    'error'=>$error,
    'connectionClosed'=>$connectionClosed,
    'finished'=>$finished,
    'failed'=>$failed,
    'verified'=>$verified,
    'complete'=>$complete,
    'archiveExists'=>$archiveExists,
    'message'=>$message
]);
