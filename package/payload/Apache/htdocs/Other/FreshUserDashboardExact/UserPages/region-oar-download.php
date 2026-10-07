<?php

declare(strict_types=1);

/*
 * USER REGION OAR DOWNLOAD V2
 *
 * Security:
 * - Signed-in user only.
 * - Avatar identity comes only from signed session.
 * - Requested region must be owned by that avatar in
 *   DreamGrid's live region list.
 * - Only the resident's 3-slot UserOARBackups are exposed.
 *
 * Portable:
 * - No fixed DreamGrid installation path.
 * - Uses existing DreamGrid/user OAR helpers.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/core/dreamgrid-env.php';
require_once dirname(__DIR__, 2) . '/oar-user-tools.php';


function userOarFail(
    string $message,
    int $status = 400
): never {

    http_response_code($status);

    header(
        'Content-Type: text/plain; charset=utf-8'
    );

    header(
        'Cache-Control: private, no-store, no-cache, must-revalidate'
    );

    echo $message;

    exit;
}


$session =
    ag_current_session();


if (!$session) {

    userOarFail(
        'Not logged in.',
        401
    );
}


$avatar =
    trim(
        (string)(
            $session['avatar'] ??
            ''
        )
    );


if ($avatar === '') {

    userOarFail(
        'Signed-in avatar is unavailable.',
        401
    );
}


$region =
    trim(
        (string)(
            $_GET['region'] ??
            ''
        )
    );


$slot =
    (int)(
        $_GET['slot'] ??
        0
    );


if (
    $region === '' ||
    strlen($region) > 255
) {

    userOarFail(
        'Valid region name required.'
    );
}


if (
    $slot < 1 ||
    $slot > 3
) {

    userOarFail(
        'Invalid OAR backup slot.'
    );
}


/*
 * ==========================================================
 * VERIFY REGION OWNERSHIP THROUGH DREAMGRID
 * ==========================================================
 */

$regionListUrl =
    rtrim(
        ag_dg_diagnostics_base(),
        '/'
    ) .
    '/?command=regionlist' .
    '&page=1' .
    '&rp=500' .
    '&sortorder=asc';


$context =
    stream_context_create(
        [
            'http' =>
                [
                    'method' =>
                        'GET',

                    'timeout' =>
                        15,

                    'ignore_errors' =>
                        true
                ]
        ]
    );


$raw =
    @file_get_contents(
        $regionListUrl,
        false,
        $context
    );


if ($raw === false) {

    userOarFail(
        'DreamGrid region list is unavailable.',
        502
    );
}


$data =
    json_decode(
        $raw,
        true
    );


if (!is_array($data)) {

    userOarFail(
        'DreamGrid returned an invalid region list.',
        502
    );
}


$rows =
    $data['rows'] ??
    [];


if (!is_array($rows)) {

    $rows = [];
}


$owned =
    false;


foreach ($rows as $row) {

    if (!is_array($row)) {
        continue;
    }


    $regionName =
        trim(
            (string)(
                $row['RegionName'] ??
                $row['regionName'] ??
                ''
            )
        );


    $estateOwner =
        trim(
            (string)(
                $row['EstateOwner'] ??
                $row['EstateOwnerName'] ??
                $row['estateOwner'] ??
                ''
            )
        );


    if (
        strcasecmp(
            $regionName,
            $region
        ) === 0
        &&
        strcasecmp(
            $estateOwner,
            $avatar
        ) === 0
    ) {

        $owned =
            true;

        break;
    }
}


if (!$owned) {

    userOarFail(
        'You do not own this region.',
        403
    );
}


/*
 * ==========================================================
 * RESOLVE THE USER'S ROLLING OAR SLOT
 * ==========================================================
 */

try {

    $path =
        australiaUserOarSlotPath(
            $avatar,
            $region,
            $slot
        );

}
catch (Throwable $error) {

    userOarFail(
        'OAR backup could not be resolved.',
        400
    );
}


$realFile =
    realpath(
        $path
    );


if (
    $realFile === false ||
    !is_file($realFile)
) {

    userOarFail(
        'This OAR backup slot is empty.',
        404
    );
}


/*
 * ==========================================================
 * VERIFY FILE STAYS INSIDE UserOARBackups
 * ==========================================================
 */

$backupRoot =
    australiaUserOarBackupRoot();


$realRoot =
    realpath(
        $backupRoot
    );


if ($realRoot === false) {

    userOarFail(
        'User OAR backup storage is unavailable.',
        500
    );
}


$normalRoot =
    rtrim(
        strtolower(
            str_replace(
                '\\',
                '/',
                $realRoot
            )
        ),
        '/'
    ) .
    '/';


$normalFile =
    strtolower(
        str_replace(
            '\\',
            '/',
            $realFile
        )
    );


if (
    strpos(
        $normalFile,
        $normalRoot
    ) !== 0
) {

    userOarFail(
        'Invalid OAR backup path.',
        403
    );
}


if (
    strtolower(
        pathinfo(
            $realFile,
            PATHINFO_EXTENSION
        )
    ) !== 'oar'
) {

    userOarFail(
        'Invalid backup file.',
        403
    );
}


/*
 * ==========================================================
 * DOWNLOAD
 * ==========================================================
 */

$safeRegion =
    australiaUserOarSafeComponent(
        $region
    );


if ($safeRegion === '') {

    $safeRegion =
        'Region';
}


$downloadName =
    $safeRegion .
    '-Backup-' .
    $slot .
    '.oar';


clearstatcache(
    true,
    $realFile
);


$size =
    filesize(
        $realFile
    );


if ($size === false) {

    userOarFail(
        'Could not read OAR backup.',
        500
    );
}


while (ob_get_level() > 0) {
    @ob_end_clean();
}


header(
    'Content-Type: application/octet-stream'
);

header(
    'Content-Disposition: attachment; filename="' .
    str_replace(
        '"',
        '',
        $downloadName
    ) .
    '"'
);

header(
    'Content-Length: ' .
    (string)$size
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Cache-Control: private, no-store, no-cache, must-revalidate'
);

header(
    'Pragma: no-cache'
);


readfile(
    $realFile
);

exit;