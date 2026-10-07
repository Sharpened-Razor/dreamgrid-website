<?php
// Read-only checks of the proposed page and the published shared site content.
// No HTTP requests, uploads or page writes are performed here.
function ag_pc_document($page) {
    if(!class_exists('DOMDocument')) throw new RuntimeException('Publish checks require the PHP DOM extension.');
    $doc=new DOMDocument();$old=libxml_use_internal_errors(true);
    try{if(!$doc->loadHTML('<?xml encoding="UTF-8">'.ag_pd_render($page,true),LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING))throw new RuntimeException('The page preview could not be checked.');}
    finally{libxml_clear_errors();libxml_use_internal_errors($old);}return $doc;
}
function ag_pc_location($element) {
    $target='';$label='Page';$source='page';
    for($n=$element;$n instanceof DOMElement;$n=$n->parentNode){
        if($target===''&&$n->hasAttribute('data-card-id')){$target='card:'.$n->getAttribute('data-card-id');$label='Existing page card';}
        if($target===''&&$n->hasAttribute('data-block-id')){$target=$n->getAttribute('data-block-id');$label=$n->getAttribute('data-block-name') ?: 'Element';}
        if($n->hasAttribute('data-global-section')){$source=$n->getAttribute('data-global-section');$target='';$label='Shared '.$source;}
    }return array('target'=>$target,'label'=>$label,'source'=>$source);
}
function ag_pc_anchors($doc) {$ids=array();foreach($doc->getElementsByTagName('*') as $n)if($n->hasAttribute('id')&&$n->getAttribute('id')!==''){$id=$n->getAttribute('id');$ids[$id][]=$n;}return $ids;}
function ag_pc_check($input,$pending=array(),$host='') {
    $p=ag_pd_normalize($input);if(!is_array($pending)||count($pending)>300)throw new RuntimeException('Invalid pending image list.');
    $available=array();foreach($pending as $key){if(!is_string($key)||!preg_match('/^(builder_background_image|background_image|block_image_[a-f0-9]{12}|image_[a-f0-9]{12})$/',$key))throw new RuntimeException('Invalid pending image reference.');$available[$key]=true;}
    $doc=ag_pc_document($p);$anchors=ag_pc_anchors($doc);$issues=array();$seen=array();$external=0;$pendingUsed=array();$checkedLinks=0;$checkedImages=0;
    $add=function($code,$severity,$message,$where,$value='',$hint='')use(&$issues,&$seen){$key=$code.'|'.$where['source'].'|'.$where['target'].'|'.$value.'|'.$message;if(isset($seen[$key]))return;$seen[$key]=true;$issues[]=array_merge($where,array('code'=>$code,'severity'=>$severity,'message'=>$message,'value'=>$value,'hint'=>$hint));};
    $rootLocation=array('target'=>'','label'=>'Page','source'=>'page');
    if($p['title']==='')$add('missing_title','error','The page needs a title.',$rootLocation,'','Enter Page title in Page settings.');
    elseif(ag_pd_slug_in_use($p['slug'],$p['id']))$add('duplicate_slug','error','Another page already uses this URL slug.',$rootLocation,$p['slug'],'Choose a unique URL slug in Page settings.');
    $image=function($name,$key,$where,$kind='page',$id='')use(&$image,$p,$available,&$pendingUsed,&$checkedImages,$add){
        if($kind==='page'&&isset($available[$key])){$pendingUsed[$key]=true;$checkedImages++;return;}
        if($name==='')return;$checkedImages++;$path=null;try{$path=$kind==='page'?ag_pd_asset_path($p['id'],$name):ag_pbl_asset('section',$id,$name);}catch(Throwable $e){}
        if(!$path){$add('missing_image','error','A referenced image is missing.',$where,$name,'Choose a replacement image.');return;}
        $info=@getimagesize($path);if(!$info||!in_array($info['mime'] ?? '',array('image/png','image/jpeg','image/gif','image/webp'),true))$add('invalid_image','error','An image file is unreadable or unsupported.',$where,$name,'Replace this image with a supported file.');
    };
    if(isset($p['seo'])){if($p['seo']['description']==='')$add('seo_description','warning','The search description is empty.',$rootLocation,'','Add a description in Page settings → Search & sharing.');$image($p['seo']['image'],'',$rootLocation);if($p['seo']['image']!==''&&$p['seo']['imageAlt']==='')$add('seo_image_alt','warning','The sharing image needs a description.',$rootLocation);}
    $site=ag_pbl_site_live();$nodes=isset($p['builder'])?$p['builder']['blocks']:array();$legacy=!isset($p['builder']);$legacyId='';
    $menu=function($items,$where)use(&$menu,$p,$add){foreach($items as $item){if(!empty($item['pageId'])&&$item['pageId']!==$p['id']){$linked=ag_pd_load_by_id($item['pageId']);if(!$linked||empty($linked['published']))$add('unpublished_menu_page','error','A menu destination is deleted or not published.',$where,$item['label'] ?? 'Menu link','Publish that page or choose another destination.');}if(!empty($item['children']))$menu($item['children'],$where);}};
    $walk=function($items,$source='page',$entryId='')use(&$walk,&$legacy,&$legacyId,$p,$available,$image,$add,$site,$menu){foreach($items as $n){$where=array('target'=>$source==='page'?$n['id']:'','label'=>$source==='page'?$n['name']:'Shared '.$source,'source'=>$source);$type=$n['type'];$props=$n['props'];if($type==='form'){
            if(!$props['fields'])$add('empty_form','error','A form needs at least one field.',$where);
            foreach($props['fields'] as $f)if($f['type']==='select'&&!$f['options'])$add('empty_form_options','error','A form dropdown has no options.',$where,$f['label']);
            if($props['handling']!=='local'){if($props['recipient']==='')$add('form_recipient','error','A form email notification needs a recipient.',$where);$mail=ag_pf_mail_status();if(!$mail['configured']||!$mail['enabled'])$add('form_mail_setup','warning','DreamGrid SMTP is unavailable or disabled. Messages remain in the inbox for later delivery.',$where);}
        }if($type==='legacy'){$legacy=true;if($source==='page')$legacyId=$n['id'];}
        if(in_array($type,array('image','hero','section','video'),true)){$key='block_image_'.$n['id'];$name=$props['image'] ?? '';$waiting=$source==='page'&&isset($available[$key]);$image($name,$key,$where,$source==='page'?'page':'section',$entryId);
            if($type==='image'&&$name===''&&!$waiting)$add('empty_image','warning','An image element still contains a placeholder.',$where,'','Choose an image or remove the unused element.');
            if(in_array($type,array('image','hero'),true)&&($name!==''||$waiting)&&trim($props['alt'] ?? '')==='')$add('missing_alt','warning','An image has no description.',$where,'','Add Alternative text in Content. Decorative images may intentionally have an empty description.');
        }
        if(in_array($type,array('navigation','footer'),true))$menu(!empty($props['useSiteMenu'])?$site['navigation']:($props['items'] ?? array()),$where);
        $walk($n['children'],$source,$entryId);
    }};
    if(isset($p['builder'])){$image($p['builder']['theme']['backgroundImage'] ?? '','builder_background_image',array('target'=>'','label'=>'Page background','source'=>'page'));$walk($nodes);
        foreach(array('header','footer') as $role)if(!empty($p['builder']['site'][$role])&&!empty($site[$role])){try{$entry=ag_pbl_get('section',$site[$role]);$walk(array($entry['block']),$role,$entry['id']);}catch(Throwable $e){$add('missing_shared_section','error','The assigned shared '.$role.' is unavailable.',array('target'=>'','label'=>'Shared '.$role,'source'=>$role),'','Choose an available section in Site settings.');}}
    }
    if($legacy){$image($p['backgroundImage'],'background_image',array('target'=>$legacyId,'label'=>'Existing page background','source'=>'page'));foreach($p['cards'] as $card){$where=array('target'=>'card:'.$card['id'],'label'=>$card['title'] ?: 'Existing page card','source'=>'page');$key='image_'.$card['id'];$image($card['image'],$key,$where);if($card['image']!==''||isset($available[$key]))$add('legacy_image_description','warning','An existing-style card image has an empty description.',$where,'','Convert the Existing page section to native elements to set Alternative text.');}}
    foreach($anchors as $id=>$elements)if(count($elements)>1)foreach($elements as $element)$add('duplicate_anchor','error','The anchor #'.$id.' appears more than once.',ag_pc_location($element),'#'.$id,'Give each section a unique anchor name.');
    $documents=array();$hostParts=@parse_url('http://'.$host);$localHost=strtolower($hostParts['host'] ?? '');$localPort=$hostParts['port'] ?? null;$webRoot=realpath(dirname(__DIR__,3));
    foreach($doc->getElementsByTagName('a') as $link){$url=trim($link->getAttribute('href'));$where=ag_pc_location($link);$checkedLinks++;
        if($url===''||$url==='#'){$add('empty_link','warning','A link has no destination.',$where,$link->textContent,'Set a destination or disable/remove the unused link.');continue;}
        $parts=@parse_url($url);if($parts===false){$add('invalid_link','error','A link could not be read.',$where,$url,'Choose a valid URL.');continue;}
        $scheme=strtolower($parts['scheme'] ?? '');if($scheme!==''&&!in_array($scheme,array('http','https'),true)){$external++;continue;}
        if(!empty($parts['host'])&&($localHost===''||strtolower($parts['host'])!==$localHost||($parts['port'] ?? null)!==$localPort)){$external++;continue;}
        $path=rawurldecode($parts['path'] ?? '');$fragment=rawurldecode($parts['fragment'] ?? '');$targetAnchors=null;$targetPage=null;
        if($path==='')$targetAnchors=$anchors;
        elseif(strcasecmp($path,'/Other/custom-page.php')===0){$query=array();parse_str($parts['query'] ?? '',$query);$slug=isset($query['page'])&&is_string($query['page'])?$query['page']:'';
            if(!empty($query['preview'])){$add('preview_link','warning','A link points to an administrator preview.',$where,$url,'Use the published page URL for visitors.');continue;}
            if($slug!==''&&$slug===$p['slug']){$targetPage=$p;$targetAnchors=$anchors;}else{$targetPage=$slug!==''?ag_pd_load_by_slug($slug):null;
                if(!$targetPage||empty($targetPage['published'])){$add('broken_page_link','error','An internal page destination is missing or not published.',$where,$url,'Publish the destination or update the link.');continue;}
                if($targetPage['id']===$p['id'])$targetAnchors=$anchors;
                elseif($fragment!==''){if(!isset($documents[$targetPage['id']]))$documents[$targetPage['id']]=ag_pc_anchors(ag_pc_document($targetPage));$targetAnchors=$documents[$targetPage['id']];}
            }
            $levels=array('public'=>0,'members'=>1,'admin'=>2);if(($levels[$targetPage['access']] ?? 0)>($levels[$p['access']] ?? 0))$add('restricted_link','warning','Some visitors may not have access to this destination.',$where,$url,'Check the destination page access rule.');
        }else{
            if($path[0]!=='/'||strpos($path,"\0")!==false||strpos($path,'\\')!==false){$add('invalid_internal_path','error','An internal path is invalid.',$where,$url);continue;}
            $resolved=$webRoot?realpath($webRoot.'/'.ltrim($path,'/')):false;$inside=$resolved&&($resolved===$webRoot||stripos(str_replace('\\','/',$resolved),str_replace('\\','/',$webRoot).'/')===0);
            if(!$inside||(!is_file($resolved)&&!(is_dir($resolved)&&(is_file($resolved.'/index.php')||is_file($resolved.'/index.html')))))$add('missing_local_link','error','A local link has no matching file or index page.',$where,$url,'Correct the path. Rewritten routes may need manual verification.');
        }
        if($fragment!==''&&$targetAnchors!==null&&!isset($targetAnchors[$fragment]))$add('missing_anchor','error','The destination anchor #'.$fragment.' does not exist.',$where,$url,'Set a matching section anchor or update the link.');
    }
    $errors=0;$warnings=0;foreach($issues as $issue){if($issue['severity']==='error')$errors++;else $warnings++;}
    return array('issues'=>$issues,'errors'=>$errors,'warnings'=>$warnings,'checkedLinks'=>$checkedLinks,'checkedImages'=>$checkedImages,'pendingImages'=>count($pendingUsed),'externalLinksSkipped'=>$external,'scope'=>'Current page, published shared sections, local file paths and published custom-page anchors. External links and dynamic route responses are not requested. Pending image uploads are validated when saving.');
}
