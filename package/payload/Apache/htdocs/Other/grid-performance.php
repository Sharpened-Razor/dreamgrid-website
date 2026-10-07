<?php

/*
 ============================================================
 Grid - REGION PERFORMANCE API V2
 ============================================================
*/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

define(
    'AUSTRALIA_PERFORMANCE_TOKEN',
    '829b04b888f948b19dd27b6073c1feecb1bd48cf672448876c630ba3f1f85bbd'
);

define(
    'AUSTRALIA_PERFORMANCE_RETENTION',
    2592000
);


function perfReply($data, $status = 200)
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
}


function perfEmpty()
{
    return array(
        'version' => 2,
        'created' => time(),
        'buckets' => array()
    );
}


function perfLoad($file)
{
    if(!is_file($file))
    {
        return perfEmpty();
    }

    $raw = @file_get_contents($file);

    if($raw === false || trim($raw) === '')
    {
        return perfEmpty();
    }

    $data = json_decode($raw, true);

    if(
        !is_array($data) ||
        !isset($data['buckets']) ||
        !is_array($data['buckets'])
    )
    {
        return perfEmpty();
    }

    return $data;
}


function perfSave($file, $data)
{
    $json = json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    if($json === false)
    {
        return false;
    }

    return @file_put_contents(
        $file,
        $json,
        LOCK_EX
    ) !== false;
}


/* ============================================================
   SECURITY
   ============================================================ */

$token =
    isset($_GET['token'])
        ? (string)$_GET['token']
        : '';

if(
    $token === '' ||
    !hash_equals(
        AUSTRALIA_PERFORMANCE_TOKEN,
        $token
    )
)
{
    perfReply(
        array(
            'ok' => false,
            'error' => 'Forbidden'
        ),
        403
    );

    return;
}


/* ============================================================
   FILES
   ============================================================ */

$jobsDir =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'jobs';

$performanceFile =
    $jobsDir .
    DIRECTORY_SEPARATOR .
    'grid-performance-history.json';

$trafficFile =
    $jobsDir .
    DIRECTORY_SEPARATOR .
    'grid-traffic-history.json';

if(!is_dir($jobsDir))
{
    @mkdir(
        $jobsDir,
        0775,
        true
    );
}


/* ============================================================
   SAVE SAMPLE
   ============================================================ */

$method =
    isset($_SERVER['REQUEST_METHOD'])
        ? strtoupper(
            (string)$_SERVER['REQUEST_METHOD']
        )
        : 'GET';

if($method === 'POST')
{
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
        !is_array($request) ||
        !isset($request['regions']) ||
        !is_array($request['regions'])
    )
    {
        perfReply(
            array(
                'ok' => false,
                'error' => 'Invalid performance sample'
            ),
            400
        );

        return;
    }

    $now = time();

    $bucketStart =
        (int)(
            floor(
                $now / 300
            ) * 300
        );

    $history =
        perfLoad(
            $performanceFile
        );

    $index = -1;

    foreach(
        $history['buckets']
        as $i => $bucket
    )
    {
        if(
            isset($bucket['start']) &&
            (int)$bucket['start'] ===
            $bucketStart
        )
        {
            $index = $i;
            break;
        }
    }

    if($index < 0)
    {
        $history['buckets'][] =
            array(
                'start' => $bucketStart,
                'regions' => array()
            );

        $index =
            count(
                $history['buckets']
            ) - 1;
    }

    if(
        !isset(
            $history['buckets'][$index]['regions']
        ) ||
        !is_array(
            $history['buckets'][$index]['regions']
        )
    )
    {
        $history['buckets'][$index]['regions'] =
            array();
    }

    $recorded = 0;

    foreach(
        $request['regions']
        as $region
    )
    {
        if(!is_array($region))
        {
            continue;
        }

        $name =
            isset($region['name'])
                ? trim(
                    (string)$region['name']
                )
                : '';

        if(
            $name === '' ||
            !isset($region['prims']) ||
            !is_numeric($region['prims'])
        )
        {
            continue;
        }

        $prims =
            (int)$region['prims'];

        if($prims < 0)
        {
            continue;
        }

        $history['buckets'][$index]['regions'][$name] =
            array(
                'prims' => $prims
            );

        $recorded++;
    }

    $cutoff =
        $now -
        AUSTRALIA_PERFORMANCE_RETENTION;

    $newBuckets =
        array();

    foreach(
        $history['buckets']
        as $bucket
    )
    {
        if(
            isset($bucket['start']) &&
            (int)$bucket['start'] >=
            $cutoff
        )
        {
            $newBuckets[] =
                $bucket;
        }
    }

    $history['buckets'] =
        $newBuckets;

    $history['updated'] =
        $now;

    if(
        !perfSave(
            $performanceFile,
            $history
        )
    )
    {
        perfReply(
            array(
                'ok' => false,
                'error' => 'Could not save performance history'
            ),
            500
        );

        return;
    }

    perfReply(
        array(
            'ok' => true,
            'recorded' => $recorded,
            'time' => $now
        )
    );

    return;
}


/* ============================================================
   REPORTING WINDOW
   ============================================================ */

$windows =
    array(
        3600,
        86400,
        604800,
        2592000
    );

$window =
    isset($_GET['window'])
        ? (int)$_GET['window']
        : 86400;

if(
    !in_array(
        $window,
        $windows,
        true
    )
)
{
    $window = 86400;
}

$now = time();
$cutoff = $now - $window;


/* ============================================================
   PRIM HISTORY
   ============================================================ */

$history =
    perfLoad(
        $performanceFile
    );

$regionSamples =
    array();

foreach(
    $history['buckets']
    as $bucket
)
{
    $start =
        isset($bucket['start'])
            ? (int)$bucket['start']
            : 0;

    if($start < $cutoff)
    {
        continue;
    }

    if(
        !isset($bucket['regions']) ||
        !is_array($bucket['regions'])
    )
    {
        continue;
    }

    foreach(
        $bucket['regions']
        as $regionName => $entry
    )
    {
        if(
            !isset($entry['prims']) ||
            !is_numeric($entry['prims'])
        )
        {
            continue;
        }

        if(
            !isset(
                $regionSamples[$regionName]
            )
        )
        {
            $regionSamples[$regionName] =
                array();
        }

        $regionSamples[$regionName][] =
            array(
                'time' => $start,
                'prims' => (int)$entry['prims']
            );
    }
}


/* ============================================================
   TRAFFIC HISTORY
   ============================================================ */

$traffic =
    perfLoad(
        $trafficFile
    );

$totalTrafficSamples = 0;
$trafficRegions = array();

foreach(
    $traffic['buckets']
    as $bucket
)
{
    $start =
        isset($bucket['start'])
            ? (int)$bucket['start']
            : 0;

    if($start < $cutoff)
    {
        continue;
    }

    $samples =
        isset($bucket['samples'])
            ? max(
                0,
                (int)$bucket['samples']
            )
            : 0;

    $totalTrafficSamples +=
        $samples;

    if(
        !isset($bucket['regions']) ||
        !is_array($bucket['regions'])
    )
    {
        continue;
    }

    foreach(
        $bucket['regions']
        as $regionName => $entry
    )
    {
        if(
            !isset(
                $trafficRegions[$regionName]
            )
        )
        {
            $trafficRegions[$regionName] =
                array(
                    'avatar_samples' => 0,
                    'active_samples' => 0,
                    'max_online' => 0
                );
        }

        $trafficRegions[$regionName]['avatar_samples'] +=
            isset($entry['avatar_samples'])
                ? (int)$entry['avatar_samples']
                : 0;

        $trafficRegions[$regionName]['active_samples'] +=
            isset($entry['active_samples'])
                ? (int)$entry['active_samples']
                : 0;

        $trafficRegions[$regionName]['max_online'] =
            max(
                $trafficRegions[$regionName]['max_online'],
                isset($entry['max_online'])
                    ? (int)$entry['max_online']
                    : 0
            );
    }
}


/* ============================================================
   CALCULATE REGION PERFORMANCE
   ============================================================ */

$result = array();

foreach(
    $regionSamples
    as $regionName => $samples
)
{
    usort(
        $samples,
        function($a, $b)
        {
            return
                $a['time'] <=>
                $b['time'];
        }
    );

    $count =
        count(
            $samples
        );

    if($count === 0)
    {
        continue;
    }

    $first =
        $samples[0]['prims'];

    $current =
        $samples[
            $count - 1
        ]['prims'];

    $minimum = $current;
    $maximum = $current;
    $largestStep = 0;
    $previous = null;

    foreach(
        $samples
        as $sample
    )
    {
        $value =
            $sample['prims'];

        $minimum =
            min(
                $minimum,
                $value
            );

        $maximum =
            max(
                $maximum,
                $value
            );

        if($previous !== null)
        {
            $largestStep =
                max(
                    $largestStep,
                    abs(
                        $value -
                        $previous
                    )
                );
        }

        $previous =
            $value;
    }

    $change =
        $count >= 2
            ? $current - $first
            : 0;

    $changePercent =
        (
            $count >= 2 &&
            $first > 0
        )
            ? (
                $change /
                $first *
                100
            )
            : 0;

    $regionTraffic =
        isset(
            $trafficRegions[$regionName]
        )
            ? $trafficRegions[$regionName]
            : array(
                'avatar_samples' => 0,
                'active_samples' => 0,
                'max_online' => 0
            );

    $avgOnline =
        $totalTrafficSamples > 0
            ? (
                $regionTraffic['avatar_samples'] /
                $totalTrafficSamples
            )
            : 0;

    $activePercent =
        $totalTrafficSamples > 0
            ? (
                $regionTraffic['active_samples'] /
                $totalTrafficSamples *
                100
            )
            : 0;

    $activity = 'LOW';

    if(
        $avgOnline >= 2 ||
        $regionTraffic['max_online'] >= 5
    )
    {
        $activity = 'HIGH';
    }
    elseif(
        $avgOnline >= 0.5 ||
        $regionTraffic['max_online'] >= 2
    )
    {
        $activity = 'MEDIUM';
    }

    $largeChange = false;

    if($count >= 2)
    {
        if(abs($change) >= 5000)
        {
            $largeChange = true;
        }
        elseif(
            abs($changePercent) >= 20 &&
            abs($change) >= 100
        )
        {
            $largeChange = true;
        }
    }

    $result[] =
        array(
            'region' => $regionName,
            'current_prims' => $current,
            'first_prims' => $first,
            'change' => $change,
            'change_percent' => round(
                $changePercent,
                1
            ),
            'minimum_prims' => $minimum,
            'maximum_prims' => $maximum,
            'largest_step' => $largestStep,
            'sample_count' => $count,
            'avg_online' => round(
                $avgOnline,
                2
            ),
            'peak_online' =>
                (int)$regionTraffic['max_online'],
            'active_percent' => round(
                $activePercent,
                1
            ),
            'activity' => $activity,
            'large_change' => $largeChange
        );
}


/*
 * Highest prim count first.
 */

usort(
    $result,
    function($a, $b)
    {
        return
            $b['current_prims'] <=>
            $a['current_prims'];
    }
);


$highest =
    count($result) > 0
        ? $result[0]
        : null;


$biggestChange = null;

foreach(
    $result
    as $region
)
{
    if(
        $region['sample_count'] < 2
    )
    {
        continue;
    }

    if(
        $biggestChange === null ||
        abs($region['change']) >
        abs($biggestChange['change'])
    )
    {
        $biggestChange =
            $region;
    }
}


perfReply(
    array(
        'ok' => true,
        'window' => $window,
        'highest' => $highest,
        'biggest_change' => $biggestChange,
        'regions' => $result,
        'updated' => $now
    )
);
