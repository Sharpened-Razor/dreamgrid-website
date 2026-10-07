<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

@set_time_limit(600);

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


$region =
    trim(
        (string)(
            $_GET['region']
            ??
            ''
        )
    );


if ($region === '') {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'error' => 'Region is required'
    ]);

    exit;
}


/*
 * READ-ONLY OAR VERIFICATION.
 *
 * Nothing is created, renamed, moved or deleted.
 */

$root =
    ag_dg_autobackup_root();


function australiaBackupVerifyNormal(
    string $value
): string {

    return strtolower(
        preg_replace(
            '/[^a-z0-9]+/i',
            '',
            $value
        )
    );
}


function australiaBackupVerifyRegionFromFile(
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


if (!is_dir($root)) {

    http_response_code(404);

    echo json_encode([
        'ok' => false,
        'error' => 'DreamGrid Autobackup folder was not found'
    ]);

    exit;
}


$target =
    australiaBackupVerifyNormal(
        $region
    );


$latestPath =
    null;

$latestTime =
    0;


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


        $fileRegion =
            australiaBackupVerifyRegionFromFile(
                basename($path)
            );


        if (
            australiaBackupVerifyNormal(
                $fileRegion
            )
            !==
            $target
        ) {
            continue;
        }


        $mtime =
            (int)@filemtime(
                $path
            );


        if (
            $latestPath === null
            ||
            $mtime > $latestTime
        ) {

            $latestPath =
                $path;

            $latestTime =
                $mtime;
        }
    }
}


if ($latestPath === null) {

    http_response_code(404);

    echo json_encode([
        'ok' => false,
        'error' =>
            'No OAR backup was found for '
            . $region
    ]);

    exit;
}


$bytes =
    (int)@filesize(
        $latestPath
    );


if ($bytes <= 0) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => 'Newest OAR is empty or unreadable'
    ]);

    exit;
}


$started =
    microtime(true);


$sha256 =
    @hash_file(
        'sha256',
        $latestPath
    );


if (
    !is_string($sha256)
    ||
    strlen($sha256) !== 64
) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => 'SHA-256 verification failed'
    ]);

    exit;
}


echo json_encode(
    [
        'ok' =>
            true,

        'region' =>
            $region,

        'file' =>
            basename(
                $latestPath
            ),

        'bytes' =>
            $bytes,

        'mtime' =>
            $latestTime,

        'date' =>
            $latestTime > 0
            ?
            date(
                'c',
                $latestTime
            )
            :
            null,

        'sha256' =>
            $sha256,

        'durationSeconds' =>
            round(
                microtime(true) -
                $started,
                3
            ),

        'verifiedAt' =>
            date('c'),
    ],
    JSON_UNESCAPED_SLASHES
);