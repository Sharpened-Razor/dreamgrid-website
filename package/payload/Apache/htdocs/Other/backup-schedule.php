<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    ag_require_same_origin_post();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s=ag_current_session();
if(!$s){ http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
if(!ag_is_admin($s)){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']); exit; }

$jobDir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
if(!is_dir($jobDir) && !@mkdir($jobDir,0775,true)){
    http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Unable to create jobs folder']); exit;
}
$configFile=$jobDir.DIRECTORY_SEPARATOR.'backup_schedule.json';
$runnerMarkerFile=$jobDir.DIRECTORY_SEPARATOR.'backup_scheduler_installed.json';
$runnerStateFile=$jobDir.DIRECTORY_SEPARATOR.'backup_scheduler_state.json';

function readJsonFileNoBom($path){
    if(!is_file($path)) return null;
    $raw=(string)@file_get_contents($path);
    if($raw==='') return null;

    // Windows PowerShell 5.1 writes UTF-8 files with a BOM.
    // json_decode() does not accept that BOM, so strip it first.
    if(substr($raw,0,3)==="\xEF\xBB\xBF"){
        $raw=substr($raw,3);
    }

    $data=json_decode($raw,true);
    return is_array($data) ? $data : null;
}

function runnerInfo($markerFile,$stateFile){
    $marker=readJsonFileNoBom($markerFile);
    $state=readJsonFileNoBom($stateFile);
    return [
        'installed'=>is_array($marker) && !empty($marker['installed']),
        'marker'=>$marker,
        'state'=>$state
    ];
}


function cleanScheduleConfig($in){
    $type=strtolower(trim((string)($in['type'] ?? 'daily')));
    if(!in_array($type,['daily','weekly','selected'],true)) $type='daily';

    $time=trim((string)($in['time'] ?? '02:00'));
    if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$time)) $time='02:00';

    $days=[];
    foreach(($in['days'] ?? []) as $d){
        $d=(int)$d;
        if($d>=0 && $d<=6 && !in_array($d,$days,true)) $days[]=$d;
    }
    sort($days);
    if($type==='weekly' && count($days)!==1) $days=[0];
    if($type==='selected' && !$days) $days=[1,2,3,4,5];

    $regions=[];
    foreach(($in['regions'] ?? []) as $r){
        $r=trim((string)$r);
        if($r!=='' && !in_array($r,$regions,true)) $regions[]=$r;
    }

    $keep=(int)($in['keepLast'] ?? 3);
    if($keep<1) $keep=1;
    if($keep>20) $keep=20;

    return [
        'enabled'=>filter_var($in['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'type'=>$type,
        'time'=>$time,
        'days'=>$days,
        'regions'=>$regions,
        'keepLast'=>$keep
    ];
}

function nextRunFromConfig($c){
    if(empty($c['enabled']) || empty($c['regions'])) return null;

    [$hh,$mm]=array_map('intval',explode(':',$c['time']));
    $now=new DateTimeImmutable('now');

    for($i=0;$i<15;$i++){
        $day=$now->setTime($hh,$mm)->modify("+{$i} day");
        if($day <= $now) continue;

        $dow=(int)$day->format('w'); // 0 Sun ... 6 Sat
        if($c['type']==='daily') return $day->format(DateTimeInterface::ATOM);
        if($c['type']==='weekly' && in_array($dow,$c['days'],true)) return $day->format(DateTimeInterface::ATOM);
        if($c['type']==='selected' && in_array($dow,$c['days'],true)) return $day->format(DateTimeInterface::ATOM);
    }
    return null;
}

function latestSelectedBackup($regions){
    if(!$regions) return null;
    $root=ag_dg_autobackup_root();
    if(!is_dir($root)) return null;

    $latest=null;
    foreach(glob($root.DIRECTORY_SEPARATOR.'AutoBackup-*',GLOB_ONLYDIR) ?: [] as $dayDir){
        $oarDir=$dayDir.DIRECTORY_SEPARATOR.'OAR';
        if(!is_dir($oarDir)) continue;
        foreach(glob($oarDir.DIRECTORY_SEPARATOR.'*.oar') ?: [] as $path){
            if(!is_file($path)) continue;
            $name=basename($path);
            foreach($regions as $region){
                if(stripos($name,$region.'_')===0){
                    $mtime=(int)@filemtime($path);
                    if($latest===null || $mtime>$latest['mtime']){
                        $latest=['mtime'=>$mtime,'name'=>$name,'region'=>$region,'time'=>date(DATE_ATOM,$mtime)];
                    }
                    break;
                }
            }
        }
    }
    return $latest;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $raw=(string)($_POST['config'] ?? '');
    $data=json_decode($raw,true);
    if(!is_array($data)){
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid schedule configuration']); exit;
    }
    $c=cleanScheduleConfig($data);
    $c['savedAt']=date(DATE_ATOM);
    $c['savedBy']=trim((string)$s['avatar']);

    if(@file_put_contents($configFile,json_encode($c,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX)===false){
        http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Unable to save schedule']); exit;
    }

    echo json_encode([
        'ok'=>true,
        'config'=>$c,
        'nextRun'=>nextRunFromConfig($c),
        'latestBackup'=>latestSelectedBackup($c['regions']),
        'runner'=>runnerInfo($runnerMarkerFile,$runnerStateFile)
    ]);
    exit;
}

$c=[
    'enabled'=>false,
    'type'=>'daily',
    'time'=>'02:00',
    'days'=>[1,2,3,4,5],
    'regions'=>[],
    'keepLast'=>3,
    'savedAt'=>null,
    'savedBy'=>null
];
if(is_file($configFile)){
    $saved=json_decode((string)@file_get_contents($configFile),true);
    if(is_array($saved)){
        $base=cleanScheduleConfig($saved);
        $base['savedAt']=$saved['savedAt'] ?? null;
        $base['savedBy']=$saved['savedBy'] ?? null;
        $c=$base;
    }
}

echo json_encode([
    'ok'=>true,
    'config'=>$c,
    'nextRun'=>nextRunFromConfig($c),
    'latestBackup'=>latestSelectedBackup($c['regions']),
    'runner'=>runnerInfo($runnerMarkerFile,$runnerStateFile)
]);
