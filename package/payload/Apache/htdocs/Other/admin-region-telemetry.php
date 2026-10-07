<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/region-inspection.php';

$session = ag_require_admin();

ag_no_cache();

header('Content-Type: application/json; charset=utf-8');


/*
 * Never allow local OpenSim statistics traffic to leave the PC.
 */

putenv('NO_PROXY='.ag_web_local_host());
putenv('no_proxy='.ag_web_local_host());


function rt_fail(string $message, int $status = 500)
{
    http_response_code($status);

    echo json_encode(
        [
            'ok' => false,
            'error' => $message,
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}


function rt_http_get(string $url): array {
    $context=stream_context_create(['http'=>['timeout'=>2,'ignore_errors'=>true,'follow_location'=>0]]);
    $body=@file_get_contents($url,false,$context,0,1048576);
    $status=0;
    foreach($http_response_header??[] as $header) if(preg_match('/^HTTP\/\S+\s+(\d{3})/',$header,$m)) $status=(int)$m[1];
    return ['status'=>$status,'body'=>is_string($body)?$body:''];
}
function rt_stats_page(string $regionName, string $regionUuid, int $regionPort): array {
    if ($regionPort<1 || $regionPort>65535) return ['ok'=>false,'url'=>'','body'=>'','attempts'=>[]];
    $base=ag_web_local_base($regionPort);
    $urls=[$base.'/jsonSimStats',$base.'/Stats?r='.rawurlencode($regionUuid),$base.'/Stats/?r='.rawurlencode($regionName),$base.'/Stats/'];
    $attempts=[];
    foreach($urls as $url){
        $attempts[]=$url; $response=rt_http_get($url);
        if($response['status']>=200 && $response['status']<300 && rt_metrics($response['body'])['MetricCount']>0) return ['ok'=>true,'url'=>$url,'body'=>$response['body'],'attempts'=>$attempts];
    }
    return ['ok'=>false,'url'=>'','body'=>'','attempts'=>$attempts];
}

function rt_key(string $value): string
{
    $value = html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );


    $value = strtolower(
        trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            )
            ?: ''
        )
    );


    return preg_replace(
        '/[^a-z0-9]+/',
        '',
        $value
    )
    ?: '';
}


function rt_clean(string $value): string
{
    $value = html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );


    $value = strip_tags($value);


    return trim(
        preg_replace(
            '/\s+/u',
            ' ',
            $value
        )
        ?: ''
    );
}


/*
 * ============================================================
 * JSON METRIC EXTRACTION
 * ============================================================
 */

function rt_json_flatten(
    $value,
    array &$result
): void {

    if (!is_array($value)) {
        return;
    }


    foreach ($value as $key => $child) {

        if (is_array($child)) {

            rt_json_flatten(
                $child,
                $result
            );

            continue;
        }


        if (
            is_string($child)
            ||
            is_numeric($child)
            ||
            is_bool($child)
        ) {

            $normal =
                rt_key(
                    (string)$key
                );


            if ($normal !== '') {

                $result[$normal] =
                    (string)$child;
            }
        }
    }
}


/*
 * ============================================================
 * HTML METRIC EXTRACTION
 * ============================================================
 */

function rt_html_pairs(string $html): array
{
    $pairs = [];


    if (class_exists('DOMDocument')) {

        $oldErrors =
            libxml_use_internal_errors(
                true
            );


        $dom =
            new DOMDocument();


        @$dom->loadHTML(
            '<?xml encoding="UTF-8">'
            .
            $html
        );


        $rows =
            $dom->getElementsByTagName(
                'tr'
            );


        foreach ($rows as $row) {

            $cells = [];


            foreach ($row->childNodes as $child) {

                if (
                    $child->nodeType
                    !==
                    XML_ELEMENT_NODE
                ) {
                    continue;
                }


                $tag =
                    strtolower(
                        (string)$child->nodeName
                    );


                if (
                    $tag !== 'td'
                    &&
                    $tag !== 'th'
                ) {
                    continue;
                }


                $cells[] =
                    rt_clean(
                        (string)$child->textContent
                    );
            }


            if (count($cells) < 2) {
                continue;
            }


            $label =
                rt_key(
                    $cells[0]
                );


            if ($label !== '') {

                $pairs[$label] =
                    $cells[1];
            }
        }


        libxml_clear_errors();

        libxml_use_internal_errors(
            $oldErrors
        );
    }


    /*
     * Plain text fallback.
     */

    $prepared =
        preg_replace(
            '/<(?:br|\/tr|\/td|\/th|\/p|\/div|\/li)>/i',
            "\n",
            $html
        );


    if (!is_string($prepared)) {
        $prepared = $html;
    }


    $plain =
        html_entity_decode(
            strip_tags($prepared),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );


    $lines =
        preg_split(
            '/[\r\n]+/',
            $plain
        );


    if (!is_array($lines)) {
        $lines = [];
    }


    foreach ($lines as $line) {

        $line =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $line
                )
                ?: ''
            );


        if ($line === '') {
            continue;
        }


        $match = [];


        if (
            preg_match(
                '/^(.{2,90}?)\s*[:=]\s*(.+)$/',
                $line,
                $match
            )
        ) {

            $label =
                rt_key(
                    (string)$match[1]
                );


            if (
                $label !== ''
                &&
                !isset($pairs[$label])
            ) {

                $pairs[$label] =
                    rt_clean(
                        (string)$match[2]
                    );
            }
        }
    }


    return $pairs;
}


/*
 * ============================================================
 * PICK METRIC
 * ============================================================
 */

function rt_pick(
    array $pairs,
    array $aliases
): string {

    foreach ($aliases as $alias) {

        $wanted =
            rt_key(
                $alias
            );


        if (!isset($pairs[$wanted])) {
            continue;
        }


        $value =
            trim(
                (string)$pairs[$wanted]
            );


        if ($value !== '') {

            return $value;
        }
    }


    foreach ($pairs as $key => $value) {

        foreach ($aliases as $alias) {

            $wanted =
                rt_key(
                    $alias
                );


            if ($wanted === '') {
                continue;
            }


            if (
                strpos(
                    $key,
                    $wanted
                )
                !==
                false
            ) {

                return trim(
                    (string)$value
                );
            }
        }
    }


    return '';
}


/*
 * ============================================================
 * PARSE STATS RESPONSE
 * ============================================================
 */

function rt_metrics(string $body): array
{
    $pairs = [];


    $trimmed =
        trim(
            $body
        );


    if (
        $trimmed !== ''
        &&
        (
            $trimmed[0] === '{'
            ||
            $trimmed[0] === '['
        )
    ) {

        $json =
            json_decode(
                $trimmed,
                true
            );


        if (is_array($json)) {

            rt_json_flatten(
                $json,
                $pairs
            );
        }
    }


    $htmlPairs =
        rt_html_pairs(
            $body
        );


    foreach ($htmlPairs as $key => $value) {

        if (!isset($pairs[$key])) {

            $pairs[$key] =
                $value;
        }
    }


    
    $metrics=[];
    foreach ([
        'SimFPS' => ['SimFPS','Sim FPS','Simulator FPS'],
        'PhysicsFPS' => ['PhyFPS','PhysicsFPS','Phys FPS'],
        'TimeDilation' => ['Dilatn','TimeDilation'],
        'FrameTime' => ['TotlFt','TotalFrameTime','FrameTime'],
        'ActiveScripts' => ['AtvScr','ActiveScripts'],
        'ScriptEvents' => ['ScrEPS','ScriptEvents','ScriptEPS'],
        'ScriptLPS' => ['ScrLPS','ScriptLPS'],
        'RootAgents' => ['RootAg','RootAgents'],
        'ChildAgents' => ['ChldAg','ChildAgents'],
        'NPCAgents' => ['NPCAg','NPCAgents'],
        'Prims' => ['Prims','Primitives','PrimCount'],
        'ActivePrims' => ['AtvPrm','ActivePrims'],
        'NetIn' => ['PktsIn','PacketsIn','NetIn'],
        'NetOut' => ['PktOut','PacketsOut','NetOut'],
        'PendingDownloads' => ['PendDl','PendingDownloads'],
        'PendingUploads' => ['PendUl','PendingUploads'],
        'UnackedBytes' => ['UnackB','UnackedBytes'],
        'NetFrameTime' => ['NetFt','NetFrameTime'],
        'PhysicsFrameTime' => ['PhysFt','PhysicsFrameTime'],
        'OtherFrameTime' => ['OthrFt','OtherFrameTime'],
        'AgentFrameTime' => ['AgntFt','AgentFrameTime'],
        'ImageFrameTime' => ['ImgsFt','ImageFrameTime']
    ] as $name=>$aliases) $metrics[$name]=rt_pick($pairs,$aliases);

    $count = 0;


    foreach ($metrics as $value) {

        if (
            trim(
                (string)$value
            )
            !==
            ''
        ) {

            $count++;
        }
    }


    $metrics['MetricCount'] =
        $count;


    $metrics['DetectedLabels'] =
        array_slice(
            array_keys($pairs),
            0,
            80
        );


    return $metrics;
}


/*
 * ============================================================
 * RUN
 * ============================================================
 */

$regionsRoot =
    ag_dg_regions_root();


$rootOkay =
    is_string($regionsRoot)
    &&
    $regionsRoot !== ''
    &&
    is_dir($regionsRoot);


if (!$rootOkay) {

    rt_fail(
        'Regions directory unavailable.'
    );
}


try {
    $regions=ri_regions();
    if (isset($_GET['region']) || isset($_GET['uuid'])) $regions=[ri_select($regions,$_GET['region']??'',$_GET['uuid']??'')];
} catch (InvalidArgumentException $e) { rt_fail($e->getMessage(),400); }
catch (Throwable $e) { rt_fail('Region statistics unavailable for this selection.',404); }
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();


$output =
    [];


foreach ($regions as $region) {

    $name =
        (string)$region['RegionName'];


    $uuid =
        (string)$region['RegionUUID'];


    $port =
        (int)$region['Port'];


    $page =
        rt_stats_page(
            $name,
            $uuid,
            $port
        );


    if (!$page['ok']) {

        $output[] =
            [
                'RegionName' =>
                    $name,

                'RegionUUID' =>
                    $uuid,

                'Port' =>
                    $port,

                'Available' =>
                    false,

                'PageFound' =>
                    false,

                'MetricCount' =>
                    0,

                'StatsUrl' =>
                    '',

                'Attempts' =>
                    $page['attempts'],
            ];


        continue;
    }


    $metrics =
        rt_metrics(
            (string)$page['body']
        );


    $metricCount =
        (int)$metrics['MetricCount'];


    $output[] =
        array_merge(
            [
                'RegionName' =>
                    $name,

                'RegionUUID' =>
                    $uuid,

                'Port' =>
                    $port,

                'Available' =>
                    ($metricCount > 0),

                'PageFound' =>
                    true,

                'StatsUrl' =>
                    (string)$page['url'],
            ],
            $metrics
        );
}


echo json_encode(
    [
        'ok' =>
            true,

        'generated' =>
            date(
                DATE_ATOM
            ),

        'regionCount' =>
            count(
                $regions
            ),

        'regions' =>
            $output,
    ],
    JSON_UNESCAPED_SLASHES
    |
    JSON_UNESCAPED_UNICODE
);