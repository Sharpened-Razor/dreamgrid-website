<?php
declare(strict_types=1);

require_once __DIR__ . '/core/site-design-assets.php';


$key =
    trim(
        (string)
        ($_GET['slot'] ?? '')
    );


$path =
    ag_site_design_active_path(
        $key
    );


if (
    $path === null ||
    !is_file($path)
) {

    http_response_code(404);
    exit;
}


$image =
    @getimagesize(
        $path
    );


if (!is_array($image)) {

    http_response_code(404);
    exit;
}


$mime =
    strtolower(
        (string)
        ($image['mime'] ?? '')
    );


$allowed = [
    'image/jpeg',
    'image/png',
    'image/webp',
];


if (
    !in_array(
        $mime,
        $allowed,
        true
    )
) {

    http_response_code(415);
    exit;
}


$mtime =
    (int)
    (@filemtime($path) ?: time());


$size =
    (int)
    (@filesize($path) ?: 0);


$etag =
    '"' .
    sha1(
        $path .
        '|' .
        (string)$mtime .
        '|' .
        (string)$size
    ) .
    '"';


header(
    'Content-Type: ' .
    $mime
);

header(
    'Cache-Control: no-cache, must-revalidate'
);

header(
    'ETag: ' .
    $etag
);

header(
    'Last-Modified: ' .
    gmdate(
        'D, d M Y H:i:s',
        $mtime
    ) .
    ' GMT'
);


if (
    isset(
        $_SERVER['HTTP_IF_NONE_MATCH']
    ) &&
    trim(
        (string)
        $_SERVER['HTTP_IF_NONE_MATCH']
    )
    ===
    $etag
) {

    http_response_code(304);
    exit;
}


readfile($path);

exit;
