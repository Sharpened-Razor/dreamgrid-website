<?php
// Drafts and history live outside the public web root. Existing page files stay live.
function ag_pd_draft_file($id) { return ag_pd_page_file($id) ? ag_pd_root().DIRECTORY_SEPARATOR.'drafts'.DIRECTORY_SEPARATOR.$id.'.json' : null; }
function ag_pd_read_document($file) {
    if(!$file || !is_file($file)) return null;
    $raw=file_get_contents($file); $p=json_decode($raw,true);
    if(!is_array($p)) throw new RuntimeException('Saved document could not be read.'); return $p;
}
function ag_pd_load_draft($id) {
    $p=ag_pd_read_document(ag_pd_draft_file($id));
    if($p && ($p['id'] ?? '')!==$id) throw new RuntimeException('Draft identifier does not match its file.'); return $p;
}
function ag_pd_edit_page($id) { return ag_pd_load_draft($id) ?: ag_pd_load_by_id($id); }
function ag_pd_editor_revision($id) {
    $live=ag_pd_load_by_id($id); $draft=ag_pd_load_draft($id);
    return $live || $draft ? hash('sha256',ag_pd_revision($live).':'.ag_pd_revision($draft)) : '';
}
function ag_pd_write_document($file,$p) {
    $dir=dirname($file); if(!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Could not create document folder.');
    $json=json_encode($p,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if($json===false) throw new RuntimeException('Could not encode document.');
    $tmp=tempnam($dir,'.transaction-'); if(!$tmp) throw new RuntimeException('Could not create document transaction.');
    try {
        if(file_put_contents($tmp,$json,LOCK_EX)!==strlen($json) || !@rename($tmp,$file)) throw new RuntimeException('Could not commit document transaction.');
    } finally { if(is_file($tmp)) @unlink($tmp); }
}
function ag_pd_editor_pages() {
    $out=array(); foreach(ag_pd_list_pages() as $p) $out[$p['id']]=$p;
    foreach(glob(ag_pd_root().'/drafts/*.json') ?: array() as $file) {
        $id=basename($file,'.json'); if(!ag_pd_page_file($id)) continue; $draft=ag_pd_load_draft($id);
        if($draft) $out[$id]=$draft;
    }
    foreach($out as $id=>&$p) { $live=ag_pd_load_by_id($id); $p['published']=!empty($live['published']); $p['hasDraft']=(bool)ag_pd_load_draft($id); } unset($p);
    $out=array_values($out); usort($out,function($a,$b){return strcasecmp($a['title'],$b['title']);}); return $out;
}
function ag_pd_publishing_state($id) {
    $live=ag_pd_load_by_id($id); return array('published'=>!empty($live['published']),'liveSlug'=>$live['slug'] ?? '', 'hasDraft'=>(bool)ag_pd_load_draft($id));
}
function ag_pd_page_images($p) {
    $names=array($p['backgroundImage'] ?? '',$p['seo']['image'] ?? ''); foreach($p['cards'] ?? array() as $c) $names[]=$c['image'] ?? '';
    if(isset($p['builder'])) $names=array_merge($names,ag_pb_images($p['builder']));
    return array_values(array_unique(array_filter($names,function($v){return ag_pd_image_name($v)!=='';})));
}
function ag_pd_history_dir($id) { return ag_pd_page_file($id) ? ag_pd_root().'/history/'.$id : null; }
function ag_pd_history_file($id,$token) { return ag_pd_history_dir($id) && is_string($token) && preg_match('/^[0-9]{14}-[a-f0-9]{12}$/',$token) ? ag_pd_history_dir($id).'/'.$token.'.json' : null; }
function ag_pd_history_entry($id,$token) {
    $entry=ag_pd_read_document(ag_pd_history_file($id,$token));
    if(!$entry || ($entry['page']['id'] ?? '')!==$id) throw new RuntimeException('Revision not found.'); return $entry;
}
function ag_pd_history_list($id) {
    if(!ag_pd_edit_page($id)) throw new RuntimeException('Page not found.');
    $items=array(); foreach(glob(ag_pd_history_dir($id).'/*.json') ?: array() as $file) {
        $token=basename($file,'.json'); if(!ag_pd_history_file($id,$token)) continue;
        $e=ag_pd_history_entry($id,$token); $items[]=array('token'=>$token,'saved'=>$e['saved'],'kind'=>$e['kind'],'title'=>$e['page']['title'],'legacy'=>!isset($e['page']['builder']));
    }
    usort($items,function($a,$b){return ($b['saved'] <=> $a['saved']) ?: strcmp($b['token'],$a['token']);}); return $items;
}
function ag_pd_record_revision($page,$kind) {
    $token=gmdate('YmdHis').'-'.bin2hex(random_bytes(6));
    ag_pd_write_document(ag_pd_history_file($page['id'],$token),array('saved'=>microtime(true),'kind'=>$kind,'page'=>$page)); return $token;
}
function ag_pd_history_images($id) {
    $out=array(); foreach(glob(ag_pd_history_dir($id).'/*.json') ?: array() as $file) {
        $e=ag_pd_read_document($file); if(($e['page']['id'] ?? '')===$id) $out=array_merge($out,ag_pd_page_images($e['page']));
    } return array_unique($out);
}
function ag_pd_store_edition($p,$mode,$existing) {
    $id=$p['id'];
    if($existing) {
        if(!glob(ag_pd_history_dir($id).'/*.json')) {
            $live=ag_pd_load_by_id($id); if($live) ag_pd_record_revision($live,'original');
            if(ag_pd_load_draft($id)) ag_pd_record_revision($existing,'draft');
        }
    }
    $token=ag_pd_record_revision($p,$mode==='draft'?'draft':'published');
    try {
    if($mode==='draft') ag_pd_write_document(ag_pd_draft_file($id),$p);
    else {
        // Commit live content first. An interrupted cleanup leaves a recoverable draft.
        ag_pd_save_page($p); $draft=ag_pd_draft_file($id); if(is_file($draft)) @unlink($draft);
    }
    } catch(Throwable $e) { @unlink(ag_pd_history_file($id,$token)); throw $e; }
}
function ag_pd_restore_revision($id,$token,$revision) {
    if(!ag_pd_edit_page($id)) throw new RuntimeException('Page not found.');
    $entry=ag_pd_history_entry($id,$token); $page=$entry['page']; if(!isset($page['builder'])) $page['builder']=null;
    return ag_pd_commit($page,array(),$revision,'draft');
}
function ag_pd_take_offline($id,$revision) {
    return ag_pd_lock(function() use($id,$revision) {
        if(!hash_equals(ag_pd_editor_revision($id),(string)$revision)) throw new RuntimeException('Page changed. Reload before taking it offline.');
        $live=ag_pd_load_by_id($id); if(!$live) throw new RuntimeException('There is no published page to take offline.');
        if(!glob(ag_pd_history_dir($id).'/*.json')) ag_pd_record_revision($live,'original');
        $live['published']=false; $live['updated']=time(); $token=ag_pd_record_revision($live,'offline');
        try { ag_pd_save_page($live); } catch(Throwable $e) { @unlink(ag_pd_history_file($id,$token)); throw $e; }
        return ag_pd_edit_page($id);
    });
}
function ag_pd_package_limit() { return 33554432; }
function ag_pd_remap_self_links(&$page,$oldSlug) {
    $replace=function($url) use($oldSlug,$page) {
        if(!is_string($url) || strpos($url,'/Other/custom-page.php?')!==0) return $url;
        $query=array(); parse_str(parse_url($url,PHP_URL_QUERY) ?: '',$query);
        if(($query['page'] ?? null)!==$oldSlug) return $url;
        return preg_replace_callback('/([?&]page=)[^&#]*/',function($match) use($page){return $match[1].rawurlencode($page['slug']);},$url);
    };
    foreach($page['cards'] as &$card) $card['linkUrl']=$replace($card['linkUrl']); unset($card);
    if(isset($page['builder'])) {
        $walk=function(&$nodes) use(&$walk,$replace) {
            foreach($nodes as &$node) {
                foreach(array('url','buttonUrl') as $key) if(isset($node['props'][$key])) $node['props'][$key]=$replace($node['props'][$key]);
                if(isset($node['props']['items'])) { $links=function(&$items)use(&$links,$replace){foreach($items as &$item){if(isset($item['url']))$item['url']=$replace($item['url']);if(!empty($item['children']))$links($item['children']);}unset($item);};$links($node['props']['items']); }
                $walk($node['children']);
            } unset($node);
        }; $walk($page['builder']['blocks']);
    }
}
function ag_pd_export_package($id,$revision) {
    return ag_pd_lock(function() use($id,$revision) {
        $p=ag_pd_edit_page($id); if(!$p) throw new RuntimeException('Save the page before exporting.');
        if(!hash_equals(ag_pd_editor_revision($id),(string)$revision)) throw new RuntimeException('Page changed. Reload before exporting.');
        $p=ag_pd_normalize($p); $assets=array(); $total=0; $paths=array();ag_pbl_export_snapshot($p,$paths);
        foreach(ag_pd_page_images($p) as $name) {
            $path=$paths[$name] ?? ag_pd_asset_path($id,$name); if(!$path) throw new RuntimeException('An image is missing. Remove or replace it before exporting.');
            $size=filesize($path); if($size>8388608 || ($total+=$size)>ag_pd_package_limit()*0.7) throw new RuntimeException('Page images exceed the 32 MB portable package limit.');
            $bytes=file_get_contents($path); $assets[$name]=array('sha256'=>hash('sha256',$bytes),'data'=>base64_encode($bytes));
        }
        $keys=ag_pd_default_page(); $keys['builder']=true; $keys['seo']=true; $p=array_intersect_key($p,$keys);
        $package=array('format'=>'dreamgrid-page','version'=>1,'page'=>$p,'assets'=>$assets);
        $source=ag_pd_read_document(ag_pd_root().'/template-sources/'.$id.'.json');if($source)$package['templateSource']=ag_ti_source($source);
        if(strlen(json_encode($package,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))>ag_pd_package_limit()) throw new RuntimeException('Page exceeds the 32 MB portable package limit.');
        return $package;
    });
}
function ag_pd_import_package($raw) {
    if(!is_string($raw) || strlen($raw)>ag_pd_package_limit()) throw new RuntimeException('Portable packages must be 32 MB or smaller.');
    $package=json_decode($raw,true);
    if(!is_array($package) || ($package['format'] ?? '')!=='dreamgrid-page' || ($package['version'] ?? null)!==1 || !is_array($package['page'] ?? null) || !is_array($package['assets'] ?? null)) throw new RuntimeException('Choose a valid DreamGrid page package.');
    $source=isset($package['templateSource'])?ag_ti_source($package['templateSource']):null;
    $keys=ag_pd_default_page(); $keys['builder']=true; $keys['seo']=true;
    $p=ag_pd_normalize(array_intersect_key($package['page'],$keys));
    if($p['title']==='' || $p['slug']==='') throw new RuntimeException('The package needs a page title.');
    $assets=$package['assets']; if(count($assets)>300) throw new RuntimeException('The package contains too many images.');
    $needed=ag_pd_page_images($p); if(count($assets)!==count($needed)) throw new RuntimeException('The package image list does not match its page.');
    $decoded=array(); $total=0; $types=array('image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp');
    foreach($needed as $name) {
        $asset=$assets[$name] ?? null;
        if(!is_array($asset) || !is_string($asset['data'] ?? null) || !is_string($asset['sha256'] ?? null)) throw new RuntimeException('A packaged image is missing.');
        $bytes=base64_decode($asset['data'],true); $size=is_string($bytes)?strlen($bytes):0;
        if($size<1 || $size>8388608 || ($total+=$size)>ag_pd_package_limit()) throw new RuntimeException('A packaged image is too large or invalid.');
        if(!hash_equals(hash('sha256',$bytes),$asset['sha256'])) throw new RuntimeException('Packaged image checksum failed.');
        $info=@getimagesizefromstring($bytes); $ext=$types[$info['mime'] ?? ''] ?? '';
        if(!$ext || substr($name,strrpos($name,'.')+1)!==$ext) throw new RuntimeException('A packaged image is not a supported image.');
        $decoded[$name]=$bytes;
    }
    return ag_pd_lock(function() use($p,$decoded,$source) {
        $sourceId=$p['id'];$p['id']=bin2hex(random_bytes(8));if(isset($p['builder'])){$links=function(&$items)use(&$links,$sourceId,$p){foreach($items as &$item){if(($item['pageId'] ?? '')===$sourceId)$item['pageId']=$p['id'];elseif(!empty($item['pageId']))unset($item['pageId']);if(!empty($item['children']))$links($item['children']);}unset($item);};$walk=function(&$nodes)use(&$walk,$links){foreach($nodes as &$n){if(isset($n['props']['items']))$links($n['props']['items']);$walk($n['children']);}unset($n);};$walk($p['builder']['blocks']);} $p['published']=false; $p['created']=time(); $p['updated']=time();
        $base=$p['slug']; $n=2; while(ag_pd_slug_in_use($p['slug'])) $p['slug']=substr($base,0,70).'-'.$n++;
        if($base!==$p['slug']) ag_pd_remap_self_links($p,$base);
        $mapping=array(); $added=array(); $token=null;
        try {
            foreach($decoded as $old=>$bytes) {
                $name=bin2hex(random_bytes(12)).substr($old,strrpos($old,'.')); $dir=ag_pd_asset_dir_for_page($p['id'],true); $file=$dir.DIRECTORY_SEPARATOR.$name;
                $added[]=$file; if(file_put_contents($file,$bytes,LOCK_EX)!==strlen($bytes)) throw new RuntimeException('Could not store an imported image.'); $mapping[$old]=$name;
            }
            $p['backgroundImage']=$mapping[$p['backgroundImage']] ?? '';
            foreach($p['cards'] as &$c) $c['image']=$mapping[$c['image']] ?? ''; unset($c);
            if(isset($p['seo']))$p['seo']['image']=$mapping[$p['seo']['image']] ?? '';
            if(isset($p['builder'])) {
                $p['builder']['theme']['backgroundImage']=$mapping[$p['builder']['theme']['backgroundImage']] ?? '';
                $walk=function(&$nodes) use(&$walk,$mapping){foreach($nodes as &$node){if(isset($node['props']['image']))$node['props']['image']=$mapping[$node['props']['image']] ?? ''; $walk($node['children']);}unset($node);}; $walk($p['builder']['blocks']);
            }
            if($source){$source['pageId']=$p['id'];$sourceFile=ag_pd_root().'/template-sources/'.$p['id'].'.json';$added[]=$sourceFile;ag_pd_write_document($sourceFile,$source);}
            $token=ag_pd_record_revision($p,'imported'); ag_pd_write_document(ag_pd_draft_file($p['id']),$p); return $p;
        } catch(Throwable $e) { if($token) @unlink(ag_pd_history_file($p['id'],$token)); foreach($added as $file) @unlink($file); throw $e; }
    });
}
function ag_pd_import_upload($upload) {
    if(!$upload || ($upload['error'] ?? -1)!==UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) throw new RuntimeException('Package upload failed or exceeds the server upload limit.');
    if(filesize($upload['tmp_name'])>ag_pd_package_limit()) throw new RuntimeException('Portable packages must be 32 MB or smaller.');
    return ag_pd_import_package(file_get_contents($upload['tmp_name']));
}
