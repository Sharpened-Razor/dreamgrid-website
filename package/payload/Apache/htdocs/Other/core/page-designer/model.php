<?php
// DreamGrid Page Designer: V10-compatible model and validation. PHP 7.4+.
function ag_pd_font_catalog()
{
    return array(
        'segoe-ui' => array('label' => 'Segoe UI', 'css' => '"Segoe UI", Arial, sans-serif'),
        'arial' => array('label' => 'Arial', 'css' => 'Arial, Helvetica, sans-serif'),
        'helvetica' => array('label' => 'Helvetica', 'css' => 'Helvetica, Arial, sans-serif'),
        'verdana' => array('label' => 'Verdana', 'css' => 'Verdana, Geneva, sans-serif'),
        'tahoma' => array('label' => 'Tahoma', 'css' => 'Tahoma, Geneva, sans-serif'),
        'trebuchet-ms' => array('label' => 'Trebuchet MS', 'css' => '"Trebuchet MS", Arial, sans-serif'),
        'georgia' => array('label' => 'Georgia', 'css' => 'Georgia, serif'),
        'times-new-roman' => array('label' => 'Times New Roman', 'css' => '"Times New Roman", Times, serif'),
        'garamond' => array('label' => 'Garamond', 'css' => 'Garamond, Georgia, serif'),
        'palatino' => array('label' => 'Palatino', 'css' => '"Palatino Linotype", Palatino, serif'),
        'bookman' => array('label' => 'Bookman Old Style', 'css' => '"Bookman Old Style", Georgia, serif'),
        'century-gothic' => array('label' => 'Century Gothic', 'css' => '"Century Gothic", Arial, sans-serif'),
        'calibri' => array('label' => 'Calibri', 'css' => 'Calibri, Candara, Arial, sans-serif'),
        'cambria' => array('label' => 'Cambria', 'css' => 'Cambria, Georgia, serif'),
        'candara' => array('label' => 'Candara', 'css' => 'Candara, Calibri, Arial, sans-serif'),
        'corbel' => array('label' => 'Corbel', 'css' => 'Corbel, Calibri, Arial, sans-serif'),
        'consolas' => array('label' => 'Consolas', 'css' => 'Consolas, "Courier New", monospace'),
        'courier-new' => array('label' => 'Courier New', 'css' => '"Courier New", Courier, monospace'),
        'lucida-console' => array('label' => 'Lucida Console', 'css' => '"Lucida Console", Monaco, monospace'),
        'lucida-sans' => array('label' => 'Lucida Sans Unicode', 'css' => '"Lucida Sans Unicode", "Lucida Grande", sans-serif'),
        'arial-black' => array('label' => 'Arial Black', 'css' => '"Arial Black", Arial, sans-serif'),
        'impact' => array('label' => 'Impact', 'css' => 'Impact, Haettenschweiler, sans-serif'),
        'franklin-gothic' => array('label' => 'Franklin Gothic Medium', 'css' => '"Franklin Gothic Medium", Arial, sans-serif'),
        'gill-sans' => array('label' => 'Gill Sans', 'css' => '"Gill Sans", "Gill Sans MT", Calibri, sans-serif'),
        'optima' => array('label' => 'Optima', 'css' => 'Optima, Candara, Arial, sans-serif')
    );
}


function ag_pd_text($value, $max = 5000) {
    $value = trim(is_scalar($value) ? (string)$value : '');
    if ($max > 0 && strlen($value) > $max) {
        $value = substr($value, 0, $max);
        while ($value !== '' && !preg_match('//u', $value)) $value = substr($value, 0, -1);
    }
    return $value;
}
function ag_pd_enum($value, $choices, $fallback) { return in_array($value, $choices, true) ? $value : $fallback; }
function ag_pd_int($value, $fallback, $min, $max) { return max($min, min($max, is_numeric($value) ? (int)$value : $fallback)); }
function ag_pd_hex($value, $fallback = '#111615') { return is_string($value) && preg_match('/^#[a-f0-9]{6}$/i', $value) ? strtolower($value) : $fallback; }
function ag_pd_slug($value) { return rtrim(substr(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(ag_pd_text($value, 0))), '-'), 0, 80), '-'); }
function ag_pd_menu_icons() { return array_combine(array('account','add','avatar','backup','console','email','groups','help','inventory','link','map','region','settings','statistics'), array('ACCOUNT','ADD','AVATAR','BACKUP','CONSOLE','EMAIL','GROUPS','HELP','INVENTORY','LINK','MAP','REGION','SETTINGS','STATISTICS')); }
function ag_pd_menu_locations() { return array('dashboard'=>'DASHBOARD','admin-menu'=>'ADMIN MENU','admin-tools'=>'ADMIN TOOLS'); }
function ag_pd_menu_icon($v) { return ag_pd_enum($v, array_keys(ag_pd_menu_icons()), 'link'); }
function ag_pd_menu_location($v) { return ag_pd_enum($v, array_keys(ag_pd_menu_locations()), 'admin-tools'); }
function ag_pd_menu_order($v) { return ag_pd_int($v,100,0,999); }
function ag_pd_menu_label($v, $fallback = '') { return ag_pd_text($v,40) ?: ag_pd_text($fallback,40); }
function ag_pd_menu_settings($p) { return array('label'=>ag_pd_menu_label($p['menuLabel'] ?? '',$p['title'] ?? ''),'icon'=>ag_pd_menu_icon($p['menuIcon'] ?? ''),'order'=>ag_pd_menu_order($p['menuOrder'] ?? 100),'location'=>ag_pd_menu_location($p['menuLocation'] ?? '')); }
function ag_pd_background_fit($v) { return ag_pd_enum($v,array('stretch','cover','contain','custom'),'stretch'); }
function ag_pd_background_percent($v,$fallback=100) { return ag_pd_int($v,$fallback,50,200); }
function ag_pd_background_position($v) { return ag_pd_int($v,50,0,100); }
function ag_pd_font_key($v,$inherit=false) { return $inherit && $v === 'inherit' ? 'inherit' : ag_pd_enum($v,array_keys(ag_pd_font_catalog()),'segoe-ui'); }
function ag_pd_font_css($v) { $v=ag_pd_font_key($v,true); return $v === 'inherit' ? 'inherit' : ag_pd_font_catalog()[$v]['css']; }
function ag_pd_font_stack_map() { return array_map(function($f){return $f['css'];},ag_pd_font_catalog()); }
function ag_pd_font_size($v,$fallback,$min=8,$max=96) { return ag_pd_int($v,$fallback,$min,$max); }
function ag_pd_text_align($v,$fallback='left') { return ag_pd_enum($v,array('left','center','right'),$fallback); }
function ag_pd_typography_defaults() {
    $out=array('globalFont'=>'segoe-ui');
    foreach (array('title'=>array(46,true,'#eef2ef','center'),'cardTitle'=>array(21,true,'#efc54c','left'),'cardText'=>array(16,false,'#e4e9e6','left'),'button'=>array(12,true,'#f4d56b','center')) as $key=>$v) {
        $out[$key.'Font']='inherit'; $out[$key.'Size']=$v[0]; $out[$key.'Bold']=$v[1]; $out[$key.'Italic']=false; $out[$key.'Color']=$v[2]; $out[$key.'Align']=$v[3];
    }
    return $out;
}
function ag_pd_style_group($stored,$key,$defaults,$min,$max) {
    $out=array();
    foreach (array('Font','Size','Bold','Italic','Color','Align') as $suffix) {
        $k=$key.$suffix; $v=$stored[$k] ?? $defaults[$k];
        if ($suffix==='Font') $v=ag_pd_font_key($v,true);
        elseif ($suffix==='Size') $v=ag_pd_font_size($v,$defaults[$k],$min,$max);
        elseif ($suffix==='Color') $v=ag_pd_hex($v,$defaults[$k]);
        elseif ($suffix==='Align') $v=ag_pd_text_align($v,$defaults[$k]);
        else $v=!empty($v);
        $out[$k]=$v;
    }
    return $out;
}
function ag_pd_typography($page) {
    $s=is_array($page['typography'] ?? null) ? $page['typography'] : array(); $d=ag_pd_typography_defaults();
    $out=array('globalFont'=>ag_pd_font_key($s['globalFont'] ?? $d['globalFont']));
    foreach (array('title'=>array(18,96),'cardTitle'=>array(10,64),'cardText'=>array(9,48),'button'=>array(9,40)) as $key=>$bounds) $out=array_merge($out,ag_pd_style_group($s,$key,$d,$bounds[0],$bounds[1]));
    return $out;
}
function ag_pd_card_typography($card,$pageTypography) {
    $d=array(); foreach (array('title'=>'cardTitle','text'=>'cardText','button'=>'button') as $key=>$source) foreach(array('Font','Size','Bold','Italic','Color','Align') as $s) $d[$key.$s]=$pageTypography[$source.$s];
    $stored=is_array($card['typography'] ?? null) ? $card['typography'] : array(); $out=array('custom'=>!empty($card['customTypography']));
    foreach(array('title'=>array(10,64),'text'=>array(9,48),'button'=>array(9,40)) as $key=>$b) $out=array_merge($out,ag_pd_style_group($stored,$key,$d,$b[0],$b[1]));
    return $out;
}
function ag_pd_effective_card_typography($card,$t) { return ag_pd_card_typography(!empty($card['customTypography']) ? $card : array(),$t); }
function ag_pd_card_picture_fit($v) { return ag_pd_enum($v,array('stretch','cover','contain'),'cover'); }
function ag_pd_card_picture_height($v,$fallback=220) { return ag_pd_int($v,$fallback,120,700); }
function ag_pd_card_picture_position($v) { return ag_pd_int($v,50,0,100); }
function ag_pd_card_picture($card) { $p=is_array($card['picture'] ?? null) ? $card['picture'] : array(); return array('fit'=>ag_pd_card_picture_fit($p['fit'] ?? ''),'height'=>ag_pd_card_picture_height($p['height'] ?? 220),'x'=>ag_pd_card_picture_position($p['x'] ?? 50),'y'=>ag_pd_card_picture_position($p['y'] ?? 50)); }
function ag_pd_safe_link($v) {
    $v=ag_pd_text($v,2048);
    if (preg_match('/[\x00-\x20\x7f\\\\]/', $v)) return '';
    return preg_match('#^(https?://|mailto:|hop://|/(?!/))#i',$v) ? $v : '';
}
function ag_pd_default_page() {
    return array('id'=>'','title'=>'','slug'=>'','access'=>'public','published'=>false,'showInMenu'=>false,'menuLabel'=>'','menuIcon'=>'link','menuOrder'=>100,'menuLocation'=>'admin-tools','columns'=>3,'backgroundColor'=>'#111615','backgroundImage'=>'','backgroundFit'=>'stretch','backgroundWidth'=>100,'backgroundHeight'=>100,'backgroundLock'=>true,'backgroundPositionX'=>50,'backgroundPositionY'=>50,'typography'=>ag_pd_typography_defaults(),'cards'=>array(),'created'=>0,'updated'=>0);
}
function ag_pd_image_name($v) { return is_string($v) && preg_match('/^[a-f0-9]{24}\.(jpg|png|gif|webp)$/',$v) ? $v : ''; }
function ag_pd_normalize($input,$existing=null) {
    if (!is_array($input)) throw new RuntimeException('Invalid page data.');
    $p=array_merge(ag_pd_default_page(),is_array($existing)?$existing:array(),$input);
    $p['title']=ag_pd_text($p['title'],120); $p['slug']=ag_pd_slug($p['slug'] ?: $p['title']);
    $p['access']=ag_pd_enum($p['access'],array('public','members','admin'),'public');
    foreach(array('published','showInMenu','backgroundLock') as $key) $p[$key]=!empty($p[$key]);
    $p['menuLabel']=ag_pd_text($p['menuLabel'],40); $p['menuIcon']=ag_pd_menu_icon($p['menuIcon']); $p['menuOrder']=ag_pd_menu_order($p['menuOrder']); $p['menuLocation']=ag_pd_menu_location($p['menuLocation']);
    $p['columns']=ag_pd_int($p['columns'],3,1,4); $p['backgroundColor']=ag_pd_hex($p['backgroundColor']); $p['backgroundImage']=ag_pd_image_name($p['backgroundImage']); $p['backgroundFit']=ag_pd_background_fit($p['backgroundFit']);
    foreach(array('backgroundWidth','backgroundHeight') as $k) $p[$k]=ag_pd_background_percent($p[$k]);
    foreach(array('backgroundPositionX','backgroundPositionY') as $k) $p[$k]=ag_pd_background_position($p[$k]);
    $p['typography']=ag_pd_typography($p);
    if (!is_array($p['cards']) || count($p['cards'])>30) throw new RuntimeException('A page can contain up to 30 cards.');
    $cards=array(); $ids=array();
    foreach($p['cards'] as $c) {
        if (!is_array($c)) throw new RuntimeException('Invalid card data.');
        $c=array_merge(array('id'=>'','title'=>'','text'=>'','image'=>'','linkLabel'=>'','linkUrl'=>'','newTab'=>false,'span'=>1,'backgroundColor'=>'#171c1b','customTypography'=>false),$c);
        if (!is_string($c['id']) || !preg_match('/^[a-f0-9]{12}$/',$c['id']) || isset($ids[$c['id']])) $c['id']=bin2hex(random_bytes(6));
        $ids[$c['id']]=true;
        foreach(array('title'=>120,'text'=>5000,'linkLabel'=>80) as $k=>$max) $c[$k]=ag_pd_text($c[$k],$max);
        $link=ag_pd_safe_link($c['linkUrl']);
        if (ag_pd_text($c['linkUrl'])!=='' && $link==='') throw new RuntimeException('Use an http, https, mailto, hop or local / link.');
        $c['linkUrl']=$link; $c['newTab']=!empty($c['newTab']); $c['span']=ag_pd_int($c['span'],1,1,4); $c['backgroundColor']=ag_pd_hex($c['backgroundColor'],'#171c1b'); $c['image']=ag_pd_image_name($c['image']); $c['picture']=ag_pd_card_picture($c); $c['customTypography']=!empty($c['customTypography']);
        $c['typography']=ag_pd_card_typography($c,$p['typography']); unset($c['typography']['custom']); $cards[]=$c;
    }
    $p['cards']=$cards;
    if(isset($p['seo']))$p['seo']=ag_seo_normalize($p['seo']);
    if(isset($p['builder'])) $p['builder']=ag_pb_normalize($p['builder']);
    return $p;
}
require_once __DIR__.'/builder-model.php';
function ag_pd_menu_pages() {
    $out=array(); foreach(ag_pd_list_pages() as $p) {
        if(empty($p['published']) || empty($p['showInMenu'])) continue;
        $m=ag_pd_menu_settings($p); $out[]=array_merge($m,array('id'=>$p['id'],'title'=>$p['title'],'slug'=>$p['slug']));
    }
    usort($out,function($a,$b){return ($a['order'] <=> $b['order']) ?: (strcasecmp($a['label'],$b['label']) ?: strcmp($a['id'],$b['id']));}); return $out;
}
