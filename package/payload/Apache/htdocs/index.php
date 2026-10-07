<?php
require_once __DIR__.'/Metromap/includes/config.php';
$destination='/'.trim($CONF_HOME,'/');
echo '<meta http-equiv="refresh" content="1;url='.htmlspecialchars($destination,ENT_QUOTES,'UTF-8').'">';
