<?php
// Optional settings: old pages keep their stored shape and legacy rendering.
function ag_pi_settings($raw) {
    if(!is_array($raw)) throw new RuntimeException('Invalid interaction settings.');
    return array('disableMobile'=>!array_key_exists('disableMobile',$raw)||!empty($raw['disableMobile']),'backToTop'=>!empty($raw['backToTop']));
}
function ag_pi_props($raw) {
    $out=array();
    foreach(array('motion'=>array('none','fade','slide-up','slide-left','slide-right'),'hover'=>array('none','lift','zoom','glow')) as $key=>$choices)
        if(isset($raw[$key])) $out[$key]=ag_pd_enum($raw[$key],$choices,'none');
    foreach(array('duration'=>array(100,2000),'delay'=>array(0,2000)) as $key=>$range)
        if(isset($raw[$key])) $out[$key]=ag_pd_int($raw[$key],$key==='duration'?600:0,$range[0],$range[1]);
    if(isset($raw['sticky'])) $out['sticky']=!empty($raw['sticky']);
    if(isset($raw['defaultPanel'])) $out['defaultPanel']=ag_pd_int($raw['defaultPanel'],1,1,24);
    if(isset($raw['singleOpen'])) $out['singleOpen']=!empty($raw['singleOpen']);
    if(isset($raw['startClosed'])) $out['startClosed']=!empty($raw['startClosed']);
    return $out;
}
function ag_pi_attrs($n) {
    $p=$n['props'];$out='';
    if(!empty($p['motion']) && $p['motion']!=='none') $out.=' data-motion="'.ag_pd_h($p['motion']).'" data-duration="'.($p['duration'] ?? 600).'" data-delay="'.($p['delay'] ?? 0).'"';
    if(!empty($p['hover']) && $p['hover']!=='none') $out.=' data-hover="'.ag_pd_h($p['hover']).'"';
    if($n['type']==='navigation'&&!empty($p['sticky'])) $out.=' data-sticky="1"';
    return $out;
}
function ag_pi_container($n,$page,$attrs) {
    $tabs=$n['type']==='tabs';$id='wb-group-'.$n['id'];$panels=$n['children'];
    $out='<section'.$attrs.' data-interactive="'.$n['type'].'" data-default-panel="'.($n['props']['defaultPanel'] ?? 1).'" data-single-open="'.(!empty($n['props']['singleOpen'])?'1':'0').'" data-start-closed="'.(!empty($n['props']['startClosed'])?'1':'0').'">';
    if($tabs){$out.='<div class="wb-tablist" aria-label="'.ag_pd_h($n['name']).'">';foreach($panels as $i=>$child)$out.='<button type="button" class="wb-tab" id="'.$id.'-tab-'.$i.'" data-tab-index="'.$i.'" aria-controls="'.$id.'-panel-'.$i.'">'.ag_pd_h($child['name']).'</button>';$out.='</div>';}
    foreach($panels as $i=>$child){
        $title=ag_pd_h($child['name']);$content=ag_pb_nodes(array($child),$page);
        if($tabs)$out.='<div class="wb-tabpanel" id="'.$id.'-panel-'.$i.'" data-panel-index="'.$i.'"><h3 class="wb-panel-heading">'.$title.'</h3>'.$content.'</div>';
        else $out.='<details class="wb-accordion-item" data-panel-index="'.$i.'"'.(empty($n['props']['startClosed'])&&$i===max(0,($n['props']['defaultPanel'] ?? 1)-1)?' open':'').'><summary>'.$title.'</summary><div class="wb-accordion-content">'.$content.'</div></details>';
    }
    return $out.'</section>';
}
function ag_pi_css() {
    return file_get_contents(__DIR__.'/interactions.css');
}
function ag_seo_normalize($raw) {
    if(!is_array($raw)) throw new RuntimeException('Invalid search and sharing settings.');
    $out=array();foreach(array('title'=>120,'description'=>500,'shareTitle'=>120,'shareDescription'=>500,'imageAlt'=>300) as $key=>$limit)$out[$key]=ag_pd_text($raw[$key] ?? '',$limit);
    $out['image']=ag_pd_image_name($raw['image'] ?? '');$out['noindex']=!empty($raw['noindex']);return $out;
}
function ag_seo_origin() {
    $host=$_SERVER['HTTP_HOST'] ?? '';
    // Do not trust forwarded headers or permit HTML/path/header injection.
    if(!is_string($host)||!preg_match('/^(?:[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|\[[a-f0-9:]+\])(?::[0-9]{1,5})?$/i',$host))return '';
    return (!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off'?'https':'http').'://'.$host;
}
function ag_seo_head($page,$preview=false) {
    $restricted=$preview||empty($page['published'])||($page['access'] ?? 'public')!=='public';
    if(!isset($page['seo']))return $restricted?'<meta name="robots" content="noindex,nofollow">':'';
    $s=ag_seo_normalize($page['seo']);$out='<meta name="robots" content="'.($restricted?'noindex,nofollow':($s['noindex']?'noindex,follow':'index,follow')).'">';
    if($s['description']!=='')$out.='<meta name="description" content="'.ag_pd_h($s['description']).'">';
    if($restricted)return $out;
    $title=$s['shareTitle']?:($s['title']?:$page['title']);$description=$s['shareDescription']?:$s['description'];
    foreach(array('type'=>'website','title'=>$title,'description'=>$description) as $key=>$value)if($value!=='')$out.='<meta property="og:'.$key.'" content="'.ag_pd_h($value).'">';
    $origin=ag_seo_origin();
    if($origin!==''){$url=$origin.'/Other/custom-page.php?page='.rawurlencode($page['slug']);$out.='<link rel="canonical" href="'.ag_pd_h($url).'"><meta property="og:url" content="'.ag_pd_h($url).'">';
        if($s['image']!==''&&ag_pd_asset_path($page['id'],$s['image']))$out.='<meta property="og:image" content="'.ag_pd_h($origin.ag_pd_asset_url($page['id'],$s['image'])).'"><meta property="og:image:alt" content="'.ag_pd_h($s['imageAlt']).'">';}
    return $out;
}
function ag_seo_legacy($html,$page,$preview) {
    if(isset($page['seo'])&&($page['seo']['title'] ?? '')!=='')$html=preg_replace_callback('#<title>.*?</title>#s',function()use($page){return '<title>'.ag_pd_h($page['seo']['title']).'</title>';},$html,1);
    return str_replace('</head>',ag_seo_head($page,$preview).'</head>',$html);
}
