<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

ag_require_same_origin_post();


function dg_smtp_fail(
    string $message,
    int $status = 400
): never
{
    http_response_code($status);

    echo json_encode([
        'ok' => false,
        'error' => $message,
    ]);

    exit;
}


function dg_smtp_bool(
    $value
): bool
{
    return in_array(
        strtolower(trim((string)$value)),
        ['1', 'true', 'yes', 'on'],
        true
    );
}


function dg_smtp_int(
    string $name,
    int $default,
    int $minimum,
    int $maximum
): int
{
    $raw =
        trim(
            (string)(
                $_POST[$name] ??
                ''
            )
        );


    if ($raw === '') {
        return $default;
    }


    if (
        !preg_match(
            '/^\d+$/',
            $raw
        )
    ) {
        dg_smtp_fail(
            'Invalid numeric SMTP setting: ' .
            $name
        );
    }


    $value =
        (int)$raw;


    if (
        $value < $minimum ||
        $value > $maximum
    ) {
        dg_smtp_fail(
            'SMTP setting out of range: ' .
            $name
        );
    }


    return $value;
}


function dg_smtp_worker(
    array $request
): array
{
    $outworldzRoot =
        dirname(
            __DIR__,
            3
        );


    $dreamGridRoot =
        dirname(
            $outworldzRoot
        );


    $worker =
        $outworldzRoot .
        DIRECTORY_SEPARATOR .
        '_WEB_CONTROL' .
        DIRECTORY_SEPARATOR .
        'DreamGrid.EmailSettings.ps1';


    $startDll =
        $dreamGridRoot .
        DIRECTORY_SEPARATOR .
        'Start.dll';


    $pwsh =
        ag_dg_pwsh_exe();


    if (
        !is_file($worker) ||
        !is_file($startDll) ||
        !is_file($pwsh)
    ) {
        dg_smtp_fail(
            'DreamGrid SMTP settings worker is unavailable.',
            500
        );
    }


    $json =
        json_encode(
            $request,
            JSON_UNESCAPED_SLASHES
        );


    if ($json === false) {
        dg_smtp_fail(
            'Could not encode SMTP settings request.',
            500
        );
    }


    $command =
        escapeshellarg($pwsh) .
        ' -NoLogo' .
        ' -NoProfile' .
        ' -NonInteractive' .
        ' -ExecutionPolicy Bypass' .
        ' -File ' .
        escapeshellarg($worker) .
        ' -StartDll ' .
        escapeshellarg($startDll) .
        ' -DreamGridRoot ' .
        escapeshellarg($dreamGridRoot);


    $process =
        @proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $dreamGridRoot
        );


    if (!is_resource($process)) {
        dg_smtp_fail(
            'Could not start DreamGrid SMTP settings worker.',
            500
        );
    }


    fwrite(
        $pipes[0],
        $json
    );

    fclose($pipes[0]);


    $stdout =
        stream_get_contents(
            $pipes[1]
        );

    fclose($pipes[1]);


    $stderr =
        stream_get_contents(
            $pipes[2]
        );

    fclose($pipes[2]);


    $exitCode =
        proc_close(
            $process
        );


    $result =
        null;


    $lines =
        preg_split(
            '/\R/',
            trim(
                (string)$stdout
            )
        );


    if (is_array($lines)) {

        for (
            $i = count($lines) - 1;
            $i >= 0;
            $i--
        ) {

            $candidate =
                json_decode(
                    trim(
                        (string)$lines[$i]
                    ),
                    true
                );


            if (is_array($candidate)) {

                $result =
                    $candidate;

                break;
            }
        }
    }


    if (
        $exitCode !== 0 ||
        !is_array($result) ||
        empty($result['ok'])
    ) {

        $error =
            is_array($result) &&
            !empty($result['error'])
                ? (string)$result['error']
                : trim(
                    (string)$stderr
                );


        if ($error === '') {
            $error =
                'DreamGrid SMTP settings worker failed.';
        }


        dg_smtp_fail(
            $error,
            500
        );
    }


    return $result;
}


$session =
    ag_current_session();


if (
    !$session ||
    !ag_is_admin(
        $session
    )
) {
    dg_smtp_fail(
        'Grid Owner permission required.',
        403
    );
}


$action =
    trim(
        (string)(
            $_POST['action'] ??
            ''
        )
    );


if ($action === 'get') {

    $result =
        dg_smtp_worker([
            'action' =>
                'get',
        ]);


    $settings =
        is_array(
            $result['settings'] ??
            null
        )
            ? $result['settings']
            : [];


    $username =
        trim(
            (string)(
                $settings['SmtPropUserName'] ??
                'LoginName@somewhere.net'
            )
        );


    echo json_encode([
        'ok' =>
            true,

        'settings' =>
            [
                'emailEnabled' =>
                    (bool)(
                        $settings['EmailEnabled'] ??
                        false
                    ),

                'username' =>
                    $username,

                'passwordSet' =>
                    !empty(
                        $result['passwordSet']
                    ),

                'host' =>
                    (string)(
                        $settings['SmtpHost'] ??
                        'smtp.somewhere.net'
                    ),

                'port' =>
                    (int)(
                        $settings['SmtpPort'] ??
                        587
                    ),

                'sslType' =>
                    (int)(
                        $settings['SslType'] ??
                        3
                    ),

                'verifyCertificate' =>
                    (bool)(
                        $settings['VerifyCertCheckBox'] ??
                        true
                    ),

                'emailToObjectsEnabled' =>
                    (bool)(
                        $settings['EnableEmailToExternalObjects'] ??
                        false
                    ),

                'emailFromObjectsEnabled' =>
                    (bool)(
                        $settings['OutboundEnabled'] ??
                        false
                    ),

                'mailsFromOwnerPerHour' =>
                    (int)(
                        $settings['MailsFromOwnerPerHour'] ??
                        500
                    ),

                'mailsToPrimAddressPerHour' =>
                    (int)(
                        $settings['MailsToPrimAddressPerHour'] ??
                        20
                    ),

                'mailsPerDay' =>
                    (int)(
                        $settings['MailsPerDay'] ??
                        100
                    ),

                'mailsToSmtpAddressPerHour' =>
                    (int)(
                        $settings['EmailsToSmtpAddressPerHour'] ??
                        10
                    ),

                'emailPauseTime' =>
                    (int)(
                        $settings['EmailPauseTime'] ??
                        20
                    ),

                'maxMailSize' =>
                    (int)(
                        $settings['MaxMailSize'] ??
                        4096
                    ),

                'configured' =>
                    (
                        $username !== '' &&
                        strcasecmp(
                            $username,
                            'LoginName@somewhere.net'
                        ) !== 0
                    ),
            ],
    ]);

    exit;
}


if ($action !== 'save') {
    dg_smtp_fail(
        'Unsupported SMTP settings action.'
    );
}


$username =
    trim(
        (string)(
            $_POST['username'] ??
            ''
        )
    );


$host =
    trim(
        (string)(
            $_POST['host'] ??
            ''
        )
    );


$password =
    (string)(
        $_POST['password'] ??
        ''
    );


if (
    $username === '' ||
    mb_strlen($username) > 254
) {
    dg_smtp_fail(
        'Enter the SMTP user name.'
    );
}


if (
    $host === '' ||
    mb_strlen($host) > 255
) {
    dg_smtp_fail(
        'Enter the SMTP host.'
    );
}


$port =
    dg_smtp_int(
        'port',
        587,
        1,
        65535
    );


$sslType =
    dg_smtp_int(
        'sslType',
        3,
        0,
        4
    );


$settings = [
    'EmailEnabled' =>
        dg_smtp_bool(
            $_POST['emailEnabled'] ??
            false
        )
            ? 'True'
            : 'False',

    'SmtPropUserName' =>
        $username,

    'SmtpHost' =>
        $host,

    'SmtpPort' =>
        (string)$port,

    'SmtpSecure' =>
        $sslType === 0
            ? 'False'
            : 'True',

    'SSLType' =>
        (string)$sslType,

    'VerifyCertCheckBox' =>
        dg_smtp_bool(
            $_POST['verifyCertificate'] ??
            false
        )
            ? 'True'
            : 'False',

    'enableEmailToExternalObjects' =>
        dg_smtp_bool(
            $_POST['emailToObjectsEnabled'] ??
            false
        )
            ? 'True'
            : 'False',

    'OutboundEnabled' =>
        dg_smtp_bool(
            $_POST['emailFromObjectsEnabled'] ??
            false
        )
            ? 'True'
            : 'False',

    'MailsFromOwnerPerHour' =>
        (string)dg_smtp_int(
            'mailsFromOwnerPerHour',
            500,
            0,
            1000000
        ),

    'MailsToPrimAddressPerHour' =>
        (string)dg_smtp_int(
            'mailsToPrimAddressPerHour',
            20,
            0,
            1000000
        ),

    'MailsPerDay' =>
        (string)dg_smtp_int(
            'mailsPerDay',
            100,
            0,
            1000000
        ),

    'EmailsToSMTPAddressPerHour' =>
        (string)dg_smtp_int(
            'mailsToSmtpAddressPerHour',
            10,
            0,
            1000000
        ),

    'EmailPauseTime' =>
        (string)dg_smtp_int(
            'emailPauseTime',
            20,
            0,
            86400
        ),

    'MaxMailSize' =>
        (string)dg_smtp_int(
            'maxMailSize',
            4096,
            1,
            100000000
        ),
];


if ($password !== '') {

    $settings['SmtpPassword'] =
        $password;
}


dg_smtp_worker([
    'action' =>
        'save',

    'settings' =>
        $settings,
]);


echo json_encode([
    'ok' =>
        true,
]);