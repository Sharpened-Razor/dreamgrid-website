<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
date_default_timezone_set(
    ag_dg_timezone()
);
require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();
require_once __DIR__ . '/restore-safety.php';
// Database configuration is loaded by the portable central bootstrap.
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function outFailIar($message,$code=400){
    http_response_code($code);
    echo json_encode(['ok'=>false,'error'=>$message]);
    exit;
}
function iniValueIar($text,$name){
    if(preg_match('/^\s*'.preg_quote($name,'/').'\s*=\s*"?([^"\r\n;]+)"?\s*(?:;.*)?$/mi',$text,$m))
        return trim($m[1]);
    return '';
}
function anyRemoteAdminIar(){
    $base=ag_dg_regions_root();
    foreach(glob($base.DIRECTORY_SEPARATOR.'*',GLOB_ONLYDIR) ?: [] as $dir){
        $ini=$dir.DIRECTORY_SEPARATOR.'Opensim.ini';
        if(!is_file($ini)) continue;
        $text=(string)@file_get_contents($ini);
        $pass=iniValueIar($text,'access_password');
        $port=(int)iniValueIar($text,'port');
        if($pass!=='' && $port>0) return ['password'=>$pass,'port'=>$port,'folder'=>basename($dir)];
    }
    outFailIar('No OpenSim RemoteAdmin endpoint is available.',500);
}
function iarFromJob($job){
    if(!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/',$job)) return false;
    $metaFile=__DIR__.DIRECTORY_SEPARATOR.'jobs'.DIRECTORY_SEPARATOR.'iar_meta_'.$job.'.json';
    $m=json_decode((string)@file_get_contents($metaFile),true);
    if(!is_array($m)) return false;
    $path=(string)($m['path'] ?? '');
    if($path==='' || !is_file($path) || !preg_match('/\.iar$/i',$path)) return false;

    $root=realpath(ag_dg_autobackup_root());
    $real=realpath($path);
    if($root===false || $real===false) return false;
    $prefix=rtrim(strtolower(str_replace('\\','/',$root)),'/').'/';
    if(strpos(strtolower(str_replace('\\','/',$real)),$prefix)!==0) return false;
    return ['path'=>$real,'meta'=>$m];
}
function validateLocalAvatarIar($avatar){
    global $CONF_db_server,$CONF_db_user,$CONF_db_pass,$CONF_db_database,$CONF_db_port;
    $parts=preg_split('/\s+/',trim($avatar),2);
    $first=$parts[0] ?? '';
    $last=$parts[1] ?? '';
    if($first==='' || $last==='') outFailIar('Destination avatar must have a first and last name.');

    $con=mysqli_connect($CONF_db_server,$CONF_db_user,$CONF_db_pass,$CONF_db_database,(int)$CONF_db_port);
    if(!$con) outFailIar('Could not connect to Robust database.',500);
    $stmt=mysqli_prepare($con,'SELECT PrincipalID FROM UserAccounts WHERE FirstName=? AND LastName=? AND UserLevel>=0 LIMIT 1');
    if(!$stmt){ mysqli_close($con); outFailIar('Could not validate destination avatar.',500); }
    mysqli_stmt_bind_param($stmt,'ss',$first,$last);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt,$principalId);
    $found=mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    if(!$found) outFailIar('Destination avatar is not a local user.',404);
    return ['first'=>$first,'last'=>$last,'avatar'=>$first.' '.$last,'principalId'=>(string)$principalId];
}
function flushJsonIar($payload){
    $body=json_encode($payload);
    while(ob_get_level()>0) @ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Content-Length: '.strlen($body));
    header('Connection: close');
    echo $body;
    @flush();
    if(function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
}
function runIarRestore($jobId,$cfg,$command){
    @set_time_limit(0);
    @ignore_user_abort(true);
    $dir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
    $logFile=$dir.DIRECTORY_SEPARATOR.'iar_restore_'.$jobId.'.log';
    $metaFile=$dir.DIRECTORY_SEPARATOR.'iar_restore_meta_'.$jobId.'.json';

    $meta=json_decode((string)@file_get_contents($metaFile),true);
    if(is_array($meta)){
        $meta['state']='started'; $meta['started']=date('c');
        @file_put_contents($metaFile,json_encode($meta,JSON_UNESCAPED_SLASHES));
    }

    $xml='<?xml version="1.0"?>'.
         '<methodCall><methodName>admin_console_command</methodName><params><param><value><struct>'.
         '<member><name>password</name><value><string>'.
         htmlspecialchars((string)$cfg['password'],ENT_XML1|ENT_QUOTES,'UTF-8').
         '</string></value></member>'.
         '<member><name>command</name><value><string>'.
         htmlspecialchars($command,ENT_XML1|ENT_QUOTES,'UTF-8').
         '</string></value></member>'.
         '</struct></value></param></params></methodCall>';

    @file_put_contents($logFile,'START '.date('c').PHP_EOL,LOCK_EX);
    @file_put_contents($logFile,'COMMAND '.$command.PHP_EOL,FILE_APPEND|LOCK_EX);

    $ctx=stream_context_create(['http'=>[
        'method'=>'POST',
        'header'=>"Content-Type: text/xml\r\nConnection: close\r\n",
        'content'=>$xml,
        'timeout'=>14400,
        'ignore_errors'=>true
    ]]);
    $response=@file_get_contents(ag_web_local_base((int)$cfg['port']).'/',false,$ctx);
    if($response===false){
        $e=error_get_last();
        @file_put_contents($logFile,'ERROR '.date('c').' '.trim((string)($e['message'] ?? 'RemoteAdmin request failed')).PHP_EOL,FILE_APPEND|LOCK_EX);
        return;
    }
    $status='HTTP';
    if(isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) $status=$http_response_header[0];
    @file_put_contents($logFile,$status.PHP_EOL,FILE_APPEND|LOCK_EX);
    @file_put_contents($logFile,$response.PHP_EOL,FILE_APPEND|LOCK_EX);
    @file_put_contents($logFile,'END '.date('c').PHP_EOL,FILE_APPEND|LOCK_EX);

    $meta=json_decode((string)@file_get_contents($metaFile),true);
    if(is_array($meta)){
        $meta['state']='finished'; $meta['finished']=date('c');
        @file_put_contents($metaFile,json_encode($meta,JSON_UNESCAPED_SLASHES));
    }
}

$s=ag_current_session();

if(!$s){
    outFailIar(
        'Not logged in.',
        401
    );
}

if(!ag_is_admin($s)){
    outFailIar(
        'Grid Owner access required.',
        403
    );
}

$job=trim((string)($_POST['job'] ?? ''));
$avatar=trim((string)($_POST['avatar'] ?? ''));
if($job==='') outFailIar('IAR backup job required.');
if($avatar==='') outFailIar('Destination avatar required.');

$iar=iarFromJob($job);
if($iar===false) outFailIar('IAR backup not found or invalid.',404);
$user=validateLocalAvatarIar($avatar);
$cfg=anyRemoteAdminIar();

$consolePath=str_replace('\\','/',$iar['path']);
// Exact syntax confirmed from this grid's OpenSim help:
// load iar [-m|--merge] <first> <last> <inventory path> [<IAR path>]
// V50 deliberately does NOT use --merge.
$command='load iar "'.str_replace('"','',$user['first']).'" "'.str_replace('"','',$user['last']).'" "/" "'.str_replace('"','',$consolePath).'"';

$restoreJob=date('Ymd_His').'_'.bin2hex(random_bytes(4));
$dir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
if(!is_dir($dir) && !@mkdir($dir,0775,true)) outFailIar('Could not create restore job folder.',500);
$metaFile=$dir.DIRECTORY_SEPARATOR.'iar_restore_meta_'.$restoreJob.'.json';
if(@file_put_contents($metaFile,json_encode([
    'jobId'=>$restoreJob,
    'sourceJob'=>$job,
    'avatar'=>$user['avatar'],
    'iar'=>$iar['path'],
    'name'=>basename($iar['path']),
    'created'=>date('c'),
    'requestedBy'=>trim((string)$s['avatar']),
    'state'=>'prepared'
],JSON_UNESCAPED_SLASHES))===false) outFailIar('Could not create IAR restore metadata.',500);

list($lockOk,$lockDesc)=dgRestoreAcquireLock(
    $restoreJob,'iar',$user['avatar'],basename($iar['path']),trim((string)$s['avatar'])
);
if(!$lockOk){
    @unlink($metaFile);
    outFailIar('Another restore is already running: '.$lockDesc,409);
}

@file_put_contents(__DIR__.'/command-debug.log',
    date('Y-m-d H:i:s').' | RestoreIAR requestedBy='.trim((string)$s['avatar']).
    ' avatar='.$user['avatar'].' file='.basename($iar['path']).' job='.$restoreJob.PHP_EOL,
    FILE_APPEND|LOCK_EX);

flushJsonIar([
    'ok'=>true,
    'message'=>'IAR restore prepared.',
    'restore'=>['jobId'=>$restoreJob,'avatar'=>$user['avatar'],'name'=>basename($iar['path'])]
]);

runIarRestore($restoreJob,$cfg,$command);
dgRestoreReleaseLock($restoreJob);
exit;
