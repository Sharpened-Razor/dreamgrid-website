<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_login();

header(
    'Content-Type: application/json; charset=UTF-8'
);

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
 * REAL OPENSIM MATERIAL METADATA SERVICE V6C-H1-R3
 *
 * Exact OpenMetaverse TextureEntry decoding.
 *
 * Important Windows behaviour:
 *
 * JSON is passed to the .NET decoder using a temporary UTF-8
 * JSON file. Native-process stdin is deliberately not used.
 *
 * GET:
 *     ?entry=<urlencoded Base64>
 *
 * POST:
 *     {"entries":["Base64","Base64",...]}
 *
 * Maximum:
 *     256 TextureEntries per .NET process.
 *
 * Read-only metadata service.
 * ============================================================
 */

function agmat_fail(
    string $message,
    int $status = 500
): never {

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

function agmat_valid_entry(
    string $entry
): bool {

    $entry =
        trim(
            $entry
        );

    if (
        $entry === '' ||
        strlen($entry) > 8192
    ) {

        return false;
    }

    $decoded =
        base64_decode(
            $entry,
            true
        );

    if ($decoded === false) {

        return false;
    }

    $length =
        strlen(
            $decoded
        );

    return
        $length >= 16 &&
        $length <= 4096;
}

function agmat_run_batch(
    array $entries
): array {

    $helper =
        (string)ag_dg_path('Texture3DTools' . DIRECTORY_SEPARATOR . 'TextureMaterialDecoder' . DIRECTORY_SEPARATOR . 'AustraliaTextureMaterial.exe');

    if (!is_file($helper)) {

        agmat_fail(
            'Material decoder is unavailable.',
            500
        );
    }

    $count =
        count(
            $entries
        );

    if (
        $count < 1 ||
        $count > 256
    ) {

        agmat_fail(
            'Material batch must contain 1-256 TextureEntries.',
            400
        );
    }

    $clean =
        [];

    foreach ($entries as $entry) {

        if (!is_string($entry)) {

            agmat_fail(
                'TextureEntry batch contains a non-string value.',
                400
            );
        }

        $entry =
            trim(
                $entry
            );

        if (!agmat_valid_entry($entry)) {

            agmat_fail(
                'TextureEntry batch contains invalid Base64 data.',
                400
            );
        }

        $clean[] =
            base64_encode(
                base64_decode(
                    $entry,
                    true
                )
            );
    }

    $payload =
        json_encode(
            $clean,
            JSON_UNESCAPED_SLASHES
        );

    if ($payload === false) {

        agmat_fail(
            'Material batch could not be encoded.',
            500
        );
    }

    $token =
        bin2hex(
            random_bytes(12)
        );

    $inputPath =
        rtrim(
            sys_get_temp_dir(),
            '\\/'
        ) .
        DIRECTORY_SEPARATOR .
        'australia-material-' .
        $token .
        '.json';

    if (
        file_put_contents(
            $inputPath,
            $payload,
            LOCK_EX
        ) !== strlen($payload)
    ) {

        @unlink(
            $inputPath
        );

        agmat_fail(
            'Material batch temporary file could not be written.',
            500
        );
    }

    $command = [
        $helper,
        'decode-batch',
        $inputPath
    ];

    $descriptors = [
        0 => [
            'pipe',
            'r'
        ],

        1 => [
            'pipe',
            'w'
        ],

        2 => [
            'pipe',
            'w'
        ]
    ];

    $pipes =
        [];

    $process =
        @proc_open(
            $command,
            $descriptors,
            $pipes,
            null,
            null,
            [
                'bypass_shell' =>
                    true
            ]
        );

    if (!is_resource($process)) {

        @unlink(
            $inputPath
        );

        agmat_fail(
            'Material decoder could not be started.',
            500
        );
    }

    /*
     * No process input is required.
     */

    fclose(
        $pipes[0]
    );

    $stdout =
        stream_get_contents(
            $pipes[1]
        );

    $stderr =
        stream_get_contents(
            $pipes[2]
        );

    fclose(
        $pipes[1]
    );

    fclose(
        $pipes[2]
    );

    $exitCode =
        proc_close(
            $process
        );

    @unlink(
        $inputPath
    );

    $decoded =
        json_decode(
            $stdout,
            true
        );

    if (
        $exitCode !== 0 ||
        !is_array($decoded) ||
        empty($decoded['ok']) ||
        !isset($decoded['Results']) ||
        !is_array($decoded['Results'])
    ) {

        agmat_fail(
            'TextureEntry material decoder failed.',
            422
        );
    }

    return $decoded;
}

/*
 * ------------------------------------------------------------
 * Single-entry browser proof
 * ------------------------------------------------------------
 */

$single =
    trim(
        (string)($_GET['entry'] ?? '')
    );

if ($single !== '') {

    if (!agmat_valid_entry($single)) {

        agmat_fail(
            'Invalid TextureEntry.',
            400
        );
    }

    $batch =
        agmat_run_batch(
            [
                $single
            ]
        );

    $first =
        $batch['Results'][0] ??
        null;

    if (!is_array($first)) {

        agmat_fail(
            'Material decoder returned no result.',
            422
        );
    }

    echo json_encode(
        [
            'ok' =>
                !empty($first['ok']),

            'Marker' =>
                'REAL OPENSIM MATERIAL METADATA SERVICE V6C-H1-R3',

            'Result' =>
                $first
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );

    exit;
}

/*
 * ------------------------------------------------------------
 * Batch POST
 * ------------------------------------------------------------
 */

if (
    strtoupper(
        (string)($_SERVER['REQUEST_METHOD'] ?? '')
    ) !== 'POST'
) {

    agmat_fail(
        'Material endpoint requires GET entry= or POST JSON.',
        405
    );
}

$raw =
    file_get_contents(
        'php://input'
    );

if ($raw === false) {

    agmat_fail(
        'Material request body could not be read.',
        400
    );
}

if (strlen($raw) > 2097152) {

    agmat_fail(
        'Material request body is too large.',
        413
    );
}

$request =
    json_decode(
        $raw,
        true
    );

if (
    !is_array($request) ||
    !isset($request['entries']) ||
    !is_array($request['entries'])
) {

    agmat_fail(
        'Material request JSON must contain entries[].',
        400
    );
}

$result =
    agmat_run_batch(
        $request['entries']
    );

echo json_encode(
    $result,
    JSON_UNESCAPED_SLASHES
);