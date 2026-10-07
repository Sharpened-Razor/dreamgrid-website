<?php
require_once __DIR__.'/core/bootstrap.php';require_once __DIR__.'/core/page-designer.php';ag_no_cache();
try {
    $session=ag_current_session();$admin=$session && ag_is_admin($session);$kind=$_GET['kind'] ?? '';$id=$_GET['id'] ?? '';$file=$_GET['file'] ?? '';
    if(!$admin){
        $page=ag_pd_load_by_id($_GET['page'] ?? '');$site=ag_pbl_site_live();$allowed=false;
        if($kind==='section' && $page)foreach(array('header','footer') as $role)if(!empty($page['builder']['site'][$role]) && $site[$role]===$id)$allowed=true;
        $status=$page?ag_pd_can_view($page,(bool)$session,false):404;if(!$allowed || $status!==200){http_response_code($status===403?403:404);exit('Image not found.');}
    }
    $path=ag_pbl_asset($kind,$id,$file);$info=@getimagesize($path);$mime=$info['mime'] ?? '';if(!in_array($mime,array('image/jpeg','image/png','image/gif','image/webp'),true)){http_response_code(415);exit('Invalid image.');}
    while(ob_get_level()>0)ob_end_clean();header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');readfile($path);
}catch(Throwable $e){http_response_code(404);echo 'Image not found.';}
