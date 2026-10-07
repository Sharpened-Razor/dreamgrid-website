(function(){

'use strict';

let fileRecords=[];
let healthData={};
let healthSchedule={};
let filesLoading=false;
let healthLoading=false;


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
        'items',
        'rows',
        'files',
        'backups',
        'records',
        'data'
    ]){

        if(Array.isArray(data[key])){
            return data[key];
        }
    }

    return [];
}


async function json(url){

    const response=
        await fetch(
            url,
            {
                credentials:'same-origin',
                cache:'no-store'
            }
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


function bytes(value){

    const number=
        Number(value);

    if(
        !Number.isFinite(number) ||
        number < 0
    ){
        return '-';
    }

    const units=[
        'B',
        'KB',
        'MB',
        'GB',
        'TB'
    ];

    let size=number;
    let unit=0;

    while(
        size >= 1024 &&
        unit < units.length-1
    ){
        size/=1024;
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


function dateText(value){

    if(!value){
        return '-';
    }

    let date;

    if(
        Number.isFinite(
            Number(value)
        ) &&
        Number(value) > 100000000
    ){

        const number=
            Number(value);

        date=
            new Date(
                number < 100000000000
                ? number*1000
                : number
            );

    }else{

        date=
            new Date(value);
    }

    if(
        Number.isNaN(
            date.getTime()
        )
    ){
        return String(value);
    }

    return date.toLocaleString();
}


function fileType(item){

    let type=
        String(
            pick(
                item,
                [
                    'type',
                    'Type',
                    'backupType',
                    'kind',
                    'extension'
                ],
                ''
            )
        )
        .toUpperCase();

    if(type.includes('OAR')){
        return 'OAR';
    }

    if(type.includes('IAR')){
        return 'IAR';
    }

    const name=
        fileName(item)
        .toUpperCase();

    if(name.endsWith('.OAR')){
        return 'OAR';
    }

    if(name.endsWith('.IAR')){
        return 'IAR';
    }

    return type || 'FILE';
}


function fileName(item){

    return String(
        pick(
            item,
            [
                'name',
                'filename',
                'file',
                'FileName'
            ],
            'Unknown File'
        )
    );
}


function fileOwner(item){

    return String(
        pick(
            item,
            [
                'region',
                'RegionName',
                'regionName',
                'avatar',
                'AvatarName',
                'owner',
                'Owner'
            ],
            '-'
        )
    );
}


function fileDate(item){

    return pick(
        item,
        [
            'time',
            'Time',
            'date',
            'Date',
            'mtime',
            'modified',
            'created',
            'createdAt'
        ],
        ''
    );
}


function fileTime(item){

    const value=
        fileDate(item);

    if(!value){
        return 0;
    }

    if(
        Number.isFinite(
            Number(value)
        ) &&
        Number(value) > 100000000
    ){

        const number=
            Number(value);

        return number < 100000000000
            ? number*1000
            : number;
    }

    const date=
        new Date(value);

    return Number.isNaN(
        date.getTime()
    )
    ? 0
    : date.getTime();
}


function fileSize(item){

    return Number(
        pick(
            item,
            [
                'bytes',
                'Bytes',
                'size',
                'Size',
                'fileSize'
            ],
            0
        )
    ) || 0;
}


function downloadUrl(item){

    return String(
        pick(
            item,
            [
                'downloadUrl',
                'downloadURL',
                'download_url',
                'download',
                'url'
            ],
            ''
        )
    );
}


function filesMessage(text,state){

    const box=
        id(
            'agcp-files-message'
        );

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


function renderFiles(){

    const search=
        String(
            id(
                'agcp-files-search'
            ).value || ''
        )
        .trim()
        .toLowerCase();

    const type=
        id(
            'agcp-files-type'
        ).value;

    const sort=
        id(
            'agcp-files-sort'
        ).value;

    let rows=
        fileRecords.filter(
            function(item){

                if(
                    type &&
                    fileType(item) !== type
                ){
                    return false;
                }

                if(search){

                    const text=
                        JSON.stringify(
                            item
                        )
                        .toLowerCase();

                    if(
                        !text.includes(
                            search
                        )
                    ){
                        return false;
                    }
                }

                return true;
            }
        );

    rows=
        rows.slice();

    rows.sort(
        function(a,b){

            if(sort === 'oldest'){
                return fileTime(a)-fileTime(b);
            }

            if(sort === 'name'){

                return fileName(a)
                    .localeCompare(
                        fileName(b),
                        undefined,
                        {
                            numeric:true,
                            sensitivity:'base'
                        }
                    );
            }

            if(sort === 'size'){
                return fileSize(b)-fileSize(a);
            }

            return fileTime(b)-fileTime(a);
        }
    );

    const oars=
        fileRecords.filter(
            item =>
                fileType(item) ===
                'OAR'
        ).length;

    const iars=
        fileRecords.filter(
            item =>
                fileType(item) ===
                'IAR'
        ).length;

    id(
        'agcp-files-total'
    ).textContent=
        fileRecords.length;

    id(
        'agcp-files-oar'
    ).textContent=
        oars;

    id(
        'agcp-files-iar'
    ).textContent=
        iars;

    id(
        'agcp-files-visible'
    ).textContent=
        rows.length;

    id(
        'agcp-files-count'
    ).textContent=
        rows.length +
        (
            rows.length === 1
            ? ' FILE'
            : ' FILES'
        );

    const target=
        id(
            'agcp-files-list'
        );

    if(!rows.length){

        target.innerHTML=
            '<div class="agcp-empty">' +
            (
                fileRecords.length
                ? 'No files match the current search or filter.'
                : 'No backup files found.'
            ) +
            '</div>';

        return;
    }

    target.innerHTML=
        rows.map(
            function(item){

                const url=
                    downloadUrl(item);

                return (
                    '<div class="agcp-files-row">' +

                        '<div>' +
                            '<span class="agcp-files-type">' +
                                esc(
                                    fileType(item)
                                ) +
                            '</span>' +
                        '</div>' +

                        '<div>' +
                            '<div class="agcp-files-name">' +
                                esc(
                                    fileName(item)
                                ) +
                            '</div>' +

                            '<div class="agcp-files-sub">' +
                                esc(
                                    String(
                                        pick(
                                            item,
                                            [
                                                'path',
                                                'Path',
                                                'relativePath'
                                            ],
                                            ''
                                        )
                                    )
                                ) +
                            '</div>' +
                        '</div>' +

                        '<div class="agcp-files-data">' +
                            esc(
                                fileOwner(item)
                            ) +
                        '</div>' +

                        '<div class="agcp-files-data">' +
                            esc(
                                dateText(
                                    fileDate(item)
                                )
                            ) +
                        '</div>' +

                        '<div class="agcp-files-data">' +
                            esc(
                                bytes(
                                    fileSize(item)
                                )
                            ) +
                        '</div>' +

                        '<div>' +
                            (
                                url
                                ?
                                (
                                    '<a ' +
                                        'class="agcp-files-download" ' +
                                        'href="' +
                                        esc(url) +
                                        '">' +
                                        'DOWNLOAD' +
                                    '</a>'
                                )
                                :
                                '<span class="agcp-files-data">-</span>'
                            ) +
                        '</div>' +

                    '</div>'
                );
            }
        ).join('');
}


async function loadFiles(){
    if(filesLoading) return;
    filesLoading=true;

    filesMessage(
        'Loading backup files...',
        ''
    );

    try{

        const data=
            await json(
                '/Other/list-backups.php?nocache=' +
                Date.now()
            );

        fileRecords=
            listFrom(data);

        renderFiles();

        filesMessage(
            'Backup files loaded.',
            'good'
        );

    }catch(error){

        fileRecords=[];

        renderFiles();

        filesMessage(
            error.message ||
            'Unable to load backup files.',
            'bad'
        );
    }finally{
        filesLoading=false;
    }
}


function healthMessage(text,state){

    const box=
        id(
            'agcp-health-message'
        );

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


function healthState(level){

    const text=
        String(
            level || ''
        )
        .toLowerCase();

    if(
        text.includes('error') ||
        text.includes('fail') ||
        text.includes('critical') ||
        text.includes('unhealthy')
    ){
        return 'bad';
    }

    if(
        text.includes('warning') ||
        text.includes('disabled') ||
        text.includes('unknown') ||
        text.includes('skip')
    ){
        return 'warn';
    }

    if(
        text.includes('healthy') ||
        text.includes('good') ||
        text === 'ok' ||
        text.includes('normal') ||
        text.includes('success')
    ){
        return 'good';
    }

    return '';
}


function healthCheck(title){

    const checks=
        Array.isArray(
            healthData.checks
        )
        ? healthData.checks
        : [];

    return (
        checks.find(
            check =>
                String(
                    check.title || ''
                ) === title
        )
        ||
        {}
    );
}


function healthDetail(
    label,
    value
){

    return (
        '<div class="agcp-health-detail">' +
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


function setWatch(
    valueId,
    copyId,
    value,
    copy,
    state
){

    const element=
        id(valueId);

    element.textContent=
        value || '-';

    element.className=
        state || '';

    id(
        copyId
    ).textContent=
        copy || '';
}


function renderHealth(){

    const overall=
        String(
            healthData.overall ||
            'UNKNOWN'
        );

    const overallState=
        healthState(overall);

    const state=
        id(
            'agcp-health-state'
        );

    state.textContent=
        overall.toUpperCase();

    state.className=
        'agcp-status-pill ' +
        overallState;

    const completed=
        Number(
            healthData.completed || 0
        );

    const skipped=
        Number(
            healthData.skipped || 0
        );

    const errors=
        Number(
            healthData.errors || 0
        );

    const files=
        healthData.backupFiles &&
        typeof healthData.backupFiles ===
        'object'
        ? healthData.backupFiles
        : {};

    const storage=
        healthData.storage &&
        typeof healthData.storage ===
        'object'
        ? healthData.storage
        : {};

    id(
        'agcp-health-completed'
    ).textContent=
        completed;

    id(
        'agcp-health-skipped'
    ).textContent=
        skipped;

    id(
        'agcp-health-errors'
    ).textContent=
        errors;

    id(
        'agcp-health-total'
    ).textContent=
        Number(
            files.total || 0
        );


    const scheduler=
        healthCheck(
            'Background scheduler'
        );

    const schedule=
        healthCheck(
            'Backup schedule'
        );

    const last=
        healthCheck(
            'Last scheduled backup'
        );


    setWatch(
        'agcp-health-scheduler',
        'agcp-health-scheduler-copy',
        scheduler.level || 'UNKNOWN',
        scheduler.detail || '',
        healthState(
            scheduler.level
        )
    );


    setWatch(
        'agcp-health-schedule',
        'agcp-health-schedule-copy',
        schedule.level || 'UNKNOWN',
        schedule.detail || '',
        healthState(
            schedule.level
        )
    );


    const nextRun=
        healthSchedule.nextRun || '';

    setWatch(
        'agcp-health-next',
        'agcp-health-next-copy',
        nextRun
        ? dateText(nextRun)
        : 'NOT SCHEDULED',
        healthData.timezone
        ? 'Server timezone: ' +
          healthData.timezone
        : '',
        nextRun
        ? 'good'
        : 'warn'
    );


    setWatch(
        'agcp-health-last',
        'agcp-health-last-copy',
        last.level || 'UNKNOWN',
        last.detail || '',
        healthState(
            last.level
        )
    );


    const checks=
        Array.isArray(
            healthData.checks
        )
        ? healthData.checks
        : [];

    const checksTarget=
        id(
            'agcp-health-checks'
        );

    if(!checks.length){

        checksTarget.innerHTML=
            '<div class="agcp-empty">' +
            'No health checks returned.' +
            '</div>';

    }else{

        checksTarget.innerHTML=
            checks.map(
                function(check){

                    const level=
                        String(
                            check.level ||
                            check.status ||
                            'UNKNOWN'
                        );

                    const checkState=
                        healthState(level);

                    return (
                        '<div class="agcp-health-item ' +
                        esc(checkState) +
                        '">' +

                            '<div class="agcp-health-item-label">' +
                                esc(
                                    check.title ||
                                    check.name ||
                                    'CHECK'
                                ) +
                            '</div>' +

                            '<div class="agcp-health-item-value">' +
                                esc(level) +
                            '</div>' +

                            '<div class="agcp-health-item-detail">' +
                                esc(
                                    check.detail ||
                                    check.message ||
                                    ''
                                ) +
                            '</div>' +

                        '</div>'
                    );
                }
            ).join('');
    }


    id(
        'agcp-health-files'
    ).innerHTML=

        healthDetail(
            'OAR FILES',
            files.oar ?? 0
        ) +

        healthDetail(
            'IAR FILES',
            files.iar ?? 0
        ) +

        healthDetail(
            'TOTAL FILES',
            files.total ?? 0
        ) +

        healthDetail(
            'NEWEST',
            files.newest
            ?
            (
                String(
                    files.newest.type ||
                    ''
                ) +
                ' - ' +
                String(
                    files.newest.name ||
                    ''
                )
            )
            :
            'NONE'
        ) +

        healthDetail(
            'NEWEST TIME',
            files.newest
            ? dateText(
                files.newest.time
              )
            : '-'
        ) +

        healthDetail(
            'OLDEST',
            files.oldest
            ?
            (
                String(
                    files.oldest.type ||
                    ''
                ) +
                ' - ' +
                String(
                    files.oldest.name ||
                    ''
                )
            )
            :
            'NONE'
        );


    let storageUsed='-';
    let storageFree='-';
    let storageTotal='-';

    if(
        storage.used !== undefined
    ){
        storageUsed=
            bytes(
                storage.used
            );
    }

    if(
        storage.free !== undefined
    ){
        storageFree=
            bytes(
                storage.free
            );
    }

    if(
        storage.total !== undefined
    ){
        storageTotal=
            bytes(
                storage.total
            );
    }


    id(
        'agcp-health-storage'
    ).innerHTML=

        healthDetail(
            'USED',
            storageUsed
        ) +

        healthDetail(
            'FREE',
            storageFree
        ) +

        healthDetail(
            'TOTAL',
            storageTotal
        ) +

        healthDetail(
            'FREE PERCENT',
            storage.percentFree !== undefined
            ? storage.percentFree + '%'
            : '-'
        ) +

        healthDetail(
            'LAST FINISHED',
            healthData.lastFinished
            ? dateText(
                healthData.lastFinished
              )
            : '-'
        ) +

        healthDetail(
            'LAST CHECK',
            healthData.lastCheck
            ? dateText(
                healthData.lastCheck
              )
            : '-'
        );
}


async function loadHealth(){
    if(healthLoading) return;
    healthLoading=true;

    healthMessage(
        'Loading backup health...',
        ''
    );

    const stamp=
        Date.now();

    try{

        healthData=
            await json(
                '/Other/backup-health.php?nocache=' +
                stamp
            );

        try{

            healthSchedule=
                await json(
                    '/Other/backup-schedule.php?nocache=' +
                    stamp
                );

        }catch(error){

            healthSchedule={};
        }

        renderHealth();

        healthMessage(
            'Backup health loaded.',
            'good'
        );

    }catch(error){

        healthData={};
        healthSchedule={};

        renderHealth();

        healthMessage(
            error.message ||
            'Unable to load backup health.',
            'bad'
        );
    }finally{
        healthLoading=false;
    }
}


/* FILE EVENTS */

id(
    'agcp-files-refresh'
).addEventListener(
    'click',
    loadFiles
);

id(
    'agcp-files-search'
).addEventListener(
    'input',
    renderFiles
);

id(
    'agcp-files-type'
).addEventListener(
    'change',
    renderFiles
);

id(
    'agcp-files-sort'
).addEventListener(
    'change',
    renderFiles
);


/* HEALTH EVENTS */

id(
    'agcp-health-refresh'
).addEventListener(
    'click',
    loadHealth
);


/* NAV LOADERS */

const filesNav=
    document.querySelector(
        '.agcp-nav[data-page="backup-files"]'
    );

if(filesNav){

    filesNav.addEventListener(
        'click',
        function(){

            setTimeout(
                loadFiles,
                0
            );
        }
    );
}


const healthNav=
    document.querySelector(
        '.agcp-nav[data-page="backup-health"]'
    );

if(healthNav){

    healthNav.addEventListener(
        'click',
        function(){

            setTimeout(
                loadHealth,
                0
            );
        }
    );
}


const filesPage=
    id(
        'agcp-page-backup-files'
    );

if(
    filesPage &&
    filesPage.classList.contains(
        'active'
    )
){
    loadFiles();
}


const healthPage=
    id(
        'agcp-page-backup-health'
    );

if(
    healthPage &&
    healthPage.classList.contains(
        'active'
    )
){
    loadHealth();
}


})();
