<?php
require_once __DIR__ . '/core/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    ag_require_same_origin_post();
}
require_once __DIR__ . '/restore-safety.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s=ag_current_session();
if(!$s){ http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
if(!ag_is_admin($s)){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']); exit; }

$dir=dgRestoreJobsDir();

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'){
    $action=(string)($_POST['action'] ?? '');

    if($action !== 'clear'){
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Invalid restore history action']);
        exit;
    }

    $active=dgRestoreActiveInfo();
    if($active){
        http_response_code(409);
        echo json_encode([
            'ok'=>false,
            'error'=>'A restore is currently active. Wait for it to finish before clearing restore history.'
        ]);
        exit;
    }

    $deleted=0;
    $patterns=[
        $dir.DIRECTORY_SEPARATOR.'oar_restore_meta_*.json',
        $dir.DIRECTORY_SEPARATOR.'iar_restore_meta_*.json'
    ];

    foreach($patterns as $pattern){
        foreach(glob($pattern) ?: [] as $file){
            if(is_file($file) && @unlink($file)){
                $deleted++;
            }
        }
    }

    echo json_encode([
        'ok'=>true,
        'deleted'=>$deleted
    ]);
    exit;
}

$rows=[];

function restoreHistoryStatus($type,$job,$meta,$dir){
    $prefix=$type==='OAR' ? 'oar_restore_' : 'iar_restore_';
    $logFile=$dir.DIRECTORY_SEPARATOR.$prefix.$job.'.log';
    $log=is_file($logFile) ? (string)@file_get_contents($logFile) : '';

    if(preg_match('/(?:^|\R)ERROR\s+([^\r\n]+)/mi',$log,$m)){
        return ['status'=>'ERROR','detail'=>trim($m[1])];
    }

    $hasEnd=(bool)preg_match('/(?:^|\R)END\s+/m',$log);
    if($type==='OAR'){
        if(stripos($log,'Could not find file')!==false){
            return ['status'=>'ERROR','detail'=>'OpenSim could not find the OAR file'];
        }
        $success=stripos($log,'Successfully loaded archive')!==false;
        if($success) return ['status'=>'COMPLETE','detail'=>''];
        if($hasEnd) return ['status'=>'ERROR','detail'=>'Restore ended without a successful archive-load confirmation'];
    } else {
        $success=strpos($log,'<name>success</name><value><boolean>1</boolean>')!==false;
        if($hasEnd && $success) return ['status'=>'COMPLETE','detail'=>''];
    }

    $state=strtolower((string)($meta['state'] ?? 'prepared'));
    if($state==='started') return ['status'=>'RESTORING','detail'=>''];
    return ['status'=>'PREPARED','detail'=>''];
}

foreach(glob($dir.DIRECTORY_SEPARATOR.'oar_restore_meta_*.json') ?: [] as $mf){
    $meta=json_decode((string)@file_get_contents($mf),true);
    if(!is_array($meta)) continue;
    $job=(string)($meta['jobId'] ?? '');
    if(!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/',$job)) continue;
    $st=restoreHistoryStatus('OAR',$job,$meta,$dir);
    $rows[]=[
        'jobId'=>$job,
        'type'=>'OAR',
        'source'=>basename((string)($meta['oar'] ?? '')),
        'target'=>(string)($meta['region'] ?? ''),
        'requestedBy'=>(string)($meta['requestedBy'] ?? 'Grid Owner'),
        'created'=>(string)($meta['created'] ?? ''),
        'finished'=>(string)($meta['finished'] ?? ''),
        'status'=>$st['status'],
        'detail'=>$st['detail'],
        'sourceSize'=>(string)($meta['sourceSize'] ?? ''),
        'destinationSize'=>(string)($meta['destinationSize'] ?? '')
    ];
}

foreach(glob($dir.DIRECTORY_SEPARATOR.'iar_restore_meta_*.json') ?: [] as $mf){
    $meta=json_decode((string)@file_get_contents($mf),true);
    if(!is_array($meta)) continue;
    $job=(string)($meta['jobId'] ?? '');
    if(!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/',$job)) continue;
    $st=restoreHistoryStatus('IAR',$job,$meta,$dir);
    $rows[]=[
        'jobId'=>$job,
        'type'=>'IAR',
        'source'=>(string)($meta['name'] ?? basename((string)($meta['iar'] ?? ''))),
        'target'=>(string)($meta['avatar'] ?? ''),
        'requestedBy'=>(string)($meta['requestedBy'] ?? 'Grid Owner'),
        'created'=>(string)($meta['created'] ?? ''),
        'finished'=>(string)($meta['finished'] ?? ''),
        'status'=>$st['status'],
        'detail'=>$st['detail'],
        'sourceSize'=>'',
        'destinationSize'=>''
    ];
}

usort($rows,function($a,$b){
    return strcmp((string)$b['created'],(string)$a['created']);
});
$rows=array_slice($rows,0,100);

echo json_encode([
    'ok'=>true,
    'active'=>dgRestoreActiveInfo(),
    'history'=>$rows
]);
