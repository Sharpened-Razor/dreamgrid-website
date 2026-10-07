<?php

/*
 ============================================================
 Grid - REGION UPTIME API V1

 Records lightweight region status snapshots.

 NO RemoteAdmin calls.
 NO OpenSim configuration changes.
 ============================================================
*/

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


define(
    'AUSTRALIA_UPTIME_TOKEN',
    '60d9cfcdfd4cc73ed97f34f57061bb2a1a1bf14be33b0b4885051014f0e15b4d'
);


define(
    'AUSTRALIA_UPTIME_RETENTION',
    2592000
);


function uptimeReply(
    $data,
    $status = 200
)
{
    http_response_code(
        $status
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
}


$requestToken =
    isset(
        $_GET['token']
    )
        ? (string)$_GET['token']
        : '';


if(
    $requestToken === '' ||
    !hash_equals(
        AUSTRALIA_UPTIME_TOKEN,
        $requestToken
    )
){

    uptimeReply(
        array(
            'ok' => false,
            'error' => 'Forbidden'
        ),
        403
    );

    return;
}


/* ============================================================
   STATUS CLASSIFICATION
   ============================================================ */

function uptimeStatusClass(
    $status
)
{
    $status =
        strtolower(
            trim(
                (string)$status
            )
        );


    if(
        in_array(
            $status,
            array(
                'booted',
                'running',
                'online'
            ),
            true
        )
    ){

        return 'online';
    }


    if(
        in_array(
            $status,
            array(
                'booting',
                'starting',
                'recyclingdown',
                'restart',
                'restarting',
                'stopping',
                'backingup',
                'backup',
                'savingoar',
                'saving',
                'warning',
                'busy',
                'degraded'
            ),
            true
        )
    ){

        return 'warning';
    }


    if(
        in_array(
            $status,
            array(
                'stopped',
                'offline',
                'shutdown',
                'crashed',
                'failed',
                'failure',
                'error',
                'dead'
            ),
            true
        )
    ){

        return 'offline';
    }


    return 'unknown';
}


/* ============================================================
   STORAGE
   ============================================================ */

$jobsDir =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs';


$historyFile =
    $jobsDir .
    DIRECTORY_SEPARATOR .
    'grid-uptime-history.json';


if(
    !is_dir(
        $jobsDir
    )
){

    @mkdir(
        $jobsDir,
        0775,
        true
    );
}


function uptimeEmptyHistory()
{
    return array(
        'version' => 1,
        'created' => time(),
        'buckets' => array()
    );
}


function uptimeReadFile(
    $file
)
{
    if(
        !is_file(
            $file
        )
    ){

        return uptimeEmptyHistory();
    }


    $raw =
        @file_get_contents(
            $file
        );


    if(
        $raw === false ||
        trim(
            $raw
        ) === ''
    ){

        return uptimeEmptyHistory();
    }


    $data =
        json_decode(
            $raw,
            true
        );


    if(
        !is_array(
            $data
        ) ||
        !isset(
            $data['buckets']
        ) ||
        !is_array(
            $data['buckets']
        )
    ){

        return uptimeEmptyHistory();
    }


    return $data;
}


function uptimeWriteFile(
    $file,
    $history
)
{
    $json =
        json_encode(
            $history,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );


    if(
        $json === false
    ){

        return false;
    }


    return
        @file_put_contents(
            $file,
            $json,
            LOCK_EX
        ) !== false;
}


/* ============================================================
   RECORD STATUS SNAPSHOT
   ============================================================ */

$method =
    isset(
        $_SERVER['REQUEST_METHOD']
    )
        ? strtoupper(
            (string)$_SERVER['REQUEST_METHOD']
        )
        : 'GET';


if(
    $method === 'POST'
){

    $raw =
        @file_get_contents(
            'php://input'
        );


    $request =
        json_decode(
            $raw,
            true
        );


    if(
        !is_array(
            $request
        ) ||
        !isset(
            $request['regions']
        ) ||
        !is_array(
            $request['regions']
        )
    ){

        uptimeReply(
            array(
                'ok' => false,
                'error' => 'Invalid status sample'
            ),
            400
        );

        return;
    }


    $now =
        time();


    /*
     * Five-minute buckets.
     */

    $bucketStart =
        (int)(
            floor(
                $now / 300
            ) *
            300
        );


    $history =
        uptimeReadFile(
            $historyFile
        );


    $bucketIndex =
        -1;


    foreach(
        $history['buckets']
        as $index => $bucket
    ){

        if(
            isset(
                $bucket['start']
            ) &&
            (int)$bucket['start'] ===
            $bucketStart
        ){

            $bucketIndex =
                $index;

            break;
        }
    }


    if(
        $bucketIndex < 0
    ){

        $history['buckets'][] =
            array(
                'start' => $bucketStart,
                'regions' => array()
            );


        $bucketIndex =
            count(
                $history['buckets']
            ) - 1;
    }


    if(
        !isset(
            $history['buckets'][$bucketIndex]['regions']
        ) ||
        !is_array(
            $history['buckets'][$bucketIndex]['regions']
        )
    ){

        $history['buckets'][$bucketIndex]['regions'] =
            array();
    }


    $recorded =
        0;


    foreach(
        $request['regions']
        as $region
    ){

        if(
            !is_array(
                $region
            )
        ){

            continue;
        }


        $name =
            isset(
                $region['name']
            )
                ? trim(
                    (string)$region['name']
                )
                : '';


        $status =
            isset(
                $region['status']
            )
                ? trim(
                    (string)$region['status']
                )
                : '';


        if(
            $name === ''
        ){

            continue;
        }


        $history['buckets'][$bucketIndex]['regions'][$name] =
            array(
                'class' =>
                    uptimeStatusClass(
                        $status
                    ),

                'status' =>
                    $status
            );


        $recorded++;
    }


    /*
     * 30 day retention.
     */

    $cutoff =
        $now -
        AUSTRALIA_UPTIME_RETENTION;


    $kept =
        array();


    foreach(
        $history['buckets']
        as $bucket
    ){

        if(
            isset(
                $bucket['start']
            ) &&
            (int)$bucket['start'] >=
            $cutoff
        ){

            $kept[] =
                $bucket;
        }
    }


    $history['buckets'] =
        $kept;


    $history['updated'] =
        $now;


    if(
        !uptimeWriteFile(
            $historyFile,
            $history
        )
    ){

        uptimeReply(
            array(
                'ok' => false,
                'error' => 'Could not save uptime history'
            ),
            500
        );

        return;
    }


    uptimeReply(
        array(
            'ok' => true,
            'recorded' => $recorded,
            'time' => $now
        )
    );

    return;
}


/* ============================================================
   SUMMARY
   ============================================================ */

$allowedWindows =
    array(
        3600,
        86400,
        604800,
        2592000
    );


$window =
    isset(
        $_GET['window']
    )
        ? (int)$_GET['window']
        : 86400;


if(
    !in_array(
        $window,
        $allowedWindows,
        true
    )
){

    $window =
        86400;
}


$now =
    time();


$cutoff =
    $now -
    $window;


$history =
    uptimeReadFile(
        $historyFile
    );


$buckets =
    $history['buckets'];


usort(
    $buckets,
    function(
        $a,
        $b
    ){

        return
            (int)$a['start'] <=>
            (int)$b['start'];
    }
);


/*
 * Discover all region names.
 */

$allRegionNames =
    array();


foreach(
    $buckets
    as $bucket
){

    if(
        !isset(
            $bucket['regions']
        ) ||
        !is_array(
            $bucket['regions']
        )
    ){

        continue;
    }


    foreach(
        $bucket['regions']
        as $name => $entry
    ){

        $allRegionNames[$name] =
            true;
    }
}


$results =
    array();


foreach(
    array_keys(
        $allRegionNames
    )
    as $regionName
){

    $online =
        0;

    $warning =
        0;

    $offline =
        0;

    $unknown =
        0;


    $latestClass =
        'unknown';

    $latestRaw =
        '';

    $latestTime =
        null;


    $relevant =
        array();


    foreach(
        $buckets
        as $bucket
    ){

        $start =
            isset(
                $bucket['start']
            )
                ? (int)$bucket['start']
                : 0;


        if(
            !isset(
                $bucket['regions'][$regionName]
            )
        ){

            continue;
        }


        $entry =
            $bucket['regions'][$regionName];


        $class =
            isset(
                $entry['class']
            )
                ? (string)$entry['class']
                : 'unknown';


        $rawStatus =
            isset(
                $entry['status']
            )
                ? (string)$entry['status']
                : '';


        /*
         * Current status comes from latest record regardless
         * of selected reporting window.
         */

        if(
            $latestTime === null ||
            $start >= $latestTime
        ){

            $latestTime =
                $start;

            $latestClass =
                $class;

            $latestRaw =
                $rawStatus;
        }


        if(
            $start <
            $cutoff
        ){

            continue;
        }


        $relevant[] =
            array(
                'start' => $start,
                'class' => $class,
                'status' => $rawStatus
            );


        if(
            $class === 'online'
        ){

            $online++;
        }
        elseif(
            $class === 'warning'
        ){

            $warning++;
        }
        elseif(
            $class === 'offline'
        ){

            $offline++;
        }
        else{

            $unknown++;
        }
    }


    $known =
        $online +
        $warning +
        $offline;


    $uptime =
        $known > 0
            ? (
                $online /
                $known *
                100
            )
            : null;


    $availability =
        $known > 0
            ? (
                (
                    $online +
                    $warning
                ) /
                $known *
                100
            )
            : null;


    /*
     * Current status duration.
     */

    $currentSince =
        $latestTime;


    if(
        $latestTime !== null
    ){

        for(
            $index =
                count(
                    $buckets
                ) - 1;

            $index >= 0;

            $index--
        ){

            $bucket =
                $buckets[$index];


            if(
                !isset(
                    $bucket['regions'][$regionName]
                )
            ){

                continue;
            }


            $entry =
                $bucket['regions'][$regionName];


            $class =
                isset(
                    $entry['class']
                )
                    ? (string)$entry['class']
                    : 'unknown';


            if(
                $class !==
                $latestClass
            ){

                break;
            }


            $currentSince =
                (int)$bucket['start'];
        }
    }


    /*
     * Last outage start.
     */

    $lastOutage =
        null;


    $previousClass =
        null;


    foreach(
        $buckets
        as $bucket
    ){

        if(
            !isset(
                $bucket['regions'][$regionName]
            )
        ){

            continue;
        }


        $entry =
            $bucket['regions'][$regionName];


        $class =
            isset(
                $entry['class']
            )
                ? (string)$entry['class']
                : 'unknown';


        if(
            $class === 'offline' &&
            $previousClass !== 'offline'
        ){

            $lastOutage =
                (int)$bucket['start'];
        }


        $previousClass =
            $class;
    }


    /*
     * Build 48-cell timeline.
     */

    $timeline =
        array_fill(
            0,
            48,
            'unknown'
        );


    $severity =
        array(
            'unknown' => 0,
            'online' => 1,
            'warning' => 2,
            'offline' => 3
        );


    $secondsPerCell =
        $window /
        48;


    foreach(
        $relevant
        as $entry
    ){

        $position =
            (
                $entry['start'] -
                $cutoff
            ) /
            $secondsPerCell;


        $cell =
            (int)floor(
                $position
            );


        if(
            $cell < 0
        ){

            $cell =
                0;
        }


        if(
            $cell > 47
        ){

            $cell =
                47;
        }


        $newClass =
            $entry['class'];


        $oldClass =
            $timeline[$cell];


        if(
            isset(
                $severity[$newClass]
            ) &&
            $severity[$newClass] >
            $severity[$oldClass]
        ){

            $timeline[$cell] =
                $newClass;
        }
    }


    $results[] =
        array(
            'region' =>
                $regionName,

            'current_class' =>
                $latestClass,

            'current_status' =>
                $latestRaw,

            'current_since' =>
                $currentSince,

            'last_outage' =>
                $lastOutage,

            'uptime_percent' =>
                $uptime === null
                    ? null
                    : round(
                        $uptime,
                        2
                    ),

            'availability_percent' =>
                $availability === null
                    ? null
                    : round(
                        $availability,
                        2
                    ),

            'online_samples' =>
                $online,

            'warning_samples' =>
                $warning,

            'offline_samples' =>
                $offline,

            'unknown_samples' =>
                $unknown,

            'known_samples' =>
                $known,

            'timeline' =>
                $timeline
        );
}


/*
 * OFFLINE first, then warning, then lowest uptime.
 */

usort(
    $results,
    function(
        $a,
        $b
    ){

        $rank =
            array(
                'offline' => 0,
                'warning' => 1,
                'unknown' => 2,
                'online' => 3
            );


        $ra =
            isset(
                $rank[
                    $a['current_class']
                ]
            )
                ? $rank[
                    $a['current_class']
                ]
                : 2;


        $rb =
            isset(
                $rank[
                    $b['current_class']
                ]
            )
                ? $rank[
                    $b['current_class']
                ]
                : 2;


        if(
            $ra !== $rb
        ){

            return
                $ra <=>
                $rb;
        }


        $ua =
            $a['uptime_percent'] === null
                ? 101
                : $a['uptime_percent'];


        $ub =
            $b['uptime_percent'] === null
                ? 101
                : $b['uptime_percent'];


        if(
            $ua != $ub
        ){

            return
                $ua <
                $ub
                    ? -1
                    : 1;
        }


        return
            strcasecmp(
                $a['region'],
                $b['region']
            );
    }
);


uptimeReply(
    array(
        'ok' => true,
        'window' => $window,
        'regions' => $results,
        'updated' => $now
    )
);
