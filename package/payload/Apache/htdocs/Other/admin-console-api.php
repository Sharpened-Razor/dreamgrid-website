<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';
ag_require_same_origin_post();

ag_no_cache();

header(
    'Content-Type: application/json; charset=utf-8'
);

$session =
    ag_require_admin();


function consoleFail(
    string $message,
    int $status = 400
): void {

    http_response_code(
        $status
    );

    echo json_encode(
        [
            'ok' =>
                false,

            'error' =>
                $message
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !==
    'POST'
) {

    consoleFail(
        'POST required.',
        405
    );
}


foreach (['csrf','region','command'] as $field) { if(isset($_POST[$field])&&!is_string($_POST[$field])) consoleFail('Invalid console request.'); }

$cookie =
    (string)(
        $_COOKIE['dg_session'] ??
        ''
    );

$expectedCsrf =
    hash_hmac(
        'sha256',
        $cookie,
        'AUSTRALIA_ADMIN_CONSOLE_V1'
    );

$csrf =
    (string)(
        $_POST['csrf'] ??
        ''
    );

if (
    $csrf === '' ||
    !hash_equals(
        $expectedCsrf,
        $csrf
    )
) {

    consoleFail(
        'Security token failed.',
        403
    );
}


$region =
    trim(
        (string)(
            $_POST['region'] ??
            ''
        )
    );

$command =
    trim(
        (string)(
            $_POST['command'] ??
            ''
        )
    );


if (
    $region === '' ||
    !preg_match(
        '/^[A-Za-z0-9 _.\-]{1,100}$/',
        $region
    )
) {

    consoleFail(
        'Invalid region process.'
    );
}


if ($command === '') {

    consoleFail(
        'Console command is blank.'
    );
}


if (strlen($command) > 1200) {

    consoleFail(
        'Console command is too long.'
    );
}


if (
    preg_match(
        '/[\x00-\x1F\x7F]/',
        $command
    )
) {

    consoleFail(
        'Console command contains invalid control characters.'
    );
}


$regionsRoot = ag_dg_regions_root();

$rootReal =
    realpath(
        $regionsRoot
    );

if ($rootReal === false) {

    consoleFail(
        'OpenSim Regions folder was not found.',
        500
    );
}


$ini =
    $regionsRoot .
    DIRECTORY_SEPARATOR .
    $region .
    DIRECTORY_SEPARATOR .
    'Opensim.ini';


$iniReal =
    realpath(
        $ini
    );

if (
    $iniReal === false ||
    stripos(
        $iniReal,
        $rootReal .
        DIRECTORY_SEPARATOR
    ) !== 0
) {

    consoleFail(
        'Region Opensim.ini was not found.',
        404
    );
}


$text =
    @file_get_contents(
        $iniReal
    );

if ($text === false) {

    consoleFail(
        'Could not read region configuration.',
        500
    );
}


/*
 * Extract only the [RemoteAdmin] section.
 */

if (
    !preg_match(
        '/(?ms)^\s*\[RemoteAdmin\]\s*\R(.*?)(?=^\s*\[[^\]]+\]\s*$|\z)/',
        $text,
        $sectionMatch
    )
) {

    consoleFail(
        'RemoteAdmin section was not found for this region.',
        500
    );
}


$remote =
    (string)$sectionMatch[1];


if (
    !preg_match(
        '/^\s*access_password\s*=\s*"?([^"\r\n;]+)"?\s*(?:;.*)?$/mi',
        $remote,
        $passwordMatch
    )
) {

    consoleFail(
        'RemoteAdmin access password was not found.',
        500
    );
}


$password =
    trim(
        (string)$passwordMatch[1]
    );

if ($password === '') {

    consoleFail(
        'RemoteAdmin access password is blank.',
        500
    );
}


$port = 0;


if (
    preg_match(
        '/^\s*port\s*=\s*(\d+)\s*(?:;.*)?$/mi',
        $remote,
        $portMatch
    )
) {

    $port =
        (int)$portMatch[1];
}


/*
 * If RemoteAdmin uses port 0, fall back to the simulator's
 * HTTP listener port where available.
 */

if ($port <= 0) {

    if (
        preg_match(
            '/^\s*http_listener_port\s*=\s*(\d+)\s*(?:;.*)?$/mi',
            $text,
            $listenerMatch
        )
    ) {

        $port =
            (int)$listenerMatch[1];
    }
}


if ($port < 1 || $port > 65535) {
    consoleFail(
        'RemoteAdmin port was not found.',
        500
    );
}


function xmlEscapeValue(
    string $value
): string {

    return htmlspecialchars(
        $value,
        ENT_XML1 |
        ENT_QUOTES,
        'UTF-8'
    );
}


$xml =
    '<?xml version="1.0"?>' .
    '<methodCall>' .
    '<methodName>admin_console_command</methodName>' .
    '<params>' .
    '<param>' .
    '<value>' .
    '<struct>' .

    '<member>' .
    '<name>password</name>' .
    '<value><string>' .
    xmlEscapeValue(
        $password
    ) .
    '</string></value>' .
    '</member>' .

    '<member>' .
    '<name>command</name>' .
    '<value><string>' .
    xmlEscapeValue(
        $command
    ) .
    '</string></value>' .
    '</member>' .

    '</struct>' .
    '</value>' .
    '</param>' .
    '</params>' .
    '</methodCall>';


$url =
    ag_web_local_base($port) .
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
                        45,

                    'follow_location' => 0,
                    'ignore_errors' => true
                ]
        ]
    );


$response =
    @file_get_contents(
        $url,
        false,
        $context, 0, 262145
    );


if(is_string($response)&&strlen($response)>262144) consoleFail('Console response exceeds the display limit.',502);
$status = 0;

if (isset($http_response_header)) {

    foreach ($http_response_header as $headerLine) {

        if (
            preg_match(
                '/^HTTP\/\S+\s+(\d{3})/',
                $headerLine,
                $statusMatch
            )
        ) {

            $status =
                (int)$statusMatch[1];

            break;
        }
    }
}


$avatar =
    ag_avatar_name(
        $session
    );


$logDir =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs';

if (!is_dir($logDir)) {

    @mkdir(
        $logDir,
        0775,
        true
    );
}


$logLine =
    date('c') .
    ' | ' .
    $avatar .
    ' | ' .
    $region .
    ' | ' .
    $command .
    PHP_EOL;


@file_put_contents(
    $logDir .
    DIRECTORY_SEPARATOR .
    'admin-console.log',
    $logLine,
    FILE_APPEND |
    LOCK_EX
);


if ($response === false) {

    consoleFail(
        'OpenSim RemoteAdmin did not respond on localhost port ' .
        $port .
        '.',
        502
    );
}


$trimmed =
    trim(
        (string)$response
    );


if (
    stripos(
        $trimmed,
        '<fault>'
    ) !== false
) {

    consoleFail(
        'OpenSim returned a RemoteAdmin fault.',
        502
    );
}


if (
    $status < 200 || $status >= 300 || stripos($trimmed, '<methodResponse') === false
) {

    consoleFail(
        'OpenSim returned HTTP ' .
        $status .
        '.',
        502
    );
}


/*
 * RemoteAdmin admin_console_command normally confirms
 * execution rather than providing live console scrollback.
 */

echo json_encode(
    [
        'ok' =>
            true,

        'message' =>
            'OpenSim accepted the command for ' .
            $region .
            '.',

        'region' =>
            $region,

        'httpStatus' =>
            $status,

        'response' =>
            $trimmed
    ],
    JSON_UNESCAPED_SLASHES
);
