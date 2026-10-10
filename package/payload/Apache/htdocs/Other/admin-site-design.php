<?php
require_once __DIR__.'/core/bootstrap.php';
require_once __DIR__.'/core/site-appearance.php';
require_once __DIR__.'/core/site-design-assets.php';
ag_no_cache();ag_require_admin();if(session_status()!==PHP_SESSION_ACTIVE)session_start();
if(empty($_SESSION['site_appearance_csrf']))$_SESSION['site_appearance_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['site_appearance_csrf'];$error='';$success=$_SESSION['site_appearance_flash'] ?? '';unset($_SESSION['site_appearance_flash']);
$tab=is_string($_GET['tab'] ?? null)?$_GET['tab']:'themes';$stage=null;$newAssets=array();
try {
    if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
        ag_require_same_origin_post();if(!is_string($_POST['csrf_token'] ?? null)||!hash_equals($csrf,$_POST['csrf_token']))throw new RuntimeException('Your session changed. Reload this page and try again.');
        $action=is_string($_POST['action'] ?? null)?$_POST['action']:'';$revision=$_POST['revision'] ?? '';
        if(in_array($action,array('theme_save','theme_update','theme_save_apply'),true)) {
            $styles=$_POST['styles'] ?? array();if(!is_array($styles))throw new RuntimeException('Invalid styles.');
            if(($_FILES['themeBackground']['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$styles['background']=ag_sd_upload_image($_FILES['themeBackground']);$newAssets[]=$styles['background'];}
            $raw=array('name'=>$_POST['name'] ?? '', 'font'=>$_POST['font'] ?? '', 'colors'=>$_POST['colors'] ?? array(),'styles'=>$styles);
            if($action==='theme_update')ag_sd_update_theme($_POST['themeId'] ?? '',$raw,$revision);
            elseif($action==='theme_save_apply')ag_sd_save_apply($raw,$_POST['scopes'] ?? array(),$revision);
            else ag_sa_save_theme($raw,$revision);
            $success=$action==='theme_save_apply'?'Theme applied. Refresh an open dashboard to update its outer shell.':'Theme saved. Active appearance stays unchanged until Apply.';
        }
        elseif($action==='theme_apply'){ag_sa_apply($_POST['theme'] ?? '',$_POST['scopes'] ?? $_POST['scope'] ?? array(),$revision);$success='Theme applied. The previous setup was backed up.';}
        elseif($action==='theme_duplicate'){ag_sd_duplicate_theme($_POST['theme'] ?? '',$_POST['name'] ?? '',$revision);$success='Theme duplicated into your library.';}
        elseif($action==='theme_delete'){ag_sa_delete_theme($_POST['theme'] ?? '',$revision);$success='Saved theme removed. Restore the previous setup to bring it back.';}
        elseif($action==='theme_import'){$file=$_FILES['themeFile'] ?? array();if(($file['error'] ?? -1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name'] ?? '')||($file['size'] ?? 0)>16384)throw new RuntimeException('Upload a theme JSON file smaller than 16 KB.');ag_sa_import(file_get_contents($file['tmp_name']),$revision);$success='Theme imported into the library. Preview it before applying.';}
        elseif($action==='theme_export'){$state=ag_sa_read();if(!hash_equals(ag_sa_revision($state),(string)$revision))throw new RuntimeException('Design changed. Reload before exporting.');$theme=ag_sa_library($state)[$_POST['theme'] ?? ''] ?? null;if(!$theme||empty($theme['colors']))throw new RuntimeException('Choose a saved or built-in colour theme to export.');$theme=ag_sa_theme($theme);$theme['styles']['background']='';header('Content-Type: application/json');header('Content-Disposition: attachment; filename="website-theme.json"');echo json_encode(array('format'=>'dreamgrid-appearance-theme','version'=>1,'theme'=>$theme),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);exit;}
        elseif($action==='front_apply'){ag_sa_front_page($_POST['pageId'] ?? '',$revision);$success='Front login page activated. The previous setup was backed up.';$tab='front';}
        elseif($action==='restore'){ag_sa_restore($revision);$success='Previous appearance setup restored. You can restore again to undo this.';}
        elseif($action==='starter_create'){ag_sd_create_starter($_POST['starter'] ?? '',$_POST['name'] ?? '');$success='Starter duplicated as a private draft. Edit it in Page Designer before publishing.';$tab='front';}
        elseif($action==='branding_save'){$raw=$_POST['branding'] ?? array();foreach($_POST['removeBranding'] ?? array() as $key=>$yes)if(isset(ag_sd_brand_defaults()[$key]))$raw[$key]='';ag_sd_brand_save($raw,$_FILES,$revision);$success='Branding applied. Previous branding and uploaded images are retained for restore.';$tab='branding';}
        elseif($action==='package_export'){$bytes=ag_sd_package_export($_POST['name'] ?? '',$_POST['pageId'] ?? '',$_POST['scopes'] ?? array(),$revision,$_POST['designId'] ?? '');header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="website-design.zip"');echo $bytes;exit;}
        elseif($action==='package_import'){$file=$_FILES['designPackage'] ?? array();if(($file['error'] ?? -1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name'] ?? '')||($file['size'] ?? 0)>33554432)throw new RuntimeException('Choose a design ZIP no larger than 32 MB.');ag_sd_package_import($file['tmp_name']);$success='Design imported as a private draft. Preview, review and choose scopes before Apply.';$tab='packages';}
        elseif($action==='package_apply'){ag_sd_apply_package($_POST['designId'] ?? '',$_POST['scopes'] ?? array(),($_POST['applyBranding'] ?? '')==='1',$revision,$_POST['pageRevision'] ?? '',($_POST['reviewed'] ?? '')==='1');$success='Reviewed design applied to the selected scopes. Unchecked scopes were preserved.';$tab='packages';}
        elseif($action==='artwork_apply'){ag_sd_apply_artwork($_POST['artwork'] ?? '',$_POST['upload'] ?? '',$_POST['target'] ?? '',$revision);$success='Artwork applied. The previous design was backed up.';$tab='artwork';}
        elseif($action==='artwork_upload'){$asset=ag_sd_upload_image($_FILES['artworkUpload'] ?? array());$newAssets[]=$asset;ag_sa_change($revision,function($s){return $s;},array('label'=>'Add custom artwork'));$success='Custom artwork added to My uploads. Apply it when ready.';$tab='artwork';}
        elseif($action==='history_restore'){ag_sd_history_restore($_POST['historyId'] ?? '',$revision);$success='Selected design restored and a new history entry created.';$tab='history';}
        elseif($action==='history_limit'){ag_sd_history_limit($_POST['limit'] ?? '',$revision);$success='History retention updated.';$tab='history';}
        elseif($action==='schedule_save'){ag_sd_schedule($_POST['schedule'] ?? array(),$revision);$success='Schedule saved as a disabled draft. Automatic activation is not enabled.';$tab='schedule';}
        elseif($action==='schedule_cancel'){ag_sd_cancel_schedule($_POST['scheduleId'] ?? '',$revision);$success='Schedule cancelled.';$tab='schedule';}
        elseif($action==='template_upload'){$stage=ag_ti_upload($_FILES['templateZip'] ?? null);$tab='front';}
        elseif($action==='template_import'){$page=ag_ti_commit($_POST['token'] ?? '',$_POST['htmlFile'] ?? '',($_POST['reviewed'] ?? '')==='1');ag_sa_add_login($page['id']);$success='Imported as a private draft with Member Login. Review and publish in Page Designer.';$tab='front';}
        elseif($action==='add_login'){ag_sa_add_login($_POST['pageId'] ?? '');$success='Member Login added to a private draft.';$tab='front';}
        else throw new RuntimeException('Choose a supported design action.');
        if($success!==''){$_SESSION['site_appearance_flash']=$success;header('Location: /Other/admin-site-design.php?tab='.rawurlencode($tab),true,303);exit;}
    }
}catch(Throwable $e){foreach($newAssets as $name)@unlink(ag_sd_asset_path($name));$error=$e->getMessage();}
$state=ag_sa_read();$revision=ag_sa_revision($state);$library=ag_sa_library($state);$pages=ag_pd_editor_pages();
$tabs=array('themes'=>'Themes','starters'=>'Starter designs','front'=>'Front login page','branding'=>'Branding','packages'=>'Design packages','history'=>'History','schedule'=>'Scheduling','artwork'=>'Artwork');
$activeTab=isset($tabs[$tab])?$tab:'themes';require __DIR__.'/site-design/panel.php';
