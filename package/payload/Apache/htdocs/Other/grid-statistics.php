<?php

/*
 ============================================================
 Grid - STATISTICS DASHBOARD API V1

 READ ONLY.

 Reads existing:
 - jobs/grid-traffic-history.json
 - jobs/grid-uptime-history.json

 NO RemoteAdmin calls.
 NO OpenSim calls.
 NO regions.php calls.
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
    'AUSTRALIA_STATS_TOKEN',
    '4e6c8e72233ca143c3df3815c681f7daa07d2165d4c1fee5ccd187b5fa5d440b'
);


function statsReply(
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


function statsLoadHistory(
    $file
)
{
    if(
        !is_file(
            $file
        )
    ){

        return array(
            'buckets' => array()
        );
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

        return array(
            'buckets' => array()
        );
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

        return array(
            'buckets' => array()
        );
    }


    return $data;
}


/* ============================================================
   TOKEN
   ============================================================ */

$requestToken =
    isset(
        $_GET['token']
    )
        ? (string)$_GET['token']
        : '';


if(
    $requestToken === '' ||
    !hash_equals(
        AUSTRALIA_STATS_TOKEN,
        $requestToken
    )
){

    statsReply(
        array(
            'ok' => false,
            'error' => 'Forbidden'
        ),
        403
    );

    return;
}


/* ============================================================
   WINDOW
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


/* ============================================================
   FILES
   ============================================================ */

$trafficFile =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs' .
    DIRECTORY_SEPARATOR .
    'grid-traffic-history.json';


$uptimeFile =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs' .
    DIRECTORY_SEPARATOR .
    'grid-uptime-history.json';


$trafficHistory =
    statsLoadHistory(
        $trafficFile
    );


$uptimeHistory =
    statsLoadHistory(
        $uptimeFile
    );


/* ============================================================
   TRAFFIC
   ============================================================ */

$totalTrafficSamples =
    0;


$trafficRegions =
    array();


$allUnique =
    array();


foreach(
    $trafficHistory['buckets']
    as $bucket
){

    $start =
        isset(
            $bucket['start']
        )
            ? (int)$bucket['start']
            : 0;


    if(
        $start <
        $cutoff
    ){

        continue;
    }


    $samples =
        isset(
            $bucket['samples']
        )
            ? max(
                0,
                (int)$bucket['samples']
            )
            : 0;


    $totalTrafficSamples +=
        $samples;


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
        as $regionName => $entry
    ){

        if(
            !isset(
                $trafficRegions[$regionName]
            )
        ){

            $trafficRegions[$regionName] =
                array(
                    'region' =>
                        $regionName,

                    'avatar_samples' =>
                        0,

                    'active_samples' =>
                        0,

                    'max_online' =>
                        0,

                    'avatars' =>
                        array()
                );
        }


        $trafficRegions[$regionName]['avatar_samples'] +=
            isset(
                $entry['avatar_samples']
            )
                ? (int)$entry['avatar_samples']
                : 0;


        $trafficRegions[$regionName]['active_samples'] +=
            isset(
                $entry['active_samples']
            )
                ? (int)$entry['active_samples']
                : 0;


        $trafficRegions[$regionName]['max_online'] =
            max(
                $trafficRegions[$regionName]['max_online'],
                isset(
                    $entry['max_online']
                )
                    ? (int)$entry['max_online']
                    : 0
            );


        if(
            isset(
                $entry['avatars']
            ) &&
            is_array(
                $entry['avatars']
            )
        ){

            foreach(
                $entry['avatars']
                as $hash
            ){

                $hash =
                    (string)$hash;


                $trafficRegions[$regionName]['avatars'][$hash] =
                    true;


                $allUnique[$hash] =
                    true;
            }
        }
    }
}


$trafficResult =
    array();


foreach(
    $trafficRegions
    as $region
){

    $average =
        $totalTrafficSamples > 0
            ? (
                $region['avatar_samples'] /
                $totalTrafficSamples
            )
            : 0;


    $activePercent =
        $totalTrafficSamples > 0
            ? (
                $region['active_samples'] /
                $totalTrafficSamples *
                100
            )
            : 0;


    $trafficResult[] =
        array(
            'region' =>
                $region['region'],

            'avg_online' =>
                round(
                    $average,
                    3
                ),

            'max_online' =>
                (int)$region['max_online'],

            'unique_count' =>
                count(
                    $region['avatars']
                ),

            'active_percent' =>
                round(
                    $activePercent,
                    1
                )
        );
}


usort(
    $trafficResult,
    function(
        $a,
        $b
    ){

        if(
            $a['avg_online'] ==
            $b['avg_online']
        ){

            return
                $b['unique_count'] <=>
                $a['unique_count'];
        }


        return
            $a['avg_online'] <
            $b['avg_online']
                ? 1
                : -1;
    }
);


$busiestRegion =
    count(
        $trafficResult
    ) > 0
        ? $trafficResult[0]
        : null;


/* ============================================================
   UPTIME
   ============================================================ */

$uptimeBuckets =
    $uptimeHistory['buckets'];


usort(
    $uptimeBuckets,
    function(
        $a,
        $b
    ){

        return
            (int)(
                isset(
                    $a['start']
                )
                    ? $a['start']
                    : 0
            )
            <=>
            (int)(
                isset(
                    $b['start']
                )
                    ? $b['start']
                    : 0
            );
    }
);


$currentRegions =
    array();


$uptimeRegions =
    array();


foreach(
    $uptimeBuckets
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
        as $regionName => $entry
    ){

        $class =
            isset(
                $entry['class']
            )
                ? strtolower(
                    trim(
                        (string)$entry['class']
                    )
                )
                : 'unknown';


        if(
            !in_array(
                $class,
                array(
                    'online',
                    'warning',
                    'offline',
                    'unknown'
                ),
                true
            )
        ){

            $class =
                'unknown';
        }


        $currentRegions[$regionName] =
            array(
                'region' =>
                    $regionName,

                'class' =>
                    $class,

                'status' =>
                    isset(
                        $entry['status']
                    )
                        ? (string)$entry['status']
                        : '',

                'time' =>
                    $start
            );


        if(
            $start <
            $cutoff
        ){

            continue;
        }


        if(
            !isset(
                $uptimeRegions[$regionName]
            )
        ){

            $uptimeRegions[$regionName] =
                array(
                    'region' =>
                        $regionName,

                    'online' =>
                        0,

                    'warning' =>
                        0,

                    'offline' =>
                        0,

                    'unknown' =>
                        0
                );
        }


        $uptimeRegions[$regionName][$class]++;
    }
}


$uptimeResult =
    array();


$totalKnown =
    0;


$totalOnline =
    0;


foreach(
    $currentRegions
    as $regionName => $current
){

    if(
        !isset(
            $uptimeRegions[$regionName]
        )
    ){

        $uptimeRegions[$regionName] =
            array(
                'region' =>
                    $regionName,

                'online' =>
                    0,

                'warning' =>
                    0,

                'offline' =>
                    0,

                'unknown' =>
                    0
            );
    }


    $entry =
        $uptimeRegions[$regionName];


    $known =
        $entry['online'] +
        $entry['warning'] +
        $entry['offline'];


    $percent =
        $known > 0
            ? (
                $entry['online'] /
                $known *
                100
            )
            : null;


    $totalKnown +=
        $known;


    $totalOnline +=
        $entry['online'];


    $uptimeResult[] =
        array(
            'region' =>
                $regionName,

            'current_class' =>
                $current['class'],

            'current_status' =>
                $current['status'],

            'uptime_percent' =>
                $percent === null
                    ? null
                    : round(
                        $percent,
                        2
                    ),

            'online_samples' =>
                $entry['online'],

            'warning_samples' =>
                $entry['warning'],

            'offline_samples' =>
                $entry['offline'],

            'unknown_samples' =>
                $entry['unknown']
        );
}


$gridUptime =
    $totalKnown > 0
        ? (
            $totalOnline /
            $totalKnown *
            100
        )
        : null;


$regionsOnline =
    0;


$regionsWarning =
    0;


$regionsOffline =
    0;


foreach(
    $currentRegions
    as $current
){

    if(
        $current['class'] ===
        'online'
    ){

        $regionsOnline++;
    }
    elseif(
        $current['class'] ===
        'warning'
    ){

        $regionsWarning++;
    }
    elseif(
        $current['class'] ===
        'offline'
    ){

        $regionsOffline++;
    }
}


$rankedUptime =
    array();


foreach(
    $uptimeResult
    as $region
){

    if(
        $region['uptime_percent'] !==
        null
    ){

        $rankedUptime[] =
            $region;
    }
}


usort(
    $rankedUptime,
    function(
        $a,
        $b
    ){

        if(
            $a['uptime_percent'] ==
            $b['uptime_percent']
        ){

            return
                strcasecmp(
                    $a['region'],
                    $b['region']
                );
        }


        return
            $a['uptime_percent'] <
            $b['uptime_percent']
                ? 1
                : -1;
    }
);


$bestUptime =
    count(
        $rankedUptime
    ) > 0
        ? $rankedUptime[0]
        : null;


$worstUptime =
    count(
        $rankedUptime
    ) > 0
        ? $rankedUptime[
            count(
                $rankedUptime
            ) - 1
          ]
        : null;


/* ============================================================
   INCIDENT HISTORY
   ============================================================ */

$eventsByRegion =
    array();


foreach(
    $uptimeBuckets
    as $bucket
){

    if(
        !isset(
            $bucket['start']
        ) ||
        !isset(
            $bucket['regions']
        ) ||
        !is_array(
            $bucket['regions']
        )
    ){

        continue;
    }


    $start =
        (int)$bucket['start'];


    foreach(
        $bucket['regions']
        as $regionName => $entry
    ){

        if(
            !isset(
                $eventsByRegion[$regionName]
            )
        ){

            $eventsByRegion[$regionName] =
                array();
        }


        $class =
            isset(
                $entry['class']
            )
                ? strtolower(
                    trim(
                        (string)$entry['class']
                    )
                )
                : 'unknown';


        $eventsByRegion[$regionName][] =
            array(
                'time' =>
                    $start,

                'class' =>
                    $class,

                'status' =>
                    isset(
                        $entry['status']
                    )
                        ? (string)$entry['status']
                        : ''
            );
    }
}


$incidents =
    array();


foreach(
    $eventsByRegion
    as $regionName => $events
){

    $incident =
        null;


    foreach(
        $events
        as $event
    ){

        $class =
            $event['class'];


        if(
            $class === 'warning' ||
            $class === 'offline'
        ){

            if(
                $incident ===
                null
            ){

                $incident =
                    array(
                        'region' =>
                            $regionName,

                        'severity' =>
                            $class,

                        'started' =>
                            $event['time'],

                        'ended' =>
                            null,

                        'recovered' =>
                            false
                    );
            }
            elseif(
                $class ===
                'offline'
            ){

                $incident['severity'] =
                    'offline';
            }


            continue;
        }


        if(
            $class === 'online' &&
            $incident !==
            null
        ){

            $incident['ended'] =
                $event['time'];


            $incident['recovered'] =
                true;


            $incident['duration'] =
                max(
                    0,
                    $incident['ended'] -
                    $incident['started']
                );


            if(
                $incident['ended'] >=
                $cutoff ||
                $incident['started'] >=
                $cutoff
            ){

                $incidents[] =
                    $incident;
            }


            $incident =
                null;
        }
    }


    if(
        $incident !==
        null
    ){

        $incident['duration'] =
            max(
                0,
                $now -
                $incident['started']
            );


        if(
            $incident['started'] >=
            $cutoff ||
            isset(
                $currentRegions[$regionName]
            ) &&
            (
                $currentRegions[$regionName]['class'] ===
                'warning' ||
                $currentRegions[$regionName]['class'] ===
                'offline'
            )
        ){

            $incidents[] =
                $incident;
        }
    }
}


usort(
    $incidents,
    function(
        $a,
        $b
    ){

        if(
            $a['recovered'] !==
            $b['recovered']
        ){

            return
                $a['recovered']
                    ? 1
                    : -1;
        }


        return
            $b['started'] <=>
            $a['started'];
    }
);


/* ============================================================
   RESPONSE
   ============================================================ */

statsReply(
    array(
        'ok' => true,

        'window' =>
            $window,

        'traffic' =>
            array(
                'samples' =>
                    $totalTrafficSamples,

                'unique_count' =>
                    count(
                        $allUnique
                    ),

                'busiest' =>
                    $busiestRegion,

                'regions' =>
                    $trafficResult
            ),

        'uptime' =>
            array(
                'regions_total' =>
                    count(
                        $currentRegions
                    ),

                'regions_online' =>
                    $regionsOnline,

                'regions_warning' =>
                    $regionsWarning,

                'regions_offline' =>
                    $regionsOffline,

                'grid_uptime_percent' =>
                    $gridUptime === null
                        ? null
                        : round(
                            $gridUptime,
                            2
                        ),

                'best' =>
                    $bestUptime,

                'worst' =>
                    $worstUptime,

                'regions' =>
                    $uptimeResult
            ),

        'incidents' =>
            array(
                'active_count' =>
                    $regionsWarning +
                    $regionsOffline,

                'count' =>
                    count(
                        $incidents
                    ),

                'recent' =>
                    array_slice(
                        $incidents,
                        0,
                        30
                    )
            ),

        'updated' =>
            $now
    )
);
