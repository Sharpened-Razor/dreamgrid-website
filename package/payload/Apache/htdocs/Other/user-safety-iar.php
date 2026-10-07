<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/login/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$session = dreamGridCurrentSession();

if (!$session) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'Not logged in.'
    ]);
    exit;
}

$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );

if ($principalId === '') {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Signed account ID is unavailable.'
    ]);
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
 * ============================================================
 * Grid
 * SAFETY INVENTORY IAR V1.1
 *
 * NO LIVE INVENTORY DELETE / MOVE / REPLACEMENT OCCURS HERE.
 * ============================================================
 */


function safetyJson(
    array $data,
    int $status = 200
): void {

    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function safetyRoot(): string {

    return
        ag_dg_autobackup_root() .
        DIRECTORY_SEPARATOR .
        'Safety-IAR';
}


function safetyJobsRoot(): string {

    return
        __DIR__ .
        DIRECTORY_SEPARATOR .
        'jobs';
}


function safetyMetaFile(
    string $job
): string {

    return
        safetyJobsRoot() .
        DIRECTORY_SEPARATOR .
        'safety_iar_meta_' .
        $job .
        '.json';
}


function safetyLogFile(
    string $job
): string {

    return
        safetyJobsRoot() .
        DIRECTORY_SEPARATOR .
        'safety_iar_' .
        $job .
        '.log';
}


function safetyWriteMeta(
    string $file,
    array $meta
): void {

    $json =
        json_encode(
            $meta,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );

    if (!is_string($json)) {
        throw new Exception(
            'Safety IAR metadata could not be encoded.'
        );
    }

    if (
        @file_put_contents(
            $file,
            $json,
            LOCK_EX
        ) === false
    ) {

        throw new Exception(
            'Safety IAR metadata could not be saved.'
        );
    }
}


function safetyValidJob(
    string $job
): bool {

    return (
        preg_match(
            '/^\d{8}_\d{6}_[a-f0-9]{8}$/',
            $job
        ) === 1
    );
}


function safetyInsideRoot(
    string $path
) {

    $rootReal =
        realpath(
            safetyRoot()
        );

    if ($rootReal === false) {
        return false;
    }

    $real =
        realpath(
            $path
        );

    if ($real === false) {
        return false;
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
        return false;
    }

    return $real;
}


function safetyGetAccount(
    string $principalId
): array {

    require __DIR__ . '/../../MetroMap/includes/config.php';

    $con =
        @mysqli_connect(
            $CONF_db_server,
            $CONF_db_user,
            $CONF_db_pass,
            $CONF_db_database,
            (int)$CONF_db_port
        );

    if (!$con) {
        throw new Exception(
            'Could not connect to the Grid account database.'
        );
    }

    @mysqli_set_charset(
        $con,
        'utf8mb4'
    );

    $stmt =
        mysqli_prepare(
            $con,
            'SELECT FirstName, LastName, UserLevel
             FROM UserAccounts
             WHERE PrincipalID = ?
             LIMIT 1'
        );

    if (!$stmt) {

        mysqli_close($con);

        throw new Exception(
            'Could not validate the signed-in avatar.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        's',
        $principalId
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result(
        $stmt,
        $firstName,
        $lastName,
        $userLevel
    );

    $found =
        mysqli_stmt_fetch(
            $stmt
        );

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    if (
        !$found ||
        (int)$userLevel < 0
    ) {

        throw new Exception(
            'The signed-in avatar is not an active local Grid account.'
        );
    }

    $firstName =
        trim(
            (string)$firstName
        );

    $lastName =
        trim(
            (string)$lastName
        );

    if (
        $firstName === '' ||
        $lastName === ''
    ) {

        throw new Exception(
            'A valid First Last avatar name is required.'
        );
    }

    return [
        'first' =>
            $firstName,

        'last' =>
            $lastName,

        'avatar' =>
            $firstName .
            ' ' .
            $lastName
    ];
}


function safetyGetRemoteAdmin(): array {

    $ini =
        ag_dg_regions_root() .
        DIRECTORY_SEPARATOR .
        'Welcome' .
        DIRECTORY_SEPARATOR .
        'Opensim.ini';

    if (!is_file($ini)) {
        throw new Exception(
            'Welcome Opensim.ini was not found.'
        );
    }

    $text =
        @file_get_contents(
            $ini
        );

    if (!is_string($text)) {
        throw new Exception(
            'Welcome Opensim.ini could not be read.'
        );
    }

    if (
        !preg_match(
            '/^\s*access_password\s*=\s*(\S+)\s*$/mi',
            $text,
            $passwordMatch
        )
    ) {

        throw new Exception(
            'Welcome RemoteAdmin access_password was not found.'
        );
    }

    $password =
        trim(
            (string)$passwordMatch[1]
        );

    if ($password === '') {
        throw new Exception(
            'Welcome RemoteAdmin access_password is blank.'
        );
    }

    $port = 0;

    if (
        preg_match(
            '/^\s*port\s*=\s*(\d+)\s*$/mi',
            $text,
            $portMatch
        )
    ) {

        $port =
            (int)$portMatch[1];
    }

    if ($port <= 0) {
        throw new Exception(
            'Welcome RemoteAdmin port was not found.'
        );
    }

    return [
        'password' =>
            $password,

        'port' =>
            $port
    ];
}


function safetyLoadOwnedMeta(
    string $job,
    string $principalId
): array {

    if (!safetyValidJob($job)) {
        throw new Exception(
            'Invalid Safety IAR job.'
        );
    }

    $meta =
        json_decode(
            (string)@file_get_contents(
                safetyMetaFile($job)
            ),
            true
        );

    if (!is_array($meta)) {
        throw new Exception(
            'Safety IAR record was not found.'
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

        throw new Exception(
            'Permission denied.'
        );
    }

    return $meta;
}


function safetyGzipHeader(
    string $path
): bool {

    $fh =
        @fopen(
            $path,
            'rb'
        );

    if (!$fh) {
        return false;
    }

    $header =
        fread(
            $fh,
            2
        );

    fclose($fh);

    return (
        is_string($header) &&
        strlen($header) === 2 &&
        ord($header[0]) === 0x1f &&
        ord($header[1]) === 0x8b
    );
}


function safetyStatus(
    array $meta
): array {

    $job =
        (string)$meta['jobId'];

    $metaFile =
        safetyMetaFile($job);

    $logFile =
        safetyLogFile($job);

    $logText =
        is_file($logFile)
        ?
        (string)@file_get_contents($logFile)
        :
        '';

    $error = '';

    if (
        preg_match(
            '/^ERROR\s+.*?\s(.+)$/mi',
            $logText,
            $errorMatch
        )
    ) {

        $error =
            trim(
                (string)$errorMatch[1]
            );
    }

    $connectionClosed =
        stripos(
            $logText,
            'CONNECTION_CLOSED '
        ) !== false;

    $workerFinished =
        (
            strpos(
                $logText,
                "\nEND "
            ) !== false
            ||
            strpos(
                $logText,
                'END '
            ) === 0
        );

    $path =
        (string)(
            $meta['path'] ??
            ''
        );

    clearstatcache(
        true,
        $path
    );

    $real =
        safetyInsideRoot(
            $path
        );

    $exists =
        (
            $real !== false &&
            is_file($real)
        );

    $size =
        $exists
        ?
        (int)filesize($real)
        :
        0;

    $mtime =
        $exists
        ?
        (int)filemtime($real)
        :
        0;

    $gzipValid =
        (
            $exists &&
            $size > 0 &&
            safetyGzipHeader($real)
        );

    $now =
        time();

    $previousSize =
        (int)(
            $meta['lastObservedSize'] ??
            -1
        );

    $previousAt =
        (int)(
            $meta['lastObservedAt'] ??
            0
        );

    $stable =
        false;

    if ($size > 0) {

        if (
            $previousSize === $size &&
            $previousAt > 0 &&
            ($now - $previousAt) >= 15 &&
            $mtime > 0 &&
            ($now - $mtime) >= 10
        ) {

            $stable =
                true;
        }

        if (
            $previousSize !== $size
        ) {

            $meta['lastObservedSize'] =
                $size;

            $meta['lastObservedAt'] =
                $now;
        }
        elseif (
            $previousAt <= 0
        ) {

            $meta['lastObservedAt'] =
                $now;
        }
    }

    $verified =
        (
            $error === '' &&
            $size > 0 &&
            $gzipValid &&
            (
                $workerFinished ||
                (
                    $connectionClosed &&
                    $stable
                )
            )
        );

    $createdTime =
        strtotime(
            (string)(
                $meta['created'] ??
                ''
            )
        );

    $timedOut =
        (
            !$verified &&
            $createdTime !== false &&
            ($now - $createdTime) > 16200
        );

    $failed =
        (
            $error !== '' ||
            $timedOut
        );

    if ($verified) {

        $meta['status'] =
            'verified';

        if (
            empty(
                $meta['verifiedAt']
            )
        ) {

            $meta['verifiedAt'] =
                date('c');
        }

        $meta['verifiedSize'] =
            $size;
    }
    elseif ($failed) {

        $meta['status'] =
            'failed';
    }
    else {

        $meta['status'] =
            'processing';
    }

    safetyWriteMeta(
        $metaFile,
        $meta
    );

    $message =
        'Safety IAR is being created...';

    if (!$exists) {

        $message =
            'Waiting for OpenSim to create the Safety IAR file...';
    }
    elseif (
        $size > 0 &&
        !$workerFinished &&
        !$connectionClosed
    ) {

        $message =
            'OpenSim is writing the Safety IAR...';
    }
    elseif (
        $connectionClosed &&
        !$verified
    ) {

        $message =
            'RemoteAdmin connection closed. Verifying that the Safety IAR finished writing...';
    }

    if ($verified) {

        $message =
            'Safety IAR VERIFIED. Your emergency inventory backup is ready.';
    }

    if ($failed) {

        $message =
            $error !== ''
            ?
            $error
            :
            'Safety IAR timed out before verification completed.';
    }

    return [
        'ok' =>
            true,

        'found' =>
            true,

        'jobId' =>
            $job,

        'name' =>
            (string)(
                $meta['name'] ??
                ''
            ),

        'created' =>
            (string)(
                $meta['created'] ??
                ''
            ),

        'verifiedAt' =>
            (string)(
                $meta['verifiedAt'] ??
                ''
            ),

        'exists' =>
            $exists,

        'size' =>
            $size,

        'gzipValid' =>
            $gzipValid,

        'stable' =>
            $stable,

        'workerFinished' =>
            $workerFinished,

        'connectionClosed' =>
            $connectionClosed,

        'verified' =>
            $verified,

        'failed' =>
            $failed,

        'message' =>
            $message,

        'downloadUrl' =>
            $verified
            ?
            (
                '/Other/user-safety-iar-download.php?job=' .
                rawurlencode($job)
            )
            :
            ''
    ];
}


function safetyLatestMeta(
    string $principalId
) {

    $files =
        glob(
            safetyJobsRoot() .
            DIRECTORY_SEPARATOR .
            'safety_iar_meta_*.json'
        )
        ?:
        [];

    usort(
        $files,
        function(
            $a,
            $b
        ) {

            return
                @filemtime($b)
                <=>
                @filemtime($a);
        }
    );

    foreach(
        $files
        as
        $file
    ) {

        $meta =
            json_decode(
                (string)@file_get_contents(
                    $file
                ),
                true
            );

        if (!is_array($meta)) {
            continue;
        }

        if (
            hash_equals(
                (string)(
                    $meta['principalId'] ??
                    ''
                ),
                $principalId
            )
        ) {

            return $meta;
        }
    }

    return false;
}


$action =
    strtolower(
        trim(
            (string)(
                $_GET['api'] ??
                ''
            )
        )
    );


try{


    # ------------------------------------------------------------
    # LATEST
    # ------------------------------------------------------------

    if ($action === 'latest') {

        $meta =
            safetyLatestMeta(
                $principalId
            );

        if ($meta === false) {

            safetyJson([
                'ok' =>
                    true,

                'found' =>
                    false
            ]);
        }

        safetyJson(
            safetyStatus($meta)
        );
    }


    # ------------------------------------------------------------
    # STATUS
    # ------------------------------------------------------------

    if ($action === 'status') {

        $job =
            trim(
                (string)(
                    $_GET['job'] ??
                    ''
                )
            );

        $meta =
            safetyLoadOwnedMeta(
                $job,
                $principalId
            );

        safetyJson(
            safetyStatus($meta)
        );
    }


    # ------------------------------------------------------------
    # START
    # ------------------------------------------------------------

    if ($action === 'start') {

        if (
            $_SERVER['REQUEST_METHOD']
            !==
            'POST'
        ) {

            safetyJson(
                [
                    'ok' =>
                        false,

                    'error' =>
                        'POST request required.'
                ],
                405
            );
        }

        $csrf =
            (string)(
                $_SERVER['HTTP_X_CSRF_TOKEN'] ??
                ''
            );

        $expectedCsrf =
            (string)(
                $_SESSION[
                    'australia_clean_inventory_csrf'
                ]
                ??
                ''
            );

        if (
            $csrf === '' ||
            $expectedCsrf === '' ||
            !hash_equals(
                $expectedCsrf,
                $csrf
            )
        ) {

            safetyJson(
                [
                    'ok' =>
                        false,

                    'error' =>
                        'Security token expired. Reload Clean Inventory and try again.'
                ],
                403
            );
        }


        /*
         * Do not launch another backup while an earlier
         * Safety IAR is still running.
         */

        $latest =
            safetyLatestMeta(
                $principalId
            );

        if (
            is_array(
                $latest
            )
        ) {

            $latestStatus =
                safetyStatus(
                    $latest
                );

            if (
                !$latestStatus['verified'] &&
                !$latestStatus['failed']
            ) {

                safetyJson(
                    array_merge(
                        $latestStatus,
                        [
                            'error' =>
                                'A Safety IAR is already being created for this avatar.'
                        ]
                    ),
                    409
                );
            }
        }


        $account =
            safetyGetAccount(
                $principalId
            );

        $remote =
            safetyGetRemoteAdmin();

        $root =
            safetyRoot();

        if (
            !is_dir($root) &&
            !@mkdir(
                $root,
                0775,
                true
            )
        ) {

            throw new Exception(
                'Could not create Safety IAR storage.'
            );
        }


        $safeAvatar =
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                $account['avatar']
            );

        $safeAvatar =
            trim(
                $safeAvatar,
                '_'
            );

        if ($safeAvatar === '') {
            $safeAvatar = 'Avatar';
        }


        $ownerFolder =
            $root .
            DIRECTORY_SEPARATOR .
            $safeAvatar .
            '_' .
            substr(
                hash(
                    'sha256',
                    $principalId
                ),
                0,
                12
            );

        if (
            !is_dir($ownerFolder) &&
            !@mkdir(
                $ownerFolder,
                0775,
                true
            )
        ) {

            throw new Exception(
                'Could not create personal Safety IAR storage.'
            );
        }


        $job =
            date(
                'Ymd_His'
            ) .
            '_' .
            bin2hex(
                random_bytes(4)
            );


        $fileName =
            'SAFETY_' .
            $safeAvatar .
            '_' .
            date(
                'Y-m-d_H_i_s'
            ) .
            '.iar';


        $fullPath =
            $ownerFolder .
            DIRECTORY_SEPARATOR .
            $fileName;


        $consolePath =
            str_replace(
                '\\',
                '/',
                $fullPath
            );


        /*
         * SAME COMMAND FORMAT USED BY THE EXISTING WORKING
         * Grid IAR ENGINE.
         */

        $command =
            'save iar --home ' . ag_dg_hostname() . ' ' .
            $account['first'] .
            ' ' .
            $account['last'] .
            ' "/" "' .
            $consolePath .
            '"';


        $jobs =
            safetyJobsRoot();

        if (
            !is_dir($jobs) &&
            !@mkdir(
                $jobs,
                0775,
                true
            )
        ) {

            throw new Exception(
                'Could not create Safety IAR job folder.'
            );
        }


        $psFile =
            $jobs .
            DIRECTORY_SEPARATOR .
            'safety_iar_' .
            $job .
            '.ps1';


        $logFile =
            safetyLogFile(
                $job
            );


        $metaFile =
            safetyMetaFile(
                $job
            );


        $worker =
<<<'POWERSHELL'
param(
    [string]$RemotePass,
    [int]$RemotePort,
    [string]$ConsoleCommand,
    [string]$LogFile
)

$ErrorActionPreference = 'Stop'
$GridDirectory = [IO.DirectoryInfo]$PSScriptRoot
while($GridDirectory -and -not (Test-Path -LiteralPath (Join-Path $GridDirectory.FullName 'Settings.ini'))){$GridDirectory=$GridDirectory.Parent}
if(-not $GridDirectory){throw 'Grid settings were not found.'}
. (Join-Path $GridDirectory.FullName 'Apache/htdocs/Other/windows/WebsiteRuntime.ps1')
$RemoteHost = (Get-DgWebsiteRuntime -StartDirectory $PSScriptRoot).host

function XmlEscape([string]$s) {
    return [System.Security.SecurityElement]::Escape($s)
}

try {

    $xml =
        '<?xml version="1.0"?>' +
        '<methodCall><methodName>admin_console_command</methodName><params><param><value><struct>' +
        '<member><name>password</name><value><string>' +
        (XmlEscape $RemotePass) +
        '</string></value></member>' +
        '<member><name>command</name><value><string>' +
        (XmlEscape $ConsoleCommand) +
        '</string></value></member>' +
        '</struct></value></param></params></methodCall>'

    "START $(Get-Date -Format o)" |
        Set-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    "COMMAND $ConsoleCommand" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    $response =
        Invoke-WebRequest `
            -UseBasicParsing `
            -Uri ("http://{1}:{0}/" -f $RemotePort, $RemoteHost) `
            -Method POST `
            -ContentType "text/xml" `
            -Body $xml `
            -TimeoutSec 14400

    "HTTP $($response.StatusCode)" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    $response.Content |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

    "END $(Get-Date -Format o)" |
        Add-Content `
            -LiteralPath $LogFile `
            -Encoding UTF8

}
catch {

    $message =
        $_.Exception.Message

    if(
        $message -match
        'connection.*closed|closed.*unexpected|underlying connection'
    ){

        "CONNECTION_CLOSED $(Get-Date -Format o) $message" |
            Add-Content `
                -LiteralPath $LogFile `
                -Encoding UTF8
    }
    else{

        "ERROR $(Get-Date -Format o) $message" |
            Add-Content `
                -LiteralPath $LogFile `
                -Encoding UTF8
    }

}
finally {

    Remove-Variable RemotePass `
        -ErrorAction SilentlyContinue
}
POWERSHELL;


        if (
            @file_put_contents(
                $psFile,
                $worker
            ) === false
        ) {

            throw new Exception(
                'Could not create Safety IAR background worker.'
            );
        }


        $meta = [

            'type' =>
                'safety-iar',

            'jobId' =>
                $job,

            'principalId' =>
                $principalId,

            'avatar' =>
                $account['avatar'],

            'name' =>
                $fileName,

            'path' =>
                $fullPath,

            'created' =>
                date('c'),

            'status' =>
                'starting',

            'lastObservedSize' =>
                -1,

            'lastObservedAt' =>
                0,

            'verifiedAt' =>
                '',

            'verifiedSize' =>
                0
        ];


        safetyWriteMeta(
            $metaFile,
            $meta
        );


        $args =
            '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' .
            escapeshellarg(
                $psFile
            ) .
            ' -RemotePass ' .
            escapeshellarg(
                $remote['password']
            ) .
            ' -RemotePort ' .
            (int)$remote['port'] .
            ' -ConsoleCommand ' .
            escapeshellarg(
                $command
            ) .
            ' -LogFile ' .
            escapeshellarg(
                $logFile
            );


        $cmdLine =
            'start "" /B powershell.exe ' .
            $args .
            ' >NUL 2>&1';


        $pipe =
            @popen(
                $cmdLine,
                'r'
            );


        if ($pipe === false) {

            $meta['status'] =
                'failed';

            safetyWriteMeta(
                $metaFile,
                $meta
            );

            throw new Exception(
                'Could not launch Safety IAR worker.'
            );
        }


        @pclose(
            $pipe
        );


        usleep(
            400000
        );


        safetyJson([
            'ok' =>
                true,

            'found' =>
                true,

            'jobId' =>
                $job,

            'name' =>
                $fileName,

            'verified' =>
                false,

            'failed' =>
                false,

            'size' =>
                0,

            'message' =>
                'Safety IAR started. OpenSim is preparing the full inventory archive.'
        ]);
    }


    safetyJson(
        [
            'ok' =>
                false,

            'error' =>
                'Unknown Safety IAR request.'
        ],
        400
    );

}
catch (Throwable $e) {

    safetyJson(
        [
            'ok' =>
                false,

            'error' =>
                $e->getMessage()
        ],
        500
    );
}
