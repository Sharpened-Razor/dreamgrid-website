(function(){

'use strict';

let historyData={};
let historyRecords=[];


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


function pick(
    object,
    names,
    fallback
){

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


function recordsFrom(data){

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
        'history',
        'entries',
        'records',
        'data'
    ]){

        if(Array.isArray(data[key])){
            return data[key];
        }
    }

    return [];
}


async function json(
    url,
    options
){

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
        data=
            JSON.parse(raw);
    }
    catch(error){

        throw new Error(
            raw ||
            'Invalid history response.'
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
            'History request failed.'
        );
    }

    return data;
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


function message(
    text,
    state
){

    const box=
        id(
            'agcp-history-message'
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


function titleOf(
    item,
    index
){

    return String(
        pick(
            item,
            [
                'name',
                'filename',
                'file',
                'FileName',
                'region',
                'RegionName',
                'avatar',
                'AvatarName',
                'title',
                'message',
                'action'
            ],
            'Backup Record ' +
            (
                index + 1
            )
        )
    );
}


function typeOf(item){

    return String(
        pick(
            item,
            [
                'type',
                'Type',
                'backupType',
                'kind',
                'extension'
            ],
            '-'
        )
    );
}


function statusOf(item){

    return String(
        pick(
            item,
            [
                'status',
                'Status',
                'result',
                'Result',
                'state',
                'outcome'
            ],
            '-'
        )
    );
}


function sourceOf(item){

    return String(
        pick(
            item,
            [
                'source',
                'Source',
                'origin',
                'trigger',
                'requestedBy',
                'RequestedBy'
            ],
            '-'
        )
    );
}


function regionOf(item){

    return String(
        pick(
            item,
            [
                'region',
                'RegionName',
                'regionName',
                'target',
                'Target'
            ],
            '-'
        )
    );
}


function timeOf(item){

    return String(
        pick(
            item,
            [
                'time',
                'Time',
                'date',
                'Date',
                'created',
                'Created',
                'createdAt',
                'timestamp',
                'finished',
                'Finished'
            ],
            '-'
        )
    );
}


function detailOf(item){

    return String(
        pick(
            item,
            [
                'detail',
                'Detail',
                'message',
                'Message',
                'error',
                'Error',
                'path',
                'file'
            ],
            ''
        )
    );
}


function stateOf(item){

    const text=
        statusOf(item)
        .toLowerCase();

    if(
        text.includes('fail') ||
        text.includes('error') ||
        text.includes('bad')
    ){
        return 'failed';
    }

    if(
        text.includes('warn') ||
        text.includes('skip') ||
        text.includes('partial')
    ){
        return 'warning';
    }

    if(
        text.includes('success') ||
        text.includes('complete') ||
        text.includes('ok') ||
        text.includes('done')
    ){
        return 'success';
    }

    return '';
}


function flatten(
    input,
    prefix,
    output,
    depth
){

    output=
        output || [];

    prefix=
        prefix || '';

    depth=
        depth || 0;

    if(
        depth > 3 ||
        input === null ||
        input === undefined
    ){
        return output;
    }

    if(
        typeof input !==
        'object'
    ){

        output.push({
            key:
                prefix ||
                'value',
            value:
                String(input)
        });

        return output;
    }

    if(Array.isArray(input)){

        input.forEach(
            function(value,index){

                flatten(
                    value,
                    prefix
                    ? prefix +
                      '.' +
                      index
                    : String(index),
                    output,
                    depth + 1
                );
            }
        );

        return output;
    }

    Object.entries(input)
    .forEach(
        function(pair){

            const key=
                prefix
                ? prefix +
                  '.' +
                  pair[0]
                : pair[0];

            const value=
                pair[1];

            if(
                value &&
                typeof value ===
                'object'
            ){

                flatten(
                    value,
                    key,
                    output,
                    depth + 1
                );

            }else{

                output.push({
                    key:key,
                    value:
                        value === null ||
                        value === undefined
                        ? ''
                        : String(value)
                });
            }
        }
    );

    return output;
}


function setOptions(
    selectId,
    values,
    firstText
){

    const select=
        id(selectId);

    const current=
        select.value;

    const unique=
        Array.from(
            new Set(
                values
                .map(
                    value =>
                        String(
                            value || ''
                        )
                        .trim()
                )
                .filter(
                    value =>
                        value !== '' &&
                        value !== '-'
                )
            )
        )
        .sort(
            function(a,b){

                return a.localeCompare(
                    b,
                    undefined,
                    {
                        sensitivity:'base'
                    }
                );
            }
        );

    select.innerHTML=
        '<option value="">' +
        esc(firstText) +
        '</option>' +

        unique.map(
            value =>
                '<option value="' +
                esc(value) +
                '">' +
                esc(
                    value.toUpperCase()
                ) +
                '</option>'
        ).join('');

    if(
        unique.includes(
            current
        )
    ){
        select.value=
            current;
    }
}


function buildFilters(){

    setOptions(
        'agcp-history-type',
        historyRecords.map(typeOf),
        'ALL TYPES'
    );

    setOptions(
        'agcp-history-result',
        historyRecords.map(statusOf),
        'ALL STATUS'
    );

    setOptions(
        'agcp-history-source',
        historyRecords.map(sourceOf),
        'ALL SOURCES'
    );
}


function extraPairs(item){

    const hiddenKeys=
        new Set([
            'name',
            'filename',
            'file',
            'filename',
            'region',
            'regionname',
            'regionname',
            'type',
            'backuptype',
            'kind',
            'extension',
            'status',
            'result',
            'state',
            'outcome',
            'source',
            'origin',
            'trigger',
            'requestedby',
            'time',
            'date',
            'created',
            'createdat',
            'timestamp',
            'finished',
            'detail',
            'message'
        ]);

    return flatten(item)
        .filter(
            function(pair){

                const key=
                    String(
                        pair.key || ''
                    )
                    .toLowerCase()
                    .replace(
                        /[^a-z0-9]/g,
                        ''
                    );

                return (
                    !hiddenKeys.has(key) &&
                    String(
                        pair.value || ''
                    ).trim() !== ''
                );
            }
        )
        .slice(
            0,
            6
        );
}


function filtered(){

    const search=
        String(
            id(
                'agcp-history-search'
            ).value || ''
        )
        .trim()
        .toLowerCase();

    const type=
        id(
            'agcp-history-type'
        ).value;

    const result=
        id(
            'agcp-history-result'
        ).value;

    const source=
        id(
            'agcp-history-source'
        ).value;

    return historyRecords.filter(
        function(item){

            if(
                type &&
                typeOf(item) !== type
            ){
                return false;
            }

            if(
                result &&
                statusOf(item) !== result
            ){
                return false;
            }

            if(
                source &&
                sourceOf(item) !== source
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
}


function render(){

    const records=
        filtered();

    id(
        'agcp-history-count'
    ).textContent=
        records.length +
        (
            records.length === 1
            ? ' RECORD'
            : ' RECORDS'
        );

    const target=
        id(
            'agcp-history-list'
        );

    if(!records.length){

        target.innerHTML=
            '<div class="agcp-empty">' +
            (
                historyRecords.length
                ? 'No history records match the current filters.'
                : 'No backup history records found.'
            ) +
            '</div>';

        message(
            historyRecords.length
            ?
            (
                'Showing 0 of ' +
                historyRecords.length +
                ' backup records.'
            )
            :
            'Backup history is empty.',
            'good'
        );

        return;
    }

    target.innerHTML=
        records.map(
            function(item,index){

                const state=
                    stateOf(item);

                const extras=
                    extraPairs(item);

                return (
                    '<div class="agcp-history-row ' +
                    esc(state) +
                    '">' +

                        '<div>' +

                            '<span class="agcp-history-badge ' +
                            esc(state) +
                            '">' +
                                esc(
                                    statusOf(item)
                                ) +
                            '</span>' +

                        '</div>' +

                        '<div class="agcp-history-primary">' +

                            '<div class="agcp-history-title">' +
                                esc(
                                    titleOf(
                                        item,
                                        index
                                    )
                                ) +
                            '</div>' +

                            '<div class="agcp-history-sub">' +
                                esc(
                                    detailOf(item)
                                ) +
                            '</div>' +

                        '</div>' +

                        '<div class="agcp-history-cell">' +
                            '<span>TYPE</span>' +
                            '<strong>' +
                                esc(
                                    typeOf(item)
                                ) +
                            '</strong>' +
                        '</div>' +

                        '<div class="agcp-history-cell">' +
                            '<span>SOURCE</span>' +
                            '<strong>' +
                                esc(
                                    sourceOf(item)
                                ) +
                            '</strong>' +
                        '</div>' +

                        '<div class="agcp-history-cell">' +
                            '<span>TIME / REGION</span>' +
                            '<strong>' +
                                esc(
                                    timeOf(item)
                                ) +
                            '</strong>' +

                            '<div class="agcp-history-sub">' +
                                esc(
                                    regionOf(item)
                                ) +
                            '</div>' +
                        '</div>' +

                        (
                            extras.length
                            ?
                            (
                                '<div class="agcp-history-extra">' +

                                    extras.map(
                                        function(pair){

                                            return (
                                                '<div class="agcp-history-extra-item">' +

                                                    '<span>' +
                                                        esc(
                                                            pair.key
                                                        ) +
                                                    '</span>' +

                                                    '<strong>' +
                                                        esc(
                                                            pair.value
                                                        ) +
                                                    '</strong>' +

                                                '</div>'
                                            );
                                        }
                                    ).join('') +

                                '</div>'
                            )
                            :
                            ''
                        ) +

                    '</div>'
                );
            }
        ).join('');

    if(
        records.length ===
        historyRecords.length
    ){

        message(
            'Backup history loaded. ' +
            historyRecords.length +
            (
                historyRecords.length === 1
                ? ' record.'
                : ' records.'
            ),
            'good'
        );

    }else{

        message(
            'Showing ' +
            records.length +
            ' of ' +
            historyRecords.length +
            ' backup records.',
            'good'
        );
    }
}


async function load(){

    message(
        'Loading backup history...',
        ''
    );

    try{

        historyData=
            await json(
                '/Other/backup-history.php?nocache=' +
                Date.now()
            );

        historyRecords=
            recordsFrom(
                historyData
            );

        buildFilters();
        render();

    }catch(error){

        historyData={};
        historyRecords=[];

        id(
            'agcp-history-list'
        ).innerHTML=
            '<div class="agcp-empty">' +
            'Backup history could not be loaded.' +
            '</div>';

        id(
            'agcp-history-count'
        ).textContent=
            '0 RECORDS';

        message(
            error.message ||
            'Unable to load backup history.',
            'bad'
        );
    }
}


async function clearHistory(){

    const confirmed=
        await confirmBox(
            'Clear Backup History',
            'Clear the displayed backup history?',
            'This clears the history log only. It does not delete OAR or IAR backup files.'
        );

    if(!confirmed){
        return;
    }

    const body=
        new URLSearchParams();

    body.set(
        'action',
        'clear'
    );

    id(
        'agcp-history-clear'
    ).disabled=true;

    message(
        'Clearing backup history...',
        ''
    );

    try{

        await json(
            '/Other/backup-history.php',
            {
                method:'POST',

                headers:{
                    'Content-Type':
                        'application/x-www-form-urlencoded;charset=UTF-8'
                },

                body:body
            }
        );

        await load();

        message(
            'Backup history cleared. Backup files were not deleted.',
            'good'
        );

    }catch(error){

        message(
            error.message ||
            'Unable to clear backup history.',
            'bad'
        );

    }finally{

        id(
            'agcp-history-clear'
        ).disabled=false;
    }
}


id(
    'agcp-history-refresh'
).addEventListener(
    'click',
    load
);


id(
    'agcp-history-clear'
).addEventListener(
    'click',
    clearHistory
);


id(
    'agcp-history-search'
).addEventListener(
    'input',
    render
);


id(
    'agcp-history-type'
).addEventListener(
    'change',
    render
);


id(
    'agcp-history-result'
).addEventListener(
    'change',
    render
);


id(
    'agcp-history-source'
).addEventListener(
    'change',
    render
);


const nav=
    document.querySelector(
        '.agcp-nav[data-page="backup-history"]'
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
        'agcp-page-backup-history'
    );

if(
    page &&
    page.classList.contains(
        'active'
    )
){
    load();
}


})();