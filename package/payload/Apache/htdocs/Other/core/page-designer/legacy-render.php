<?php
// One renderer serves saved public pages and unsaved designer previews.
function ag_pd_h($v) { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function ag_pd_style($t,$key) { return 'font-family:'.ag_pd_font_css($t[$key.'Font']).';font-size:'.$t[$key.'Size'].'px;font-weight:'.($t[$key.'Bold']?'800':'400').';font-style:'.($t[$key.'Italic']?'italic':'normal').';color:'.$t[$key.'Color'].';text-align:'.$t[$key.'Align'].';'; }
function ag_pd_render_legacy($page,$preview=false) {
    $p=ag_pd_normalize($page); $t=$p['typography']; $size=$p['backgroundFit'];
    if($size==='stretch') $size='100% 100%'; elseif($size==='custom') $size=$p['backgroundWidth'].'% '.($p['backgroundLock']?'auto':$p['backgroundHeight'].'%');
    $bg=ag_pd_asset_url($p['id'],$p['backgroundImage']);
    ob_start(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=ag_pd_h($p['title'])?></title>
<style>
*{box-sizing:border-box}html,body{margin:0;min-height:100%;background:var(--site-background,#111615);color:var(--site-text,#eef2ef)}body{min-height:100vh;overflow-x:hidden;font-family:var(--site-font,<?=ag_pd_font_css($t['globalFont'])?>);background-color:var(--site-background,<?=ag_pd_h($p['backgroundColor'])?>)}.cp-bg{position:fixed;inset:0;z-index:0;pointer-events:none;background-position:center,<?=$p['backgroundPositionX']?>% <?=$p['backgroundPositionY']?>%;background-size:100% 100%,<?=ag_pd_h($size)?>;background-repeat:no-repeat;background-image:linear-gradient(rgba(0,0,0,.28),rgba(0,0,0,.28))<?php if($bg): ?>,url(<?=json_encode($bg,JSON_UNESCAPED_SLASHES)?>)<?php endif ?>}.cp-wrap{position:relative;z-index:1;width:min(1500px,calc(100% - 36px));margin:0 auto;padding:34px 0 50px}.cp-title{margin:0 0 26px;text-shadow:0 3px 16px #000b}.cp-grid{display:grid;grid-template-columns:repeat(<?=$p['columns']?>,minmax(0,1fr));gap:18px}.cp-card{min-width:0;padding:18px;border:1px solid rgba(211,165,43,.4);box-shadow:0 12px 28px #0005;backdrop-filter:blur(2px)}.cp-card img{display:block;width:100%;margin-bottom:14px;background:#0b0f0e}.cp-card h2{margin:0 0 9px}.cp-card p{margin:0;white-space:pre-wrap;line-height:1.6;overflow-wrap:anywhere}.cp-link{display:inline-block;margin-top:14px;padding:9px 12px;border:1px solid #8e7127;background:linear-gradient(#25261f,#15160f);text-decoration:none;letter-spacing:.04em;overflow-wrap:anywhere}.cp-link:hover{border-color:#e1b640;color:#fff1a3}.cp-link-disabled{cursor:default;opacity:.9}.cp-draft{margin-bottom:18px;padding:8px 12px;border:1px solid #8a6c24;background:#241f10;color:#f0c85a;font-size:11px;font-weight:800;text-align:center}@media(max-width:1000px){.cp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.cp-card{grid-column:span 1!important}}@media(max-width:640px){.cp-grid{grid-template-columns:1fr}.cp-card{grid-column:span 1!important}.cp-wrap{width:calc(100% - 20px);padding-top:20px}}
</style><?=ag_sa_link('designer')?></head><body><div class="cp-bg" aria-hidden="true"></div><main class="cp-wrap">
<?php if($preview && !$p['published']): ?><div class="cp-draft">ADMIN PREVIEW — THIS PAGE IS NOT PUBLISHED</div><?php endif ?>
<h1 class="cp-title" style="<?=ag_pd_h(ag_pd_style($t,'title'))?>font-size:clamp(18px,4vw,<?=$t['titleSize']?>px)"><?=ag_pd_h($p['title'])?></h1><div class="cp-grid">
<?php foreach($p['cards'] as $c): $ct=ag_pd_effective_card_typography($c,$t); $pic=$c['picture']; $url=ag_pd_asset_url($p['id'],$c['image']); ?>
<article class="cp-card" data-card-id="<?=ag_pd_h($c['id'])?>" style="grid-column:span <?=min($c['span'],$p['columns'])?>;background:<?=ag_pd_h($c['backgroundColor'])?>">
<img <?=!$url?'hidden':''?> src="<?=ag_pd_h($url)?>" alt="" style="height:<?=$pic['height']?>px;object-fit:<?=$pic['fit']==='stretch'?'fill':$pic['fit']?>;object-position:<?=$pic['x']?>% <?=$pic['y']?>%;<?=!$url?'display:none;':''?>">
<?php if($c['title']!==''): ?><h2 style="<?=ag_pd_h(ag_pd_style($ct,'title'))?>"><?=ag_pd_h($c['title'])?></h2><?php endif ?>
<?php if($c['text']!==''): ?><p style="<?=ag_pd_h(ag_pd_style($ct,'text'))?>"><?=ag_pd_h($c['text'])?></p><?php endif ?>
<?php if($c['linkUrl']!==''): ?><a class="cp-link" style="<?=ag_pd_h(ag_pd_style($ct,'button'))?>" href="<?=ag_pd_h($c['linkUrl'])?>"<?=$c['newTab']?' target="_blank" rel="noopener noreferrer"':''?>><?=ag_pd_h($c['linkLabel'] ?: 'OPEN LINK')?></a>
<?php elseif($c['linkLabel']!==''): ?><span class="cp-link cp-link-disabled" aria-disabled="true" style="<?=ag_pd_h(ag_pd_style($ct,'button'))?>"><?=ag_pd_h($c['linkLabel'])?></span><?php endif ?></article>
<?php endforeach ?></div></main></body></html>
<?php return ob_get_clean();
}
