(function(){

'use strict';


let regions = [];
let healthData = {};
let scheduleData = {};
let storageData = {};

let controlSelected = new Set();
let controlBusy = false;

let confirmResolve = null;


/* ============================================================
   BASICS
   ============================================================ */

function byId(id){
    return document.getElementById(id);
}


function esc(value){

    return String(
        value === null ||
        value === undefined
        ? ''
        : value
    )
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}


function firstValue(object,names,fallback){

    if(
        !object ||
        typeof object !== 'object'
    ){
        return fallback;
    }

    for(const name of names){

        if(
            object[name] !== undefined &&
            object[name] !== null &&
            object[name] !== ''
        ){
            return object[name];
        }
    }

    return fallback;
}


function deepFind(value,names){

    const wanted =
        names.map(
            name =>
                String(name).toLowerCase()
        );

    function walk(item){

        if(
            !item ||
            typeof item !== 'object'
        ){
            return undefined;
        }

        for(const [key,val] of Object.entries(item)){

            if(
                wanted.includes(
                    String(key).toLowerCase()
                )
            ){
                return val;
            }
        }

        for(const val of Object.values(item)){

            if(
                val &&
                typeof val === 'object'
            ){
                const found =
                    walk(val);

                if(found !== undefined){
                    return found;
                }
            }
        }

        return undefined;
    }

    return walk(value);
}


async function getJson(url){

    const response =
        await fetch(
            url,
            {
                credentials:'same-origin',
                cache:'no-store'
            }
        );

    const raw =
        await response.text();

    let data;

    try{
        data = JSON.parse(raw);
    }
    catch(error){

        throw new Error(
            raw ||
            'Invalid server response.'
        );
    }

    if(
        !response.ok ||
        (
            data &&
            data.ok === false
        )
    ){
        throw new Error(
            data.error ||
            data.message ||
            'Request failed.'
        );
    }

    return data;
}


async function postCommand(
    commandName,
    regionNameValue
){

    const body =
        new URLSearchParams();

    body.set(
        'command',
        commandName
    );

    if(regionNameValue){

        body.set(
            'region',
            regionNameValue
        );
    }

    const response =
        await fetch(
            '/Other/command.php',
            {
                method:'POST',

                credentials:'same-origin',

                headers:{
                    'Content-Type':
                        'application/x-www-form-urlencoded;charset=UTF-8'
                },

                body:body
            }
        );

    const raw =
        await response.text();

    let data;

    try{
        data = JSON.parse(raw);
    }
    catch(error){

        throw new Error(
            raw ||
            'Invalid command response.'
        );
    }

    if(
        !response.ok ||
        data.ok === false
    ){
        throw new Error(
            data.error ||
            data.message ||
            'Command failed.'
        );
    }

    return data;
}


function listFrom(data){

    if(Array.isArray(data)){
        return data;
    }

    if(
        !data ||
        typeof data !== 'object'
    ){
        return [];
    }

    for(const key of [
        'regions',
        'rows',
        'data'
    ]){

        if(Array.isArray(data[key])){
            return data[key];
        }
    }

    return [];
}


function regionName(region){

    return String(
        firstValue(
            region,
            [
                'RegionName',
                'regionName',
                'name',
                'Name'
            ],
            'Unknown Region'
        )
    );
}


function regionOwner(region){

    return String(
        firstValue(
            region,
            [
                'EstateOwner',
                'estateOwner',
                'OwnerName',
                'owner',
                'Owner'
            ],
            '-'
        )
    );
}


function regionStatus(region){

    return String(
        firstValue(
            region,
            [
                'Status',
                'status',
                'RegionStatus',
                'state'
            ],
            'Unknown'
        )
    );
}


function avatarCount(region){

    return String(
        firstValue(
            region,
            [
                'Users',
                'users',
                'avatars',
                'AvatarCount'
            ],
            '0'
        )
    );
}


function primCount(region){

    return String(
        firstValue(
            region,
            [
                'Prims',
                'prims',
                'primCount',
                'Primitives'
            ],
            '0'
        )
    );
}


function running(region){

    const status =
        regionStatus(region)
        .toLowerCase();

    return (
        status.includes('boot') ||
        status.includes('run') ||
        status.includes('online')
    );
}


function bytes(value){

    const number =
        Number(value);

    if(
        !Number.isFinite(number) ||
        number < 0
    ){
        return '-';
    }

    const units =
        ['B','KB','MB','GB','TB'];

    let size =
        number;

    let index =
        0;

    while(
        size >= 1024 &&
        index < units.length - 1
    ){
        size /= 1024;
        index++;
    }

    return (
        (
            size >= 100 ||
            index === 0
        )
        ? size.toFixed(0)
        : size.toFixed(1)
    ) +
    ' ' +
    units[index];
}


function statusState(region){

    if(running(region)){
        return 'good';
    }

    const text =
        regionStatus(region)
        .toLowerCase();

    if(
        text.includes('stop') ||
        text.includes('offline') ||
        text.includes('down')
    ){
        return 'bad';
    }

    return 'warn';
}


function statusPill(text,state){

    return (
        '<span class="agcp-status-pill ' +
        esc(state || '') +
        '">' +
        esc(text) +
        '</span>'
    );
}


function detailRow(label,value){

    return (
        '<div class="agcp-detail-row">' +

            '<span>' +
                esc(label) +
            '</span>' +

            '<strong>' +
                esc(
                    value === undefined ||
                    value === null ||
                    value === ''
                    ? '-'
                    : value
                ) +
            '</strong>' +

        '</div>'
    );
}


/* ============================================================
   CLEAN CONFIRM
   ============================================================ */

function closeConfirm(result){

    const modal =
        byId('agcp-confirm-modal');

    modal.classList.remove('open');

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    if(confirmResolve){

        const resolve =
            confirmResolve;

        confirmResolve =
            null;

        resolve(
            !!result
        );
    }
}


function confirmAction(
    title,
    message,
    detail
){

    byId(
        'agcp-confirm-title'
    ).textContent =
        title;

    byId(
        'agcp-confirm-message'
    ).textContent =
        message;

    byId(
        'agcp-confirm-detail'
    ).textContent =
        detail || '';

    const modal =
        byId('agcp-confirm-modal');

    modal.classList.add('open');

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    return new Promise(
        resolve => {

            confirmResolve =
                resolve;
        }
    );
}


/* ============================================================
   COMMON DATA
   ============================================================ */

async function loadCoreData(){

    const stamp =
        Date.now();

    const results =
        await Promise.allSettled([

            getJson(
                '/Other/regions.php?nocache=' +
                stamp
            ),

            getJson(
                '/Other/backup-health.php?nocache=' +
                stamp
            ),

            getJson(
                '/Other/backup-schedule.php?nocache=' +
                stamp
            ),

            getJson(
                '/Other/backup-storage.php?nocache=' +
                stamp
            )
        ]);

    if(
        results[0].status !==
        'fulfilled'
    ){
        throw results[0].reason;
    }

    regions =
        listFrom(
            results[0].value
        );

    healthData =
        results[1].status ===
        'fulfilled'
        ? results[1].value
        : {};

    scheduleData =
        results[2].status ===
        'fulfilled'
        ? results[2].value
        : {};

    storageData =
        results[3].status ===
        'fulfilled'
        ? results[3].value
        : {};
}


/* ============================================================
   GRID VIEW
   ============================================================ */

function setGridMessage(text,state){

    const box =
        byId('agcp-grid-message');

    box.className =
        'agcp-message' +
        (
            state
            ? ' ' + state
            : ''
        );

    box.textContent =
        text;
}


function renderGridViewRegions(){

    const target =
        byId('agcp-region-list');

    if(!regions.length){

        target.innerHTML =
            '<div class="agcp-empty">' +
            'No regions returned.' +
            '</div>';

        return;
    }

    target.innerHTML =
        regions.map(
            function(region){

                return (
                    '<div class="agcp-region-row">' +

                        '<div class="agcp-region-name">' +
                            esc(regionName(region)) +
                        '</div>' +

                        '<div>' +
                            statusPill(
                                regionStatus(region),
                                statusState(region)
                            ) +
                        '</div>' +

                        '<div class="agcp-region-owner">' +
                            esc(regionOwner(region)) +
                        '</div>' +

                        '<div class="agcp-region-number">' +
                            'AV ' +
                            esc(avatarCount(region)) +
                        '</div>' +

                        '<div class="agcp-region-number">' +
                            'PRIMS ' +
                            esc(primCount(region)) +
                        '</div>' +

                    '</div>'
                );
            }
        ).join('');
}


function renderGridView(){

    const runningCount =
        regions.filter(running).length;

    byId(
        'agcp-total-regions'
    ).textContent =
        regions.length;

    byId(
        'agcp-running-regions'
    ).textContent =
        runningCount;

    byId(
        'agcp-stopped-regions'
    ).textContent =
        Math.max(
            0,
            regions.length - runningCount
        );

    renderGridViewRegions();

    const overall =
        String(
            deepFind(
                healthData,
                [
                    'overall',
                    'health',
                    'status'
                ]
            )
            ||
            'UNKNOWN'
        ).toUpperCase();

    byId(
        'agcp-backup-health'
    ).textContent =
        overall;

    const gridState =
        byId('agcp-grid-state');

    let state =
        'warn';

    if(
        overall.includes('HEALTH') ||
        overall.includes('GOOD') ||
        overall.includes('OK')
    ){
        state = 'good';
    }

    if(
        overall.includes('FAIL') ||
        overall.includes('ERROR')
    ){
        state = 'bad';
    }

    gridState.textContent =
        overall;

    gridState.className =
        'agcp-status-pill ' +
        state;

    const config =
        scheduleData &&
        scheduleData.config &&
        typeof scheduleData.config === 'object'
        ? scheduleData.config
        : {};

    const backupFiles =
        healthData &&
        healthData.backupFiles &&
        typeof healthData.backupFiles === 'object'
        ? healthData.backupFiles
        : {};

    const storage =
        healthData &&
        healthData.storage &&
        typeof healthData.storage === 'object'
        ? healthData.storage
        : {};

    byId(
        'agcp-backup-summary'
    ).innerHTML =

        detailRow(
            'SCHEDULER',
            config.enabled
            ? 'ENABLED'
            : 'DISABLED'
        ) +

        detailRow(
            'SCHEDULE TYPE',
            config.type || '-'
        ) +

        detailRow(
            'NEXT RUN',
            scheduleData.nextRun || '-'
        ) +

        detailRow(
            'OAR FILES',
            backupFiles.oar ?? 0
        ) +

        detailRow(
            'IAR FILES',
            backupFiles.iar ?? 0
        ) +

        detailRow(
            'STORAGE FREE',
            storage.freeFormatted ||
            storage.freeHuman ||
            (
                storage.free !== undefined
                ? bytes(storage.free)
                : '-'
            )
        );
}


async function loadGridView(){

    setGridMessage(
        'Refreshing Grid View...',
        ''
    );

    try{

        await loadCoreData();

        renderGridView();

        setGridMessage(
            'Grid View loaded.',
            'good'
        );

    }catch(error){

        setGridMessage(
            error.message ||
            'Grid View failed.',
            'bad'
        );
    }
}


/* ============================================================
   GRID CONTROL
   ============================================================ */

function setControlMessage(text,state){

    const box =
        byId('agcp-control-message');

    box.className =
        'agcp-message' +
        (
            state
            ? ' ' + state
            : ''
        );

    box.textContent =
        text;
}


function updateControlButtons(){

    const selected =
        controlSelected.size;

    byId(
        'agcp-control-selected'
    ).textContent =
        selected;

    for(const id of [
        'agcp-control-start',
        'agcp-control-stop',
        'agcp-control-restart',
        'agcp-control-freeze',
        'agcp-control-thaw'
    ]){

        byId(id).disabled =
            controlBusy ||
            selected === 0;
    }

    byId(
        'agcp-control-select-all'
    ).disabled =
        controlBusy;

    byId(
        'agcp-control-clear'
    ).disabled =
        controlBusy;

    byId(
        'agcp-control-refresh'
    ).disabled =
        controlBusy;

    byId(
        'agcp-control-restart-all'
    ).disabled =
        controlBusy;

    const state =
        byId('agcp-control-state');

    state.textContent =
        controlBusy
        ? 'WORKING'
        : 'READY';

    state.className =
        'agcp-status-pill ' +
        (
            controlBusy
            ? 'warn'
            : 'good'
        );
}


function renderGridControl(){

    const valid =
        new Set(
            regions.map(regionName)
        );

    controlSelected =
        new Set(
            Array.from(controlSelected)
            .filter(
                name =>
                    valid.has(name)
            )
        );

    const runningCount =
        regions.filter(running).length;

    byId(
        'agcp-control-total'
    ).textContent =
        regions.length;

    byId(
        'agcp-control-running'
    ).textContent =
        runningCount;

    byId(
        'agcp-control-other'
    ).textContent =
        Math.max(
            0,
            regions.length - runningCount
        );

    const target =
        byId('agcp-control-list');

    if(!regions.length){

        target.innerHTML =
            '<div class="agcp-empty">' +
            'No regions returned.' +
            '</div>';

        updateControlButtons();

        return;
    }

    target.innerHTML =
        regions.map(
            function(region){

                const name =
                    regionName(region);

                const selected =
                    controlSelected.has(name);

                return (
                    '<label class="agcp-control-row ' +
                    (
                        selected
                        ? 'selected'
                        : ''
                    ) +
                    '">' +

                        '<input ' +
                            'type="checkbox" ' +
                            'class="agcp-check agcp-control-check" ' +
                            'data-region="' +
                            esc(name) +
                            '"' +
                            (
                                selected
                                ? ' checked'
                                : ''
                            ) +
                        '>' +

                        '<div class="agcp-region-name">' +
                            esc(name) +
                        '</div>' +

                        '<div>' +
                            statusPill(
                                regionStatus(region),
                                statusState(region)
                            ) +
                        '</div>' +

                        '<div class="agcp-control-owner">' +
                            esc(regionOwner(region)) +
                        '</div>' +

                        '<div class="agcp-control-number">' +
                            esc(avatarCount(region)) +
                        '</div>' +

                        '<div class="agcp-control-number">' +
                            esc(primCount(region)) +
                        '</div>' +

                    '</label>'
                );
            }
        ).join('');

    target
        .querySelectorAll(
            '.agcp-control-check'
        )
        .forEach(
            function(box){

                box.addEventListener(
                    'change',
                    function(){

                        const name =
                            box.dataset.region;

                        if(box.checked){

                            controlSelected.add(
                                name
                            );

                        }else{

                            controlSelected.delete(
                                name
                            );
                        }

                        renderGridControl();
                    }
                );
            }
        );

    updateControlButtons();
}


async function loadGridControl(){

    setControlMessage(
        'Refreshing region status...',
        ''
    );

    try{

        const data =
            await getJson(
                '/Other/regions.php?nocache=' +
                Date.now()
            );

        regions =
            listFrom(data);

        renderGridControl();

        setControlMessage(
            'Grid Control ready.',
            'good'
        );

    }catch(error){

        setControlMessage(
            error.message ||
            'Unable to load regions.',
            'bad'
        );
    }
}


async function runSelectedCommand(
    commandName,
    title
){

    if(controlBusy){
        return;
    }

    const names =
        Array.from(controlSelected);

    if(!names.length){
        return;
    }

    const confirmed =
        await confirmAction(
            title,
            title + ' for ' +
            names.length +
            (
                names.length === 1
                ? ' region?'
                : ' regions?'
            ),
            names.join('\n')
        );

    if(!confirmed){
        return;
    }

    controlBusy =
        true;

    updateControlButtons();

    const failed =
        [];

    let completed =
        0;

    for(const name of names){

        setControlMessage(
            commandName +
            ': ' +
            name +
            '...',
            ''
        );

        try{

            await postCommand(
                commandName,
                name
            );

            completed++;

        }catch(error){

            failed.push(
                name +
                ': ' +
                error.message
            );
        }
    }

    controlBusy =
        false;

    updateControlButtons();

    let message =
        'Completed: ' +
        completed;

    if(failed.length){

        message +=
            '\n\nFAILED:\n' +
            failed.join('\n');
    }

    setControlMessage(
        message,
        failed.length
        ? 'bad'
        : 'good'
    );

    setTimeout(
        loadGridControl,
        2000
    );
}


async function restartAllRegions(){

    if(controlBusy){
        return;
    }

    const confirmed =
        await confirmAction(
            'Restart All Regions',
            'Restart every region on the grid?',
            'This sends the DreamGrid RestartAll command.'
        );

    if(!confirmed){
        return;
    }

    controlBusy =
        true;

    updateControlButtons();

    setControlMessage(
        'Restart All requested...',
        ''
    );

    try{

        const data =
            await postCommand(
                'RestartAll',
                ''
            );

        setControlMessage(
            data.message ||
            'Restart All accepted.',
            'good'
        );

    }catch(error){

        setControlMessage(
            error.message ||
            'Restart All failed.',
            'bad'
        );

    }finally{

        controlBusy =
            false;

        updateControlButtons();

        setTimeout(
            loadGridControl,
            4000
        );
    }
}


/* ============================================================
   GRID ALERTS
   ============================================================ */

function schedulerEnabled(){

    const config =
        scheduleData &&
        scheduleData.config &&
        typeof scheduleData.config === 'object'
        ? scheduleData.config
        : scheduleData;

    const value =
        firstValue(
            config,
            [
                'enabled',
                'active',
                'scheduleEnabled'
            ],
            false
        );

    return (
        value === true ||
        value === 1 ||
        String(value) === '1' ||
        String(value).toLowerCase() === 'true'
    );
}


function healthErrors(){

    const value =
        Number(
            deepFind(
                healthData,
                [
                    'errors',
                    'errorCount'
                ]
            )
            ||
            0
        );

    return Number.isFinite(value)
        ? value
        : 0;
}


function storageSummary(){

    if(
        storageData &&
        storageData.summary &&
        typeof storageData.summary === 'object'
    ){
        return storageData.summary;
    }

    return {};
}


function buildAlerts(){

    const alerts =
        [];

    regions.forEach(
        function(region){

            if(!running(region)){

                alerts.push({
                    severity:'critical',
                    source:'REGION',
                    title:
                        regionName(region) +
                        ' is not running',
                    detail:
                        'Current status: ' +
                        regionStatus(region)
                });
            }
        }
    );

    const errors =
        healthErrors();

    if(errors > 0){

        alerts.push({
            severity:'critical',
            source:'BACKUP',
            title:
                errors +
                ' backup error(s) reported',
            detail:
                'Backup Health reports active errors.'
        });
    }

    const overall =
        String(
            deepFind(
                healthData,
                [
                    'overall',
                    'health',
                    'status'
                ]
            )
            ||
            ''
        ).toLowerCase();

    const healthText =
        JSON.stringify(
            healthData || {}
        ).toLowerCase();

    const schedulerOnlyWarning =
        errors === 0 &&
        (
            healthText.includes(
                'scheduler disabled'
            ) ||
            healthText.includes(
                'schedule disabled'
            )
        );

    if(
        errors === 0 &&
        !schedulerOnlyWarning &&
        (
            overall.includes('warning') ||
            overall.includes('unhealthy')
        )
    ){

        alerts.push({
            severity:'warning',
            source:'BACKUP',
            title:'Backup health warning',
            detail:
                'Backup Health reports ' +
                (
                    overall
                    ? overall.toUpperCase()
                    : 'WARNING'
                ) +
                '.'
        });
    }

    const summary =
        storageSummary();

    const free =
        Number(
            summary.driveFreeBytes
        );

    const total =
        Number(
            summary.driveTotalBytes
        );

    if(
        Number.isFinite(free) &&
        Number.isFinite(total) &&
        total > 0
    ){

        const percent =
            free /
            total *
            100;

        if(percent < 10){

            alerts.push({
                severity:'critical',
                source:'STORAGE',
                title:'Backup drive space critically low',
                detail:
                    percent.toFixed(1) +
                    '% free (' +
                    bytes(free) +
                    ').'
            });

        }else if(percent < 20){

            alerts.push({
                severity:'warning',
                source:'STORAGE',
                title:'Backup drive space is getting low',
                detail:
                    percent.toFixed(1) +
                    '% free (' +
                    bytes(free) +
                    ').'
            });
        }
    }

    return alerts;
}


function setWatch(
    id,
    copyId,
    value,
    copy,
    state
){

    const target =
        byId(id);

    target.textContent =
        value;

    target.className =
        'agcp-watch-value ' +
        (
            state || ''
        );

    byId(
        copyId
    ).textContent =
        copy;
}


function renderGridAlerts(){

    const alerts =
        buildAlerts();

    const critical =
        alerts.filter(
            item =>
                item.severity ===
                'critical'
        ).length;

    const warnings =
        alerts.filter(
            item =>
                item.severity ===
                'warning'
        ).length;

    const runningCount =
        regions.filter(running).length;

    byId(
        'agcp-alerts-critical'
    ).textContent =
        critical;

    byId(
        'agcp-alerts-warning'
    ).textContent =
        warnings;

    byId(
        'agcp-alerts-regions'
    ).textContent =
        runningCount +
        ' / ' +
        regions.length;

    byId(
        'agcp-alerts-last-check'
    ).textContent =
        new Date()
        .toLocaleTimeString(
            [],
            {
                hour:'2-digit',
                minute:'2-digit',
                second:'2-digit'
            }
        );

    const state =
        byId('agcp-alerts-state');

    if(critical > 0){

        state.textContent =
            'ATTENTION REQUIRED';

        state.className =
            'agcp-status-pill bad';

    }else if(warnings > 0){

        state.textContent =
            'WARNING';

        state.className =
            'agcp-status-pill warn';

    }else{

        state.textContent =
            'MONITORING NORMAL';

        state.className =
            'agcp-status-pill good';
    }

    byId(
        'agcp-alerts-active'
    ).textContent =
        alerts.length +
        ' ACTIVE';


    const regionGood =
        regions.length > 0 &&
        runningCount === regions.length;

    setWatch(
        'agcp-watch-regions',
        'agcp-watch-regions-copy',
        runningCount +
            ' / ' +
            regions.length +
            ' ONLINE',
        regionGood
            ? 'All configured regions are running'
            : (
                regions.length -
                runningCount
              ) +
              ' region(s) require attention',
        regionGood
            ? 'good'
            : 'bad'
    );


    const errors =
        healthErrors();

    const overall =
        String(
            deepFind(
                healthData,
                [
                    'overall',
                    'health',
                    'status'
                ]
            )
            ||
            'UNKNOWN'
        ).toUpperCase();

    setWatch(
        'agcp-watch-health',
        'agcp-watch-health-copy',
        errors > 0
            ? 'ERROR'
            : overall,
        errors > 0
            ? errors +
              ' backup error(s) reported'
            : 'Current backup health status',
        errors > 0
            ? 'bad'
            :
            (
                overall.includes('HEALTH') ||
                overall.includes('GOOD') ||
                overall.includes('OK')
                ? 'good'
                : 'warn'
            )
    );


    const scheduled =
        schedulerEnabled();

    setWatch(
        'agcp-watch-scheduler',
        'agcp-watch-scheduler-copy',
        scheduled
            ? 'ENABLED'
            : 'DISABLED',
        scheduled
            ? 'Automatic backup schedule is active'
            : 'Scheduled backups are configured off',
        scheduled
            ? 'good'
            : 'warn'
    );


    const summary =
        storageSummary();

    const free =
        Number(
            summary.driveFreeBytes
        );

    const total =
        Number(
            summary.driveTotalBytes
        );

    if(
        Number.isFinite(free) &&
        free >= 0
    ){

        let storageState =
            'good';

        let copy =
            'Backup storage is available';

        if(
            Number.isFinite(total) &&
            total > 0
        ){

            const percent =
                free /
                total *
                100;

            copy =
                percent.toFixed(1) +
                '% free of ' +
                bytes(total);

            if(percent < 10){
                storageState = 'bad';
            }
            else if(percent < 20){
                storageState = 'warn';
            }
        }

        setWatch(
            'agcp-watch-storage',
            'agcp-watch-storage-copy',
            bytes(free) +
                ' FREE',
            copy,
            storageState
        );

    }else{

        setWatch(
            'agcp-watch-storage',
            'agcp-watch-storage-copy',
            'NOT REPORTED',
            'Storage free-space value is unavailable',
            'warn'
        );
    }


    setWatch(
        'agcp-watch-errors',
        'agcp-watch-errors-copy',
        String(errors),
        errors > 0
            ? errors +
              ' backup error(s) reported'
            : 'No backup errors reported',
        errors > 0
            ? 'bad'
            : 'good'
    );


    const list =
        byId('agcp-alert-list');

    if(!alerts.length){

        list.innerHTML =
            '<div class="agcp-clear-state">' +
            'NO ACTIVE ALERTS' +
            '</div>';

        return;
    }

    list.innerHTML =
        alerts.map(
            function(alert){

                return (
                    '<div class="agcp-alert ' +
                    esc(alert.severity) +
                    '">' +

                        '<div class="agcp-alert-level">' +
                            esc(
                                alert.severity.toUpperCase()
                            ) +
                        '</div>' +

                        '<div>' +

                            '<div class="agcp-alert-title">' +
                                esc(
                                    alert.source +
                                    ' - ' +
                                    alert.title
                                ) +
                            '</div>' +

                            '<div class="agcp-alert-detail">' +
                                esc(alert.detail) +
                            '</div>' +

                        '</div>' +

                    '</div>'
                );
            }
        ).join('');
}


async function loadGridAlerts(){

    const state =
        byId('agcp-alerts-state');

    state.textContent =
        'CHECKING';

    state.className =
        'agcp-status-pill warn';

    try{

        await loadCoreData();

        renderGridAlerts();

    }catch(error){

        state.textContent =
            'REFRESH ERROR';

        state.className =
            'agcp-status-pill bad';

        byId(
            'agcp-alert-list'
        ).innerHTML =
            '<div class="agcp-alert critical">' +

                '<div class="agcp-alert-level">' +
                    'ERROR' +
                '</div>' +

                '<div>' +
                    '<div class="agcp-alert-title">' +
                        'Monitoring refresh failed' +
                    '</div>' +

                    '<div class="agcp-alert-detail">' +
                        esc(
                            error.message ||
                            'Unknown monitoring error.'
                        ) +
                    '</div>' +
                '</div>' +

            '</div>';
    }
}



window.AGCPConfirm =
    confirmAction;

/* ============================================================
   NAVIGATION
   ============================================================ */

function openPage(name){

    document
        .querySelectorAll(
            '.agcp-page'
        )
        .forEach(
            page =>
                page.classList.remove(
                    'active'
                )
        );

    document
        .querySelectorAll(
            '.agcp-nav'
        )
        .forEach(
            button =>
                button.classList.remove(
                    'active'
                )
        );

    const page =
        byId(
            'agcp-page-' +
            name
        );

    const button =
        Array.from(
            document.querySelectorAll(
                '.agcp-nav'
            )
        )
        .find(
            item =>
                item.dataset.page ===
                name
        );

    if(page){
        page.classList.add('active');
    }

    if(button){
        button.classList.add('active');
    }

    try{

        sessionStorage.setItem(
            'australia-fresh-control-page',
            name
        );

    }catch(error){
    }

    if(name === 'grid-view'){
        loadGridView();
    }

    if(name === 'grid-control'){
        loadGridControl();
    }

    if(name === 'grid-alerts'){
        loadGridAlerts();
    }
}


/* ============================================================
   EVENTS
   ============================================================ */

document
    .querySelectorAll(
        '.agcp-nav'
    )
    .forEach(
        button =>

            button.addEventListener(
                'click',
                function(){

                    openPage(
                        this.dataset.page
                    );
                }
            )
    );


byId(
    'agcp-grid-refresh'
).addEventListener(
    'click',
    loadGridView
);


byId(
    'agcp-control-refresh'
).addEventListener(
    'click',
    loadGridControl
);


byId(
    'agcp-control-select-all'
).addEventListener(
    'click',
    function(){

        controlSelected =
            new Set(
                regions
                .map(regionName)
                .filter(Boolean)
            );

        renderGridControl();
    }
);


byId(
    'agcp-control-clear'
).addEventListener(
    'click',
    function(){

        controlSelected.clear();

        renderGridControl();
    }
);


byId(
    'agcp-control-start'
).addEventListener(
    'click',
    function(){

        runSelectedCommand(
            'StartRegion',
            'Start Selected Regions'
        );
    }
);


byId(
    'agcp-control-stop'
).addEventListener(
    'click',
    function(){

        runSelectedCommand(
            'StopRegion',
            'Stop Selected Regions'
        );
    }
);


byId(
    'agcp-control-restart'
).addEventListener(
    'click',
    function(){

        runSelectedCommand(
            'RestartRegion',
            'Restart Selected Regions'
        );
    }
);


byId(
    'agcp-control-freeze'
).addEventListener(
    'click',
    function(){

        runSelectedCommand(
            'Freeze',
            'Freeze Selected Regions'
        );
    }
);


byId(
    'agcp-control-thaw'
).addEventListener(
    'click',
    function(){

        runSelectedCommand(
            'Thaw',
            'Thaw Selected Regions'
        );
    }
);


byId(
    'agcp-control-restart-all'
).addEventListener(
    'click',
    restartAllRegions
);


byId(
    'agcp-alerts-refresh'
).addEventListener(
    'click',
    loadGridAlerts
);


byId(
    'agcp-confirm-ok'
).addEventListener(
    'click',
    function(){
        closeConfirm(true);
    }
);


byId(
    'agcp-confirm-cancel'
).addEventListener(
    'click',
    function(){
        closeConfirm(false);
    }
);


byId(
    'agcp-confirm-close'
).addEventListener(
    'click',
    function(){
        closeConfirm(false);
    }
);


byId(
    'agcp-confirm-modal'
)
.querySelector(
    '.agcp-modal-shade'
)
.addEventListener(
    'click',
    function(){
        closeConfirm(false);
    }
);


document.addEventListener(
    'keydown',
    function(event){

        const modal =
            byId('agcp-confirm-modal');

        if(
            !modal.classList.contains(
                'open'
            )
        ){
            return;
        }

        if(event.key === 'Escape'){

            event.preventDefault();

            closeConfirm(false);
        }

        if(event.key === 'Enter'){

            event.preventDefault();

            closeConfirm(true);
        }
    }
);


/* ============================================================
   ALERT AUTO REFRESH
   ============================================================ */

setInterval(
    function(){

        const page =
            byId(
                'agcp-page-grid-alerts'
            );

        if(
            page &&
            page.classList.contains(
                'active'
            )
        ){
            loadGridAlerts();
        }

    },
    60000
);


/* ============================================================
   START
   ============================================================ */

let startPage =
    'grid-view';

try{

    const saved =
        sessionStorage.getItem(
            'australia-fresh-control-page'
        );

    if(
        saved &&
        byId(
            'agcp-page-' +
            saved
        )
    ){
        startPage =
            saved;
    }

}catch(error){
}


openPage(
    startPage
);


})();