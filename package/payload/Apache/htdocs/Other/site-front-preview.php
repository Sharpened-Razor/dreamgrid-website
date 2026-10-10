<?php
require_once __DIR__.'/core/bootstrap.php';require_once __DIR__.'/core/site-appearance.php';
ag_no_cache();ag_require_admin();header('X-Robots-Tag: noindex, nofollow');
try {
    $page=null;$theme=null;$brand=null;
    if(isset($_GET['starter'])){$page=ag_sd_starter_page($_GET['starter']);$theme=ag_sa_presets()[ag_sd_starters()[$_GET['starter']]['theme']];}
    elseif(isset($_GET['design'])){$draft=ag_sd_import_get($_GET['design']);$page=$draft['pageId']!==''?ag_pd_edit_page($draft['pageId']):ag_sd_starter_page('minimal');$theme=$draft['themes']['public'] ?? reset($draft['themes']);$brand=$draft['branding'];}
    elseif(isset($_GET['history'])){$entry=ag_sd_history_get($_GET['history']);$page=$entry['afterPage'] ?: ag_sd_starter_page('minimal');$theme=ag_sd_active_theme($entry['after'],'public');$brand=$entry['after']['branding'];}
    else {$id=is_string($_GET['id'] ?? null)?$_GET['id']:'';if($id===''||!ag_sa_render_front(true,$id))throw new RuntimeException('Choose an available public Page Designer page.');exit;}
    if(!$page)throw new RuntimeException('Preview page unavailable.');$page['_designTheme']=$theme;$page['_designBranding']=$brand ?? ag_sd_state_safe()['branding'];$page['_designScope']='public';echo ag_pd_render($page,true);
}catch(Throwable $e){http_response_code(404);echo ag_h($e->getMessage());}
