<?php

declare(strict_types=1);

/*
 * ADMIN REGION LATEST OAR DOWNLOAD V1
 *
 * Portable DreamGrid implementation.
 * No fixed grid installation path.
 */

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();
ag_require_admin();


function ag_admin_oar_reply(
    array $data,
    int $status = 200
): never {

    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function ag_admin_oar_key(
    string $value
): string {

    $value =
        strtolower(
            trim($value)
        );



    /*
     * Existing DreamGrid typo compatibility.
     */
    if ($value === 'offical region') {
        return 'official region';
    }

    return $value;
}


if (isset($_GET['region']) && !is_string($_GET['region'])) {
    ag_admin_oar_reply(['ok' => false, 'error' => 'Valid region name required.'], 400);
}

$region =
    trim(
        (string)(
            $_GET['region'] ??
            ''
        )
    );

if (
    $region === '' ||
    strlen($region) > 255
) {

    ag_admin_oar_reply(
        [
            'ok' =>
                false,

            'error' =>
                'Valid region name required.'
        ],
        400
    );
}


$root =
    ag_dg_autobackup_root();

$rootReal =
    realpath($root);

if (
    $rootReal === false ||
    !is_dir($rootReal)
) {

    ag_admin_oar_reply(
        [
            'ok' =>
                false,

            'error' =>
                'DreamGrid Autobackup folder was not found.'
        ],
        404
    );
}


$wanted =
    ag_admin_oar_key(
        $region
    );

$latestPath =
    null;

$latestTime =
    -1;


$dayDirs =
    glob(
        $rootReal .
        DIRECTORY_SEPARATOR .
        'AutoBackup-*',
        GLOB_ONLYDIR
    ) ?: [];


foreach ($dayDirs as $dayDir) {

    $oarRoot =
        $dayDir .
        DIRECTORY_SEPARATOR .
        'OAR';

    if (!is_dir($oarRoot)) {
        continue;
    }


    try {

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $oarRoot,
                    FilesystemIterator::SKIP_DOTS
                )
            );

    }
    catch (Throwable $error) {

        continue;
    }


    foreach ($iterator as $file) {

        if (
            !$file instanceof SplFileInfo ||
            !$file->isFile()
        ) {
            continue;
        }


        $filename =
            $file->getFilename();

        if (
            !preg_match(
                '/\.oar$/i',
                $filename
            )
        ) {
            continue;
        }


        $fullPath =
            $file->getPathname();

        $relativeToOar =
            ltrim(
                substr(
                    $fullPath,
                    strlen($oarRoot)
                ),
                "\\/"
            );

        $relativeToOar =
            str_replace(
                '\\',
                '/',
                $relativeToOar
            );


        /*
         * Newer DreamGrid layout:
         *
         * OAR/Region Name/_timestamp.oar
         */
        if (
            strpos(
                $relativeToOar,
                '/'
            ) !== false
        ) {

            $parts =
                explode(
                    '/',
                    $relativeToOar
                );

            $fileRegion =
                trim(
                    (string)(
                        $parts[0] ??
                        ''
                    )
                );

        }
        else {

            /*
             * Flat DreamGrid layout:
             *
             * OAR/Region Name_YYYY-MM-DD_HH_MM_SS(1X1).oar
             */
            $fileRegion =
                preg_replace(
                    '/_\d{4}-\d{2}-\d{2}_\d{2}_\d{2}_\d{2}(?:\(\d+X\d+\))?\.oar$/i',
                    '',
                    $filename
                );

            if (!is_string($fileRegion)) {
                $fileRegion = '';
            }

            $fileRegion =
                trim(
                    $fileRegion
                );
        }


        if (
            ag_admin_oar_key(
                $fileRegion
            ) !==
            $wanted
        ) {
            continue;
        }


        $mtime =
            (int)$file->getMTime();

        if ($mtime > $latestTime) {

            $latestTime =
                $mtime;

            $latestPath =
                $fullPath;
        }
    }
}


if (
    $latestPath === null ||
    !is_file($latestPath)
) {

    ag_admin_oar_reply(
        [
            'ok' =>
                false,

            'error' =>
                'No OAR backup is available for ' .
                $region .
                '.'
        ],
        404
    );
}


$realFile =
    realpath(
        $latestPath
    );

if ($realFile === false) {

    ag_admin_oar_reply(
        [
            'ok' =>
                false,

            'error' =>
                'OAR file could not be resolved.'
        ],
        404
    );
}


$rootPrefix =
    rtrim(
        str_replace(
            '\\',
            '/',
            $rootReal
        ),
        '/'
    ) .
    '/';

$fileNormal =
    str_replace(
        '\\',
        '/',
        $realFile
    );


if (
    stripos(
        $fileNormal,
        $rootPrefix
    ) !== 0
) {

    ag_admin_oar_reply(
        [
            'ok' =>
                false,

            'error' =>
                'Invalid OAR path.'
        ],
        403
    );
}


$relative =
    substr(
        $fileNormal,
        strlen($rootPrefix)
    );


$download =
    '/Other/download-backup.php' .
    '?type=oar&file=' .
    rawurlencode(
        $relative
    );


if (
    (string)(
        $_GET['resolve'] ??
        ''
    ) === '1'
) {

    ag_admin_oar_reply(
        [
            'ok' =>
                true,

            'region' =>
                $region,

            'name' =>
                basename(
                    $realFile
                ),

            'modified' =>
                $latestTime,

            'download' =>
                $download
        ]
    );
}


header(
    'Location: ' .
    $download,
    true,
    302
);

exit;