<?php
function ag_pf_props($raw) {
    $out=array('title'=>ag_pd_text($raw['title'] ?? 'Get in touch',240),'text'=>ag_pd_text($raw['text'] ?? '',5000),'buttonLabel'=>ag_pd_text($raw['buttonLabel'] ?? 'Send message',120),'successMessage'=>ag_pd_text($raw['successMessage'] ?? 'Thank you. Your message has been received.',1000),'handling'=>ag_pd_enum($raw['handling'] ?? 'local',array('local','email','both'),'local'),'recipient'=>trim(ag_pd_text($raw['recipient'] ?? '',254)),'fields'=>array());
    if($out['recipient']!==''&&!filter_var($out['recipient'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid form notification email address.');
    $fields=$raw['fields'] ?? array();if(!is_array($fields)||count($fields)>24)throw new RuntimeException('Forms support up to 24 fields.');$ids=array();
    foreach($fields as $f){if(!is_array($f)||!preg_match('/^[a-f0-9]{12}$/',$f['id'] ?? '')||isset($ids[$f['id']]))throw new RuntimeException('Form field identifiers must be unique.');$ids[$f['id']]=true;
        $type=ag_pd_enum($f['type'] ?? '',array('text','email','tel','url','number','textarea','select','checkbox'),'');if(!$type)throw new RuntimeException('Unsupported form field type.');
        $field=array('id'=>$f['id'],'type'=>$type,'label'=>ag_pd_text($f['label'] ?? 'Field',120),'placeholder'=>ag_pd_text($f['placeholder'] ?? '',200),'help'=>ag_pd_text($f['help'] ?? '',300),'required'=>!empty($f['required']),'maxLength'=>ag_pd_int($f['maxLength'] ?? 1000,1000,1,$type==='textarea'?5000:1000),'options'=>array());
        if($field['label']==='')throw new RuntimeException('Every form field needs a label.');
        foreach(array('min','max') as $key){$v=$f[$key] ?? '';if($v!==''&&$v!==null){if(!is_numeric($v)||!is_finite((float)$v)||abs((float)$v)>1000000000)throw new RuntimeException('Invalid numeric field limit.');$field[$key]=(float)$v;}}
        if(isset($field['min'],$field['max'])&&$field['min']>$field['max'])throw new RuntimeException('Field minimum exceeds maximum.');
        if($type==='select'){$opts=$f['options'] ?? array();if(!is_array($opts)||count($opts)>24)throw new RuntimeException('Dropdowns support up to 24 options.');foreach($opts as $v){$v=ag_pd_text($v,120);if($v!==''&&!in_array($v,$field['options'],true))$field['options'][]=$v;}}
        $out['fields'][]=$field;
    }return $out;
}
function ag_pf_values($props,$raw){
    if(!is_array($raw)||count($raw)>24)throw new RuntimeException('Invalid submitted fields.');$known=array_column($props['fields'],'id');foreach($raw as $id=>$v)if(!in_array((string)$id,$known,true)||!is_scalar($v))throw new RuntimeException('Unexpected form field.');$out=array();
    foreach($props['fields'] as $f){$v=trim((string)($raw[$f['id']] ?? ''));if(!preg_match('//u',$v)||preg_match_all('/./us',$v)>$f['maxLength'])throw new RuntimeException($f['label'].': value is too long or invalid.');
        if($f['type']==='checkbox'){if(!in_array($v,array('','1'),true))throw new RuntimeException($f['label'].': invalid checkbox value.');$v=$v==='1'?'Yes':'';}
        if($f['required']&&$v==='')throw new RuntimeException($f['label'].' is required.');
        if($v!==''){if($f['type']==='email'&&!filter_var($v,FILTER_VALIDATE_EMAIL))throw new RuntimeException($f['label'].': enter a valid email address.');if($f['type']==='url'&&(!filter_var($v,FILTER_VALIDATE_URL)||!preg_match('#^https?://#i',$v)))throw new RuntimeException($f['label'].': enter an HTTP or HTTPS URL.');if($f['type']==='select'&&!in_array($v,$f['options'],true))throw new RuntimeException($f['label'].': choose a listed option.');if($f['type']==='number'&&(!is_numeric($v)||!is_finite((float)$v)||(isset($f['min'])&&(float)$v<$f['min'])||(isset($f['max'])&&(float)$v>$f['max'])))throw new RuntimeException($f['label'].': enter a number within its limits.');}
        $out[]=array('fieldId'=>$f['id'],'label'=>$f['label'],'type'=>$f['type'],'value'=>$v);
    }return $out;
}
function ag_pf_html($node,$page,$attrs){
    $p=$node['props'];$preview=!empty($page['_builderPreview']);$id=$node['id'];$html='<section'.$attrs.'><form class="wb-public-form" data-page="'.ag_pd_h($page['_formPageId'] ?? $page['id']).'" data-form="'.$id.'"'.($preview?' data-preview="1"':'').'><h2>'.ag_pd_h($p['title']).'</h2><p>'.ag_pd_h($p['text']).'</p><div class="wb-form-fields">';
    foreach($p['fields'] as $f){$fid='field-'.$id.'-'.$f['id'];$required=$f['required']?' required':'';$common=' id="'.$fid.'" name="'.$f['id'].'"'.$required.' aria-describedby="'.$fid.'-help"';$label=ag_pd_h($f['label']).($f['required']?' <span aria-hidden="true">*</span>':'');$html.='<div class="wb-form-field"><label for="'.$fid.'">'.$label.'</label>';
        if($f['type']==='select'){$html.='<select'.$common.'><option value="">Choose an option</option>';foreach($f['options'] as $option)$html.='<option>'.ag_pd_h($option).'</option>';$html.='</select>';}
        elseif($f['type']==='textarea')$html.='<textarea'.$common.' rows="5" maxlength="'.$f['maxLength'].'" placeholder="'.ag_pd_h($f['placeholder']).'"></textarea>';
        else{$html.='<input'.$common.' type="'.$f['type'].'"'.($f['type']==='checkbox'?' value="1"':' maxlength="'.$f['maxLength'].'" placeholder="'.ag_pd_h($f['placeholder']).'"');if($f['type']==='number'){foreach(array('min','max') as $key)if(isset($f[$key]))$html.=' '.$key.'="'.$f[$key].'"';$html.=' step="any"';}$html.='>';}
        $html.='<small id="'.$fid.'-help">'.ag_pd_h($f['help']).'</small></div>';
    }
    $html.='</div><div class="wb-form-trap" aria-hidden="true"><label>Leave this empty<input name="company_website" tabindex="-1" autocomplete="off"></label></div><button class="wb-button" type="submit" disabled>'.ag_pd_h($p['buttonLabel']).'</button><p class="wb-form-status" role="status">'.($preview?'Preview only — submissions are disabled.':'Preparing secure form…').'</p><noscript>JavaScript is required to send this form.</noscript></form></section>';return $html;
}
function ag_pf_css(){return '.wb-form{padding:32px;background:var(--wb-surface);border-radius:var(--wb-radius)}.wb-public-form{display:grid;gap:18px}.wb-public-form h2{font-size:var(--block-size,30px)}.wb-form-fields{display:grid;gap:18px}.wb-form-field{display:grid;gap:7px}.wb-form-field label{font-weight:650}.wb-form-field input:not([type=checkbox]),.wb-form-field select,.wb-form-field textarea{width:100%;padding:12px;font:inherit;color:inherit;background:var(--wb-background);border:1px solid #879ba366;border-radius:8px}.wb-form-field input[type=checkbox]{width:22px;height:22px}.wb-form-field small,.wb-form-status{font-size:14px;color:var(--wb-muted)}.wb-public-form button{justify-self:start;cursor:pointer}.wb-public-form button:disabled{opacity:.6;cursor:default}.wb-form-trap{position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden}.wb-form-status[data-error]{color:#c44040}.wb-form-field :focus-visible{outline:3px solid var(--wb-accent);outline-offset:3px}';}
