<?php
/*
 * AUSTRALIA ADMIN 2D MAP UUID CUSTOM IMAGE ENDPOINT V1
 *
 * ADMIN 2D MAP ONLY.
 * Does not participate in the 3D Map.
 */

require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/core/region-map-textures.php';

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


$session =
    dreamGridCurrentSession();


if (
    !$session ||
    (int)($session['level'] ?? 0) < 200
) {

    http_response_code(403);
    exit;
}


$regionName =
    trim(
        (string)(
            $_GET['region'] ??
            ''
        )
    );


if ($regionName === '') {

    http_response_code(400);
    exit;
}


$uuid =
    '';


foreach (
    ag_rmt_regions()
    as $region
) {

    $name =
        trim(
            (string)(
                $region['name'] ??
                ''
            )
        );

    if (
        $name !== '' &&
        strcasecmp(
            $name,
            $regionName
        ) === 0
    ) {

        $uuid =
            strtolower(
                trim(
                    (string)(
                        $region['uuid'] ??
                        ''
                    )
                )
            );

        break;
    }
}


if (
    $uuid === '' ||
    !ag_rmt_valid_uuid($uuid)
) {

    http_response_code(404);
    exit;
}


$custom =
    ag_rmt_custom_file(
        $uuid
    );


if (
    !$custom ||
    !is_file($custom)
) {

    /*
     * Intentional 404.
     *
     * Existing Admin 2D Map image error handler then leaves
     * the native OpenSim composite visible.
     */
    http_response_code(404);
    exit;
}


$image =
    @getimagesize(
        $custom
    );


if (!is_array($image)) {

    http_response_code(404);
    exit;
}


$mime =
    strtolower(
        (string)(
            $image['mime'] ??
            ''
        )
    );


$allowed =
    array(
        'image/jpeg',
        'image/png',
        'image/webp'
    );


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


$size =
    @filesize(
        $custom
    );


header(
    'Content-Type: ' .
    $mime
);


if ($size !== false) {

    header(
        'Content-Length: ' .
        (string)$size
    );
}


readfile(
    $custom
);