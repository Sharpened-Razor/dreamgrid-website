<?php

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();
ag_require_admin();

if (
    function_exists(
        'ag_require_same_origin_post'
    )
) {
    ag_require_same_origin_post();
}

header(
    'Content-Type: application/json; charset=UTF-8'
);

function dgm_rotate_reply(
    bool $ok,
    string $message,
    int $status = 200
): never
{
    http_response_code(
        $status
    );

    echo json_encode(
        $ok
            ? [
                'ok' =>
                    true,

                'message' =>
                    $message
            ]
            : [
                'ok' =>
                    false,

                'error' =>
                    $message
            ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if (
    (
        $_SERVER['REQUEST_METHOD'] ??
        ''
    ) !==
    'POST'
) {

    dgm_rotate_reply(
        false,
        'POST required.',
        405
    );
}

$region =
    trim(
        (string)(
            $_POST['region'] ??
            ''
        )
    );

$angleRaw =
    trim(
        (string)(
            $_POST['angle'] ??
            ''
        )
    );

if ($region === '') {

    dgm_rotate_reply(
        false,
        'Region is required.',
        400
    );
}

if (
    !preg_match(
        '/^-?\d+$/',
        $angleRaw
    )
) {

    dgm_rotate_reply(
        false,
        'Invalid rotation angle.',
        400
    );
}

$angle =
    (int)$angleRaw;

if (
    !in_array(
        $angle,
        [
            -90,
            90,
            180
        ],
        true
    )
) {

    dgm_rotate_reply(
        false,
        'Invalid rotation angle.',
        400
    );
}

$regionsRoot =
    ag_dg_regions_root();

if (
    $regionsRoot === null ||
    !is_dir(
        $regionsRoot
    )
) {

    dgm_rotate_reply(
        false,
        'DreamGrid Regions folder was not found.',
        500
    );
}

$pattern =
    rtrim(
        $regionsRoot,
        '/\\'
    ) .
    DIRECTORY_SEPARATOR .
    '*' .
    DIRECTORY_SEPARATOR .
    'Region' .
    DIRECTORY_SEPARATOR .
    '*.ini';

$groupPort =
    0;

foreach (
    glob(
        $pattern
    ) ?: []
    as $file
) {

    if (
        !is_file(
            $file
        )
    ) {
        continue;
    }

    $parsed =
        @parse_ini_file(
            $file,
            true,
            INI_SCANNER_RAW
        );

    if (
        !is_array(
            $parsed
        )
    ) {
        continue;
    }

    foreach (
        $parsed
        as $sectionName =>
        $section
    ) {

        if (
            !is_array(
                $section
            )
        ) {
            continue;
        }

        if (
            strcasecmp(
                trim(
                    (string)$sectionName
                ),
                $region
            ) !== 0
        ) {
            continue;
        }

        $groupPort =
            (int)(
                $section['GroupPort'] ??
                0
            );

        break 2;
    }
}

if (
    $groupPort < 1 ||
    $groupPort > 65535
) {

    dgm_rotate_reply(
        false,
        'DreamGrid GroupPort was not found for ' .
        $region .
        '.',
        404
    );
}

$machineHash =
    trim(
        (string)(
            ag_dg_setting(
                'MachineHash'
            ) ??
            ''
        )
    );

if ($machineHash === '') {

    dgm_rotate_reply(
        false,
        'DreamGrid MachineHash is unavailable.',
        500
    );
}

$command =
    'rotate scene ' .
    $angle;

$xmlEscape =
    static function(
        string $value
    ): string {

        return htmlspecialchars(
            $value,
            ENT_XML1 |
            ENT_QUOTES,
            'UTF-8'
        );
    };

$xml =
    '<?xml version="1.0"?>' .
    '<methodCall>' .
    '<methodName>admin_console_command</methodName>' .
    '<params><param><value><struct>' .

    '<member>' .
    '<name>password</name>' .
    '<value><string>' .
    $xmlEscape(
        $machineHash
    ) .
    '</string></value>' .
    '</member>' .

    '<member>' .
    '<name>command</name>' .
    '<value><string>' .
    $xmlEscape(
        $command
    ) .
    '</string></value>' .
    '</member>' .

    '</struct></value></param></params>' .
    '</methodCall>';

$url =
    ag_web_local_base($groupPort) .
    '/';

$context =
    stream_context_create(
        [
            'http' =>
                [
                    'method' =>
                        'POST',

                    'header' =>
                        "Content-Type: text/xml\r\n" .
                        "Connection: close\r\n",

                    'content' =>
                        $xml,

                    'timeout' =>
                        5,

                    'ignore_errors' =>
                        true
                ]
        ]
    );

$response =
    @file_get_contents(
        $url,
        false,
        $context
    );

if ($response === false) {

    dgm_rotate_reply(
        false,
        'DreamGrid region RPC did not respond on GroupPort ' .
        $groupPort .
        '.',
        502
    );
}

$text =
    trim(
        (string)$response
    );

if (
    stripos(
        $text,
        '<fault>'
    ) !== false
) {

    dgm_rotate_reply(
        false,
        'DreamGrid returned an RPC fault.',
        502
    );
}

if (
    preg_match(
        '~<name>\s*success\s*</name>\s*' .
        '<value>\s*<boolean>\s*0\s*</boolean>~is',
        $text
    )
) {

    dgm_rotate_reply(
        false,
        'DreamGrid rejected the rotate scene command.',
        502
    );
}

dgm_rotate_reply(
    true,
    'rotate scene ' .
    $angle .
    ' sent to ' .
    $region .
    '.'
);