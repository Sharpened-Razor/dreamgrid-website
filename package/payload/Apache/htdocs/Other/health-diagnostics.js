const result = document.getElementById('diag-result');

function esc(v){
  return String(v ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');
}

function bytes(n){
  if(n === null || n === undefined || !Number.isFinite(Number(n))) return 'Unavailable';

  n = Number(n);

  const units = ['B','KB','MB','GB','TB'];
  let i = 0;

  while(n >= 1024 && i < units.length - 1){
    n /= 1024;
    i++;
  }

  return `${n.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

function row(label,value,cls=''){
  const display = cls
    ? `<span class="diag-status-wrap"><span class="diag-status-dot ${cls}"></span><span>${esc(value)}</span></span>`
    : esc(value);

  return `
    <div class="diag-row">
      <div class="diag-label">${esc(label)}</div>
      <div class="diag-value">${display}</div>
    </div>
  `;
}

function goodBad(ok){
  return ok ? 'diag-ok' : 'diag-bad';
}

function healthClass(value){
  const s = String(value || '').toUpperCase();

  if(s === 'HEALTHY' || s === 'OK' || s === 'RUNNING' || s === 'COMPLETE'){
    return 'diag-ok';
  }

  if(s === 'WARNING' || s === 'DISABLED'){
    return 'diag-warn';
  }

  if(s === 'ERROR' || s === 'FAILED' || s === 'OFFLINE'){
    return 'diag-bad';
  }

  return '';
}

function dateText(value){
  if(!value) return 'None';

  const d = new Date(value);

  if(Number.isNaN(d.getTime())){
    return String(value);
  }

  return d.toLocaleString('en-AU');
}

async function fetchJson(url){
  const separator = url.includes('?') ? '&' : '?';

  const r = await fetch(
    `${url}${separator}nocache=${Date.now()}`,
    {
      credentials:'same-origin',
      cache:'no-store'
    }
  );

  const raw = await r.text();

  let d;

  try{
    d = JSON.parse(raw);
  }
  catch(e){
    throw new Error(raw || `Invalid response from ${url}`);
  }

  if(!d.ok){
    throw new Error(d.error || `Request failed: ${url}`);
  }

  return d;
}

function regionOnline(r){
  const value = String(
    r.RegionStatus ??
    r.status ??
    r.Status ??
    ''
  ).toLowerCase();

  if(
    value.includes('stopped') ||
    value.includes('offline') ||
    value.includes('shutdown') ||
    value.includes('crashed')
  ){
    return false;
  }

  return true;
}

async function refreshDiagnostics(){

  const btn = document.getElementById('diag-refresh');

  if(btn){
    btn.disabled = true;
  }

  result.textContent = 'Running read-only diagnostics...';

  try{

    const [
      sys,
      regionsData,
      healthData,
      storageData,
      scheduleData,
      restoreData
    ] = await Promise.all([

      fetchJson('/Other/health-diagnostics-api.php'),

      fetchJson('/Other/regions.php'),

      fetchJson('/Other/backup-health.php'),

      fetchJson('/Other/backup-storage.php'),

      fetchJson('/Other/backup-schedule.php'),

      fetchJson('/Other/restore-history.php')

    ]);

    // ==================================================
    // SYSTEM SERVICES
    // ==================================================

    const services = sys.services || {};

    let serviceHtml = '';

    const apacheState =
      String(services['Apache service'] ?? 'UNKNOWN');

    serviceHtml += row(
      'Apache service',
      apacheState,
      healthClass(apacheState)
    );

    serviceHtml += row(
      'Apache processes',
      services['httpd.exe processes'] ?? 'Unknown',
      Number(services['httpd.exe processes']) > 0
        ? 'diag-ok'
        : 'diag-warn'
    );

    serviceHtml += row(
      'MySQL processes',
      services['MySQL processes'] ?? 'Unknown',
      Number(services['MySQL processes']) > 0
        ? 'diag-ok'
        : 'diag-bad'
    );

    serviceHtml += row(
      'Robust processes',
      services['Robust processes'] ?? 'Unknown',
      Number(services['Robust processes']) > 0
        ? 'diag-ok'
        : 'diag-bad'
    );

    serviceHtml += row(
      'OpenSim processes',
      services['OpenSim processes'] ?? 'Unknown',
      Number(services['OpenSim processes']) > 0
        ? 'diag-ok'
        : 'diag-bad'
    );

    document.getElementById('diag-services').innerHTML = serviceHtml;


    // ==================================================
    // GRID STATUS
    // ==================================================

    const regions =
      Array.isArray(regionsData.regions)
        ? regionsData.regions
        : [];

    const online =
      regions.filter(regionOnline);

    const avatars =
      regions.reduce(
        (sum,r) =>
          sum +
          Number(
            r.Avatars ??
            r.avatars ??
            r.RootAgents ??
            r.Users ??
            0
          ),
        0
      );

    const overallHealth =
      String(
        healthData.overall || 'WARNING'
      ).toUpperCase();

    const config =
      scheduleData.config || {};

    const schedulerEnabled =
      !!config.enabled;

    const schedulerText =
      schedulerEnabled
        ? 'ENABLED'
        : 'DISABLED';

    const nextRun =
      schedulerEnabled
        ? dateText(scheduleData.nextRun)
        : 'Not scheduled';

    const runnerState =
      scheduleData.runner?.state || {};

    const lastBackup =
      runnerState.lastBackupRun
        ? dateText(runnerState.lastBackupRun)
        : 'None';

    const restoreActive =
      restoreData.active || null;

    let gridHtml = '';

    gridHtml += row(
      'Regions online',
      `${online.length} / ${regions.length}`,
      online.length === regions.length
        ? 'diag-ok'
        : 'diag-warn'
    );

    gridHtml += row(
      'Avatars online',
      avatars
    );

    gridHtml += row(
      'Backup health',
      overallHealth,
      healthClass(overallHealth)
    );

    gridHtml += row(
      'Automatic backups',
      schedulerText,
      schedulerEnabled
        ? 'diag-ok'
        : 'diag-warn'
    );

    gridHtml += row(
      'Next scheduled backup',
      nextRun
    );

    gridHtml += row(
      'Last scheduled backup',
      lastBackup
    );

    gridHtml += row(
      'Restore system',
      restoreActive
        ? `ACTIVE - ${String(restoreActive.type || '').toUpperCase()}`
        : 'IDLE',
      restoreActive
        ? 'diag-warn'
        : 'diag-ok'
    );

    gridHtml += row(
      'Diagnostics checked',
      dateText(sys.checkedAt)
    );

    document.getElementById('diag-grid').innerHTML = gridHtml;


    // ==================================================
    // NETWORK
    // ==================================================

    const network =
      sys.network || {};

    let netHtml = '';

    netHtml += row(
      'DNS host',
      network.host || ''
    );

    netHtml += row(
      'Resolved IP',
      network.resolvedIp || 'FAILED',
      network.dnsOk
        ? 'diag-ok'
        : 'diag-bad'
    );

    netHtml += row(
      `Port ${network.loginPort ?? 'not configured'} / Login`,
      network.login8002?.ok
        ? 'RESPONDING'
        : (
            network.login8002?.detail ||
            'FAILED'
          ),
      goodBad(
        !!network.login8002?.ok
      )
    );

    netHtml += row(
      `Port ${network.statusPort ?? 'not configured'} / Sim Status`,
      network.simstatus8013?.ok
        ? 'RESPONDING'
        : (
            network.simstatus8013?.detail ||
            'FAILED'
          ),
      goodBad(
        !!network.simstatus8013?.ok
      )
    );

    document.getElementById('diag-network').innerHTML =
      netHtml;


    // ==================================================
    // STORAGE
    // Use existing Backup Storage API.
    // NO recursive FSAssets scans.
    // ==================================================

    const summary =
      storageData.summary || {};

    const driveFree =
      Number(summary.driveFreeBytes);

    const driveTotal =
      Number(summary.driveTotalBytes);

    const driveUsed =
      Number.isFinite(driveFree) &&
      Number.isFinite(driveTotal)
        ? driveTotal - driveFree
        : NaN;

    const freePct =
      Number.isFinite(driveFree) &&
      Number.isFinite(driveTotal) &&
      driveTotal > 0
        ? (driveFree / driveTotal) * 100
        : NaN;

    let storageHtml = '';

    storageHtml += row(
      'All backup files',
      bytes(summary.backupBytes)
    );

    storageHtml += row(
      'OAR backups',
      `${bytes(summary.oarBytes)} - ${Number(summary.oarCount || 0)} files`
    );

    storageHtml += row(
      'IAR backups',
      `${bytes(summary.iarBytes)} - ${Number(summary.iarCount || 0)} files`
    );

    storageHtml += row(
      'Drive free',
      bytes(summary.driveFreeBytes),
      Number.isFinite(freePct) && freePct > 10
        ? 'diag-ok'
        : 'diag-warn'
    );

    storageHtml += row(
      'Drive used',
      bytes(driveUsed)
    );

    storageHtml += row(
      'Drive total',
      bytes(summary.driveTotalBytes)
    );

    if(Number.isFinite(freePct)){
      storageHtml += row(
        'Free space',
        `${freePct.toFixed(1)}%`,
        freePct > 10
          ? 'diag-ok'
          : 'diag-warn'
      );
    }

    document.getElementById('diag-storage').innerHTML =
      storageHtml;


    // ==================================================
    // RECENT IMPORTANT ERRORS
    // ==================================================

    const errors =
      Array.isArray(sys.recentErrors)
        ? sys.recentErrors
        : [];

    document.getElementById('diag-errors').innerHTML =
      errors.length
        ? `
          <ul class="diag-error-list">
            ${errors
              .map(x=>`<li>${esc(x)}</li>`)
              .join('')}
          </ul>
        `
        : `
          <div class="diag-ok">
            No recent important errors found.
          </div>
        `;


    // ==================================================
    // RESULT BAR
    // ==================================================

    const warningChecks =
      Array.isArray(healthData.checks)
        ? healthData.checks.filter(
            c =>
              String(c.level || '')
                .toUpperCase() === 'WARNING'
          ).length
        : 0;

    const errorChecks =
      Array.isArray(healthData.checks)
        ? healthData.checks.filter(
            c =>
              String(c.level || '')
                .toUpperCase() === 'ERROR'
          ).length
        : 0;

    result.textContent =
      `GRID HEALTH CHECK COMPLETE\n` +
      `Regions: ${online.length}/${regions.length}\n` +
      `Backup health: ${overallHealth}\n` +
      `Health warnings: ${warningChecks}\n` +
      `Health errors: ${errorChecks}\n` +
      `Automatic backups: ${schedulerText}\n` +
      `DNS: ${network.dnsOk ? 'OK' : 'FAILED'}\n` +
      `Login port ${network.loginPort ?? 'not configured'}: ${network.login8002?.ok ? 'OK' : 'FAILED'}\n` +
      `Diagnostics port ${network.statusPort ?? 'not configured'}: ${network.simstatus8013?.ok ? 'OK' : 'FAILED'}\n` +
      `Restore system: ${restoreActive ? 'ACTIVE' : 'IDLE'}`;

    const hasCriticalProblem =
      errorChecks > 0 ||
      !network.dnsOk ||
      !network.login8002?.ok ||
      !network.simstatus8013?.ok ||
      online.length < regions.length ||
      Number(services['MySQL processes'] || 0) < 1 ||
      Number(services['Robust processes'] || 0) < 1 ||
      Number(services['OpenSim processes'] || 0) < 1;

    const hasWarning =
      warningChecks > 0 ||
      !schedulerEnabled ||
      overallHealth === 'WARNING';

    result.classList.remove(
      'result-good',
      'result-warning',
      'result-error'
    );

    if(hasCriticalProblem){
      result.classList.add('result-error');
    }
    else if(hasWarning){
      result.classList.add('result-warning');
    }
    else{
      result.classList.add('result-good');
    }

  }
  catch(e){

    result.textContent =
      `DIAGNOSTICS ERROR\n${e.message}`;

  }
  finally{

    if(btn){
      btn.disabled = false;
    }

  }
}

document
  .getElementById('diag-refresh')
  ?.addEventListener(
    'click',
    e=>{
      e.preventDefault();
      refreshDiagnostics();
    }
  );

refreshDiagnostics();
