<?php
require_once __DIR__ . '/core/dreamgrid-env.php';
date_default_timezone_set(
    ag_dg_timezone()
);
require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();
require_once __DIR__ . '/restore-safety.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function outFail($message, $code=400) {
    http_response_code($code);
    echo json_encode(['ok'=>false,'error'=>$message]);
    exit;
}
function dgRoot($params) {
    $url=ag_dg_diagnostics_base() . '/?'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
    $ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>20,'ignore_errors'=>true]]);
    $r=@file_get_contents($url,false,$ctx);
    if($r===false) outFail('Region list unavailable.',502);
    return trim($r);
}
function backupPath($rel) {

    $outworldz =
        ag_dg_root();

    $rel =
        str_replace(
            '\\',
            '/',
            trim((string)$rel)
        );

    if (
        $rel === '' ||
        strpos($rel, "\0") !== false ||
        strpos($rel, '..') !== false ||
        !preg_match('/\.oar$/i', $rel)
    ) {
        return false;
    }


    /*
     * OAR restores are allowed only from these two
     * controlled DreamGrid backup locations:
     *
     * UserOARBackups = resident 3-slot rollback backups
     * Autobackup     = normal DreamGrid generated OARs
     */
    $roots = array(
        $outworldz .
        DIRECTORY_SEPARATOR .
        'UserOARBackups',

        $outworldz .
        DIRECTORY_SEPARATOR .
        'Autobackup'
    );


    $candidates = array();

    $normalized =
        ltrim(
            $rel,
            '/'
        );


    /*
     * Accept paths that already contain the backup-root name.
     *
     * Example:
     *
     * UserOARBackups/Owner/Welcome/Backup-1.oar
     *
     * Autobackup/AutoBackup-2026-09-11/OAR/Welcome_....oar
     */
    foreach (
        array(
            'UserOARBackups',
            'Autobackup'
        )
        as $rootName
    ) {

        $prefix =
            $rootName .
            '/';

        if (
            stripos(
                $normalized,
                $prefix
            ) === 0
        ) {

            $inside =
                substr(
                    $normalized,
                    strlen($prefix)
                );

            foreach (
                $roots
                as $root
            ) {

                if (
                    strcasecmp(
                        basename($root),
                        $rootName
                    ) === 0
                ) {

                    $candidates[] =
                        $root .
                        DIRECTORY_SEPARATOR .
                        str_replace(
                            '/',
                            DIRECTORY_SEPARATOR,
                            $inside
                        );
                }
            }
        }
    }


    /*
     * Also accept paths relative to either allowed root.
     *
     * Example:
     *
     * Owner/Welcome/Backup-1.oar
     */
    foreach (
        $roots
        as $root
    ) {

        $candidates[] =
            $root .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $normalized
            );
    }


    /*
     * If the UI supplied an absolute Windows path,
     * allow it only if realpath proves that it is
     * inside one of the two approved backup roots.
     */
    if (
        preg_match(
            '/^[A-Za-z]:[\/\\\\]/',
            $rel
        )
    ) {

        $candidates[] =
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $rel
            );
    }


    foreach (
        array_unique($candidates)
        as $candidate
    ) {

        $real =
            realpath(
                $candidate
            );

        if (
            $real === false ||
            !is_file($real) ||
            !preg_match('/\.oar$/i', $real)
        ) {
            continue;
        }


        $realNormalized =
            strtolower(
                str_replace(
                    '\\',
                    '/',
                    $real
                )
            );


        foreach (
            $roots
            as $root
        ) {

            $rootReal =
                realpath(
                    $root
                );

            if (
                $rootReal === false
            ) {
                continue;
            }


            $allowedPrefix =
                rtrim(
                    strtolower(
                        str_replace(
                            '\\',
                            '/',
                            $rootReal
                        )
                    ),
                    '/'
                ) .
                '/';


            if (
                strpos(
                    $realNormalized,
                    $allowedPrefix
                ) === 0
            ) {

                return $real;
            }
        }
    }


    return false;
}
function iniValue($text,$name) {
    if(preg_match('/^\s*'.preg_quote($name,'/').'\s*=\s*"?([^"\r\n;]+)"?\s*(?:;.*)?$/mi',$text,$m))
        return trim($m[1]);
    return '';
}
function regionRemoteAdmin($region) {
    $base=ag_dg_regions_root();
    foreach(glob($base.DIRECTORY_SEPARATOR.'*',GLOB_ONLYDIR) ?: [] as $dir) {
        $ini=$dir.DIRECTORY_SEPARATOR.'Opensim.ini';
        if(!is_file($ini)) continue;
        $text=(string)@file_get_contents($ini);
        $names=[];
        // RegionName may live in Opensim.ini or another DreamGrid INI in the region folder.
        foreach(glob($dir.DIRECTORY_SEPARATOR.'*.ini') ?: [] as $candidateIni){
            $ct=(string)@file_get_contents($candidateIni);
            foreach(['RegionName','region_name','Region Name'] as $key){
                $v=iniValue($ct,$key); if($v!=='') $names[]=$v;
            }
        }
        // DreamGrid folder names normally match, but do not require that (e.g. Offical Region).
        $folder=basename($dir);
        $matched=strcasecmp($folder,$region)===0;
        foreach($names as $n) if(strcasecmp($n,$region)===0) $matched=true;

        // Also tolerate the known DreamGrid folder typo "Offical Region" for "Official Region".
        if(!$matched && strcasecmp(str_replace('Offical','Official',$folder),$region)===0) $matched=true;
        if(!$matched) continue;

        $pass=iniValue($text,'access_password');
        $port=(int)iniValue($text,'port');
        if($pass==='' || $port<=0) outFail('RemoteAdmin settings missing for '.$region.'.',500);
        return ['password'=>$pass,'port'=>$port,'folder'=>$folder];
    }
    outFail('Could not find the OpenSim region folder for '.$region.'.',404);
}
function prepareRestore($cfg,$command,$region,$oarPath) {
    $jobDir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
    if(!is_dir($jobDir) && !@mkdir($jobDir,0775,true)) outFail('Could not create restore job folder.',500);
    $jobId=date('Ymd_His').'_'.bin2hex(random_bytes(4));
    $psFile=$jobDir.DIRECTORY_SEPARATOR.'oar_restore_'.$jobId.'.ps1';
    $logFile=$jobDir.DIRECTORY_SEPARATOR.'oar_restore_'.$jobId.'.log';
    $metaFile=$jobDir.DIRECTORY_SEPARATOR.'oar_restore_meta_'.$jobId.'.json';
    $commandFile=$jobDir.DIRECTORY_SEPARATOR.'oar_restore_'.$jobId.'.command.txt';
    $configFile=$jobDir.DIRECTORY_SEPARATOR.'oar_restore_'.$jobId.'.config.json';

    $ps=<<<'POWERSHELL'
param([string]$ConfigFile)
$ErrorActionPreference='Stop'
$GridDirectory = [IO.DirectoryInfo]$PSScriptRoot
while($GridDirectory -and -not (Test-Path -LiteralPath (Join-Path $GridDirectory.FullName 'Settings.ini'))){$GridDirectory=$GridDirectory.Parent}
if(-not $GridDirectory){throw 'Grid settings were not found.'}
. (Join-Path $GridDirectory.FullName 'Apache/htdocs/Other/windows/WebsiteRuntime.ps1')
$RemoteHost = (Get-DgWebsiteRuntime -StartDirectory $PSScriptRoot).host
function XmlEscape([string]$s){ return [System.Security.SecurityElement]::Escape($s) }
$cfg=Get-Content -LiteralPath $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
$RemotePass=[string]$cfg.RemotePass
$RemotePort=[int]$cfg.RemotePort
$CommandFile=[string]$cfg.CommandFile
$LogFile=[string]$cfg.LogFile
try {
  $ConsoleCommand=(Get-Content -LiteralPath $CommandFile -Raw -Encoding UTF8).Trim()
  if([string]::IsNullOrWhiteSpace($ConsoleCommand)){ throw "Restore console command is blank" }
  $xml='<?xml version="1.0"?>' +
    '<methodCall><methodName>admin_console_command</methodName><params><param><value><struct>' +
    '<member><name>password</name><value><string>'+(XmlEscape $RemotePass)+'</string></value></member>' +
    '<member><name>command</name><value><string>'+(XmlEscape $ConsoleCommand)+'</string></value></member>' +
    '</struct></value></param></params></methodCall>'
  "START $(Get-Date -Format o)" | Set-Content -LiteralPath $LogFile -Encoding UTF8
  "COMMAND $ConsoleCommand" | Add-Content -LiteralPath $LogFile -Encoding UTF8
  $response=Invoke-WebRequest -UseBasicParsing -Uri ("http://{1}:{0}/" -f $RemotePort, $RemoteHost) -Method POST -ContentType "text/xml" -Body $xml -TimeoutSec 14400
  "HTTP $($response.StatusCode)" | Add-Content -LiteralPath $LogFile -Encoding UTF8
  $response.Content | Add-Content -LiteralPath $LogFile -Encoding UTF8
  "END $(Get-Date -Format o)" | Add-Content -LiteralPath $LogFile -Encoding UTF8
} catch {
  "ERROR $(Get-Date -Format o) $($_.Exception.Message)" | Add-Content -LiteralPath $LogFile -Encoding UTF8
  exit 1
} finally { Remove-Variable RemotePass -ErrorAction SilentlyContinue }
POWERSHELL;

    if(@file_put_contents($psFile,$ps)===false) outFail('Could not create restore worker.',500);
    if(@file_put_contents($commandFile,$command)===false) outFail('Could not create restore command file.',500);


    if(@file_put_contents($metaFile,json_encode([
        'jobId'=>$jobId,'region'=>$region,'oar'=>$oarPath,'created'=>date('c'),
        'state'=>'prepared'
    ],JSON_UNESCAPED_SLASHES))===false) outFail('Could not create restore metadata.',500);

    // V48: no detached child process is launched here.
    // The caller sends the job ID to the browser, then performs the restore inline.

    return $jobId;
}

function runPreparedRestoreInline($jobId,$cfg,$command) {
    @set_time_limit(0);
    @ignore_user_abort(true);

    $dir=__DIR__.DIRECTORY_SEPARATOR.'jobs';
    $commandFile=$dir.DIRECTORY_SEPARATOR.'oar_restore_'.$jobId.'.command.txt';
    $logFile=$dir.DIRECTORY_SEPARATOR.'oar_restore_'.$jobId.'.log';
    $metaFile=$dir.DIRECTORY_SEPARATOR.'oar_restore_meta_'.$jobId.'.json';

    $command=trim((string)$command);
    if(!is_array($cfg) || $command===''){
        @file_put_contents($logFile,'ERROR '.date('c').' Restore configuration missing'.PHP_EOL,FILE_APPEND);
        return;
    }

    $meta=json_decode((string)@file_get_contents($metaFile),true);
    if(is_array($meta)){
        $meta['state']='started';
        $meta['started']=date('c');
        @file_put_contents($metaFile,json_encode($meta,JSON_UNESCAPED_SLASHES));
    }

    $pass=(string)($cfg['RemotePass'] ?? '');
    $port=(int)($cfg['RemotePort'] ?? 0);
    if($pass==='' || $port<=0){
        @file_put_contents($logFile,'ERROR '.date('c').' RemoteAdmin configuration missing'.PHP_EOL,FILE_APPEND);
        return;
    }

    $xml='<?xml version="1.0"?>'.
         '<methodCall><methodName>admin_console_command</methodName><params><param><value><struct>'.
         '<member><name>password</name><value><string>'.
         htmlspecialchars($pass,ENT_XML1|ENT_QUOTES,'UTF-8').
         '</string></value></member>'.
         '<member><name>command</name><value><string>'.
         htmlspecialchars($command,ENT_XML1|ENT_QUOTES,'UTF-8').
         '</string></value></member>'.
         '</struct></value></param></params></methodCall>';

    @file_put_contents($logFile,'START '.date('c').PHP_EOL,LOCK_EX);
    @file_put_contents($logFile,'COMMAND '.$command.PHP_EOL,FILE_APPEND|LOCK_EX);

    $ctx=stream_context_create([
        'http'=>[
            'method'=>'POST',
            'header'=>"Content-Type: text/xml\r\nConnection: close\r\n",
            'content'=>$xml,
            'timeout'=>14400,
            'ignore_errors'=>true
        ]
    ]);

    $response=@file_get_contents(ag_web_local_base($port).'/',false,$ctx);

    if($response===false){
        $e=error_get_last();
        @file_put_contents(
            $logFile,
            'ERROR '.date('c').' '.trim((string)($e['message'] ?? 'RemoteAdmin request failed')).PHP_EOL,
            FILE_APPEND|LOCK_EX
        );
        return;
    }

    $status='HTTP';
    if(isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])){
        $status=$http_response_header[0];
    }
    @file_put_contents($logFile,$status.PHP_EOL,FILE_APPEND|LOCK_EX);
    @file_put_contents($logFile,$response.PHP_EOL,FILE_APPEND|LOCK_EX);
    @file_put_contents($logFile,'END '.date('c').PHP_EOL,FILE_APPEND|LOCK_EX);

    $meta=json_decode((string)@file_get_contents($metaFile),true);
    if(is_array($meta)){
        $meta['state']='finished';
        $meta['finished']=date('c');
        @file_put_contents($metaFile,json_encode($meta,JSON_UNESCAPED_SLASHES));
    }
}

function flushJsonAndContinue($payload) {
    $body=json_encode($payload);

    // Send the complete JSON response to the browser before the long restore begins.
    while(ob_get_level()>0) @ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Content-Length: '.strlen($body));
    header('Connection: close');
    echo $body;
    @flush();

    // Apache/PHP can buffer output; this forces the response body out where supported.
    if(function_exists('fastcgi_finish_request')){
        @fastcgi_finish_request();
    }
}

$s=ag_current_session();

if(!$s){
    outFail(
        'Not logged in.',
        401
    );
}

if(!ag_is_admin($s)){
    outFail(
        'Grid Owner access required.',
        403
    );
}

$region=trim($_POST['region'] ?? '');
$rel=trim($_POST['file'] ?? '');
if($region==='') outFail('Destination region required.');

$data=json_decode(dgRoot(['command'=>'regionlist','page'=>1,'rp'=>500,'sortorder'=>'asc']),true);
$found=false;
$destinationSize='';
foreach(($data['rows'] ?? []) as $row){
    if(strcasecmp(trim((string)($row['cell']['RegionName'] ?? '')),$region)===0){
        $found=true;
        $destinationSize=strtoupper(trim((string)($row['cell']['Size'] ?? '')));
        break;
    }
}
if(!$found) outFail('Destination region not found.',404);

$oar=backupPath($rel);
if($oar===false) outFail('OAR backup not found or invalid.',404);

// V51: authoritative OAR-size vs destination-region-size safety check.
$sourceSize='';
if(preg_match('/\((\d+)X(\d+)\)\.oar$/i', basename($oar), $sm)){
    $sourceSize=strtoupper($sm[1].'X'.$sm[2]);
}
$sizeMismatch=($sourceSize!=='' && $destinationSize!=='' && strcasecmp($sourceSize,$destinationSize)!==0);
$confirmedMismatch=(string)($_POST['confirm_size_mismatch'] ?? '')==='1';
if($sizeMismatch && !$confirmedMismatch){
    echo json_encode([
        'ok'=>false,
        'sizeMismatch'=>true,
        'error'=>'OAR size does not match destination region size.',
        'sourceSize'=>$sourceSize,
        'destinationSize'=>$destinationSize
    ]);
    exit;
}

$cfg=regionRemoteAdmin($region);

// Exact syntax confirmed from this grid's OpenSim help: load oar [<OAR path>].
$consoleOar=str_replace('\\','/',$oar);
$command='load oar "'.str_replace('"','',$consoleOar).'"';
$jobId=prepareRestore($cfg,$command,$region,$oar);

$metaFile=__DIR__.DIRECTORY_SEPARATOR.'jobs'.DIRECTORY_SEPARATOR.'oar_restore_meta_'.$jobId.'.json';
$meta=json_decode((string)@file_get_contents($metaFile),true);
if(is_array($meta)){
    $meta['requestedBy']=trim((string)$s['avatar']);
    $meta['sourceSize']=$sourceSize;
    $meta['destinationSize']=$destinationSize;
    $meta['sizeMismatch']=$sizeMismatch;
    @file_put_contents($metaFile,json_encode($meta,JSON_UNESCAPED_SLASHES));
}

list($lockOk,$lockDesc)=dgRestoreAcquireLock(
    $jobId,'oar',$region,basename($oar),trim((string)$s['avatar'])
);
if(!$lockOk){
    @unlink($metaFile);
    outFail('Another restore is already running: '.$lockDesc,409);
}

@file_put_contents(__DIR__.'/command-debug.log',
    date('Y-m-d H:i:s').' | RestoreOAR requestedBy='.trim((string)$s['avatar']).
    ' region='.$region.' file='.basename($oar).' job='.$jobId.PHP_EOL,
    FILE_APPEND|LOCK_EX);

flushJsonAndContinue([
  'ok'=>true,'message'=>'OAR restore prepared.',
  'restore'=>['jobId'=>$jobId,'region'=>$region,'name'=>basename($oar)]
]);

// V48: after the browser has received the job ID, this same server request
// performs the restore directly. No browser second POST and no detached child process.
runPreparedRestoreInline($jobId,$cfg,$command);
dgRestoreReleaseLock($jobId);
exit;
