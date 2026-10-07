(function(){

'use strict';

let restoreData={};
let restoreRows=[];

let userNames=[];
let iarPolling=false;


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


function listFrom(
    data,
    keys
){

    if(Array.isArray(data)){
        return data;
    }

    if(
        !data ||
        typeof data !== 'object'
    ){
        return [];
    }

    for(const key of keys){

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
    extra
){

    const body=
        new URLSearchParams();

    body.set(
        'command',
        commandName
    );

    if(extra){

        Object.entries(extra)
        .forEach(
            function(pair){

                body.set(
                    pair[0],
                    pair[1]
                );
            }
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
                ? '\n\n'+detail
                : ''
            )
        )
    );
}


/* ============================================================
   RESTORE HISTORY
   ============================================================ */

function restoreMessage(
    text,
    state
){

    const box=
        id(
            'agcp-restore-message'
        );

    box.className=
        'agcp-message' +
        (
            state
            ? ' '+state
            : ''
        );

    box.textContent=
        text;
}


function restoreState(status){

    const text=
        String(
            status || ''
        )
        .toLowerCase();

    if(
        text.includes('fail') ||
        text.includes('error') ||
        text.includes('cancel')
    ){
        return 'failed';
    }

    if(
        text.includes('warn') ||
        text.includes('partial') ||
        text.includes('skip') ||
        text.includes('processing') ||
        text.includes('running')
    ){
        return 'warning';
    }

    if(
        text.includes('complete') ||
        text.includes('success') ||
        text === 'ok' ||
        text.includes('done')
    ){
        return 'success';
    }

    return '';
}


function setRestoreOptions(
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
                .filter(Boolean)
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


function buildRestoreFilters(){

    setRestoreOptions(
        'agcp-restore-type',
        restoreRows.map(
            row =>
                row.type || ''
        ),
        'ALL TYPES'
    );

    setRestoreOptions(
        'agcp-restore-status',
        restoreRows.map(
            row =>
                row.status || ''
        ),
        'ALL STATUS'
    );
}


function renderRestores(){

    const search=
        String(
            id(
                'agcp-restore-search'
            ).value || ''
        )
        .trim()
        .toLowerCase();

    const type=
        String(
            id(
                'agcp-restore-type'
            ).value || ''
        )
        .toUpperCase();

    const status=
        String(
            id(
                'agcp-restore-status'
            ).value || ''
        )
        .toUpperCase();

    const rows=
        restoreRows.filter(
            function(row){

                const rowType=
                    String(
                        row.type || ''
                    )
                    .toUpperCase();

                const rowStatus=
                    String(
                        row.status || ''
                    )
                    .toUpperCase();

                if(
                    type &&
                    rowType !== type
                ){
                    return false;
                }

                if(
                    status &&
                    rowStatus !== status
                ){
                    return false;
                }

                if(search){

                    const text=[
                        row.type,
                        row.status,
                        row.source,
                        row.target,
                        row.requestedBy,
                        row.created,
                        row.finished,
                        row.detail,
                        row.jobId
                    ]
                    .map(
                        value =>
                            String(
                                value || ''
                            )
                    )
                    .join(' ')
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

    id(
        'agcp-restore-count'
    ).textContent=
        rows.length +
        (
            rows.length === 1
            ? ' RECORD'
            : ' RECORDS'
        );

    const target=
        id(
            'agcp-restore-list'
        );

    if(!rows.length){

        target.innerHTML=
            '<div class="agcp-empty">' +
            (
                restoreRows.length
                ? 'No restore records match the current filters.'
                : 'No restore history records found.'
            ) +
            '</div>';

        restoreMessage(
            restoreRows.length
            ?
            (
                'Showing 0 of '+
                restoreRows.length+
                ' restore records.'
            )
            :
            'Restore history is empty.',
            'good'
        );

        return;
    }

    target.innerHTML=
        rows.map(
            function(row){

                const state=
                    restoreState(
                        row.status
                    );

                const created=
                    String(
                        row.created || '-'
                    );

                const finished=
                    String(
                        row.finished || ''
                    );

                return (
                    '<div class="agcp-restore-row '+
                    esc(state)+
                    '">' +

                        '<div>' +
                            '<span class="agcp-restore-badge '+
                            esc(state)+
                            '">' +
                                esc(
                                    row.status ||
                                    'UNKNOWN'
                                ) +
                            '</span>' +
                        '</div>' +

                        '<div class="agcp-restore-data">' +
                            esc(
                                row.type || '-'
                            ) +
                        '</div>' +

                        '<div class="agcp-restore-data">' +
                            esc(
                                row.source || '-'
                            ) +
                        '</div>' +

                        '<div class="agcp-restore-data">' +
                            esc(
                                row.target || '-'
                            ) +
                        '</div>' +

                        '<div class="agcp-restore-data">' +
                            esc(
                                row.requestedBy || '-'
                            ) +
                        '</div>' +

                        '<div class="agcp-restore-data">' +
                            esc(created) +

                            (
                                finished
                                ?
                                (
                                    '<div class="agcp-restore-sub">' +
                                        'Finished: '+
                                        esc(finished) +
                                    '</div>'
                                )
                                :
                                ''
                            ) +
                        '</div>' +

                        (
                            row.detail ||
                            row.jobId
                            ?
                            (
                                '<div class="agcp-restore-detail">' +

                                    (
                                        row.jobId
                                        ?
                                        (
                                            'JOB: '+
                                            esc(row.jobId)+
                                            ' | '
                                        )
                                        :
                                        ''
                                    ) +

                                    esc(
                                        row.detail || ''
                                    ) +

                                '</div>'
                            )
                            :
                            ''
                        ) +

                    '</div>'
                );
            }
        ).join('');

    restoreMessage(
        rows.length ===
        restoreRows.length
        ?
        (
            'Restore history loaded. '+
            rows.length+
            (
                rows.length === 1
                ? ' record.'
                : ' records.'
            )
        )
        :
        (
            'Showing '+
            rows.length+
            ' of '+
            restoreRows.length+
            ' restore records.'
        ),
        'good'
    );
}


async function loadRestores(){

    restoreMessage(
        'Loading restore history...',
        ''
    );

    try{

        restoreData=
            await json(
                '/Other/restore-history.php?nocache='+
                Date.now()
            );

        restoreRows=
            restoreData &&
            Array.isArray(
                restoreData.history
            )
            ? restoreData.history
            : [];

        buildRestoreFilters();
        renderRestores();

    }catch(error){

        restoreData={};
        restoreRows=[];

        renderRestores();

        restoreMessage(
            error.message ||
            'Unable to load restore history.',
            'bad'
        );
    }
}


async function clearRestores(){

    const confirmed=
        await confirmBox(
            'Clear Restore History',
            'Clear the restore history?',
            'This clears the restore history log.'
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
        'agcp-restore-clear'
    ).disabled=true;

    try{

        await json(
            '/Other/restore-history.php',
            {
                method:'POST',

                headers:{
                    'Content-Type':
                        'application/x-www-form-urlencoded;charset=UTF-8'
                },

                body:body
            }
        );

        await loadRestores();

    }catch(error){

        restoreMessage(
            error.message ||
            'Unable to clear restore history.',
            'bad'
        );

    }finally{

        id(
            'agcp-restore-clear'
        ).disabled=false;
    }
}


/* ============================================================
   USER IAR
   ============================================================ */

function userIarMessage(
    text,
    state
){

    const box=
        id(
            'agcp-user-iar-message'
        );

    box.className=
        'agcp-message' +
        (
            state
            ? ' '+state
            : ''
        );

    box.textContent=
        text;
}


function avatarName(user){

    const direct=
        pick(
            user,
            [
                'AvatarName',
                'avatar',
                'name',
                'Name'
            ],
            ''
        );

    if(direct){
        return String(direct);
    }

    const first=
        pick(
            user,
            [
                'FirstName',
                'firstname',
                'first'
            ],
            ''
        );

    const last=
        pick(
            user,
            [
                'LastName',
                'lastname',
                'last'
            ],
            ''
        );

    return (
        String(first)+
        ' '+
        String(last)
    ).trim();
}


function renderUsers(){

    const select=
        id(
            'agcp-user-iar-select'
        );

    const query=
        String(
            id(
                'agcp-user-iar-search'
            ).value || ''
        )
        .trim()
        .toLowerCase();

    const current=
        select.value;

    const filtered=
        userNames.filter(
            name =>
                !query ||
                name
                .toLowerCase()
                .includes(query)
        );

    select.innerHTML=
        '<option value="">' +
        (
            filtered.length
            ? 'SELECT LOCAL AVATAR'
            : 'NO MATCHING AVATARS'
        ) +
        '</option>' +

        filtered.map(
            name =>
                '<option value="' +
                esc(name) +
                '">' +
                esc(name) +
                '</option>'
        ).join('');

    if(
        current &&
        filtered.includes(current)
    ){
        select.value=current;
    }

    id(
        'agcp-user-iar-count'
    ).textContent=
        userNames.length;

    updateUserSelection();
}


function updateUserSelection(){

    const avatar=
        id(
            'agcp-user-iar-select'
        ).value;

    id(
        'agcp-user-iar-selected'
    ).textContent=
        avatar ||
        'NONE';

    id(
        'agcp-user-iar-save'
    ).disabled=
        !avatar ||
        iarPolling;
}


async function loadUsers(){

    const select=
        id(
            'agcp-user-iar-select'
        );

    select.innerHTML=
        '<option value="">' +
        'Loading local avatars...' +
        '</option>';

    userIarMessage(
        'Loading local avatars...',
        ''
    );

    try{

        const data=
            await json(
                '/Other/users.php?nocache='+
                Date.now()
            );

        const users=
            listFrom(
                data,
                [
                    'users',
                    'rows',
                    'avatars',
                    'data'
                ]
            );

        userNames=
            users
            .map(avatarName)
            .filter(Boolean)
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

        renderUsers();

        userIarMessage(
            userNames.length+
            ' local avatars loaded.',
            'good'
        );

    }catch(error){

        userNames=[];

        select.innerHTML=
            '<option value="">' +
            'Unable to load users' +
            '</option>';

        id(
            'agcp-user-iar-count'
        ).textContent='0';

        updateUserSelection();

        userIarMessage(
            error.message ||
            'Unable to load local avatars.',
            'bad'
        );
    }
}


function setIarJobState(
    avatar,
    job,
    status,
    archive,
    state
){

    id(
        'agcp-user-iar-job-avatar'
    ).textContent=
        avatar || '-';

    id(
        'agcp-user-iar-job-id'
    ).textContent=
        job || '-';

    id(
        'agcp-user-iar-job-archive'
    ).textContent=
        archive || '-';

    id(
        'agcp-user-iar-job-status'
    ).textContent=
        status || '-';

    id(
        'agcp-user-iar-job'
    ).textContent=
        job
        ? 'ACTIVE'
        : 'IDLE';

    id(
        'agcp-user-iar-result'
    ).textContent=
        status || '-';

    const badge=
        id(
            'agcp-user-iar-state'
        );

    badge.textContent=
        status || 'READY';

    badge.className=
        'agcp-status-pill '+
        (
            state || ''
        );
}


async function startUserIar(){

    const avatar=
        id(
            'agcp-user-iar-select'
        ).value;

    if(!avatar){

        userIarMessage(
            'Select a local avatar first.',
            'bad'
        );

        return;
    }

    const confirmed=
        await confirmBox(
            'Backup User IAR',
            'Create a new server-side inventory archive for this local avatar?',
            avatar
        );

    if(!confirmed){
        return;
    }

    iarPolling=true;

    updateUserSelection();

    setIarJobState(
        avatar,
        '',
        'STARTING',
        '',
        'warn'
    );

    userIarMessage(
        'Starting IAR backup for '+
        avatar+
        '...',
        ''
    );

    try{

        const data=
            await command(
                'AdminSaveIAR',
                {
                    avatarname:
                        avatar
                }
            );

        const job=
            data.iar &&
            data.iar.jobId
            ? data.iar.jobId
            : data.jobId;

        userIarMessage(
            data.message ||
            (
                'IAR backup started for '+
                avatar+
                '.'
            ),
            'good'
        );

        if(job){

            setIarJobState(
                avatar,
                job,
                'PROCESSING',
                '',
                'warn'
            );

            pollUserIar(
                job,
                avatar
            );

        }else{

            iarPolling=false;

            setIarJobState(
                avatar,
                '',
                'STARTED',
                '',
                'good'
            );

            updateUserSelection();
        }

    }catch(error){

        iarPolling=false;

        setIarJobState(
            avatar,
            '',
            'FAILED',
            '',
            'bad'
        );

        updateUserSelection();

        userIarMessage(
            error.message ||
            'Unable to start IAR backup.',
            'bad'
        );
    }
}


async function pollUserIar(
    job,
    avatar
){

    try{

        const data=
            await json(
                '/Other/iar-job-status.php?job='+
                encodeURIComponent(job)+
                '&nocache='+
                Date.now()
            );

        const archive=
            String(
                data.archive ||
                data.file ||
                data.filename ||
                data.path ||
                ''
            );

        const complete=
            !!data.verified ||
            !!data.complete;

        const failed=
            !!data.failed;

        let status=
            'PROCESSING';

        let state=
            'warn';

        if(complete){

            status=
                'COMPLETED';

            state=
                'good';
        }

        if(failed){

            status=
                'FAILED';

            state=
                'bad';
        }

        setIarJobState(
            avatar,
            job,
            status,
            archive,
            state
        );

        userIarMessage(
            data.message ||
            (
                complete
                ? 'IAR backup completed successfully.'
                :
                (
                    failed
                    ? 'IAR backup failed.'
                    :
                    'IAR backup processing for '+
                    avatar+
                    '...'
                )
            ),
            failed
            ? 'bad'
            :
            (
                complete
                ? 'good'
                : ''
            )
        );

        if(
            complete ||
            failed
        ){

            iarPolling=false;

            updateUserSelection();

            return;
        }

        setTimeout(
            function(){

                pollUserIar(
                    job,
                    avatar
                );
            },
            4000
        );

    }catch(error){

        iarPolling=false;

        updateUserSelection();

        setIarJobState(
            avatar,
            job,
            'ERROR',
            '',
            'bad'
        );

        userIarMessage(
            error.message ||
            'Unable to read IAR job status.',
            'bad'
        );
    }
}


/* ============================================================
   EVENTS
   ============================================================ */

id(
    'agcp-restore-refresh'
).addEventListener(
    'click',
    loadRestores
);

id(
    'agcp-restore-clear'
).addEventListener(
    'click',
    clearRestores
);

id(
    'agcp-restore-search'
).addEventListener(
    'input',
    renderRestores
);

id(
    'agcp-restore-type'
).addEventListener(
    'change',
    renderRestores
);

id(
    'agcp-restore-status'
).addEventListener(
    'change',
    renderRestores
);


id(
    'agcp-user-iar-refresh'
).addEventListener(
    'click',
    loadUsers
);

id(
    'agcp-user-iar-search'
).addEventListener(
    'input',
    renderUsers
);

id(
    'agcp-user-iar-select'
).addEventListener(
    'change',
    updateUserSelection
);

id(
    'agcp-user-iar-save'
).addEventListener(
    'click',
    startUserIar
);


/* ============================================================
   NAVIGATION LOADERS
   ============================================================ */

const restoreNav=
    document.querySelector(
        '.agcp-nav[data-page="restore-history"]'
    );

if(restoreNav){

    restoreNav.addEventListener(
        'click',
        function(){

            setTimeout(
                loadRestores,
                0
            );
        }
    );
}


const userIarNav=
    document.querySelector(
        '.agcp-nav[data-page="user-iar"]'
    );

if(userIarNav){

    userIarNav.addEventListener(
        'click',
        function(){

            setTimeout(
                loadUsers,
                0
            );
        }
    );
}


const restorePage=
    id(
        'agcp-page-restore-history'
    );

if(
    restorePage &&
    restorePage.classList.contains(
        'active'
    )
){
    loadRestores();
}


const userIarPage=
    id(
        'agcp-page-user-iar'
    );

if(
    userIarPage &&
    userIarPage.classList.contains(
        'active'
    )
){
    loadUsers();
}


})();