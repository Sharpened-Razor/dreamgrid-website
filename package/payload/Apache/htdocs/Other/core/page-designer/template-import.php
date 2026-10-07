<?php
function ag_ti_source($source){
    if(!is_array($source)||!is_array($source['licenses'] ?? null)||count($source['licenses'])>8||!is_array($source['notices'] ?? null)||count($source['notices'])>100)throw new RuntimeException('Invalid template licence metadata.');
    $out=array('archiveName'=>ag_pd_text($source['archiveName'] ?? '',160),'htmlFile'=>ag_pd_text($source['htmlFile'] ?? '',300),'licenses'=>array(),'notices'=>array());
    foreach($source['licenses'] as $name=>$text){if(!is_string($name)||!is_string($text)||strlen($text)>50000)throw new RuntimeException('Invalid template licence text.');$out['licenses'][ag_pd_text($name,300)]=$text;}
    foreach($source['notices'] as $notice){if(!is_string($notice)||strlen($notice)>2000)throw new RuntimeException('Invalid template import notice.');$out['notices'][]=$notice;}return $out;
}
function ag_ti_preview($token,$selected){$meta=ag_ti_read($token);$result=ag_ti_convert(ag_ti_stage_file($token,'zip'),$selected);unset($result['package']);$result['archive']=$meta['name'];return $result;}
function ag_ti_commit($token,$selected,$reviewed){if(!$reviewed)throw new RuntimeException('Review the conversion and template licence before importing.');$meta=ag_ti_read($token);$result=ag_ti_convert(ag_ti_stage_file($token,'zip'),$selected);$p=ag_pd_import_package(json_encode($result['package'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));$dir=ag_pd_root().'/template-sources';$archive=$dir.'/'.$p['id'].'.zip';$file=$dir.'/'.$p['id'].'.json';
    try{if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Template source storage is unavailable.');if(!copy(ag_ti_stage_file($token,'zip'),$archive)||!hash_equals($meta['sha256'],hash_file('sha256',$archive)))throw new RuntimeException('The original template could not be retained.');ag_pd_write_document($file,array('pageId'=>$p['id'],'imported'=>time(),'archiveName'=>$meta['name'],'archiveSha256'=>$meta['sha256'],'htmlFile'=>$selected,'licenses'=>$result['licenses'],'notices'=>$result['notices']));}
    catch(Throwable $e){@unlink($archive);@unlink($file);ag_pd_lock(function()use($p){ag_pd_delete_page($p);});throw $e;}ag_ti_discard($token);return $p;
}
