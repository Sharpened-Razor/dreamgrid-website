(function(){

'use strict';

let myData={};
let activeBackupJob='';
let backupTimer=null;
let restoreTimer=null;
let busy=false;


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


function csrf(){

    return (
        window.AGCP &&
        window.AGCP.iarCsrf
    )
    ?
    String(
        window.AGCP.iarCsrf
    )
    :
    '';
}


function privileged(){

    return !!(
        window.AGCP &&
        window.AGCP.canPrivilegedIar
    );
}


function message(text,state){

    const box=
        id(
            'agcp-my-iar-message'
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


function bytes(value){

    const n=
        Number(
            value || 0
        );

    if(n < 1024){
        return n+' bytes';
    }

    if(n < 1048576){

        return (
            n/1024
        ).toFixed(1)+
        ' KB';
    }

    if(n < 1073741824){

        return (
            n/1048576
        ).toFixed(2)+
        ' MB';
    }

    return (
        n/1073741824
    ).toFixed(2)+
    ' GB';
}


function dateText(value){

    if(!value){
        return '-';
    }

    const date=
        new Date(value);

    if(
        Number.isNaN(
            date.getTime()
        )
    ){
        return String(value);
    }

    return date.toLocaleString();
}


function confirmBox(
    title,
    text,
    detail
){

    if(
        typeof window.AGCPConfirm ===
        'function'
    ){
        return window.AGCPConfirm(
            title,
            text,
            detail
        );
    }

    return Promise.resolve(
        window.confirm(
            text+
            (
                detail
                ? '\n\n'+detail
                : ''
            )
        )
    );
}


async function request(
    action,
    options
){

    const url=
        new URL(
            '/Other/user-iar-backups.php',
            window.location.origin
        );

    url.searchParams.set(
        'api',
        action
    );

    if(
        options &&
        options.job
    ){
        url.searchParams.set(
            'job',
            options.job
        );
    }

    const fetchOptions={
        credentials:'same-origin',
        cache:'no-store'
    };

    if(
        options &&
        options.post
    ){

        fetchOptions.method=
            'POST';

        fetchOptions.headers={
            'Content-Type':
                'application/json',

            'X-CSRF-Token':
                csrf()
        };

        fetchOptions.body=
            JSON.stringify(
                options.body || {}
            );
    }

    const response=
        await fetch(
            url.toString(),
            fetchOptions
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
            'Invalid IAR response.'
        );
    }

    if(
        !response.ok ||
        !data ||
        !data.ok
    ){

        const error=
            new Error(
                data &&
                data.error
                ? data.error
                : 'IAR request failed.'
            );

        error.data=data || {};

        throw error;
    }

    return data;
}


function setBusy(value){

    busy=
        !!value;

    id(
        'agcp-my-iar-save'
    ).disabled=
        busy;

    const file=
        id(
            'agcp-my-iar-upload-file'
        ).files;

    id(
        'agcp-my-iar-upload'
    ).disabled=
        busy ||
        !file ||
        !file.length;
}


function setJob(
    operation,
    job,
    status,
    result,
    state
){

    id(
        'agcp-my-iar-operation'
    ).textContent=
        operation || 'IDLE';

    id(
        'agcp-my-iar-job-id'
    ).textContent=
        job || '-';

    id(
        'agcp-my-iar-job-status'
    ).textContent=
        status || 'READY';

    id(
        'agcp-my-iar-job-result'
    ).textContent=
        result || '-';

    id(
        'agcp-my-iar-job'
    ).textContent=
        job
        ? 'ACTIVE'
        : 'NONE';

    const badge=
        id(
            'agcp-my-iar-state'
        );

    badge.textContent=
        status || 'READY';

    badge.className=
        'agcp-status-pill '+
        (
            state || ''
        );
}


function render(data){

    myData=
        data || {};

    const backups=
        Array.isArray(
            myData.backups
        )
        ? myData.backups
        : [];

    const used=
        Math.min(
            backups.length,
            2
        );

    id(
        'agcp-my-iar-used'
    ).textContent=
        used+
        ' / 2';

    id(
        'agcp-my-iar-available'
    ).textContent=
        Math.max(
            0,
            2-used
        );

    const target=
        id(
            'agcp-my-iar-slots'
        );

    const html=[];

    for(
        let index=0;
        index<2;
        index++
    ){

        const backup=
            backups[index];

        if(!backup){

            html.push(
                '<div class="agcp-my-iar-slot empty">'+
                    'IAR SLOT '+
                    (index+1)+
                    '<br>EMPTY'+
                '</div>'
            );

            continue;
        }

        let actions='';

        if(privileged()){

            if(backup.downloadUrl){

                actions+=
                    '<a '+
                        'class="agcp-my-iar-download" '+
                        'href="'+
                        esc(
                            backup.downloadUrl
                        )+
                        '">'+
                        'DOWNLOAD IAR'+
                    '</a>';
            }

            actions+=
                '<button '+
                    'type="button" '+
                    'class="agcp-my-iar-action" '+
                    'data-agcp-iar-restore="'+
                    esc(
                        backup.jobId
                    )+
                    '">'+
                    'RESTORE'+
                '</button>';

            actions+=
                '<button '+
                    'type="button" '+
                    'class="agcp-my-iar-action" '+
                    'data-agcp-iar-merge="'+
                    esc(
                        backup.jobId
                    )+
                    '">'+
                    'MERGE RESTORE'+
                '</button>';

            actions+=
                '<button '+
                    'type="button" '+
                    'class="agcp-my-iar-action danger" '+
                    'data-agcp-iar-delete="'+
                    esc(
                        backup.jobId
                    )+
                    '">'+
                    'DELETE'+
                '</button>';
        }

        html.push(
            '<div class="agcp-my-iar-slot">'+

                '<div class="agcp-my-iar-slot-head">'+

                    '<span>'+
                        'IAR SLOT '+
                        (index+1)+
                    '</span>'+

                    '<span class="agcp-status-pill good">'+
                        'VERIFIED'+
                    '</span>'+

                '</div>'+

                '<div class="agcp-my-iar-slot-body">'+

                    '<div class="agcp-my-iar-name">'+
                        esc(
                            backup.name ||
                            'Inventory Archive'
                        )+
                    '</div>'+

                    '<div class="agcp-my-iar-meta">'+

                        '<div class="agcp-my-iar-meta-row">'+
                            '<span>CREATED</span>'+
                            '<strong>'+
                                esc(
                                    dateText(
                                        backup.created
                                    )
                                )+
                            '</strong>'+
                        '</div>'+

                        '<div class="agcp-my-iar-meta-row">'+
                            '<span>SIZE</span>'+
                            '<strong>'+
                                esc(
                                    bytes(
                                        backup.size
                                    )
                                )+
                            '</strong>'+
                        '</div>'+

                        '<div class="agcp-my-iar-meta-row">'+
                            '<span>JOB ID</span>'+
                            '<strong>'+
                                esc(
                                    backup.jobId ||
                                    '-'
                                )+
                            '</strong>'+
                        '</div>'+

                        '<div class="agcp-my-iar-meta-row">'+
                            '<span>STATUS</span>'+
                            '<strong>'+
                                'VERIFIED'+
                            '</strong>'+
                        '</div>'+

                    '</div>'+

                    (
                        actions
                        ?
                        (
                            '<div class="agcp-my-iar-actions">'+
                                actions+
                            '</div>'
                        )
                        :
                        ''
                    )+

                '</div>'+

            '</div>'
        );
    }

    target.innerHTML=
        html.join('');

    if(myData.activeJob){

        activeBackupJob=
            String(
                myData.activeJob.jobId ||
                ''
            );

        if(activeBackupJob){

            setBusy(true);

            setJob(
                'BACKUP',
                activeBackupJob,
                'PROCESSING',
                '',
                'warn'
            );

            pollBackup();
        }

    }else if(!busy){

        setJob(
            'IDLE',
            '',
            'READY',
            '',
            'good'
        );
    }
}


async function load(){

    message(
        'Loading IAR backups...',
        ''
    );

    try{

        const data=
            await request(
                'list'
            );

        render(data);

        if(!activeBackupJob){

            message(
                'IAR backup slots ready.',
                'good'
            );
        }

    }catch(error){

        message(
            error.message ||
            'Unable to load IAR backups.',
            'bad'
        );

        setJob(
            'LOAD',
            '',
            'ERROR',
            error.message || '',
            'bad'
        );
    }
}


async function startBackup(
    confirmReplace
){

    if(busy){
        return;
    }

    setBusy(true);

    setJob(
        'BACKUP',
        '',
        'STARTING',
        '',
        'warn'
    );

    message(
        'Starting IAR backup...',
        ''
    );

    try{

        const data=
            await request(
                'start',
                {
                    post:true,

                    body:{
                        confirmReplace:
                            !!confirmReplace
                    }
                }
            );

        activeBackupJob=
            String(
                data.jobId || ''
            );

        setJob(
            'BACKUP',
            activeBackupJob,
            'PROCESSING',
            data.message || '',
            'warn'
        );

        message(
            data.message ||
            'IAR backup started.',
            ''
        );

        pollBackup();

    }catch(error){

        if(
            error.data &&
            error.data.needsConfirmation &&
            !confirmReplace
        ){

            const oldest=
                error.data.oldest || {};

            const confirmed=
                await confirmBox(
                    'Replace Oldest IAR',
                    'Both IAR slots are full. Create a new IAR and safely replace the oldest slot only after the new backup succeeds and verifies?',
                    oldest.name || ''
                );

            if(confirmed){

                setBusy(false);

                return startBackup(
                    true
                );
            }
        }

        activeBackupJob='';

        setBusy(false);

        setJob(
            'BACKUP',
            '',
            'FAILED',
            error.message || '',
            'bad'
        );

        message(
            error.message ||
            'Unable to start IAR backup.',
            'bad'
        );
    }
}


function pollBackup(){

    if(!activeBackupJob){
        return;
    }

    if(backupTimer){

        clearTimeout(
            backupTimer
        );
    }

    backupTimer=
        setTimeout(
            async function(){

                try{

                    const data=
                        await request(
                            'status',
                            {
                                job:
                                    activeBackupJob
                            }
                        );

                    const verified=
                        !!data.verified;

                    const failed=
                        !!data.failed;

                    setJob(
                        'BACKUP',
                        activeBackupJob,
                        verified
                        ? 'COMPLETED'
                        :
                        (
                            failed
                            ? 'FAILED'
                            : 'PROCESSING'
                        ),
                        data.message || '',
                        verified
                        ? 'good'
                        :
                        (
                            failed
                            ? 'bad'
                            : 'warn'
                        )
                    );

                    message(
                        data.message ||
                        (
                            verified
                            ? 'IAR backup completed successfully.'
                            :
                            (
                                failed
                                ? 'IAR backup failed.'
                                : 'IAR backup processing...'
                            )
                        ),
                        verified
                        ? 'good'
                        :
                        (
                            failed
                            ? 'bad'
                            : ''
                        )
                    );

                    if(
                        verified ||
                        failed
                    ){

                        activeBackupJob='';

                        setBusy(false);

                        if(verified){

                            setTimeout(
                                load,
                                500
                            );
                        }

                        return;
                    }

                    pollBackup();

                }catch(error){

                    message(
                        error.message ||
                        'Unable to read IAR job status.',
                        'bad'
                    );

                    pollBackup();
                }

            },
            4000
        );
}


async function deleteBackup(job){

    const confirmed=
        await confirmBox(
            'Delete IAR Backup',
            'Delete this IAR backup?',
            'This cannot be undone.'
        );

    if(!confirmed){
        return;
    }

    setBusy(true);

    setJob(
        'DELETE',
        job,
        'PROCESSING',
        '',
        'warn'
    );

    try{

        const data=
            await request(
                'delete',
                {
                    post:true,

                    body:{
                        jobId:
                            job
                    }
                }
            );

        message(
            data.message ||
            'IAR backup deleted.',
            'good'
        );

        setJob(
            'DELETE',
            job,
            'COMPLETED',
            data.message || '',
            'good'
        );

        setBusy(false);

        await load();

    }catch(error){

        setBusy(false);

        setJob(
            'DELETE',
            job,
            'FAILED',
            error.message || '',
            'bad'
        );

        message(
            error.message ||
            'Unable to delete IAR backup.',
            'bad'
        );
    }
}


async function restoreBackup(
    job,
    merge
){

    const confirmed=
        await confirmBox(
            merge
            ? 'Merge IAR Restore'
            : 'Restore IAR Backup',

            merge
            ? 'Merge this IAR into your current inventory?'
            : 'Restore this IAR backup?',

            merge
            ? 'The archive will be merged with your current inventory.'
            : 'The selected archive will be restored.'
        );

    if(!confirmed){
        return;
    }

    setBusy(true);

    setJob(
        merge
        ? 'MERGE RESTORE'
        : 'RESTORE',
        job,
        'STARTING',
        '',
        'warn'
    );

    try{

        const data=
            await request(
                'restore-start',
                {
                    post:true,

                    job:job,

                    body:{
                        merge:
                            !!merge
                    }
                }
            );

        const restoreJob=
            String(
                data.restoreJobId ||
                data.jobId ||
                ''
            );

        message(
            data.message ||
            'IAR restore started.',
            ''
        );

        setJob(
            merge
            ? 'MERGE RESTORE'
            : 'RESTORE',
            restoreJob,
            'PROCESSING',
            data.message || '',
            'warn'
        );

        if(restoreJob){

            pollRestore(
                restoreJob,
                merge
            );

        }else{

            setBusy(false);
        }

    }catch(error){

        setBusy(false);

        setJob(
            merge
            ? 'MERGE RESTORE'
            : 'RESTORE',
            job,
            'FAILED',
            error.message || '',
            'bad'
        );

        message(
            error.message ||
            'Unable to start IAR restore.',
            'bad'
        );
    }
}


function pollRestore(
    job,
    merge
){

    if(restoreTimer){

        clearTimeout(
            restoreTimer
        );
    }

    restoreTimer=
        setTimeout(
            async function(){

                try{

                    const data=
                        await request(
                            'restore-status',
                            {
                                job:job
                            }
                        );

                    const complete=
                        !!data.complete;

                    const failed=
                        !!data.failed;

                    setJob(
                        merge
                        ? 'MERGE RESTORE'
                        : 'RESTORE',
                        job,
                        complete
                        ? 'COMPLETED'
                        :
                        (
                            failed
                            ? 'FAILED'
                            : 'PROCESSING'
                        ),
                        data.message || '',
                        complete
                        ? 'good'
                        :
                        (
                            failed
                            ? 'bad'
                            : 'warn'
                        )
                    );

                    message(
                        data.message ||
                        (
                            complete
                            ? 'IAR restore completed.'
                            :
                            (
                                failed
                                ? 'IAR restore failed.'
                                : 'IAR restore processing...'
                            )
                        ),
                        complete
                        ? 'good'
                        :
                        (
                            failed
                            ? 'bad'
                            : ''
                        )
                    );

                    if(
                        complete ||
                        failed
                    ){

                        setBusy(false);

                        return;
                    }

                    pollRestore(
                        job,
                        merge
                    );

                }catch(error){

                    message(
                        error.message ||
                        'Unable to read IAR restore status.',
                        'bad'
                    );

                    pollRestore(
                        job,
                        merge
                    );
                }

            },
            4000
        );
}


async function uploadBackup(
    confirmReplace
){

    if(busy){
        return;
    }

    const input=
        id(
            'agcp-my-iar-upload-file'
        );

    const file=
        input.files &&
        input.files[0];

    if(!file){

        message(
            'Select an .iar file first.',
            'bad'
        );

        return;
    }

    if(
        !String(
            file.name || ''
        )
        .toLowerCase()
        .endsWith('.iar')
    ){

        message(
            'Only .iar files can be uploaded.',
            'bad'
        );

        return;
    }

    const form=
        new FormData();

    form.append(
        'iarFile',
        file,
        file.name
    );

    form.append(
        'confirmReplace',
        confirmReplace
        ? '1'
        : '0'
    );

    const url=
        new URL(
            '/Other/user-iar-backups.php',
            window.location.origin
        );

    url.searchParams.set(
        'api',
        'upload'
    );

    setBusy(true);

    setJob(
        'UPLOAD',
        '',
        'UPLOADING',
        file.name,
        'warn'
    );

    message(
        'Uploading IAR...',
        ''
    );

    try{

        const response=
            await fetch(
                url.toString(),
                {
                    method:'POST',

                    credentials:
                        'same-origin',

                    cache:
                        'no-store',

                    headers:{
                        'X-CSRF-Token':
                            csrf()
                    },

                    body:form
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
                'Invalid IAR upload response.'
            );
        }

        if(
            !response.ok ||
            !data ||
            !data.ok
        ){

            const error=
                new Error(
                    data &&
                    data.error
                    ? data.error
                    : 'IAR upload failed.'
                );

            error.data=
                data || {};

            throw error;
        }

        input.value='';

        setBusy(false);

        setJob(
            'UPLOAD',
            '',
            'COMPLETED',
            data.message || '',
            'good'
        );

        message(
            data.message ||
            'IAR uploaded.',
            'good'
        );

        await load();

    }catch(error){

        if(
            error.data &&
            error.data.needsConfirmation &&
            !confirmReplace
        ){

            const confirmed=
                await confirmBox(
                    'Replace Oldest IAR',
                    'Both IAR slots are full. Upload this IAR and safely replace the oldest slot after verification?',
                    file.name
                );

            if(confirmed){

                setBusy(false);

                return uploadBackup(
                    true
                );
            }
        }

        setBusy(false);

        setJob(
            'UPLOAD',
            '',
            'FAILED',
            error.message || '',
            'bad'
        );

        message(
            error.message ||
            'IAR upload failed.',
            'bad'
        );
    }
}


/* ============================================================
   EVENTS
   ============================================================ */

id(
    'agcp-my-iar-refresh'
).addEventListener(
    'click',
    load
);


id(
    'agcp-my-iar-save'
).addEventListener(
    'click',
    function(){

        startBackup(false);
    }
);


id(
    'agcp-my-iar-upload-file'
).addEventListener(
    'change',
    function(){

        setBusy(busy);
    }
);


id(
    'agcp-my-iar-upload'
).addEventListener(
    'click',
    function(){

        uploadBackup(false);
    }
);


id(
    'agcp-my-iar-slots'
).addEventListener(
    'click',
    function(event){

        const deleteButton=
            event.target.closest(
                '[data-agcp-iar-delete]'
            );

        if(deleteButton){

            deleteBackup(
                deleteButton.getAttribute(
                    'data-agcp-iar-delete'
                )
            );

            return;
        }


        const restoreButton=
            event.target.closest(
                '[data-agcp-iar-restore]'
            );

        if(restoreButton){

            restoreBackup(
                restoreButton.getAttribute(
                    'data-agcp-iar-restore'
                ),
                false
            );

            return;
        }


        const mergeButton=
            event.target.closest(
                '[data-agcp-iar-merge]'
            );

        if(mergeButton){

            restoreBackup(
                mergeButton.getAttribute(
                    'data-agcp-iar-merge'
                ),
                true
            );
        }
    }
);


const nav=
    document.querySelector(
        '.agcp-nav[data-page="my-iar"]'
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
        'agcp-page-my-iar'
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