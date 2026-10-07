<?php
/* Shared website environment discovery. Values come from the current grid,
 * never from an example installation. This helper does not write configuration. */
require_once __DIR__ . '/grid-branding.php';

function ag_web_ini(string $file): array {
    $result=array(); $section='';
    foreach (@file($file, FILE_IGNORE_NEW_LINES) ?: array() as $line) {
        $line=preg_replace('/^\xEF\xBB\xBF/','',$line);
        if (preg_match('/^\s*\[([^\]]+)\]/', $line, $match)) {$section=strtolower(trim($match[1])); continue;}
        if (!preg_match('/^\s*([^;#=]+?)\s*=\s*(.*?)\s*$/', $line, $match)) continue;
        $value=trim($match[2]);
        if(strtolower(trim($match[1]))!=='connectionstring'){
            $quote=null;
            for($i=0;$i<strlen($value);$i++){
                $character=$value[$i];
                if($quote!==null){if($character===$quote)$quote=null;}
                elseif($character==='"'||$character==="'")$quote=$character;
                elseif(($character===';'||$character==='#')&&($i===0||ctype_space($value[$i-1]))){$value=rtrim(substr($value,0,$i));break;}
            }
        }
        if (strlen($value)>1 && (($value[0]==='"' && substr($value,-1)==='"') || ($value[0]==="'" && substr($value,-1)==="'"))) $value=substr($value,1,-1);
        $result[$section][strtolower(trim($match[1]))]=$value;
    }
    return $result;
}
function ag_web_ini_value(array $ini,string $section,string $key): ?string {
    $value=$ini[strtolower($section)][strtolower($key)] ?? null;
    if ($value===null) return null;
    for($attempt=0;$attempt<16 && strpos($value,'${')!==false;$attempt++) {
        $next=preg_replace_callback('/\$\{([^|}]+)\|([^}]+)\}/',function($m)use($ini){return $ini[strtolower($m[1])][strtolower($m[2])] ?? $m[0];},$value);
        if ($next===$value) break; $value=$next;
    }
    return strpos($value,'${')===false ? $value : null;
}
function ag_web_robust_ini(): array {
    $root=ag_find_dreamgrid_root();
    return $root ? ag_web_ini($root.'/Opensim/bin/Robust.HG.ini') : array();
}
function ag_web_setting(array $keys): ?string {
    $root=ag_find_dreamgrid_root();if(!$root)return null;
    foreach($keys as $key){$value=ag_read_grid_setting($root.'/Settings.ini',$key);if($value!==null&&trim($value)!=='')return trim($value);}
    return null;
}
function ag_web_port(array $settings,array $constants): int {
    $candidates=array(ag_web_setting($settings));$ini=ag_web_robust_ini();
    foreach($constants as $key)$candidates[]=ag_web_ini_value($ini,'Const',$key);
    foreach($candidates as $value)if(is_string($value)&&ctype_digit($value)&&(int)$value>=1&&(int)$value<=65535)return (int)$value;
    throw new RuntimeException('Required grid service port is not configured.');
}
function ag_web_local_host(): string {
    $configured=ag_web_setting(array('WebsiteControlHost'));
    $root=ag_find_dreamgrid_root();
    $metadata=$root ? json_decode(@file_get_contents($root.'/_WEB_CONTROL/website-runtime/local-host.json') ?: '',true) : null;
    $ini=ag_web_robust_ini();$private=ag_web_ini_value($ini,'Const','PrivURL');
    $candidates=array($configured,is_array($metadata)?($metadata['loopbackHost']??null):null,ag_web_setting(array('LanIP','InternalAddress')),$private ? parse_url($private,PHP_URL_HOST) : null,gethostname());
    foreach($candidates as $host){if(!is_string($host)||$host==='')continue;$host=trim($host,'[]');$packed=@inet_pton($host);if($packed!==false&&trim($packed,chr(0))==='')continue;if(preg_match('/[\s\/\\\\?#@]/',$host))continue;return $host;}
    throw new RuntimeException('Local grid service host could not be discovered.');
}
function ag_web_authority(string $host,int $port): string {
    if($port<1||$port>65535||preg_match('/[\s\/\\\\?#@]/',$host))throw new RuntimeException('Invalid grid service address.');
    return (strpos($host,':')!==false?'['.trim($host,'[]').']':$host).':'.$port;
}
function ag_web_local_base(int $port): string {
    $private=ag_web_ini_value(ag_web_robust_ini(),'Const','PrivURL');$scheme=$private ? parse_url($private,PHP_URL_SCHEME) : 'http';
    if(!in_array($scheme,array('http','https'),true))throw new RuntimeException('Invalid local service scheme.');
    return $scheme.'://'.ag_web_authority(ag_web_local_host(),$port);
}
function ag_web_database(string $kind='robust'): array {
    $root=ag_find_dreamgrid_root();if(!$root)throw new RuntimeException('Grid configuration was not found.');
    $ini=ag_web_robust_ini();$connection=ag_web_ini_value($ini,'DatabaseService','ConnectionString');
    if(!$connection)throw new RuntimeException('Robust database connection is not configured.');
    $parts=array();foreach(explode(';',$connection)as $part){$pair=explode('=',$part,2);if(count($pair)===2)$parts[strtolower(trim($pair[0]))]=trim($pair[1]," \t\"'");}
    $get=function($keys)use($parts){foreach($keys as $key)if(array_key_exists($key,$parts))return $parts[$key];return null;};
    $port=$get(array('port'));if(!$port)$port=ag_web_setting(array('MySqlRobustDBPort'));
    $config=array('host'=>$get(array('data source','server','host')),'port'=>(int)$port,'database'=>$get(array('database','initial catalog')),'user'=>$get(array('user id','uid','user')),'password'=>$get(array('password','pwd')));
    if(!$config['host']||!$config['database']||$config['user']===null||$config['password']===null||$config['port']<1||$config['port']>65535)throw new RuntimeException('Database configuration is incomplete.');
    return $config;
}
function ag_web_secure_request(): bool {
    return (!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off');
}
function ag_web_database_port(string $kind='robust'): int {
    $value=ag_web_setting(array($kind==='region'?'MySqlRegionDBPort':'MySqlRobustDBPort'));
    if(!$value)$value=ini_get('mysqli.default_port');
    if(!is_numeric($value)||(int)$value<1||(int)$value>65535)throw new RuntimeException('Database port is not configured.');
    return (int)$value;
}
function ag_web_select_bridge_port(array $ports): int {
    $known=array(ag_dg_robust_port(),ag_dg_private_robust_port());
    $ini=ag_web_robust_ini();
    foreach($ini as $section=>$values)foreach($values as $key=>$value)if(strpos($key,'port')!==false){$v=ag_web_ini_value($ini,$section,$key);if(is_string($v)&&ctype_digit($v))$known[]=(int)$v;}
    $remaining=array_values(array_unique(array_filter(array_map('intval',$ports),function($port)use($known){return $port>=1&&$port<=65535&&!in_array($port,$known,true);}))); 
    if(count($remaining)!==1)throw new RuntimeException('Robust console bridge could not be identified; configure its port explicitly.');
    return $remaining[0];
}
function ag_web_bridge_port(): int {
    return ag_web_bridge_endpoint()['port'];
}
function ag_web_bridge_endpoint(): array {
    static $resolved=null;
    if($resolved!==null)return $resolved;
    $value=ag_web_setting(array('WebsiteRobustBridgePort','RobustConsoleBridgePort'));
    if($value!==null&&(!ctype_digit($value)||(int)$value<1||(int)$value>65535))throw new RuntimeException('Invalid Robust console bridge port.');
    $host=ag_web_setting(array('WebsiteRobustBridgeHost'));
    if($value!==null&&$host!==null){ag_web_authority($host,(int)$value);return $resolved=array('host'=>$host,'port'=>(int)$value);}
    $root=ag_find_dreamgrid_root();$pwsh=function_exists('ag_dg_pwsh_exe')?ag_dg_pwsh_exe():'';
    if(!$root||!$pwsh)throw new RuntimeException('Robust listener discovery is unavailable.');
    $pipes=array();$process=@proc_open(array($pwsh,'-NoProfile','-NonInteractive','-File',$root.'/_WEB_CONTROL/DreamGrid.WebsiteListener.ps1'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$root,null,array('bypass_shell'=>true,'create_no_window'=>true));
    if(!is_resource($process))throw new RuntimeException('Robust listener discovery could not start.');
    fclose($pipes[0]);$deadline=microtime(true)+10;$state=proc_get_status($process);
    while($state['running']&&microtime(true)<$deadline){usleep(50000);$state=proc_get_status($process);}
    if($state['running']){proc_terminate($process);fclose($pipes[1]);fclose($pipes[2]);proc_close($process);throw new RuntimeException('Robust listener discovery timed out.');}
    $output=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$closed=proc_close($process);$status=$state['exitcode']>=0?$state['exitcode']:$closed;$endpoint=json_decode($output,true);
    if($status!==0||!is_array($endpoint)||!is_string($endpoint['host']??null)||!is_array($endpoint['ports']??null))throw new RuntimeException('Robust listener discovery failed.');
    $ports=array_values(array_unique(array_map('intval',$endpoint['ports'])));
    $port=$value!==null?(int)$value:(count($ports)===1?$ports[0]:0);
    if($port<1||$port>65535)throw new RuntimeException('Robust console bridge could not be uniquely identified; configure its port explicitly.');
    $host=$host??$endpoint['host'];ag_web_authority($host,$port);
    return $resolved=array('host'=>$host,'port'=>$port);
}

function ag_web_map_token(): string {
    $root=ag_find_dreamgrid_root();
    $key=$root ? trim(@file_get_contents($root.'/_WEB_CONTROL/DreamGrid.NativeBridge.key') ?: '') : '';
    if($key==='') throw new RuntimeException('Website installation key is unavailable; run the website installer.');
    return hash_hmac('sha256','DreamGrid website public map', $key);
}
