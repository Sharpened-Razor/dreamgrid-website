<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s=ag_current_session();
if(!$s){ http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
if(!ag_is_admin($s)){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']); exit; }

$root=ag_dg_autobackup_root();
$jobDir=__DIR__.DIRECTORY_SEPARATOR.'jobs';

$oarCount=0; $oarBytes=0; $iarCount=0; $iarBytes=0;
$oarGroups=[]; $iarGroups=[];
$seenIar=[];

function addStorageGroup(&$groups,$key,$bytes){
    if(!isset($groups[$key])) $groups[$key]=['name'=>$key,'count'=>0,'bytes'=>0];
    $groups[$key]['count']++;
    $groups[$key]['bytes'] += $bytes;
}

// Count every physical OAR in Autobackup, not merely the first 100 shown in the panel.
if(is_dir($root)){
    foreach(glob($root.DIRECTORY_SEPARATOR.'AutoBackup-*',GLOB_ONLYDIR) ?: [] as $dayDir){
        $oarDir=$dayDir.DIRECTORY_SEPARATOR.'OAR';
        if(!is_dir($oarDir)) continue;
        foreach(glob($oarDir.DIRECTORY_SEPARATOR.'*.oar') ?: [] as $path){
            if(!is_file($path)) continue;
            $bytes=(int)@filesize($path);
            $oarCount++; $oarBytes += $bytes;

            $name=basename($path);
            // DreamGrid backup names end in _YYYY-MM-DD_HH_MM_SS(size).oar.
            // Strip that suffix to obtain the region name without guessing at spaces.
            $region=preg_replace('/_\d{4}-\d{2}-\d{2}_\d{2}_\d{2}_\d{2}(?:\(\d+X\d+\))?\.oar$/i','',$name);
            if(!$region || $region===$name) $region=preg_replace('/\.oar$/i','',$name);
            addStorageGroup($oarGroups,$region,$bytes);
        }
    }
}

// Count completed, durable panel-created IARs once by real path.
foreach(glob($jobDir.DIRECTORY_SEPARATOR.'iar_meta_*.json') ?: [] as $metaFile){
    $m=json_decode((string)@file_get_contents($metaFile),true);
    if(!is_array($m)) continue;
    $path=(string)($m['path'] ?? '');
    if($path==='' || !is_file($path)) continue;
    $real=realpath($path);
    if($real===false || isset($seenIar[strtolower($real)])) continue;

    $job=(string)($m['jobId'] ?? '');
    if(!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/',$job)) continue;
    $log=$jobDir.DIRECTORY_SEPARATOR.'iar_'.$job.'.log';
    $logText=is_file($log) ? (string)@file_get_contents($log) : '';
    $finished=strpos($logText,"\nEND ")!==false || strpos($logText,"END ")===0;
    if(!$finished) continue;

    $seenIar[strtolower($real)]=true;
    $bytes=(int)@filesize($real);
    $owner=trim((string)($m['avatar'] ?? 'Unknown User'));
    if($owner==='') $owner='Unknown User';
    $iarCount++; $iarBytes += $bytes;
    addStorageGroup($iarGroups,$owner,$bytes);
}

usort($oarGroups,function($a,$b){ return $b['bytes'] <=> $a['bytes']; });
usort($iarGroups,function($a,$b){ return $b['bytes'] <=> $a['bytes']; });

$drivePath=is_dir($root) ? $root : ag_dg_root();
$free=@disk_free_space($drivePath);
$total=@disk_total_space($drivePath);

echo json_encode([
    'ok'=>true,
    'readOnly'=>true,
    'root'=>$root,
    'summary'=>[
        'oarCount'=>$oarCount,
        'oarBytes'=>$oarBytes,
        'iarCount'=>$iarCount,
        'iarBytes'=>$iarBytes,
        'backupBytes'=>$oarBytes+$iarBytes,
        'driveFreeBytes'=>$free===false ? null : (int)$free,
        'driveTotalBytes'=>$total===false ? null : (int)$total
    ],
    'oarGroups'=>array_values($oarGroups),
    'iarGroups'=>array_values($iarGroups)
]);
