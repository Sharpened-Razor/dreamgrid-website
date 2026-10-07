<?php
require_once __DIR__.'/forms-model.php';
require_once __DIR__.'/interactions.php';
// Portable structured webpage model. Stored alongside the unchanged V10 fields.
function ag_pb_types() { return array('section','columns','heading','text','image','button','navigation','hero','gallery','quote','faq','stats','video','divider','spacer','footer','form','tabs','accordion','legacy'); }
function ag_pb_link($value) {
    $value=ag_pd_text($value,2048);
    return preg_match('/^#[a-zA-Z][a-zA-Z0-9_-]*$/',$value) ? $value : ag_pd_safe_link($value);
}
function ag_pb_theme($input=array()) {
    $d=array('background'=>'#f7f8fa','surface'=>'#ffffff','text'=>'#172b31','muted'=>'#65777c','accent'=>'#087f75','font'=>'segoe-ui','width'=>1200,'radius'=>16,'backgroundImage'=>'','backgroundFit'=>'cover','backgroundWidth'=>100,'backgroundHeight'=>100,'backgroundLock'=>true,'backgroundPositionX'=>50,'backgroundPositionY'=>50);
    $t=array_merge($d,is_array($input)?$input:array());
    foreach(array('background','surface','text','muted','accent') as $k) $t[$k]=ag_pd_hex($t[$k],$d[$k]);
    $t['font']=ag_pd_font_key($t['font']); $t['width']=ag_pd_int($t['width'],1200,640,1800); $t['radius']=ag_pd_int($t['radius'],16,0,64);
    $t['backgroundImage']=ag_pd_image_name($t['backgroundImage']); $t['backgroundFit']=ag_pd_background_fit($t['backgroundFit']);
    foreach(array('backgroundWidth','backgroundHeight') as $k) $t[$k]=ag_pd_background_percent($t[$k]);
    foreach(array('backgroundPositionX','backgroundPositionY') as $k) $t[$k]=ag_pd_background_position($t[$k]);
    $t['backgroundLock']=!empty($t['backgroundLock']);
    return array_intersect_key($t,$d);
}
function ag_pb_style($input) {
    $s=is_array($input)?$input:array(); $out=array();
    foreach(array('background','color','borderColor') as $k) if(isset($s[$k]) && $s[$k]!=='') $out[$k]=in_array($s[$k],array('background','surface','text','muted','accent','white','transparent'),true)?$s[$k]:ag_pd_hex($s[$k],$k==='color'?'#172b31':'#ffffff');
    foreach(array('padding'=>array(0,200),'paddingY'=>array(0,240),'gap'=>array(0,120),'radius'=>array(0,100),'fontSize'=>array(8,160),'minHeight'=>array(0,1200),'height'=>array(0,1200),'marginTop'=>array(0,160),'marginBottom'=>array(0,160),'borderWidth'=>array(0,12),'columns'=>array(1,6),'tabletColumns'=>array(1,6),'mobileColumns'=>array(1,3),'gridSpan'=>array(1,6),'imageX'=>array(0,100),'imageY'=>array(0,100),'backgroundWidth'=>array(50,200),'backgroundHeight'=>array(50,200)) as $k=>$range) if(isset($s[$k])) $out[$k]=ag_pd_int($s[$k],$range[0],$range[0],$range[1]);
    if(isset($s['backgroundFit'])) $out['backgroundFit']=ag_pd_background_fit($s['backgroundFit']);
    if(isset($s['backgroundLock'])) $out['backgroundLock']=!empty($s['backgroundLock']);
    if(isset($s['lineHeight'])) $out['lineHeight']=is_numeric($s['lineHeight'])?max(1,min(3,(float)$s['lineHeight'])):1.6;
    foreach(array('align'=>array('left','center','right'),'vertical'=>array('start','center','end'),'fit'=>array('cover','contain','fill'),'width'=>array('full','wide','medium','narrow'),'shadow'=>array('none','soft','strong')) as $k=>$choices) if(isset($s[$k])) $out[$k]=ag_pd_enum($s[$k],$choices,$choices[0]);
    if(isset($s['font'])) $out['font']=ag_pd_font_key($s['font'],true);
    foreach(array('bold','italic','hideDesktop','hideTablet','hideMobile') as $k) if(isset($s[$k])) $out[$k]=!empty($s[$k]);
    return $out;
}
function ag_pb_responsive($input) {
    if(!is_array($input)) throw new RuntimeException('Invalid device styles.'); $out=array();
    $keys=array_flip(array('fontSize','lineHeight','align','padding','paddingY','gap','height','minHeight','marginTop','marginBottom','imageX','imageY','fit','gridSpan','vertical'));
    foreach(array('tablet','mobile') as $device) if(isset($input[$device])) {
        if(!is_array($input[$device])) throw new RuntimeException('Invalid device style settings.');
        $style=array_intersect_key(ag_pb_style($input[$device]),$keys); if($style) $out[$device]=$style;
    } return $out;
}
function ag_pb_menu_items($input,$depth=0,&$count=0) {
    if(!is_array($input) || count($input)>24 || $depth>2) throw new RuntimeException('Menus support 24 links per level and three levels.'); $out=array();
    foreach($input as $item) {
        if(!is_array($item) || ++$count>72) throw new RuntimeException('A menu supports up to 72 links.');
        $url=ag_pb_link($item['url'] ?? ''); if(ag_pd_text($item['url'] ?? '')!=='' && $url==='') throw new RuntimeException('Invalid menu link.');
        $entry=array('label'=>ag_pd_text($item['label'] ?? 'Link',120),'url'=>$url);
        if(!empty($item['pageId'])) { if(!ag_pd_page_file($item['pageId'])) throw new RuntimeException('Invalid linked page.'); $entry['pageId']=$item['pageId']; }
        if(isset($item['children']) && $item['children']) $entry['children']=ag_pb_menu_items($item['children'],$depth+1,$count); $out[]=$entry;
    } return $out;
}
function ag_pb_normalize($input) {
    if(!is_array($input) || !is_array($input['blocks'] ?? null)) throw new RuntimeException('Invalid webpage structure.');
    $ids=array(); $count=0;
    $walk=function($nodes,$depth) use (&$walk,&$ids,&$count) {
        if($depth>7 || !is_array($nodes)) throw new RuntimeException('Webpage nesting exceeds the supported limit.');
        $out=array();
        foreach($nodes as $node) {
            if(++$count>240 || !is_array($node)) throw new RuntimeException('A webpage supports up to 240 elements.');
            $type=ag_pd_enum($node['type'] ?? '',ag_pb_types(),''); if(!$type) throw new RuntimeException('Unknown webpage element.');
            $id=$node['id'] ?? ''; if(!is_string($id) || !preg_match('/^[a-f0-9]{12}$/',$id) || isset($ids[$id])) throw new RuntimeException('Webpage element identifiers must be unique.'); $ids[$id]=true;
            $raw=is_array($node['props'] ?? null)?$node['props']:array(); $props=array();
            foreach(array('text'=>12000,'title'=>240,'subtitle'=>5000,'label'=>120,'alt'=>300,'kicker'=>120,'brand'=>120,'attribution'=>240,'buttonLabel'=>120) as $k=>$max) if(isset($raw[$k])) $props[$k]=ag_pd_text($raw[$k],$max);
            foreach(array('url','buttonUrl','videoUrl') as $k) if(isset($raw[$k])) {
                $v=ag_pd_text($raw[$k],2048); $safe=ag_pb_link($v);
                if($v!=='' && $safe==='') throw new RuntimeException('Use an http, https, mailto, hop, /local or #section link.');
                if($k==='videoUrl' && $safe!=='' && !preg_match('#^(https?://|/(?!/))#i',$safe)) throw new RuntimeException('Use an HTTP or local video URL.');
                $props[$k]=$safe;
            }
            if(isset($raw['image'])) $props['image']=ag_pd_image_name($raw['image']);
            if(isset($raw['anchor'])) $props['anchor']=ag_pd_slug($raw['anchor']);
            if(isset($raw['tag'])) $props['tag']=ag_pd_enum($raw['tag'],array('h1','h2','h3','h4','h5','h6'),'h2');
            foreach(array('newTab','autoplay','showTitle','useGridName','fluidTitle','disabled','useSiteMenu') as $k) if(isset($raw[$k])) $props[$k]=!empty($raw[$k]);
            if(isset($raw['items']) && in_array($type,array('navigation','footer'),true)) $props['items']=ag_pb_menu_items($raw['items']);
            elseif(isset($raw['items'])) {
                if(!is_array($raw['items']) || count($raw['items'])>24) throw new RuntimeException('A component supports up to 24 entries.');
                $props['items']=array(); foreach($raw['items'] as $item) {
                    if(!is_array($item)) throw new RuntimeException('Invalid component entry.');
                    $entry=array(); foreach(array('label','value','question','answer') as $k) if(isset($item[$k])) $entry[$k]=ag_pd_text($item[$k],$k==='answer'?5000:240);
                    if(isset($item['url'])) { $entry['url']=ag_pb_link($item['url']); if(ag_pd_text($item['url'])!=='' && $entry['url']==='') throw new RuntimeException('Invalid navigation link.'); }
                    $props['items'][]=$entry;
                }
            }
            if($type==='form')$props=array_merge($props,ag_pf_props($raw));
            $props=array_merge($props,ag_pi_props($raw));
            $children=$node['children'] ?? array();
            if(in_array($type,array('tabs','accordion'),true)&&(!is_array($children)||count($children)>24))throw new RuntimeException('Tabs and accordions support up to 24 panels.');
            if(!in_array($type,array('section','columns','gallery','tabs','accordion'),true) && !empty($children)) throw new RuntimeException('This element cannot contain other elements.');
            $entry=array('id'=>$id,'type'=>$type,'name'=>ag_pd_text($node['name'] ?? ucfirst($type),80),'props'=>$props,'style'=>ag_pb_style($node['style'] ?? array()),'children'=>$walk($children,$depth+1));
            if(isset($node['locked']))$entry['locked']=!empty($node['locked']);
            if(isset($node['responsive'])) { $responsive=ag_pb_responsive($node['responsive']); if($responsive) $entry['responsive']=$responsive; }
            $out[]=$entry;
        }
        return $out;
    };
    $out=array('version'=>1,'theme'=>ag_pb_theme($input['theme'] ?? array()),'blocks'=>$walk($input['blocks'],0));
    if(isset($input['tokens'])) { if(!is_array($input['tokens']))throw new RuntimeException('Invalid page style tokens.');$defaults=array('headingSize'=>36,'textSize'=>17,'sectionSpacing'=>64,'gap'=>24,'buttonRadius'=>16,'buttonX'=>23,'buttonY'=>13);$out['tokens']=array();foreach(array('headingSize'=>array(8,160),'textSize'=>array(8,64),'sectionSpacing'=>array(0,200),'gap'=>array(0,120),'buttonRadius'=>array(0,64),'buttonX'=>array(0,100),'buttonY'=>array(0,100)) as $key=>$range)$out['tokens'][$key]=ag_pd_int($input['tokens'][$key] ?? $defaults[$key],$defaults[$key],$range[0],$range[1]); }
    if(isset($input['site'])) { if(!is_array($input['site'])) throw new RuntimeException('Invalid site inheritance settings.'); $out['site']=array(); foreach(array('styles','header','footer') as $key) $out['site'][$key]=!empty($input['site'][$key]); }
    if(isset($input['interactions']))$out['interactions']=ag_pi_settings($input['interactions']);
    return $out;
}
function ag_pb_images($builder) {
    $files=array(); if(!empty($builder['theme']['backgroundImage'])) $files[]=$builder['theme']['backgroundImage']; $walk=function($nodes) use(&$walk,&$files) { foreach($nodes as $n) { if(!empty($n['props']['image'])) $files[]=$n['props']['image']; $walk($n['children'] ?? array()); } }; $walk($builder['blocks'] ?? array()); return $files;
}
function ag_pb_uploads(&$builder,$files,$pageId,$allowed,&$added) {
    $old=$builder['theme']['backgroundImage'] ?? ''; if($old!=='' && !in_array($old,$allowed,true)) throw new RuntimeException('Unknown page background image.');
    $builder['theme']['backgroundImage']=ag_pd_store_image_upload($files['builder_background_image'] ?? null,$pageId,$old);
    if($builder['theme']['backgroundImage']!==$old) $added[]=$builder['theme']['backgroundImage'];
    $walk=function(&$nodes) use(&$walk,$files,$pageId,$allowed,&$added) {
        foreach($nodes as &$n) {
            $old=$n['props']['image'] ?? '';
            if($old!=='' && !in_array($old,$allowed,true)) throw new RuntimeException('Unknown webpage image.');
            if(isset($n['props']['image']) || isset($files['block_image_'.$n['id']])) {
                $new=ag_pd_store_image_upload($files['block_image_'.$n['id']] ?? null,$pageId,$old); $n['props']['image']=$new; if($new!==$old) $added[]=$new;
            }
            $walk($n['children']);
        } unset($n);
    }; $walk($builder['blocks']);
}
