<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/oar-user-tools.php';
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

ag_require_same_origin_post();

function dgAudit($message) {
    $line = date('Y-m-d H:i:s') . ' | ' . $message . PHP_EOL;
    @file_put_contents(__DIR__ . '/command-debug.log', $line, FILE_APPEND | LOCK_EX);
}

function fail($message, $code = 400) {
    if (function_exists('dgAudit')) {
        dgAudit('FAIL code=' . $code . ' message=' . $message);
    }
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function getMachineHash() {

    $hash =
        ag_dg_setting(
            'MachineHash'
        );

    if ($hash === null) {
        fail(
            'MachineHash was not found in the grid settings.',
            500
        );
    }

    $hash =
        trim(
            $hash
        );

    if ($hash === '') {
        fail(
            'MachineHash is blank.',
            500
        );
    }

    return $hash;
}

function dreamGridRequest($params) {
    $base = rtrim(ag_dg_diagnostics_base(), '/') . '/API/';
    $url = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        fail('The grid service did not respond.', 502);
    }

    return trim($response);
}


function dreamGridSaveOarRequest($params) {

    $base =
        rtrim(
            ag_dg_diagnostics_base(),
            '/'
        ) .
        '/API/';

    $url =
        $base .
        '?' .
        http_build_query(
            $params,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

    $ctx =
        stream_context_create(
            [
                'http' => [
                    'method' =>
                        'GET',

                    'timeout' =>
                        20,

                    'ignore_errors' =>
                        true,
                ],
            ]
        );

    $response =
        @file_get_contents(
            $url,
            false,
            $ctx
        );

    /*
     * IMPORTANT:
     *
     * SaveOAR can take much longer than the diagnostics HTTP
     * request remains open.
     *
     * A timeout here does NOT mean OAR creation failed.
     * DreamGrid often continues creating the OAR afterwards.
     *
     * Therefore we return blank and allow the existing
     * filesystem watcher to wait for the real completed OAR.
     */
    if ($response === false) {

        if (
            function_exists(
                'dgAudit'
            )
        ) {

            dgAudit(
                'SaveOAR diagnostics request timed out; continuing to wait for completed OAR file.'
            );
        }

        return '';
    }

    return
        trim(
            (string)$response
        );
}

function dreamGridRootRequest($params) {
    $base = rtrim(ag_dg_diagnostics_base(), '/') . '/';
    $url = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        fail('The grid service root endpoint did not respond.', 502);
    }

    return trim($response);
}


function getWelcomeRemoteAdmin() {
    $ini = ag_dg_regions_root() . DIRECTORY_SEPARATOR . 'Welcome' . DIRECTORY_SEPARATOR . 'Opensim.ini';

    if (!is_file($ini)) {
        fail('Welcome Opensim.ini was not found.', 500);
    }

    $text = @file_get_contents($ini);
    if ($text === false) {
        fail('Welcome Opensim.ini could not be read.', 500);
    }

    if (!preg_match('/^\s*access_password\s*=\s*(\S+)\s*$/mi', $text, $pm)) {
        fail('Welcome RemoteAdmin access_password was not found.', 500);
    }

    $password = trim($pm[1]);
    if ($password === '') {
        fail('Welcome RemoteAdmin access_password is blank.', 500);
    }

    $port = 0;
    if (preg_match('/^\s*port\s*=\s*(\d+)\s*$/mi', $text, $portm)) {
        $port = (int)$portm[1];
    }
    if ($port <= 0) {
        fail('Welcome RemoteAdmin port was not found.', 500);
    }

    return ['password' => $password, 'port' => $port];
}

function xmlEscape($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function launchRemoteAdminIar($command, $iarPath) {
    $cfg = getWelcomeRemoteAdmin();

    $jobDir = __DIR__ . DIRECTORY_SEPARATOR . 'jobs';
    if (!is_dir($jobDir) && !@mkdir($jobDir, 0775, true)) {
        fail('Could not create IAR job folder.', 500);
    }

    $jobId = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    $psFile = $jobDir . DIRECTORY_SEPARATOR . 'iar_' . $jobId . '.ps1';
    $logFile = $jobDir . DIRECTORY_SEPARATOR . 'iar_' . $jobId . '.log';

    // Keep the RemoteAdmin password entirely server-side.
    $ps = <<<'POWERSHELL'
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
    $xml = '<?xml version="1.0"?>' +
        '<methodCall><methodName>admin_console_command</methodName><params><param><value><struct>' +
        '<member><name>password</name><value><string>' + (XmlEscape $RemotePass) + '</string></value></member>' +
        '<member><name>command</name><value><string>' + (XmlEscape $ConsoleCommand) + '</string></value></member>' +
        '</struct></value></param></params></methodCall>'

    "START $(Get-Date -Format o)" | Set-Content -LiteralPath $LogFile -Encoding UTF8
    "COMMAND $ConsoleCommand" | Add-Content -LiteralPath $LogFile -Encoding UTF8

    # OpenSim keeps this request open until save iar completes.
    # Use a generous timeout and run outside the Apache/PHP request.
    $response = Invoke-WebRequest `
        -UseBasicParsing `
        -Uri ("http://{1}:{0}/" -f $RemotePort, $RemoteHost) `
        -Method POST `
        -ContentType "text/xml" `
        -Body $xml `
        -TimeoutSec 14400

    "HTTP $($response.StatusCode)" | Add-Content -LiteralPath $LogFile -Encoding UTF8
    $response.Content | Add-Content -LiteralPath $LogFile -Encoding UTF8
    "END $(Get-Date -Format o)" | Add-Content -LiteralPath $LogFile -Encoding UTF8
}
catch {
    $m = $_.Exception.Message
    if($m -match 'connection.*closed|closed.*unexpected|underlying connection'){
        "CONNECTION_CLOSED $(Get-Date -Format o) $m" | Add-Content -LiteralPath $LogFile -Encoding UTF8
        exit 0
    }
    "ERROR $(Get-Date -Format o) $m" | Add-Content -LiteralPath $LogFile -Encoding UTF8
    exit 1
}
finally {
    Remove-Variable RemotePass -ErrorAction SilentlyContinue
}
POWERSHELL;

    if (@file_put_contents($psFile, $ps) === false) {
        fail('Could not create the background IAR job.', 500);
    }

    $powerShell = 'powershell.exe';
    $args = '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' .
            escapeshellarg($psFile) . ' ' .
            '-RemotePass ' . escapeshellarg($cfg['password']) . ' ' .
            '-RemotePort ' . (int)$cfg['port'] . ' ' .
            '-ConsoleCommand ' . escapeshellarg($command) . ' ' .
            '-LogFile ' . escapeshellarg($logFile);

    // Start detached so Apache can return immediately while OpenSim spends
    // as long as necessary building the IAR.
    $cmdLine = 'start "" /B ' . $powerShell . ' ' . $args .
               ' >NUL 2>&1';

    @pclose(@popen($cmdLine, 'r'));

    // Give the worker a moment to launch. We deliberately do not wait for
    // RemoteAdmin to finish.
    usleep(500000);

    return [
        'jobId' => $jobId,
        'log' => basename($logFile),
        'path' => $iarPath,
    ];
}


function validateLocalAvatar($avatar) {

    $parts = preg_split('/\s+/', trim($avatar), 2);
    $first = $parts[0] ?? '';
    $last = $parts[1] ?? '';

    if ($first === '' || $last === '') {
        fail('A local First Last avatar name is required.', 400);
    }

    $con = ag_db_connect();

    if (!$con) {
        fail('Could not connect to Robust database.', 500);
    }

    $stmt = mysqli_prepare(
        $con,
        'SELECT UserLevel FROM UserAccounts WHERE FirstName = ? AND LastName = ?'
    );

    if (!$stmt) {
        mysqli_close($con);
        fail('Could not validate local avatar.', 500);
    }

    mysqli_stmt_bind_param($stmt, 'ss', $first, $last);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userLevel);
    $found = mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    if (!$found || (int)$userLevel < 0) {
        fail('Selected avatar is not an active local user.', 404);
    }

    return trim($first . ' ' . $last);
}

function makeIarJob($avatar) {
    $parts = preg_split('/\s+/', trim($avatar), 2);
    $first = $parts[0] ?? '';
    $last = $parts[1] ?? '';

    if ($first === '' || $last === '') {
        fail('A local First Last avatar name is required for IAR backup.', 400);
    }

    $root = ag_dg_autobackup_root();
    $folderName = 'AutoBackup-' . date('Y-m-d');
    $folder = $root . DIRECTORY_SEPARATOR . $folderName;

    if (!is_dir($folder) && !@mkdir($folder, 0775, true)) {
        fail('Could not create today\'s Autobackup folder.', 500);
    }

    // V40: make the archive identifiable without trusting the filename for security.
    $safeAvatar = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($avatar));
    $safeAvatar = trim($safeAvatar, '_');
    if ($safeAvatar === '') $safeAvatar = 'Avatar';

    $fileName = $safeAvatar . '_' . date('Y-m-d_H_i_s') . '.iar';
    $fullPath = $folder . DIRECTORY_SEPARATOR . $fileName;
    $consolePath = str_replace('\\', '/', $fullPath);

    // Same OpenSim command format confirmed working from DreamGrid GUI.
    $command = 'save iar --home ' . ag_dg_hostname() . ' ' .
               $first . ' ' . $last . ' "/" "' . $consolePath . '"';

    $worker = launchRemoteAdminIar($command, $consolePath);

    // V40: durable server-side ownership record used by history/download endpoints.
    // The browser never gets to decide who owns an IAR.
    $metaDir = __DIR__ . DIRECTORY_SEPARATOR . 'jobs';
    if (!is_dir($metaDir)) @mkdir($metaDir, 0775, true);

    $meta = [
        'type' => 'iar',
        'jobId' => $worker['jobId'],
        'avatar' => trim($avatar),
        'name' => $fileName,
        'folder' => $folderName,
        'path' => $fullPath,
        'created' => date('c'),
    ];
    @file_put_contents(
        $metaDir . DIRECTORY_SEPARATOR . 'iar_meta_' . $worker['jobId'] . '.json',
        json_encode($meta, JSON_UNESCAPED_SLASHES)
    );

    return [
        'name' => $fileName,
        'folder' => $folderName,
        'path' => $consolePath,
        'consoleCommand' => $command,
        'jobId' => $worker['jobId'],
    ];
}

function getRegionList() {
    $json = dreamGridRequest([
        'command' => 'regionlist',
        'page' => 1,
        'rp' => 500,
        'sortorder' => 'asc',
    ]);

    $data = json_decode($json, true);
    if (!is_array($data)) {
        fail('The grid service returned an invalid region list.', 502);
    }

    return $data['rows'] ?? [];
}

$session = ag_current_session();
if (!$session) {
    fail('Not logged in.', 401);
}

$command = trim($_POST['command'] ?? '');
$region  = trim($_POST['region'] ?? '');

dgAudit(
    'REQUEST avatar=' .
    ($session['avatar'] ?? '?') .
    ' level=' .
    ($session['level'] ?? '?') .
    ' command=' .
    $command .
    ' region=' .
    $region
);

$allowed = [
    'StartRegion',
    'StopRegion',
    'RestartRegion',
    'SaveOAR',
    'DeleteOAR',
    'SaveIAR',
    'AdminSaveIAR',
    'Backup',
    'RestartAll',
    'Freeze',
    'Thaw',
];

if (!in_array($command, $allowed, true)) {
    fail('Unsupported command.');
}

$level  = (int)$session['level'];
$avatar = trim((string)$session['avatar']);
$hash   = getMachineHash();

/*
 * Save IAR:
 * DreamGrid's API documentation allows this for avatar-level users and also
 * validates the named avatar/inventory. We still take the AvatarName only from
 * the signed session, never from the browser.
 */
if ($command === 'SaveIAR') {
    if (
        !ag_can_use_privileged_user_tools(
            $session
        )
    ) {
        fail(
            'Grid requires UserLevel 50 or higher for Save IAR.',
            403
        );
    }

    $job = makeIarJob($avatar);

    dgAudit('SaveIAR RemoteAdmin avatar=' . $avatar .
            ' file=' . $job['name']);

    echo json_encode([
        'ok' => true,
        'message' => 'IAR backup started for ' . $avatar . '.',
        'iar' => [
            'name' => $job['name'],
            'folder' => $job['folder'],
            'jobId' => $job['jobId'],
            'avatar' => $avatar,
        ],
    ]);
    exit;
}


if ($command === 'AdminSaveIAR') {
    if (
        !ag_is_admin(
            $session
        )
    ) {
        fail(
            'Grid Owner access required.',
            403
        );
    }

    $targetAvatar = validateLocalAvatar(trim((string)($_POST['avatarname'] ?? '')));
    $job = makeIarJob($targetAvatar);

    dgAudit('AdminSaveIAR requestedBy=' . $avatar .
            ' target=' . $targetAvatar .
            ' file=' . $job['name']);

    echo json_encode([
        'ok' => true,
        'message' => 'IAR backup started for ' . $targetAvatar . '.',
        'iar' => [
            'name' => $job['name'],
            'folder' => $job['folder'],
            'jobId' => $job['jobId'],
            'avatar' => $targetAvatar,
        ],
    ]);
    exit;
}


/*
 * Resolve a RegionUUID server-side from the real DreamGrid Region INI.
 * The browser never supplies or chooses the UUID.
 */
function getRegionUuidFromIni(string $regionName): string
{
    $regionsRoot =
        ag_dg_regions_root();

    if ($regionsRoot === null || !is_dir($regionsRoot)) {
        fail('Regions folder was not found.', 500);
    }

    $pattern =
        $regionsRoot .
        DIRECTORY_SEPARATOR .
        '*' .
        DIRECTORY_SEPARATOR .
        'Region' .
        DIRECTORY_SEPARATOR .
        '*.ini';

    $files = glob($pattern);

    if (!is_array($files)) {
        $files = [];
    }

    foreach ($files as $iniFile) {

        $iniText =
            @file_get_contents($iniFile);

        if ($iniText === false) {
            continue;
        }

        if (
            !preg_match(
                '/^\s*\[([^\]]+)\]\s*$/m',
                $iniText,
                $sectionMatch
            )
        ) {
            continue;
        }

        $iniRegionName =
            trim(
                (string)$sectionMatch[1]
            );

        if (
            strcasecmp(
                $iniRegionName,
                $regionName
            ) !== 0
        ) {
            continue;
        }

        if (
            !preg_match(
                '/^\s*RegionUUID\s*=\s*"?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})"?\s*$/mi',
                $iniText,
                $uuidMatch
            )
        ) {
            fail(
                'RegionUUID was not found in the Region INI for ' .
                $regionName .
                '.',
                500
            );
        }

        return strtolower(
            trim(
                (string)$uuidMatch[1]
            )
        );
    }

    fail(
        'Region INI was not found for ' .
        $regionName .
        '.',
        404
    );
}


/*
 * Website-managed Freeze / Thaw state.
 *
 * DreamGrid regionlist does not expose a reliable paused/frozen flag,
 * so successful website Freeze/Thaw commands are remembered here.
 */
function setRegionFreezeState(
    string $regionName,
    ?string $regionUuid,
    bool $frozen
): void
{
    $dir =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        'jobs';

    if (
        !is_dir($dir) &&
        !@mkdir($dir, 0775, true) &&
        !is_dir($dir)
    ) {
        fail(
            'Region freeze-state folder could not be created.',
            500
        );
    }

    $file =
        $dir .
        DIRECTORY_SEPARATOR .
        'region_freeze_state.json';

    $state = [];

    if (is_file($file)) {

        $raw =
            @file_get_contents($file);

        if (
            $raw !== false &&
            trim($raw) !== ''
        ) {

            $decoded =
                json_decode(
                    $raw,
                    true
                );

            if (is_array($decoded)) {
                $state = $decoded;
            }
        }
    }

    $key =
        strtolower(
            trim($regionName)
        );

    if ($key === '') {
        return;
    }

    if ($frozen) {

        $state[$key] = [
            'regionName' =>
                $regionName,

            'regionUuid' =>
                $regionUuid,

            'frozen' =>
                true,

            'updatedAt' =>
                date(DATE_ATOM),
        ];

    }
    else {

        unset(
            $state[$key]
        );
    }

    $json =
        json_encode(
            $state,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );

    if ($json === false) {
        fail(
            'Region freeze state could not be encoded.',
            500
        );
    }

    if (
        @file_put_contents(
            $file,
            $json,
            LOCK_EX
        ) === false
    ) {
        fail(
            'Region freeze state could not be saved.',
            500
        );
    }
}

/* Grid-wide controls: only Grid Owner / Level God style accounts. */
if (in_array($command, ['Backup', 'RestartAll'], true)) {
    if (
        !ag_is_admin(
            $session
        )
    ) {
        fail('Grid Owner permission required.', 403);
    }

    $response = dreamGridRequest([
        'command' => $command,
        'password' => $hash,
    ]);

    if (stripos($response, 'NAK') === 0) {
        fail($response, 403);
    }

    dgAudit($command . ' response=' . $response);
    echo json_encode(['ok' => true, 'message' => $command . ': ' . $response]);
    exit;
}

/* Region commands must name a real region. */
if ($region === '') {
    fail('Region required.');
}

$found = null;
foreach (getRegionList() as $row) {
    $cell = $row['cell'] ?? [];
    if (strcasecmp((string)($cell['RegionName'] ?? ''), $region) === 0) {
        $found = $cell;
        break;
    }
}

if (!$found) {
    fail('Region not found.', 404);
}

/*
 * Security boundary:
 *  - Level >= 200 administrators may administer all regions.
 *  - Everyone else must be the EstateOwner returned by DreamGrid regionlist.
 */
$isAdmin = ag_is_admin($session);
$isOwner = strcasecmp(trim((string)($found['EstateOwner'] ?? '')), $avatar) === 0;

if (!$isAdmin && !$isOwner) {
    fail('You do not own this region.', 403);
}

/*
 * USER OAR SLOT DELETE
 *
 * Region and EstateOwner ownership were already verified above.
 * Only slot 1 or 2 is accepted.
 * DreamGrid Autobackup OAR files are never deleted here.
 */
if ($command === 'DeleteOAR') {

    $slot =
        (int)(
            $_POST['slot'] ??
            0
        );

    if (
        $slot < 1 ||
        $slot > 2
    ) {

        fail(
            'Invalid OAR backup slot.',
            400
        );
    }

    $backupOwner =
        trim(
            (string)(
                $found['EstateOwner'] ??
                ''
            )
        );

    if ($backupOwner === '') {

        $backupOwner =
            $avatar;
    }

    $slotPath =
        australiaUserOarSlotPath(
            $backupOwner,
            $region,
            $slot
        );

    $slotFolder =
        dirname(
            $slotPath
        );

    if (!is_dir($slotFolder)) {

        fail(
            'This OAR backup slot is already empty.',
            404
        );
    }

    $lockPath =
        $slotFolder .
        DIRECTORY_SEPARATOR .
        '.slots.lock';

    $lock =
        @fopen(
            $lockPath,
            'c'
        );

    if (!$lock) {

        fail(
            'Unable to open OAR slot lock.',
            500
        );
    }

    $locked =
        false;

    $deleteError =
        '';

    $deleteStatus =
        500;

    try {

        if (
            !@flock(
                $lock,
                LOCK_EX
            )
        ) {

            throw new RuntimeException(
                'Unable to lock OAR backup slots.'
            );
        }

        $locked =
            true;

        clearstatcache(
            true,
            $slotPath
        );

        if (!is_file($slotPath)) {

            $deleteStatus =
                404;

            throw new RuntimeException(
                'This OAR backup slot is already empty.'
            );
        }

        if (!@unlink($slotPath)) {

            throw new RuntimeException(
                'OAR backup could not be deleted.'
            );
        }

        clearstatcache(
            true,
            $slotPath
        );

        if (is_file($slotPath)) {

            throw new RuntimeException(
                'OAR backup still exists after delete.'
            );
        }
    }
    catch (Throwable $error) {

        $deleteError =
            $error->getMessage();
    }
    finally {

        if ($locked) {

            @flock(
                $lock,
                LOCK_UN
            );
        }

        @fclose(
            $lock
        );
    }

    if ($deleteError !== '') {

        fail(
            $deleteError,
            $deleteStatus
        );
    }

    dgAudit(
        'DeleteOAR region=' .
        $region .
        ' slot=' .
        $slot .
        ' owner=' .
        $backupOwner
    );

    echo json_encode(
        array(
            'ok' =>
                true,

            'message' =>
                'Backup ' .
                $slot .
                ' deleted for ' .
                $region .
                '.',

            'slot' =>
                $slot
        )
    );

    exit;
}


/*
 * Region Freeze / Thaw
 *
 * DreamGrid requires RegionUUID for these commands.
 * Resolve it from the trusted Region INI on the server.
 */
if (in_array($command, ['Freeze', 'Thaw'], true)) {

    /*
     * Keep the existing Grid Owner restriction that Freeze/Thaw
     * previously had in this command bridge.
     */
    if (
        !ag_is_admin(
            $session
        )
    ) {
        fail('Grid Owner permission required.', 403);
    }

    $regionUuid =
        getRegionUuidFromIni(
            $region
        );

    $response =
        dreamGridRequest([
            'command' =>
                $command,

            'password' =>
                $hash,

            'RegionUUID' =>
                $regionUuid,
        ]);

    if (
        stripos(
            $response,
            'NAK'
        ) === 0
    ) {
        fail(
            $response,
            403
        );
    }

    setRegionFreezeState(
        $region,
        $regionUuid,
        $command === 'Freeze'
    );

    dgAudit(
        $command .
        ' region=' .
        $region .
        ' uuid=' .
        $regionUuid .
        ' response=' .
        $response
    );

    echo json_encode([
        'ok' =>
            true,

        'message' =>
            $command .
            ' ' .
            $region .
            ': ' .
            $response,

        'regionUuid' =>
            $regionUuid,
    ]);

    exit;
}




/*
 ============================================================
 USER 2-SLOT OAR SAVE

 DreamGrid still creates its normal Autobackup OAR first.

 Only after that new OAR exists, is stable, copied and
 SHA-256 verified do we rotate the resident's 2-slot copy.

 DreamGrid Autobackup files are NEVER deleted here.
 ============================================================
*/
if ($command === 'SaveOAR') {

    /*
     * USER DASHBOARD SAVE OAR
     *
     * DreamGrid creates its normal native Autobackup OAR.
     *
     * The browser request does not wait for the completed file.
     * The existing pending finalizer completes Backup 1 /
     * Backup 2 when the new DreamGrid OAR is available.
     */

    $saveStarted =
        microtime(true);

    $pendingOwner =
        trim(
            (string)(
                $found['EstateOwner'] ??
                ''
            )
        );

    if ($pendingOwner === '') {
        $pendingOwner = $avatar;
    }

    australiaUserOarMarkPending(
        $pendingOwner,
        $region,
        $saveStarted
    );

    $response =
        dreamGridSaveOarRequest(
            [
                'command' =>
                    $command,

                'password' =>
                    $hash,

                'RegionName' =>
                    $region,
            ]
        );

    if (
        stripos(
            $response,
            'NAK'
        ) === 0
    ) {

        australiaUserOarClearPending(
            $pendingOwner,
            $region
        );

        fail(
            $response,
            403
        );
    }

    dgAudit(
        'SaveOAR started region=' .
        $region .
        ' owner=' .
        $pendingOwner .
        ' response=' .
        $response
    );

    echo json_encode(
        [
            'ok' =>
                true,

            'pending' =>
                true,

            'message' =>
                'Saving OAR for ' .
                $region .
                '.'
        ]
    );

    exit;
}

$response = dreamGridRequest([
    'command' => $command,
    'password' => $hash,
    'RegionName' => $region,
]);

if (stripos($response, 'NAK') === 0) {
    fail($response, 403);
}

if (
    in_array(
        $command,
        [
            'StartRegion',
            'StopRegion',
            'RestartRegion'
        ],
        true
    )
) {
    setRegionFreezeState(
        $region,
        null,
        false
    );
}

dgAudit($command . ' region=' . $region . ' response=' . $response);

echo json_encode([
    'ok' => true,
    'message' => $command . ' ' . $region . ': ' . $response,
]);
