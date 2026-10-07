<?php
require_once __DIR__.'/../Other/core/dreamgrid-env.php';
require_once __DIR__.'/../Other/core/search-runtime.php';
$CONF_domain=ag_dg_hostname();$CONF_port=(string)ag_dg_robust_port();$DB_GRIDNAME=ag_dg_hop_host();
$searchConfig=ag_web_search_database();
$DB_HOST=$searchConfig['host'];$DB_PORT=(string)$searchConfig['port'];
$DB_USER=$searchConfig['user'];$DB_PASSWORD=$searchConfig['password'];$DB_NAME=$searchConfig['database'];
unset($searchConfig);
