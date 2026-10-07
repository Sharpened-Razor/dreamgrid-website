(function(){
    'use strict';
    if (new URLSearchParams(window.location.search).get('import') !== '1') return;
    const form = document.getElementById('createRegionForm');
    const create = document.getElementById('createRegionButton');
    const csrf = document.getElementById('createRegionCsrf');
    if (!form || !create || !csrf) return;

    const panel = document.createElement('section');
    panel.className = 'panel';
    panel.style.marginBottom = '18px';
    const heading = document.createElement('h2');
    heading.textContent = 'Import a Region INI';
    const help = document.createElement('p');
    help.textContent = 'Preview one Region INI, choose a local owner, then review the fields below. Other INI settings are preserved. A new estate and disabled, stopped region will be created; existing regions are never overwritten.';
    const label = document.createElement('label');
    label.textContent = 'Region INI file';
    label.htmlFor = 'regionIniImportFile';
    const file = document.createElement('input');
    file.type = 'file';
    file.id = 'regionIniImportFile';
    file.accept = '.ini,text/plain';
    const preview = document.createElement('button');
    preview.type = 'button';
    preview.className = 'button';
    preview.textContent = 'PREVIEW INI';
    const status = document.createElement('p');
    status.setAttribute('role', 'status');
    status.textContent = 'Choose a UTF-8 Region INI file up to 256 KB.';
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = 'region_ini_import_token';
    const source = document.createElement('details');
    const summary = document.createElement('summary');
    summary.textContent = 'Source INI';
    const text = document.createElement('pre');
    text.style.maxHeight = '240px';
    text.style.overflow = 'auto';
    source.append(summary, text);
    panel.append(heading, help, label, file, preview, status, token, source);
    form.prepend(panel);
    create.disabled = true;
    let revision = 0;
    file.addEventListener('change', function(){
        revision++;
        token.value = '';
        create.disabled = true;
        status.textContent = 'Preview this file before creating the region.';
    });
    form.addEventListener('submit', function(event){
        if (!token.value) {
            event.preventDefault();
            event.stopImmediatePropagation();
            status.textContent = 'Preview a valid Region INI first.';
        }
    }, true);
    preview.addEventListener('click', async function(){
        const selectedFile = file.files[0];
        token.value = '';
        create.disabled = true;
        if (!selectedFile || selectedFile.size > 262144) {
            status.textContent = 'Choose a Region INI file up to 256 KB.';
            return;
        }
        if (!csrf.value) {
            status.textContent = 'Wait for the local owner list to load, then preview the file.';
            return;
        }
        const currentRevision = ++revision;
        preview.disabled = true;
        status.textContent = 'Validating INI settings...';
        try {
            const body = new FormData();
            body.set('csrf_token', csrf.value);
            body.set('region_ini', selectedFile);
            const response = await fetch('/Other/admin-region-import.php', {method:'POST', credentials:'same-origin', body:body});
            const data = await response.json();
            if (currentRevision !== revision) return;
            if (!response.ok || !data || data.ok !== true) throw new Error(data && data.error || 'The INI preview failed.');
            const sourceText = await selectedFile.text();
            if (currentRevision !== revision) return;
            Object.entries(data.fields).forEach(function(entry){
                const field = form.elements.namedItem(entry[0]);
                if (!field) return;
                if (field.type === 'checkbox') field.checked = entry[1] === true;
                else field.value = String(entry[1]);
            });
            const estate = document.getElementById('estateName');
            if (estate) estate.dataset.manual = '1';
            ['enabled', 'smartStart'].forEach(function(name){
                const field = form.elements.namedItem(name);
                if (field) field.disabled = true;
            });
            text.textContent = sourceText;
            token.value = data.token;
            create.disabled = false;
            status.textContent = data.settings + ' INI settings staged. Choose a local owner and review the region fields. Core fields override the source values; Enabled, SmartStart and SmartBoot are saved as False. The server checks for duplicate names, UUIDs, ports and overlapping locations before creating anything.';
        } catch (error) {
            if (currentRevision === revision) status.textContent = error instanceof Error ? error.message : 'The INI preview failed.';
        } finally {
            preview.disabled = false;
        }
    });
})();