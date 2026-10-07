<?php
declare(strict_types=1);

/*
 * DREAMGRID REGION MANAGER EMAIL V1
 *
 * Admin-only bridge to DreamGrid's existing SMTP settings.
 * No SMTP password is ever returned to the browser.
 */

require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

ag_require_same_origin_post();


function dg_email_fail(
    string $message,
    int $status = 400
): never
{
    http_response_code($status);

    echo json_encode([
        'ok' =>
            false,

        'error' =>
            $message,
    ]);

    exit;
}


function dg_email_setting(
    string $name,
    $default = ''
)
{
    try {

        $value =
            ag_dg_setting(
                $name
            );

        if (
            $value === null ||
            $value === ''
        ) {
            return $default;
        }

        return $value;
    }
    catch (Throwable $exception) {

        return $default;
    }
}


function dg_email_bool(
    $value,
    bool $default = false
): bool
{
    if (is_bool($value)) {
        return $value;
    }


    if (is_int($value)) {
        return $value !== 0;
    }


    $text =
        strtolower(
            trim(
                (string)$value
            )
        );


    if (
        in_array(
            $text,
            [
                '1',
                'true',
                'yes',
                'on',
                'enabled'
            ],
            true
        )
    ) {
        return true;
    }


    if (
        in_array(
            $text,
            [
                '0',
                'false',
                'no',
                'off',
                'disabled'
            ],
            true
        )
    ) {
        return false;
    }


    return $default;
}


function dg_email_config(): array
{
    /*
     * Exact DreamGrid Start.dll setting.
     */
    $username =
        trim(
            (string)dg_email_setting(
                'SmtPropUserName',
                'LoginName@somewhere.net'
            )
        );


    $host =
        trim(
            (string)dg_email_setting(
                'SmtpHost',
                'smtp.somewhere.net'
            )
        );


    $password =
        (string)dg_email_setting(
            'SmtpPassword',
            'Password'
        );


    $port =
        (int)dg_email_setting(
            'SmtpPort',
            587
        );


    if (
        $port < 1 ||
        $port > 65535
    ) {
        $port = 587;
    }


    /*
     * DreamGrid Start.dll security selector:
     *
     * 0 = None
     * 1 = Automatic
     * 2 = SSL On Connect
     * 3 = Start TLS
     * 4 = Start TLS When Available
     */
    $sslType =
        (int)dg_email_setting(
            'SSLType',
            3
        );


    if (
        $sslType < 0 ||
        $sslType > 4
    ) {
        $sslType = 3;
    }


    $secure = '';

    switch ($sslType) {

        case 0:
            $secure = 'None';
            break;

        case 1:
            $secure = 'Automatic';
            break;

        case 2:
            $secure = 'SslOnConnect';
            break;

        case 3:
            $secure = 'StartTls';
            break;

        case 4:
            $secure = 'StartTlsWhenAvailable';
            break;

        default:
            $secure = 'StartTls';
            break;
    }


    $verifyCertificate =
        dg_email_bool(
            dg_email_setting(
                'VerifyCertCheckBox',
                true
            ),
            true
        );


    $enabled =
        dg_email_bool(
            dg_email_setting(
                'EmailEnabled',
                false
            ),
            false
        );


    $usernameLower =
        strtolower(
            $username
        );

    $hostLower =
        strtolower(
            $host
        );


    /*
     * Same setup test used by DreamGrid FormEmail.Init.
     */
    $configured =
        (
            $username !== '' &&
            $usernameLower !== 'loginname@somewhere.net'
        );


    return [
        'username' =>
            $username,

        'password' =>
            $password,

        'host' =>
            $host,

        'port' =>
            $port,

        'secure' =>
            $secure,

        'verifyCertificate' =>
            $verifyCertificate,

        'enabled' =>
            $enabled,

        'configured' =>
            $configured,
    ];
}


$session =
    ag_current_session();


if (
    !$session ||
    !ag_is_admin(
        $session
    )
) {
    dg_email_fail(
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


$config =
    dg_email_config();


if ($action === 'status') {

    echo json_encode([
        'ok' =>
            true,

        'configured' =>
            $config['configured'],

        'enabled' =>
            $config['enabled'],

        'host' =>
            $config['configured']
                ? $config['host']
                : '',

        'port' =>
            $config['configured']
                ? $config['port']
                : null,
    ]);

    exit;
}


if ($action !== 'send') {

    dg_email_fail(
        'Unsupported email action.'
    );
}


if (!$config['configured']) {

    dg_email_fail(
        'Email Server not yet set up.',
        409
    );
}


if (!$config['enabled']) {

    dg_email_fail(
        'Email to SMTP is not enabled.',
        409
    );
}


$subject =
    trim(
        (string)(
            $_POST['subject'] ??
            ''
        )
    );


$message =
    trim(
        (string)(
            $_POST['message'] ??
            ''
        )
    );


if (
    $subject === '' ||
    mb_strlen(
        $subject
    ) > 200
) {
    dg_email_fail(
        'Email subject must be between 1 and 200 characters.'
    );
}


if (
    $message === '' ||
    mb_strlen(
        $message
    ) > 10000
) {
    dg_email_fail(
        'Email message must be between 1 and 10000 characters.'
    );
}


$recipientJson =
    (string)(
        $_POST['recipients'] ??
        ''
    );


$recipients =
    json_decode(
        $recipientJson,
        true
    );


if (!is_array($recipients)) {

    dg_email_fail(
        'Invalid recipient list.'
    );
}


if (
    count($recipients) < 1 ||
    count($recipients) > 100
) {
    dg_email_fail(
        'Select between 1 and 100 email users.'
    );
}


$cleanRecipients = [];


foreach ($recipients as $recipient) {

    if (!is_array($recipient)) {
        continue;
    }


    $name =
        trim(
            (string)(
                $recipient['name'] ??
                ''
            )
        );


    $email =
        trim(
            (string)(
                $recipient['email'] ??
                ''
            )
        );


    if (
        $email === '' ||
        filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) === false
    ) {
        continue;
    }


    $cleanRecipients[] = [
        'name' =>
            mb_substr(
                $name,
                0,
                120
            ),

        'email' =>
            $email,
    ];
}


if (!$cleanRecipients) {

    dg_email_fail(
        'No selected users have a valid email address.'
    );
}


/*
 * Worker lives outside the web root.
 */
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
    'DreamGrid.EmailSender.ps1';


$startDll =
    $dreamGridRoot .
    DIRECTORY_SEPARATOR .
    'Start.dll';


if (
    !is_file($worker) ||
    !is_file($startDll)
) {
    dg_email_fail(
        'DreamGrid email worker is unavailable.',
        500
    );
}


$gridName =
    function_exists(
        'ag_grid_name'
    )
        ? trim(
            (string)ag_grid_name()
        )
        : 'DreamGrid';


if ($gridName === '') {
    $gridName = 'DreamGrid';
}


$payload =
    json_encode(
        [
            'username' =>
                $config['username'],

            'password' =>
                $config['password'],

            'host' =>
                $config['host'],

            'port' =>
                $config['port'],

            'secure' =>
                $config['secure'],

            'verifyCertificate' =>
                $config['verifyCertificate'],

            'fromName' =>
                $gridName,

            'subject' =>
                $subject,

            'message' =>
                $message,

            'recipients' =>
                $cleanRecipients,
        ],
        JSON_UNESCAPED_SLASHES
    );


if ($payload === false) {

    dg_email_fail(
        'Could not encode email request.',
        500
    );
}


$command =
    'powershell.exe ' .
    '-NoProfile ' .
    '-NonInteractive ' .
    '-ExecutionPolicy Bypass ' .
    '-File ' .
    escapeshellarg(
        $worker
    ) .
    ' -StartDll ' .
    escapeshellarg(
        $startDll
    );


$descriptors = [
    0 =>
        [
            'pipe',
            'r'
        ],

    1 =>
        [
            'pipe',
            'w'
        ],

    2 =>
        [
            'pipe',
            'w'
        ],
];


$process =
    @proc_open(
        $command,
        $descriptors,
        $pipes,
        $dreamGridRoot
    );


if (!is_resource($process)) {

    dg_email_fail(
        'Could not start the DreamGrid email worker.',
        500
    );
}


fwrite(
    $pipes[0],
    $payload
);

fclose(
    $pipes[0]
);


$stdout =
    stream_get_contents(
        $pipes[1]
    );

fclose(
    $pipes[1]
);


$stderr =
    stream_get_contents(
        $pipes[2]
    );

fclose(
    $pipes[2]
);


$exitCode =
    proc_close(
        $process
    );


$result =
    json_decode(
        trim(
            (string)$stdout
        ),
        true
    );


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
        $error = 'DreamGrid SMTP send failed.';
    }


    dg_email_fail(
        $error,
        502
    );
}


echo json_encode([
    'ok' =>
        true,

    'sent' =>
        (int)(
            $result['sent'] ??
            count(
                $cleanRecipients
            )
        ),
]);