<?php
declare(strict_types=1);
require_once __DIR__.'/map3d-cache.php';
require_once __DIR__.'/dreamgrid-env.php';

function map3d_parse_material(string $body): ?array {
    if (strlen($body)>1048576) return null;
    if (str_starts_with(ltrim($body),'<')) {
        $xml=@simplexml_load_string($body,SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
        if (!$xml || !isset($xml->map)) return null;
        $values=[]; $key=null;
        foreach($xml->map->children() as $node){
            if($node->getName()==='key'){$key=(string)$node;continue;}
            if($key!==null){$values[$key]=(string)$node;$key=null;}
        }
        if(!isset($values['DiffuseAlphaMode'])) return null;
        $mode=(int)$values['DiffuseAlphaMode'];
        if($mode<0||$mode>3)return null;
        return ['source'=>'legacy-material','alphaMode'=>['OPAQUE','BLEND','MASK','EMISSIVE'][$mode],
            'alphaCutoff'=>max(0,min(255,(int)($values['AlphaMaskCutoff']??0)))/255];
    }
    $json=json_decode($body,true);
    $material=is_array($json)?($json['materials'][0]??null):null;
    if(!is_array($material))return null;
    $mode=$material['alphaMode']??'OPAQUE';
    if(!in_array($mode,['OPAQUE','MASK','BLEND'],true))return null;
    $factor=$material['pbrMetallicRoughness']['baseColorFactor']??[1,1,1,1];
    if(!is_array($factor)||count($factor)!==4||!array_is_list($factor))return null;
    foreach($factor as &$value){if(!is_numeric($value))return null;$value=max(0,min(1,(float)$value));}unset($value);
    return ['source'=>'gltf-material','alphaMode'=>$mode,'alphaCutoff'=>max(0,min(1,(float)($material['alphaCutoff']??0.5))), 'baseColorFactor'=>$factor];
}

function map3d_material_properties(array $ids): array {
    $results=[]; $deadline=microtime(true)+8;
    foreach($ids as $uuid){
        $path=map3d_cache_path('material','alpha-v1:'.$uuid);
        if(($cached=map3d_cache_read($path))!==null){$data=json_decode($cached,true);$data['cache']='HIT';$results[]=$data;continue;}
        if(microtime(true)>=$deadline){$results[]=['ok'=>false,'MaterialUuid'=>$uuid,'error'=>'Batch deadline'];continue;}
        $url=rtrim(ag_dg_private_robust_base(),'/').'/assets/'.$uuid.'/data';
        $context=stream_context_create(['http'=>['timeout'=>min(2,max(0.1,$deadline-microtime(true))),
            'ignore_errors'=>true,'follow_location'=>0,'header'=>"Connection: close\r\n"]]);
        $http_response_header=[];
        $body=@file_get_contents($url,false,$context,0,1048577);
        $data=is_string($body)&&preg_match('/\s200\s/',$http_response_header[0]??'')?map3d_parse_material($body):null;
        $result=['ok'=>true,'MaterialUuid'=>$uuid]+($data??['source'=>'unavailable','alphaMode'=>null,'Expires'=>time()+30]);
        map3d_cache_write($path,json_encode($result,JSON_UNESCAPED_SLASHES));
        $result['cache']='MISS';$results[]=$result;
    }
    return $results;
}
