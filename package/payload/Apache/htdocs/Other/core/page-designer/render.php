<?php
require_once __DIR__.'/legacy-render.php';
function ag_pb_background($image,$page,$fit='cover',$width=100,$height=100,$lock=true,$x=50,$y=50) {
    $url=ag_pd_asset_url($page['id'],$image); if(!$url) return '';
    $size=$fit==='stretch'?'100% 100%':($fit==='custom'?$width.'% '.($lock?'auto':$height.'%'):$fit);
    return 'background-image:linear-gradient(#0005,#0005),url('.json_encode($url,JSON_UNESCAPED_SLASHES).');background-size:'.$size.';background-position:'.$x.'% '.$y.'%;background-repeat:no-repeat;';
}
function ag_pb_css($node) {
    $s=$node['style']; $css='';
    foreach(array('background'=>'background','color'=>'color','borderColor'=>'border-color') as $k=>$attr) if(isset($s[$k])) {
        $v=$s[$k]; if(in_array($v,array('background','surface','text','muted','accent'),true)) $v='var(--wb-'.$v.')'; elseif($v==='white') $v='#ffffff';
        $css.=$attr.':'.$v.';'; if($k==='color') $css.='--block-color:'.$v.';';
    }
    foreach(array('padding'=>'padding','gap'=>'gap','radius'=>'border-radius','fontSize'=>'font-size','minHeight'=>'min-height','height'=>'height','marginTop'=>'margin-top','marginBottom'=>'margin-bottom','borderWidth'=>'border-width') as $k=>$attr) if(isset($s[$k])) $css.=$attr.':'.$s[$k].'px;';
    if(isset($s['paddingY'])) $css.='padding-top:'.$s['paddingY'].'px;padding-bottom:'.$s['paddingY'].'px;';
    if(isset($s['align'])) $css.='text-align:'.$s['align'].';';
    if(isset($s['vertical'])) $css.='align-items:'.$s['vertical'].';';
    if(isset($s['font'])) $css.='font-family:'.ag_pd_font_css($s['font']).';';
    if(isset($s['bold'])) $css.='font-weight:'.($s['bold']?'800':'400').';';
    if(isset($s['italic'])) $css.='font-style:'.($s['italic']?'italic':'normal').';';
    if(isset($s['columns'])) $css.='--columns:'.$s['columns'].';';
    if(isset($s['fontSize'])) $css.='--block-size:'.$s['fontSize'].'px;';
    if(isset($s['radius'])) $css.='--block-radius:'.$s['radius'].'px;';
    if(isset($s['bold'])) $css.='--block-weight:'.($s['bold']?'800':'400').';';
    if(isset($s['tabletColumns'])) $css.='--tablet-columns:'.$s['tabletColumns'].';';
    if(isset($s['mobileColumns'])) $css.='--mobile-columns:'.$s['mobileColumns'].';';
    if(isset($s['fit'])) $css.='--image-fit:'.$s['fit'].';';
    if(isset($s['shadow'])) $css.='box-shadow:'.array('none'=>'none','soft'=>'0 12px 36px #172b3110','strong'=>'0 24px 64px #172b3126')[$s['shadow']].';';
    if(isset($s['borderWidth'])) $css.='border-style:solid;';
    if(isset($s['gridSpan'])) $css.='--grid-span:'.$s['gridSpan'].';';
    if(isset($s['lineHeight'])) $css.='line-height:'.$s['lineHeight'].';--block-line-height:'.$s['lineHeight'].';';
    foreach(array('imageX'=>'--image-x','imageY'=>'--image-y') as $key=>$var) if(isset($s[$key])) $css.=$var.':'.$s[$key].'%;';
    if(!empty($node['props']['fluidTitle'])) $css.='font-size:clamp(18px,4vw,'.($s['fontSize'] ?? 46).'px);';
    if($node['type']==='button') {
        foreach(array('background'=>'--button-background','borderColor'=>'--button-border-color') as $key=>$var) if(isset($s[$key])) {
            $v=$s[$key]; if(in_array($v,array('background','surface','text','muted','accent'),true)) $v='var(--wb-'.$v.')'; elseif($v==='white') $v='#fff'; $css.=$var.':'.$v.';';
        }
        if(isset($s['borderWidth'])) $css.='--button-border-width:'.$s['borderWidth'].'px;';
        $css.='background:transparent;border:0;border-radius:0;box-shadow:none;';
    }
    return $css;
}
function ag_pb_image($node,$page) {
    $url=ag_pd_asset_url($page['id'],$node['props']['image'] ?? '');
    return $url ? '<img data-image-for="'.$node['id'].'" src="'.ag_pd_h($url).'" alt="'.ag_pd_h($node['props']['alt'] ?? '').'">' : '<div data-image-for="'.$node['id'].'" class="wb-image-placeholder"><svg viewBox="0 0 64 64" aria-hidden="true"><rect x="8" y="8" width="48" height="48" rx="8"/><circle cx="23" cy="23" r="5"/><path d="m10 48 15-16 10 10 9-11 10 17"/></svg><span>Add your image</span></div>';
}
function ag_pb_anchor($label,$url,$newTab=false,$class='') {
    return '<a class="'.ag_pd_h($class).'" href="'.ag_pd_h($url ?: '#').'"'.($newTab?' target="_blank" rel="noopener noreferrer"':'').'>'.ag_pd_h($label).'</a>';
}
function ag_pb_device_css($nodes) {
    $css=''; foreach($nodes as $n) {
        foreach(array('tablet'=>'(min-width:641px) and (max-width:1000px)','mobile'=>'(max-width:640px)') as $device=>$query) if(!empty($n['responsive'][$device])) {
            $override=$n; $override['style']=$n['responsive'][$device]; $override['props']['fluidTitle']=false;
            $css.='@media'.$query.'{[data-block-id="'.$n['id'].'"]{'.str_replace(';','!important;',ag_pb_css($override)).'}}';
        } $css.=ag_pb_device_css($n['children']);
    } return $css;
}
function ag_pb_menu_html($items) {
    $html=''; foreach($items as $item) {
        $url=$item['url'] ?? ''; if(!empty($item['pageId'])) { $linked=ag_pd_load_by_id($item['pageId']); if($linked && !empty($linked['published'])) $url='/Other/custom-page.php?page='.rawurlencode($linked['slug']); }
        $link=ag_pb_anchor($item['label'] ?? 'Link',$url);
        $html.=!empty($item['children'])?'<div class="wb-nav-group">'.$link.'<div class="wb-nav-dropdown">'.ag_pb_menu_html($item['children']).'</div></div>':$link;
    } return $html;
}
function ag_pb_nodes($nodes,$page) {
    $html=''; foreach($nodes as $n) {
        $p=ag_sd_bound_props($n['props'],$page['_designBranding'] ?? null); $type=$n['type']; $id=$n['id']; $s=$n['style'];
        if(!empty($p['useGridName']) && function_exists('ag_grid_name')) {
            if($type==='hero') $p['kicker']=trim(($p['kicker'] ?? '').' '.ag_grid_name());
            elseif($type==='navigation' || $type==='footer') $p['brand']=ag_grid_name();
        }
        if(!empty($p['useSiteMenu']) && function_exists('ag_pbl_site_live')) $p['items']=ag_pbl_site_live()['navigation'];
        $class='wb-block wb-'.($type==='button'?'button-block':$type);
        foreach(array('Desktop','Tablet','Mobile') as $device) if(!empty($s['hide'.$device])) $class.=' wb-hide-'.strtolower($device);
        if(isset($s['width'])) $class.=' wb-width-'.$s['width'];
        $attrs=ag_pi_attrs($n).' data-block-id="'.$id.'" data-block-type="'.$type.'" data-block-name="'.ag_pd_h($n['name']).'" class="'.$class.'" style="'.ag_pd_h(ag_pb_css($n).($type==='section'?ag_pb_background($p['image'] ?? '',$page,$s['backgroundFit'] ?? 'cover',$s['backgroundWidth'] ?? 100,$s['backgroundHeight'] ?? 100,$s['backgroundLock'] ?? true,$s['imageX'] ?? 50,$s['imageY'] ?? 50):'')).'"';
        if(!empty($p['anchor'])) $attrs.=' id="'.ag_pd_h($p['anchor']).'"';
        if($type==='tabs'||$type==='accordion'){$html.=ag_pi_container($n,$page,$attrs);}
        elseif($type==='section' || $type==='columns' || $type==='gallery') {
            $html.='<section'.$attrs.'><div class="wb-children" data-container-id="'.$id.'">'.ag_pb_nodes($n['children'],$page).'</div></section>';
        } elseif($type==='heading') { $tag=$p['tag'] ?? 'h2'; $html.='<'.$tag.$attrs.' data-edit-prop="text">'.ag_pd_h($p['text'] ?? '').'</'.$tag.'>'; }
        elseif($type==='text') $html.='<div'.$attrs.' data-edit-prop="text">'.ag_pd_h($p['text'] ?? '').'</div>';
        elseif($type==='image') $html.='<figure'.$attrs.'>'.ag_pb_image($n,$page).'</figure>';
        elseif($type==='site-branding') $html.='<div'.$attrs.'>'.ag_sd_brand_html($page['_designBranding'] ?? null).'</div>';
        elseif($type==='grid-login') $html.='<div'.$attrs.'>'.ag_sa_native_login($id,!empty($page['_builderPreview'])).'</div>';
        elseif($type==='form') $html.=ag_pf_html($n,$page,$attrs);
        elseif($type==='button') $html.='<div'.$attrs.'>'.(!empty($p['disabled'])?'<span class="wb-button" aria-disabled="true">'.ag_pd_h($p['label'] ?? 'Button').'</span>':ag_pb_anchor($p['label'] ?? 'Button',$p['url'] ?? '',!empty($p['newTab']),'wb-button')).'</div>';
        elseif($type==='navigation') {
            $html.='<nav'.$attrs.'><div class="wb-nav-inner"><strong data-edit-prop="brand">'.ag_pd_h($p['brand'] ?? '').'</strong><div class="wb-nav-links">';
            $html.=ag_pb_menu_html($p['items'] ?? array());
            if(!empty($p['buttonLabel'])) $html.=ag_pb_anchor($p['buttonLabel'],$p['buttonUrl'] ?? '',false,'wb-button');
            $html.='</div></div></nav>';
        } elseif($type==='hero') {
            $html.='<section'.$attrs.'><div class="wb-hero-inner"><div class="wb-hero-copy"><div class="wb-kicker" data-edit-prop="kicker">'.ag_pd_h($p['kicker'] ?? '').'</div><h1 data-edit-prop="title">'.ag_pd_h($p['title'] ?? '').'</h1><p data-edit-prop="subtitle">'.ag_pd_h($p['subtitle'] ?? '').'</p>';
            if(!empty($p['buttonLabel'])) $html.=ag_pb_anchor($p['buttonLabel'],$p['buttonUrl'] ?? '',false,'wb-button');
            $html.='</div><div class="wb-hero-media">';
            if(!empty($p['image'])) $html.=ag_pb_image($n,$page);
            else $html.='<div data-image-for="'.$id.'" class="wb-hero-art" aria-hidden="true"><div class="wb-orbit wb-orbit-one"></div><div class="wb-orbit wb-orbit-two"></div><div class="wb-orbit wb-orbit-three"></div><div class="wb-art-core"><svg viewBox="0 0 80 80"><path d="m40 8 28 16v32L40 72 12 56V24zM12 24l28 16 28-16M40 40v32"/></svg></div><i class="wb-art-spark"></i></div>';
            $html.='</div></div></section>';
        } elseif($type==='quote') $html.='<blockquote'.$attrs.'><span class="wb-quote-mark">“</span><p data-edit-prop="text">'.ag_pd_h($p['text'] ?? '').'</p><cite data-edit-prop="attribution">'.ag_pd_h($p['attribution'] ?? '').'</cite></blockquote>';
        elseif($type==='faq') {
            $html.='<section'.$attrs.'>'; foreach($p['items'] ?? array() as $item) $html.='<details class="wb-faq-item"><summary>'.ag_pd_h($item['question'] ?? '').'</summary><p>'.ag_pd_h($item['answer'] ?? '').'</p></details>'; $html.='</section>';
        } elseif($type==='stats') {
            $html.='<section'.$attrs.'>'; foreach($p['items'] ?? array() as $item) $html.='<div><strong>'.ag_pd_h($item['value'] ?? '').'</strong><span>'.ag_pd_h($item['label'] ?? '').'</span></div>'; $html.='</section>';
        } elseif($type==='video') {
            $html.='<div'.$attrs.'>'; if(!empty($p['videoUrl'])) $html.='<video controls preload="metadata"'.(!empty($p['image'])?' poster="'.ag_pd_h(ag_pd_asset_url($page['id'],$p['image'])).'"':'').'><source src="'.ag_pd_h($p['videoUrl']).'"></video>'; else $html.='<div class="wb-video-placeholder">▶<span>Add a video URL</span></div>'; $html.='</div>';
        } elseif($type==='divider') $html.='<div'.$attrs.'><hr></div>';
        elseif($type==='spacer') $html.='<div'.$attrs.' aria-hidden="true"></div>';
        elseif($type==='footer') {
            $html.='<footer'.$attrs.'><div class="wb-footer-inner"><div><strong data-edit-prop="brand">'.ag_pd_h($p['brand'] ?? '').'</strong><p data-edit-prop="text">'.ag_pd_h($p['text'] ?? '').'</p></div><div class="wb-nav-links">'; $html.=ag_pb_menu_html($p['items'] ?? array()); $html.='</div></div></footer>';
        } elseif($type==='legacy') {
            $legacy=ag_pd_render_legacy($page); preg_match('#<style>(.*?)</style>#s',$legacy,$styles); preg_match('#<body>(.*?)</body>#s',$legacy,$body);
            $legacyStyles=str_replace(array('html,body{','}body{'),array('.wb-legacy{','}.wb-legacy{'),$styles[1] ?? '');
            $legacyBody=str_replace('<div class="cp-bg" aria-hidden="true"></div>','',$body[1] ?? '');
            $legacyBg=ag_pd_asset_url($page['id'],$page['backgroundImage'] ?? '');
            if($legacyBg) $legacyStyles.='[data-block-id="'.$id.'"]{background-image:linear-gradient(#0005,#0005),url('.json_encode($legacyBg,JSON_UNESCAPED_SLASHES).');background-size:cover;background-position:center}';
            $html.='<section'.$attrs.'><style>'.$legacyStyles.'</style>'.$legacyBody.'</section>';
        }
    } return $html;
}
function ag_pd_render($page,$preview=false) {
    if(!isset($page['builder'])) return ag_seo_legacy(ag_pd_render_legacy($page,$preview),$page,$preview);
    $page=ag_pd_normalize($page); $page['_builderPreview']=$preview; $builder=$page['builder'];
    // A legacy-only page uses its exact existing renderer, including its background.
    if(count($builder['blocks'])===1 && $builder['blocks'][0]['type']==='legacy' && empty($builder['site']['header']) && empty($builder['site']['footer'])) {
        $n=$builder['blocks'][0]; return ag_seo_legacy(str_replace('<main class="cp-wrap">','<main class="cp-wrap" data-block-id="'.$n['id'].'" data-block-type="legacy" data-block-name="Existing page">',ag_pd_render_legacy($page,$preview)),$page,$preview);
    }
    $site=function_exists('ag_pbl_site_live')?ag_pbl_site_live():null;
    if($site && !empty($builder['site']['styles'])) $builder['theme']=array_merge($builder['theme'],$site['theme']);
    $t=$builder['theme']; $siteCss=$site && !empty($builder['site']['styles'])?ag_pbl_site_css($site):(!empty($builder['tokens'])?ag_pbl_site_css(array('styles'=>$builder['tokens'])):'');
    $shared=function_exists('ag_pbl_shared_html')?ag_pbl_shared_html($page,$builder,$site):array('header'=>'','footer'=>'');
    ob_start(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=ag_pd_h($page['seo']['title'] ?? '' ?: $page['title'])?></title><?=ag_seo_head($page,$preview)?><style>
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background-image:var(--site-background-image,none);background-size:cover;background-attachment:fixed;background-color:var(--site-background,<?=$t['background']?>);color:var(--site-text,<?=$t['text']?>);font-size:var(--site-body-size,16px);font-family:var(--site-font,<?=ag_pd_font_css($t['font'])?>);line-height:1.6;--wb-background:var(--site-background,<?=$t['background']?>);--wb-text:var(--site-text,<?=$t['text']?>);--wb-accent:var(--site-accent,<?=$t['accent']?>);--wb-surface:var(--site-surface,<?=$t['surface']?>);--wb-muted:var(--site-muted,<?=$t['muted']?>);--wb-width:<?=$t['width']?>px;--wb-radius:<?=$t['radius']?>px}h1,h2,h3,h4,h5,h6,p,figure,blockquote{margin:0}button,a,input,summary{-webkit-tap-highlight-color:transparent}a{color:inherit}img,video{max-width:100%}.wb-block{position:relative;min-width:0;overflow-wrap:anywhere}.wb-section{padding:var(--wb-section-space,64px) 32px;gap:var(--wb-gap,normal)}.wb-section>.wb-children{max-width:var(--wb-width);margin:auto;display:flex;flex-direction:column;gap:inherit}.wb-section:empty,.wb-children:empty{min-height:100px}.wb-columns,.wb-gallery{padding:0}.wb-columns>.wb-children,.wb-gallery>.wb-children{display:grid;grid-template-columns:repeat(var(--columns,3),minmax(0,1fr));gap:inherit;align-items:inherit}.wb-heading{font-size:var(--wb-heading-size,36px);line-height:var(--block-line-height,1.16);font-weight:750;letter-spacing:-.025em}.wb-text{white-space:pre-wrap;font-size:var(--wb-text-size,17px);line-height:var(--block-line-height,1.7)}.wb-image{margin:0;border-radius:var(--wb-radius);overflow:hidden}.wb-image img{object-position:var(--image-x,50%) var(--image-y,50%);display:block;width:100%;height:inherit;object-fit:var(--image-fit,cover)}.wb-image-placeholder,.wb-video-placeholder{min-height:240px;background:linear-gradient(135deg,#e8edf0,#d8e5e6);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:#638086}.wb-image-placeholder svg{width:60px;fill:none;stroke:currentColor;stroke-width:2}.wb-video-placeholder{font-size:40px}.wb-video-placeholder span{font-size:14px}.wb-button{border:var(--button-border-width,0) solid var(--button-border-color,transparent)!important;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:var(--block-radius,var(--site-button-radius,var(--wb-button-radius,var(--wb-radius))));background:var(--button-background,var(--wb-accent));color:var(--block-color,var(--site-accent-text,#fff));text-decoration:none;padding:var(--wb-button-y,13px) var(--wb-button-x,23px);font-weight:var(--block-weight,650);font-size:var(--block-size,15px);line-height:var(--block-line-height,1.5);box-shadow:0 6px 20px #00000008;transition:filter .2s,transform .2s}.wb-button:hover{filter:brightness(1.08);transform:translateY(-1px)}.wb-button:focus-visible,a:focus-visible,summary:focus-visible{outline:3px solid var(--wb-accent);outline-offset:4px}.wb-navigation{padding:20px 32px;background:var(--wb-surface);border-bottom:1px solid #879ba31f}.wb-nav-inner,.wb-footer-inner{max-width:var(--wb-width);margin:auto;display:flex;align-items:center;justify-content:space-between;gap:24px}.wb-nav-inner>strong{font-size:var(--block-size,21px);letter-spacing:-.03em}.wb-nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap}.wb-nav-links>a{text-decoration:none;font-size:14px;font-weight:550}.wb-hero{padding:84px 32px;background:var(--wb-surface)}.wb-hero-inner{max-width:var(--wb-width);margin:auto;display:grid;grid-template-columns:1.15fr 1fr;align-items:center;gap:70px}.wb-hero-copy h1{font-size:var(--block-size,clamp(38px,5.5vw,76px));font-weight:var(--block-weight,800);letter-spacing:-.055em;line-height:var(--block-line-height,1.06);margin:18px 0 24px;white-space:pre-wrap}.wb-hero-copy p{font-size:18px;color:var(--wb-muted);max-width:570px;margin-bottom:30px;white-space:pre-wrap}.wb-kicker{display:inline-block;font-size:11px;font-weight:750;letter-spacing:.16em;text-transform:uppercase;color:var(--wb-accent);border:1px solid currentColor;padding:6px 11px;border-radius:100px}.wb-hero-media img{width:100%;min-height:300px;max-height:560px;object-fit:cover;border-radius:var(--wb-radius)}.wb-hero-art{height:430px;border-radius:calc(var(--wb-radius)*2);position:relative;overflow:hidden;background:radial-gradient(circle at 25% 15%,#ffffffa0,transparent 50%),linear-gradient(145deg,#d8efed,#b8dad7);display:grid;place-items:center;isolation:isolate}.wb-orbit{position:absolute;border:1px solid #087f7530;border-radius:50%;width:360px;height:360px;transform:rotate(-25deg) scaleY(.62)}.wb-orbit-two{transform:rotate(35deg) scaleY(.62)}.wb-orbit-three{transform:rotate(95deg) scaleY(.62)}.wb-art-core{width:145px;height:145px;border-radius:32px;display:grid;place-items:center;background:var(--wb-accent);box-shadow:0 35px 55px #087f7530;transform:rotate(-12deg)}.wb-art-core svg{width:90px;fill:none;stroke:#fff;stroke-width:1.6}.wb-art-spark{position:absolute;left:20%;top:21%;width:20px;height:20px;border-radius:50%;background:#ffffff;box-shadow:210px 210px 0 6px #ffffff90}.wb-quote{padding:40px;border-radius:var(--wb-radius);background:var(--wb-surface)}.wb-quote-mark{font:80px Georgia,serif;color:var(--wb-accent);line-height:.7}.wb-quote p{font-size:var(--block-size,27px);line-height:var(--block-line-height,1.45);letter-spacing:-.025em;margin:12px 0 24px;white-space:pre-wrap}.wb-quote cite{font-size:14px;font-style:normal;color:var(--wb-muted)}.wb-faq-item{border-bottom:1px solid #879ba333;padding:20px 0}.wb-faq-item summary{cursor:pointer;font-weight:650;font-size:18px}.wb-faq-item p{padding-top:12px;color:var(--wb-muted);white-space:pre-wrap}.wb-stats{display:grid;grid-template-columns:repeat(var(--columns,3),minmax(0,1fr));gap:30px;text-align:center;padding:32px}.wb-stats strong{display:block;font-size:var(--block-size,44px);letter-spacing:-.04em;color:var(--wb-accent)}.wb-stats span{font-size:14px;color:var(--wb-muted)}.wb-video video{width:100%;display:block;border-radius:var(--wb-radius)}.wb-divider{padding:16px 0}.wb-divider hr{border:0;border-top:1px solid #879ba333;margin:0}.wb-spacer{height:60px}.wb-footer{padding:40px 32px;background:var(--wb-surface);border-top:1px solid #879ba333}.wb-footer p{color:var(--wb-muted);font-size:13px;margin-top:8px;white-space:pre-wrap}.wb-width-wide{max-width:var(--wb-width);margin-left:auto;margin-right:auto;width:100%}.wb-width-medium{max-width:850px;margin-left:auto;margin-right:auto;width:100%}.wb-width-narrow{max-width:620px;margin-left:auto;margin-right:auto;width:100%}.wb-width-full{width:100%}.wb-legacy .cp-wrap{color:#eef2ef}.wb-draft{padding:8px;background:#fff2c7;color:#6a4c0b;text-align:center;font-size:11px;font-weight:700}
@media(min-width:1001px){.wb-hide-desktop{display:none!important}}@media(min-width:641px) and (max-width:1000px){.wb-hide-tablet{display:none!important}.wb-columns>.wb-children,.wb-gallery>.wb-children{grid-template-columns:repeat(var(--tablet-columns,2),minmax(0,1fr))}.wb-hero-inner{gap:30px}.wb-hero-art{height:330px}.wb-nav-links{gap:16px}}
@media(max-width:640px){.wb-hide-mobile{display:none!important}.wb-section,.wb-hero{padding-left:20px!important;padding-right:20px!important}.wb-section{padding-top:40px;padding-bottom:40px}.wb-columns>.wb-children,.wb-gallery>.wb-children{grid-template-columns:repeat(var(--mobile-columns,1),minmax(0,1fr))}.wb-hero{padding-top:48px;padding-bottom:48px}.wb-hero-inner{grid-template-columns:1fr;gap:32px}.wb-hero-art{height:300px}.wb-navigation{padding:16px 20px}.wb-nav-inner{align-items:flex-start;flex-direction:column;gap:14px}.wb-nav-links{gap:16px}.wb-nav-links .wb-button{padding:8px 13px}.wb-footer-inner{flex-direction:column;align-items:flex-start}.wb-quote{padding:26px}.wb-quote p{font-size:22px}.wb-stats{grid-template-columns:repeat(var(--mobile-columns,1),minmax(0,1fr))}.wb-heading{font-size:30px}}
.wb-page{position:relative;z-index:1}.wb-background{position:fixed;inset:0;pointer-events:none}.wb-columns>.wb-children>.wb-block{grid-column:span var(--grid-span,1)}@media(max-width:1000px){.wb-columns>.wb-children>.wb-block{grid-column:span 1}}
.wb-nav-group{position:relative}.wb-nav-group>a{text-decoration:none;font-size:14px}.wb-nav-dropdown{display:none;position:absolute;top:100%;left:0;min-width:190px;padding:12px;background:var(--wb-surface);border:1px solid #879ba333;border-radius:8px;z-index:20;box-shadow:0 12px 30px #0002}.wb-nav-dropdown a{display:block;text-decoration:none;padding:8px;font-size:14px}.wb-nav-group:hover>.wb-nav-dropdown,.wb-nav-group:focus-within>.wb-nav-dropdown{display:block}.wb-nav-dropdown .wb-nav-dropdown{left:100%;top:0}@media(max-width:640px){.wb-nav-dropdown,.wb-nav-dropdown .wb-nav-dropdown{position:static;display:block;box-shadow:none;margin-top:6px}}
<?=ag_pf_css()?><?=$siteCss?><?=ag_pb_device_css($builder['blocks'])?>
<?=ag_pi_css()?>
</style><link rel="stylesheet" href="/Other/site-design/member-login.css"><?=ag_sa_link($page['_designScope'] ?? 'designer').ag_sd_brand_head($page['_designBranding'] ?? null).(!empty($page['_designTheme'])?'<style>'.ag_sa_css($page['_designTheme']).ag_sd_effective_brand_css($page['_designTheme'],$page['_designBranding'] ?? array()).'</style>':'')?></head><body data-motion-mobile="<?=!isset($builder['interactions']['disableMobile'])||!empty($builder['interactions']['disableMobile'])?'off':'on'?>"><div class="wb-background" aria-hidden="true" style="<?=ag_pd_h(ag_pb_background($t['backgroundImage'],$page,$t['backgroundFit'],$t['backgroundWidth'],$t['backgroundHeight'],$t['backgroundLock'],$t['backgroundPositionX'],$t['backgroundPositionY']))?>"></div><?php if($preview && empty($page['published'])): ?><div class="wb-draft">ADMIN PREVIEW · UNPUBLISHED PAGE</div><?php endif ?><main class="wb-page"><?=$shared['header']?><?=ag_pb_nodes($builder['blocks'],$page)?><?=$shared['footer']?></main><?php if(!empty($builder['interactions']['backToTop'])): ?><button type="button" class="wb-top" data-back-to-top aria-label="Back to top" hidden>↑</button><?php endif ?><?php if(!$preview): ?><script src="/Other/page-designer/interactions-public.js?v=interactions-seo-1" defer></script><script src="/Other/page-designer/forms-public.js?v=interactions-seo-1" defer></script><?php endif ?></body></html>
<?php return ob_get_clean();
}
