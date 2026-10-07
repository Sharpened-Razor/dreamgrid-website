<?php
// All writes run under a project-wide lock. Failed uploads never remove saved images.
function ag_pd_root() { return ag_dg_path('_WEB_PAGE_DESIGNER'); }
function ag_pd_pages_dir() { return ag_pd_root() ? ag_pd_root().DIRECTORY_SEPARATOR.'pages' : null; }
function ag_pd_assets_dir() { return ag_pd_root() ? ag_pd_root().DIRECTORY_SEPARATOR.'assets' : null; }
function ag_pd_ensure_storage() {
    foreach(array(ag_pd_root(),ag_pd_pages_dir(),ag_pd_assets_dir()) as $dir) if(!$dir || (!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir))) throw new RuntimeException('Page Designer storage is unavailable.');
}
function ag_pd_page_file($id) { return is_string($id) && preg_match('/^[a-f0-9]{16}$/',$id) && ag_pd_pages_dir() ? ag_pd_pages_dir().DIRECTORY_SEPARATOR.$id.'.json' : null; }
function ag_pd_load_by_id($id) {
    $file=ag_pd_page_file($id); if(!$file || !is_file($file)) return null;
    $raw=@file_get_contents($file); if($raw===false) throw new RuntimeException('Page could not be read.');
    $p=json_decode($raw,true); if(!is_array($p)) throw new RuntimeException('Page JSON is damaged: '.$id);
    if(($p['id'] ?? '') !== $id) throw new RuntimeException('Page identifier does not match its file.');
    return $p;
}
function ag_pd_list_pages() {
    ag_pd_ensure_storage(); $out=array(); foreach(glob(ag_pd_pages_dir().DIRECTORY_SEPARATOR.'*.json') ?: array() as $f) {
        $id=basename($f,'.json'); if(!ag_pd_page_file($id)) continue; $p=ag_pd_load_by_id($id); if($p) $out[]=$p;
    }
    usort($out,function($a,$b){return strcasecmp($a['title'] ?? '',$b['title'] ?? '');}); return $out;
}
function ag_pd_load_by_slug($slug) { $slug=ag_pd_slug($slug); if(!$slug) return null; foreach(ag_pd_list_pages() as $p) if(($p['slug'] ?? '')===$slug) return $p; return null; }
function ag_pd_slug_in_use($slug,$ignoreId='') { foreach(array_merge(ag_pd_list_pages(),ag_pd_editor_pages()) as $p) if(($p['id'] ?? '')!==$ignoreId && ($p['slug'] ?? '')===ag_pd_slug($slug)) return true; return false; }
function ag_pd_revision($p) { return $p ? hash('sha256',json_encode($p,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) : ''; }
function ag_pd_lock($callback) {
    ag_pd_ensure_storage(); $lock=@fopen(ag_pd_root().DIRECTORY_SEPARATOR.'.write.lock','c');
    if(!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Could not lock Page Designer storage.');
    try { return $callback(); } finally { flock($lock,LOCK_UN); fclose($lock); }
}
function ag_pd_save_page($p) {
    ag_pd_ensure_storage(); $file=ag_pd_page_file($p['id'] ?? ''); if(!$file) throw new RuntimeException('Invalid page identifier.');
    $json=json_encode($p,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if($json===false) throw new RuntimeException('Page could not be encoded.');
    $tmp=tempnam(ag_pd_pages_dir(),'.save-'); if(!$tmp) throw new RuntimeException('Could not create page transaction.');
    try {
        if(file_put_contents($tmp,$json,LOCK_EX)!==strlen($json)) throw new RuntimeException('Could not write page transaction.');
        if(!@rename($tmp,$file)) throw new RuntimeException('Could not commit page transaction.');
    } finally { if(is_file($tmp)) @unlink($tmp); }
}
function ag_pd_asset_dir_for_page($id,$create=false) {
    if(!ag_pd_page_file($id)) return null; $dir=ag_pd_assets_dir().DIRECTORY_SEPARATOR.$id;
    if($create && !is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Could not create image folder.'); return $dir;
}
function ag_pd_asset_path($id,$file) { $dir=ag_pd_asset_dir_for_page($id); return $dir && ag_pd_image_name($file) && is_file($dir.DIRECTORY_SEPARATOR.$file) ? $dir.DIRECTORY_SEPARATOR.$file : null; }
function ag_pd_asset_url($id,$file) { return ag_pd_page_file($id) && ag_pd_image_name($file) ? '/Other/custom-page-asset.php?page='.rawurlencode($id).'&file='.rawurlencode($file) : ''; }
function ag_pd_store_image_upload($upload,$id,$previous='') {
    if(!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return $previous;
    if(($upload['error'] ?? -1)!==UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed or exceeds the server limit.');
    $tmp=$upload['tmp_name'] ?? ''; $size=is_file($tmp) ? filesize($tmp) : 0;
    if(!is_uploaded_file($tmp) || $size<1 || $size>8388608) throw new RuntimeException('Each image must be a valid upload of 8 MB or smaller.');
    $info=@getimagesize($tmp); $types=array('image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp');
    if(!$info || !isset($types[$info['mime'] ?? ''])) throw new RuntimeException('Only JPG, PNG, GIF and WebP images are allowed.');
    $name=bin2hex(random_bytes(12)).'.'.$types[$info['mime']]; $dir=ag_pd_asset_dir_for_page($id,true);
    if(!move_uploaded_file($tmp,$dir.DIRECTORY_SEPARATOR.$name)) throw new RuntimeException('Could not store image.'); return $name;
}
function ag_pd_commit($input,$files,$revision,$mode='compat') {
    if(!in_array($mode,array('compat','draft','publish'),true)) throw new RuntimeException('Invalid save mode.');
    return ag_pd_lock(function() use($input,$files,$revision,$mode) {
        $id=$input['id'] ?? ''; $existing=$id!=='' ? ($mode==='compat'?ag_pd_load_by_id($id):ag_pd_edit_page($id)) : null;
        if($id!=='' && !$existing) throw new RuntimeException('This page no longer exists. Reload before saving.');
        if(!hash_equals($mode==='compat'?ag_pd_revision($existing):($existing?ag_pd_editor_revision($id):''),(string)$revision)) throw new RuntimeException('This page changed in another editor. Reload before saving. Your edits are still here.');
        $p=ag_pd_normalize($input,$existing);
        if(array_key_exists('builder',$input) && $input['builder']===null) unset($p['builder']);
        if($p['title']==='' || $p['slug']==='') throw new RuntimeException('A page title and URL slug are required.');
        $p['id']=$existing ? $id : bin2hex(random_bytes(8));
        if(ag_pd_slug_in_use($p['slug'],$p['id'])) throw new RuntimeException('That page URL slug is already in use.');
        if($mode==='draft') $p['published']=false; elseif($mode==='publish') $p['published']=true;
        $p['created']=$existing['created'] ?? time(); $p['updated']=time();
        // Image references must belong to the saved page. New files are accepted only as uploads.
        $allowed=array(); if($existing) { $allowed=array_merge($allowed,ag_pd_page_images($existing)); $allowed[]=$existing['backgroundImage'] ?? ''; foreach($existing['cards'] ?? array() as $c) $allowed[]=$c['image'] ?? ''; if(isset($existing['builder'])) $allowed=array_merge($allowed,ag_pb_images($existing['builder'])); }
        if($existing && $mode!=='compat') { $live=ag_pd_load_by_id($id); if($live) $allowed=array_merge($allowed,ag_pd_page_images($live)); $allowed=array_merge($allowed,ag_pd_history_images($id)); }
        $added=array();
        try {
            if($p['backgroundImage']!=='' && !in_array($p['backgroundImage'],$allowed,true)) throw new RuntimeException('Unknown background image.');
            $old=$p['backgroundImage']; $p['backgroundImage']=ag_pd_store_image_upload($files['background_image'] ?? null,$p['id'],$old); if($old!==$p['backgroundImage']) $added[]=$p['backgroundImage'];
            foreach($p['cards'] as &$c) {
                if($c['image']!=='' && !in_array($c['image'],$allowed,true)) throw new RuntimeException('Unknown card image.');
                $old=$c['image']; $c['image']=ag_pd_store_image_upload($files['image_'.$c['id']] ?? null,$p['id'],$old); if($old!==$c['image']) $added[]=$c['image'];
            } unset($c);
            if(isset($p['builder'])) ag_pb_uploads($p['builder'],$files,$p['id'],$allowed,$added);
            if(!empty($p['seo']['image'])&&!in_array($p['seo']['image'],$allowed,true))throw new RuntimeException('Unknown sharing image. Choose a saved image belonging to this page.');
            if($mode==='compat') ag_pd_save_page($p); else ag_pd_store_edition($p,$mode,$existing); return $p;
        } catch(Throwable $e) { foreach($added as $name) { $file=ag_pd_asset_path($p['id'],$name); if($file) @unlink($file); } throw $e; }
    });
}
function ag_pd_delete_page($page) {
    $id=$page['id'] ?? ''; $file=ag_pd_page_file($id); $draft=ag_pd_draft_file($id);
    if(!$file || (!is_file($file) && !is_file($draft))) throw new RuntimeException('Page no longer exists.');
    $dir=ag_pd_root().DIRECTORY_SEPARATOR.'trash'; if(!is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('Could not create recovery folder.');
    $moved=array(); try {
        foreach(array($file,$draft) as $source) if(is_file($source)) {
            $destination=$dir.DIRECTORY_SEPARATOR.$id.($source===$draft?'-draft':'').'-'.bin2hex(random_bytes(6)).'.json';
            if(!rename($source,$destination)) throw new RuntimeException('Could not delete page.'); $moved[$source]=$destination;
        }
    } catch(Throwable $e) { foreach($moved as $source=>$destination) rename($destination,$source); throw $e; }
    // Keep assets and history for recovery. Public routes reject deleted pages.
}
function ag_pd_can_view($p,$member,$admin,$preview=false,$asset=false) {
    if(empty($p['published']) && !($admin && ($preview || $asset))) return 404;
    if(($p['access'] ?? 'public')==='members' && !$member) return 403;
    if(($p['access'] ?? 'public')==='admin' && !$admin) return 403;
    return 200;
}
require_once __DIR__.'/lifecycle.php';

require_once __DIR__.'/library.php';
