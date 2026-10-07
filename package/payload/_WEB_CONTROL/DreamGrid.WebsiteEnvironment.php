<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once dirname(__DIR__).'/Apache/htdocs/Other/core/dreamgrid-env.php';
try{
    $data=array('root'=>ag_dg_root(),'host'=>ag_web_local_host(),'gridName'=>ag_grid_name(),'publicHost'=>ag_dg_hostname(),
        'loginPort'=>ag_dg_robust_port(),'diagnosticsPort'=>ag_dg_diagnostics_port(),'apachePort'=>ag_dg_apache_port(),
        'loginBase'=>ag_web_local_base(ag_dg_robust_port()),'diagnosticsBase'=>ag_dg_diagnostics_base(),
        'apacheBase'=>ag_web_local_base(ag_dg_apache_port()));
    echo json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){fwrite(STDERR,'Website configuration: '.$e->getMessage().PHP_EOL);exit(1);}
