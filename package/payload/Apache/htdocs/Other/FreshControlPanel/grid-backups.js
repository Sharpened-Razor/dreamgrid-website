(function(){

'use strict';

let backupRegions=[];
let backupMeta={
    summary:{},
    regions:[]
};

let backupSelected=new Set();
let backupBusy=false;

let backupCurrent='';
let backupDone=0;
let backupTotal=0;
let backupSuccess=0;
let backupFailed=0;

let backupSession=[];


function id(name){
    return document.getElementById(name);
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


function pick(object,names,fallback){

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


async function json(url,options){

    const response=
        await fetch(
            url,
            Object.assign(
                {
                    credentials:'same-origin',
                    cache:'no-store'
                },
                options || {}
            )
        );

    const raw=
        await response.text();

    let data;

    try{
        data=JSON.parse(raw);
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


async function command(
    commandName,
    region
){

    const body=
        new URLSearchParams();

    body.set(
        'command',
        commandName
    );

    if(region){

        body.set(
            'region',
            region
        );
    }

    return json(
        '/Other/command.php',
        {
            method:'POST',

            headers:{
                'Content-Type':
                    'application/x-www-form-urlencoded;charset=UTF-8'
            },

            body:body
        }
    );
}


function confirmBox(
    title,
    message,
    detail
){

    if(
        typeof window.AGCPConfirm ===
        'function'
    ){
        return window.AGCPConfirm(
            title,
            message,
            detail
        );
    }

    return Promise.resolve(
        window.confirm(
            message +
            (
                detail
                ? '\n\n' + detail
                : ''
            )
        )
    );
}


function nameOf(region){

    return String(
        pick(
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


function ownerOf(region){

    return String(
        pick(
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


function statusOf(region){

    return String(
        pick(
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


function primsOf(region){

    return Number(
        pick(
            region,
            [
                'Prims',
                'prims',
                'primCount',
                'Primitives'
            ],
            0
        )
    ) || 0;
}


function online(region){

    const text=
        statusOf(region)
        .toLowerCase();

    return (
        text.includes('boot') ||
        text.includes('run') ||
        text.includes('online')
    );
}


function normal(value){

    return String(value || '')
        .toLowerCase()
        .replace(
            /[^a-z0-9]+/g,
            ''
        );
}


function metaFor(name){

    if(
        !backupMeta ||
        !Array.isArray(backupMeta.regions)
    ){
        return null;
    }

    const target=
        normal(name);

    return (
        backupMeta.regions.find(
            item =>
                normal(item.region) ===
                target
        )
        ||
        null
    );
}


function bytes(value){

    const number=
        Number(value);

    if(
        !Number.isFinite(number) ||
        number < 0
    ){
        return '-';
    }

    const units=
        ['B','KB','MB','GB','TB'];

    let size=number;
    let unit=0;

    while(
        size >= 1024 &&
        unit < units.length - 1
    ){
        size /= 1024;
        unit++;
    }

    return (
        (
            size >= 100 ||
            unit === 0
        )
        ? size.toFixed(0)
        : size.toFixed(1)
    ) +
    ' ' +
    units[unit];
}


function newestTime(meta){

    if(
        !meta ||
        !meta.newest
    ){
        return 0;
    }

    const mtime=
        Number(
            meta.newest.mtime
        );

    if(
        Number.isFinite(mtime) &&
        mtime > 0
    ){
        return mtime;
    }

    const parsed=
        new Date(
            meta.newest.date || ''
        );

    if(
        Number.isNaN(
            parsed.getTime()
        )
    ){
        return 0;
    }

    return Math.floor(
        parsed.getTime() /
        1000
    );
}


function age(meta){

    const stamp=
        newestTime(meta);

    if(stamp <= 0){
        return '-';
    }

    const seconds=
        Math.max(
            0,
            Math.floor(
                Date.now() /
                1000
            ) -
            stamp
        );

    if(seconds < 60){
        return seconds + ' sec';
    }

    const minutes=
        Math.floor(
            seconds /
            60
        );

    if(minutes < 60){
        return minutes + ' min';
    }

    const hours=
        Math.floor(
            minutes /
            60
        );

    if(hours < 48){
        return hours + ' hr';
    }

    return (
        Math.floor(
            hours /
            24
        ) +
        ' days'
    );
}


function dateText(file){

    if(!file){
        return 'NEVER';
    }

    let date=null;

    const mtime=
        Number(file.mtime);

    if(
        Number.isFinite(mtime) &&
        mtime > 0
    ){
        date=
            new Date(
                mtime *
                1000
            );
    }
    else if(file.date){

        date=
            new Date(file.date);
    }

    if(
        !date ||
        Number.isNaN(
            date.getTime()
        )
    ){
        return String(
            file.date ||
            'UNKNOWN'
        );
    }

    return date.toLocaleString();
}


function pill(text,state){

    return (
        '<span class="agcp-status-pill ' +
        esc(state || '') +
        '">' +
        esc(text) +
        '</span>'
    );
}


function message(text,state){

    const box=
        id('agcp-backup-message');

    box.className=
        'agcp-message' +
        (
            state
            ? ' ' + state
            : ''
        );

    box.textContent=
        text;
}


function selectedCount(){

    id(
        'agcp-backup-selected-count'
    ).textContent=
        backupSelected.size;

    id(
        'agcp-backup-selected'
    ).disabled=
        backupBusy ||
        backupSelected.size === 0;
}


function renderSummary(){

    const total=
        backupRegions.length;

    let protectedCount=0;

    backupRegions.forEach(
        function(region){

            const meta=
                metaFor(
                    nameOf(region)
                );

            if(
                meta &&
                Number(meta.count || 0) > 0
            ){
                protectedCount++;
            }
        }
    );

    const summary=
        backupMeta.summary || {};

    id(
        'agcp-backup-regions'
    ).textContent=
        total;

    id(
        'agcp-backup-protected'
    ).textContent=
        protectedCount +
        ' / ' +
        total;

    id(
        'agcp-backup-missing'
    ).textContent=
        Math.max(
            0,
            total -
            protectedCount
        );

    id(
        'agcp-backup-oar-count'
    ).textContent=
        Number(
            summary.oarCount || 0
        ).toLocaleString();

    id(
        'agcp-backup-storage-used'
    ).textContent=
        bytes(
            summary.oarBytes
        );

    id(
        'agcp-backup-drive-free'
    ).textContent=
        bytes(
            summary.driveFreeBytes
        );

    id(
        'agcp-backup-newest'
    ).textContent=
        summary.newest
        ?
        (
            (
                summary.newest.region
                ? summary.newest.region + ' - '
                : ''
            ) +
            dateText(
                summary.newest
            )
        )
        :
        'NO OARS';

    selectedCount();
}


function filteredRegions(){

    const search=
        String(
            id(
                'agcp-backup-search'
            ).value || ''
        )
        .trim()
        .toLowerCase();

    const filter=
        id(
            'agcp-backup-filter'
        ).value;

    const sort=
        id(
            'agcp-backup-sort'
        ).value;

    let source=
        backupRegions.slice();

    if(search){

        source=
            source.filter(
                function(region){

                    return (
                        nameOf(region) +
                        ' ' +
                        ownerOf(region)
                    )
                    .toLowerCase()
                    .includes(search);
                }
            );
    }

    if(filter === 'online'){

        source=
            source.filter(online);
    }

    if(filter === 'missing'){

        source=
            source.filter(
                function(region){

                    const meta=
                        metaFor(
                            nameOf(region)
                        );

                    return (
                        !meta ||
                        Number(
                            meta.count || 0
                        ) === 0
                    );
                }
            );
    }

    if(filter === 'selected'){

        source=
            source.filter(
                region =>
                    backupSelected.has(
                        nameOf(region)
                    )
            );
    }

    source.sort(
        function(a,b){

            const aName=
                nameOf(a);

            const bName=
                nameOf(b);

            const aMeta=
                metaFor(aName);

            const bMeta=
                metaFor(bName);

            if(sort === 'last'){

                return (
                    newestTime(bMeta) -
                    newestTime(aMeta)
                );
            }

            if(sort === 'count'){

                return (
                    Number(
                        bMeta
                        ? bMeta.count || 0
                        : 0
                    )
                    -
                    Number(
                        aMeta
                        ? aMeta.count || 0
                        : 0
                    )
                );
            }

            if(sort === 'size'){

                return (
                    Number(
                        bMeta &&
                        bMeta.newest
                        ? bMeta.newest.bytes || 0
                        : 0
                    )
                    -
                    Number(
                        aMeta &&
                        aMeta.newest
                        ? aMeta.newest.bytes || 0
                        : 0
                    )
                );
            }

            return aName.localeCompare(
                bName,
                undefined,
                {
                    numeric:true,
                    sensitivity:'base'
                }
            );
        }
    );

    return source;
}


function renderRows(){

    renderSummary();

    const source=
        filteredRegions();

    const target=
        id('agcp-backup-list');

    if(!source.length){

        target.innerHTML=
            '<div class="agcp-empty">' +
            'No regions match the current filter.' +
            '</div>';

        return;
    }

    target.innerHTML=
        source.map(
            function(region){

                const name=
                    nameOf(region);

                const meta=
                    metaFor(name);

                const selected=
                    backupSelected.has(name);

                const newest=
                    meta &&
                    meta.newest
                    ? meta.newest
                    : null;

                const count=
                    meta
                    ? Number(
                        meta.count || 0
                    )
                    : 0;

                const state=
                    region.__backupState ||
                    '';

                let rowClass=
                    'agcp-backup-row';

                if(selected){
                    rowClass +=
                        ' selected';
                }

                if(state){
                    rowClass +=
                        ' ' +
                        state;
                }

                return (
                    '<div class="' +
                    rowClass +
                    '">' +

                        '<input ' +
                            'type="checkbox" ' +
                            'class="agcp-check agcp-backup-check" ' +
                            'data-region="' +
                            esc(name) +
                            '"' +
                            (
                                selected
                                ? ' checked'
                                : ''
                            ) +
                        '>' +

                        '<div>' +
                            '<div class="agcp-backup-name">' +
                                esc(name) +
                            '</div>' +

                            '<div class="agcp-backup-owner">' +
                                esc(
                                    ownerOf(region)
                                ) +
                            '</div>' +
                        '</div>' +

                        '<div>' +
                            pill(
                                statusOf(region),
                                online(region)
                                ? 'good'
                                : 'warn'
                            ) +
                        '</div>' +

                        '<div class="agcp-backup-data">' +
                            esc(
                                newest
                                ? dateText(newest)
                                : 'NEVER'
                            ) +
                        '</div>' +

                        '<div class="agcp-backup-data">' +
                            esc(
                                age(meta)
                            ) +
                        '</div>' +

                        '<div class="agcp-backup-data">' +
                            esc(count) +
                        '</div>' +

                        '<div class="agcp-backup-data">' +
                            esc(
                                newest
                                ? bytes(
                                    newest.bytes
                                )
                                : '-'
                            ) +
                        '</div>' +

                        '<div class="agcp-backup-data">' +
                            esc(
                                primsOf(region)
                            ) +
                        '</div>' +

                        '<div class="agcp-backup-row-actions">' +

                            '<button ' +
                                'type="button" ' +
                                'class="agcp-backup-mini gold" ' +
                                'data-agcp-backup-now="' +
                                esc(name) +
                                '"' +
                                (
                                    backupBusy
                                    ? ' disabled'
                                    : ''
                                ) +
                            '>' +
                                'BACKUP NOW' +
                            '</button>' +

                            '<button ' +
                                'type="button" ' +
                                'class="agcp-backup-mini" ' +
                                'data-agcp-backup-files="' +
                                esc(name) +
                                '">' +
                                'FILES' +
                            '</button>' +

                        '</div>' +

                    '</div>'
                );
            }
        ).join('');

    target
        .querySelectorAll(
            '.agcp-backup-check'
        )
        .forEach(
            function(box){

                box.addEventListener(
                    'change',
                    function(){

                        const name=
                            box.dataset.region;

                        if(box.checked){

                            backupSelected.add(
                                name
                            );

                        }else{

                            backupSelected.delete(
                                name
                            );
                        }

                        renderRows();
                    }
                );
            }
        );
}


function progress(){

    let percent=0;

    if(backupTotal > 0){

        percent=
            Math.round(
                (
                    backupDone /
                    backupTotal
                ) *
                100
            );
    }

    id(
        'agcp-backup-progress-bar'
    ).style.width=
        percent + '%';

    id(
        'agcp-backup-progress-percent'
    ).textContent=
        percent + '%';

    id(
        'agcp-backup-progress-text'
    ).textContent=
        backupTotal > 0
        ?
        (
            backupDone +
            ' / ' +
            backupTotal +
            ' COMPLETE'
        )
        :
        'IDLE';

    const operation=
        id(
            'agcp-backup-operation'
        );

    const copy=
        id(
            'agcp-backup-operation-copy'
        );

    if(backupBusy){

        operation.textContent=
            'BACKING UP';

        operation.className=
            'agcp-status-pill warn';

        copy.textContent=
            backupCurrent
            ?
            'Creating OAR backup for ' +
            backupCurrent +
            '...'
            :
            'Preparing OAR backups...';

        return;
    }

    if(backupTotal > 0){

        if(backupFailed > 0){

            operation.textContent=
                'COMPLETE WITH ERRORS';

            operation.className=
                'agcp-status-pill bad';

        }else{

            operation.textContent=
                'COMPLETE';

            operation.className=
                'agcp-status-pill good';
        }

        copy.textContent=
            backupSuccess +
            ' succeeded - ' +
            backupFailed +
            ' failed';

        return;
    }

    operation.textContent=
        'IDLE';

    operation.className=
        'agcp-status-pill';

    copy.textContent=
        backupSelected.size
        ?
        backupSelected.size +
        ' region(s) selected.'
        :
        'Select one or more regions to create OAR backups.';
}


function renderSession(){

    const panel=
        id(
            'agcp-backup-session-panel'
        );

    const target=
        id(
            'agcp-backup-session-list'
        );

    if(!backupSession.length){

        panel.hidden=true;
        target.innerHTML='';

        return;
    }

    panel.hidden=false;

    const good=
        backupSession.filter(
            item =>
                item.ok
        ).length;

    const bad=
        backupSession.length -
        good;

    id(
        'agcp-backup-session-summary'
    ).textContent=
        good +
        ' SUCCESS - ' +
        bad +
        ' FAILED';

    target.innerHTML=
        backupSession.map(
            function(item){

                return (
                    '<div class="agcp-backup-session-row ' +
                    (
                        item.ok
                        ? 'good'
                        : 'bad'
                    ) +
                    '">' +

                        '<div class="agcp-backup-session-result">' +
                            (
                                item.ok
                                ? 'SUCCESS'
                                : 'FAILED'
                            ) +
                        '</div>' +

                        '<div>' +
                            esc(item.name) +
                        '</div>' +

                        '<div>' +
                            esc(item.message) +
                        '</div>' +

                        '<div>' +
                            esc(item.time) +
                        '</div>' +

                    '</div>'
                );
            }
        ).join('');
}


async function load(){

    message(
        'Loading Grid Backups...',
        ''
    );

    try{

        const stamp=
            Date.now();

        const results=
            await Promise.all([
                json(
                    '/Other/regions.php?gridbackup=1&nocache=' +
                    stamp
                ),
                json(
                    '/Other/grid-backup-meta.php?nocache=' +
                    stamp
                )
            ]);

        backupRegions=
            listFrom(
                results[0]
            );

        backupMeta=
            results[1] || {
                summary:{},
                regions:[]
            };

        const valid=
            new Set(
                backupRegions
                .map(nameOf)
            );

        backupSelected=
            new Set(
                Array.from(
                    backupSelected
                )
                .filter(
                    name =>
                        valid.has(name)
                )
            );

        renderRows();
        progress();
        renderSession();

        message(
            'Grid Backups loaded.',
            'good'
        );

    }catch(error){

        message(
            error.message ||
            'Unable to load Grid Backups.',
            'bad'
        );
    }
}


async function runBackups(names){

    if(
        backupBusy ||
        !names.length
    ){
        return;
    }

    const confirmed=
        await confirmBox(
            names.length === 1
            ? 'Create OAR Backup'
            : 'Create OAR Backups',

            names.length === 1
            ? 'Create an OAR backup for this region?'
            : 'Create OAR backups for the selected regions?',

            names.join('\n')
        );

    if(!confirmed){
        return;
    }

    backupBusy=true;
    backupCurrent='';
    backupDone=0;
    backupTotal=names.length;
    backupSuccess=0;
    backupFailed=0;
    backupSession=[];

    progress();
    renderRows();

    for(const name of names){

        backupCurrent=name;

        const region=
            backupRegions.find(
                item =>
                    nameOf(item) ===
                    name
            );

        if(region){
            region.__backupState=
                'running';
        }

        progress();
        renderRows();

        message(
            'Starting OAR backup for ' +
            name +
            '...',
            ''
        );

        const started=
            Date.now();

        try{

            const data=
                await command(
                    'SaveOAR',
                    name
                );

            backupSuccess++;

            if(region){
                region.__backupState=
                    'success';
            }

            backupSession.push({
                name:name,
                ok:true,
                message:
                    data.message ||
                    'OAR backup completed.',
                time:
                    new Date()
                    .toLocaleTimeString(),
                duration:
                    Date.now() -
                    started
            });

        }catch(error){

            backupFailed++;

            if(region){
                region.__backupState=
                    'failed';
            }

            backupSession.push({
                name:name,
                ok:false,
                message:
                    error.message ||
                    'Backup failed.',
                time:
                    new Date()
                    .toLocaleTimeString(),
                duration:
                    Date.now() -
                    started
            });
        }

        backupDone++;

        progress();
        renderRows();
        renderSession();
    }

    backupBusy=false;
    backupCurrent='';

    progress();
    renderRows();
    renderSession();

    message(
        backupFailed
        ?
        (
            'OAR requests completed with ' +
            backupFailed +
            ' failure(s).'
        )
        :
        'OAR backups completed successfully.',
        backupFailed
        ? 'bad'
        : 'good'
    );

    setTimeout(
        load,
        1800
    );
}


id(
    'agcp-backup-select-all'
).addEventListener(
    'click',
    function(){

        backupSelected=
            new Set(
                backupRegions
                .map(nameOf)
                .filter(Boolean)
            );

        renderRows();
        progress();
    }
);


id(
    'agcp-backup-clear'
).addEventListener(
    'click',
    function(){

        backupSelected.clear();

        renderRows();
        progress();
    }
);


id(
    'agcp-backup-selected'
).addEventListener(
    'click',
    function(){

        runBackups(
            Array.from(
                backupSelected
            )
        );
    }
);


id(
    'agcp-backup-refresh'
).addEventListener(
    'click',
    load
);


id(
    'agcp-backup-search'
).addEventListener(
    'input',
    renderRows
);


id(
    'agcp-backup-filter'
).addEventListener(
    'change',
    renderRows
);


id(
    'agcp-backup-sort'
).addEventListener(
    'change',
    renderRows
);


id(
    'agcp-backup-list'
).addEventListener(
    'click',
    function(event){

        const backupButton=
            event.target.closest(
                '[data-agcp-backup-now]'
            );

        if(backupButton){

            runBackups([
                backupButton.getAttribute(
                    'data-agcp-backup-now'
                )
            ]);

            return;
        }

        const filesButton=
            event.target.closest(
                '[data-agcp-backup-files]'
            );

        if(filesButton){

            const nav=
                document.querySelector(
                    '.agcp-nav[data-page="backup-files"]'
                );

            if(nav){
                nav.click();
            }
        }
    }
);


const nav=
    document.querySelector(
        '.agcp-nav[data-page="grid-backups"]'
    );

if(nav){

    nav.addEventListener(
        'click',
        function(){

            setTimeout(
                load,
                0
            );
        }
    );
}


const page=
    id(
        'agcp-page-grid-backups'
    );

if(
    page &&
    page.classList.contains('active')
){
    load();
}


})();