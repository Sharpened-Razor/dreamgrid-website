<?php
function ag_sd_history_id($id) { return is_string($id)&&preg_match('/^\d{14}-[a-f0-9]{12}$/D',$id)?$id:null; }
function ag_sd_snapshot_page($state) { return $state['frontPage']!==''?ag_pd_load_by_id($state['frontPage']):null; }
function ag_sd_record_history($before,$after,$meta,$beforePage) {
    $id=gmdate('YmdHis').'-'.bin2hex(random_bytes(6));$scopes=array();
    foreach(ag_sd_scopes() as $scope=>$label)if($before['scopes'][$scope]!==$after['scopes'][$scope])$scopes[]=$scope;
    if($before['frontPage']!==$after['frontPage'])$scopes[]='front page';
    $entry=array('version'=>2,'id'=>$id,'timestamp'=>microtime(true),'label'=>ag_pd_text($meta['label'] ?? 'Design updated',160),'scopes'=>$meta['scopes'] ?? $scopes,'before'=>$before,'after'=>$after,'beforePage'=>$beforePage,'afterPage'=>ag_sd_snapshot_page($after));
    ag_pd_write_document(dirname(ag_sa_file()).'/history/'.$id.'.json',$entry);
    return $id;
}
function ag_sd_history_list() {
    $entries=array();foreach(glob(dirname(ag_sa_file()).'/history/*.json') ?: array() as $file){
        $id=basename($file,'.json');if(!ag_sd_history_id($id))continue;
        try{$e=ag_pd_read_document($file);if(($e['version'] ?? null)!==2)$e=array('version'=>2,'id'=>$id,'timestamp'=>filemtime($file),'label'=>'Previous appearance setup','scopes'=>array(),'before'=>$e,'after'=>$e,'beforePage'=>null,'afterPage'=>null);
            $e['after']=ag_sa_validate_state($e['after']);$entries[]=$e;
        }catch(Throwable $error){error_log('Design history entry could not be read.');}
    }usort($entries,function($a,$b){return $b['timestamp']<=>$a['timestamp'];});return $entries;
}
function ag_sd_history_get($id) { if(!ag_sd_history_id($id))throw new RuntimeException('Invalid history identifier.');foreach(ag_sd_history_list() as $e)if($e['id']===$id)return $e;throw new RuntimeException('Design history entry not found.'); }
function ag_sd_prune_history($limit) { foreach(array_slice(ag_sd_history_list(),$limit) as $entry)@unlink(dirname(ag_sa_file()).'/history/'.$entry['id'].'.json'); }
function ag_sd_history_restore($id,$revision) {
    $entry=ag_sd_history_get($id);$target=ag_sa_validate_state($entry['after']);$snapshot=$entry['afterPage'] ?? null;
    $oldPage=null;$oldDraft=null;$changed=false;
    $rollback=function()use(&$oldPage,&$oldDraft,$snapshot,&$changed){if(!$changed||!$snapshot)return;$live=ag_pd_page_file($snapshot['id']);if($oldPage)ag_pd_write_document($live,$oldPage);elseif(is_file($live))unlink($live);$draft=ag_pd_draft_file($snapshot['id']);if($oldDraft)ag_pd_write_document($draft,$oldDraft);elseif(is_file($draft))unlink($draft);};
    return ag_sa_change($revision,function($s)use($target,$snapshot,&$oldPage,&$oldDraft,&$changed){
        if($snapshot){$snapshot=ag_pd_normalize($snapshot);if(($snapshot['access'] ?? '')!=='public'||ag_sa_page_login_count($snapshot)!==1)throw new RuntimeException('The saved login design needs review before restoration.');
            foreach(ag_pd_page_images($snapshot) as $name)if(!ag_pd_asset_path($snapshot['id'],$name))throw new RuntimeException('An image needed by the saved design is missing.');
            $oldPage=ag_pd_load_by_id($snapshot['id']);$oldDraft=ag_pd_load_draft($snapshot['id']);$changed=true;ag_pd_store_edition($snapshot,'publish',$oldPage);
        }
        if($target['frontPage']!=='')ag_sa_front_candidate($target['frontPage']);return $target;
    },array('label'=>'Restore '.$entry['label'],'rollback'=>$rollback));
}
function ag_sd_history_limit($limit,$revision) {
    if(!is_numeric($limit)||(int)$limit<10||(int)$limit>200)throw new RuntimeException('Keep between 10 and 200 history entries.');
    return ag_sa_change($revision,function($s)use($limit){$s['historyLimit']=(int)$limit;return $s;},array('label'=>'Change history retention'));
}
function ag_sd_thumbnail($theme,$title) { return ag_sd_visual_card($theme,$title); }
