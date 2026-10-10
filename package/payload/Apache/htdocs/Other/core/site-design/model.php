<?php
// Versioned appearance data; no uploaded design is executable code.
function ag_sd_scopes() { return array('public'=>'Public Website','admin'=>'Admin Dashboard','user'=>'User Dashboard','designer'=>'Page Designer Pages'); }
function ag_sd_style_defaults() { return array('bodySize'=>16,'radius'=>12,'borderWidth'=>1,'button'=>'rounded','panel'=>'solid','navigation'=>'solid','background'=>'','artwork'=>'','treatment'=>'plain'); }
function ag_sd_styles($raw) {
    if(!is_array($raw))throw new RuntimeException('Invalid design styles.');
    $out=ag_sd_style_defaults();
    foreach(array('bodySize'=>array(10,28),'radius'=>array(0,40),'borderWidth'=>array(0,5)) as $key=>$range) {
        if(isset($raw[$key])&&(!is_numeric($raw[$key])||(float)$raw[$key]<(float)$range[0]||(float)$raw[$key]>(float)$range[1]))throw new RuntimeException('Style value is outside its supported range.');
        if(isset($raw[$key]))$out[$key]=(int)$raw[$key];
    }
    foreach(array('button'=>array('rounded','square','pill'),'panel'=>array('solid','outlined'),'navigation'=>array('solid','minimal'),'treatment'=>array_keys(ag_sd_treatments()),'artwork'=>array_merge(array(''),array_keys(ag_sd_artwork_library()))) as $key=>$choices) {
        if(isset($raw[$key])&&!in_array($raw[$key],$choices,true))throw new RuntimeException('Unsupported design style.');
        if(isset($raw[$key]))$out[$key]=$raw[$key];
    }
    $out['background']=ag_sd_asset_name($raw['background'] ?? '');return $out;
}
function ag_sd_asset_name($name) {
    if($name==='')return '';
    if(!is_string($name)||!preg_match('/^[a-f0-9]{24}\.(png|jpg|gif|webp)$/D',$name))throw new RuntimeException('Invalid branding asset reference.');
    return $name;
}
function ag_sd_brand_defaults() { return array('title'=>'','heading'=>'','welcome'=>'','footer'=>'','accent'=>'','button'=>'','logo'=>'','mark'=>'','background'=>'','favicon'=>''); }
function ag_sd_brand($raw) {
    if(!is_array($raw))throw new RuntimeException('Invalid branding settings.');$out=ag_sd_brand_defaults();
    foreach(array('title'=>120,'heading'=>240,'welcome'=>2000,'footer'=>500) as $key=>$limit){if(isset($raw[$key])&&!is_string($raw[$key]))throw new RuntimeException('Branding text must be plain text.');$out[$key]=ag_pd_text($raw[$key] ?? '',$limit);}
    foreach(array('logo','mark','background','favicon') as $key)$out[$key]=ag_sd_asset_name($raw[$key] ?? '');
    $accent=$raw['accent'] ?? '';if(!is_string($accent)||($accent!==''&&!preg_match('/^#[a-f0-9]{6}$/iD',$accent)))throw new RuntimeException('Use a six-digit branding accent colour.');$out['accent']=strtolower($accent);
    $button=$raw['button'] ?? '';if(!in_array($button,array('','rounded','square','pill'),true))throw new RuntimeException('Invalid branding button style.');$out['button']=$button;
    return $out;
}
function ag_sd_style_css($styles) {
    $s=ag_sd_styles($styles);$radius=$s['button']==='pill'?999:($s['button']==='square'?0:$s['radius']);
    $css='--site-background-image:none;--site-body-size:'.$s['bodySize'].'px;--site-radius:'.$s['radius'].'px;--site-border-width:'.$s['borderWidth'].'px;--site-button-radius:'.$radius.'px;';
    $css.='--site-panel-fill:'.($s['panel']==='outlined'?'var(--site-background)':'var(--site-surface)').';--site-nav-fill:'.($s['navigation']==='minimal'?'var(--site-background)':'var(--site-surface)').';';
    if($s['background']!==''&&ag_sd_asset_path($s['background']))$css.='--site-background-image:url("'.ag_sd_asset_url($s['background']).'");';
    return $css;
}
function ag_sd_contrast($a,$b) { $x=ag_sa_luminance($a);$y=ag_sa_luminance($b);return (max($x,$y)+0.05)/(min($x,$y)+0.05); }
function ag_sd_accessibility($theme,$page=null) {
    $warnings=array();$t=empty($theme['colors'])?ag_sa_presets()['classic-gold']:ag_sa_theme($theme);$c=$t['colors'];
    foreach(array('background'=>'page','surface'=>'panel') as $key=>$label)if(ag_sd_contrast($c['text'],$c[$key])<4.5)$warnings[]='Low contrast between '.$label.' background and text.';
    if(ag_sd_contrast($c['accent'],$c['background'])<3)$warnings[]='Headings and navigation accent may be difficult to read.';
    $s=$t['styles'] ?? ag_sd_style_defaults();if($s['bodySize']<16)$warnings[]='Body font size is below the recommended minimum.';
    if($s['background']!==''||$s['artwork']!=='')$warnings[]='Check text over the background image at desktop and mobile sizes.';
    if($page){
        $walk=function($nodes)use(&$walk,&$warnings){foreach($nodes as $n){if($n['type']==='image'&&!empty($n['props']['image'])&&empty($n['props']['alt']))$warnings[]='An image component has no alt text.';
            if(in_array($n['type'],array('text','heading'),true)&&isset($n['style']['fontSize'])&&$n['style']['fontSize']<16)$warnings[]='A text or heading component is very small.';
            if(!empty($n['props']['image'])||!empty($n['style']['backgroundImage']))$warnings[]='Review text over images for readability.';
            if($n['type']==='button'&&preg_match('/^#[a-f0-9]{6}$/iD',$n['style']['color'] ?? '')&&ag_sd_contrast($n['style']['color'],$c['accent'])<4.5)$warnings[]='Button text may be difficult to read.';
            $walk($n['children'] ?? array());}};$walk($page['builder']['blocks'] ?? array());
    }
    return array_values(array_unique($warnings));
}
function ag_sd_update_theme($id,$raw,$revision) {
    $theme=ag_sa_theme($raw);return ag_sa_change($revision,function($s)use($id,$theme){if(!is_string($id)||!isset($s['themes'][$id]))throw new RuntimeException('Duplicate a built-in theme before editing.');$s['themes'][$id]=$theme;return $s;},array('label'=>'Edit '.$theme['name']));
}
function ag_sd_duplicate_theme($id,$name,$revision) {
    $s=ag_sa_read();$theme=ag_sa_library($s)[$id] ?? null;if(!$theme||empty($theme['colors']))throw new RuntimeException('Choose a colour theme to duplicate.');
    $theme['name']=is_string($name)&&trim($name)!==''?$name:$theme['name'].' copy';return ag_sa_save_theme($theme,$revision);
}
function ag_sd_schedule($raw,$revision) {
    // Intentionally disabled until automatic activation is separately approved.
    if(!is_array($raw))throw new RuntimeException('Invalid schedule.');$page=$raw['pageId'] ?? '';ag_sa_front_candidate($page);
    $zone=$raw['timezone'] ?? '';if(!is_string($zone)||!in_array($zone,DateTimeZone::listIdentifiers(),true))throw new RuntimeException('Choose the grid owner’s intended timezone.');
    $parse=function($value)use($zone){if(!is_string($value))throw new RuntimeException('Choose a valid local date and time.');$dt=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new DateTimeZone($zone));if(!$dt||$dt->format('Y-m-d\TH:i')!==$value)throw new RuntimeException('Choose a valid local date and time.');return $dt->getTimestamp();};
    $start=$parse($raw['start'] ?? '');$end=($raw['end'] ?? '')!==''?$parse($raw['end']):null;if($end!==null&&$end<=$start)throw new RuntimeException('End time must be after start time.');
    $after=$raw['after'] ?? 'previous';if(!in_array($after,array('previous','default'),true))throw new RuntimeException('Choose what happens after the end.');
    return ag_sa_change($revision,function($s)use($page,$zone,$start,$end,$after){if(count($s['schedules'])>=20)throw new RuntimeException('Keep no more than 20 schedule drafts.');$s['schedules'][bin2hex(random_bytes(8))]=array('pageId'=>$page,'timezone'=>$zone,'start'=>$start,'end'=>$end,'after'=>$after,'enabled'=>false,'status'=>'draft','previousPage'=>$s['frontPage'],'previousScopes'=>$s['scopes'],'previousThemes'=>$s['activeThemes'],'previousBranding'=>$s['branding']);return $s;},array('label'=>'Save disabled schedule'));
}
function ag_sd_cancel_schedule($id,$revision) { return ag_sa_change($revision,function($s)use($id){if(!is_string($id)||!isset($s['schedules'][$id]))throw new RuntimeException('Schedule not found.');$s['schedules'][$id]['status']='cancelled';$s['schedules'][$id]['enabled']=false;return $s;},array('label'=>'Cancel schedule')); }

function ag_sd_active_theme($state,$scope) { return $state['activeThemes'][$scope] ?? ag_sa_library($state)[$state['scopes'][$scope]]; }
function ag_sd_save_apply($raw,$scopes,$revision) {
    $theme=ag_sa_theme($raw);$selected=ag_sd_scope_selection($scopes);
    return ag_sa_change($revision,function($s)use($theme,$selected){if(count($s['themes'])>=50)throw new RuntimeException('Remove an unused saved theme first.');$id=bin2hex(random_bytes(8));$s['themes'][$id]=$theme;foreach($selected as $scope){$s['scopes'][$scope]=$id;$s['activeThemes'][$scope]=$theme;}return $s;},array('label'=>'Apply '.$theme['name'],'scopes'=>$selected));
}
