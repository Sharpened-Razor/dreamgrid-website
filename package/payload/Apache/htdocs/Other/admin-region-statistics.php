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
<title>Region Statistics</title><style>body{margin:0;padding:20px;background:#080b0d;color:#e8e8e8;font:14px Arial,sans-serif}h1{color:#e4b84e;font-size:21px}button,a{background:#20231d;color:#f0c45b;border:1px solid #826627;border-radius:5px;padding:10px;cursor:pointer}button:disabled{opacity:.5}table{width:100%;border-collapse:collapse;margin-top:18px}td,th{text-align:left;border-bottom:1px solid #34372e;padding:10px}#status{margin:18px 0;color:#d1c7ac}a{display:inline-block;text-decoration:none}</style></head><body><h1>Statistics — <?=inspection_h($name)?></h1><p>UUID: <?=inspection_h($selected['RegionUUID'])?></p><button id="refresh" type="button">REFRESH</button> <label><input type="checkbox" id="automatic"> Auto-refresh every 10 seconds</label><p id="status" role="status">Loading statistics…</p><table><thead><tr><th>Statistic</th><th>Value</th></tr></thead><tbody id="metrics"></tbody></table>
<script>
(function(){'use strict';
const region=<?=json_encode($selected['RegionUUID'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
const button=document.getElementById('refresh'),automatic=document.getElementById('automatic'),status=document.getElementById('status'),rows=document.getElementById('metrics');let busy=false;
async function refresh(){if(busy)return;busy=true;button.disabled=true;status.textContent='Loading statistics…';try{
const response=await fetch('/Other/admin-region-telemetry.php?uuid='+encodeURIComponent(region),{credentials:'same-origin',cache:'no-store'});const data=await response.json();if(!response.ok||!data.ok)throw Error(data.error||'Statistics unavailable.');
const item=data.regions.find(item=>item.RegionUUID===region);if(!item||!item.Available)throw Error('Live statistics are unavailable. The simulator may be stopped or its statistics endpoint disabled.');
rows.replaceChildren();Object.entries(item).forEach(([key,value])=>{if(['RegionName','RegionUUID','Port','Available','PageFound','StatsUrl','MetricCount','DetectedLabels','Attempts'].includes(key)||value==='')return;const row=document.createElement('tr');for(const text of [key.replace(/([a-z])([A-Z])/g,'$1 $2'),String(value)]){const cell=document.createElement('td');cell.textContent=text;row.appendChild(cell);}rows.appendChild(row);});status.textContent='Updated '+new Date().toLocaleTimeString();
}catch(error){rows.replaceChildren();status.textContent=error.message||'Statistics unavailable.';}finally{busy=false;button.disabled=false;}}
button.addEventListener('click',refresh);const timer=setInterval(()=>{if(automatic.checked&&!document.hidden)refresh();},10000);window.addEventListener('pagehide',()=>clearInterval(timer),{once:true});refresh();
})();
</script></body></html>