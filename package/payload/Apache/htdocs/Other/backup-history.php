<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
date_default_timezone_set(
    ag_dg_timezone()
);
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$s=ag_current_session();
if(!$s){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'Not logged in']);exit;}
if(!ag_is_admin($s)){http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']);exit;}
$jobs=__DIR__.DIRECTORY_SEPARATOR.'jobs'; if(!is_dir($jobs)) @mkdir($jobs,0775,true);
$file=$jobs.DIRECTORY_SEPARATOR.'backup_history.json';
function jread($p){if(!is_file($p))return null;$r=(string)@file_get_contents($p);if(substr($r,0,3)==="\xEF\xBB\xBF")$r=substr($r,3);$d=json_decode($r,true);return is_array($d)?$d:null;}
function hread($p){$d=jread($p);return is_array($d)?$d:[];}
function hsave($p,$r){return @file_put_contents($p,json_encode(array_values($r),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX)!==false;}
function norm($e){
 $type=strtoupper(trim((string)($e['type']??'OAR')));if(!in_array($type,['OAR','IAR'],true))$type='OAR';
 $src=strtoupper(trim((string)($e['source']??'MANUAL')));if(!in_array($src,['MANUAL','SCHEDULED','IMPORTED'],true))$src='MANUAL';
 $st=strtoupper(trim((string)($e['status']??'SUCCESS')));if(!in_array($st,['SUCCESS','FAILED','SKIPPED'],true))$st='SUCCESS';
 return ['id'=>trim((string)($e['id']??''))?:date('YmdHis').'_'.bin2hex(random_bytes(4)),'time'=>trim((string)($e['time']??''))?:date(DATE_ATOM),'type'=>$type,'source'=>$src,'status'=>$st,'subject'=>trim((string)($e['subject']??'')),'file'=>basename(trim((string)($e['file']??''))),'size'=>max(0,(int)($e['size']??0)),'duration'=>max(0,(int)($e['duration']??0)),'message'=>trim((string)($e['message']??'')),'actor'=>trim((string)($e['actor']??''))];
}
$action=strtolower(trim((string)($_REQUEST['action']??'list')));
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='add'){
 $e=json_decode((string)($_POST['entry']??''),true);if(!is_array($e)){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'Invalid history entry']);exit;}
 $e=norm($e);if($e['actor']==='')$e['actor']=trim((string)$s['avatar']);$rows=hread($file);array_unshift($rows,$e);if(count($rows)>2000)$rows=array_slice($rows,0,2000);
 if(!hsave($file,$rows)){http_response_code(500);echo json_encode(['ok'=>false,'error'=>'Unable to save backup history']);exit;}echo json_encode(['ok'=>true,'entry'=>$e]);exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='clear'){if(!hsave($file,[])){http_response_code(500);echo json_encode(['ok'=>false,'error'=>'Unable to clear history']);exit;}echo json_encode(['ok'=>true]);exit;}
$rows=hread($file);

// Import durable V57 scheduled result.
$state=jread($jobs.DIRECTORY_SEPARATOR.'backup_scheduler_state.json');
if(is_array($state)){
 $run=$state['lastBackupRun']??$state['lastRun']??null;$regs=$state['lastBackupRegions']??$state['regions']??[];
 if($run&&is_array($regs))foreach($regs as $r){if(!is_array($r))continue;$st=strtoupper((string)($r['status']??''));if($st==='COMPLETE')$st='SUCCESS';elseif($st==='ERROR')$st='FAILED';elseif($st!=='SKIPPED')continue;
  $id='scheduled_'.sha1($run.'|'.($r['region']??'').'|'.($r['file']??''));$exists=false;foreach($rows as $x)if(($x['id']??'')===$id){$exists=true;break;}
  if(!$exists)$rows[]=norm(['id'=>$id,'time'=>$run,'type'=>'OAR','source'=>'SCHEDULED','status'=>$st,'subject'=>$r['region']??'','file'=>$r['file']??'','size'=>$r['size']??0,'message'=>$r['message']??'','actor'=>'Windows Scheduler']);
 }
}

// Import existing backup files without recursively walking the whole Autobackup tree.
// The grid can contain very large OARs and many unrelated files, so only inspect the
// exact folders/formats already used by the working Backup Files panel.
$root=ag_dg_autobackup_root();
$knownFiles=[];

// OARs live only under AutoBackup-YYYY-MM-DD\OAR\*.oar
if(is_dir($root)){
    foreach(glob($root.DIRECTORY_SEPARATOR.'AutoBackup-*',GLOB_ONLYDIR) ?: [] as $dayDir){
        $oarDir=$dayDir.DIRECTORY_SEPARATOR.'OAR';
        if(!is_dir($oarDir)) continue;
        foreach(glob($oarDir.DIRECTORY_SEPARATOR.'*.oar') ?: [] as $path){
            if(is_file($path)) $knownFiles[]=$path;
        }
    }
}

// V40+ IARs already have durable metadata, so use those paths instead of scanning.
foreach(glob($jobs.DIRECTORY_SEPARATOR.'iar_meta_*.json') ?: [] as $metaFile){
    $meta=jread($metaFile);
    if(!is_array($meta)) continue;
    $path=(string)($meta['path']??'');
    if($path!=='' && is_file($path)) $knownFiles[]=$path;
}

// Build one filename index so importing stays O(n), not O(files × history).
$existingNames=[];
foreach($rows as $x){
    $n=strtolower((string)($x['file']??''));
    if($n!=='') $existingNames[$n]=true;
}

foreach($knownFiles as $path){
    $name=basename($path);
    $key=strtolower($name);
    if(isset($existingNames[$key])) continue;

    $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if($ext!=='oar' && $ext!=='iar') continue;

    $subject=$name;
    if(preg_match('/^(.*?)_\d{4}-\d{2}-\d{2}_\d{2}_\d{2}_\d{2}/',$name,$m)) $subject=$m[1];
    if($ext==='iar') $subject=str_replace('_',' ',$subject);

    $mtime=(int)@filemtime($path);
    $size=(int)@filesize($path);
    $rows[]=norm([
        'id'=>'import_'.sha1($path.'|'.$mtime.'|'.$size),
        'time'=>date(DATE_ATOM,$mtime),
        'type'=>strtoupper($ext),
        'source'=>'IMPORTED',
        'status'=>'SUCCESS',
        'subject'=>$subject,
        'file'=>$name,
        'size'=>$size,
        'message'=>'Existing backup file'
    ]);
    $existingNames[$key]=true;
}
usort($rows,function($a,$b){return strcmp((string)($b['time']??''),(string)($a['time']??''));});hsave($file,$rows);
echo json_encode(['ok'=>true,'history'=>$rows,'count'=>count($rows)]);
