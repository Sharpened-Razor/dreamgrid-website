

(function(){

    "use strict";

    const table =
        document.getElementById(
            "dgRegionTable"
        );

    if (!table) return;
    let rows =
        Array.from(
            table.querySelectorAll(
                "tbody tr.dg-row"
            )
        );

    const backdrop =
        document.getElementById(
            "dgModalBackdrop"
        );

    const dialog =
        document.getElementById(
            "dgRegionDialog"
        );

    const dialogTitle =
        document.getElementById(
            "dgDialogTitle"
        );

    const closeButton =
        document.getElementById(
            "dgDialogClose"
        );

    const detailsButton =
        document.getElementById(
            "dgDetailsButton"
        );

    const startButton =
        document.getElementById(
            "dgStartRegionButton"
        );

    const stopButton =
        document.getElementById(
            "dgStopRegionButton"
        );

    const restartButton =
        document.getElementById(
            "dgRestartRegionButton"
        );

    const freezeButton =
        document.getElementById(
            "dgFreezeRegionButton"
        );

    const thawButton =
        document.getElementById(
            "dgThawRegionButton"
        );

    const viewLogButton =
        document.getElementById(
            "dgViewLogButton"
        );
    const teleportButton =
        document.getElementById(
            "dgTeleportButton"
        );
    const viewConsoleButton =
        document.getElementById(
            "dgViewConsoleButton"
        );
    const viewStatisticsButton =
        document.getElementById(
            "dgViewStatisticsButton"
        );
    const viewMapButton =
        document.getElementById(
            "dgViewMapButton"
        );
    const editRegionButton =
        document.getElementById(
            "dgEditRegionButton"
        );

    const saveOarButton = document.getElementById('dgSaveOarButton');

    const downloadOarButton =
        document.getElementById(
            "dgDownloadOarButton"
        );

    const dialogNote =
        document.getElementById(
            "dgDialogNote"
        );

    const selectAll = document.getElementById('dgSelectAll');
    const cards = Array.from(document.querySelectorAll('.dg-icon-region'));
    const identity = node => String(node.dataset.uuid || node.dataset.name || '').trim().toLowerCase();
    const rowIndex = new Map(rows.map(row => [identity(row), row]));
    const cardIndex = new Map(cards.map(card => [identity(card), card]));
    let selectedRow = rows[0] || null;
    let activeFilter = 'all';

    function rowFor(item){
        const row = item && rowIndex.get(identity(item));
        return row && row.isConnected ? row : null;
    }
    function syncSelection(){
        const visible = rows.filter(row => row.style.display !== 'none');
        const count = visible.filter(row => row.classList.contains('dg-selected')).length;
        selectAll.checked = visible.length > 0 && count === visible.length;
        selectAll.indeterminate = count > 0 && count < visible.length;
        for (const row of rows) {
            const card = cardIndex.get(identity(row));
            if (card) card.classList.toggle('dg-selected', row.classList.contains('dg-selected'));
        }
    }
    function selectRow(item){
        const row = rowFor(item);
        if (!row) return;
        selectedRow = row;
        rows.forEach(item => item.classList.toggle('dg-selected', item === row));
        syncSelection();
    }
    function applyFilter(){
        for (const row of rows) {
            const d = row.dataset;
            const visible = activeFilter === 'all' ||
                (activeFilter === 'enabled' && d.enabled === '1') ||
                (activeFilter === 'disabled' && d.enabled === '0') ||
                (activeFilter === 'smart' && d.smart === '1') ||
                (activeFilter === 'running' && d.state === 'running') ||
                (activeFilter === 'stopped' && d.state === 'stopped');
            row.style.display = visible ? '' : 'none';
            const card = cardIndex.get(identity(row));
            if (card) {
                Object.assign(card.dataset, d);
                // Shared button styling uses !important; filtered cards must stay hidden.
                card.style.setProperty('display', visible ? '' : 'none', visible ? '' : 'important');
            }
        }
        syncSelection();
    }
    function updateCounts(){
        const counts = {dgRegionCount:rows.length,dgEnabledCount:rows.filter(r=>r.dataset.enabled==='1').length,dgSmartCount:rows.filter(r=>r.dataset.smart==='1').length};
        for (const [id,count] of Object.entries(counts)) document.getElementById(id).textContent = String(count);
    }

    function openDialog(item){
        const row = rowFor(item);

        if (!row) {
            return;
        }

        selectRow(row);

        const name =
            row.dataset.name ||
            "Region";

        const status =
            row.dataset.status ||
            "";

        dialogTitle.textContent =
            name +
            (
                status
                    ? " " + status
                    : ""
            );

        const state =
            row.dataset.state ||
            "";

        startButton.disabled =
            state !== "stopped";

        stopButton.disabled =
            state !== "running";

        restartButton.disabled =
            state !== "running";

        viewStatisticsButton.disabled =
            state !== "running";

        viewConsoleButton.disabled =
            state !== "running";

        editRegionButton.disabled =
            false;

        saveOarButton.disabled = state !== 'running';

        downloadOarButton.disabled =
            false;

        const hop =
            row.dataset.hop ||
            "";

        teleportButton.disabled =
            state !== "running" ||
            !hop.toLowerCase().startsWith(
                "hop://"
            );

        const frozen =
            row.dataset.frozen ===
            "1";

        freezeButton.disabled =
            state !== "running" ||
            frozen;

        thawButton.disabled =
            state !== "running" ||
            !frozen;

        freezeButton
            .querySelector(".dg-control-label")
            .textContent =
                "Freeze";

        thawButton
            .querySelector(".dg-control-label")
            .textContent =
                "Thaw";

        startButton.querySelector(".dg-control-label").textContent =
            "Start";

        stopButton.querySelector(".dg-control-label").textContent =
            "Stop";

        restartButton.querySelector(".dg-control-label").textContent =
            "Restart";

        if (state === "stopped") {

            dialogNote.textContent =
                "Region is stopped. Start is available.";

        }
        else if (
            state === "running" &&
            frozen
        ) {

            dialogNote.textContent =
                "Region is frozen. Thaw is available.";

        }
        else if (state === "running") {

            dialogNote.textContent =
                "Region is running. Stop, Restart and Freeze are available.";

        }
        else if (state === "disabled") {

            dialogNote.textContent =
                "Region is disabled.";

        }
        else {

            dialogNote.textContent =
                "Region controls are not available for this state.";
        }

        backdrop.classList.add(
            "open"
        );

        dialog.classList.add(
            "open"
        );
    }

    function closeDialog(){

        backdrop.classList.remove(
            "open"
        );

        dialog.classList.remove(
            "open"
        );
    }

    const settingControls = table.querySelectorAll('.dg-enable-box, .dg-region-setting-control');
    for (const control of settingControls) {
        let saved = control.type === 'checkbox' ? control.checked : control.value;
        control.addEventListener('click', event => event.stopPropagation());
        control.addEventListener('change', async function(event){
            event.stopPropagation();
            const row = rowFor(control.closest('.dg-row'));
            if (!row || control.disabled) return;
            const enabled = control.classList.contains('dg-enable-box');
            const setting = enabled ? 'enabled' : control.dataset.setting;
            const next = control.type === 'checkbox' ? control.checked : control.value;
            const value = control.type === 'checkbox' ? (next ? '1' : '0') : next;
            const body = new URLSearchParams({region:row.dataset.name});
            if (enabled) body.set('enabled', value);
            else { body.set('uuid',row.dataset.uuid); body.set('setting',setting); body.set('value',value); }
            control.disabled = true;
            try {
                const response = await fetch(enabled ? '/Other/admin-region-enabled-action.php' : '/Other/admin-region-setting-action.php', {
                    method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:body.toString()
                });
                const result = await response.json();
                if (!response.ok || !result || result.ok !== true) throw new Error(result && (result.error || result.message) || 'The region setting could not be saved.');
                saved = next;
                if (enabled) row.dataset.enabled = value;
                if (setting === 'smart_mode') row.dataset.smart = value === 'off' ? '0' : '1';
                applyFilter();
                updateCounts();
            } catch(error) {
                if (control.type === 'checkbox') control.checked = saved;
                else control.value = saved;
                window.alert(error.message || 'The region setting could not be saved.');
            } finally { control.disabled = false; }
        });
    }

    rows.forEach(
        function(row){

            row.addEventListener(
                "click",
                function(event){
                    if (event.target.closest('input,select,button,a,textarea')) return;
                    selectRow(row);
                }
            );

            const nameCell =
                row.querySelector(
                    ".dg-region-name-cell"
                );

            if (nameCell) {

                nameCell.addEventListener(
                    "click",
                    function(event){

                        event.stopPropagation();

                        openDialog(row);
                    }
                );
            }

            row.addEventListener(
                "keydown",
                function(event){

                    if (
                        event.key === 'Enter' && event.target === row
                    ) {
                        event.preventDefault();
                        openDialog(row);
                    }
                }
            );
        }
    );

    if (selectedRow) {
        selectRow(
            selectedRow
        );
    }
    const iconsButton =
        document.getElementById(
            "dgIconsButton"
        );

    const usersButton =
        document.getElementById(
            "dgUsersButton"
        );

    const avatarsButton =
        document.getElementById(
            "dgAvatarsButton"
        );

    const visitorsButton =
        document.getElementById(
            "dgVisitorsButton"
        );

    const mapButton =
        document.getElementById(
            "dgMapButton"
        );

    const addRegionButton =
        document.getElementById(
            "dgAddRegionButton"
        );

    const importIniButton =
        document.getElementById(
            "dgImportIniButton"
        );

    const managerViews = {

        details:
            document.getElementById(
                "dgDetailsView"
            ),

        icons:
            document.getElementById(
                "dgIconsView"
            ),

        users:
            document.getElementById(
                "dgUsersView"
            ),

        avatars:
            document.getElementById(
                "dgAvatarsView"
            ),

        visitors:
            document.getElementById(
                "dgVisitorsView"
            )
    };

    const managerViewButtons = {

        details:
            detailsButton,

        icons:
            iconsButton,

        users:
            usersButton,

        avatars:
            avatarsButton,

        visitors:
            visitorsButton
    };

    function showManagerView(name){

        Object.keys(
            managerViews
        ).forEach(
            function(key){

                if (
                    managerViews[key]
                ) {

                    managerViews[key]
                        .classList.toggle(
                            "active",
                            key === name
                        );
                }

                if (
                    managerViewButtons[key]
                ) {

                    managerViewButtons[key]
                        .classList.toggle(
                            "dg-view-active",
                            key === name
                        );
                }
            }
        );

        try {

            sessionStorage.setItem(
                "dreamgrid-region-manager-view",
                name
            );

        }
        catch (error) {
        }
    }

    for (const [name,button] of Object.entries(managerViewButtons)) {
        if (button) button.addEventListener('click', () => showManagerView(name));
    }
    try {
        const savedView = sessionStorage.getItem('dreamgrid-region-manager-view');
        if (Object.hasOwn(managerViews, savedView)) showManagerView(savedView);
    } catch (_) { /* View memory is optional. */ }

    document
        .querySelectorAll(
            ".dg-icon-region"
        )
        .forEach(
            function(iconRegion){

                iconRegion.addEventListener(
                    "click",
                    function(){

                        openDialog(
                            iconRegion
                        );
                    }
                );
            }
        );

    mapButton.addEventListener('click', function(){
        closeDialog();
        window.dgOpenInternalPopup('/Other/admin-region-global-map.php', 'Global Map', {layout:'map'});
    });

    closeButton.addEventListener(
        "click",
        closeDialog
    );

    backdrop.addEventListener(
        "click",
        closeDialog
    );

    document.addEventListener(
        "keydown",
        function(event){

            if (
                event.key ===
                "Escape"
            ) {
                closeDialog();
            }
        }
    );

    for (const input of document.querySelectorAll('input[name="dg-filter"]')) {
        input.addEventListener('change', function(){ activeFilter = input.value; applyFilter(); });
    }
    selectAll.addEventListener('change', function(){
        rows.filter(row => row.style.display !== 'none').forEach(row => row.classList.toggle('dg-selected', selectAll.checked));
        syncSelection();
    });
    applyFilter();

    // Region actions share the existing popup and authenticated endpoints.

    // Region control actions use the existing authenticated DreamGrid bridges.
    let regionActionBusy = false;

    function selectedRegionName(){
        return selectedRow ? String(selectedRow.dataset.name || '').trim() : '';
    }

    async function regionControlRequest(button, endpoint, command){
        const region = selectedRegionName();
        if (regionActionBusy || !region || button.disabled) return;
        if (command && typeof window.auConfirm !== 'function') {
            dialogNote.textContent = 'The confirmation dialog is unavailable. Reload Region Manager.';
            return;
        }
        regionActionBusy = true;
        button.disabled = true;
        try {
            if (command && !(await window.auConfirm({
                title: 'REGION CONTROL',
                message: command.replace(/Region$/, '') + ' region "' + region + '"?',
                confirmText: 'CONTINUE', cancelText: 'CANCEL'
            }))) return;
            dialogNote.textContent = 'Sending request for ' + region + '...';
            const body = new URLSearchParams({region: region});
            if (command) body.set('command', command);
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: body.toString()
            });
            const data = await response.json();
            if (!response.ok || !data || data.ok !== true) {
                throw new Error(data && (data.error || data.message) || 'DreamGrid rejected the request.');
            }
            dialogNote.textContent = data.message || 'Request accepted by DreamGrid.';
            if (command) {
                // Re-read native state after DreamGrid has processed the request.
                window.setTimeout(function(){ window.location.reload(); }, 2500);
            }
        } catch (error) {
            dialogNote.textContent = error instanceof Error ? error.message : 'The request failed.';
        } finally {
            regionActionBusy = false;
            button.disabled = false;
        }
    }

    [
        [startButton, 'StartRegion'],
        [stopButton, 'StopRegion'],
        [restartButton, 'RestartRegion'],
        [freezeButton, 'Freeze'],
        [thawButton, 'Thaw'],
        [saveOarButton, 'SaveOAR']
    ].forEach(function(entry){
        if (entry[0]) entry[0].addEventListener('click', function(){
            return regionControlRequest(entry[0], '/Other/command.php', entry[1]);
        });
    });



    if (teleportButton) teleportButton.addEventListener('click', function(){
        const hop = selectedRow ? String(selectedRow.dataset.hop || '') : '';
        if (!teleportButton.disabled && /^hop:\/\//i.test(hop)) window.location.href = hop;
    });

    if (downloadOarButton) downloadOarButton.addEventListener('click', async function(){
        const region = selectedRegionName();
        if (!region || downloadOarButton.disabled) return;
        downloadOarButton.disabled = true;
        dialogNote.textContent = 'Finding the latest OAR for ' + region + '...';
        try {
            const response = await fetch('/Other/admin-region-oar-download.php?resolve=1&region=' + encodeURIComponent(region), {credentials: 'same-origin'});
            const data = await response.json();
            if (!response.ok || !data || data.ok !== true || !data.download) {
                throw new Error(data && (data.error || data.message) || 'No OAR backup is available.');
            }
            const url = new URL(data.download, window.location.href);
            if (url.origin !== window.location.origin || url.pathname !== '/Other/download-backup.php') {
                throw new Error('The backup download address is invalid.');
            }
            dialogNote.textContent = 'Downloading ' + data.name;
            window.location.assign(url.href);
        } catch (error) {
            dialogNote.textContent = error instanceof Error ? error.message : 'The backup could not be downloaded.';
        } finally {
            downloadOarButton.disabled = false;
        }
    });

    if (addRegionButton) addRegionButton.addEventListener('click', function(){
        if (typeof window.dgOpenInternalPopup === 'function') {
            window.dgOpenInternalPopup('/Other/admin-create-region.php', 'Create Region');
        }
    });

    const loadRegionOarButton = document.getElementById('dgLoadOarButton');
    if (loadRegionOarButton) loadRegionOarButton.addEventListener('click', function(){
        const region = selectedRegionName();
        if (!region) {
            dialogNote.textContent = 'Select a region first.';
            return;
        }
        if (typeof window.dgOpenInternalPopup === 'function') {
            window.dgOpenInternalPopup(
                '/Other/admin-region-global-map.php?task=load-oar&region=' + encodeURIComponent(region),
                'Load Region OAR — ' + region
            );
        }
    });

    if (importIniButton) importIniButton.addEventListener('click', function(){
        if (typeof window.dgOpenInternalPopup === 'function') {
            window.dgOpenInternalPopup('/Other/admin-create-region.php?import=1', 'Import Region INI');
        }
    });

    // Open region detail pages in the existing shared internal popup.
    [
        [viewMapButton, 'admin-region-map.php', 'Region Map'],
        [editRegionButton, 'admin-region-edit.php', 'Edit Region'],
        [viewStatisticsButton, 'admin-region-statistics.php', 'Region Statistics'],
        [viewConsoleButton, 'admin-region-console.php', 'Region Console'],
        [viewLogButton, 'admin-region-log.php', 'Region Log']
    ].forEach(function(entry){
        const button = entry[0];
        if (!button) return;

        button.addEventListener('click', function(){
            if (button.disabled) return;
            const regionName = selectedRow
                ? String(selectedRow.dataset.name || '').trim()
                : '';
            if (!regionName) {
                dialogNote.textContent = 'Select a region first.';
                return;
            }
            if (typeof window.dgOpenInternalPopup !== 'function') {
                dialogNote.textContent = 'The internal popup controller is unavailable.';
                return;
            }
            window.dgOpenInternalPopup(
                '/Other/' + entry[1] + '?region=' + encodeURIComponent(regionName),
                entry[2] + ' — ' + regionName
            );
        });
    });

    // Native deregistration/deletion still owns the operation; update both local views.
    window.addEventListener('message', function(event){
        if (event.origin !== window.location.origin || !event.data || event.data.type !== 'dreamgrid-region-removed-v2') return;
        const uuid = String(event.data.regionUuid || '').trim().toLowerCase();
        const name = String(event.data.regionName || '').trim().toLowerCase();
        const removed = rows.filter(row => uuid ? String(row.dataset.uuid).toLowerCase() === uuid : name && String(row.dataset.name).toLowerCase() === name);
        for (const row of removed) {
            const key = identity(row), card = cardIndex.get(key);
            if (selectedRow === row) { selectedRow = null; closeDialog(); }
            row.remove(); if (card) card.remove();
            rowIndex.delete(key); cardIndex.delete(key);
        }
        rows = rows.filter(row => row.isConnected);
        applyFilter(); updateCounts();
    });
})();



(function(){
    'use strict';
    const frame = document.querySelector('.dg-table-frame');
    if (!frame) return;
    const key = 'grid-region-manager-table-scroll';
    let pending = false;
    function save(){
        pending = false;
        try { sessionStorage.setItem(key, JSON.stringify({left:frame.scrollLeft,top:frame.scrollTop})); }
        catch (_) { /* Scroll memory is optional. */ }
    }
    frame.addEventListener('scroll', function(){
        if (pending) return;
        pending = true;
        requestAnimationFrame(save);
    }, {passive:true});
    window.addEventListener('beforeunload', save);
    try {
        const saved = JSON.parse(sessionStorage.getItem(key) || 'null');
        if (saved && Number.isFinite(saved.left) && Number.isFinite(saved.top)) {
            requestAnimationFrame(function(){ frame.scrollLeft = saved.left; frame.scrollTop = saved.top; });
        }
    } catch (_) { /* Ignore invalid or unavailable storage. */ }
})();
