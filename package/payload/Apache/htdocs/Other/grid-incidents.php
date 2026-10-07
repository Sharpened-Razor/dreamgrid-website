<?php

/*
 ============================================================
 Grid - ALERTS & INCIDENTS API V1

 READ ONLY.

 Reads:
 jobs/grid-uptime-history.json

 Does NOT contact:
 - RemoteAdmin
 - OpenSim
 - Robust
 - regions.php
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
    'AUSTRALIA_INCIDENT_TOKEN',
    '9a303d9cd29f90c12eb91a1879f76ce1ec4e000a82d0170844b5cd662841877f'
);


function incidentReply(
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
        AUSTRALIA_INCIDENT_TOKEN,
        $requestToken
    )
){

    incidentReply(
        array(
            'ok' => false,
            'error' => 'Forbidden'
        ),
        403
    );

    return;
}


/* ============================================================
   HISTORY
   ============================================================ */

$historyFile =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs' .
    DIRECTORY_SEPARATOR .
    'grid-uptime-history.json';


if(
    !is_file(
        $historyFile
    )
){

    incidentReply(
        array(
            'ok' => true,
            'active' => array(),
            'incidents' => array(),
            'regions' => array(),
            'updated' => time(),
            'message' => 'No uptime history yet'
        )
    );

    return;
}


$raw =
    @file_get_contents(
        $historyFile
    );


$history =
    json_decode(
        $raw,
        true
    );


if(
    !is_array(
        $history
    ) ||
    !isset(
        $history['buckets']
    ) ||
    !is_array(
        $history['buckets']
    )
){

    incidentReply(
        array(
            'ok' => false,
            'error' => 'Invalid uptime history'
        ),
        500
    );

    return;
}


$buckets =
    $history['buckets'];


usort(
    $buckets,
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
   DISCOVER REGIONS
   ============================================================ */

$regionNames =
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
        as $regionName => $entry
    ){

        $regionNames[
            $regionName
        ] =
            true;
    }
}


/* ============================================================
   BUILD CURRENT STATUS + INCIDENTS
   ============================================================ */

$active =
    array();


$incidents =
    array();


$regionSummaries =
    array();


foreach(
    array_keys(
        $regionNames
    )
    as $regionName
){

    $events =
        array();


    foreach(
        $buckets
        as $bucket
    ){

        if(
            !isset(
                $bucket['start']
            ) ||
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


        $events[] =
            array(
                'time' =>
                    (int)$bucket['start'],

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


    if(
        count(
            $events
        ) === 0
    ){

        continue;
    }


    $latest =
        $events[
            count(
                $events
            ) - 1
        ];


    /*
     * Find when current state started.
     */

    $currentSince =
        $latest['time'];


    for(
        $i =
            count(
                $events
            ) - 1;

        $i >= 0;

        $i--
    ){

        if(
            $events[$i]['class'] !==
            $latest['class']
        ){

            break;
        }


        $currentSince =
            $events[$i]['time'];
    }


    $regionSummaries[] =
        array(
            'region' =>
                $regionName,

            'current_class' =>
                $latest['class'],

            'current_status' =>
                $latest['status'],

            'current_since' =>
                $currentSince
        );


    /*
     * Current active alert.
     */

    if(
        $latest['class'] === 'warning' ||
        $latest['class'] === 'offline'
    ){

        $active[] =
            array(
                'region' =>
                    $regionName,

                'severity' =>
                    $latest['class'],

                'status' =>
                    $latest['status'],

                'started' =>
                    $currentSince,

                'duration' =>
                    max(
                        0,
                        $now -
                        $currentSince
                    )
            );
    }


    /*
     * Build historical incidents.

     * WARNING and OFFLINE belong to the same incident until
     * the region returns ONLINE.
     *
     * If WARNING escalates to OFFLINE, incident severity is
     * upgraded to OFFLINE.
     */

    $currentIncident =
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
                $currentIncident ===
                null
            ){

                $currentIncident =
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
                            false,

                        'last_status' =>
                            $event['status']
                    );
            }
            else{

                if(
                    $class === 'offline'
                ){

                    $currentIncident['severity'] =
                        'offline';
                }


                $currentIncident['last_status'] =
                    $event['status'];
            }


            continue;
        }


        if(
            $class === 'online' &&
            $currentIncident !==
            null
        ){

            $currentIncident['ended'] =
                $event['time'];


            $currentIncident['recovered'] =
                true;


            $currentIncident['duration'] =
                max(
                    0,
                    $event['time'] -
                    $currentIncident['started']
                );


            if(
                $currentIncident['ended'] >=
                $cutoff ||
                $currentIncident['started'] >=
                $cutoff
            ){

                $incidents[] =
                    $currentIncident;
            }


            $currentIncident =
                null;
        }
    }


    /*
     * Still-active incident.
     */

    if(
        $currentIncident !==
        null
    ){

        $currentIncident['duration'] =
            max(
                0,
                $now -
                $currentIncident['started']
            );


        if(
            $currentIncident['started'] >=
            $cutoff ||
            $latest['class'] === 'warning' ||
            $latest['class'] === 'offline'
        ){

            $incidents[] =
                $currentIncident;
        }
    }
}


/* ============================================================
   SORT ACTIVE ALERTS
   ============================================================ */

usort(
    $active,
    function(
        $a,
        $b
    ){

        $severity =
            array(
                'offline' => 0,
                'warning' => 1
            );


        $sa =
            isset(
                $severity[
                    $a['severity']
                ]
            )
                ? $severity[
                    $a['severity']
                ]
                : 2;


        $sb =
            isset(
                $severity[
                    $b['severity']
                ]
            )
                ? $severity[
                    $b['severity']
                ]
                : 2;


        if(
            $sa !== $sb
        ){

            return
                $sa <=>
                $sb;
        }


        return
            $a['started'] <=>
            $b['started'];
    }
);


/* ============================================================
   SORT INCIDENT HISTORY
   ============================================================ */

usort(
    $incidents,
    function(
        $a,
        $b
    ){

        /*
         * Active first.
         */

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
   SUMMARY COUNTS
   ============================================================ */

$offlineCount =
    0;


$warningCount =
    0;


foreach(
    $active
    as $alert
){

    if(
        $alert['severity'] ===
        'offline'
    ){

        $offlineCount++;
    }
    elseif(
        $alert['severity'] ===
        'warning'
    ){

        $warningCount++;
    }
}


/* ============================================================
   RESPONSE
   ============================================================ */

incidentReply(
    array(
        'ok' => true,

        'window' =>
            $window,

        'active_count' =>
            count(
                $active
            ),

        'offline_count' =>
            $offlineCount,

        'warning_count' =>
            $warningCount,

        'active' =>
            $active,

        'incidents' =>
            array_slice(
                $incidents,
                0,
                50
            ),

        'regions' =>
            $regionSummaries,

        'updated' =>
            $now
    )
);
