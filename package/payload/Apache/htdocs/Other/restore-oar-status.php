<?php
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$s=ag_current_session();
if(!$s){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'Not logged in']);exit;}
if(!ag_is_admin($s)){http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']);exit;}
$job=trim($_GET['job'] ?? '');
if(!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/',$job)){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'Invalid restore job']);exit;}
$dir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
$meta=json_decode((string)@file_get_contents($dir.DIRECTORY_SEPARATOR.'oar_restore_meta_'.$job.'.json'),true);
if(!is_array($meta)){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Restore job not found']);exit;}
$logFile=$dir.DIRECTORY_SEPARATOR.'oar_restore_'.$job.'.log';
$log=is_file($logFile)?(string)@file_get_contents($logFile):'';
$error='';
if(preg_match('/^ERROR\s+.+$/mi',$log,$m)) $error=trim($m[0]);
$workerEnded=(strpos($log,"\nEND ")!==false || strpos($log,'END ')===0);
$archiveSuccess=(stripos($log,'Successfully loaded archive')!==false);
$complete=$workerEnded || $archiveSuccess;
echo json_encode([
 'ok'=>true,'complete'=>$complete,'error'=>$error,
 'state'=>$meta['state'] ?? 'prepared',
 'region'=>$meta['region'] ?? '','name'=>basename((string)($meta['oar'] ?? ''))
]);
