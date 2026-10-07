<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/login/session.php';

$session =
    dreamGridCurrentSession();

if (!$session) {

    http_response_code(401);

    exit(
        'Not logged in'
    );
}


$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );


$job =
    trim(
        (string)(
            $_GET['job'] ??
            ''
        )
    );


if (
    $principalId === '' ||
    !preg_match(
        '/^\d{8}_\d{6}_[a-f0-9]{8}$/',
        $job
    )
) {

    http_response_code(400);

    exit(
        'Invalid Safety IAR reference'
    );
}


$metaFile =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs' .
    DIRECTORY_SEPARATOR .
    'safety_iar_meta_' .
    $job .
    '.json';


$meta =
    json_decode(
        (string)@file_get_contents(
            $metaFile
        ),
        true
    );


if (!is_array($meta)) {

    http_response_code(404);

    exit(
        'Safety IAR record not found'
    );
}


if (
    !hash_equals(
        (string)(
            $meta['principalId'] ??
            ''
        ),
        $principalId
    )
) {

    http_response_code(403);

    exit(
        'Permission denied'
    );
}


if (
    (string)(
        $meta['status'] ??
        ''
    ) !==
    'verified'
) {

    http_response_code(409);

    exit(
        'Safety IAR has not been verified'
    );
}


$root =
    ag_dg_autobackup_root() .
    DIRECTORY_SEPARATOR .
    'Safety-IAR';


$rootReal =
    realpath(
        $root
    );


$path =
    (string)(
        $meta['path'] ??
        ''
    );


$real =
    realpath(
        $path
    );


if (
    $rootReal === false ||
    $real === false
) {

    http_response_code(404);

    exit(
        'Safety IAR file not found'
    );
}


$prefix =
    rtrim(
        strtolower(
            str_replace(
                '\\',
                '/',
                $rootReal
            )
        ),
        '/'
    ) .
    '/';


$candidate =
    strtolower(
        str_replace(
            '\\',
            '/',
            $real
        )
    );


if (
    strpos(
        $candidate,
        $prefix
    ) !== 0
) {

    http_response_code(403);

    exit(
        'Invalid Safety IAR path'
    );
}


if (
    !is_file($real) ||
    filesize($real) <= 0 ||
    strtolower(
        pathinfo(
            $real,
            PATHINFO_EXTENSION
        )
    ) !==
    'iar'
) {

    http_response_code(404);

    exit(
        'Verified Safety IAR file is unavailable'
    );
}


$fh =
    @fopen(
        $real,
        'rb'
    );


if (!$fh) {

    http_response_code(500);

    exit(
        'Safety IAR could not be opened'
    );
}


$magic =
    fread(
        $fh,
        2
    );


if (
    !is_string($magic) ||
    strlen($magic) !== 2 ||
    ord($magic[0]) !== 0x1f ||
    ord($magic[1]) !== 0x8b
) {

    fclose($fh);

    http_response_code(409);

    exit(
        'Safety IAR validation failed'
    );
}


rewind($fh);


$size =
    filesize(
        $real
    );


header(
    'Content-Type: application/octet-stream'
);

header(
    'Content-Disposition: attachment; filename="' .
    str_replace(
        '"',
        '',
        basename($real)
    ) .
    '"'
);

header(
    'Content-Length: ' .
    $size
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


while (!feof($fh)) {

    echo fread(
        $fh,
        1024 * 1024
    );

    flush();
}


fclose($fh);

exit;