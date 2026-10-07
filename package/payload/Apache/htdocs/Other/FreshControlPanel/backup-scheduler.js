(function(){

'use strict';

let schedulerData={};
let schedulerRegions=[];
let schedulerSelected=new Set();


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
            'Invalid scheduler response.'
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
            'Scheduler request failed.'
        );
    }

    return data;
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


function avatarsOf(region){

    return String(
        pick(
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


function primsOf(region){

    return String(
        pick(
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
        id(
            'agcp-schedule-message'
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


/* AGCP_SCHEDULER_ENABLED_STATE_FIX */
let schedulerEnabledChoice = null;
let schedulerEnabledDirty = false;

function enabledValue(value){

    return (
        value === true ||
        value === 1 ||
        String(value) === '1' ||
        String(value)
            .toLowerCase() ===
            'true'
    );
}


function renderSummary(){

    const config=
        schedulerData.config || {};

    const enabled=
        enabledValue(
            config.enabled
        );

    id(
        'agcp-schedule-summary-enabled'
    ).textContent=
        enabled
        ? 'ENABLED'
        : 'DISABLED';

    id(
        'agcp-schedule-summary-enabled'
    ).className=
        enabled
        ? 'good'
        : 'warn';

    id(
        'agcp-schedule-summary-next'
    ).textContent=
        schedulerData.nextRun ||
        (
            schedulerData.runner &&
            schedulerData.runner.nextRun
            ? schedulerData.runner.nextRun
            : '-'
        );

    id(
        'agcp-schedule-summary-regions'
    ).textContent=
        schedulerSelected.size;

    id(
        'agcp-schedule-summary-keep'
    ).textContent=
        String(
            config.keepLast || 3
        ) +
        ' BACKUPS';

    const state=
        id(
            'agcp-schedule-state'
        );

    state.textContent=
        enabled
        ? 'ENABLED'
        : 'DISABLED';

    state.className=
        'agcp-status-pill ' +
        (
            enabled
            ? 'good'
            : 'warn'
        );
}


function applyMode(){

    const type=
        id(
            'agcp-schedule-type'
        ).value;

    const boxes=
        Array.from(
            document.querySelectorAll(
                '.agcp-schedule-day'
            )
        );

    boxes.forEach(
        function(box){

            box.disabled=
                type === 'daily';

            const label=
                box.closest('label');

            if(label){

                label.classList.toggle(
                    'disabled',
                    type === 'daily'
                );
            }
        }
    );

    if(type === 'daily'){

        boxes.forEach(
            box =>
                box.checked=false
        );
    }

    if(type === 'weekly'){

        const checked=
            boxes.filter(
                box =>
                    box.checked
            );

        if(checked.length > 1){

            checked
                .slice(1)
                .forEach(
                    box =>
                        box.checked=false
                );
        }
    }
}


function renderRegions(){

    const search=
        String(
            id(
                'agcp-schedule-search'
            ).value || ''
        )
        .trim()
        .toLowerCase();

    const known=
        new Set(
            schedulerRegions
            .map(nameOf)
        );

    const rows=
        schedulerRegions.map(
            region => ({
                region:region,
                name:nameOf(region),
                missing:false
            })
        );

    schedulerSelected.forEach(
        function(name){

            if(!known.has(name)){

                rows.push({
                    region:null,
                    name:name,
                    missing:true
                });
            }
        }
    );

    const filtered=
        rows.filter(
            function(item){

                if(!search){
                    return true;
                }

                return (
                    item.name +
                    ' ' +
                    (
                        item.region
                        ? ownerOf(item.region)
                        : ''
                    )
                )
                .toLowerCase()
                .includes(search);
            }
        );

    const target=
        id(
            'agcp-schedule-region-list'
        );

    if(!filtered.length){

        target.innerHTML=
            '<div class="agcp-empty">' +
            'No regions match the current search.' +
            '</div>';

        renderSummary();

        return;
    }

    target.innerHTML=
        filtered.map(
            function(item){

                const selected=
                    schedulerSelected.has(
                        item.name
                    );

                let rowClass=
                    'agcp-schedule-region-row';

                if(selected){
                    rowClass +=
                        ' selected';
                }

                if(item.missing){
                    rowClass +=
                        ' missing';
                }

                return (
                    '<label class="' +
                    rowClass +
                    '">' +

                        '<input ' +
                            'type="checkbox" ' +
                            'class="agcp-check agcp-schedule-region-check" ' +
                            'data-region="' +
                            esc(item.name) +
                            '"' +
                            (
                                selected
                                ? ' checked'
                                : ''
                            ) +
                        '>' +

                        '<div class="agcp-schedule-region-name">' +
                            esc(item.name) +
                        '</div>' +

                        '<div>' +
                            (
                                item.missing
                                ?
                                pill(
                                    'NOT RETURNED',
                                    'bad'
                                )
                                :
                                pill(
                                    statusOf(
                                        item.region
                                    ),
                                    online(
                                        item.region
                                    )
                                    ? 'good'
                                    : 'warn'
                                )
                            ) +
                        '</div>' +

                        '<div class="agcp-schedule-region-owner">' +
                            esc(
                                item.region
                                ? ownerOf(item.region)
                                : '-'
                            ) +
                        '</div>' +

                        '<div class="agcp-schedule-region-data">' +
                            esc(
                                item.region
                                ? avatarsOf(item.region)
                                : '-'
                            ) +
                        '</div>' +

                        '<div class="agcp-schedule-region-data">' +
                            esc(
                                item.region
                                ? primsOf(item.region)
                                : '-'
                            ) +
                        '</div>' +

                    '</label>'
                );
            }
        ).join('');

    target
        .querySelectorAll(
            '.agcp-schedule-region-check'
        )
        .forEach(
            function(box){

                box.addEventListener(
                    'change',
                    function(){

                        const name=
                            box.dataset.region;

                        if(box.checked){

                            schedulerSelected.add(
                                name
                            );

                        }else{

                            schedulerSelected.delete(
                                name
                            );
                        }

                        renderRegions();
                    }
                );
            }
        );

    renderSummary();
}


function renderRunner(){

    const target=
        id(
            'agcp-schedule-runner'
        );

    const runner=
        schedulerData.runner &&
        typeof schedulerData.runner ===
        'object'
        ? schedulerData.runner
        : {};

    const entries=
        Object.entries(runner);

    if(!entries.length){

        target.innerHTML=
            '<div class="agcp-empty">' +
            'No runner state reported.' +
            '</div>';

        return;
    }

    target.innerHTML=
        entries
        .slice(0,18)
        .map(
            function(pair){

                let value=
                    pair[1];

                if(
                    value &&
                    typeof value ===
                    'object'
                ){
                    value=
                        JSON.stringify(value);
                }

                return (
                    '<div class="agcp-schedule-runner-item">' +

                        '<span>' +
                            esc(
                                pair[0]
                            ) +
                        '</span>' +

                        '<strong>' +
                            esc(value) +
                        '</strong>' +

                    '</div>'
                );
            }
        ).join('');
}



/*
 * ============================================================
 * AUSTRALIA SCHEDULER ACTIVE STATE FIX V1
 *
 * The DreamGrid RegionStatus endpoint can retain BackingUp
 * after an OAR operation has already finished.
 *
 * On the Backup Scheduler page the scheduler runner state is
 * the authority for whether a SCHEDULED backup is active.
 * ============================================================
 */

function schedulerFixStaleBackupStates(){

    const runnerState =
        schedulerData &&
        schedulerData.runner &&
        schedulerData.runner.state &&
        typeof schedulerData.runner.state === 'object'
            ?
            schedulerData.runner.state
            :
            {};


    const runnerRegions =
        Array.isArray(
            runnerState.regions
        )
            ?
            runnerState.regions
            :
            [];


    const activeRegions =
        new Set();


    runnerRegions.forEach(
        function(item){

            if(
                !item ||
                typeof item !== 'object'
            ){
                return;
            }


            const name =
                String(
                    item.region ||
                    item.RegionName ||
                    item.name ||
                    ''
                )
                .trim()
                .toLowerCase();


            const status =
                String(
                    item.status ||
                    item.Status ||
                    ''
                )
                .trim()
                .toLowerCase()
                .replace(
                    /[^a-z]/g,
                    ''
                );


            if(
                name &&
                (
                    status === 'starting' ||
                    status === 'backingup' ||
                    status === 'saving' ||
                    status === 'savingoar'
                )
            ){
                activeRegions.add(
                    name
                );
            }
        }
    );


    if(
        !Array.isArray(
            schedulerRegions
        )
    ){
        return;
    }


    schedulerRegions.forEach(
        function(region){

            if(
                !region ||
                typeof region !== 'object'
            ){
                return;
            }


            const name =
                String(
                    region.RegionName ||
                    region.region ||
                    region.Name ||
                    region.name ||
                    ''
                )
                .trim()
                .toLowerCase();


            const rawStatus =
                String(
                    region.Status ||
                    region.RegionStatus ||
                    region.State ||
                    region.status ||
                    ''
                )
                .trim();


            const cleanStatus =
                rawStatus
                .toLowerCase()
                .replace(
                    /[^a-z]/g,
                    ''
                );


            /*
             * The scheduler says this region is genuinely
             * being processed right now.
             */
            if(
                name &&
                activeRegions.has(
                    name
                )
            ){

                region.Status =
                    'BackingUp';

                return;
            }


            /*
             * DreamGrid still says BackingUp, but the scheduler
             * has no active job for this region.
             *
             * Treat it as stale display state.
             */
            if(
                cleanStatus === 'backingup' ||
                cleanStatus === 'saving' ||
                cleanStatus === 'savingoar' ||
                cleanStatus === 'backup'
            ){

                region.Status =
                    'Booted';
            }
        }
    );
}

async function load(){

    message(
        'Loading scheduler...',
        ''
    );

    try{

        const stamp=
            Date.now();

        const results=
            await Promise.all([
                json(
                    '/Other/backup-schedule.php?nocache=' +
                    stamp
                ),
                json(
                    '/Other/regions.php?gridbackup=1&nocache=' +
                    stamp
                )
            ]);

        schedulerData=
            results[0] || {};

        schedulerRegions=
            listFrom(
                results[1]
            );

        /*
         * AUSTRALIA SCHEDULER ACTIVE STATE FIX V1
         */
        schedulerFixStaleBackupStates();


        const config=
            schedulerData.config || {};

        const backendEnabled=
            enabledValue(
                config.enabled
            );

        if(!schedulerEnabledDirty){

            schedulerEnabledChoice=
                backendEnabled;

            id(
                'agcp-schedule-enabled'
            ).value=
                backendEnabled
                ? '1'
                : '0';
        }

        const type=
            String(
                config.type ||
                'daily'
            )
            .toLowerCase();

        id(
            'agcp-schedule-type'
        ).value=
            [
                'daily',
                'weekly',
                'selected'
            ].includes(type)
            ? type
            : 'daily';

        const time=
            String(
                config.time ||
                '02:00'
            );

        id(
            'agcp-schedule-time'
        ).value=
            /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(
                time
            )
            ? time
            : '02:00';

        const keep=
            Number(
                config.keepLast || 3
            );

        const keepSelect=
            id(
                'agcp-schedule-keep'
            );

        if(
            !Array.from(
                keepSelect.options
            ).some(
                option =>
                    Number(
                        option.value
                    ) ===
                    keep
            )
        ){

            const option=
                document.createElement(
                    'option'
                );

            option.value=
                String(keep);

            option.textContent=
                keep +
                ' BACKUPS';

            keepSelect.appendChild(
                option
            );
        }

        keepSelect.value=
            String(keep);

        schedulerSelected=
            new Set(
                Array.isArray(
                    config.regions
                )
                ? config.regions
                : []
            );

        const days=
            Array.isArray(
                config.days
            )
            ? config.days.map(Number)
            : [];

        document
            .querySelectorAll(
                '.agcp-schedule-day'
            )
            .forEach(
                function(box){

                    box.checked=
                        days.includes(
                            Number(
                                box.value
                            )
                        );
                }
            );

        applyMode();
        renderRegions();
        renderRunner();
        renderSummary();

        message(
            'Scheduler loaded.',
            'good'
        );

    }catch(error){

        message(
            error.message ||
            'Unable to load scheduler.',
            'bad'
        );

        const state=
            id(
                'agcp-schedule-state'
            );

        state.textContent=
            'ERROR';

        state.className=
            'agcp-status-pill bad';
    }
}


async function save(){

    const enabledControl=
        id(
            'agcp-schedule-enabled'
        );

    const enabled=
        schedulerEnabledChoice !== null
        ?
        schedulerEnabledChoice
        :
        enabledControl.value === '1';

    const type=
        id(
            'agcp-schedule-type'
        ).value;

    const time=
        id(
            'agcp-schedule-time'
        ).value;

    const keepLast=
        Number(
            id(
                'agcp-schedule-keep'
            ).value
        );

    let days=
        Array.from(
            document.querySelectorAll(
                '.agcp-schedule-day:checked'
            )
        )
        .map(
            box =>
                Number(
                    box.value
                )
        );

    const regions=
        Array.from(
            schedulerSelected
        );

    if(
        enabled &&
        regions.length === 0
    ){

        message(
            'Select at least one region before enabling the scheduler.',
            'bad'
        );

        return;
    }

    if(type === 'daily'){

        days=[];
    }

    if(
        type === 'weekly' &&
        days.length !== 1
    ){

        message(
            'Weekly schedules require exactly one backup day.',
            'bad'
        );

        return;
    }

    if(
        type === 'selected' &&
        days.length === 0
    ){

        message(
            'Selected Days requires at least one backup day.',
            'bad'
        );

        return;
    }

    if(
        !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(
            time
        )
    ){

        message(
            'Enter a valid backup time.',
            'bad'
        );

        return;
    }

    const config={
        enabled:enabled,
        type:type,
        time:time,
        days:days,
        regions:regions,
        keepLast:keepLast
    };

    const body=
        new URLSearchParams();

    body.set(
        'config',
        JSON.stringify(
            config
        )
    );

    const button=
        id(
            'agcp-schedule-save'
        );

    button.disabled=true;

    message(
        'Saving scheduler...',
        ''
    );

    try{

        schedulerData=
            await json(
                '/Other/backup-schedule.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8'
                    },

                    body:body
                }
            );
        const savedEnabled=
            enabledValue(
                schedulerData &&
                schedulerData.config
                ?
                schedulerData.config.enabled
                :
                false
            );

        if(savedEnabled !== enabled){
            throw new Error(
                'Scheduler enabled state returned by server does not match the selected state.'
            );
        }

        schedulerEnabledChoice=
            savedEnabled;

        schedulerEnabledDirty=
            false;

        enabledControl.value=
            savedEnabled
            ? '1'
            : '0';

        message(
            'Scheduler saved successfully.',
            'good'
        );

        setTimeout(
            load,
            400
        );

    }catch(error){

        message(
            error.message ||
            'Scheduler save failed.',
            'bad'
        );

    }finally{

        button.disabled=false;
    }
}


id(
    'agcp-schedule-refresh'
).addEventListener(
    'click',
    load
);


id(
    'agcp-schedule-save'
).addEventListener(
    'click',
    save
);


id(
    'agcp-schedule-select-all'
).addEventListener(
    'click',
    function(){

        schedulerSelected=
            new Set(
                schedulerRegions
                .map(nameOf)
                .filter(Boolean)
            );

        renderRegions();
    }
);


id(
    'agcp-schedule-clear'
).addEventListener(
    'click',
    function(){

        schedulerSelected.clear();

        renderRegions();
    }
);


id(
    'agcp-schedule-search'
).addEventListener(
    'input',
    renderRegions
);


id(
    'agcp-schedule-type'
).addEventListener(
    'change',
    function(){

        applyMode();
        renderSummary();
    }
);


id(
    'agcp-schedule-enabled'
).addEventListener(
    'change',
    function(event){

        schedulerEnabledChoice=
            event.target.value === '1';

        schedulerEnabledDirty=
            true;

        if(!schedulerData.config){
            schedulerData.config={};
        }

        schedulerData.config.enabled=
            schedulerEnabledChoice;

        renderSummary();
    }
);


id(
    'agcp-schedule-keep'
).addEventListener(
    'change',
    renderSummary
);


document
    .querySelectorAll(
        '.agcp-schedule-day'
    )
    .forEach(
        function(box){

            box.addEventListener(
                'change',
                function(){

                    const type=
                        id(
                            'agcp-schedule-type'
                        ).value;

                    if(
                        type === 'weekly' &&
                        box.checked
                    ){

                        document
                            .querySelectorAll(
                                '.agcp-schedule-day'
                            )
                            .forEach(
                                function(other){

                                    if(other !== box){

                                        other.checked=false;
                                    }
                                }
                            );
                    }
                }
            );
        }
    );


const nav=
    document.querySelector(
        '.agcp-nav[data-page="backup-scheduler"]'
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
        'agcp-page-backup-scheduler'
    );

if(
    page &&
    page.classList.contains('active')
){
    load();
}


})();