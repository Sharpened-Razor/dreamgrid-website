(function(){

'use strict';

let storageData={};


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
            'Invalid storage response.'
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
            'Storage request failed.'
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
        unit <
        units.length - 1
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


function percent(
    value,
    total
){

    const a=
        Number(value);

    const b=
        Number(total);

    if(
        !Number.isFinite(a) ||
        !Number.isFinite(b) ||
        b <= 0
    ){
        return 0;
    }

    return Math.max(
        0,
        Math.min(
            100,
            a /
            b *
            100
        )
    );
}


function percentText(value){

    const number=
        Number(value);

    if(!Number.isFinite(number)){
        return '-';
    }

    return (
        number < 10
        ? number.toFixed(1)
        : number.toFixed(0)
    ) +
    '%';
}


function message(text,state){

    const box=
        id(
            'agcp-storage-message'
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


function renderGroups(
    targetId,
    groups,
    totalBytes
){

    const target=
        id(targetId);

    if(
        !Array.isArray(groups) ||
        !groups.length
    ){

        target.innerHTML=
            '<div class="agcp-empty">' +
            'No archive groups found.' +
            '</div>';

        return;
    }

    const source=
        groups
        .slice()
        .sort(
            function(a,b){

                return (
                    Number(
                        b.bytes || 0
                    )
                    -
                    Number(
                        a.bytes || 0
                    )
                );
            }
        );

    target.innerHTML=
        source.map(
            function(item){

                const itemBytes=
                    Number(
                        item.bytes || 0
                    );

                const count=
                    Number(
                        item.count ||
                        item.files ||
                        item.fileCount ||
                        0
                    );

                const share=
                    percent(
                        itemBytes,
                        totalBytes
                    );

                return (
                    '<div class="agcp-storage-group-row">' +

                        '<div class="agcp-storage-group-name">' +
                            esc(
                                item.name ||
                                item.region ||
                                item.owner ||
                                'Unknown'
                            ) +
                        '</div>' +

                        '<div class="agcp-storage-group-data">' +
                            esc(
                                count.toLocaleString()
                            ) +
                            ' FILES' +
                        '</div>' +

                        '<div class="agcp-storage-group-data">' +
                            esc(
                                bytes(
                                    itemBytes
                                )
                            ) +
                        '</div>' +

                        '<div class="agcp-storage-group-data">' +
                            esc(
                                percentText(
                                    share
                                )
                            ) +
                        '</div>' +

                    '</div>'
                );
            }
        ).join('');
}


function render(data){

    const summary=
        data &&
        data.summary &&
        typeof data.summary ===
        'object'
        ? data.summary
        : {};

    const oarCount=
        Number(
            summary.oarCount || 0
        );

    const iarCount=
        Number(
            summary.iarCount || 0
        );

    const oarBytes=
        Number(
            summary.oarBytes || 0
        );

    const iarBytes=
        Number(
            summary.iarBytes || 0
        );

    const backupBytes=
        Number(
            summary.backupBytes || 0
        );

    const driveFree=
        Number(
            summary.driveFreeBytes
        );

    const driveTotal=
        Number(
            summary.driveTotalBytes
        );

    const haveDrive=
        Number.isFinite(
            driveFree
        ) &&
        Number.isFinite(
            driveTotal
        ) &&
        driveTotal > 0;

    const driveUsed=
        haveDrive
        ?
        Math.max(
            0,
            driveTotal -
            driveFree
        )
        :
        0;

    const usedPercent=
        haveDrive
        ?
        percent(
            driveUsed,
            driveTotal
        )
        :
        0;

    const freePercent=
        haveDrive
        ?
        percent(
            driveFree,
            driveTotal
        )
        :
        0;

    const oarPercent=
        percent(
            oarBytes,
            backupBytes
        );

    const iarPercent=
        percent(
            iarBytes,
            backupBytes
        );

    id(
        'agcp-storage-backup-bytes'
    ).textContent=
        bytes(
            backupBytes
        );

    id(
        'agcp-storage-oar-bytes'
    ).textContent=
        bytes(
            oarBytes
        );

    id(
        'agcp-storage-iar-bytes'
    ).textContent=
        bytes(
            iarBytes
        );

    id(
        'agcp-storage-drive-free'
    ).textContent=
        haveDrive
        ?
        bytes(
            driveFree
        )
        :
        'NOT REPORTED';

    id(
        'agcp-storage-drive-used'
    ).textContent=
        haveDrive
        ?
        bytes(
            driveUsed
        )
        :
        '-';

    id(
        'agcp-storage-drive-total'
    ).textContent=
        haveDrive
        ?
        bytes(
            driveTotal
        )
        :
        '-';

    id(
        'agcp-storage-free-percent'
    ).textContent=
        haveDrive
        ?
        percentText(
            freePercent
        )
        :
        '-';

    const driveBar=
        id(
            'agcp-storage-drive-bar'
        );

    driveBar.style.width=
        usedPercent +
        '%';

    driveBar.className=
        'agcp-storage-progress-bar';

    const state=
        id(
            'agcp-storage-state'
        );

    const GB=
        1024 *
        1024 *
        1024;

    if(!haveDrive){

        state.textContent=
            'NOT REPORTED';

        state.className=
            'agcp-status-pill warn';

        id(
            'agcp-storage-capacity-copy'
        ).textContent=
            'Drive capacity information is unavailable.';

    }else if(
        freePercent < 5 ||
        driveFree <
        20 * GB
    ){

        state.textContent=
            'CRITICAL';

        state.className=
            'agcp-status-pill bad';

        driveBar.classList.add(
            'bad'
        );

        id(
            'agcp-storage-capacity-copy'
        ).textContent=
            bytes(driveFree) +
            ' free of ' +
            bytes(driveTotal) +
            '. Backup drive requires attention.';

    }else if(
        freePercent < 10 ||
        driveFree <
        50 * GB
    ){

        state.textContent=
            'LOW SPACE';

        state.className=
            'agcp-status-pill warn';

        driveBar.classList.add(
            'warn'
        );

        id(
            'agcp-storage-capacity-copy'
        ).textContent=
            bytes(driveFree) +
            ' free of ' +
            bytes(driveTotal) +
            '. Drive free space is getting low.';

    }else{

        state.textContent=
            'NORMAL';

        state.className=
            'agcp-status-pill good';

        id(
            'agcp-storage-capacity-copy'
        ).textContent=
            bytes(driveFree) +
            ' free of ' +
            bytes(driveTotal) +
            ' total drive capacity.';
    }

    id(
        'agcp-storage-oar-count'
    ).textContent=
        oarCount.toLocaleString() +
        ' OARS';

    id(
        'agcp-storage-iar-count'
    ).textContent=
        iarCount.toLocaleString() +
        ' IARS';

    id(
        'agcp-storage-oar-percent'
    ).textContent=
        percentText(
            oarPercent
        );

    id(
        'agcp-storage-iar-percent'
    ).textContent=
        percentText(
            iarPercent
        );

    id(
        'agcp-storage-oar-bar'
    ).style.width=
        oarPercent +
        '%';

    id(
        'agcp-storage-iar-bar'
    ).style.width=
        iarPercent +
        '%';

    const oarGroups=
        Array.isArray(
            data.oarGroups
        )
        ? data.oarGroups
        : [];

    const iarGroups=
        Array.isArray(
            data.iarGroups
        )
        ? data.iarGroups
        : [];

    id(
        'agcp-storage-oar-groups-count'
    ).textContent=
        oarGroups.length.toLocaleString() +
        (
            oarGroups.length === 1
            ? ' REGION'
            : ' REGIONS'
        );

    id(
        'agcp-storage-iar-groups-count'
    ).textContent=
        iarGroups.length.toLocaleString() +
        (
            iarGroups.length === 1
            ? ' OWNER'
            : ' OWNERS'
        );

    const largestOar=
        oarGroups
        .slice()
        .sort(
            function(a,b){

                return (
                    Number(
                        b.bytes || 0
                    )
                    -
                    Number(
                        a.bytes || 0
                    )
                );
            }
        )[0];

    const largestIar=
        iarGroups
        .slice()
        .sort(
            function(a,b){

                return (
                    Number(
                        b.bytes || 0
                    )
                    -
                    Number(
                        a.bytes || 0
                    )
                );
            }
        )[0];

    id(
        'agcp-storage-largest-oar'
    ).textContent=
        largestOar
        ?
        (
            (
                largestOar.name ||
                largestOar.region ||
                'Unknown'
            ) +
            ' - ' +
            bytes(
                largestOar.bytes
            )
        )
        :
        'NONE';

    id(
        'agcp-storage-largest-iar'
    ).textContent=
        largestIar
        ?
        (
            (
                largestIar.name ||
                largestIar.owner ||
                'Unknown'
            ) +
            ' - ' +
            bytes(
                largestIar.bytes
            )
        )
        :
        'NONE';

    renderGroups(
        'agcp-storage-oar-groups',
        oarGroups,
        oarBytes
    );

    renderGroups(
        'agcp-storage-iar-groups',
        iarGroups,
        iarBytes
    );

    id(
        'agcp-storage-root'
    ).textContent=
        data.root ||
        '-';

    id(
        'agcp-storage-mode'
    ).textContent=
        data.readOnly === true
        ? 'READ ONLY'
        : 'STANDARD';

    id(
        'agcp-storage-total-files'
    ).textContent=
        (
            oarCount +
            iarCount
        ).toLocaleString();

    id(
        'agcp-storage-refresh-time'
    ).textContent=
        new Date()
        .toLocaleTimeString();
}


async function load(){

    message(
        'Loading backup storage...',
        ''
    );

    try{

        storageData=
            await json(
                '/Other/backup-storage.php?nocache=' +
                Date.now()
            );

        render(
            storageData
        );

        message(
            'Backup storage loaded.',
            'good'
        );

    }catch(error){

        storageData={};

        render(
            storageData
        );

        message(
            error.message ||
            'Unable to load backup storage.',
            'bad'
        );
    }
}


id(
    'agcp-storage-refresh'
).addEventListener(
    'click',
    load
);


const nav=
    document.querySelector(
        '.agcp-nav[data-page="backup-storage"]'
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
        'agcp-page-backup-storage'
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