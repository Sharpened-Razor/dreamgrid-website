<?php

declare(strict_types=1);
require_once __DIR__ . '/core/bootstrap.php';
ag_no_cache();
header('Content-Type: application/json; charset=utf-8');
$session = ag_current_session();
if (!$session || !ag_is_admin($session)) {
    http_response_code($session ? 403 : 401);
    echo json_encode(['ok'=>false, 'error'=>$session ? 'Administrator access required.' : 'Not logged in.']);
    exit;
}


/*
 ===============================================================
 AUSTRALIA CONTROL CENTER
 PERFORMANCE HISTORY API V1
 ===============================================================
*/


header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');


function out(array $data): never
{
    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


$root =
    realpath(
        dirname(__DIR__, 3)
    );


if ($root === false) {

    out([
        'ok' => false,
        'error' => 'DreamGrid root could not be derived.'
    ]);
}


$dataDir =
    $root .
    DIRECTORY_SEPARATOR .
    'ControlCenterData' .
    DIRECTORY_SEPARATOR .
    'StatsHistory';


$historyFile =
    $dataDir .
    DIRECTORY_SEPARATOR .
    'history.jsonl';


$statusFile =
    $dataDir .
    DIRECTORY_SEPARATOR .
    'collector-status.json';


$range =
    strtolower(
        trim(
            (string)(
                $_GET['range'] ?? '1h'
            )
        )
    );


$ranges = [

    '1h' =>
        60 * 60,

    '6h' =>
        6 * 60 * 60,

    '24h' =>
        24 * 60 * 60,

    '7d' =>
        7 * 24 * 60 * 60
];


if (!isset($ranges[$range])) {

    $range =
        '1h';
}


$cutoff =
    time() -
    $ranges[$range];


$status =
    null;


if (is_file($statusFile)) {

    $raw =
        @file_get_contents(
            $statusFile
        );


    if ($raw !== false) {

        $decoded =
            json_decode(
                $raw,
                true
            );


        if (is_array($decoded)) {

            $status =
                $decoded;
        }
    }
}


if (!is_file($historyFile)) {

    out([
        'ok' => true,
        'range' => $range,
        'samples' => [],
        'events' => [],
        'status' => $status,
        'message' => 'History collector has not written a sample yet.'
    ]);
}


$file =
    new SplFileObject(
        $historyFile,
        'r'
    );


$samples =
    [];


while (!$file->eof()) {

    $line =
        trim(
            (string)$file->fgets()
        );


    if ($line === '') {
        continue;
    }


    $row =
        json_decode(
            $line,
            true
        );


    if (!is_array($row)) {
        continue;
    }


    $stamp =
        strtotime(
            (string)(
                $row['ts'] ?? ''
            )
        );


    if (
        $stamp === false ||
        $stamp < $cutoff
    ) {
        continue;
    }


    $row['_unix'] =
        $stamp;


    $samples[] =
        $row;
}


usort(
    $samples,
    static function(
        array $a,
        array $b
    ): int {

        return
            ($a['_unix'] ?? 0)
            <=>
            ($b['_unix'] ?? 0);
    }
);


/*
 ===============================================================
 EVENTS

 Derive important changes from the full-resolution history before
 graph decimation.
 ===============================================================
*/


$events =
    [];


$previous =
    null;


foreach ($samples as $row) {

    if ($previous !== null) {

        $checks = [

            [
                'key' => 'login',
                'name' => 'Grid login service'
            ],

            [
                'key' => 'diagnostics',
                'name' => 'Grid diagnostics service'
            ],

            [
                'key' => 'telemetryOk',
                'name' => 'Region telemetry'
            ]
        ];


        foreach ($checks as $check) {

            $key =
                $check['key'];


            $old =
                (bool)(
                    $previous[$key] ?? false
                );


            $new =
                (bool)(
                    $row[$key] ?? false
                );


            if ($old !== $new) {

                $events[] = [

                    'ts' =>
                        $row['ts'] ?? null,

                    'level' =>
                        $new
                            ? 'good'
                            : 'bad',

                    'text' =>
                        $check['name'] .
                        (
                            $new
                                ? ' recovered'
                                : ' unavailable'
                        )
                ];
            }
        }


        foreach ([
            'apache' => 'Apache',
            'mysql' => 'MySQL',
            'robust' => 'Robust',
            'opensim' => 'OpenSim'
        ] as $key => $name) {

            $old =
                (int)(
                    $previous[$key] ?? 0
                );


            $new =
                (int)(
                    $row[$key] ?? 0
                );


            if ($old !== $new) {

                $events[] = [

                    'ts' =>
                        $row['ts'] ?? null,

                    'level' =>
                        $new > 0
                            ? 'good'
                            : 'bad',

                    'text' =>
                        $name .
                        ' process count ' .
                        $old .
                        ' → ' .
                        $new
                ];
            }
        }


        $oldAvatars =
            (int)(
                $previous['rootAgents'] ?? 0
            );


        $newAvatars =
            (int)(
                $row['rootAgents'] ?? 0
            );


        if ($oldAvatars !== $newAvatars) {

            $events[] = [

                'ts' =>
                    $row['ts'] ?? null,

                'level' =>
                    'info',

                'text' =>
                    'Root avatars ' .
                    $oldAvatars .
                    ' → ' .
                    $newAvatars
            ];
        }
    }


    $previous =
        $row;
}


/*
 ===============================================================
 DECIMATE LONG RANGES FOR THE BROWSER

 Keep at most ~1200 points.
 ===============================================================
*/


$graphSamples =
    $samples;


$maxPoints =
    1200;


if (count($graphSamples) > $maxPoints) {

    $step =
        (int)ceil(
            count($graphSamples) /
            $maxPoints
        );


    $reduced =
        [];


    $count =
        count(
            $graphSamples
        );


    for (
        $i = 0;
        $i < $count;
        $i += $step
    ) {

        $reduced[] =
            $graphSamples[$i];
    }


    $last =
        $graphSamples[
            $count - 1
        ];


    if (
        empty($reduced) ||
        (
            $reduced[
                count($reduced) - 1
            ]['ts'] ?? null
        )
        !==
        (
            $last['ts'] ?? null
        )
    ) {

        $reduced[] =
            $last;
    }


    $graphSamples =
        $reduced;
}


foreach ($graphSamples as &$row) {

    unset(
        $row['_unix']
    );
}


unset($row);


$events =
    array_slice(
        array_reverse(
            $events
        ),
        0,
        100
    );


$latest =
    null;


if (!empty($samples)) {

    $latest =
        $samples[
            count($samples) - 1
        ];


    unset(
        $latest['_unix']
    );
}


$collectorAge =
    null;


if (
    is_array($status) &&
    isset($status['lastRunUtc'])
) {

    $statusTime =
        strtotime(
            (string)$status['lastRunUtc']
        );


    if ($statusTime !== false) {

        $collectorAge =
            max(
                0,
                time() -
                $statusTime
            );
    }
}


out([
    'ok' => true,

    'range' =>
        $range,

    'fullSampleCount' =>
        count($samples),

    'graphSampleCount' =>
        count($graphSamples),

    'collectorAgeSec' =>
        $collectorAge,

    'status' =>
        $status,

    'latest' =>
        $latest,

    'samples' =>
        $graphSamples,

    'events' =>
        $events
]);