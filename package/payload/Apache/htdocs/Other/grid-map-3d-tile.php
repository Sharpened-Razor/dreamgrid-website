<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

$session = ag_require_login();

$x =
    filter_input(
        INPUT_GET,
        'x',
        FILTER_VALIDATE_INT
    );

$y =
    filter_input(
        INPUT_GET,
        'y',
        FILTER_VALIDATE_INT
    );

if (
    $x === false ||
    $x === null ||
    $y === false ||
    $y === null ||
    $x < 0 ||
    $y < 0 ||
    $x > 65535 ||
    $y > 65535
) {

    http_response_code(400);

    header(
        'Content-Type: text/plain; charset=UTF-8'
    );

    echo 'Invalid map tile coordinates.';

    exit;
}

$tileUrl =
    ag_web_local_base(ag_dg_robust_port()) . '/map-1-' .
    $x .
    '-' .
    $y .
    '-objects.jpg';

$context =
    stream_context_create(
        [
            'http' => [
                'method'        => 'GET',
                'timeout'       => 5,
                'ignore_errors' => true,
                'header'        =>
                    "Connection: close\r\n" .
                    "User-Agent: Australia-3D-Map/1.0\r\n"
            ]
        ]
    );

$image =
    @file_get_contents(
        $tileUrl,
        false,
        $context
    );

$statusCode =
    0;

if (
    isset($http_response_header) &&
    is_array($http_response_header) &&
    isset($http_response_header[0]) &&
    preg_match(
        '/\s(\d{3})\s/',
        (string)$http_response_header[0],
        $match
    )
) {

    $statusCode =
        (int)$match[1];
}

if (
    $image === false ||
    $image === '' ||
    (
        $statusCode !== 0 &&
        $statusCode !== 200
    )
) {

    http_response_code(404);

    header(
        'Content-Type: text/plain; charset=UTF-8'
    );

    echo 'OpenSim map tile not available.';

    exit;
}

header(
    'Content-Type: image/jpeg'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

header(
    'Content-Length: ' .
    strlen($image)
);

echo $image;