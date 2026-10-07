<?php
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$s=ag_current_session();
if(!$s){ http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit; }
if(!ag_is_admin($s)){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']); exit; }

$job=trim((string)($_GET['job'] ?? ''));
if(!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/',$job)){
    echo json_encode(['ok'=>false,'error'=>'Invalid IAR restore job']); exit;
}
$dir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
$metaFile=$dir.DIRECTORY_SEPARATOR.'iar_restore_meta_'.$job.'.json';
$logFile=$dir.DIRECTORY_SEPARATOR.'iar_restore_'.$job.'.log';
$meta=json_decode((string)@file_get_contents($metaFile),true);
if(!is_array($meta)){ echo json_encode(['ok'=>false,'error'=>'IAR restore job not found']); exit; }

$log=is_file($logFile) ? (string)@file_get_contents($logFile) : '';
$error='';
if(preg_match('/(?:^|\R)ERROR\s+[^\r\n]*\s(.+?)(?:\R|$)/i',$log,$m)) $error=trim($m[1]);

// OpenSim's successful IAR output varies by build, so END + XML success=1 is the
// authoritative RemoteAdmin completion signal. Any explicit ERROR wins.
$hasEnd=(bool)preg_match('/(?:^|\R)END\s+/m',$log);
$xmlSuccess=strpos($log,'<name>success</name><value><boolean>1</boolean>')!==false;
$complete=($error==='' && $hasEnd && $xmlSuccess);

echo json_encode([
    'ok'=>true,
    'complete'=>$complete,
    'error'=>$error,
    'state'=>$meta['state'] ?? 'prepared',
    'avatar'=>$meta['avatar'] ?? '',
    'name'=>$meta['name'] ?? basename((string)($meta['iar'] ?? ''))
]);
