<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/auth.php';

$s = ag_current_session();
if (!$s) {
    http_response_code(401);
    exit('Not logged in');
}

$avatar = trim((string)$s['avatar']);
$isAdmin = ag_is_admin($s);
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


function ag_download_oar_region_name(string $path): string
{
    $name = basename($path);

    if (
        preg_match(
            '/^(.*)_\d{4}-\d{2}-\d{2}_\d{2}_\d{2}_\d{2}(?:\([^)]*\))?\.oar$/i',
            $name,
            $m
        ) !== 1
    ) {
        return '';
    }

    return trim((string)$m[1]);
}

function ag_download_oar_is_owned(string $avatar, string $region): bool
{
    $page = 1;
    $rp = 500;

    while ($page <= 100) {

        $url =
            rtrim(ag_dg_diagnostics_base(), '/') .
            '/?command=regionlist&page=' .
            $page .
            '&rp=' .
            $rp .
            '&sortorder=asc';

        $json = @file_get_contents($url);

        if ($json === false) {
            return false;
        }

        $data = json_decode($json, true);
        $rows = is_array($data['rows'] ?? null)
            ? $data['rows']
            : [];

        foreach ($rows as $row) {

            $cell = $row['cell'] ?? [];

            $regionName =
                trim((string)($cell['RegionName'] ?? ''));

            $estateOwner =
                trim((string)($cell['EstateOwner'] ?? ''));

            if (
                strcasecmp($regionName, $region) === 0 &&
                strcasecmp($estateOwner, $avatar) === 0
            ) {
                return true;
            }
        }

        if (count($rows) < $rp) {
            break;
        }

        $page++;
    }

    return false;
}

foreach (['type', 'job', 'file'] as $field) {
    if (isset($_GET[$field]) && (!is_string($_GET[$field]) || preg_match('/[\x00-\x1f\x7f]/', $_GET[$field]))) {
        http_response_code(400); exit('Invalid backup reference');
    }
}
$type = strtolower(trim($_GET['type'] ?? ''));
$path = false;

if ($type === 'iar') {
    $job = trim($_GET['job'] ?? '');
    if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $job)) {
        http_response_code(400); exit('Invalid IAR reference');
    }

    $metaFile = __DIR__ . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'iar_meta_' . $job . '.json';
    $m = json_decode((string)@file_get_contents($metaFile), true);
    if (!is_array($m)) {
        http_response_code(404); exit('IAR record not found');
    }

    $owner = trim((string)($m['avatar'] ?? ''));
    if (!$isAdmin && strcasecmp($owner, $avatar) !== 0) {
        http_response_code(403); exit('Permission denied');
    }

    $log = __DIR__ . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'iar_' . $job . '.log';
    $logText = is_file($log) ? (string)@file_get_contents($log) : '';
    $finished = strpos($logText, "\nEND ") !== false || strpos($logText, "END ") === 0;
    if (!$finished) {
        http_response_code(409); exit('IAR backup is not complete');
    }

    $path = insideBackupRoot((string)($m['path'] ?? ''), $rootReal);
} elseif ($type === 'oar') {
    if (
        !$isAdmin &&
        !ag_has_user_level(50, $s)
    ) {
        http_response_code(403);
        exit('Region owner access required');
    }

    $rel = str_replace('\\','/',trim($_GET['file'] ?? ''));
    if ($rel === '' || str_starts_with($rel, '/') || strpos($rel, ':') !== false || strpos($rel, '..') !== false || !preg_match('/\.oar$/i', $rel)) {
        http_response_code(400); exit('Invalid OAR reference');
    }

    $path = insideBackupRoot($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel), $rootReal);
    if ($path === false || !is_file($path)) {
        http_response_code(404); exit('Backup file not found');
    }
    if (!$isAdmin) {

        $regionName =
            ag_download_oar_region_name($path);

        if (
            $regionName === '' ||
            !ag_download_oar_is_owned(
                $avatar,
                $regionName
            )
        ) {
            http_response_code(403);
            exit('Region owner access required');
        }
    }
} else {
    http_response_code(400); exit('Invalid backup type');
}

if ($path === false || !is_file($path)) {
    http_response_code(404); exit('Backup file not found');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if ($ext !== $type) {
    http_response_code(403); exit('Invalid backup file');
}

$size = filesize($path);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"','',basename($path)) . '"');
header('Content-Length: ' . $size);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$fh = fopen($path, 'rb');
while (!feof($fh)) {
    echo fread($fh, 1024 * 1024);
    flush();
}
fclose($fh);
exit;
