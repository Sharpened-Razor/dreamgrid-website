const result = document.getElementById('result');
const RESULT_KEY = 'australiaGridLastResultV32';

const completedOarRegions = new Set(
  JSON.parse(sessionStorage.getItem('australiaCompletedOarRegionsV35') || '[]')
);

function rememberOarComplete(region){
  completedOarRegions.add(String(region));
  sessionStorage.setItem(
    'australiaCompletedOarRegionsV35',
    JSON.stringify(Array.from(completedOarRegions))
  );
}

function clearOarComplete(region){
  completedOarRegions.delete(String(region));
  sessionStorage.setItem(
    'australiaCompletedOarRegionsV35',
    JSON.stringify(Array.from(completedOarRegions))
  );
}

function effectiveRegionStatus(region){
  const raw = String(region.Status || '').trim();
  if(raw.toLowerCase() === 'backingup' && completedOarRegions.has(String(region.RegionName))){
    return 'Booted';
  }
  return raw;
}

let iarMonitorId = null;

function msg(t, bad=false){
  result.textContent = t;
  result.classList.toggle('bad', bad);
  sessionStorage.setItem(RESULT_KEY, JSON.stringify({text:t,bad:bad}));
}

function setResult(text, state='ok'){
  msg(text, state === 'error');
}

function restoreResult(){
  try{
    const saved = JSON.parse(sessionStorage.getItem(RESULT_KEY) || 'null');
    if(saved && saved.text){
      result.textContent = saved.text;
      result.classList.toggle('bad', !!saved.bad);
      return true;
    }
  }catch(e){}
  result.textContent = 'No command run yet.';
  result.classList.remove('bad');
  return false;
}

function fmtBytes(n){
  n = Number(n || 0);
  if(n < 1024) return n + ' B';
  const units = ['KB','MB','GB','TB'];
  let i = -1;
  do { n /= 1024; i++; } while(n >= 1024 && i < units.length-1);
  return n.toFixed(n >= 100 ? 0 : n >= 10 ? 1 : 2) + ' ' + units[i];
}

async function refreshDreamGridBackupStatus(region){
  try{
    const u = new URL('/Other/refresh-backup-status.php', window.location.origin);
    u.searchParams.set('region', region);

    await fetch(u, {
      credentials:'same-origin',
      cache:'no-store'
    });
  }catch(e){
    // Non-fatal. The archive itself is already complete.
  }
}

function iarAvatar(job){
  return String((job && job.avatar) || DG.avatar || '');
}

function setIarPanelStatus(text, state='processing'){
  const el = document.getElementById('iar-panel-status');
  if(!el) return;
  el.hidden = false;
  el.className = 'iar-panel-status iar-' + state;
  el.textContent = text;
}

function setIarStatusTarget(target, text, state='processing'){
  if(target === 'admin'){
    const el = document.getElementById('admin-iar-status');
    if(!el) return;
    el.hidden = false;
    el.className = 'iar-panel-status iar-' + state;
    el.textContent = text;
    return;
  }

  setIarPanelStatus(text, state);
}

function backupDate(ts){
  if(!ts) return '';
  try{
    return new Date(Number(ts) * 1000).toLocaleString('en-AU');
  }catch(e){ return ''; }
}

let backupRows = [];
const selectedBackups = new Map();
let restoreRegionNames = [];
let restoreAvatarNames = [];
let restoreRegionSizes = {};
let restoreBusy = false;

let gridBackupRegions = [];
const gridBackupSelected = new Set();
let gridBackupRunning = false;

let scheduleConfig = null;
const scheduleSelectedRegions = new Set();



function oarSizeFromName(name){
  const m = String(name || '').match(/\((\d+)X(\d+)\)\.oar$/i);
  return m ? `${m[1]}X${m[2]}`.toUpperCase() : '';
}

function setRestoreBusy(flag){
  restoreBusy = !!flag;
  document.querySelectorAll('.backup-restore').forEach(b => {
    b.disabled = restoreBusy;
  });
}

function restoreTime(v){
  if(!v) return '';
  try{
    const d=new Date(v);
    return isNaN(d.getTime()) ? String(v) : d.toLocaleString('en-AU');
  }catch(e){ return String(v); }
}

function storageSize(bytes){
  const n=Number(bytes);
  if(!Number.isFinite(n) || n<0) return '—';
  const units=['B','KB','MB','GB','TB'];
  let v=n, i=0;
  while(v>=1024 && i<units.length-1){ v/=1024; i++; }
  const dp=i>=3 ? 2 : (i===2 ? 1 : 0);
  return `${v.toFixed(dp)} ${units[i]}`;
}

async function loadBackupStorage(){
  if(Number(DG.level)<200) return;
  const box=document.getElementById('backup-storage');
  if(!box) return;
  try{
    const r=await fetch('/Other/backup-storage.php',{
      credentials:'same-origin',cache:'no-store'
    });
    const raw=await r.text();
    let d;
    try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid storage response'); }
    if(!d.ok) throw new Error(d.error || 'Unable to calculate backup storage');

    const s=d.summary || {};
    const free=Number(s.driveFreeBytes);
    const total=Number(s.driveTotalBytes);
    const used=(Number.isFinite(total)&&Number.isFinite(free)) ? total-free : NaN;
    const freePct=(Number.isFinite(total)&&total>0&&Number.isFinite(free)) ? Math.max(0,Math.min(100,(free/total)*100)) : NaN;

    const oars=(d.oarGroups || []).map(g=>`
      <div class="storage-group-row"><span>${esc(g.name)}</span><span>${Number(g.count)||0} backup${Number(g.count)===1?'':'s'} · ${storageSize(g.bytes)}</span></div>
    `).join('') || '<div class="backup-empty">No OAR backups found.</div>';

    const iars=(d.iarGroups || []).map(g=>`
      <div class="storage-group-row"><span>${esc(g.name)}</span><span>${Number(g.count)||0} backup${Number(g.count)===1?'':'s'} · ${storageSize(g.bytes)}</span></div>
    `).join('') || '<div class="backup-empty">No IAR backups found.</div>';

    box.innerHTML=`
      <div class="storage-cards">
        <div class="storage-card"><span>ALL BACKUPS</span><strong>${storageSize(s.backupBytes)}</strong><small>${(Number(s.oarCount)||0)+(Number(s.iarCount)||0)} files</small></div>
        <div class="storage-card"><span>OAR BACKUPS</span><strong>${storageSize(s.oarBytes)}</strong><small>${Number(s.oarCount)||0} files</small></div>
        <div class="storage-card"><span>IAR BACKUPS</span><strong>${storageSize(s.iarBytes)}</strong><small>${Number(s.iarCount)||0} files</small></div>
        <div class="storage-card"><span>DRIVE FREE</span><strong>${storageSize(s.driveFreeBytes)}</strong><small>${storageSize(s.driveTotalBytes)} total</small></div>
      </div>
      ${Number.isFinite(freePct) ? `<div class="storage-meter"><div class="storage-meter-label"><span>Drive used ${storageSize(used)}</span><span>${freePct.toFixed(1)}% free</span></div><div class="storage-meter-track"><div class="storage-meter-free" style="width:${freePct.toFixed(2)}%"></div></div></div>` : ''}
      <div class="storage-columns">
        <div><h3>OAR STORAGE BY REGION</h3>${oars}</div>
        <div><h3>IAR STORAGE BY USER</h3>${iars}</div>
      </div>
    `;
  }catch(e){
    box.innerHTML=`<div class="backup-empty backup-error">BACKUP STORAGE ERROR — ${esc(e.message)}</div>`;
  }
}

async function loadRestoreHistory(){
  if(Number(DG.level) < 200) return;
  const box=document.getElementById('restore-history');
  const activeBox=document.getElementById('restore-active');
  if(!box) return;

  try{
    const r=await fetch('/Other/restore-history.php',{
      credentials:'same-origin',cache:'no-store'
    });
    const raw=await r.text();
    let d;
    try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid restore history response'); }
    if(!d.ok) throw new Error(d.error || 'Unable to load restore history');

    restoreBusy=!!d.active;
    if(activeBox){
      if(d.active){
        activeBox.hidden=false;
        activeBox.textContent=`ACTIVE RESTORE — ${String(d.active.type || '').toUpperCase()} — ${d.active.target || ''} — ${d.active.source || ''}`;
      }else{
        activeBox.hidden=true;
        activeBox.textContent='';
      }
    }

    const rows=Array.isArray(d.history) ? d.history : [];
    if(!rows.length){
      box.innerHTML='<div class="backup-empty">No restore history yet.</div>';
    }else{
      box.innerHTML=rows.map(h=>`
        <div class="restore-history-row">
          <div class="restore-history-main">
            <div class="backup-file-title">
              <span class="backup-type backup-${String(h.type).toLowerCase()}">${esc(h.type)}</span>
              <strong>${esc(h.source)}</strong>
            </div>
            <div class="restore-history-meta">
              Destination: ${esc(h.target)} · By: ${esc(h.requestedBy || 'Grid Owner')} · ${esc(restoreTime(h.created))}
              ${h.sourceSize && h.destinationSize ? ` · ${esc(h.sourceSize)} → ${esc(h.destinationSize)}` : ''}
            </div>
            ${h.detail ? `<div class="restore-history-detail">${esc(h.detail)}</div>` : ''}
          </div>
          <span class="restore-history-status restore-${String(h.status).toLowerCase()}">${esc(h.status)}</span>
        </div>
      `).join('');
    }

    renderBackups();
  }catch(e){
    box.innerHTML=`<div class="backup-empty backup-error">RESTORE HISTORY ERROR — ${esc(e.message)}</div>`;
  }
}

function backupSelectionKey(b){
  if(!b || !b.delete) return '';
  const d=b.delete;
  if(String(d.type).toLowerCase()==='iar') return `iar:${d.job || ''}`;
  if(String(d.type).toLowerCase()==='oar') return `oar:${d.file || ''}`;
  return '';
}

function updateCleanupSummary(){
  const count=document.getElementById('backup-selected-count');
  const size=document.getElementById('backup-selected-size');
  const del=document.getElementById('backup-delete-selected');
  let bytes=0;
  selectedBackups.forEach(b=>bytes += Number(b.size || 0));
  if(count) count.textContent=`${selectedBackups.size} selected`;
  if(size) size.textContent=storageSize(bytes);
  if(del) del.disabled=selectedBackups.size===0 || restoreBusy;
}

function retentionGroupKey(b){
  if(!b || !b.delete) return '';
  const type=String(b.type || '').toUpperCase();
  if(type==='IAR') return `IAR:${String(b.owner || '').trim().toLowerCase()}`;
  if(type==='OAR'){
    const name=String(b.name || '');
    // DreamGrid OAR names end with _YYYY-MM-DD_HH_MM_SS(size).oar.
    // Everything before that timestamp is the region name.
    const m=name.match(/^(.*?)_\d{4}-\d{2}-\d{2}_\d{2}_\d{2}_\d{2}(?:\([^)]*\))?\.oar$/i);
    const region=(m ? m[1] : name.replace(/\.oar$/i,''));
    return `OAR:${region.trim().toLowerCase()}`;
  }
  return '';
}

function selectOldBackups(){
  const visible=currentVisibleBackups();
  const groups=new Map();
  visible.forEach(b=>{
    const g=retentionGroupKey(b);
    if(!g) return;
    if(!groups.has(g)) groups.set(g,[]);
    groups.get(g).push(b);
  });

  selectedBackups.clear();
  let protectedCount=0;
  let selectedCount=0;
  groups.forEach(items=>{
    items.sort((a,b)=>Number(b.modified||0)-Number(a.modified||0));
    if(items.length) protectedCount++;
    items.slice(1).forEach(b=>{
      const key=backupSelectionKey(b);
      if(key){ selectedBackups.set(key,b); selectedCount++; }
    });
  });
  renderBackups();
  if(selectedCount===0){
    alert('No older backups found in the current view.\n\nThe newest backup for each region/user is protected.');
  }else{
    const total=Array.from(selectedBackups.values()).reduce((n,b)=>n+Number(b.size||0),0);
    alert(`RETENTION HELPER\n\nSelected ${selectedCount} older backup${selectedCount===1?'':'s'} (${storageSize(total)}).\nProtected the newest backup in each of ${protectedCount} region/user group${protectedCount===1?'':'s'}.\n\nNothing has been deleted. Review the highlighted files, then use DELETE SELECTED only if you want to remove them.`);
  }
}

function currentVisibleBackups(){
  const q=(document.getElementById('backup-search')?.value || '').trim().toLowerCase();
  const filter=document.getElementById('backup-filter')?.value || 'all';
  return backupRows.filter(b=>{
    if(!b.delete) return false;
    if(filter!=='all' && String(b.type).toUpperCase()!==filter) return false;
    if(q){ const hay=`${b.type || ''} ${b.owner || ''} ${b.name || ''}`.toLowerCase(); if(!hay.includes(q)) return false; }
    return true;
  });
}

function renderBackups(){
  const box = document.getElementById('backup-files');
  if(!box) return;

  const q = (document.getElementById('backup-search')?.value || '').trim().toLowerCase();
  const filter = document.getElementById('backup-filter')?.value || 'all';
  const sort = document.getElementById('backup-sort')?.value || 'newest';

  let rows = backupRows.filter(b => {
    if(filter !== 'all' && String(b.type).toUpperCase() !== filter) return false;
    if(q){
      const hay = `${b.type || ''} ${b.owner || ''} ${b.name || ''}`.toLowerCase();
      if(!hay.includes(q)) return false;
    }
    return true;
  });

  rows.sort((a,b) => {
    if(sort === 'oldest') return Number(a.modified||0) - Number(b.modified||0);
    if(sort === 'largest') return Number(b.size||0) - Number(a.size||0);
    if(sort === 'smallest') return Number(a.size||0) - Number(b.size||0);
    return Number(b.modified||0) - Number(a.modified||0);
  });

  if(!rows.length){
    box.innerHTML = '<div class="backup-empty">No backups match this view.</div>';
    return;
  }

  box.innerHTML = rows.map((b,idx) => `
    <div class="backup-file-row ${selectedBackups.has(backupSelectionKey(b)) ? 'is-selected' : ''}">
      ${b.delete ? `<label class="backup-select-wrap" title="Select ${attr(b.name)}"><input type="checkbox" class="backup-select" data-backup-index="${idx}" ${selectedBackups.has(backupSelectionKey(b)) ? 'checked' : ''} aria-label="Select ${attr(b.name)}"></label>` : ''}
      <div class="backup-file-main">
        <div class="backup-file-title">
          <span class="backup-type backup-${String(b.type).toLowerCase()}">${esc(b.type)}</span>
          <strong>${esc(b.name)}</strong>
        </div>
        <div class="backup-file-meta">
          ${b.owner ? `${esc(b.owner)} · ` : ''}${fmtBytes(Number(b.size || 0))} · ${esc(backupDate(b.modified))}
        </div>
      </div>
      <div class="backup-file-actions">
        <a class="backup-download" href="${attr(b.download)}">DOWNLOAD</a>
        ${b.restore ? `<button type="button" class="backup-restore" data-backup-index="${idx}" data-restore-type="${attr(String(b.restore.type || b.type || '').toLowerCase())}"${restoreBusy ? ' disabled' : ''}>RESTORE</button>` : ''}
        ${b.delete ? `<button type="button" class="backup-delete" data-backup-index="${idx}">DELETE</button>` : ''}
      </div>
    </div>
  `).join('');

  box.querySelectorAll('.backup-select').forEach(cb => {
    cb.addEventListener('change', () => {
      const b=rows[Number(cb.dataset.backupIndex)];
      const key=backupSelectionKey(b);
      if(!key) return;
      if(cb.checked) selectedBackups.set(key,b); else selectedBackups.delete(key);
      cb.closest('.backup-file-row')?.classList.toggle('is-selected',cb.checked);
      updateCleanupSummary();
    });
  });
  updateCleanupSummary();

  box.querySelectorAll('.backup-restore').forEach(btn => {
    btn.addEventListener('click', async () => {
      const b = rows[Number(btn.dataset.backupIndex)];
      if(!b || !b.restore || restoreBusy) return;
      const restoreType = String(b.restore.type || b.type || '').toLowerCase();

      if(restoreType === 'iar'){
        if(!restoreAvatarNames.length){
          alert('Local avatar list is not loaded yet. Refresh the page and try again.');
          return;
        }

        const avatar = await uiChoice(
          'RESTORE IAR — SELECT AVATAR',
          `<div class="dg-modal-copy"><p><strong>Backup:</strong> ${esc(b.name)}</p><p>Select the destination local avatar.</p></div>`,
          restoreAvatarNames.map(a=>({value:a,title:a,meta:'Local avatar'})),
          'SELECT AVATAR'
        );
        if(!avatar) return;

        if(!(await uiConfirm(
          'FINAL IAR RESTORE CONFIRMATION',
          `<div class="dg-modal-warning"><strong>${esc(b.name)}</strong><span>Destination avatar: <b>${esc(avatar)}</b></span>` +
          `<span>This loads the archive into the avatar's inventory root (/).</span><span>V51 does not use --merge.</span></div>`,
          'RESTORE IAR'
        ))) return;

        setRestoreBusy(true);
        btn.textContent = 'STARTING…';
        try{
          const body = new URLSearchParams();
          body.set('job',String(b.restore.job || ''));
          body.set('avatar',avatar);
          const r = await fetch('/Other/restore-iar.php',{
            method:'POST',credentials:'same-origin',cache:'no-store',
            headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body
          });
          const raw=await r.text();
          let d;
          try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid IAR restore response'); }
          if(!d.ok) throw new Error(d.error || 'IAR restore failed to prepare');

          btn.textContent='RESTORING…';
          setResult(`IAR RESTORE PREPARED\n${d.restore.name}\nDestination: ${d.restore.avatar}`,'ok');
          monitorIarRestore(d.restore);
          loadRestoreHistory();
        }catch(e){
          setResult(`IAR RESTORE ERROR\n${e.message}`,'error');
          restoreBusy=false;
          renderBackups();
          loadRestoreHistory();
        }
        return;
      }

      if(!restoreRegionNames.length){
        alert('Region list is not loaded yet. Click REFRESH REGIONS and try again.');
        return;
      }

      const region = await uiChoice(
        'RESTORE OAR — SELECT REGION',
        `<div class="dg-modal-copy"><p><strong>Backup:</strong> ${esc(b.name)}</p><p>Select the destination live region.</p></div>`,
        restoreRegionNames.map(r=>({value:r,title:r,meta:String(restoreRegionSizes[r]||'') || 'Region'})),
        'SELECT REGION'
      );
      if(!region) return;

      const sourceSize=oarSizeFromName(b.name);
      const destinationSize=String(restoreRegionSizes[region] || '').toUpperCase();
      const mismatch=!!(sourceSize && destinationSize && sourceSize !== destinationSize);
      const mismatchHtml=mismatch
        ? `<div class="dg-modal-size-warning"><strong>SIZE MISMATCH</strong><span>OAR size: ${esc(sourceSize)}</span><span>Destination size: ${esc(destinationSize)}</span><span>This can crop or misplace restored content.</span></div>`
        : '';

      if(!(await uiConfirm(
        'FINAL OAR RESTORE CONFIRMATION',
        `<div class="dg-modal-warning"><strong>${esc(b.name)}</strong><span>Destination region: <b>${esc(region)}</b></span>` +
        mismatchHtml +
        `<span>This loads the OAR into the selected live region.</span><span>Terrain, parcels and scene contents may be replaced.</span></div>`,
        'RESTORE OAR'
      ))) return;

      setRestoreBusy(true);
      btn.textContent = 'STARTING…';
      try{
        const body = new URLSearchParams();
        body.set('region',region);
        body.set('file',String(b.restore.file || ''));
        if(mismatch) body.set('confirm_size_mismatch','1');
        const r = await fetch('/Other/restore-oar.php',{
          method:'POST',credentials:'same-origin',cache:'no-store',
          headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body
        });
        const raw=await r.text();
        let d;
        try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid restore response'); }
        if(!d.ok){
          if(d.sizeMismatch){
            throw new Error(`Size mismatch: OAR ${d.sourceSize} / destination ${d.destinationSize}`);
          }
          throw new Error(d.error || 'Restore failed to prepare');
        }

        btn.textContent = 'RESTORING…';
        setResult(`OAR RESTORE PREPARED\n${d.restore.name}\nDestination: ${d.restore.region}`, 'ok');
        monitorOarRestore(d.restore);
        loadRestoreHistory();
      }catch(e){
        setResult(`OAR RESTORE ERROR\n${e.message}`,'error');
        restoreBusy=false;
        renderBackups();
        loadRestoreHistory();
      }
    });
  });

  box.querySelectorAll('.backup-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
      const b = rows[Number(btn.dataset.backupIndex)];
      if(!b || !b.delete) return;
      if(!(await uiConfirm(
        'DELETE BACKUP',
        `<div class="dg-modal-warning"><strong>${esc(b.name)}</strong><span>This permanently removes the backup file from the server.</span><span>This cannot be undone.</span></div>`,
        'DELETE BACKUP',
        true
      ))) return;

      btn.disabled = true;
      btn.textContent = 'DELETING…';
      try{
        const body = new URLSearchParams();
        Object.entries(b.delete).forEach(([k,v]) => body.set(k,String(v)));
        const r = await fetch('/Other/delete-backup.php', {
          method:'POST', credentials:'same-origin', cache:'no-store',
          headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
          body
        });
        const raw = await r.text();
        let d;
        try { d = JSON.parse(raw); } catch(e){ throw new Error(raw || 'Invalid delete response'); }
        if(!d.ok) throw new Error(d.error || 'Delete failed');
        selectedBackups.delete(backupSelectionKey(b));
        setResult(`BACKUP DELETED — ${d.deleted}`, 'ok');
        await loadBackups();
        await loadBackupStorage();
      }catch(e){
        setResult(`BACKUP DELETE ERROR\n${e.message}`, 'error');
        btn.disabled = false;
        btn.textContent = 'DELETE';
      }
    });
  });
}

async function monitorIarRestore(job){
  const monitorStarted=Date.now();
  const check=async()=>{
    try{
      const r=await fetch(`/Other/restore-iar-status.php?job=${encodeURIComponent(job.jobId)}&nocache=${Date.now()}`,{
        credentials:'same-origin',cache:'no-store'
      });
      const d=await r.json();
      if(!d.ok) throw new Error(d.error || 'IAR restore monitor failed');
      if(d.error){
        setResult(`IAR RESTORE ERROR\n${d.avatar}\n${d.error}`,'error');
        restoreBusy=false;
        renderBackups();
        loadRestoreHistory();
        return;
      }
      if(d.complete){
        setResult(`IAR RESTORE COMPLETE\n${d.name}\nDestination: ${d.avatar}`,'ok');
        restoreBusy=false;
        renderBackups();
        loadRestoreHistory();
        return;
      }
      if(Date.now()-monitorStarted>14400000){
        setResult(`IAR RESTORE MONITOR TIMED OUT\n${d.name}\nDestination: ${d.avatar}`,'error');
        restoreBusy=false;
        renderBackups();
        loadRestoreHistory();
        return;
      }
      setResult(`IAR RESTORING\n${d.name}\nDestination: ${d.avatar}\nPlease wait…`,'ok');
      setTimeout(check,2000);
    }catch(e){
      setResult(`IAR RESTORE MONITOR ERROR\n${e.message}`,'error');
      restoreBusy=false;
      renderBackups();
      loadRestoreHistory();
    }
  };
  check();
}

async function monitorOarRestore(job){
  const monitorStarted = Date.now();
  const check = async () => {
    try{
      const r=await fetch(`/Other/restore-oar-status.php?job=${encodeURIComponent(job.jobId)}&nocache=${Date.now()}`,{
        credentials:'same-origin',cache:'no-store'
      });
      const d=await r.json();
      if(!d.ok) throw new Error(d.error || 'Restore monitor failed');
      if(d.error){
        setResult(`OAR RESTORE ERROR\n${d.region}\n${d.error}`,'error');
        restoreBusy=false;
        renderBackups();
        loadRestoreHistory();
        return;
      }
      if(d.complete){
        setResult(`OAR RESTORE COMPLETE\n${d.name}\nDestination: ${d.region}`,'ok');
        restoreBusy=false;
        renderBackups();
        loadRestoreHistory();
        setTimeout(()=>loadRestoreRegionData(),1000);
        return;
      }
      if(Date.now() - monitorStarted > 30000){
        setResult(`OAR RESTORE START ERROR\n${d.name}\nDestination: ${d.region}\nWorker did not start within 30 seconds.`,'error');
        restoreBusy=false;
        renderBackups();
        loadRestoreHistory();
        return;
      }
      setResult(`OAR RESTORING\n${d.name}\nDestination: ${d.region}\nPlease wait…`,'ok');
      setTimeout(check,2000);
    }catch(e){
      setResult(`OAR RESTORE MONITOR ERROR\n${e.message}`,'error');
      restoreBusy=false;
      renderBackups();
      loadRestoreHistory();
    }
  };
  check();
}

let gridAlertsTimer=null;
let gridAlertBusySince=new Map();

function alertNowText(){
  return new Date().toLocaleTimeString('en-AU',{hour:'numeric',minute:'2-digit',second:'2-digit'});
}

function alertSeverityRank(level){
  const x=String(level||'').toUpperCase();
  if(x==='ERROR') return 3;
  if(x==='WARNING') return 2;
  if(x==='INFO') return 1;
  return 0;
}

function renderGridAlerts(items){
  const box=document.getElementById('panel-alerts');
  const title=document.getElementById('grid-alerts-title');
  const body=document.getElementById('grid-alerts-body');
  const last=document.getElementById('grid-alerts-lastcheck');
  if(!box || !title || !body) return;

  const alerts=Array.isArray(items)?items:[];
  const worst=alerts.reduce((m,a)=>Math.max(m,alertSeverityRank(a.level)),0);

  box.classList.remove('healthy','warning','error');
  if(worst>=3) box.classList.add('error');
  else if(worst>=2) box.classList.add('warning');
  else box.classList.add('healthy');

  if(!alerts.length){
    title.textContent='ALL SYSTEMS NORMAL';
    body.innerHTML=`
      <div class="grid-alert-item healthy">
        <span class="grid-alert-level">HEALTHY</span>
        <span class="grid-alert-message">No region, backup or storage problems detected.</span>
      </div>`;
  }else{
    title.textContent=worst>=3?'ACTION REQUIRED':(worst>=2?'ATTENTION NEEDED':'SYSTEM NOTICE');
    body.innerHTML=alerts
      .sort((a,b)=>alertSeverityRank(b.level)-alertSeverityRank(a.level))
      .map(a=>`
        <div class="grid-alert-item ${String(a.level||'info').toLowerCase()}">
          <span class="grid-alert-level">${esc(String(a.level||'INFO').toUpperCase())}</span>
          <span class="grid-alert-message">${esc(a.message||'')}</span>
        </div>`).join('');
  }

  if(last) last.textContent=`Last checked ${alertNowText()}`;
}

async function loadGridAlerts(){
  if(Number(DG.level)<200) return;
  const btn=document.getElementById('alerts-refresh');
  if(btn) btn.disabled=true;

  const alerts=[];

  try{
    const [rr,hr]=await Promise.all([
      fetch(`/Other/regions.php?nocache=${Date.now()}`,{credentials:'same-origin',cache:'no-store'}),
      fetch(`/Other/backup-health.php?nocache=${Date.now()}`,{credentials:'same-origin',cache:'no-store'})
    ]);

    const regionsData=await rr.json();
    const healthData=await hr.json();

    if(!regionsData.ok) throw new Error(regionsData.error||'Region status unavailable');
    if(!healthData.ok) throw new Error(healthData.error||'Backup health unavailable');

    const regions=Array.isArray(regionsData.regions)?regionsData.regions:[];

    // Region state alerts.
    regions.forEach(r=>{
      const name=String(r.RegionName||'Unknown region');
      const effective=String(effectiveRegionStatus(r)||'').trim();
      const key=name.toLowerCase();

      if(regionIsOffline(effective)){
        alerts.push({level:'ERROR',message:`${name} is OFFLINE.`});
        gridAlertBusySince.delete(key);
        return;
      }

      if(regionIsBusy(effective)){
        if(!gridAlertBusySince.has(key)){
          gridAlertBusySince.set(key,Date.now());
        }
        const mins=Math.floor((Date.now()-gridAlertBusySince.get(key))/60000);
        const label=statusLabel(effective);

        // Busy state is informational initially, warning only if it persists.
        if(mins>=15){
          alerts.push({level:'WARNING',message:`${name} has remained ${label} for ${mins} minutes.`});
        }else{
          alerts.push({level:'INFO',message:`${name} is currently ${label}.`});
        }
      }else{
        gridAlertBusySince.delete(key);
      }
    });

    // Backup health alerts.
    const overall=String(healthData.overall||'WARNING').toUpperCase();
    if(overall==='ERROR'){
      alerts.push({level:'ERROR',message:'Backup Health reports an ERROR. Open Backup Health for details.'});
    }else if(overall==='WARNING'){
      alerts.push({level:'WARNING',message:'Backup Health reports a WARNING. Open Backup Health for details.'});
    }

    const errors=Number(healthData.errors)||0;
    const skipped=Number(healthData.skipped)||0;
    if(errors>0){
      alerts.push({level:'ERROR',message:`Last scheduled backup recorded ${errors} error${errors===1?'':'s'}.`});
    }
    if(skipped>0){
      alerts.push({level:'WARNING',message:`Last scheduled backup skipped ${skipped} region${skipped===1?'':'s'}.`});
    }

    // Storage threshold.
    const st=healthData.storage;
    if(st && Number.isFinite(Number(st.percentFree))){
      const pct=Number(st.percentFree);
      if(pct<8){
        alerts.push({level:'ERROR',message:`Backup drive is critically low: ${pct.toFixed(1)}% free.`});
      }else if(pct<15){
        alerts.push({level:'WARNING',message:`Backup drive is getting low: ${pct.toFixed(1)}% free.`});
      }
    }

    // Background scheduler / schedule state comes from backup health checks.
    const checks=Array.isArray(healthData.checks)?healthData.checks:[];
    checks.forEach(c=>{
      const level=String(c.level||'').toUpperCase();
      const title=String(c.title||'');
      if(level==='ERROR'){
        alerts.push({level:'ERROR',message:`${title}: ${String(c.detail||'Problem detected')}`});
      }else if(level==='WARNING'){
        alerts.push({level:'WARNING',message:`${title}: ${String(c.detail||'Attention required')}`});
      }
    });

    // Deduplicate repeated alert text.
    const seen=new Set();
    const unique=alerts.filter(a=>{
      const k=`${a.level}|${a.message}`;
      if(seen.has(k)) return false;
      seen.add(k);
      return true;
    });

    renderGridAlerts(unique);
  }catch(e){
    renderGridAlerts([{level:'ERROR',message:`Grid alert check failed: ${e.message}`}]);
  }finally{
    if(btn) btn.disabled=false;
  }
}

function startGridAlertWatch(){
  if(Number(DG.level)<200) return;
  if(gridAlertsTimer) clearInterval(gridAlertsTimer);
  gridAlertsTimer=setInterval(loadGridAlerts,60000);
}

function overviewSet(id,value,detail,state=''){
  const el=document.getElementById(id);
  if(!el) return;
  const v=el.querySelector('.overview-value');
  const d=el.querySelector('.overview-detail');
  if(v) v.textContent=value;
  if(d) d.textContent=detail;
  el.classList.remove('healthy','warning','error','offline');
  if(state) el.classList.add(state);
}

function overviewDate(value){
  if(!value) return 'Not recorded';
  const d=new Date(value);
  if(Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleString('en-AU',{
    day:'2-digit',month:'2-digit',year:'numeric',
    hour:'numeric',minute:'2-digit'
  });
}

async function loadGridOverview(){
  if(Number(DG.level)<200) return;
  const btn=document.getElementById('overview-refresh');
  if(btn) btn.disabled=true;

  try{
    const [rr,hr]=await Promise.all([
      fetch(`/Other/regions.php?nocache=${Date.now()}`,{credentials:'same-origin',cache:'no-store'}),
      fetch(`/Other/backup-health.php?nocache=${Date.now()}`,{credentials:'same-origin',cache:'no-store'})
    ]);
    const regionsData=await rr.json();
    const healthData=await hr.json();

    if(regionsData.ok){
      const regions=Array.isArray(regionsData.regions)?regionsData.regions:[];
      // For the overview, a region that is actively booting/restarting/backing up
      // is still a running/available region. Only explicit stopped/offline/shutdown
      // states count as offline.
      const online=regions.filter(r=>{
        const s=effectiveRegionStatus(r);
        return regionIsOnline(s) || regionIsBusy(s);
      }).length;
      const offline=regions.length-online;
      const avatars=regions.reduce((n,r)=>n+(Number(r.AvatarCount)||0),0);
      const prims=regions.reduce((n,r)=>n+(Number(r.PrimCount)||0),0);

      overviewSet('ov-regions',`${online}/${regions.length}`,
        offline ? `${online} online • ${offline} offline` : `${online} online • all available`,
        offline ? 'warning' : 'healthy');
      overviewSet('ov-avatars',String(avatars),
        avatars===1?'1 avatar currently online':`${avatars} avatars currently online`,
        'healthy');
      overviewSet('ov-prims',Number(prims).toLocaleString('en-AU'),
        `Across ${regions.length} region${regions.length===1?'':'s'}`,
        'healthy');
    }else{
      throw new Error(regionsData.error||'Region status unavailable');
    }

    if(healthData.ok){
      const overall=String(healthData.overall||'WARNING').toUpperCase();
      const state=overall==='HEALTHY'?'healthy':(overall==='ERROR'?'error':'warning');
      overviewSet('ov-health',overall,
        healthData.errors>0 ? `${healthData.errors} backup error(s)` :
        healthData.skipped>0 ? `${healthData.skipped} skipped` : 'Backup system operating normally',
        state);

      const last=healthData.lastFinished||healthData.lastRun;
      overviewSet('ov-last-backup',overviewDate(last),
        `${Number(healthData.completed)||0} completed • ${Number(healthData.errors)||0} errors`,
        Number(healthData.errors)>0?'error':'healthy');

      const st=healthData.storage;
      if(st && Number.isFinite(Number(st.percentFree))){
        const pct=Number(st.percentFree);
        overviewSet('ov-storage',`${pct.toFixed(1)}%`,
          `${storageSize(Number(st.free)||0)} free`,
          pct<8?'error':(pct<15?'warning':'healthy'));
      }else{
        overviewSet('ov-storage','UNKNOWN','Unable to read free space','warning');
      }
    }else{
      throw new Error(healthData.error||'Backup health unavailable');
    }
  }catch(e){
    ['ov-regions','ov-avatars','ov-prims','ov-health','ov-last-backup','ov-storage']
      .forEach(id=>overviewSet(id,'—',e.message||'Unable to load overview','error'));
  }finally{
    if(btn) btn.disabled=false;
  }
}

async function loadBackupHealth(){
  if(Number(DG.level)<200) return;
  const summary=document.getElementById('backup-health-summary');
  const host=document.getElementById('backup-health-checks');
  if(!summary || !host) return;

  try{
    const r=await fetch(`/Other/backup-health.php?nocache=${Date.now()}`,{
      credentials:'same-origin',cache:'no-store'
    });
    const raw=await r.text();
    let d;
    try{ d=JSON.parse(raw); }
    catch(e){ throw new Error(raw || 'Invalid backup-health response'); }
    if(!d.ok) throw new Error(d.error || 'Unable to read backup health');

    const overall=String(d.overall||'WARNING').toUpperCase();
    summary.className=`backup-health-summary ${overall.toLowerCase()}`;
    summary.innerHTML=`<strong>${esc(overall)}</strong><span>Backup system health</span>`;

    const checks=Array.isArray(d.checks)?d.checks:[];
    host.innerHTML=checks.length ? checks.map(c=>{
      const level=String(c.level||'WARNING').toUpperCase();
      return `<div class="backup-health-row ${level.toLowerCase()}">
        <span class="backup-health-state">${esc(level)}</span>
        <span class="backup-health-title">${esc(c.title||'Check')}</span>
        <span class="backup-health-detail">${esc(c.detail||'')}</span>
      </div>`;
    }).join('') : '<div class="history-empty">No health checks returned.</div>';
  }catch(e){
    summary.className='backup-health-summary error';
    summary.innerHTML='<strong>ERROR</strong><span>Unable to read backup health</span>';
    host.innerHTML=`<div class="backup-health-row error"><span class="backup-health-state">ERROR</span><span class="backup-health-title">Backup Health</span><span class="backup-health-detail">${esc(e.message)}</span></div>`;
  }
}

let backupHistoryRows=[];

async function addBackupHistory(entry){
  if(Number(DG.level)<200) return;
  try{
    const body=new URLSearchParams({action:'add',entry:JSON.stringify(entry)});
    await fetch('/Other/backup-history.php',{
      method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
      body,credentials:'same-origin',cache:'no-store'
    });
    await loadBackupHistory();
  }catch(e){}
}

function historyTime(v){
  try{const d=new Date(v);return isNaN(d.getTime())?String(v||''):d.toLocaleString('en-AU');}
  catch(e){return String(v||'');}
}

function renderBackupHistory(){
  const host=document.getElementById('backup-history');
  const summary=document.getElementById('history-summary');
  if(!host) return;

  const q=String(document.getElementById('history-search')?.value||'').trim().toLowerCase();
  const type=document.getElementById('history-type')?.value||'ALL';
  const status=document.getElementById('history-status')?.value||'ALL';
  const source=document.getElementById('history-source')?.value||'ALL';

  const rows=backupHistoryRows.filter(r=>{
    if(type!=='ALL' && r.type!==type) return false;
    if(status!=='ALL' && r.status!==status) return false;
    if(source!=='ALL' && r.source!==source) return false;
    if(q){
      const hay=[r.subject,r.file,r.type,r.source,r.status,r.message,r.actor].join(' ').toLowerCase();
      if(!hay.includes(q)) return false;
    }
    return true;
  });

  const success=backupHistoryRows.filter(r=>r.status==='SUCCESS').length;
  const failed=backupHistoryRows.filter(r=>r.status==='FAILED').length;
  const scheduled=backupHistoryRows.filter(r=>r.source==='SCHEDULED').length;
  if(summary) summary.textContent=`${rows.length} shown · ${backupHistoryRows.length} total · ${success} successful · ${failed} failed · ${scheduled} scheduled`;

  if(!rows.length){
    host.innerHTML='<div class="history-empty">No backup history matches these filters.</div>';
    return;
  }

  host.innerHTML=`
    <div class="history-row history-head">
      <span>DATE / TIME</span><span>TYPE</span><span>SOURCE</span><span>RESULT</span><span>REGION / USER</span><span>FILE</span><span>SIZE</span>
    </div>`+
    rows.map(r=>`
      <div class="history-row" title="${attr(r.message||'')}">
        <span>${esc(historyTime(r.time))}</span>
        <strong>${esc(r.type)}</strong>
        <span>${esc(r.source)}</span>
        <span class="history-result ${String(r.status||'').toLowerCase()}">${esc(r.status)}</span>
        <span class="history-subject">${esc(r.subject||'—')}</span>
        <span class="history-file">${esc(r.file||r.message||'—')}</span>
        <span class="history-size">${Number(r.size||0)>0?fmtBytes(Number(r.size)):'—'}</span>
      </div>`).join('');
}

async function getBackupHistoryCutoff(){

  try{

    const response =
      await fetch(
        `/Other/backup-history-cutoff.php?nocache=${Date.now()}`,
        {
          credentials:'same-origin',
          cache:'no-store'
        }
      );

    const raw =
      await response.text();

    const data =
      JSON.parse(raw);

    if(!data.ok){
      return 0;
    }

    return Number(
      data.cutoff || 0
    );

  }
  catch(error){

    return 0;
  }
}


async function setBackupHistoryCutoff(){

  const body =
    new URLSearchParams();

  body.set(
    'action',
    'set'
  );


  const response =
    await fetch(
      '/Other/backup-history-cutoff.php',
      {
        method:'POST',

        headers:{
          'Content-Type':
            'application/x-www-form-urlencoded;charset=UTF-8'
        },

        body:body,

        credentials:'same-origin',

        cache:'no-store'
      }
    );


  const raw =
    await response.text();


  let data;


  try{

    data =
      JSON.parse(raw);
  }
  catch(error){

    throw new Error(
      raw ||
      'Invalid history cutoff response'
    );
  }


  if(!data.ok){

    throw new Error(
      data.error ||
      'Unable to save history clear point'
    );
  }


  return Number(
    data.cutoff || 0
  );
}


async function loadBackupHistory(){

  if(Number(DG.level) < 200){
    return;
  }


  const host =
    document.getElementById(
      'backup-history'
    );


  if(!host){
    return;
  }


  try{

    const cutoff =
      await getBackupHistoryCutoff();


    const response =
      await fetch(
        `/Other/backup-history.php?nocache=${Date.now()}`,
        {
          credentials:'same-origin',
          cache:'no-store'
        }
      );


    const raw =
      await response.text();


    let data;


    try{

      data =
        JSON.parse(raw);
    }
    catch(error){

      throw new Error(
        raw ||
        'Invalid history response'
      );
    }


    if(!data.ok){

      throw new Error(
        data.error ||
        'Unable to load backup history'
      );
    }


    const rows =
      Array.isArray(data.history)
        ?
        data.history
        :
        [];


    backupHistoryRows =
      rows.filter(
        function(row){

          if(!cutoff){
            return true;
          }


          const rowTime =
            Date.parse(
              String(
                row.time || ''
              )
            );


          if(!Number.isFinite(rowTime)){

            return false;
          }


          return rowTime > cutoff;
        }
      );


    renderBackupHistory();

  }
  catch(error){

    host.innerHTML =
      `<div class="history-empty">BACKUP HISTORY ERROR — ${esc(error.message)}</div>`;
  }
}

async function clearBackupHistory(){

  const confirmed =
    await uiConfirm(

      'CLEAR BACKUP HISTORY',

      `<div class="dg-modal-warning">
         <strong>Clear displayed backup history</strong>
         <span>Old OAR and IAR history entries will be removed from this list.</span>
         <span>The actual OAR and IAR backup files will NOT be deleted.</span>
         <span>Old backup files will NOT come back when you press Refresh.</span>
       </div>`,

      'CLEAR HISTORY',

      true
    );


  if(!confirmed){
    return;
  }


  try{

    const body =
      new URLSearchParams();

    body.set(
      'action',
      'clear'
    );


    const response =
      await fetch(
        '/Other/backup-history.php',
        {
          method:'POST',

          headers:{
            'Content-Type':
              'application/x-www-form-urlencoded;charset=UTF-8'
          },

          body:body,

          credentials:'same-origin',

          cache:'no-store'
        }
      );


    const raw =
      await response.text();


    let data;


    try{

      data =
        JSON.parse(raw);
    }
    catch(error){

      throw new Error(
        raw ||
        'Invalid clear history response'
      );
    }


    if(!data.ok){

      throw new Error(
        data.error ||
        'Unable to clear backup history'
      );
    }


    /*
     * Store the time of this clear.
     * This prevents old physical backup files from being
     * re-imported into the displayed history.
     */

    await setBackupHistoryCutoff();


    backupHistoryRows = [];

    renderBackupHistory();


    await loadBackupHistory();


    setResult(
      'BACKUP HISTORY CLEARED\n' +
      'Old history will stay cleared after Refresh.\n' +
      'No OAR or IAR files were deleted.',
      'ok'
    );

  }
  catch(error){

    setResult(
      `BACKUP HISTORY ERROR\n${error.message}`,
      'error'
    );
  }
}

async function loadBackups(){
  const box = document.getElementById('backup-files');
  if(!box) return;

  try{
    const r = await fetch('/Other/list-backups.php', {
      credentials:'same-origin',
      cache:'no-store'
    });
    const raw = await r.text();
    let d;
    try { d = JSON.parse(raw); }
    catch(e){ throw new Error(`Backup list returned: ${raw || '(empty response)'}`); }

    if(!d.ok) throw new Error(d.error || 'Unable to load backups');
    backupRows = Array.isArray(d.backups) ? d.backups : [];
    const validKeys=new Set(backupRows.map(backupSelectionKey).filter(Boolean));
    for(const key of Array.from(selectedBackups.keys())) if(!validKeys.has(key)) selectedBackups.delete(key);
    updateCleanupSummary();

    if(!backupRows.length){
      box.innerHTML = '<div class="backup-empty">No downloadable V40+ IARs or OAR backups found.</div>';
      return;
    }
    renderBackups();
  }catch(e){
    box.innerHTML = `<div class="backup-empty backup-error">BACKUP LIST ERROR — ${esc(e.message)}</div>`;
  }
}

async function loadLocalAvatars(){
  if(Number(DG.level) < 200) return;

  const select = document.getElementById('admin-iar-avatar');
  const button = document.getElementById('admin-save-iar');
  if(!select || !button) return;

  button.disabled = true;

  try{
    const r = await fetch('/Other/users.php', {
      credentials:'same-origin',
      cache:'no-store'
    });
    const raw = await r.text();
    let d;
    try { d = JSON.parse(raw); }
    catch(e){ throw new Error(`User list returned: ${raw || '(empty response)'}`); }

    if(!d.ok) throw new Error(d.error || 'Unable to load local avatars');

    restoreAvatarNames = d.users.map(u => String(u.avatar || '')).filter(Boolean);

    restoreAvatarNames = d.users.map(u => String(u.avatar || '')).filter(Boolean);

    select.innerHTML = '<option value="">Select local avatar…</option>' +
      d.users.map(u =>
        `<option value="${attr(u.avatar)}">${esc(u.avatar)}${Number(u.level) >= 200 ? ' — ADMIN' : ''}</option>`
      ).join('');

    button.disabled = false;
  }catch(e){
    select.innerHTML = '<option value="">Unable to load local avatars</option>';
    setIarStatusTarget('admin', `USER LIST ERROR — ${e.message}`, 'error');
  }
}

async function monitorIAR(job, statusTarget='self'){
  let polls = 0;
  let lastSize = -1;
  let stableCount = 0;
  const maxPolls = 1440; // ~2 hours at 5 seconds

  if(iarMonitorId){
    clearInterval(iarMonitorId);
    iarMonitorId = null;
  }

  async function check(){
    polls++;
    try{
      if(job.jobId){
        const ju = new URL('/Other/iar-job-status.php', window.location.origin);
        ju.searchParams.set('job', job.jobId);
        const jr = await fetch(ju, {credentials:'same-origin', cache:'no-store'});
        const jraw = await jr.text();
        let jd;
        try { jd = JSON.parse(jraw); }
        catch(e){ throw new Error(`IAR worker status returned: ${jraw || '(empty response)'}`); }
        if(jd.ok && jd.error){
          msg(`IAR BACKUP ERROR — ${iarAvatar(job)}\n${jd.error}`, true);
          stop();
          return;
        }
      }

      const u = new URL('/Other/iar-status.php', window.location.origin);
      u.searchParams.set('name', job.name);
      u.searchParams.set('folder', job.folder);
      if(job.jobId) u.searchParams.set('job', job.jobId);

      const r = await fetch(u, {credentials:'same-origin', cache:'no-store'});
      const raw = await r.text();
      let d;
      try { d = JSON.parse(raw); }
      catch(e){ throw new Error(`IAR status returned: ${raw || '(empty response)'}`); }

      if(!d.ok){
        msg(`IAR MONITOR ERROR\n${d.error || 'Unknown error'}`, true);
        stop();
        return;
      }

      if(!d.found){
        msg(`IAR BACKUP STARTED — ${iarAvatar(job)}\nWaiting for OpenSim to create ${job.name}…`);
      } else if(Number(d.size) === 0){
        // Confirmed behaviour on this grid: IAR can remain 0 KB for the whole
        // inventory/asset collection phase, then jump to its final size.
        msg(`IAR BACKUP PROCESSING — ${iarAvatar(job)}\nFile: ${job.name}\nCurrent size: 0 B\nOpenSim is collecting inventory and assets. 0 KB is normal while processing…`);
        stableCount = 0;
        lastSize = 0;
      } else {
        const size = Number(d.size);
        if(size === lastSize) stableCount++;
        else stableCount = 0;
        lastSize = size;

        if(stableCount >= 12){
          msg(`IAR BACKUP COMPLETE — ${iarAvatar(job)}\nFile: ${job.name}\nFinal size: ${fmtBytes(size)}`);
          setIarStatusTarget(statusTarget, `BACKUP COMPLETE — ${job.name} — ${fmtBytes(size)}`, 'complete');
          addBackupHistory({
            time:new Date().toISOString(),type:'IAR',source:'MANUAL',status:'SUCCESS',
            subject:iarAvatar(job),file:job.name,size:size,message:'Inventory backup complete'
          });
          loadBackups();
          stop();
          return;
        }

        msg(`IAR BACKUP FINALISING — ${iarAvatar(job)}\nFile: ${job.name}\nCurrent size: ${fmtBytes(size)}\nWaiting for the archive size to remain stable…`);
        setIarStatusTarget(statusTarget, `FINALISING — ${job.name} — ${fmtBytes(size)}`, 'finalising');
      }

      if(polls >= maxPolls){
        msg(`IAR BACKUP MONITOR TIMED OUT — ${iarAvatar(job)}\nThe archive may still be running. Check the Autobackup folder.`, true);
        setIarStatusTarget(statusTarget, 'BACKUP STATUS UNKNOWN — archive may still be running', 'error');
        stop();
      }
    }catch(e){
      msg(`IAR MONITOR ERROR\n${e.message}`, true);
      setIarStatusTarget(statusTarget, `BACKUP ERROR — ${e.message}`, 'error');
      stop();
    }
  }

  function stop(){
    if(iarMonitorId) clearInterval(iarMonitorId);
    iarMonitorId = null;

    if(statusTarget === 'admin'){
      const b = document.getElementById('admin-save-iar');
      const s = document.getElementById('admin-iar-avatar');
      if(b) b.disabled = false;
      if(s) s.disabled = false;
    }
  }

  await check();
  if(!iarMonitorId){
    iarMonitorId = setInterval(check, 5000);
  }
}

function scheduleSetStatus(text,state='processing'){
  const el=document.getElementById('schedule-status');
  if(!el) return;
  el.hidden=false;
  el.className='iar-panel-status iar-'+state;
  el.textContent=text;
}

function scheduleNextText(v){
  if(!v) return 'Not scheduled';
  try{
    const d=new Date(v);
    return isNaN(d.getTime()) ? String(v) : d.toLocaleString('en-AU');
  }catch(e){ return String(v); }
}

function updateScheduleDayVisibility(){
  const type=document.getElementById('schedule-type')?.value || 'daily';
  const days=document.getElementById('schedule-days');
  if(days) days.classList.toggle('is-hidden',type==='daily');
}

function updateScheduleRegionCount(){
  const el=document.getElementById('schedule-region-count');
  if(el) el.textContent=`${scheduleSelectedRegions.size} selected`;
}

function renderScheduleRegions(){
  const host=document.getElementById('schedule-regions');
  if(!host) return;

  if(!gridBackupRegions.length){
    host.innerHTML='<div class="backup-empty">No regions available.</div>';
    updateScheduleRegionCount();
    return;
  }

  host.innerHTML=gridBackupRegions.map(r=>{
    const name=String(r.RegionName || '');
    const online=gridBackupRegionOnline(r);
    const checked=scheduleSelectedRegions.has(name);
    return `
      <label class="schedule-region-row${online?'':' is-offline'}">
        <input type="checkbox" class="schedule-region-check" value="${attr(name)}"${checked?' checked':''}${online?'':' disabled'}>
        <span>
          <span class="schedule-region-name">${esc(name)}</span>
          <span class="schedule-region-meta">${esc(r.Size)} · ${esc(r.EstateName)}</span>
        </span>
        <span class="schedule-region-state">${online?'ONLINE':'OFFLINE'}</span>
      </label>
    `;
  }).join('');

  host.querySelectorAll('.schedule-region-check').forEach(cb=>{
    cb.addEventListener('change',()=>{
      const name=String(cb.value||'');
      if(cb.checked) scheduleSelectedRegions.add(name);
      else scheduleSelectedRegions.delete(name);
      updateScheduleRegionCount();
    });
  });
  updateScheduleRegionCount();
}

function applyScheduleConfig(c){
  scheduleConfig=c || {};
  const enabled=document.getElementById('schedule-enabled');
  const type=document.getElementById('schedule-type');
  const time=document.getElementById('schedule-time');
  const keep=document.getElementById('schedule-keep');

  if(enabled) enabled.checked=!!scheduleConfig.enabled;
  if(type) type.value=scheduleConfig.type || 'daily';
  if(time) time.value=scheduleConfig.time || '02:00';
  if(keep) keep.value=String(scheduleConfig.keepLast || 3);

  const days=new Set((scheduleConfig.days || []).map(Number));
  document.querySelectorAll('#schedule-days input[type="checkbox"]').forEach(cb=>{
    cb.checked=days.has(Number(cb.value));
  });

  scheduleSelectedRegions.clear();
  (scheduleConfig.regions || []).forEach(r=>scheduleSelectedRegions.add(String(r)));
  renderScheduleRegions();
  updateScheduleDayVisibility();
}

function schedulerRunnerText(runner){
  if(!runner || !runner.installed) return 'NOT INSTALLED';

  const state=runner.state || {};
  const checkTime=state.lastCheck ? scheduleNextText(state.lastCheck) : 'Never';
  const checkResult=state.lastCheckResult || state.lastResult || 'No check result yet';

  return `INSTALLED | Last check: ${checkTime} | ${checkResult}`;
}

function schedulerLastBackupText(runner){
  const state=(runner && runner.state) || {};
  const started=state.lastBackupRun || state.lastRun || '';
  const result=state.lastBackupResult || '';
  const completed=Number(state.lastBackupCompleted ?? state.completed ?? 0);
  const skipped=Number(state.lastBackupSkipped ?? state.skipped ?? 0);
  const errors=Number(state.lastBackupErrors ?? state.errors ?? 0);

  if(!started || (!result && completed===0 && skipped===0 && errors===0)){
    return 'Never';
  }

  return `${scheduleNextText(started)} | Completed: ${completed} | Skipped: ${skipped} | Errors: ${errors}`;
}

function schedulerLastBackupRegionsHtml(runner){
  const state=(runner && runner.state) || {};
  const rows=Array.isArray(state.lastBackupRegions) && state.lastBackupRegions.length
    ? state.lastBackupRegions
    : (Array.isArray(state.regions) ? state.regions : []);

  if(!rows.length) return '';

  return rows.map(r=>{
    const status=String(r.status || 'UNKNOWN').toUpperCase();
    const size=Number(r.size || 0);
    const sizeText=size>0 ? ` — ${fmtBytes(size)}` : '';
    const file=r.file ? ` — ${esc(r.file)}` : '';
    const message=r.message ? ` — ${esc(r.message)}` : '';
    return `${esc(r.region || 'Unknown')} — ${esc(status)}${sizeText}${file}${message}`;
  }).join('\n');
}

async function loadBackupSchedule(){
  if(Number(DG.level)<200) return;
  try{
    const r=await fetch(`/Other/backup-schedule.php?nocache=${Date.now()}`,{
      credentials:'same-origin',cache:'no-store'
    });
    const raw=await r.text();
    let d;
    try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid schedule response'); }
    if(!d.ok) throw new Error(d.error || 'Unable to load schedule');

    applyScheduleConfig(d.config || {});

    const regionResults=schedulerLastBackupRegionsHtml(d.runner);
    const lastBackup=schedulerLastBackupText(d.runner);

    scheduleSetStatus(
      `SCHEDULE STATUS\n` +
      `Next backup: ${scheduleNextText(d.nextRun)}\n` +
      `Last scheduled backup: ${lastBackup}\n` +
      (regionResults ? `Regions:\n${regionResults}\n` : '') +
      `Keep last: ${Number(d.config?.keepLast || 3)} per region\n` +
      `Background runner: ${schedulerRunnerText(d.runner)}`,
      Number(d.runner?.state?.lastBackupErrors || 0) > 0 ? 'error'
        : (d.runner?.state?.lastBackupRun ? 'complete' : (d.config?.enabled ? 'finalising' : 'processing'))
    );

    // On page load/refresh, replace an old "BACKUP SCHEDULE SAVED" result
    // with the latest real background-backup result when one exists.
    if(d.runner?.state?.lastBackupRun){
      const rs=d.runner.state;
      setResult(
        `SCHEDULED BACKUP COMPLETE\n` +
        `Last run: ${scheduleNextText(rs.lastBackupRun)}\n` +
        `Completed: ${Number(rs.lastBackupCompleted ?? rs.completed ?? 0)}\n` +
        `Skipped: ${Number(rs.lastBackupSkipped ?? rs.skipped ?? 0)}\n` +
        `Errors: ${Number(rs.lastBackupErrors ?? rs.errors ?? 0)}\n` +
        `Next backup: ${scheduleNextText(d.nextRun)}`,
        Number(rs.lastBackupErrors ?? rs.errors ?? 0) > 0 ? 'error' : 'ok'
      );
    }
  }catch(e){
    scheduleSetStatus(`SCHEDULE ERROR\n${e.message}`,'error');
  }
}

async function saveBackupSchedule(){
  const type=document.getElementById('schedule-type')?.value || 'daily';
  const selectedDays=Array.from(document.querySelectorAll('#schedule-days input[type="checkbox"]:checked')).map(x=>Number(x.value));

  const scheduleEnabled=!!document.getElementById('schedule-enabled')?.checked;

  if(scheduleEnabled){
    if(type==='weekly' && selectedDays.length!==1){
      alert('For WEEKLY scheduling, select exactly one day.');
      return;
    }
    if(type==='selected' && selectedDays.length===0){
      alert('Select at least one day for SELECTED DAYS.');
      return;
    }
    const scheduleEnabled=!!document.getElementById('schedule-enabled')?.checked;

  if(scheduleEnabled && scheduleSelectedRegions.size===0){
    alert('Select at least one region for the schedule.');
    return;
  }
  }

  const config={
    enabled:scheduleEnabled,
    type,
    time:document.getElementById('schedule-time')?.value || '02:00',
    days:selectedDays,
    regions:Array.from(scheduleSelectedRegions),
    keepLast:Number(document.getElementById('schedule-keep')?.value || 3)
  };

  const body=new URLSearchParams();
  body.set('config',JSON.stringify(config));

  try{
    const r=await fetch('/Other/backup-schedule.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
      body,
      credentials:'same-origin',
      cache:'no-store'
    });
    const raw=await r.text();
    let d;
    try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid schedule save response'); }
    if(!d.ok) throw new Error(d.error || 'Unable to save schedule');

    applyScheduleConfig(d.config || config);
    scheduleSetStatus(
      `SCHEDULE SAVED\n` +
      `Regions: ${(d.config?.regions || []).length}\n` +
      `Next backup: ${scheduleNextText(d.nextRun)}\n` +
      `Keep last: ${Number(d.config?.keepLast || 3)} per region\n` +
      `Background runner: ${schedulerRunnerText(d.runner)}`,
      'complete'
    );
    setResult(`BACKUP SCHEDULE SAVED\nNext backup: ${scheduleNextText(d.nextRun)}`,'ok');
  }catch(e){
    scheduleSetStatus(`SCHEDULE SAVE ERROR\n${e.message}`,'error');
  }
}

async function runScheduleNow(){
  if(gridBackupRunning){
    alert('A region backup is already running.');
    return;
  }

  const chosen=Array.from(scheduleSelectedRegions);
  if(!chosen.length){
    alert('No scheduled regions are selected.');
    return;
  }

  gridBackupSelected.clear();
  chosen.forEach(name=>gridBackupSelected.add(name));
  renderGridBackupRegions();
  updateGridBackupControls();

  // Reuse the proven V55 sequential backup engine exactly as-is.
  await runGridBackup();
}

async function loadGridBackupRegions(){
  const host=document.getElementById('grid-backup-regions');
  if(!host) return;

  host.innerHTML='<div class="loading">Loading regions...</div>';

  try{
    const r=await fetch(`/Other/regions.php?gridbackup=1&nocache=${Date.now()}`,{
      credentials:'same-origin',
      cache:'no-store'
    });
    const raw=await r.text();
    let d;
    try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid region list response'); }
    if(!d.ok) throw new Error(d.error || 'Unable to load regions');

    gridBackupRegions=Array.isArray(d.regions) ? d.regions.slice() : [];

    const valid=new Set(
      gridBackupRegions
        .filter(gridBackupRegionOnline)
        .map(x=>String(x.RegionName || ''))
    );
    Array.from(gridBackupSelected).forEach(name=>{
      if(!valid.has(name)) gridBackupSelected.delete(name);
    });

    renderGridBackupRegions();
    renderScheduleRegions();
  }catch(e){
    host.innerHTML=`<div class="backup-empty backup-error">REGION BACKUP LIST ERROR - ${esc(e.message)}</div>`;
    gridBackupRegions=[];
    gridBackupSelected.clear();
    updateGridBackupControls();
  }
}

function gridBackupSetSummary(text, state='processing'){
  const el=document.getElementById('grid-backup-summary');
  if(!el) return;
  el.hidden=false;
  el.className='iar-panel-status iar-'+state;
  el.textContent=text;
}

function gridBackupStatusClass(status){
  const s=String(status || '').trim().toLowerCase();
  if(['booted','running','online'].includes(s)) return 'online';
  if(['stopped','offline','shutdown'].includes(s)) return 'offline';
  if(['booting','starting','recyclingdown','restart','restarting','stopping','backingup','backup','savingoar','saving'].includes(s)) return 'busy';
  return 'unknown';
}

function gridBackupRegionOnline(r){
  return ['booted','running','online'].includes(String(effectiveRegionStatus(r)||'').trim().toLowerCase());
}

function updateGridBackupControls(){
  const count=document.getElementById('grid-backup-count');
  const start=document.getElementById('grid-backup-start');
  const selectAll=document.getElementById('grid-backup-select-all');
  const clear=document.getElementById('grid-backup-clear');
  if(count) count.textContent=`${gridBackupSelected.size} selected`;
  if(start) start.disabled=gridBackupRunning || gridBackupSelected.size===0;
  if(selectAll) selectAll.disabled=gridBackupRunning;
  if(clear) clear.disabled=gridBackupRunning;
}

function renderGridBackupRegions(){
  if(Number(DG.level)<200) return;
  const host=document.getElementById('grid-backup-regions');
  if(!host) return;

  if(!gridBackupRegions.length){
    host.innerHTML='<div class="backup-empty">No regions available.</div>';
    updateGridBackupControls();
    return;
  }

  host.innerHTML=gridBackupRegions.map(r=>{
    const name=String(r.RegionName || '');
    const online=gridBackupRegionOnline(r);
    const checked=gridBackupSelected.has(name);
    const status=String(effectiveRegionStatus(r) || 'Unknown');
    return `
      <label class="grid-backup-row${online?'':' is-disabled'}" data-grid-backup-row="${attr(name)}">
        <input type="checkbox" class="grid-backup-check" value="${attr(name)}"${checked?' checked':''}${(!online || gridBackupRunning)?' disabled':''}>
        <span>
          <span class="grid-backup-region-name">${esc(name)}</span>
          <span class="grid-backup-region-meta">${esc(r.Size)} · ${esc(r.EstateName)} · ${esc(statusLabel(status))}</span>
        </span>
        <span class="grid-backup-status" data-grid-backup-status="${attr(name)}">${online?'READY':'NOT ONLINE'}</span>
      </label>
    `;
  }).join('');

  host.querySelectorAll('.grid-backup-check').forEach(cb=>{
    cb.addEventListener('change',()=>{
      const name=String(cb.value || '');
      if(cb.checked) gridBackupSelected.add(name);
      else gridBackupSelected.delete(name);
      updateGridBackupControls();
    });
  });

  updateGridBackupControls();
}

function setGridBackupRowState(region, text, state='running'){
  const row=Array.from(document.querySelectorAll('[data-grid-backup-row]'))
    .find(x=>x.dataset.gridBackupRow===region);
  const status=Array.from(document.querySelectorAll('[data-grid-backup-status]'))
    .find(x=>x.dataset.gridBackupStatus===region);
  if(row){
    row.classList.remove('is-running','is-complete','is-error');
    if(state==='running') row.classList.add('is-running');
    if(state==='complete') row.classList.add('is-complete');
    if(state==='error') row.classList.add('is-error');
  }
  if(status) status.textContent=text;
}

async function waitForGridOar(region, started){
  let lastSize=-1;
  let stableCount=0;
  const maxPolls=1440;

  for(let polls=1; polls<=maxPolls; polls++){
    const u=new URL('/Other/oar-status.php',window.location.origin);
    u.searchParams.set('region',region);
    u.searchParams.set('started',String(started));

    const r=await fetch(u,{credentials:'same-origin',cache:'no-store'});
    const raw=await r.text();
    let d;
    try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid OAR status response'); }
    if(!d.ok) throw new Error(d.error || 'OAR monitor error');

    if(!d.found){
      setGridBackupRowState(region,'WAITING FOR ARCHIVE…','running');
    }else{
      const size=Number(d.size || 0);
      if(size===lastSize) stableCount++;
      else stableCount=0;
      lastSize=size;

      if(size<=0){
        setGridBackupRowState(region,'ARCHIVE CREATED — WAITING FOR DATA…','running');
      }else{
        setGridBackupRowState(region,`SAVING — ${fmtBytes(size)}`,'running');
      }

      if(size>0 && stableCount>=12){
        setGridBackupRowState(region,`COMPLETE — ${fmtBytes(size)}`,'complete');
        rememberOarComplete(region);
        await refreshDreamGridBackupStatus(region);
        return {name:d.name,size};
      }
    }

    await new Promise(resolve=>setTimeout(resolve,5000));
  }

  throw new Error('Backup monitor timed out');
}

async function runGridBackup(){
  if(gridBackupRunning || gridBackupSelected.size===0) return;

  const queue=gridBackupRegions
    .filter(r=>gridBackupSelected.has(String(r.RegionName || '')) && gridBackupRegionOnline(r))
    .map(r=>String(r.RegionName || ''));

  if(!queue.length){
    gridBackupSetSummary('No selected regions are currently online.','error');
    return;
  }

  if(!(await uiConfirm(
    'START REGION BACKUP',
    `<div class="dg-modal-copy"><p>${queue.length} region${queue.length===1?'':'s'} will be backed up <strong>one at a time</strong>.</p>` +
    `<div class="dg-modal-list">${queue.map((r,i)=>`<div><span>${i+1}</span><strong>${esc(r)}</strong></div>`).join('')}</div></div>`,
    'START BACKUPS'
  ))) return;

  gridBackupRunning=true;
  updateGridBackupControls();
  renderGridBackupRegions();

  const completed=[];
  const failed=[];

  for(let i=0;i<queue.length;i++){
    const region=queue[i];
    const started=Math.floor(Date.now()/1000);
    gridBackupSetSummary(
      `GRID BACKUP RUNNING — ${i+1} of ${queue.length}\nCurrent region: ${region}\nCompleted: ${completed.length}\nErrors: ${failed.length}`,
      'processing'
    );
    setGridBackupRowState(region,'STARTING…','running');

    try{
      const body=new URLSearchParams({command:'SaveOAR',region});
      const r=await fetch('/Other/command.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body,
        credentials:'same-origin',
        cache:'no-store'
      });
      const raw=await r.text();
      let d;
      try{ d=JSON.parse(raw); }catch(e){ throw new Error(raw || 'Invalid grid service response'); }
      if(!d.ok) throw new Error(d.error || 'SaveOAR was rejected');

      clearOarComplete(region);
      setGridBackupRowState(region,'BACKING UP…','running');
      const result=await waitForGridOar(region,started);
      completed.push({region,...result});
      await addBackupHistory({
        time:new Date(started*1000).toISOString(),type:'OAR',source:'MANUAL',status:'SUCCESS',
        subject:region,file:result.name,size:Number(result.size||0),
        duration:Math.max(0,Math.floor(Date.now()/1000)-started),message:'Manual region backup complete'
      });

      await loadBackups();
      await loadBackupStorage();
    }catch(e){
      failed.push({region,error:e.message});
      await addBackupHistory({
        time:new Date(started*1000).toISOString(),type:'OAR',source:'MANUAL',status:'FAILED',
        subject:region,file:'',size:0,duration:Math.max(0,Math.floor(Date.now()/1000)-started),message:e.message
      });
      setGridBackupRowState(region,`ERROR — ${e.message}`,'error');
    }
  }

  gridBackupRunning=false;
  gridBackupSelected.clear();
  updateGridBackupControls();

  const doneText=
    `GRID BACKUP FINISHED\n` +
    `Completed: ${completed.length} of ${queue.length}\n` +
    `Errors: ${failed.length}` +
    (failed.length ? `\n\n${failed.map(x=>`${x.region}: ${x.error}`).join('\n')}` : '');

  gridBackupSetSummary(doneText, failed.length ? 'error' : 'complete');
  setResult(doneText, failed.length ? 'error' : 'ok');

  await loadBackups();
  await loadBackupStorage();
  await loadRestoreRegionData();
}

async function loadRestoreRegionData(){

  try{

    const r = await fetch('/Other/regions.php', {
      credentials:'same-origin',
      cache:'no-store'
    });

    const d = await r.json();

    if(!d.ok){
      throw new Error(
        d.error || 'Unable to load region data'
      );
    }

    const rows =
      Array.isArray(d.regions)
        ? d.regions
        : [];

    restoreRegionNames =
      rows
        .map(x => String(x.RegionName || ''))
        .filter(Boolean);

    restoreRegionSizes = {};

    rows.forEach(x => {

      const name =
        String(x.RegionName || '');

      if(name){

        restoreRegionSizes[name] =
          String(x.Size || '').toUpperCase();

      }

    });

    /*
     * Preserve completed OAR-state bookkeeping used by
     * GRID BACKUP and backup-status refresh logic.
     */
    rows.forEach(x => {

      const raw =
        String(x.Status || '')
          .trim()
          .toLowerCase();

      if(
        raw &&
        raw !== 'backingup'
      ){
        clearOarComplete(x.RegionName);
      }

    });

  }
  catch(e){

    restoreRegionNames = [];
    restoreRegionSizes = {};

    console.error(
      'Unable to load restore region data:',
      e
    );

  }

}


function regionIsOnline(status){
  return ['booted','running','online'].includes(String(status || '').trim().toLowerCase());
}

function regionIsOffline(status){
  return ['stopped','offline','shutdown'].includes(String(status || '').trim().toLowerCase());
}

function regionIsBusy(status){
  return ['booting','starting','recyclingdown','restart','restarting','stopping','backingup','backup','savingoar','saving'].includes(String(status || '').trim().toLowerCase());
}

function statusLabel(status){
  const s = String(status || '').trim();
  if(!s) return 'UNKNOWN';
  if(s.toLowerCase() === 'booted') return 'ONLINE';
  if(s.toLowerCase() === 'stopped') return 'OFFLINE';
  if(s.toLowerCase() === 'backingup') return 'BACKING UP';
  if(s.toLowerCase() === 'savingoar') return 'BACKING UP';
  return s.toUpperCase();
}

let dgModalResolve=null;
let dgModalSelectedValue=null;

function dgModalNodes(){
  return {
    root:document.getElementById('dg-modal'),
    title:document.getElementById('dg-modal-title'),
    body:document.getElementById('dg-modal-body'),
    options:document.getElementById('dg-modal-options'),
    confirm:document.getElementById('dg-modal-confirm'),
    cancel:document.getElementById('dg-modal-cancel'),
    x:document.getElementById('dg-modal-x')
  };
}

function closeDgModal(result){
  const n=dgModalNodes();
  if(!n.root) return;
  n.root.hidden=true;
  n.root.setAttribute('aria-hidden','true');
  document.body.classList.remove('modal-open');
  const resolve=dgModalResolve;
  dgModalResolve=null;
  dgModalSelectedValue=null;
  if(resolve) resolve(result);
}

function openDgModal({title='CONFIRM ACTION',body='',confirmText='CONTINUE',danger=false,options=null,selectedValue=null}={}){
  const n=dgModalNodes();
  if(!n.root) return Promise.resolve(false);

  dgModalSelectedValue=selectedValue;
  n.title.textContent=title;
  n.body.innerHTML=String(body||'');
  n.confirm.textContent=confirmText;
  n.confirm.classList.toggle('danger',!!danger);
  n.confirm.classList.toggle('gold',!danger);

  const hasOptions=Array.isArray(options) && options.length>0;
  n.options.hidden=!hasOptions;
  n.options.innerHTML='';

  if(hasOptions){
    options.forEach((opt,i)=>{
      const value=String(opt.value ?? '');
      const item=document.createElement('button');
      item.type='button';
      item.className='dg-modal-option';
      item.dataset.value=value;
      item.innerHTML=`
        <span class="dg-modal-option-title">${esc(opt.title ?? value)}</span>
        ${opt.meta ? `<span class="dg-modal-option-meta">${esc(opt.meta)}</span>` : ''}
      `;
      if(String(selectedValue ?? '')===value) item.classList.add('selected');
      item.addEventListener('click',()=>{
        dgModalSelectedValue=value;
        n.options.querySelectorAll('.dg-modal-option').forEach(x=>x.classList.remove('selected'));
        item.classList.add('selected');
        n.confirm.disabled=false;
      });
      n.options.appendChild(item);
    });
    n.confirm.disabled=dgModalSelectedValue===null || dgModalSelectedValue===undefined || dgModalSelectedValue==='';
  }else{
    n.confirm.disabled=false;
  }

  n.root.hidden=false;
  n.root.setAttribute('aria-hidden','false');
  document.body.classList.add('modal-open');
  setTimeout(()=>{ if(hasOptions){ n.options.querySelector('.dg-modal-option')?.focus(); } else n.confirm.focus(); },0);

  return new Promise(resolve=>{ dgModalResolve=resolve; });
}

async function uiConfirm(title,body,confirmText='CONTINUE',danger=false){
  const result=await openDgModal({title,body,confirmText,danger});
  return result===true;
}

async function uiChoice(title,body,options,confirmText='SELECT'){
  const result=await openDgModal({title,body,confirmText,options});
  return typeof result==='string' ? result : null;
}

document.addEventListener('DOMContentLoaded',()=>{
  const n=dgModalNodes();
  if(!n.root) return;
  n.cancel?.addEventListener('click',()=>closeDgModal(false));
  n.x?.addEventListener('click',()=>closeDgModal(false));
  n.root.querySelectorAll('[data-modal-cancel]').forEach(x=>x.addEventListener('click',()=>closeDgModal(false)));
  n.confirm?.addEventListener('click',()=>{
    if(!n.options.hidden){
      if(dgModalSelectedValue===null || dgModalSelectedValue===undefined || dgModalSelectedValue==='') return;
      closeDgModal(String(dgModalSelectedValue));
    }else{
      closeDgModal(true);
    }
  });
  document.addEventListener('keydown',e=>{
    if(n.root.hidden) return;
    if(e.key==='Escape'){e.preventDefault();closeDgModal(false);}
    if(e.key==='Enter' && !n.confirm.disabled){e.preventDefault();n.confirm.click();}
  });
});

function esc(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#39;'
  }[m]));
}
function attr(s){ return esc(s); }


document.addEventListener('click', async e => {

  const b =
    e.target.closest('button[data-command]');

  if(!b){
    return;
  }

  e.preventDefault();
  e.stopPropagation();

  const command =
    String(b.dataset.command || '').trim();

  if(!command){
    return;
  }

  b.disabled = true;

  msg(`${command}…`);

  try{

    const body =
      new URLSearchParams({
        command
      });

    const r =
      await fetch(
        '/Other/command.php',
        {
          method:'POST',
          headers:{
            'Content-Type':
              'application/x-www-form-urlencoded'
          },
          body,
          credentials:'same-origin',
          cache:'no-store'
        }
      );

    const raw =
      await r.text();

    let d;

    try{

      d =
        JSON.parse(raw);

    }
    catch(parseError){

      throw new Error(
        `Server returned: ${raw || '(empty response)'}`
      );

    }

    if(d.ok){

      if(
        command === 'SaveIAR' &&
        d.iar
      ){

        msg(
          `IAR BACKUP STARTED — ${DG.avatar}\n` +
          `File: ${d.iar.name}\n` +
          `Monitoring inventory archive…`
        );

        setIarPanelStatus(
          `PROCESSING — ${d.iar.name} — starting…`,
          'processing'
        );

        monitorIAR(
          d.iar,
          'self'
        );

      }
      else{

        msg(
          d.message ||
          'Command completed.'
        );

      }

    }
    else{

      msg(
        d.error ||
        'Command failed.',
        true
      );

    }

  }
  catch(err){

    msg(
      err.message,
      true
    );

  }
  finally{

    b.disabled = false;

  }

});


const adminSaveIar = document.getElementById('admin-save-iar');
if(adminSaveIar){
  adminSaveIar.addEventListener('click', async e => {
    e.preventDefault();

    const select = document.getElementById('admin-iar-avatar');
    const target = select ? String(select.value || '').trim() : '';

    if(!target){
      setIarStatusTarget('admin', 'SELECT A LOCAL AVATAR FIRST', 'error');
      return;
    }

    adminSaveIar.disabled = true;
    if(select) select.disabled = true;

    setIarStatusTarget('admin', `PROCESSING — ${target} — starting…`, 'processing');
    msg(`AdminSaveIAR — ${target}…`);

    try{
      const body = new URLSearchParams({
        command: 'AdminSaveIAR',
        avatarname: target
      });

      const r = await fetch('/Other/command.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body,
        credentials:'same-origin',
        cache:'no-store'
      });

      const raw = await r.text();
      let d;
      try { d = JSON.parse(raw); }
      catch(e2){ throw new Error(`Server returned: ${raw || '(empty response)'}`); }

      if(!d.ok) throw new Error(d.error || 'User IAR command failed');
      if(!d.iar) throw new Error('Server did not return IAR job information');

      msg(`IAR BACKUP STARTED — ${target}\nFile: ${d.iar.name}\nMonitoring inventory archive…`);
      setIarStatusTarget('admin', `PROCESSING — ${target} — ${d.iar.name}`, 'processing');
      monitorIAR(d.iar, 'admin');
    }catch(err){
      msg(err.message, true);
      setIarStatusTarget('admin', `BACKUP ERROR — ${err.message}`, 'error');
      adminSaveIar.disabled = false;
      if(select) select.disabled = false;
    }
  });
}

document.getElementById('overview-refresh')?.addEventListener('click',async e=>{
  e.preventDefault();

  // Clear the previous command result when manually refreshing the overview.
  sessionStorage.removeItem(RESULT_KEY);
  result.textContent='Refreshing live grid status...';
  result.classList.remove('bad');

  await Promise.all([
    loadGridOverview(),
    loadBackupHealth(),
    loadBackupStorage(),
    loadBackupSchedule()
  ]);

  result.textContent='Live grid status refreshed.';
  result.classList.remove('bad');
});
document.getElementById('alerts-refresh')?.addEventListener('click',async e=>{
  e.preventDefault();

  sessionStorage.removeItem(RESULT_KEY);
  result.textContent='Checking live grid alerts...';
  result.classList.remove('bad');

  await loadGridAlerts();

  result.textContent='Live grid alerts refreshed.';
  result.classList.remove('bad');
});
document.getElementById('health-refresh')?.addEventListener('click',async e=>{
  e.preventDefault();

  sessionStorage.removeItem(RESULT_KEY);
  result.textContent='Refreshing backup health...';
  result.classList.remove('bad');

  await loadBackupHealth();

  result.textContent='Backup health refreshed.';
  result.classList.remove('bad');
});

['history-search','history-type','history-status','history-source'].forEach(id=>{
  const el=document.getElementById(id);
  if(el) el.addEventListener(id==='history-search'?'input':'change',renderBackupHistory);
});
document.getElementById('history-refresh')?.addEventListener('click',async e=>{
  e.preventDefault();

  sessionStorage.removeItem(RESULT_KEY);
  result.textContent='Refreshing backup history...';
  result.classList.remove('bad');

  await loadBackupHistory();

  result.textContent='Backup history refreshed.';
  result.classList.remove('bad');
});
document.getElementById('history-clear')?.addEventListener('click',e=>{e.preventDefault();clearBackupHistory();});

['backup-search','backup-filter','backup-sort'].forEach(id => {
  const el = document.getElementById(id);
  if(el) el.addEventListener(id === 'backup-search' ? 'input' : 'change', renderBackups);
});

const selectOldBackupsButton=document.getElementById('backup-select-old');
if(selectOldBackupsButton){
  selectOldBackupsButton.addEventListener('click',e=>{
    e.preventDefault();
    if(restoreBusy){ alert('A restore is active. Wait for it to finish before selecting backups for cleanup.'); return; }
    selectOldBackups();
  });
}
const selectVisibleBackups=document.getElementById('backup-select-visible');
if(selectVisibleBackups){
  selectVisibleBackups.addEventListener('click',e=>{
    e.preventDefault();
    currentVisibleBackups().forEach(b=>{ const key=backupSelectionKey(b); if(key) selectedBackups.set(key,b); });
    renderBackups();
  });
}
const clearSelectedBackups=document.getElementById('backup-clear-selected');
if(clearSelectedBackups){
  clearSelectedBackups.addEventListener('click',e=>{ e.preventDefault(); selectedBackups.clear(); renderBackups(); });
}
const deleteSelectedBackups=document.getElementById('backup-delete-selected');
if(deleteSelectedBackups){
  deleteSelectedBackups.addEventListener('click',async e=>{
    e.preventDefault();
    if(restoreBusy){ alert('A restore is active. Wait for it to finish before deleting backups.'); return; }
    const chosen=Array.from(selectedBackups.values());
    if(!chosen.length) return;
    const total=chosen.reduce((n,b)=>n+Number(b.size||0),0);
    const names=chosen.map((b,i)=>`${i+1}. ${b.name}`).join('\n');
    if(!(await uiConfirm(
      `DELETE ${chosen.length} SELECTED BACKUP${chosen.length===1?'':'S'}`,
      `<div class="dg-modal-warning"><strong>Total space to free: ${esc(storageSize(total))}</strong>` +
      `<div class="dg-modal-list">${chosen.map((b,i)=>`<div><span>${i+1}</span><strong>${esc(b.name)}</strong></div>`).join('')}</div>` +
      `<span>This permanently removes these backup files from the server. This cannot be undone.</span></div>`,
      'DELETE SELECTED',
      true
    ))) return;
    deleteSelectedBackups.disabled=true;
    deleteSelectedBackups.textContent='DELETING…';
    try{
      const items=chosen.map(b=>b.delete).filter(Boolean);
      const body=new URLSearchParams(); body.set('items',JSON.stringify(items));
      const r=await fetch('/Other/bulk-delete-backups.php',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body});
      const raw=await r.text(); let d; try{d=JSON.parse(raw);}catch(err){throw new Error(raw||'Invalid cleanup response');}
      if(!d.ok) throw new Error(d.error || 'Cleanup failed');
      selectedBackups.clear();
      setResult(`BACKUP CLEANUP COMPLETE\nDeleted: ${Number(d.count)||0} file${Number(d.count)===1?'':'s'}\nSpace freed: ${storageSize(d.bytesFreed)}`,'ok');
      await loadBackups(); await loadBackupStorage();
    }catch(err){
      setResult(`BACKUP CLEANUP ERROR\n${err.message}`,'error');
      await loadBackups(); await loadBackupStorage();
    }finally{
      deleteSelectedBackups.textContent='DELETE SELECTED'; updateCleanupSummary();
    }
  });
}

const scheduleType = document.getElementById('schedule-type');
if(scheduleType){
  scheduleType.addEventListener('change',()=>{
    const type=scheduleType.value;
    if(type==='weekly'){
      const checked=Array.from(document.querySelectorAll('#schedule-days input[type="checkbox"]:checked'));
      if(checked.length!==1){
        document.querySelectorAll('#schedule-days input[type="checkbox"]').forEach(x=>x.checked=false);
        const mon=document.querySelector('#schedule-days input[value="1"]');
        if(mon) mon.checked=true;
      }
    }
    updateScheduleDayVisibility();
  });
}

const scheduleSelectAll=document.getElementById('schedule-select-all');
if(scheduleSelectAll){
  scheduleSelectAll.addEventListener('click',e=>{
    e.preventDefault();
    gridBackupRegions.filter(gridBackupRegionOnline).forEach(r=>scheduleSelectedRegions.add(String(r.RegionName||'')));
    renderScheduleRegions();
  });
}

const scheduleClear=document.getElementById('schedule-clear');
if(scheduleClear){
  scheduleClear.addEventListener('click',e=>{
    e.preventDefault();
    scheduleSelectedRegions.clear();
    renderScheduleRegions();
  });
}

const scheduleSave=document.getElementById('schedule-save');
if(scheduleSave){
  scheduleSave.addEventListener('click',e=>{
    e.preventDefault();
    saveBackupSchedule();
  });
}

const scheduleRunNow=document.getElementById('schedule-run-now');
if(scheduleRunNow){
  scheduleRunNow.addEventListener('click',e=>{
    e.preventDefault();
    runScheduleNow();
  });
}

const scheduleRefresh=document.getElementById('schedule-refresh');
if(scheduleRefresh){
  scheduleRefresh.addEventListener('click',e=>{
    e.preventDefault();
    loadGridBackupRegions().then(loadBackupSchedule);
  });
}

const gridBackupSelectAll = document.getElementById('grid-backup-select-all');
if(gridBackupSelectAll){
  gridBackupSelectAll.addEventListener('click',e=>{
    e.preventDefault();
    if(gridBackupRunning) return;
    gridBackupRegions.filter(gridBackupRegionOnline).forEach(r=>gridBackupSelected.add(String(r.RegionName || '')));
    renderGridBackupRegions();
  });
}

const gridBackupClear = document.getElementById('grid-backup-clear');
if(gridBackupClear){
  gridBackupClear.addEventListener('click',e=>{
    e.preventDefault();
    if(gridBackupRunning) return;
    gridBackupSelected.clear();
    renderGridBackupRegions();
  });
}

const gridBackupStart = document.getElementById('grid-backup-start');
if(gridBackupStart){
  gridBackupStart.addEventListener('click',e=>{
    e.preventDefault();
    runGridBackup();
  });
}

const gridBackupRefresh = document.getElementById('grid-backup-refresh');
if(gridBackupRefresh){
  gridBackupRefresh.addEventListener('click',async e=>{
    e.preventDefault();

    sessionStorage.removeItem(RESULT_KEY);
    result.textContent='Refreshing region backup list...';
    result.classList.remove('bad');

    await loadGridBackupRegions();

    result.textContent='Region backup list refreshed.';
    result.classList.remove('bad');
  });
}

const refreshBackupStorage = document.getElementById('refresh-backup-storage');
if(refreshBackupStorage){
  refreshBackupStorage.addEventListener('click', async e => {
    e.preventDefault();

    sessionStorage.removeItem(RESULT_KEY);
    result.textContent='Refreshing backup storage...';
    result.classList.remove('bad');

    await loadBackupStorage();

    result.textContent='Backup storage refreshed.';
    result.classList.remove('bad');
  });
}

const clearRestoreHistory = document.getElementById('clear-restore-history');
if(clearRestoreHistory){
  clearRestoreHistory.addEventListener('click', async e => {
    e.preventDefault();

    if(restoreBusy){
      alert('A restore is currently active. Wait for it to finish before clearing restore history.');
      return;
    }

    const confirmed = await uiConfirm(
      'CLEAR RESTORE HISTORY',
      `<div class="dg-modal-warning">
        <strong>Restore history log only</strong>
        <span>This clears the displayed OAR and IAR restore history.</span>
        <span>No OAR or IAR backup files will be deleted.</span>
        <span>This cannot be undone.</span>
      </div>`,
      'CLEAR RESTORE HISTORY',
      true
    );

    if(!confirmed) return;

    try{
      const body = new URLSearchParams();
      body.set('action','clear');

      const r = await fetch('/Other/restore-history.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
        body,
        credentials:'same-origin',
        cache:'no-store'
      });

      const raw = await r.text();
      let d;

      try{
        d=JSON.parse(raw);
      }catch(err){
        throw new Error(raw || 'Invalid clear restore history response');
      }

      if(!d.ok){
        throw new Error(d.error || 'Unable to clear restore history');
      }

      await loadRestoreHistory();

      sessionStorage.removeItem(RESULT_KEY);
      result.textContent =
        `RESTORE HISTORY CLEARED\n` +
        `History records removed: ${Number(d.deleted || 0)}\n` +
        `No backup files were deleted.`;

      result.classList.remove('bad');

    }catch(err){
      setResult(
        `RESTORE HISTORY CLEAR ERROR\n${err.message}`,
        'error'
      );
    }
  });
}
const refreshRestoreHistory = document.getElementById('refresh-restore-history');
if(refreshRestoreHistory){
  refreshRestoreHistory.addEventListener('click', async e => {
    e.preventDefault();

    sessionStorage.removeItem(RESULT_KEY);
    result.textContent='Refreshing restore history...';
    result.classList.remove('bad');

    await loadRestoreHistory();

    result.textContent='Restore history refreshed.';
    result.classList.remove('bad');
  });
}

const refreshBackups = document.getElementById('refresh-backups');
if(refreshBackups){
  refreshBackups.addEventListener('click', async e => {
    e.preventDefault();

    sessionStorage.removeItem(RESULT_KEY);
    result.textContent='Refreshing backup files...';
    result.classList.remove('bad');

    await loadBackups();

    result.textContent='Backup files refreshed.';
    result.classList.remove('bad');
  });
}

restoreResult();
loadRestoreRegionData();
loadGridBackupRegions().then(loadBackupSchedule);
loadLocalAvatars();
loadBackups();
loadGridOverview();
loadGridAlerts();
startGridAlertWatch();
loadBackupHealth();
loadBackupHistory();
loadRestoreHistory();
loadBackupStorage();

