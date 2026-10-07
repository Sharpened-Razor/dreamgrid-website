<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/oar-user-tools.php';

ag_no_cache();

$session =
    ag_require_admin();

$avatar =
    ag_avatar_name(
        $session
    );


function adminAllRegionOarFail(
    $message,
    $status = 400
) {
    http_response_code(
        (int)$status
    );

    header(
        'Content-Type: text/plain; charset=utf-8'
    );

    header(
        'Cache-Control: private, no-store, no-cache, must-revalidate'
    );

    echo (string)$message;

    exit;
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


if ($region === '') {

    adminAllRegionOarFail(
        'Region name is required.',
        400
    );
}


if ($slot < 1 || $slot > 2) {

    adminAllRegionOarFail(
        'Invalid OAR backup slot.',
        400
    );
}


/*
 * Resolve the requested region and its real EstateOwner from
 * DreamGrid. The browser never supplies the backup owner.
 */

$regionListUrl =
    rtrim(
        ag_dg_diagnostics_base(),
        '/'
    ) .
    '/?command=regionlist&page=1&rp=500&sortorder=asc';


$context =
    stream_context_create(
        array(
            'http' =>
                array(
                    'method' =>
                        'GET',

                    'timeout' =>
                        8,

                    'ignore_errors' =>
                        true
                )
        )
    );


$json =
    @file_get_contents(
        $regionListUrl,
        false,
        $context
    );


if ($json === false) {

    adminAllRegionOarFail(
        'DreamGrid region list is unavailable.',
        502
    );
}


$data =
    json_decode(
        (string)$json,
        true
    );


if (!is_array($data)) {

    adminAllRegionOarFail(
        'DreamGrid returned an invalid region list.',
        502
    );
}


$rows =
    isset($data['rows']) &&
    is_array($data['rows'])
        ? $data['rows']
        : array();


$found =
    null;


foreach ($rows as $row) {

    $cell =
        isset($row['cell']) &&
        is_array($row['cell'])
            ? $row['cell']
            : array();


    $candidate =
        trim(
            (string)(
                $cell['RegionName'] ??
                ''
            )
        );


    if (
        $candidate !== '' &&
        strcasecmp(
            $candidate,
            $region
        ) === 0
    ) {

        $found =
            $cell;

        break;
    }
}


if (!$found) {

    adminAllRegionOarFail(
        'Region was not found.',
        404
    );
}


$backupOwner =
    trim(
        (string)(
            $found['EstateOwner'] ??
            ''
        )
    );


if ($backupOwner === '') {

    $backupOwner =
        $avatar;
}


try {

    $path =
        australiaUserOarSlotPath(
            $backupOwner,
            $region,
            $slot
        );

}
catch (Throwable $error) {

    adminAllRegionOarFail(
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

    adminAllRegionOarFail(
        'This OAR backup slot is empty.',
        404
    );
}


$realRoot =
    realpath(
        australiaUserOarBackupRoot()
    );


if ($realRoot === false) {

    adminAllRegionOarFail(
        'OAR backup storage is unavailable.',
        500
    );
}


$fileNormal =
    strtolower(
        str_replace(
            '\\',
            '/',
            $realFile
        )
    );


$rootNormal =
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


if (
    strpos(
        $fileNormal,
        $rootNormal
    ) !== 0
) {

    adminAllRegionOarFail(
        'Invalid OAR backup path.',
        403
    );
}


while (ob_get_level() > 0) {

    ob_end_clean();
}


$downloadName =
    basename(
        $realFile
    );


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
    (string)filesize($realFile)
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Cache-Control: private, no-store, no-cache, must-revalidate'
);


readfile(
    $realFile
);

exit;