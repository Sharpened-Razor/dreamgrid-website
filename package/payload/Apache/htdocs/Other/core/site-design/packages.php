<?php
function ag_sd_zip($files) {
    $body='';$directory='';$count=0;
    foreach($files as $name=>$bytes){
        $crc=crc32($bytes);$size=strlen($bytes);$offset=strlen($body);$length=strlen($name);
        $body.="PK\x03\x04".pack('vvvvvVVVvv',20,0,0,0,33,$crc,$size,$size,$length,0).$name.$bytes;
        $directory.="PK\x01\x02".pack('vvvvvvVVVvvvvvVV',20,20,0,0,0,33,$crc,$size,$size,$length,0,0,0,0,0,$offset).$name;$count++;
    }return $body.$directory."PK\x05\x06".pack('vvvvVVv',0,0,$count,$count,strlen($directory),strlen($body),0);
}
function ag_sd_preview_png($theme) {
    $theme=empty($theme['colors'])?ag_sa_presets()['safe']:ag_sa_theme($theme);$c=$theme['colors'];$row='';
    for($y=0;$y<130;$y++){$row.="\0";for($x=0;$x<240;$x++){$key=($x>10&&$x<50&&$y>10&&$y<120)||($x>60&&$x<230&&$y>35&&$y<95)?'surface':'background';if($x>60&&$x<130&&$y>105&&$y<120)$key='accent';$row.=hex2bin(substr($c[$key],1));}}
    $chunk=function($type,$bytes){return pack('N',strlen($bytes)).$type.$bytes.pack('N',crc32($type.$bytes));};
    return "\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',240,130,8,2,0,0,0)).$chunk('IDAT',gzcompress($row)).$chunk('IEND','');
}
function ag_sd_portable_value($value) {
    if(is_array($value)){foreach($value as $key=>&$item)$item=ag_sd_portable_value($item);unset($item);return $value;}
    if(!is_string($value))return $value;
    if(preg_match('#(^|[^a-z0-9])(?:[a-z]:[\\\\/]|\\\\\\\\)#i',$value))throw new RuntimeException('Remove machine-specific paths from design content before exporting.');
    $identity=ag_sd_identity();foreach(array('websiteUrl','loginAddress') as $key)if($identity[$key]!==''&&strpos($value,$identity[$key])===0&&preg_match('#^https?://#i',$value))return substr($value,strlen($identity[$key])) ?: '/Other/index.php';foreach(array('websiteUrl'=>'{{websiteUrl}}','loginAddress'=>'{{loginAddress}}','gridName'=>'{{gridName}}') as $key=>$token)if($identity[$key]!=='')$value=str_replace($identity[$key],$token,$value);
    return $value;
}
function ag_sd_package_export($name,$pageId,$scopes,$revision,$designId='') {
    $state=ag_sa_read();if(!is_string($revision)||!hash_equals(ag_sa_revision($state),$revision))throw new RuntimeException('Design changed. Reload before exporting.');
    $selected=ag_sd_scope_selection($scopes);$library=ag_sa_library($state);$themes=array();$files=array();$brand=$state['branding'];$brand['title']='';
    $design=$designId!==''?ag_sd_import_get($designId):null;if($design){$brand=$design['branding'];$brand['title']='';$pageId=$design['pageId'];}
    foreach($selected as $scope){$theme=$design?($design['themes'][$scope] ?? null):ag_sd_active_theme($state,$scope);if(!$theme)throw new RuntimeException('Package scope unavailable.');if(empty($theme['colors']))$theme=ag_sa_presets()['safe'];$themes[$scope]=ag_sa_theme($theme);}
    $page=null;if($pageId!==''){
        $package=ag_pd_export_package($pageId,ag_pd_editor_revision($pageId));unset($package['templateSource']);
        foreach($package['assets'] as $asset=>$data)$files['assets/'.$asset]=base64_decode($data['data'],true);
        $package['assets']=array_map(function($asset){return array('sha256'=>$asset['sha256']);},$package['assets']);
        $cleanForms=function(&$nodes)use(&$cleanForms){foreach($nodes as &$node){if($node['type']==='form'){$node['props']['recipient']='';$node['props']['handling']='local';}$cleanForms($node['children']);}unset($node);};$cleanForms($package['page']['builder']['blocks']);
        $package['page']['published']=false;$package['page']['showInMenu']=false;
        // Shared sections have already been snapshotted by the existing exporter.
        $page=ag_sd_portable_value($package);$files['front-page.json']=json_encode($page,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }
    $systemAssets=array();foreach($themes as &$theme){$art=$theme['styles']['artwork'];if($art!==''&&$theme['styles']['background']===''){$path=ag_sd_artwork_library()[$art]['path'];if(!is_file($path))throw new RuntimeException('System artwork is unavailable. Restore it before exporting.');$bytes=file_get_contents($path);ag_sd_image($bytes);$asset=substr(hash('sha256',$bytes),0,24).'.png';$files['assets/'.$asset]=$bytes;$systemAssets[$asset]=true;$theme['styles']['background']=$asset;$theme['styles']['artwork']='';}}unset($theme);
    $assetNames=array();foreach(array('logo','mark','background','favicon') as $key)if($brand[$key]!=='')$assetNames[]=$brand[$key];foreach($themes as $theme)if($theme['styles']['background']!=='')$assetNames[]=$theme['styles']['background'];
    foreach(array_unique($assetNames) as $asset){if(isset($systemAssets[$asset]))continue;$path=ag_sd_asset_path($asset);if(!$path)throw new RuntimeException('A design asset is missing.');$files['assets/'.$asset]=file_get_contents($path);}
    $files['theme.json']=json_encode(ag_sd_portable_value(array('scopes'=>$themes,'branding'=>$brand)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $files['preview.png']=ag_sd_preview_png(reset($themes));$hashes=array();$total=0;
    foreach($files as $file=>$bytes){if(!is_string($bytes)||strlen($bytes)>8388608||($total+=strlen($bytes))>25165824)throw new RuntimeException('Design packages support 24 MB of expanded content and 8 MB per file.');$hashes[$file]=hash('sha256',$bytes);}
    $files['manifest.json']=json_encode(array('format'=>'dreamgrid-design','version'=>1,'name'=>ag_sd_portable_value(ag_pd_text($name ?: 'My website design',120)),'files'=>$hashes),JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    $zip=ag_sd_zip($files);if(strlen($zip)>33554432)throw new RuntimeException('Design ZIP exceeds 32 MB.');return $zip;
}
function ag_sd_package_read($path) {
    $zip=new AgTemplateZip();$zip->open($path);$files=array();$total=0;$names=array();
    try {
        if($zip->numFiles>150)throw new RuntimeException('A design package supports 150 files.');
        for($i=0;$i<$zip->numFiles;$i++){
            $info=$zip->statIndex($i);$name=$info['name'];
            if(!preg_match('#^(manifest\.json|theme\.json|front-page\.json|preview\.png|assets/[a-f0-9]{24}\.(png|jpg|gif|webp))$#D',$name))throw new RuntimeException('Unexpected file type or unsafe path in design package.');
            if(isset($names[strtolower($name)]))throw new RuntimeException('Duplicate design-package file.');$names[strtolower($name)]=true;
            $os=0;$attrs=0;$zip->getExternalAttributesIndex($i,$os,$attrs);if((($attrs>>16)&0170000)===0120000)throw new RuntimeException('Symbolic links are not supported.');
            if(($total+=$info['size'])>25165824||($info['size']>1048576&&$info['size']/max(1,$info['comp_size'])>200))throw new RuntimeException('Design package exceeds expansion limits.');
            $files[$name]=$zip->getFromIndex($i);
        }
    }finally{$zip->close();}
    if(!isset($files['manifest.json'],$files['theme.json'],$files['preview.png'])||strlen($files['manifest.json'])>32768||strlen($files['theme.json'])>131072)throw new RuntimeException('Design package is missing required metadata or is oversized.');
    $m=json_decode($files['manifest.json'],true);if(!is_array($m)||array_diff(array_keys($m),array('format','version','name','files'))||($m['format'] ?? '')!=='dreamgrid-design'||($m['version'] ?? null)!==1||!is_string($m['name'] ?? null)||!is_array($m['files'] ?? null)||count($m['files'])!==count($files)-1)throw new RuntimeException('Invalid design manifest.');
    foreach($files as $name=>$bytes)if($name!=='manifest.json'){if(!is_string($m['files'][$name] ?? null)||!hash_equals(hash('sha256',$bytes),$m['files'][$name]))throw new RuntimeException('A design-package checksum failed.');if($name==='preview.png'||strpos($name,'assets/')===0){$info=ag_sd_image($bytes);if($info['extension']!==pathinfo($name,PATHINFO_EXTENSION))throw new RuntimeException('Asset contents do not match their file type.');}}
    $raw=json_decode($files['theme.json'],true);if(!is_array($raw)||array_diff(array_keys($raw),array('scopes','branding'))||!is_array($raw['scopes'] ?? null)||!$raw['scopes'])throw new RuntimeException('Invalid package theme settings.');
    $themes=array();foreach($raw['scopes'] as $scope=>$theme){if(!isset(ag_sd_scopes()[$scope]))throw new RuntimeException('Unsupported package scope.');$themes[$scope]=ag_sa_theme($theme);}
    $brand=ag_sd_brand($raw['branding'] ?? array());$needed=array();foreach(array('logo','mark','background','favicon') as $key)if($brand[$key]!=='')$needed[$brand[$key]]=true;foreach($themes as $theme)if($theme['styles']['background']!=='')$needed[$theme['styles']['background']]=true;
    if($brand['favicon']!==''){if(!isset($files['assets/'.$brand['favicon']]))throw new RuntimeException('Favicon asset is missing.');ag_sd_image($files['assets/'.$brand['favicon']],true);}
    $page=null;if(isset($files['front-page.json'])){
        if(strlen($files['front-page.json'])>2097152)throw new RuntimeException('Front-page layout exceeds 2 MB.');
        $page=json_decode($files['front-page.json'],true);if(!is_array($page)||array_diff(array_keys($page),array('format','version','page','assets'))||($page['format'] ?? '')!=='dreamgrid-page'||($page['version'] ?? null)!==1||!is_array($page['page'] ?? null)||!is_array($page['assets'] ?? null))throw new RuntimeException('Invalid front-page layout.');
        $keys=ag_pd_default_page();$keys['builder']=true;$keys['seo']=true;if(array_diff_key($page['page'],$keys))throw new RuntimeException('Unexpected data in front-page layout.');
        $p=ag_pd_normalize($page['page']);if($p['access']!=='public'||!isset($p['builder'])||ag_sa_login_count($p['builder']['blocks'])!==1)throw new RuntimeException('Packaged front pages require one visible native Member Login.');
        $cleanForms=function(&$nodes)use(&$cleanForms){foreach($nodes as &$node){if($node['type']==='form'){$node['props']['recipient']='';$node['props']['handling']='local';}$cleanForms($node['children']);}unset($node);};$cleanForms($p['builder']['blocks']);
        $refs=ag_pd_page_images($p);if(count($refs)!==count($page['assets']))throw new RuntimeException('Page assets do not match the layout.');
        foreach($refs as $name){$needed[$name]=true;if(!isset($files['assets/'.$name])||!is_array($page['assets'][$name] ?? null)||array_diff(array_keys($page['assets'][$name]),array('sha256'))||!is_string($page['assets'][$name]['sha256'] ?? null)||!hash_equals(hash('sha256',$files['assets/'.$name]),$page['assets'][$name]['sha256']))throw new RuntimeException('A page asset is missing or invalid.');$page['assets'][$name]['data']=base64_encode($files['assets/'.$name]);}
        $page['page']=$p;
    }
    foreach($needed as $name=>$yes)if(!isset($files['assets/'.$name]))throw new RuntimeException('A referenced design asset is missing.');
    foreach($files as $name=>$bytes)if(strpos($name,'assets/')===0&&!isset($needed[basename($name)]))throw new RuntimeException('Unreferenced package assets are not allowed.');
    // Machine paths are rejected on import too; receiving-grid bindings remain tokens.
    ag_sd_reject_paths($m['name']);ag_sd_reject_paths($raw);if($page)ag_sd_reject_paths($page['page']);
    return array('name'=>ag_pd_text($m['name'],120),'themes'=>$themes,'branding'=>$brand,'page'=>$page,'files'=>$files);
}
function ag_sd_reject_paths($data) {
    if(is_array($data)){foreach($data as $value)ag_sd_reject_paths($value);}
    elseif(is_string($data)&&preg_match('#(^|[^a-z0-9])(?:[a-z]:[\\\\/]|\\\\\\\\)#i',$data))throw new RuntimeException('Machine-specific paths are not allowed in design packages.');
}
function ag_sd_import_dir() { return dirname(ag_sa_file()).'/imported'; }
function ag_sd_import_get($id) { if(!is_string($id)||!preg_match('/^[a-f0-9]{16}$/D',$id))throw new RuntimeException('Invalid imported design.');$p=ag_pd_read_document(ag_sd_import_dir().'/'.$id.'.json');if(!$p)throw new RuntimeException('Imported design not found.');return $p; }
function ag_sd_import_list() { $out=array();foreach(glob(ag_sd_import_dir().'/*.json') ?: array() as $file)$out[]=ag_sd_import_get(basename($file,'.json'));return $out; }
function ag_sd_package_import($path) {
    $input=ag_sd_package_read($path);if(count(ag_sd_import_list())>=20)throw new RuntimeException('Keep no more than 20 imported design drafts.');
    $added=array();$page=null;
    try {
        $mapping=array();$brand=$input['branding'];$themeAssets=array();foreach(array('logo','mark','background','favicon') as $key)if($brand[$key]!=='')$themeAssets[$brand[$key]]=true;foreach($input['themes'] as $theme)if($theme['styles']['background']!=='')$themeAssets[$theme['styles']['background']]=true;
        foreach($themeAssets as $old=>$yes){$mapping[$old]=ag_sd_store_image($input['files']['assets/'.$old]);$added[]=$mapping[$old];}
        foreach(array('logo','mark','background','favicon') as $key)$brand[$key]=$mapping[$brand[$key]] ?? '';
        $brand['title']='';foreach($input['themes'] as &$theme)$theme['styles']['background']=$mapping[$theme['styles']['background']] ?? '';unset($theme);
        if($input['page'])$page=ag_pd_import_package(json_encode($input['page'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        $id=bin2hex(random_bytes(8));$draft=array('id'=>$id,'name'=>$input['name'],'created'=>time(),'themes'=>$input['themes'],'branding'=>$brand,'pageId'=>$page['id'] ?? '','version'=>1,'source'=>'Imported design package','artworkCount'=>count(array_filter(array_keys($input['files']),function($name){return strpos($name,'assets/')===0;})));
        ag_pd_write_document(ag_sd_import_dir().'/'.$id.'.json',$draft);return $draft;
    }catch(Throwable $e){foreach($added as $asset)@unlink(ag_sd_asset_path($asset));if($page)ag_pd_lock(function()use($page){ag_pd_delete_page($page);});throw $e;}
}
function ag_sd_apply_package($id,$scopes,$brand,$revision,$pageRevision,$reviewed) {
    if(!$reviewed)throw new RuntimeException('Preview and review the imported design before applying.');$draft=ag_sd_import_get($id);$selected=ag_sd_scope_selection($scopes);
    $oldPage=null;$oldDraft=null;$changed=false;
    $rollback=function()use(&$oldPage,&$oldDraft,&$changed,$draft){if(!$changed)return;$live=ag_pd_page_file($draft['pageId']);if($oldPage)ag_pd_write_document($live,$oldPage);elseif(is_file($live))unlink($live);if($oldDraft)ag_pd_write_document(ag_pd_draft_file($draft['pageId']),$oldDraft);elseif(is_file(ag_pd_draft_file($draft['pageId'])))unlink(ag_pd_draft_file($draft['pageId']));};
    return ag_sa_change($revision,function($s)use($draft,$selected,$brand,$pageRevision,&$oldPage,&$oldDraft,&$changed){
        foreach($selected as $scope){$theme=$draft['themes'][$scope] ?? null;if(!$theme)throw new RuntimeException('This design does not contain the selected scope.');if(count($s['themes'])>=50)throw new RuntimeException('Remove an unused saved theme before applying this package.');$themeId=bin2hex(random_bytes(8));$s['themes'][$themeId]=ag_sa_theme($theme);$s['scopes'][$scope]=$themeId;$s['activeThemes'][$scope]=$s['themes'][$themeId];}
        if($brand)$s['branding']=ag_sd_brand($draft['branding']);
        if(in_array('public',$selected,true)&&$draft['pageId']!==''){
            if(!is_string($pageRevision)||!hash_equals(ag_pd_editor_revision($draft['pageId']),$pageRevision))throw new RuntimeException('The imported page changed. Reload and preview again.');
            $oldPage=ag_pd_load_by_id($draft['pageId']);$oldDraft=ag_pd_load_draft($draft['pageId']);$p=ag_pd_edit_page($draft['pageId']);if(!$p||$p['access']!=='public'||ag_sa_page_login_count($p)!==1)throw new RuntimeException('The imported login page needs one visible Member Login.');
            foreach(ag_pd_page_images($p) as $name)if(!ag_pd_asset_path($p['id'],$name))throw new RuntimeException('An imported page asset is missing.');
            $changed=true;$p['published']=true;ag_pd_store_edition(ag_pd_normalize($p),'publish',$oldPage);$s['frontPage']=$p['id'];
        }return $s;
    },array('label'=>'Apply '.$draft['name'],'scopes'=>$selected,'rollback'=>$rollback));
}
