<?php
// Independent, portable library assets. Inserting makes an editable copy.
function ag_pbl_dir($kind) { return in_array($kind,array('section','template','media'),true)?ag_pd_root().'/library/'.$kind:null; }
function ag_pbl_file($kind,$id) { return ag_pbl_dir($kind) && is_string($id) && preg_match('/^[a-f0-9]{16}$/',$id)?ag_pbl_dir($kind).'/'.$id.'.json':null; }
function ag_pbl_get($kind,$id,$archived=false) {
    $entry=ag_pd_read_document(ag_pbl_file($kind,$id));
    if(!$entry || ($entry['id'] ?? '')!==$id || (!$archived && !empty($entry['archived']))) throw new RuntimeException('Library item not found.'); return $entry;
}
function ag_pbl_list($kind,$archived=false) {
    if(!ag_pbl_dir($kind))throw new RuntimeException('Invalid library kind.');
    $out=array(); foreach(glob(ag_pbl_dir($kind).'/*.json') ?: array() as $file) { $id=basename($file,'.json'); if(!ag_pbl_file($kind,$id))continue; $e=ag_pbl_get($kind,$id,true); if((bool)!empty($e['archived'])===(bool)$archived) $out[]=$e; }
    usort($out,function($a,$b){return strcasecmp($a['name'],$b['name']);}); return $out;
}
function ag_pbl_path($kind,$id,$name) { return ag_pbl_file($kind,$id) && ag_pd_image_name($name)?ag_pbl_dir($kind).'/assets/'.$id.'/'.$name:null; }
function ag_pbl_url($kind,$id,$name,$page='') { return '/Other/custom-page-library-asset.php?kind='.rawurlencode($kind).'&id='.rawurlencode($id).'&file='.rawurlencode($name).($page?'&page='.rawurlencode($page):''); }
function ag_pbl_image_bytes($bytes) {
    if(!is_string($bytes) || strlen($bytes)<1 || strlen($bytes)>8388608) throw new RuntimeException('Images must be 8 MB or smaller.');
    $info=@getimagesizefromstring($bytes); $types=array('image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp');
    if(!$info || !isset($types[$info['mime'] ?? ''])) throw new RuntimeException('Choose a JPG, PNG, GIF or WebP image.');
    return array('mime'=>$info['mime'],'extension'=>$types[$info['mime']],'width'=>$info[0],'height'=>$info[1],'size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes));
}
function ag_pbl_uploaded_bytes($upload) {
    if(!$upload || ($upload['error'] ?? -1)!==UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) throw new RuntimeException('Image upload failed or exceeds the server limit.');
    if(filesize($upload['tmp_name'])>8388608) throw new RuntimeException('Images must be 8 MB or smaller.'); return file_get_contents($upload['tmp_name']);
}
function ag_pbl_page_names($id) {
    $p=ag_pd_edit_page($id); if(!$p) throw new RuntimeException('Source page not found.'); $out=ag_pd_page_images($p); $live=ag_pd_load_by_id($id);
    if($live) $out=array_merge($out,ag_pd_page_images($live)); return array_unique(array_merge($out,ag_pd_history_images($id)));
}
function ag_pbl_asset($kind,$id,$name) {
    if(!ag_pd_image_name($name)) throw new RuntimeException('Image not found.');
    if($kind==='page') { if(!in_array($name,ag_pbl_page_names($id),true)) throw new RuntimeException('Image not found.'); $path=ag_pd_asset_path($id,$name); }
    else { $e=ag_pbl_get($kind,$id,true); $names=$kind==='media'?array($e['file']):array_keys($e['assets']); if(!in_array($name,$names,true)) throw new RuntimeException('Image not found.'); $path=ag_pbl_path($kind,$id,$name); }
    if(!$path || !is_file($path)) throw new RuntimeException('Image not found.'); return $path;
}
function ag_pbl_write_image($kind,$id,$bytes,&$added) {
    $meta=ag_pbl_image_bytes($bytes); $name=bin2hex(random_bytes(12)).'.'.$meta['extension']; $path=ag_pbl_path($kind,$id,$name); $dir=dirname($path);
    if(!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Could not create library image folder.');
    $added[]=$path; if(file_put_contents($path,$bytes,LOCK_EX)!==strlen($bytes)) throw new RuntimeException('Could not store library image.'); return array($name,$meta);
}
function ag_pbl_find_node($nodes,$id) { foreach($nodes as $node) { if($node['id']===$id)return $node; $found=ag_pbl_find_node($node['children'],$id); if($found)return $found; } return null; }
function ag_pbl_capture($kind,$input,$files,$nodeId,$name,$revision) {
    if(!in_array($kind,array('section','template'),true)) throw new RuntimeException('Invalid library kind.');
    $name=ag_pd_text($name,80); if($name==='') throw new RuntimeException('Give this library item a name.');
    return ag_pd_lock(function()use($kind,$input,$files,$nodeId,$name,$revision){
        $p=ag_pd_normalize($input); $source=$p['id'];
        if($source && (!ag_pd_edit_page($source) || !hash_equals(ag_pd_editor_revision($source),(string)$revision))) throw new RuntimeException('Source page changed. Reload before saving to the library.');
        if(!isset($p['builder'])) throw new RuntimeException('Convert this page to native elements first.');
        $nodes=$p['builder']['blocks']; if($kind==='section') { $node=ag_pbl_find_node($nodes,$nodeId); if(!$node)throw new RuntimeException('Select an element to save.'); $nodes=array($node); }
        $hasLegacy=false; $inspect=function($nodes)use(&$inspect,&$hasLegacy){foreach($nodes as $n){if($n['type']==='legacy')$hasLegacy=true;$inspect($n['children']);}}; $inspect($nodes);
        if($hasLegacy) throw new RuntimeException('Convert existing-page sections to native elements before saving this item.');
        $id=bin2hex(random_bytes(8)); $assets=array(); $added=array(); $allowed=$source?ag_pbl_page_names($source):array(); $copies=array(); $total=0;
        try {
            $capture=function($old,$upload)use($kind,$id,$source,$allowed,&$copies,&$assets,&$added,&$total){
                if($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) $bytes=ag_pbl_uploaded_bytes($upload);
                elseif($old==='')return '';
                else { if(!in_array($old,$allowed,true))throw new RuntimeException('Unknown source image.'); $path=ag_pd_asset_path($source,$old);if(!$path)throw new RuntimeException('A source image is missing.');$bytes=file_get_contents($path); }
                $hash=hash('sha256',$bytes); if(isset($copies[$hash]))return $copies[$hash];
                if(($total+=strlen($bytes))>33554432)throw new RuntimeException('Library items support up to 32 MB of images.');
                list($file,$meta)=ag_pbl_write_image($kind,$id,$bytes,$added);$assets[$file]=$meta;$copies[$hash]=$file;return $file;
            };
            $walk=function(&$nodes)use(&$walk,$files,$capture){foreach($nodes as &$n){if(isset($n['props']['image']) || isset($files['block_image_'.$n['id']]))$n['props']['image']=$capture($n['props']['image'] ?? '',$files['block_image_'.$n['id']] ?? null);$walk($n['children']);}unset($n);}; $walk($nodes);
            $e=array('id'=>$id,'name'=>$name,'created'=>time(),'updated'=>microtime(true),'archived'=>false,'assets'=>$assets);
            if($kind==='section')$e['block']=$nodes[0];
            else { $theme=$p['builder']['theme'];$theme['backgroundImage']=$capture($theme['backgroundImage'],$files['builder_background_image'] ?? null);$e['builder']=array('version'=>1,'theme'=>$theme,'blocks'=>$nodes);if(isset($p['builder']['site']))$e['builder']['site']=$p['builder']['site'];if(isset($p['builder']['tokens']))$e['builder']['tokens']=$p['builder']['tokens'];if(isset($p['builder']['interactions']))$e['builder']['interactions']=$p['builder']['interactions']; }
            ag_pd_write_document(ag_pbl_file($kind,$id),$e); return $e;
        } catch(Throwable $e){foreach($added as $path)@unlink($path);throw $e;}
    });
}
function ag_pbl_edit($kind,$id,$revision,$action,$name='') {
    return ag_pd_lock(function()use($kind,$id,$revision,$action,$name){
        $e=ag_pbl_get($kind,$id,true);if(!hash_equals(ag_pd_revision($e),(string)$revision))throw new RuntimeException('Library item changed. Refresh and retry.');
        if($action==='rename'){ $name=ag_pd_text($name,80);if(!$name)throw new RuntimeException('A name is required.');$e['name']=$name; }
        elseif($action==='archive' || $action==='restore'){
            if($kind==='section' && $action==='archive')foreach(array(ag_pbl_site_live(),ag_pbl_site_edit()) as $site)if(in_array($id,array($site['header'],$site['footer']),true))throw new RuntimeException('This section is assigned as a site header or footer. Choose another section first.');
            $e['archived']=$action==='archive';
        } else throw new RuntimeException('Unknown library action.');
        $e['updated']=microtime(true);ag_pd_write_document(ag_pbl_file($kind,$id),$e);return $e;
    });
}
function ag_pbl_media_add($upload,$source,$file,$name='') {
    return ag_pd_lock(function()use($upload,$source,$file,$name){
        $bytes=$upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE?ag_pbl_uploaded_bytes($upload):file_get_contents(ag_pbl_asset('page',$source,$file));
        $meta=ag_pbl_image_bytes($bytes);
        foreach(array_merge(ag_pbl_list('media'),ag_pbl_list('media',true)) as $e)if($e['sha256']===$meta['sha256']) { if(!empty($e['archived'])){$e['archived']=false;$e['updated']=microtime(true);ag_pd_write_document(ag_pbl_file('media',$e['id']),$e);}return $e; }
        $id=bin2hex(random_bytes(8));$added=array();try{list($file,$meta)=ag_pbl_write_image('media',$id,$bytes,$added);$label=ag_pd_text($name ?: ($upload['name'] ?? 'Saved image'),80);$e=array_merge($meta,array('id'=>$id,'file'=>$file,'name'=>$label ?: 'Saved image','archived'=>false,'created'=>time(),'updated'=>microtime(true)));ag_pd_write_document(ag_pbl_file('media',$id),$e);return $e;}catch(Throwable $e){foreach($added as $path)@unlink($path);throw $e;}
    });
}
function ag_pbl_media_list() {
    $items=array();
    $add=function($kind,$id,$name,$label,$usage,$entry=null)use(&$items){
        $path=$kind==='page'?ag_pd_asset_path($id,$name):ag_pbl_path($kind,$id,$name);if(!$path || !is_file($path))return;
        $info=@getimagesize($path);if(!$info || !in_array($info['mime'] ?? '',array('image/jpeg','image/png','image/gif','image/webp'),true))return;
        $hash=hash_file('sha256',$path);$source=array('kind'=>$kind,'id'=>$id,'file'=>$name,'url'=>ag_pbl_url($kind,$id,$name));
        if(!isset($items[$hash]))$items[$hash]=array('hash'=>$hash,'name'=>$label,'mime'=>$info['mime'],'width'=>$info[0],'height'=>$info[1],'size'=>filesize($path),'sources'=>array(),'uses'=>array(),'library'=>null);
        $key=$kind.':'.$id.':'.$name;$items[$hash]['sources'][$key]=$source;
        if($usage)$items[$hash]['uses'][$usage['key']]=$usage;
        if($entry){$items[$hash]['name']=$entry['name'];$items[$hash]['library']=array('id'=>$entry['id'],'revision'=>ag_pd_revision($entry));}
    };
    foreach(ag_pd_editor_pages() as $page){$id=$page['id'];foreach(array('Published page'=>ag_pd_load_by_id($id),'Saved draft'=>ag_pd_load_draft($id)) as $stage=>$p)if($p)foreach(ag_pd_page_images($p) as $name)$add('page',$id,$name,'Image from '.$page['title'],array('key'=>$id.':'.$stage,'pageId'=>$id,'label'=>$page['title'].' · '.$stage));foreach(ag_pd_history_images($id) as $name)$add('page',$id,$name,'Image from '.$page['title'],array('key'=>$id.':history','pageId'=>$id,'label'=>$page['title'].' · Saved revisions'));}
    $site=ag_pbl_site_live();foreach(ag_pd_editor_pages() as $summary){$p=ag_pd_load_by_id($summary['id']);if(!$p)continue;foreach(array('header','footer') as $role)if(!empty($p['builder']['site'][$role])&&!empty($site[$role])){try{$entry=ag_pbl_get('section',$site[$role]);foreach(array_keys($entry['assets']) as $name)$add('section',$entry['id'],$name,'Image from '.$entry['name'],array('key'=>$p['id'].':shared:'.$role,'pageId'=>$p['id'],'label'=>$p['title'].' · Shared '.$role));}catch(Throwable $e){error_log($e->getMessage());}}}
    foreach(array('section','template') as $kind)foreach(ag_pbl_list($kind) as $e)foreach(array_keys($e['assets']) as $name)$add($kind,$e['id'],$name,'Image from '.$e['name'],array('key'=>$kind.':'.$e['id'],'label'=>$e['name'].' · Saved '.$kind));
    foreach(ag_pbl_list('media') as $e)$add('media',$e['id'],$e['file'],$e['name'],null,$e);
    foreach($items as &$item){$item['sources']=array_values($item['sources']);$item['uses']=array_values($item['uses']);$item['url']=$item['sources'][0]['url'];}unset($item);
    $items=array_values($items);usort($items,function($a,$b){return strcasecmp($a['name'],$b['name']);});return $items;
}
function ag_pbl_summary($kind,$archived=false) {
    $out=array();foreach(ag_pbl_list($kind,$archived) as $e){$count=0;$walk=function($nodes)use(&$walk,&$count){foreach($nodes as $n){$count++;$walk($n['children']);}};$walk($kind==='section'?array($e['block']):$e['builder']['blocks']);$out[]=array('id'=>$e['id'],'name'=>$e['name'],'revision'=>ag_pd_revision($e),'archived'=>!empty($e['archived']),'elements'=>$count,'images'=>count($e['assets']));}return $out;
}
function ag_pbl_download($kind,$id) { $e=ag_pbl_get($kind,$id);foreach($e['assets'] as $file=>&$asset)$asset['url']=ag_pbl_url($kind,$id,$file);unset($asset);return $e; }
function ag_pbl_preview($kind,$id,$theme) {
    $e=ag_pbl_get($kind,$id,true);$p=ag_pd_default_page();$p['id']=$id;$p['title']=$e['name'];$p['builder']=$kind==='template'?$e['builder']:array('version'=>1,'theme'=>ag_pb_theme($theme),'blocks'=>array($e['block']));unset($p['builder']['site']);
    return str_replace('/Other/custom-page-asset.php?page='.$id, '/Other/custom-page-library-asset.php?kind='.$kind.'&id='.$id,ag_pd_render($p,true));
}
function ag_pbl_site_defaults() { return array('version'=>1,'theme'=>array_intersect_key(ag_pb_theme(),array_flip(array('background','surface','text','muted','accent','font','width','radius'))),'styles'=>array('headingSize'=>36,'textSize'=>17,'sectionSpacing'=>64,'gap'=>24,'buttonRadius'=>16,'buttonX'=>23,'buttonY'=>13),'navigation'=>array(),'header'=>'','footer'=>''); }
function ag_pbl_site_normalize($raw) {
    if(!is_array($raw))throw new RuntimeException('Invalid site settings.');$d=ag_pbl_site_defaults();$out=$d;$out['theme']=array_intersect_key(ag_pb_theme($raw['theme'] ?? array()),$d['theme']);
    foreach(array('headingSize'=>array(8,160),'textSize'=>array(8,64),'sectionSpacing'=>array(0,200),'gap'=>array(0,120),'buttonRadius'=>array(0,64),'buttonX'=>array(0,100),'buttonY'=>array(0,100)) as $key=>$range)$out['styles'][$key]=ag_pd_int($raw['styles'][$key] ?? $d['styles'][$key],$d['styles'][$key],$range[0],$range[1]);
    $out['navigation']=ag_pb_menu_items($raw['navigation'] ?? array());foreach(array('header','footer') as $key){$id=$raw[$key] ?? '';if($id!=='')ag_pbl_get('section',$id);$out[$key]=$id;}return $out;
}
function ag_pbl_site_live() { return ag_pd_read_document(ag_pd_root().'/site.json') ?: ag_pbl_site_defaults(); }
function ag_pbl_site_edit() { return ag_pd_read_document(ag_pd_root().'/site-draft.json') ?: ag_pbl_site_live(); }
function ag_pbl_site_revision() { $live=ag_pd_read_document(ag_pd_root().'/site.json');$draft=ag_pd_read_document(ag_pd_root().'/site-draft.json');return $live || $draft?hash('sha256',ag_pd_revision($live).':'.ag_pd_revision($draft)):''; }
function ag_pbl_site_save($raw,$revision,$publish=false) {
    return ag_pd_lock(function()use($raw,$revision,$publish){if(!hash_equals(ag_pbl_site_revision(),(string)$revision))throw new RuntimeException('Site settings changed in another editor. Refresh and retry.');$site=ag_pbl_site_normalize($raw);if($publish){$token=gmdate('YmdHis').'-'.bin2hex(random_bytes(6));ag_pd_write_document(ag_pd_root().'/site-history/'.$token.'.json',array('saved'=>microtime(true),'before'=>ag_pbl_site_live(),'after'=>$site));try{ag_pd_write_document(ag_pd_root().'/site.json',$site);}catch(Throwable $e){@unlink(ag_pd_root().'/site-history/'.$token.'.json');throw $e;}if(is_file(ag_pd_root().'/site-draft.json'))@unlink(ag_pd_root().'/site-draft.json');}else ag_pd_write_document(ag_pd_root().'/site-draft.json',$site);return $site;});
}
function ag_pbl_site_css($site) { $s=$site['styles'];return 'body{--wb-heading-size:'.$s['headingSize'].'px;--wb-text-size:'.$s['textSize'].'px;--wb-section-space:'.$s['sectionSpacing'].'px;--wb-gap:'.$s['gap'].'px;--wb-button-radius:'.$s['buttonRadius'].'px;--wb-button-x:'.$s['buttonX'].'px;--wb-button-y:'.$s['buttonY'].'px}'; }
function ag_pbl_render_nodes($entry,$role,$site,&$seen) {
    $nodes=array($entry['block']);$walk=function(&$nodes)use(&$walk,&$seen,$entry,$role,$site){foreach($nodes as &$n){$salt=0;do{$id=substr(hash('sha256',$role.$entry['id'].$n['id'].$salt++),0,12);}while(isset($seen[$id]));$seen[$id]=true;$n['id']=$id;if(!empty($n['props']['useSiteMenu'])){$n['props']['items']=$site['navigation'];$n['props']['useSiteMenu']=false;}$walk($n['children']);}unset($n);};$walk($nodes);return $nodes;
}
function ag_pbl_fragment($entry,$page,$role,$site,$seen=array()) {
    $nodes=ag_pbl_render_nodes($entry,$role,$site,$seen);$renderPage=$page;$renderPage['_formPageId']=$page['_formPageId'] ?? $page['id'];$renderPage['id']=$entry['id'];
    $html=ag_pb_nodes($nodes,$renderPage);$html=str_replace('/Other/custom-page-asset.php?page='.$entry['id'], '/Other/custom-page-library-asset.php?kind=section&id='.$entry['id'].(!empty($page['id'])?'&page='.$page['id']:''),$html);
    return '<div data-global-section="'.$role.'"'.($role==='header'&&strpos($html,'data-sticky="1"')!==false?' class="wb-sticky-header"':'').'><style>'.ag_pb_device_css($nodes).'</style>'.$html.'</div>';
}
function ag_pbl_shared_html($page,$builder,$site) {
    $out=array('header'=>'','footer'=>'');$seen=array();$walk=function($nodes)use(&$walk,&$seen){foreach($nodes as $n){$seen[$n['id']]=true;$walk($n['children']);}};$walk($builder['blocks']);
    foreach(array('header','footer') as $role)if(!empty($builder['site'][$role]) && !empty($site[$role])){try{$e=ag_pbl_get('section',$site[$role]);$out[$role]=ag_pbl_fragment($e,$page,$role,$site,$seen);}catch(Throwable $e){error_log('Page Designer shared section: '.$e->getMessage());}}return $out;
}
function ag_pbl_site_preview($raw) {
    $site=ag_pbl_site_normalize($raw);$p=ag_pd_default_page();$p['title']='Site style preview';$node=function($type,$props,$children=array()){return array('id'=>bin2hex(random_bytes(6)),'type'=>$type,'name'=>$type,'props'=>$props,'style'=>array(),'children'=>$children);};
    $p['builder']=array('version'=>1,'theme'=>ag_pb_theme($site['theme']),'blocks'=>array($node('navigation',array('useGridName'=>true,'items'=>$site['navigation'])),$node('section',array('anchor'=>'welcome'),array($node('heading',array('text'=>'Your site, together.','tag'=>'h1')),$node('text',array('text'=>'Shared fonts, colours, spacing and buttons follow the published site defaults.')),$node('button',array('label'=>'Example button','url'=>'#welcome'))))));
    $html=ag_pd_render($p,true);$shared=ag_pbl_shared_html($p,array('site'=>array('header'=>true,'footer'=>true),'blocks'=>$p['builder']['blocks']),$site);
    $html=str_replace('</style></head>',ag_pbl_site_css($site).'</style></head>',$html);$html=str_replace('<main class="wb-page">','<main class="wb-page">'.$shared['header'],$html);return str_replace('</main>',$shared['footer'].'</main>',$html);
}
// Portable page exports snapshot shared content without changing the saved page.
function ag_pbl_export_snapshot(&$page,&$paths) {
    if(empty($page['builder']))return;$builder=&$page['builder'];$site=ag_pbl_site_live();$seen=array();
    $walk=function($nodes)use(&$walk,&$seen){foreach($nodes as $n){$seen[$n['id']]=true;$walk($n['children']);}};$walk($builder['blocks']);
    if(!empty($builder['site']['styles'])){$builder['theme']=array_merge($builder['theme'],$site['theme']);$builder['tokens']=$site['styles'];}
    foreach(array('header','footer') as $role)if(!empty($builder['site'][$role])&&!empty($site[$role])) {
        $entry=ag_pbl_get('section',$site[$role]);$nodes=array($entry['block']);$mapping=array();
        foreach($entry['assets'] as $file=>$meta){$name=bin2hex(random_bytes(12)).'.'.$meta['extension'];$paths[$name]=ag_pbl_asset('section',$entry['id'],$file);$mapping[$file]=$name;}
        $clone=function(&$nodes)use(&$clone,&$seen,$mapping){foreach($nodes as &$n){do{$id=bin2hex(random_bytes(6));}while(isset($seen[$id]));$seen[$id]=true;$n['id']=$id;if(!empty($n['props']['image']))$n['props']['image']=$mapping[$n['props']['image']];$clone($n['children']);}unset($n);};$clone($nodes);
        if($role==='header')array_unshift($builder['blocks'],$nodes[0]);else $builder['blocks'][]=$nodes[0];
    }
    unset($builder['site']);$self=$page['id'];
    $links=function(&$items)use(&$links,$self){foreach($items as &$item){if(!empty($item['pageId'])){$linked=ag_pd_load_by_id($item['pageId']);if($linked)$item['url']='/Other/custom-page.php?page='.rawurlencode($linked['slug']);if($item['pageId']!==$self)unset($item['pageId']);}if(!empty($item['children']))$links($item['children']);}unset($item);};
    $menus=function(&$nodes)use(&$menus,$links,$site){foreach($nodes as &$n){if(!empty($n['props']['useSiteMenu'])){$n['props']['items']=$site['navigation'];$n['props']['useSiteMenu']=false;}if(isset($n['props']['items']))$links($n['props']['items']);$menus($n['children']);}unset($n);};$menus($builder['blocks']);
    $page=ag_pd_normalize($page);
}

function ag_pd_editor_namespace(){return ag_pd_lock(function(){ $file=ag_pd_root().'/editor-instance.json';$doc=ag_pd_read_document($file);if(!$doc||!preg_match('/^[a-f0-9]{32}$/',$doc['id'] ?? '')){$doc=array('id'=>bin2hex(random_bytes(16)));ag_pd_write_document($file,$doc);} $session=ag_current_session();return hash('sha256',$doc['id'].':'.($session['principalId'] ?? $session['role'] ?? '')); });}
