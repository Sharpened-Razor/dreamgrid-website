<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/region-inspection.php';
ag_no_cache(); ag_require_admin();
try { $selected=ri_select(ri_regions(),$_GET['region']??'',$_GET['uuid']??''); }
catch (InvalidArgumentException $e) { http_response_code(400); exit('Invalid region selection.'); }
catch (Throwable $e) { http_response_code(404); exit('Region not found.'); }
$name=$selected['RegionName'];
function inspection_h(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Region Console</title><style>body{margin:0;padding:20px;background:#080b0d;color:#e8e8e8;font:14px Arial,sans-serif}h1{color:#e4b84e;font-size:21px}button,a{background:#20231d;color:#f0c45b;border:1px solid #826627;border-radius:5px;padding:10px;cursor:pointer}button:disabled{opacity:.5}table{width:100%;border-collapse:collapse;margin-top:18px}td,th{text-align:left;border-bottom:1px solid #34372e;padding:10px}#status{margin:18px 0;color:#d1c7ac}a{display:inline-block;text-decoration:none}</style></head><body><h1>Console — <?=inspection_h($name)?></h1><p>Open the existing native console for this region on the DreamGrid computer.</p><button id="openConsole" type="button">OPEN NATIVE CONSOLE</button><p id="status" role="status">Ready. No console request has been sent.</p><script>
(function(){'use strict';const name=<?=json_encode($name,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;const button=document.getElementById('openConsole'),status=document.getElementById('status');let pending=false;
button.addEventListener('click',async()=>{if(pending)return;pending=true;button.disabled=true;status.textContent='Requesting native console…';try{const response=await fetch('/Other/admin-region-console-action.php',{method:'POST',credentials:'same-origin',body:new URLSearchParams({region:name})});const data=await response.json();if(!response.ok||!data.ok)throw Error(data.error||'Console request failed.');status.textContent=data.message;}catch(error){status.textContent=error.message||'Console unavailable.';}finally{pending=false;button.disabled=false;}});
})();</script></body></html>