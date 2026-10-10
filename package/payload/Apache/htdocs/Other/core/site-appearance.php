<?php
// Site-wide appearance is structured data, stored outside the public web root.
require_once __DIR__.'/page-designer.php';
function ag_sa_presets() {
    $make=function($name,$bg,$surface,$text,$muted,$accent,$border){return array('name'=>$name,'font'=>'segoe-ui','colors'=>array('background'=>$bg,'surface'=>$surface,'text'=>$text,'muted'=>$muted,'accent'=>$accent,'border'=>$border));};
    return ag_sd_extend_presets(array('current'=>array('name'=>'Keep current appearance','font'=>'segoe-ui','colors'=>array()),
        'safe'=>$make('Safe Default','#eef2f6','#ffffff','#172b31','#526773','#17618a','#a8bac8'),
        'classic-gold'=>$make('Classic Gold','#050706','#111310','#dedbd2','#a3a69e','#e2b64f','#74602d'),
        'ocean-blue'=>$make('Ocean Blue','#07131e','#102536','#eaf4ff','#a0bbce','#65c9ff','#315971'),
        'forest'=>$make('Forest','#08150f','#15291d','#edf6ee','#abc3ae','#a1d68c','#436448'),
        'light'=>$make('Light','#eef2f6','#ffffff','#172b31','#526773','#17618a','#a8bac8')));
}
function ag_sa_theme($raw) {
    if(!is_array($raw)||!is_string($raw['name'] ?? null)||!is_array($raw['colors'] ?? null))throw new RuntimeException('Choose a valid appearance theme.');
    $name=ag_pd_text($raw['name'],80);if($name==='')throw new RuntimeException('Give the theme a name.');
    $colors=array();foreach(array('background','surface','text','muted','accent','border') as $key){
        $value=$raw['colors'][$key] ?? null;if(!is_string($value)||!preg_match('/^#[a-f0-9]{6}$/iD',$value))throw new RuntimeException('Theme colours must use six-digit hex values.');$colors[$key]=strtolower($value);
    }
    $font=$raw['font'] ?? '';if(!is_string($font)||!isset(ag_pd_font_catalog()[$font]))throw new RuntimeException('Choose a supported font.');
    return array('name'=>$name,'font'=>$font,'colors'=>$colors,'styles'=>ag_sd_styles($raw['styles'] ?? array()));
}
function ag_sa_file() { $root=ag_pd_root();if(!$root)throw new RuntimeException('DreamGrid website storage could not be located.');return $root.'/site-appearance/settings.json'; }
function ag_sa_defaults() { return array('version'=>2,'themes'=>array(),'scopes'=>array('public'=>'current','admin'=>'current','user'=>'current','designer'=>'current'),'activeThemes'=>array('public'=>null,'admin'=>null,'user'=>null,'designer'=>null),'frontPage'=>'','branding'=>ag_sd_brand_defaults(),'historyLimit'=>50,'schedules'=>array()); }
function ag_sa_validate_state($raw) {
    if(!is_array($raw)||!in_array($raw['version'] ?? null,array(1,2),true)||!is_array($raw['themes'] ?? null)||count($raw['themes'])>50||!is_array($raw['scopes'] ?? null)||!is_string($raw['frontPage'] ?? null))throw new RuntimeException('Appearance settings could not be read.');
    $out=ag_sa_defaults();
    foreach($raw['themes'] as $id=>$theme){if(!is_string($id)||!preg_match('/^[a-f0-9]{16}$/D',$id))throw new RuntimeException('Invalid saved theme identifier.');$out['themes'][$id]=ag_sa_theme($theme);}
    $library=ag_sa_library($out);
    foreach(array_keys(ag_sd_scopes()) as $scope){$id=$raw['scopes'][$scope] ?? ($scope==='designer'?($raw['scopes']['public'] ?? 'current'):null);if(!is_string($id)||!isset($library[$id]))throw new RuntimeException('Invalid saved theme selection.');$out['scopes'][$scope]=$id;}
    if($raw['frontPage']!==''&&!ag_pd_page_file($raw['frontPage']))throw new RuntimeException('Invalid front page identifier.');
    foreach(array_keys(ag_sd_scopes()) as $scope){$active=$raw['activeThemes'][$scope] ?? null;if($active===null&&!empty($library[$out['scopes'][$scope]]['colors']))$active=$library[$out['scopes'][$scope]];$out['activeThemes'][$scope]=$active===null?null:ag_sa_theme($active);}
    $out['frontPage']=$raw['frontPage'];$out['branding']=ag_sd_brand($raw['branding'] ?? array());
    $limit=$raw['historyLimit'] ?? 50;if(!is_int($limit)||$limit<10||$limit>200)throw new RuntimeException('Invalid history retention.');$out['historyLimit']=$limit;
    $schedules=$raw['schedules'] ?? array();if(!is_array($schedules)||count($schedules)>20)throw new RuntimeException('Invalid schedule drafts.');
    foreach($schedules as $id=>$schedule){if(!is_string($id)||!preg_match('/^[a-f0-9]{16}$/D',$id)||!is_array($schedule)||($schedule['enabled'] ?? null)!==false||!in_array($schedule['status'] ?? '',array('draft','cancelled'),true)||!ag_pd_page_file($schedule['pageId'] ?? '')||!is_int($schedule['start'] ?? null)||!(is_int($schedule['end'] ?? null)||($schedule['end'] ?? null)===null)||!in_array($schedule['timezone'] ?? '',DateTimeZone::listIdentifiers(),true)||!in_array($schedule['after'] ?? '',array('previous','default'),true))throw new RuntimeException('Invalid disabled schedule.');$out['schedules'][$id]=$schedule;}
    return $out;
}
function ag_sa_read() { $raw=ag_pd_read_document(ag_sa_file());return $raw===null?ag_sa_defaults():ag_sa_validate_state($raw); }
function ag_sa_revision($state) { return hash('sha256',json_encode($state)); }
function ag_sa_library($state) { return array_merge(ag_sa_presets(),$state['themes']); }
function ag_sa_change($revision,$edit,$meta=array()) {
    return ag_pd_lock(function()use($revision,$edit,$meta){
        $before=ag_sa_read();if(!is_string($revision)||!hash_equals(ag_sa_revision($before),$revision))throw new RuntimeException('These settings changed in another window. Reload before applying.');
        $beforePage=ag_sd_snapshot_page($before);$previous=ag_pd_read_document(dirname(ag_sa_file()).'/previous.json');$historyId=null;
        try {
            $next=ag_sa_validate_state($edit($before));$historyId=ag_sd_record_history($before,$next,$meta,$beforePage);
            ag_pd_write_document(dirname(ag_sa_file()).'/previous.json',$before);ag_pd_write_document(ag_sa_file(),$next);
        }catch(Throwable $e){if(isset($meta['rollback']))($meta['rollback'])();if($historyId)@unlink(dirname(ag_sa_file()).'/history/'.$historyId.'.json');if($previous)ag_pd_write_document(dirname(ag_sa_file()).'/previous.json',$previous);throw $e;}
        ag_sd_prune_history($next['historyLimit']);return $next;
    });
}
function ag_sa_save_theme($raw,$revision) { $theme=ag_sa_theme($raw);return ag_sa_change($revision,function($s)use($theme){if(count($s['themes'])>=50)throw new RuntimeException('The library supports 50 saved themes.');$s['themes'][bin2hex(random_bytes(8))]=$theme;return $s;}); }
function ag_sa_delete_theme($id,$revision) { return ag_sa_change($revision,function($s)use($id){if(!is_string($id)||!isset($s['themes'][$id]))throw new RuntimeException('Choose a saved custom theme to remove.');if(in_array($id,$s['scopes'],true))throw new RuntimeException('Apply another theme to each area using this theme before removing it.');unset($s['themes'][$id]);return $s;}); }
function ag_sd_scope_selection($scopes) {
    if($scopes==='all')return array_keys(ag_sd_scopes());if(is_string($scopes))$scopes=array($scopes);
    if(!is_array($scopes)||!$scopes||count($scopes)>4)throw new RuntimeException('Choose at least one design scope.');
    $out=array();foreach($scopes as $scope){if(!is_string($scope)||!isset(ag_sd_scopes()[$scope]))throw new RuntimeException('Choose a supported design scope.');$out[$scope]=$scope;}return array_values($out);
}
function ag_sa_apply($id,$scopes,$revision) {
    $selected=ag_sd_scope_selection($scopes);return ag_sa_change($revision,function($s)use($id,$selected){if(!is_string($id)||!isset(ag_sa_library($s)[$id]))throw new RuntimeException('Theme not found.');foreach($selected as $scope){$s['scopes'][$scope]=$id;$t=ag_sa_library($s)[$id];$s['activeThemes'][$scope]=empty($t['colors'])?null:ag_sa_theme($t);}return $s;},array('label'=>'Apply '.(ag_sa_library(ag_sa_read())[$id]['name'] ?? 'theme'),'scopes'=>$selected));
}
function ag_sa_login_count($nodes,$hidden=false) { $count=0;foreach($nodes as $n){$blocked=$hidden||!empty($n['style']['hideDesktop'])||!empty($n['style']['hideTablet'])||!empty($n['style']['hideMobile'])||in_array($n['type'],array('tabs','accordion'),true);if($n['type']==='grid-login'&&!$blocked)$count++;$count+=ag_sa_login_count($n['children'] ?? array(),$blocked);}return $count; }
function ag_sa_page_login_count($page) {
    $count=ag_sa_login_count($page['builder']['blocks'] ?? array());$site=ag_pbl_site_live();
    foreach(array('header','footer') as $role)if(!empty($page['builder']['site'][$role])&&!empty($site[$role])){
        try{$section=ag_pbl_get('section',$site[$role]);$count+=ag_sa_login_count(array($section['block']));}catch(Throwable $e){/* Missing shared sections are also skipped by the page renderer. */}
    }return $count;
}
function ag_sa_front_candidate($id) { $p=ag_pd_load_by_id($id);if(!$p||empty($p['published'])||($p['access'] ?? '')!=='public'||!isset($p['builder'])||ag_sa_page_login_count($p)!==1)throw new RuntimeException('Choose a published public page with one visible Member Login component.');foreach(ag_pd_page_images($p) as $name)if(!ag_pd_asset_path($p['id'],$name))throw new RuntimeException('A custom login-page image is unavailable.');return $p; }
function ag_sa_front_page($id,$revision) { return ag_sa_change($revision,function($s)use($id){if($id!=='')ag_sa_front_candidate($id);$s['frontPage']=$id;return $s;},array('label'=>'Apply front login page','scopes'=>array('public'))); }
function ag_sa_restore($revision) { return ag_sa_change($revision,function($s){$previous=ag_pd_read_document(dirname(ag_sa_file()).'/previous.json');if(!$previous)throw new RuntimeException('There is no previous appearance setup to restore.');$previous=ag_sa_validate_state($previous);if($previous['frontPage']!=='')ag_sa_front_candidate($previous['frontPage']);return $previous;},array('label'=>'Restore previous design')); }
function ag_sa_import($json,$revision) { if(!is_string($json)||strlen($json)>16384)throw new RuntimeException('Use an appearance theme JSON file smaller than 16 KB.');$p=json_decode($json,true);if(!is_array($p)||($p['format'] ?? '')!=='dreamgrid-appearance-theme'||($p['version'] ?? null)!==1)throw new RuntimeException('This is not a supported appearance theme file.');return ag_sa_save_theme($p['theme'] ?? null,$revision); }
function ag_sa_luminance($hex) {
    $values=array();foreach(array(1,3,5) as $offset){$v=hexdec(substr($hex,$offset,2))/255;$values[]=$v<=0.04045?$v/12.92:pow(($v+0.055)/1.055,2.4);}
    return 0.2126*$values[0]+0.7152*$values[1]+0.0722*$values[2];
}
function ag_sa_css($theme,$scope='public') {
    if(empty($theme['colors']))return ''; $theme=ag_sa_theme($theme);$css=':root{';foreach($theme['colors'] as $key=>$value)$css.='--site-'.$key.':'.$value.';';
    $l=ag_sa_luminance($theme['colors']['accent']);$foreground=($l+0.05)/0.05>=1.05/($l+0.05)?'#000000':'#ffffff';
    return $css.ag_sd_style_css($theme['styles']).ag_sd_design_css($theme['styles'],$scope).'--site-accent-text:'.$foreground.';--site-font:'.ag_pd_font_css($theme['font']).';color-scheme:'.(ag_sa_luminance($theme['colors']['background'])>0.5?'light':'dark').';}';
}
function ag_sa_link($scope) { $preview=isset($_GET['design_preview'])&&is_string($_GET['design_preview'])?$_GET['design_preview']:'';return '<link rel="stylesheet" href="/Other/site-theme.php?scope='.rawurlencode($scope).($preview!==''?'&amp;preview='.rawurlencode($preview):'').'">'; }
function ag_sa_native_login($id,$preview=false) {
    $escape=function($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');};$key='member-'.$escape($id);$disabled=$preview?' disabled':'';
    $message=$preview?'<p>Preview only — login is disabled here.</p>':((isset($_GET['error'])&&$_GET['error']==='1')?'<p class="site-login-error" role="alert">Login failed. Check your name and password, then try again.</p>':'');
    return '<section class="site-member-login" aria-label="Member Login"><h2>Member Login</h2>'.$message.'<form method="post" action="/Other/login-action.php"><label for="'.$key.'-first">First name</label><input id="'.$key.'-first" name="firstname" autocomplete="given-name" required maxlength="100"'.$disabled.'><label for="'.$key.'-last">Last name</label><input id="'.$key.'-last" name="lastname" autocomplete="family-name" required maxlength="100"'.$disabled.'><label for="'.$key.'-password">Password</label><input id="'.$key.'-password" type="password" name="password" autocomplete="current-password" required'.$disabled.'><label class="site-login-remember"><input type="checkbox" name="remember" value="1"'.$disabled.'> Remember me</label><button type="submit"'.$disabled.'>Log in</button></form><nav aria-label="Account help"><a href="/Other/index.php?panel=create-account">Create account</a><a href="/Other/index.php?panel=forgot-password">Forgot password</a><a href="/Other/index.php?panel=terms">Terms of service</a></nav></section>';
}
function ag_sa_add_login($id) { $p=ag_pd_edit_page($id);if(!$p||!isset($p['builder']))throw new RuntimeException('Open a structured page in Page Designer first.');if(ag_sa_login_count($p['builder']['blocks'])>0)throw new RuntimeException('This page already has a Member Login component.');$revision=ag_pd_editor_revision($id);$p['builder']['blocks'][]=array('id'=>bin2hex(random_bytes(6)),'type'=>'grid-login','name'=>'Member Login','props'=>array(),'style'=>array('padding'=>32,'width'=>'narrow'),'children'=>array());return ag_pd_commit($p,array(),$revision,'draft'); }
function ag_sa_render_front($preview=false,$id='') {
    $buffers=ob_get_level();
    try {if($id==='')$id=ag_sa_read()['frontPage'];if($id==='')return false;$p=$preview?ag_pd_edit_page($id):ag_sa_front_candidate($id);if(!$p||($p['access'] ?? '')!=='public')return false;$p['_designScope']='public';$html=ag_pd_render($p,$preview);if(!headers_sent())header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');echo $html;return true;}catch(Throwable $e){while(ob_get_level()>$buffers)ob_end_clean();error_log('Custom front page: '.$e->getMessage());return false;}
}

require_once __DIR__.'/site-design/model.php';
require_once __DIR__.'/site-design/branding.php';
require_once __DIR__.'/site-design/starters.php';
require_once __DIR__.'/site-design/history.php';
require_once __DIR__.'/site-design/packages.php';

require_once __DIR__.'/site-design/library.php';
