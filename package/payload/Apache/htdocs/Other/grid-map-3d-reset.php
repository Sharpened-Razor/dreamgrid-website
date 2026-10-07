<?php

declare(strict_types=1);

require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 * ============================================================
 * AUSTRALIA SAFE REGION RESET API V6C-H21-R2
 * ============================================================
 */

/*
 * ============================================================
 * RESET AUTH DATABASE BOOTSTRAP V6C-H24
 *
 * core/auth.php calls ag_db_connect().
 * Load the file that defines ag_db_connect() FIRST.
 * ============================================================
 */

require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/auth.php';

ag_require_admin();
require_once __DIR__ . '/core/request-security.php';
ag_require_same_origin_post();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$raw =
    file_get_contents(
        'php://input'
    );

$data =
    json_decode(
        is_string($raw)
            ? $raw
            : '',
        true
    );

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'error' => 'Invalid request.'
    ]);

    exit;
}

$region =
    trim(
        (string)(
            $data['region'] ??
            ''
        )
    );

$mode =
    strtolower(
        trim(
            (string)(
                $data['mode'] ??
                ''
            )
        )
    );

if (
    $region === '' ||
    strlen($region) > 128 ||
    preg_match(
        '/[\/\\\\\x00-\x1F]/',
        $region
    )
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'error' => 'Invalid region.'
    ]);

    exit;
}

if (
    $mode !== 'texture' &&
    $mode !== 'mesh'
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'error' => 'Invalid reset mode.'
    ]);

    exit;
}

$safeRegion =
    preg_replace(
        '/[^A-Za-z0-9]+/',
        '_',
        $region
    );

$safeRegion =
    trim(
        (string)$safeRegion,
        '_'
    );

if ($safeRegion === '') {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'error' => 'Invalid cache key.'
    ]);

    exit;
}

/*
 * Existing region alias.
 */

if (
    strcasecmp(
        $region,
        'Official Region'
    ) === 0
) {

    $safeRegion =
        'Offical_Region';
}

$cacheRoot =
    (string)ag_dg_path('LiveObject3DCache');

$regionCache =
    $cacheRoot .
    DIRECTORY_SEPARATOR .
    $safeRegion;

/*
 * ============================================================
 * WINDOWS CACHE LOCK RETRY V6C-H23
 *
 * OpenSim / Apache may briefly have prims.json or scene.xml
 * open while the region cache is being read or rewritten.
 *
 * Windows can reject unlink() while that handle is active.
 *
 * Retry first. If direct deletion still fails, try moving the
 * old snapshot away from the live filename so a fresh snapshot
 * can be generated.
 * ============================================================
 */

function ag_h23_remove_snapshot_file(
    string $file,
    string $name
): array {

    clearstatcache(
        true,
        $file
    );

    if (!is_file($file)) {

        return [
            'ok' =>
                true,

            'deleted' =>
                false,

            'renamed' =>
                false,

            'attempts' =>
                0
        ];
    }

    $attempts =
        24;

    $lastError =
        '';

    for (
        $attempt = 1;
        $attempt <= $attempts;
        $attempt++
    ) {

        clearstatcache(
            true,
            $file
        );

        /*
         * Another process may have completed the removal
         * between attempts.
         */

        if (!is_file($file)) {

            return [
                'ok' =>
                    true,

                'deleted' =>
                    true,

                'renamed' =>
                    false,

                'attempts' =>
                    $attempt
            ];
        }

        /*
         * Clear a possible read-only flag before retrying.
         */

        @chmod(
            $file,
            0666
        );

        if (@unlink($file)) {

            clearstatcache(
                true,
                $file
            );

            return [
                'ok' =>
                    true,

                'deleted' =>
                    true,

                'renamed' =>
                    false,

                'attempts' =>
                    $attempt
            ];
        }

        $error =
            error_get_last();

        if (
            is_array($error) &&
            isset($error['message'])
        ) {

            $lastError =
                (string)$error['message'];
        }

        /*
         * After a few ordinary retries, attempt to move the
         * stale file away from the active cache filename.
         *
         * If this succeeds the live endpoint can immediately
         * create a new prims.json / scene.xml.
         */

        if ($attempt >= 6) {

            $stale =
                $file .
                '.reset-stale-' .
                date('Ymd-His') .
                '-' .
                $attempt .
                '.bak';

            if (@rename(
                $file,
                $stale
            )) {

                /*
                 * Removing the renamed copy is optional.
                 * If Windows still holds it open, leave the
                 * .bak safely behind.
                 */

                @unlink(
                    $stale
                );

                clearstatcache(
                    true,
                    $file
                );

                return [
                    'ok' =>
                        true,

                    'deleted' =>
                        false,

                    'renamed' =>
                        true,

                    'attempts' =>
                        $attempt
                ];
            }
        }

        /*
         * 150ms x 24 = maximum roughly 3.6 seconds.
         */

        usleep(
            150000
        );
    }

    clearstatcache(
        true,
        $file
    );

    /*
     * One final check in case the handle was released on the
     * final sleep.
     */

    if (!is_file($file)) {

        return [
            'ok' =>
                true,

            'deleted' =>
                true,

            'renamed' =>
                false,

            'attempts' =>
                $attempts
        ];
    }

    return [
        'ok' =>
            false,

        'deleted' =>
            false,

        'renamed' =>
            false,

        'attempts' =>
            $attempts,

        'error' =>
            (
                $lastError !== ''
                    ? $lastError
                    : 'Windows still has the cache file locked.'
            )
    ];
}


$deleted =
    [];

$renamed =
    [];

foreach (
    [
        'prims.json',
        'scene.xml'
    ]
    as
    $name
) {

    $file =
        $regionCache .
        DIRECTORY_SEPARATOR .
        $name;

    $result =
        ag_h23_remove_snapshot_file(
            $file,
            $name
        );

    if (
        !isset($result['ok']) ||
        $result['ok'] !== true
    ) {

        http_response_code(423);

        echo json_encode(
            [
                'ok' =>
                    false,

                'error' =>
                    'Region cache file is still locked: ' .
                    $name,

                'file' =>
                    $name,

                'attempts' =>
                    (
                        $result['attempts'] ??
                        0
                    ),

                'detail' =>
                    (
                        $result['error'] ??
                        ''
                    )
            ],
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    if (
        !empty(
            $result['deleted']
        )
    ) {

        $deleted[] =
            $name;
    }

    if (
        !empty(
            $result['renamed']
        )
    ) {

        $renamed[] =
            $name;
    }
}

echo json_encode(
    [
        'ok' =>
            true,

        'marker' =>
            'AUSTRALIA SAFE REGION RESET API V6C-H21-R2',

        'region' =>
            $region,

        'mode' =>
            $mode,

        'deleted' =>
            $deleted,

        'renamedStaleSnapshots' =>
            $renamed,

        'meshGeometryCacheTouched' =>
            false,

        'textureCacheTouched' =>
            false,

        'terrainCacheTouched' =>
            false
    ],
    JSON_UNESCAPED_SLASHES
);