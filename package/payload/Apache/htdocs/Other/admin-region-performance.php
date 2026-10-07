<?php

declare(strict_types=1);

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';
ag_require_admin();


/*
 * ============================================================
 * AUSTRALIA REGIONS V7.9C
 * WINDOWS OPENSIM PROCESS PERFORMANCE
 * ============================================================
 */


header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


/*
 * ============================================================
 * BASIC HELPERS
 * ============================================================
 */


function v79c_num($value, float $default = 0.0): float
{
    if (is_int($value) || is_float($value)) {
        return (float)$value;
    }

    if (is_string($value) && is_numeric(trim($value))) {
        return (float)trim($value);
    }

    return $default;
}


function v79c_uptime($seconds): string
{
    $seconds =
        max(
            0,
            (int)round(
                v79c_num(
                    $seconds
                )
            )
        );


    $days =
        intdiv(
            $seconds,
            86400
        );


    $seconds =
        $seconds % 86400;


    $hours =
        intdiv(
            $seconds,
            3600
        );


    $seconds =
        $seconds % 3600;


    $minutes =
        intdiv(
            $seconds,
            60
        );


    $seconds =
        $seconds % 60;


    if ($days > 0) {

        return sprintf(
            '%dd %02dh %02dm',
            $days,
            $hours,
            $minutes
        );
    }


    if ($hours > 0) {

        return sprintf(
            '%dh %02dm %02ds',
            $hours,
            $minutes,
            $seconds
        );
    }


    return sprintf(
        '%dm %02ds',
        $minutes,
        $seconds
    );
}


function v79c_fail(string $message): void
{
    echo json_encode(
        [
            'ok' =>
                false,

            'error' =>
                $message,

            'host' =>
                new stdClass(),

            'regions' =>
                [],

            'rows' =>
                [],
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
 * ============================================================
 * REGION NAME FROM REGION CONFIG
 * ============================================================
 */


function v79c_region_name(string $directory): string
{
    $files =
        glob(
            $directory
            .
            DIRECTORY_SEPARATOR
            .
            '*.ini'
        );


    if (!is_array($files)) {

        return basename(
            $directory
        );
    }


    foreach ($files as $file) {

        if (
            strtolower(
                basename(
                    $file
                )
            )
            ===
            'opensim.ini'
        ) {

            continue;
        }


        $text =
            @file_get_contents(
                $file
            );


        if (!is_string($text) || $text === '') {

            continue;
        }


        $current =
            '';


        $lines =
            preg_split(
                '/\R/',
                $text
            );


        if (!is_array($lines)) {

            continue;
        }


        foreach ($lines as $line) {

            if (
                preg_match(
                    '/^\s*\[([^\]]+)\]\s*$/',
                    $line,
                    $section
                )
            ) {

                $current =
                    trim(
                        $section[1]
                    );

                continue;
            }


            if (
                $current !== ''
                &&
                preg_match(
                    '/^\s*RegionUUID\s*=/i',
                    $line
                )
            ) {

                return $current;
            }
        }
    }


    return basename(
        $directory
    );
}


/*
 * ============================================================
 * DISCOVER REGION PORTS
 * ============================================================
 */


function v79c_regions(string $root): array
{
    $regions = [];


    if (!is_dir($root)) {

        return $regions;
    }


    try {

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    FilesystemIterator::SKIP_DOTS
                )
            );


        foreach ($iterator as $file) {

            if (!$file->isFile()) {

                continue;
            }


            if (
                strtolower(
                    $file->getFilename()
                )
                !==
                'opensim.ini'
            ) {

                continue;
            }


            $text =
                @file_get_contents(
                    $file->getPathname()
                );


            if (!is_string($text) || $text === '') {

                continue;
            }


            if (
                !preg_match(
                    '/^\s*http_listener_port\s*=\s*"?(\d+)"?/mi',
                    $text,
                    $match
                )
            ) {

                continue;
            }


            $port =
                (int)$match[1];


            if ($port <= 0 || $port > 65535) {

                continue;
            }


            $directory =
                $file->getPath();


            $regions[$port] =
                v79c_region_name(
                    $directory
                );
        }
    }
    catch (Throwable $error) {
    }


    ksort(
        $regions,
        SORT_NUMERIC
    );


    return $regions;
}


/*
 * ============================================================
 * POWERSHELL
 * ============================================================
 */


function v79c_powershell(): ?string
{
    $candidate =
        ag_dg_pwsh_exe();


    if (is_file($candidate)) {

        return $candidate;
    }


    return null;
}


function v79c_collect(
    string $powershell,
    string $collector,
    string $ports
): array {

    $command =
        [
            $powershell,
            '-NoLogo',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $collector,
            '-Ports',
            $ports,
        ];


    $stdout = '';
    $stderr = '';


    if (function_exists('proc_open')) {

        $descriptorSpec =
            [
                0 =>
                    [
                        'pipe',
                        'r',
                    ],

                1 =>
                    [
                        'pipe',
                        'w',
                    ],

                2 =>
                    [
                        'pipe',
                        'w',
                    ],
            ];


        $process =
            @proc_open(
                $command,
                $descriptorSpec,
                $pipes
            );


        if (is_resource($process)) {

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


            proc_close(
                $process
            );
        }
    }


    if (trim((string)$stdout) === '') {

        return
            [
                'ok' =>
                    false,

                'error' =>
                    trim((string)$stderr) !== ''
                        ?
                        trim((string)$stderr)
                        :
                        'PowerShell collector returned no output.',
            ];
    }


    $json =
        json_decode(
            trim(
                (string)$stdout
            ),
            true
        );


    if (!is_array($json)) {

        return
            [
                'ok' =>
                    false,

                'error' =>
                    'PowerShell collector returned invalid JSON.',
            ];
    }


    return $json;
}


/*
 * ============================================================
 * PATHS
 * ============================================================
 */


$outworldz =
    dirname(
        __DIR__,
        3
    );


$regionsRoot =
    $outworldz
    .
    DIRECTORY_SEPARATOR
    .
    'Opensim'
    .
    DIRECTORY_SEPARATOR
    .
    'bin'
    .
    DIRECTORY_SEPARATOR
    .
    'Regions';


$collector =
    __DIR__
    .
    DIRECTORY_SEPARATOR
    .
    'admin-region-performance-collector.ps1';


$powershell =
    v79c_powershell();


if ($powershell === null) {

    v79c_fail(
        'PowerShell 7 was not found.'
    );
}


if (!is_file($collector)) {

    v79c_fail(
        'Performance collector was not found.'
    );
}


/*
 * ============================================================
 * REGION MAP
 * ============================================================
 */


$regionMap =
    v79c_regions(
        $regionsRoot
    );


if (count($regionMap) === 0) {

    v79c_fail(
        'No OpenSim region ports were discovered.'
    );
}


$ports =
    implode(
        ',',
        array_keys(
            $regionMap
        )
    );


/*
 * ============================================================
 * WINDOWS DATA
 * ============================================================
 */


$collection =
    v79c_collect(
        $powershell,
        $collector,
        $ports
    );


if (
    empty(
        $collection['ok']
    )
) {

    v79c_fail(
        (string)(
            $collection['error']
            ??
            'Windows process collection failed.'
        )
    );
}


/*
 * ============================================================
 * HOST
 * ============================================================
 */


$hostRaw =
    is_array(
        $collection['host']
        ??
        null
    )
        ?
        $collection['host']
        :
        [];


$physicalCores =
    (int)v79c_num(
        $hostRaw['PhysicalCores']
        ??
        0
    );


$logicalThreads =
    (int)v79c_num(
        $hostRaw['LogicalThreads']
        ??
        0
    );


if ($logicalThreads <= 0) {

    $logicalThreads = 1;
}


$totalMemoryBytes =
    v79c_num(
        $hostRaw['TotalMemoryBytes']
        ??
        0
    );


$host =
    [
        'CpuModel' =>
            trim(
                (string)(
                    $hostRaw['CpuModel']
                    ??
                    ''
                )
            ),

        'PhysicalCores' =>
            $physicalCores,

        'LogicalThreads' =>
            $logicalThreads,

        'TotalMemoryGb' =>
            $totalMemoryBytes > 0
                ?
                round(
                    $totalMemoryBytes /
                    1073741824,
                    2
                )
                :
                0,
    ];


/*
 * ============================================================
 * CPU DELTA STATE
 * ============================================================
 */


$stateFile =
    sys_get_temp_dir()
    .
    DIRECTORY_SEPARATOR
    .
    'australia-region-performance-v79c.json';


$previous =
    [];


if (is_file($stateFile)) {

    $stateText =
        @file_get_contents(
            $stateFile
        );


    if (is_string($stateText) && $stateText !== '') {

        $stateJson =
            json_decode(
                $stateText,
                true
            );


        if (is_array($stateJson)) {

            $previous =
                $stateJson;
        }
    }
}


$now =
    v79c_num(
        $collection['timestamp']
        ??
        microtime(true),
        microtime(true)
    );


$previousTime =
    v79c_num(
        $previous['timestamp']
        ??
        0
    );


$elapsed =
    $now -
    $previousTime;


$previousCpu =
    is_array(
        $previous['cpu']
        ??
        null
    )
        ?
        $previous['cpu']
        :
        [];


$newCpu =
    [];


/*
 * ============================================================
 * PROCESS BY PORT
 * ============================================================
 */


$processByPort =
    [];


$processes =
    is_array(
        $collection['processes']
        ??
        null
    )
        ?
        $collection['processes']
        :
        [];


foreach ($processes as $process) {

    if (!is_array($process)) {

        continue;
    }


    $port =
        (int)v79c_num(
            $process['Port']
            ??
            0
        );


    if ($port > 0) {

        $processByPort[$port] =
            $process;
    }
}


/*
 * ============================================================
 * BUILD ROWS
 * ============================================================
 */


$rows =
    [];


foreach ($regionMap as $port => $regionName) {

    $process =
        $processByPort[$port]
        ??
        [];


    $running =
        !empty(
            $process['Running']
        );


    $pid =
        (int)v79c_num(
            $process['ProcessId']
            ??
            0
        );


    $cpuTotal =
        max(
            0,
            v79c_num(
                $process['CpuTotalSeconds']
                ??
                0
            )
        );


    if ($pid > 0) {

        $newCpu[(string)$pid] =
            $cpuTotal;
    }


    $coreEquivalent =
        0.0;


    $cpuPercent =
        0.0;


    if (
        $running
        &&
        $pid > 0
        &&
        $elapsed > 0.20
        &&
        array_key_exists(
            (string)$pid,
            $previousCpu
        )
    ) {

        $oldCpu =
            v79c_num(
                $previousCpu[(string)$pid]
            );


        $delta =
            $cpuTotal -
            $oldCpu;


        if ($delta >= 0) {

            $coreEquivalent =
                $delta /
                $elapsed;


            $coreEquivalent =
                max(
                    0,
                    min(
                        $logicalThreads,
                        $coreEquivalent
                    )
                );


            $cpuPercent =
                (
                    $coreEquivalent /
                    $logicalThreads
                )
                *
                100;


            $cpuPercent =
                max(
                    0,
                    min(
                        100,
                        $cpuPercent
                    )
                );
        }
    }


    $workingSetBytes =
        max(
            0,
            v79c_num(
                $process['WorkingSetBytes']
                ??
                0
            )
        );


    $affinity =
        (int)v79c_num(
            $process['AffinityThreads']
            ??
            0
        );


    if ($running && $affinity <= 0) {

        $affinity =
            $logicalThreads;
    }


    $threadCount =
        (int)v79c_num(
            $process['ThreadCount']
            ??
            0
        );


    $uptimeSeconds =
        max(
            0,
            v79c_num(
                $process['UptimeSeconds']
                ??
                0
            )
        );


    $rows[] =
        [
            'RegionName' =>
                (string)$regionName,

            'Running' =>
                $running,

            'ProcessId' =>
                $pid,

            'Port' =>
                (int)$port,

            'CpuHostPercent' =>
                round(
                    $cpuPercent,
                    3
                ),

            'CpuEquivalentCores' =>
                round(
                    $coreEquivalent,
                    4
                ),

            'WorkingSetGb' =>
                round(
                    $workingSetBytes /
                    1073741824,
                    4
                ),

            'AffinityThreads' =>
                $affinity,

            'ThreadCount' =>
                $threadCount,

            'UptimeText' =>
                $running
                    ?
                    v79c_uptime(
                        $uptimeSeconds
                    )
                    :
                    '—',
        ];
}


/*
 * ============================================================
 * SAVE CPU SAMPLE
 * ============================================================
 */


@file_put_contents(
    $stateFile,
    json_encode(
        [
            'timestamp' =>
                $now,

            'cpu' =>
                $newCpu,
        ],
        JSON_UNESCAPED_SLASHES
    ),
    LOCK_EX
);


/*
 * ============================================================
 * OUTPUT
 * ============================================================
 */


echo json_encode(
    [
        'ok' =>
            true,

        'source' =>
            'Windows OpenSim performance V7.9C',

        'host' =>
            $host,

        'regionCount' =>
            count(
                $rows
            ),

        'regions' =>
            $rows,

        'rows' =>
            $rows,
    ],
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);