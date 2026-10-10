<?php
require_once __DIR__.'/core/bootstrap.php';
require_once __DIR__.'/core/site-appearance.php';
ag_no_cache();header('X-Content-Type-Options: nosniff');
try {
    $name=ag_sd_asset_name($_GET['file'] ?? '');$path=ag_sd_asset_path($name);if(!$path){http_response_code(404);exit;}
    if(!ag_sd_public_asset($name,ag_sd_state_safe()))ag_require_admin();
    $info=ag_sd_image(file_get_contents($path));header('Content-Type: '.$info['mime']);header('Content-Length: '.filesize($path));readfile($path);
}catch(Throwable $e){http_response_code(404);}
