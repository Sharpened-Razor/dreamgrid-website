<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_admin();
ag_no_cache();

$xRaw =
    trim(
        (string)(
            $_GET['x'] ??
            ''
        )
    );

$yRaw =
    trim(
        (string)(
            $_GET['y'] ??
            ''
        )
    );

if (
    !preg_match(
        '/^-?\d+$/',
        $xRaw
    ) ||
    !preg_match(
        '/^-?\d+$/',
        $yRaw
    )
) {

    http_response_code(400);
    exit;
}

$x =
    (int)$xRaw;

$y =
    (int)$yRaw;

$url =
    ag_web_local_base(ag_dg_robust_port()) . '/' .
    'map-1-' .
    $x .
    '-' .
    $y .
    '-objects.jpg';

$context =
    stream_context_create(
        [
            'http' => [
                'method' =>
                    'GET',

                'timeout' =>
                    4,

                'ignore_errors' =>
                    true
            ]
        ]
    );

$data =
    @file_get_contents(
        $url,
        false,
        $context
    );

if (
    $data === false ||
    $data === ''
) {

    http_response_code(404);
    exit;
}

$image =
    @getimagesizefromstring(
        $data
    );

if (!is_array($image)) {

    http_response_code(404);
    exit;
}

$mime =
    (string)(
        $image['mime'] ??
        'image/jpeg'
    );

header(
    'Content-Type: ' .
    $mime
);

header(
    'Content-Length: ' .
    strlen($data)
);

echo $data;
