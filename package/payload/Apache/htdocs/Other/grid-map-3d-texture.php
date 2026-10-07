<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_login();
require_once __DIR__ . '/core/map3d-preview.php';
require_once __DIR__ . '/core/map3d-sculpt.php';
if (isset($_GET['sculpt']) && (!is_string($_GET['sculpt']) || !ctype_digit($_GET['sculpt']) ||
    ((int)$_GET['sculpt'] & ~199) !== 0 || ((int)$_GET['sculpt'] & 7) < 1 || ((int)$_GET['sculpt'] & 7) > 4)) {
    http_response_code(400); exit('Invalid sculpt type.');
}
if (!is_string($_GET['uuid'] ?? '') || !is_string($_GET['size'] ?? '0') || !in_array($_GET['size'] ?? '0', ['0','256'], true)) {
    http_response_code(400); exit('Invalid texture request.');
}

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);

header(
    'X-Content-Type-Options: nosniff'
);

@set_time_limit(45);

/*
 * ============================================================
 * REAL OPENSIM LIVE TEXTURE PNG V6C-G4
 *
 * Authenticated read-only texture proxy.
 *
 * Source:
 *     localhost Robust FSAsset service on the configured PrivatePort
 *
 * Input:
 *     raw OpenSim JPEG2000 asset
 *
 * Output:
 *     browser-safe PNG
 *
 * Server cache:
 *     <GridRoot>\Texture3DCache
 *
 * No OpenSim asset is modified.
 * ============================================================
 */

$uuid =
    strtolower(
        trim(
            (string)($_GET['uuid'] ?? '')
        )
    );

function agt_fail(
    string $message,
    int $status = 500
): never {

    http_response_code(
        $status
    );

    header(
        'Content-Type: text/plain; charset=UTF-8'
    );

    echo $message;

    exit;
}

function agt_png_valid(
    string $path
): bool {

    if (
        !is_file($path) ||
        filesize($path) < 8
    ) {
        return false;
    }

    $handle =
        @fopen(
            $path,
            'rb'
        );

    if (!$handle) {
        return false;
    }

    try {

        $signature =
            fread(
                $handle,
                8
            );
    }
    finally {

        fclose(
            $handle
        );
    }

    return
        $signature ===
        "\x89PNG\x0D\x0A\x1A\x0A";
}

function agt_send_png(
    string $path
): never {

    if (isset($_GET['sculpt'])) map3d_sculpt_generate($path, strtolower((string)$_GET['uuid']), (int)$_GET['sculpt']);
    try {$path = map3d_texture_preview($path, strtolower((string)($_GET['uuid'] ?? '')), (int)($_GET['size'] ?? 0));}
    catch (RuntimeException $error) {agt_fail('Texture preview temporarily unavailable.',503);}

    if (!agt_png_valid($path)) {

        agt_fail(
            'Texture cache file is invalid.',
            500
        );
    }

    clearstatcache(
        true,
        $path
    );

    header(
        'Content-Type: image/png'
    );

    header(
        'Content-Length: ' .
        (string)filesize($path)
    );

    readfile(
        $path
    );

    exit;
}

function agt_http_status(
    array $headers
): int {

    if (
        isset($headers[0]) &&
        preg_match(
            '/\s([0-9]{3})(?:\s|$)/',
            (string)$headers[0],
            $matches
        )
    ) {

        return
            (int)$matches[1];
    }

    return 0;
}

if (
    !preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D',
        $uuid
    )
) {

    agt_fail(
        'Invalid texture asset UUID.',
        400
    );
}

if (isset($_GET['sculpt'])) {
    $sculptCached = map3d_cache_read(map3d_sculpt_cache($uuid, (int)$_GET['sculpt']), $uuid);
    if ($sculptCached !== null) map3d_sculpt_send($sculptCached, 'HIT');
}

$cacheRoot =
    (string)ag_dg_path('Texture3DCache');

$helper = __DIR__ . '/private/map3d-tools/texture/TextureDecode.exe';

if (!is_file($helper)) {

    agt_fail(
        'Texture decoder is unavailable.',
        500
    );
}

if (!is_dir($cacheRoot)) {

    if (
        !@mkdir(
            $cacheRoot,
            0775,
            true
        ) &&
        !is_dir($cacheRoot)
    ) {

        agt_fail(
            'Texture cache is unavailable.',
            500
        );
    }
}

$pngPath =
    $cacheRoot .
    DIRECTORY_SEPARATOR .
    $uuid .
    '.png';

if (agt_png_valid($pngPath)) {

    agt_send_png(
        $pngPath
    );
}

/*
 * Per-texture lock prevents duplicate JPEG2000 decoding when
 * several mesh instances request the same texture together.
 */

$lockPath =
    $cacheRoot .
    DIRECTORY_SEPARATOR .
    $uuid .
    '.lock';

$lock =
    @fopen(
        $lockPath,
        'c'
    );

if (!$lock) {

    agt_fail(
        'Texture cache lock could not be created.',
        500
    );
}

if (
    !flock(
        $lock,
        LOCK_EX
    )
) {

    fclose(
        $lock
    );

    agt_fail(
        'Texture cache lock could not be acquired.',
        503
    );
}

/*
 * Another request may have populated the cache while we waited.
 */

if (agt_png_valid($pngPath)) {

    flock(
        $lock,
        LOCK_UN
    );

    fclose(
        $lock
    );

    agt_send_png(
        $pngPath
    );
}

$token =
    bin2hex(
        random_bytes(8)
    );

$assetTemp =
    $cacheRoot .
    DIRECTORY_SEPARATOR .
    '.' .
    $uuid .
    '-' .
    $token .
    '.j2c.tmp';

$pngTemp =
    $cacheRoot .
    DIRECTORY_SEPARATOR .
    '.' .
    $uuid .
    '-' .
    $token .
    '.png.tmp';

$assetUrl =
    ag_dg_private_robust_base() . '/assets/' .
    $uuid .
    '/data';

$context =
    stream_context_create(
        [
            'http' => [
                'method' =>
                    'GET',

                'timeout' =>
                    15,

                'ignore_errors' =>
                    true,
                'follow_location' => 0,

                'header' =>
                    "Connection: close\r\n"
            ]
        ]
    );

$http_response_header =
    [];

$asset =
    @file_get_contents(
        $assetUrl,
        false,
        $context,
        0,
        16777217
    );

$status =
    agt_http_status(
        $http_response_header
    );

if (
    $status !== 200 || $asset === false ||
    strlen($asset) < 16 || strlen($asset) > 16777216
) {

    flock(
        $lock,
        LOCK_UN
    );

    fclose(
        $lock
    );

    if ($status === 404) {

        agt_fail(
            'OpenSim texture asset was not found.',
            404
        );
    }

    agt_fail(
        'OpenSim texture asset could not be fetched.',
        502
    );
}

if (
    @file_put_contents(
        $assetTemp,
        $asset,
        LOCK_EX
    ) !== strlen($asset)
) {

    @unlink(
        $assetTemp
    );

    flock(
        $lock,
        LOCK_UN
    );

    fclose(
        $lock
    );

    agt_fail(
        'Texture asset temporary file could not be written.',
        500
    );
}

$command = [
    $helper,
    ag_dg_opensim_bin(),
    $assetTemp,
    $pngTemp
];

// File-backed bounded output avoids pipe deadlocks in the Windows web host.
$stdoutPath=$assetTemp.'.out';$stderrPath=$assetTemp.'.err';$pipes=[];
$process=@proc_open($command,[0=>['pipe','r'],1=>['file',$stdoutPath,'w'],2=>['file',$stderrPath,'w']],
    $pipes,null,null,['bypass_shell'=>true]);
$exitCode=-1;$stdout='';$stderr='';
if(is_resource($process)){
    fclose($pipes[0]);$deadline=microtime(true)+10;
    do {$status=proc_get_status($process);if(!$status['running'])break;
        if(microtime(true)>=$deadline){proc_terminate($process);break;}usleep(10000);
    }while(true);
    $closed=proc_close($process);$exitCode=($status['exitcode']??-1)>=0?$status['exitcode']:$closed;
    $stdout=(string)@file_get_contents($stdoutPath,false,null,0,4096);
    $stderr=(string)@file_get_contents($stderrPath,false,null,0,4096);
}
@unlink($stdoutPath);@unlink($stderrPath);@unlink($assetTemp);

$decoded =
    json_decode(
        $stdout,
        true
    );

if (
    $exitCode !== 0 ||
    !is_array($decoded) ||
    empty($decoded['ok']) ||
    !agt_png_valid($pngTemp)
) {

    @unlink(
        $pngTemp
    );

    flock(
        $lock,
        LOCK_UN
    );

    fclose(
        $lock
    );

    agt_fail(
        'OpenSim JPEG2000 texture could not be decoded.',
        422
    );
}

/*
 * Publish the completed PNG atomically while holding the lock.
 */

if (agt_png_valid($pngPath)) {

    @unlink(
        $pngTemp
    );
}
else {

    if (
        !@rename(
            $pngTemp,
            $pngPath
        )
    ) {

        @unlink(
            $pngTemp
        );

        flock(
            $lock,
            LOCK_UN
        );

        fclose(
            $lock
        );

        agt_fail(
            'Decoded texture could not be cached.',
            500
        );
    }
}

flock(
    $lock,
    LOCK_UN
);

fclose(
    $lock
);

agt_send_png(
    $pngPath
);
