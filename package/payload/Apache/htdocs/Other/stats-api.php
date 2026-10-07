<?php

declare(strict_types=1);


require_once __DIR__ . '/core/bootstrap.php';


$envFile =
    __DIR__ .
    '/core/dreamgrid-env.php';


if (is_file($envFile)) {
    require_once $envFile;
}


$session =
    ag_current_session();


if (!$session) {

    http_response_code(401);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        [
            'ok' =>
                false,

            'error' =>
                'Not logged in.'
        ]
    );

    exit;
}


$level =
    function_exists('ag_user_level')
        ? (int)ag_user_level($session)
        : (int)($session['level'] ?? 0);


if ($level < 200) {

    http_response_code(403);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        [
            'ok' =>
                false,

            'error' =>
                'Grid Owner access required.'
        ]
    );

    exit;
}


header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);



function stats_root(): string
{
    $root =
        realpath(
            dirname(
                __DIR__,
                3
            )
        );


    if (
        $root === false ||
        !is_file(
            $root .
            DIRECTORY_SEPARATOR .
            'Settings.ini'
        )
    ) {

        throw new RuntimeException(
            'Unable to resolve DreamGrid root.'
        );
    }


    return $root;
}



function stats_process_count(
    string $image
): ?int
{
    if (!function_exists('shell_exec')) {
        return null;
    }


    $safe =
        preg_replace(
            '/[^A-Za-z0-9_.-]/',
            '',
            $image
        );


    if (
        !is_string($safe) ||
        $safe === ''
    ) {
        return null;
    }


    $command =
        'tasklist /FI "IMAGENAME eq ' .
        $safe .
        '" /FO CSV /NH 2>NUL';


    $output =
        @shell_exec(
            $command
        );


    if (!is_string($output)) {
        return null;
    }


    $lines =
        preg_split(
            '/\r\n|\r|\n/',
            trim($output)
        );


    if (!is_array($lines)) {
        return 0;
    }


    $count = 0;


    foreach ($lines as $line) {

        $line =
            trim(
                (string)$line
            );


        if (
            $line === '' ||
            stripos(
                $line,
                'INFO:'
            ) === 0
        ) {
            continue;
        }


        if (
            stripos(
                $line,
                '"' . $safe . '"'
            ) === 0
        ) {
            $count++;
        }
    }


    return $count;
}



function stats_system_snapshot(): ?array
{
    if (!function_exists('shell_exec')) {
        return null;
    }


    $helper =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        'windows' .
        DIRECTORY_SEPARATOR .
        'StatsSystemSnapshot.ps1';


    if (!is_file($helper)) {
        return null;
    }


    $quoted =
        '"' .
        str_replace(
            '"',
            '""',
            $helper
        ) .
        '"';


    $command =
        'powershell.exe ' .
        '-NoLogo ' .
        '-NoProfile ' .
        '-NonInteractive ' .
        '-ExecutionPolicy Bypass ' .
        '-File ' .
        $quoted .
        ' 2>NUL';


    $output =
        @shell_exec(
            $command
        );


    if (
        !is_string($output) ||
        trim($output) === ''
    ) {
        return null;
    }


    $output =
        preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            trim($output)
        );


    if (!is_string($output)) {
        return null;
    }


    $decoded =
        json_decode(
            $output,
            true
        );


    return
        is_array($decoded)
            ? $decoded
            : null;
}



function stats_tcp(
    string $host,
    int $port,
    float $timeout = 1.3
): array
{
    $errno = 0;
    $error = '';


    $socket =
        @fsockopen(
            $host,
            $port,
            $errno,
            $error,
            $timeout
        );


    if ($socket) {

        fclose($socket);

        return [
            'ok' =>
                true,

            'detail' =>
                'RESPONDING'
        ];
    }


    return [
        'ok' =>
            false,

        'detail' =>
            $error !== ''
                ? $error
                : 'NO RESPONSE'
    ];
}



function stats_tail(
    string $file,
    int $bytes = 262144
): string
{
    if (!is_file($file)) {
        return '';
    }


    $size =
        @filesize(
            $file
        );


    if (
        !is_int($size) ||
        $size <= 0
    ) {
        return '';
    }


    $handle =
        @fopen(
            $file,
            'rb'
        );


    if (!$handle) {
        return '';
    }


    $read =
        min(
            $bytes,
            $size
        );


    if ($size > $read) {

        @fseek(
            $handle,
            -$read,
            SEEK_END
        );
    }


    $data =
        @fread(
            $handle,
            $read
        );


    fclose($handle);


    return
        is_string($data)
            ? $data
            : '';
}



function stats_recent_errors(
    string $root,
    int $maximum = 8
): array
{
    $separator =
        DIRECTORY_SEPARATOR;


    $patterns =
        [
            $root .
            $separator .
            'Opensim' .
            $separator .
            'bin' .
            $separator .
            '*.log',

            $root .
            $separator .
            'logs' .
            $separator .
            'Apache' .
            $separator .
            '*.log',

            $root .
            $separator .
            'Apache' .
            $separator .
            'logs' .
            $separator .
            '*.log',

            $root .
            $separator .
            'Robust' .
            $separator .
            '*.log'
        ];


    $files = [];


    foreach ($patterns as $pattern) {

        $matches =
            glob(
                $pattern
            );


        if (!is_array($matches)) {
            continue;
        }


        foreach ($matches as $file) {

            if (is_file($file)) {
                $files[$file] = $file;
            }
        }
    }


    $files =
        array_values(
            $files
        );


    /*
     * STATS V3 IGNORE ACCESS LOGS
     *
     * Apache access logs contain ordinary HTTP requests and
     * 404 probes. They are not treated as important errors.
     */

    $files =
        array_values(
            array_filter(
                $files,
                static function($file): bool {

                    $name =
                        strtolower(
                            basename(
                                (string)$file
                            )
                        );


                    if (
                        str_starts_with(
                            $name,
                            'access'
                        )
                    ) {
                        return false;
                    }


                    return true;
                }
            )
        );


    usort(
        $files,
        static function(
            string $a,
            string $b
        ): int {

            return
                ((int)@filemtime($b))
                <=>
                ((int)@filemtime($a));
        }
    );


    $files =
        array_slice(
            $files,
            0,
            8
        );


    $errors = [];

    $seen = [];


    foreach ($files as $file) {

        $text =
            stats_tail(
                $file
            );


        if ($text === '') {
            continue;
        }


        $lines =
            preg_split(
                '/\r\n|\r|\n/',
                $text
            );


        if (!is_array($lines)) {
            continue;
        }


        foreach (
            array_reverse($lines)
            as
            $line
        ) {

            $line =
                trim(
                    (string)$line
                );


            if ($line === '') {
                continue;
            }


            if (
                !preg_match(
                    '/(ERROR|FATAL|Exception|crash|watchdog|Unable to|failed)/i',
                    $line
                )
            ) {
                continue;
            }


            $key =
                basename($file) .
                '|' .
                $line;


            if (isset($seen[$key])) {
                continue;
            }


            $seen[$key] =
                true;


            $message =
                function_exists(
                    'mb_substr'
                )
                    ? mb_substr(
                        $line,
                        0,
                        700
                    )
                    : substr(
                        $line,
                        0,
                        700
                    );


            $errors[] =
                [
                    'source' =>
                        basename($file),

                    'message' =>
                        $message
                ];


            if (
                count($errors) >=
                $maximum
            ) {
                return $errors;
            }
        }
    }


    return $errors;
}



try {

    $root =
        stats_root();


    $system =
        stats_system_snapshot();


    $host =
        trim(
            (string)(
                $_SERVER['HTTP_HOST'] ??
                ''
            )
        );


    $host =
        preg_replace(
            '/:\d+$/',
            '',
            $host
        );


    $host =
        trim(
            (string)$host,
            '[]'
        );


    if ($host === '') {
        $host = ag_web_local_host();
    }


    $resolved =
        @gethostbyname(
            $host
        );


    $hostIsIp =
        filter_var(
            $host,
            FILTER_VALIDATE_IP
        ) !== false;


    $dnsOk =
        $hostIsIp ||
        (
            is_string($resolved) &&
            $resolved !== '' &&
            $resolved !== $host
        );


    $diagBase =
        function_exists(
            'ag_dg_diagnostics_base'
        )
            ? (string)ag_dg_diagnostics_base()
            : ag_dg_diagnostics_base();


    $parts =
        @parse_url(
            $diagBase
        );


    $diagHost =
        (
            is_array($parts) &&
            !empty($parts['host'])
        )
            ? (string)$parts['host']
            : ag_web_local_host();


    $diagPort =
        (
            is_array($parts) &&
            !empty($parts['port'])
        )
            ? (int)$parts['port']
            : ag_dg_diagnostics_port();


    $diskRoot =
        preg_match(
            '/^[A-Za-z]:[\\\\\/]/',
            $root
        )
            ? substr(
                $root,
                0,
                3
            )
            : $root;


    $free =
        @disk_free_space(
            $diskRoot
        );


    $total =
        @disk_total_space(
            $diskRoot
        );


    $used =
        (
            is_numeric($free) &&
            is_numeric($total)
        )
            ? max(
                0,
                (float)$total -
                (float)$free
            )
            : null;


    $percentFree =
        (
            is_numeric($free) &&
            is_numeric($total) &&
            (float)$total > 0
        )
            ? round(
                (
                    (float)$free /
                    (float)$total
                ) *
                100,
                1
            )
            : null;


    echo json_encode(
        [
            'ok' =>
                true,

            'checkedAt' =>
                date(
                    DATE_ATOM
                ),

            'phpVersion' =>
                PHP_VERSION,

            'system' =>
                $system,

            'processes' =>
                [
                    'apache' =>
                        stats_process_count(
                            'httpd.exe'
                        ),

                    'mysql' =>
                        stats_process_count(
                            'mysqld.exe'
                        ),

                    'robust' =>
                        stats_process_count(
                            'Robust.exe'
                        ),

                    'opensim' =>
                        stats_process_count(
                            'OpenSim.exe'
                        )
                ],

            'network' =>
                [
                    'host' =>
                        $host,

                    'resolvedIp' =>
                        $dnsOk
                            ? (string)$resolved
                            : '',

                    'dnsOk' =>
                        $dnsOk,
                    'loginPort' => ag_dg_robust_port(),

                    'login' =>
                        stats_tcp(
                            ag_web_local_host(),
                            ag_dg_robust_port()
                        ),

                    'diagnostics' =>
                        stats_tcp(
                            $diagHost,
                            $diagPort
                        ),

                    'diagnosticsPort' =>
                        $diagPort
                ],

            'disk' =>
                [
                    'free' =>
                        is_numeric($free)
                            ? (float)$free
                            : null,

                    'used' =>
                        $used,

                    'total' =>
                        is_numeric($total)
                            ? (float)$total
                            : null,

                    'percentFree' =>
                        $percentFree
                ],

            'errors' =>
                stats_recent_errors(
                    $root,
                    8
                )
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
}
catch (Throwable $error) {

    http_response_code(500);

    echo json_encode(
        [
            'ok' =>
                false,

            'error' =>
                $error->getMessage()
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
}
