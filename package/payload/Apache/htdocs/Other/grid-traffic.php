<?php

/*
 ============================================================
 Grid - REGION TRAFFIC API V1

 READ / WRITE TRAFFIC STATISTICS ONLY.

 No RemoteAdmin requests are made here.

 The browser sends the SAME live avatar data that has already
 been retrieved by the existing Grid Map live-agent system.

 Historical avatar identities are hashed.
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
    'AUSTRALIA_TRAFFIC_TOKEN',
    'ea923d827cf9a1d00fd15eb7c38e93751148da69ac744d27f61b99b430f7ac70'
);


define(
    'AUSTRALIA_TRAFFIC_RETENTION',
    2592000
);


/* ============================================================
   JSON RESPONSE
   ============================================================ */

function trafficReply(
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
        AUSTRALIA_TRAFFIC_TOKEN,
        $requestToken
    )
){

    trafficReply(
        array(
            'ok' => false,
            'error' => 'Forbidden'
        ),
        403
    );

    return;
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
    'grid-traffic-history.json';


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


function trafficEmptyHistory()
{
    return array(
        'version' => 1,
        'created' => time(),
        'buckets' => array()
    );
}


function trafficLoadHistory(
    $file
)
{
    if(
        !is_file(
            $file
        )
    ){

        return trafficEmptyHistory();
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

        return trafficEmptyHistory();
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

        return trafficEmptyHistory();
    }


    return $data;
}


function trafficSaveHistory(
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
   POST SAMPLE
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
            $request['agents']
        ) ||
        !is_array(
            $request['agents']
        )
    ){

        trafficReply(
            array(
                'ok' => false,
                'error' => 'Invalid traffic sample'
            ),
            400
        );

        return;
    }


    $now =
        time();


    /*
     * Five-minute buckets keep the history compact while
     * individual browser samples arrive roughly once/minute.
     */

    $bucketStart =
        (int)(
            floor(
                $now / 300
            ) * 300
        );


    $history =
        trafficLoadHistory(
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
                'samples' => 0,
                'regions' => array()
            );


        $bucketIndex =
            count(
                $history['buckets']
            ) - 1;
    }


    if(
        !isset(
            $history['buckets'][$bucketIndex]['samples']
        )
    ){

        $history['buckets'][$bucketIndex]['samples'] =
            0;
    }


    $history['buckets'][$bucketIndex]['samples']++;


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


    $regionCounts =
        array();


    $regionHashes =
        array();


    foreach(
        $request['agents']
        as $agent
    ){

        if(
            !is_array(
                $agent
            )
        ){

            continue;
        }


        $region =
            isset(
                $agent['region']
            )
                ? trim(
                    (string)$agent['region']
                )
                : '';


        if(
            $region === ''
        ){

            continue;
        }


        if(
            !isset(
                $regionCounts[$region]
            )
        ){

            $regionCounts[$region] =
                0;
        }


        $regionCounts[$region]++;


        $identity =
            isset(
                $agent['id']
            )
                ? trim(
                    (string)$agent['id']
                )
                : '';


        if(
            $identity === ''
        ){

            $identity =
                isset(
                    $agent['name']
                )
                    ? strtolower(
                        trim(
                            (string)$agent['name']
                        )
                    )
                    : '';
        }


        if(
            $identity !== ''
        ){

            if(
                !isset(
                    $regionHashes[$region]
                )
            ){

                $regionHashes[$region] =
                    array();
            }


            $hash =
                hash(
                    'sha256',
                    strtolower(
                        $identity
                    )
                );


            $regionHashes[$region][$hash] =
                true;
        }
    }


    foreach(
        $regionCounts
        as $region => $count
    ){

        if(
            !isset(
                $history['buckets'][$bucketIndex]['regions'][$region]
            )
        ){

            $history['buckets'][$bucketIndex]['regions'][$region] =
                array(
                    'avatar_samples' => 0,
                    'active_samples' => 0,
                    'max_online' => 0,
                    'avatars' => array()
                );
        }


        $entry =
            &$history['buckets'][$bucketIndex]['regions'][$region];


        $entry['avatar_samples'] =
            isset(
                $entry['avatar_samples']
            )
                ? (int)$entry['avatar_samples'] + $count
                : $count;


        $entry['active_samples'] =
            isset(
                $entry['active_samples']
            )
                ? (int)$entry['active_samples'] + 1
                : 1;


        $oldMax =
            isset(
                $entry['max_online']
            )
                ? (int)$entry['max_online']
                : 0;


        $entry['max_online'] =
            max(
                $oldMax,
                $count
            );


        $known =
            array();


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

                $known[
                    (string)$hash
                ] =
                    true;
            }
        }


        if(
            isset(
                $regionHashes[$region]
            )
        ){

            foreach(
                $regionHashes[$region]
                as $hash => $unused
            ){

                $known[$hash] =
                    true;
            }
        }


        $entry['avatars'] =
            array_keys(
                $known
            );


        unset(
            $entry
        );
    }


    /*
     * Keep only the newest 30 days.
     */

    $cutoff =
        $now -
        AUSTRALIA_TRAFFIC_RETENTION;


    $pruned =
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

            $pruned[] =
                $bucket;
        }
    }


    $history['buckets'] =
        $pruned;


    $history['updated'] =
        $now;


    if(
        !trafficSaveHistory(
            $historyFile,
            $history
        )
    ){

        trafficReply(
            array(
                'ok' => false,
                'error' => 'Could not save traffic history'
            ),
            500
        );

        return;
    }


    trafficReply(
        array(
            'ok' => true,
            'recorded' => true,
            'time' => $now
        )
    );

    return;
}


/* ============================================================
   GET SUMMARY
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


$history =
    trafficLoadHistory(
        $historyFile
    );


$now =
    time();


$cutoff =
    $now -
    $window;


$totalSamples =
    0;


$regions =
    array();


$firstSample =
    null;


foreach(
    $history['buckets']
    as $bucket
){

    if(
        !isset(
            $bucket['start']
        ) ||
        (int)$bucket['start'] <
        $cutoff
    ){

        continue;
    }


    $start =
        (int)$bucket['start'];


    if(
        $firstSample === null ||
        $start < $firstSample
    ){

        $firstSample =
            $start;
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


    $totalSamples +=
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
                $regions[$regionName]
            )
        ){

            $regions[$regionName] =
                array(
                    'region' => $regionName,
                    'avatar_samples' => 0,
                    'active_samples' => 0,
                    'max_online' => 0,
                    'avatars' => array()
                );
        }


        $regions[$regionName]['avatar_samples'] +=
            isset(
                $entry['avatar_samples']
            )
                ? (int)$entry['avatar_samples']
                : 0;


        $regions[$regionName]['active_samples'] +=
            isset(
                $entry['active_samples']
            )
                ? (int)$entry['active_samples']
                : 0;


        $regions[$regionName]['max_online'] =
            max(
                $regions[$regionName]['max_online'],
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

                $regions[$regionName]['avatars'][
                    (string)$hash
                ] =
                    true;
            }
        }
    }
}


$result =
    array();


foreach(
    $regions
    as $region
){

    $average =
        $totalSamples > 0
            ? (
                $region['avatar_samples'] /
                $totalSamples
            )
            : 0;


    $activePercent =
        $totalSamples > 0
            ? (
                $region['active_samples'] /
                $totalSamples *
                100
            )
            : 0;


    $result[] =
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
                ),

            'avatar_samples' =>
                (int)$region['avatar_samples']
        );
}


usort(
    $result,
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
            $b['avg_online'] <
            $a['avg_online']
                ? -1
                : 1;
    }
);


trafficReply(
    array(
        'ok' => true,
        'window' => $window,
        'samples' => $totalSamples,
        'first_sample' => $firstSample,
        'regions' => $result,
        'updated' => $now
    )
);
