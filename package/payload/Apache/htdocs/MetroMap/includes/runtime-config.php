<?php
require_once __DIR__.'/../../Other/core/dreamgrid-env.php';
$CONF_domain=ag_dg_hostname();
$CONF_apache=(string)ag_dg_apache_port();
$CONF_port=(string)ag_dg_robust_port();
$loginParts=parse_url(ag_dg_login_url());
$CONF_sim_domain=$loginParts['scheme'].'://'.(strpos(trim($loginParts['host'],'[]'),':')!==false?'['.trim($loginParts['host'],'[]').']':$loginParts['host']);
$CONF_install_path='/Metromap';
$websiteDb=ag_web_database();
$CONF_db_server=$websiteDb['host'];$CONF_db_port=(string)$websiteDb['port'];
$CONF_db_user=$websiteDb['user'];$CONF_db_pass=$websiteDb['password'];$CONF_db_database=$websiteDb['database'];
unset($websiteDb);
// DreamGrid owns and regenerates config.php. Read only its selected coordinates.
$nativeMapConfig=@file_get_contents(__DIR__.'/config.php') ?: '';
foreach(array('x','y') as $axis){
    $key='CONF_center_coord_'.$axis;
    if(!preg_match('/\$'.preg_quote($key,'/').'\s*=\s*["\']?(-?\d+)/',$nativeMapConfig,$match)){
        throw new RuntimeException('DreamGrid map centre is unavailable; complete native Apache setup first.');
    }
    $$key=$match[1];
}
unset($nativeMapConfig,$match,$key,$axis);
$CONF_style_sheet='/css/stylesheet.css';
$CONF_HOME='Other';

