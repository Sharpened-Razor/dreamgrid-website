<?php
require_once __DIR__.'/core/bootstrap.php';
require_once __DIR__.'/core/page-designer.php';
ag_no_cache();
try {
    $s=ag_current_session(); $admin=$s && ag_is_admin($s); $preview=!empty($_GET['preview']) && $admin;
    $p=$preview && !empty($_GET['id']) ? ag_pd_edit_page($_GET['id']) : ag_pd_load_by_slug($_GET['page'] ?? '');
    if($preview && $p) $p=ag_pd_edit_page($p['id']);
    if(!$p) { http_response_code(404); exit('Page not found.'); }
    $status=ag_pd_can_view($p,(bool)$s,(bool)$admin,$preview);
    if($status!==200) { http_response_code($status); exit($status===404?'Page not found.':'Access denied.'); }
    header('Content-Type: text/html; charset=utf-8'); echo ag_pd_render($p,$preview);
} catch(Throwable $e) { http_response_code(500); error_log('Page Designer: '.$e->getMessage()); echo 'Page could not be loaded.'; }
