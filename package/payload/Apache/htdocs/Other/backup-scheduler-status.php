<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

date_default_timezone_set(
    ag_dg_timezone()
);
require_once __DIR__ . '/login/session.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s=dreamGridCurrentSession();
if(!$s){ http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
if((int)$s['level']<200){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']); exit; }

$dir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
$markerFile=$dir.DIRECTORY_SEPARATOR.'backup_scheduler_installed.json';
$stateFile=$dir.DIRECTORY_SEPARATOR.'backup_scheduler_state.json';

function readJsonFileNoBom($path){
    if(!is_file($path)) return null;
    $raw=(string)@file_get_contents($path);
    if($raw==='') return null;
    if(substr($raw,0,3)==="\xEF\xBB\xBF"){
        $raw=substr($raw,3);
    }
    $data=json_decode($raw,true);
    return is_array($data) ? $data : null;
}

$marker=readJsonFileNoBom($markerFile);
$state=readJsonFileNoBom($stateFile);

echo json_encode([
    'ok'=>true,
    'installed'=>is_array($marker) && !empty($marker['installed']),
    'marker'=>$marker,
    'state'=>$state
]);
