<?php
function ag_sd_asset_dir() { return dirname(ag_sa_file()).'/branding/assets'; }
function ag_sd_asset_path($name) { if($name==='')return null;$name=ag_sd_asset_name($name);$file=ag_sd_asset_dir().'/'.$name;return is_file($file)?$file:null; }
function ag_sd_asset_url($name) { return '/Other/site-design-asset.php?file='.rawurlencode(ag_sd_asset_name($name)); }
function ag_sd_image($bytes,$favicon=false) {
    if(!is_string($bytes)||strlen($bytes)<1||strlen($bytes)>8388608)throw new RuntimeException('Branding images must be 8 MB or smaller.');
    $image=@getimagesizefromstring($bytes);$types=array('image/png'=>'png','image/jpeg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp');
    $ext=$types[$image['mime'] ?? ''] ?? '';if(!$ext||$image[0]*$image[1]>16777216)throw new RuntimeException('Use a valid PNG, JPG, GIF or WebP image up to 16 megapixels.');
    if($favicon&&($ext!=='png'||$image[0]>512||$image[1]>512))throw new RuntimeException('Use a PNG favicon no larger than 512 × 512.');
    return array('extension'=>$ext,'mime'=>$image['mime'],'width'=>$image[0],'height'=>$image[1]);
}
function ag_sd_store_image($bytes,$favicon=false) {
    $info=ag_sd_image($bytes,$favicon);$dir=ag_sd_asset_dir();if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Branding storage is unavailable.');
    $name=bin2hex(random_bytes(12)).'.'.$info['extension'];if(file_put_contents($dir.'/'.$name,$bytes,LOCK_EX)!==strlen($bytes))throw new RuntimeException('Could not store branding image.');return $name;
}
function ag_sd_upload_image($upload,$favicon=false) {
    if(!is_array($upload)||($upload['error'] ?? -1)!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name'] ?? ''))throw new RuntimeException('Choose a valid branding image upload.');
    if(($upload['size'] ?? 0)>8388608)throw new RuntimeException('Branding images must be 8 MB or smaller.');
    return ag_sd_store_image(file_get_contents($upload['tmp_name']),$favicon);
}
function ag_sd_brand_save($raw,$uploads,$revision) {
    $brand=ag_sd_brand($raw);$added=array();
    try {
        foreach(array('logo','mark','background','favicon') as $key){
            if(($uploads[$key]['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$brand[$key]=ag_sd_upload_image($uploads[$key],$key==='favicon');$added[]=$brand[$key];}
            if($brand[$key]!==''&&!ag_sd_asset_path($brand[$key]))throw new RuntimeException('A selected branding image is unavailable.');
        }
        return ag_sa_change($revision,function($s)use($brand){$s['branding']=$brand;foreach($s['activeThemes'] as &$theme)if($theme){if($brand['accent']!=='')$theme['colors']['accent']=$brand['accent'];if($brand['button']!=='')$theme['styles']['button']=$brand['button'];}unset($theme);return $s;},array('label'=>'Apply branding','scopes'=>array('public','admin','user','designer')));
    }catch(Throwable $e){foreach($added as $name)@unlink(ag_sd_asset_path($name));throw $e;}
}
function ag_sd_state_safe() { try{return ag_sa_read();}catch(Throwable $e){error_log('Site design fallback: '.$e->getMessage());return ag_sa_defaults();} }
function ag_sd_origin() {
    $host=$_SERVER['HTTP_HOST'] ?? '';if(!is_string($host)||!preg_match('/^[a-z0-9.\-:\[\]]+$/iD',$host))return '';
    return (!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off'?'https':'http').'://'.$host;
}
function ag_sd_identity($brand=null) {
    $b=$brand===null?ag_sd_state_safe()['branding']:ag_sd_brand($brand);$grid=ag_grid_name();$url=ag_sd_origin();
    $ini=ag_web_robust_ini();$login=ag_web_ini_value($ini,'GridInfoService','login') ?? ag_web_ini_value($ini,'LoginService','LoginURL') ?? '';
    if(!is_string($login)||!preg_match('#^https?://#i',$login))$login=$url;
    $tokens=array('{{gridName}}'=>$grid,'{{websiteUrl}}'=>$url,'{{loginAddress}}'=>$login);foreach(array('title','heading','welcome','footer') as $key)$b[$key]=strtr($b[$key],$tokens);
    return array('gridName'=>$grid,'siteTitle'=>$b['title'] ?: $grid,'loginAddress'=>$login,'welcomeHeading'=>$b['heading'] ?: 'Welcome to '.$grid,'welcomeText'=>$b['welcome'] ?: 'A place to explore, connect and create.','footer'=>$b['footer'] ?: 'Welcome to our community.','websiteUrl'=>$url);
}
function ag_sd_bound_props($props,$brand=null) {
    foreach(array('text','title','subtitle','label','kicker','brand','url','buttonUrl') as $key)if(isset($props[$key])&&is_string($props[$key])){$identity=ag_sd_identity($brand);foreach($identity as $token=>$value)$props[$key]=str_replace('{{'.$token.'}}',$value,$props[$key]);}
    if(isset($props['designBinding'])){$identity=ag_sd_identity($brand);$props['text']=$identity[$props['designBinding']] ?? '';}return $props;
}
function ag_sd_brand_html($brand=null) {
    $b=$brand===null?ag_sd_state_safe()['branding']:ag_sd_brand($brand);$name=ag_sd_identity($b)['siteTitle'];
    $logo=$b['logo']!==''&&ag_sd_asset_path($b['logo'])?'<img class="site-brand-logo" src="'.ag_pd_h(ag_sd_asset_url($b['logo'])).'" alt="'.ag_pd_h($name).' logo">':'<span class="site-brand-default" aria-hidden="true">◈</span>';
    $mark=$b['mark']!==''&&ag_sd_asset_path($b['mark'])?'<img class="site-brand-mark" src="'.ag_pd_h(ag_sd_asset_url($b['mark'])).'" alt="">':'';
    return '<div class="site-brand">'.$logo.'<strong>'.ag_pd_h($name).'</strong>'.$mark.'</div>';
}
function ag_sd_brand_head($brand=null) {
    $b=$brand===null?ag_sd_state_safe()['branding']:ag_sd_brand($brand);
    return $b['favicon']!==''&&ag_sd_asset_path($b['favicon'])?'<link rel="icon" type="image/png" href="'.ag_pd_h(ag_sd_asset_url($b['favicon'])).'">':'';
}
function ag_sd_brand_css($brand) {
    $b=ag_sd_brand($brand);$css='';
    if($b['accent']!==''){$css.='--site-accent:'.$b['accent'].';';$l=ag_sa_luminance($b['accent']);$css.='--site-accent-text:'.(($l+0.05)/0.05>=1.05/($l+0.05)?'#000000':'#ffffff').';';}
    if($b['button']!=='')$css.='--site-button-radius:'.array('square'=>0,'rounded'=>12,'pill'=>999)[$b['button']].'px;';
    if($b['background']!==''&&ag_sd_asset_path($b['background']))$css.='--site-background-image:url("'.ag_sd_asset_url($b['background']).'");';
    return $css!==''?':root{'.$css.'}':'';
}
function ag_sd_public_asset($name,$state) {
    if(in_array($name,$state['branding'],true))return true;
    foreach(array_keys(ag_sd_scopes()) as $scope)if((ag_sd_active_theme($state,$scope)['styles']['background'] ?? '')===$name)return true;return false;
}

function ag_sd_title_html() { return ag_pd_h(ag_sd_identity()['siteTitle']); }

function ag_sd_effective_brand_css($theme,$brand) { if(!empty($theme['colors'])){$brand['accent']='';$brand['button']='';}if(!empty($theme['styles']['background']))$brand['background']='';return ag_sd_brand_css($brand); }

function ag_sd_shell_logo($fallback) { $b=ag_sd_state_safe()['branding'];return $b['logo']!==''&&ag_sd_asset_path($b['logo'])?'<img class="site-shell-logo" src="'.ag_pd_h(ag_sd_asset_url($b['logo'])).'" alt="'.ag_sd_title_html().' logo">':$fallback; }
