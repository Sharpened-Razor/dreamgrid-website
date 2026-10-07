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
if((int)$s['level']<200){http_response_code(403);echo json_encode(['ok'=>false,'error'=>'Grid Owner access required']);exit;}
function runCmd($cmd){if(!function_exists('shell_exec'))return ''; $out=@shell_exec($cmd.' 2>&1'); return is_string($out)?$out:'';}
function processCount($image){$out=runCmd('tasklist /FI "IMAGENAME eq '.$image.'" /FO CSV /NH'); if($out==='')return null; $c=0; foreach(preg_split('/\r?\n/',trim($out)) as $line){if($line===''||stripos($line,'INFO:')===0)continue; if(stripos($line,'"'.$image.'"')!==false)$c++;} return $c;}
function serviceState($name){$out=runCmd('sc query "'.$name.'"'); if($out==='')return 'UNKNOWN'; if(preg_match('/STATE\s*:\s*\d+\s+([A-Z_]+)/i',$out,$m))return strtoupper($m[1]); return stripos($out,'does not exist')!==false?'NOT INSTALLED':'UNKNOWN';}
function tcpCheck($host,$port,$timeout=1.5){$errno=0;$errstr='';$fp=@fsockopen($host,(int)$port,$errno,$errstr,$timeout); if($fp){fclose($fp);return ['ok'=>true,'detail'=>'RESPONDING'];} return ['ok'=>false,'detail'=>$errstr!==''?$errstr:('TCP '.$port.' failed')];}
function dirSize($path){if(!is_dir($path))return null;$total=0;try{$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY);foreach($it as $f){if($f->isFile())$total+=$f->getSize();}return $total;}catch(Throwable $e){return null;}}
function recentErrors($file,$max=25){if(!is_file($file))return [];$lines=@file($file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);if(!is_array($lines))return [];$out=[];foreach(array_reverse($lines) as $line){if(preg_match('/(ERROR|crashed|Unable to connect|failed|watchdog)/i',$line)){$out[]=$line;if(count($out)>=$max)break;}}return $out;}
$outworldz = null;
$australiaPathProbe = __DIR__;

while (true) {

    $hasSettings =
        is_file(
            $australiaPathProbe .
            DIRECTORY_SEPARATOR .
            'Settings.ini'
        );

    $hasOpenSim =
        is_dir(
            $australiaPathProbe .
            DIRECTORY_SEPARATOR .
            'Opensim'
        );

    if ($hasSettings && $hasOpenSim) {

        $resolved =
            realpath(
                $australiaPathProbe
            );

        $outworldz =
            $resolved !== false
            ? $resolved
            : $australiaPathProbe;

        break;
    }

    $australiaPathParent =
        dirname(
            $australiaPathProbe
        );

    if (
        $australiaPathParent ===
        $australiaPathProbe
    ) {
        break;
    }

    $australiaPathProbe =
        $australiaPathParent;
}

if ($outworldz === null) {

    http_response_code(500);

    echo json_encode(
        [
            'ok' => false,
            'error' =>
                'DreamGrid root could not be located.'
        ]
    );

    exit;
}
$host=preg_replace('/:\d+$/','',(string)($_SERVER['HTTP_HOST']??ag_dg_hostname())); if($host===''||filter_var($host,FILTER_VALIDATE_IP))$host=ag_dg_hostname();
$resolved=@gethostbyname($host);$dnsOk=($resolved&&$resolved!==$host);
$diskRoot=$outworldz;$diskFree=@disk_free_space($diskRoot);$diskTotal=@disk_total_space($diskRoot);
$services=['Apache service'=>serviceState('ApacheHTTPServer'),'httpd.exe processes'=>processCount('httpd.exe'),'MySQL processes'=>processCount('mysqld.exe'),'Robust processes'=>processCount('Robust.exe'),'OpenSim processes'=>processCount('OpenSim.exe')];
$network=['host'=>$host,'loginPort'=>ag_dg_robust_port(),'statusPort'=>ag_dg_diagnostics_port(),'resolvedIp'=>$dnsOk?$resolved:'','dnsOk'=>$dnsOk,'login8002'=>tcpCheck(ag_web_local_host(),ag_dg_robust_port()),'simstatus8013'=>tcpCheck(ag_web_local_host(),ag_dg_diagnostics_port())];
$storage=['diskRoot'=>$diskRoot,'diskFree'=>$diskFree===false?null:(int)$diskFree,'diskTotal'=>$diskTotal===false?null:(int)$diskTotal,'autobackup'=>null,'oar'=>null,'iar'=>null,'mysql'=>null,'fsassets'=>null];
$errors=recentErrors($outworldz.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'ERROR.log');
echo json_encode(['ok'=>true,'checkedAt'=>date(DATE_ATOM),'services'=>$services,'network'=>$network,'storage'=>$storage,'recentErrors'=>$errors]);
