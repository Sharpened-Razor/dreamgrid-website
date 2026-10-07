<?php
require_once __DIR__.'/core/bootstrap.php';
require_once __DIR__.'/core/page-designer.php';
ag_no_cache();
try {
    $s=ag_current_session(); $admin=$s && ag_is_admin($s);
    $p=$admin?ag_pd_edit_page($_GET['page'] ?? ''):ag_pd_load_by_id($_GET['page'] ?? ''); if(!$p) { http_response_code(404); exit('Image not found.'); }
    $status=ag_pd_can_view($p,(bool)$s,(bool)$admin,false,true);
    if($status!==200) { http_response_code($status); exit('Image access denied.'); }
    $file=$_GET['file'] ?? ''; $referenced=$file===($p['backgroundImage'] ?? ''); foreach($p['cards'] ?? array() as $c) if(($c['image'] ?? '')===$file) $referenced=true;
    if(isset($p['builder']) && in_array($file,ag_pb_images($p['builder']),true)) $referenced=true;
    if($admin && in_array($file,ag_pd_history_images($p['id']),true)) $referenced=true;
    if($admin) { $live=ag_pd_load_by_id($p['id']); if($live && in_array($file,ag_pd_page_images($live),true)) $referenced=true; }
    $path=$referenced ? ag_pd_asset_path($p['id'],$file) : null; if(!$path) { http_response_code(404); exit('Image not found.'); }
    $info=@getimagesize($path); $mime=$info['mime'] ?? ''; if(!in_array($mime,array('image/jpeg','image/png','image/gif','image/webp'),true)) { http_response_code(415); exit('Invalid image.'); }
    while(ob_get_level()>0) ob_end_clean(); header('Content-Type: '.$mime); header('Content-Length: '.filesize($path)); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store'); readfile($path);
} catch(Throwable $e) { http_response_code(500); error_log('Page Designer asset: '.$e->getMessage()); echo 'Image could not be loaded.'; }
