<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s = ag_current_session();

if (!$s) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

if (!ag_is_admin($s)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Grid Owner access required'
    ]);
    exit;
}


/*
 * Read-only DreamGrid OAR metadata.
 *
 * Uses the same Autobackup location convention as backup-storage.php.
 * No files are created, moved, renamed or deleted here.
 */

$root =
    ag_dg_autobackup_root();


$drivePath =
    is_dir($root)
    ?
    $root
    :
    ag_dg_root();


$regions = [];

$totalCount = 0;
$totalBytes = 0;

$globalNewest = null;
$globalOldest = null;


function backupMetaFile(
    string $path
): array {

    $mtime =
        (int)@filemtime($path);

    $bytes =
        (int)@filesize($path);

    return [
        'name' =>
            basename($path),

        'bytes' =>
            $bytes,

        'mtime' =>
            $mtime,

        'date' =>
            $mtime > 0
            ?
            date('c', $mtime)
            :
            null,
    ];
}


function backupMetaRegionName(
    string $name
): string {

    $region =
        preg_replace(
            '/_\d{4}-\d{2}-\d{2}_\d{2}_\d{2}_\d{2}(?:\(\d+X\d+\))?\.oar$/i',
            '',
            $name
        );

    if (
        !$region
        ||
        $region === $name
    ) {

        $region =
            preg_replace(
                '/\.oar$/i',
                '',
                $name
            );
    }

    return trim(
        (string)$region
    );
}


if (is_dir($root)) {

    foreach (
        glob(
            $root
            . DIRECTORY_SEPARATOR
            . 'AutoBackup-*',
            GLOB_ONLYDIR
        ) ?: []
        as $dayDir
    ) {

        $oarDir =
            $dayDir
            . DIRECTORY_SEPARATOR
            . 'OAR';

        if (!is_dir($oarDir)) {
            continue;
        }


        foreach (
            glob(
                $oarDir
                . DIRECTORY_SEPARATOR
                . '*.oar'
            ) ?: []
            as $path
        ) {

            if (!is_file($path)) {
                continue;
            }


            $name =
                basename($path);

            $region =
                backupMetaRegionName(
                    $name
                );


            if ($region === '') {
                $region = 'Unknown Region';
            }


            $file =
                backupMetaFile(
                    $path
                );


            if (!isset($regions[$region])) {

                $regions[$region] = [
                    'region' =>
                        $region,

                    'count' =>
                        0,

                    'bytes' =>
                        0,

                    'newest' =>
                        null,

                    'oldest' =>
                        null,
                ];
            }


            $regions[$region]['count']++;

            $regions[$region]['bytes'] +=
                (int)$file['bytes'];


            $mtime =
                (int)$file['mtime'];


            if (
                $regions[$region]['newest'] === null
                ||
                $mtime >
                (int)$regions[$region]['newest']['mtime']
            ) {

                $regions[$region]['newest'] =
                    $file;
            }


            if (
                $regions[$region]['oldest'] === null
                ||
                $mtime <
                (int)$regions[$region]['oldest']['mtime']
            ) {

                $regions[$region]['oldest'] =
                    $file;
            }


            if (
                $globalNewest === null
                ||
                $mtime >
                (int)$globalNewest['mtime']
            ) {

                $globalNewest =
                    array_merge(
                        [
                            'region' =>
                                $region
                        ],
                        $file
                    );
            }


            if (
                $globalOldest === null
                ||
                $mtime <
                (int)$globalOldest['mtime']
            ) {

                $globalOldest =
                    array_merge(
                        [
                            'region' =>
                                $region
                        ],
                        $file
                    );
            }


            $totalCount++;

            $totalBytes +=
                (int)$file['bytes'];
        }
    }
}


uksort(
    $regions,
    'strnatcasecmp'
);


$free =
    @disk_free_space(
        $drivePath
    );

$total =
    @disk_total_space(
        $drivePath
    );


echo json_encode(
    [
        'ok' =>
            true,

        'generatedAt' =>
            date('c'),

        'summary' => [
            'oarCount' =>
                $totalCount,

            'oarBytes' =>
                $totalBytes,

            'driveFreeBytes' =>
                $free === false
                ?
                null
                :
                (int)$free,

            'driveTotalBytes' =>
                $total === false
                ?
                null
                :
                (int)$total,

            'newest' =>
                $globalNewest,

            'oldest' =>
                $globalOldest,
        ],

        'regions' =>
            array_values(
                $regions
            ),
    ],
    JSON_UNESCAPED_SLASHES
);