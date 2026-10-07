<?php
// Preserve DreamGrid's legacy XML-RPC search protocol on its existing PHP runtime.
require_once __DIR__.'/../Other/core/website-runtime.php';
header('Content-Type: text/html; charset=UTF-8');
$root=ag_find_dreamgrid_root();
$legacy=$root.'/_WEB_CONTROL/search/query.php';
if(function_exists('xmlrpc_server_create')){ob_start();require $legacy;$directOutput=ob_get_clean();if(trim(file_get_contents('php://input'))!=='' && trim($directOutput)===''){http_response_code(400);exit('Invalid XML-RPC request.');}echo $directOutput;return;}
$php=$root.'/PHP7/php.exe';
if(!is_file($php)||!is_file($legacy)){
    http_response_code(503);exit('Legacy search requires DreamGrid\'s PHP 7 XML-RPC runtime.');
}
$input=file_get_contents('php://input');
$pipes=array();
$process=@proc_open(array($php,'-n','-d','extension_dir='.$root.'/PHP7/ext','-d','extension=mysqli','-d','extension=pdo_mysql','-d','extension=xmlrpc',$legacy),
    array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,__DIR__,null,array('bypass_shell'=>true,'create_no_window'=>true));
if(!is_resource($process)){http_response_code(503);exit('Legacy search runtime is unavailable.');}
if($input!=='')fwrite($pipes[0],$input);
fclose($pipes[0]);
$output=stream_get_contents($pipes[1]);$errors=stream_get_contents($pipes[2]);
fclose($pipes[1]);fclose($pipes[2]);$status=proc_close($process);
if($status!==0){error_log('Legacy XML-RPC search runtime failed.');http_response_code(503);exit('Legacy search is unavailable.');}
if(trim($input)!=='' && trim($output)===''){http_response_code(400);exit('Invalid XML-RPC request.');}
echo $output;



