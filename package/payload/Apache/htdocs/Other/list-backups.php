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

$avatar = trim((string)$s['avatar']);
$isAdmin = ag_is_admin($s);
$root = ag_dg_autobackup_root();
$items = [];
function ag_oar_region_name(string $path): string
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

function ag_oar_owned_regions(string $avatar): array
{
    $owned = [];
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
            break;
        }

        $data = json_decode($json, true);
        $rows = is_array($data['rows'] ?? null)
            ? $data['rows']
            : [];

        foreach ($rows as $row) {
            $cell = $row['cell'] ?? [];

            $region =
                trim((string)($cell['RegionName'] ?? ''));

            $owner =
                trim((string)($cell['EstateOwner'] ?? ''));

            if (
                $region !== '' &&
                strcasecmp($owner, $avatar) === 0
            ) {
                $owned[] = $region;
            }
        }

        if (count($rows) < $rp) {
            break;
        }

        $page++;
    }

    return array_values(array_unique($owned));
}

function ag_oar_region_owned(array $owned, string $region): bool
{
    foreach ($owned as $name) {
        if (strcasecmp((string)$name, $region) === 0) {
            return true;
        }
    }

    return false;
}

// IARs created by V40+ have durable ownership metadata.
// Normal users see only records whose owner exactly matches their signed session.
$jobDir = __DIR__ . DIRECTORY_SEPARATOR . 'jobs';
foreach (glob($jobDir . DIRECTORY_SEPARATOR . 'iar_meta_*.json') ?: [] as $metaFile) {
    $m = json_decode((string)@file_get_contents($metaFile), true);
    if (!is_array($m)) continue;

    $owner = trim((string)($m['avatar'] ?? ''));
    if (!$isAdmin && strcasecmp($owner, $avatar) !== 0) continue;

    $path = (string)($m['path'] ?? '');
    if (!is_file($path)) continue;

    $job = (string)($m['jobId'] ?? '');
    if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $job)) continue;

    $log = $jobDir . DIRECTORY_SEPARATOR . 'iar_' . $job . '.log';
    $logText = is_file($log) ? (string)@file_get_contents($log) : '';
    $finished = strpos($logText, "\nEND ") !== false || strpos($logText, "END ") === 0;
    if (!$finished) continue;

    $items[] = [
        'type'=>'IAR',
        'owner'=>$owner,
        'name'=>basename($path),
        'size'=>(int)@filesize($path),
        'modified'=>(int)@filemtime($path),
        'download'=>'/Other/download-backup.php?type=iar&job=' . rawurlencode($job),
        'delete'=>$isAdmin ? ['type'=>'iar','job'=>$job] : null,
        'restore'=>$isAdmin ? ['type'=>'iar','job'=>$job] : null,
    ];
}

// OAR access:
// Level 200+ administrators may browse all region OARs.
// Level 50+ region owners may browse only OARs for regions where
// DreamGrid reports their signed-in avatar as EstateOwner.
// Parcel ownership is intentionally not used.

$canUseOwnerOars = ag_has_user_level(50, $s);
$ownedOarRegions =
    (!$isAdmin && $canUseOwnerOars)
    ? ag_oar_owned_regions($avatar)
    : [];

if (($isAdmin || $canUseOwnerOars) && is_dir($root)) {
    $dirs = glob(
        $root . DIRECTORY_SEPARATOR . 'AutoBackup-*',
        GLOB_ONLYDIR
    ) ?: [];

    foreach ($dirs as $dayDir) {
        $oarDir = $dayDir . DIRECTORY_SEPARATOR . 'OAR';

        if (!is_dir($oarDir)) continue;

        foreach (glob($oarDir . DIRECTORY_SEPARATOR . '*.oar') ?: [] as $path) {
            if (!is_file($path)) continue;

            $regionName = ag_oar_region_name($path);

            if (
                !$isAdmin &&
                (
                    $regionName === '' ||
                    !ag_oar_region_owned($ownedOarRegions, $regionName)
                )
            ) {
                continue;
            }

            $rel = substr($path, strlen($root) + 1);

            $items[] = [
                'type'=>'OAR',
                'owner'=>$isAdmin ? 'GRID' : $avatar,
                'name'=>basename($path),
                'size'=>(int)@filesize($path),
                'modified'=>(int)@filemtime($path),
                'download'=>'/Other/download-backup.php?type=oar&file=' .
                    rawurlencode(str_replace('\\','/',$rel)),
                'delete'=>$isAdmin
                    ? ['type'=>'oar','file'=>str_replace('\\','/',$rel)]
                    : null,
                'restore'=>$isAdmin
                    ? ['type'=>'oar','file'=>str_replace('\\','/',$rel)]
                    : null,
            ];
        }
    }
}

usort($items, function($a,$b){ return ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0); });
$items = array_slice($items, 0, 100);

echo json_encode(['ok'=>true,'admin'=>$isAdmin,'backups'=>$items]);

