<?php
require_once __DIR__.'/core/bootstrap.php';
require_once __DIR__.'/core/site-appearance.php';
ag_no_cache();header('Content-Type: text/css; charset=utf-8');header('X-Content-Type-Options: nosniff');
try {
    $scope=$_GET['scope'] ?? '';if(!is_string($scope)||!isset(ag_sd_scopes()[$scope])){http_response_code(400);exit;}
    try{$state=ag_sa_read();$theme=ag_sd_active_theme($state,$scope);}catch(Throwable $e){$state=ag_sa_defaults();$theme=ag_sa_presets()['safe'];}
    if(isset($_GET['preview'])){$s=ag_current_session();if(!$s||!ag_is_admin($s)){http_response_code(403);exit;}$id=is_string($_GET['preview'])?$_GET['preview']:'';}
    if(isset($id))$theme=ag_sa_library($state)[$id] ?? null;if(!$theme){http_response_code(404);exit;}
    if(!empty($theme['styles']['background'])&&!ag_sd_asset_path($theme['styles']['background']))$theme=ag_sa_presets()['safe'];
    if(!empty($theme['styles']['artwork'])&&!is_file(ag_sd_artwork_library()[$theme['styles']['artwork']]['path']))$theme=ag_sa_presets()['safe'];
    echo ag_sa_css($theme,$scope).ag_sd_effective_brand_css($theme,$state['branding']);
}catch(Throwable $e){error_log('Site theme: '.$e->getMessage());http_response_code(503);}
