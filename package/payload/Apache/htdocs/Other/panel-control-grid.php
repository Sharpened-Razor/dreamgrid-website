<?php

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

$session =
    ag_require_admin();

$avatar =
    ag_avatar_name(
        $session
    );

$level =
    ag_user_level(
        $session
    );

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>Grid Control</title>

<style>

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    min-height:100%;
    background:#0b0c0d;
    color:#e7e7e7;
    font-family:
        Arial,
        Helvetica,
        sans-serif;
}

body{
    padding:18px;
}

.gc-page{
    width:100%;
    max-width:1500px;
    margin:0 auto;
}


/* ============================================================
   HEADER
   ============================================================ */

.gc-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;

    margin-bottom:14px;
    padding:
        4px 2px 14px;

    border-bottom:
        1px solid #4b3c19;
}

.gc-kicker{
    color:#92752f;

    font-size:9px;
    font-weight:900;

    letter-spacing:.17em;
}

.gc-title{
    margin-top:4px;

    color:#e5b94f;

    font-size:22px;
    font-weight:900;

    letter-spacing:.03em;
}

.gc-subtitle{
    margin-top:5px;

    color:#82898d;

    font-size:11px;
}

.gc-user{
    padding:
        8px 12px;

    border:
        1px solid #3e3420;

    border-radius:
        4px;

    background:#111315;

    color:#a7aaac;

    font-size:10px;
    font-weight:800;

    white-space:nowrap;
}

.gc-user strong{
    color:#dfb754;
}


/* ============================================================
   PANEL
   ============================================================ */

.gc-panel{
    border:
        1px solid #302b1f;

    border-radius:
        5px;

    background:#111315;
}

.gc-panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;

    min-height:42px;

    padding:
        0 13px;

    border-bottom:
        1px solid #302b1f;
}

.gc-panel-title{
    color:#d6a83e;

    font-size:10px;
    font-weight:900;

    letter-spacing:.12em;
}

.gc-refresh{
    min-width:92px;

    padding:
        7px 12px;

    border:
        1px solid #5b4920;

    border-radius:
        4px;

    background:#191a1b;

    color:#ddb451;

    font-size:9px;
    font-weight:900;

    cursor:pointer;
}

.gc-refresh:hover{
    border-color:#a17d2d;
    background:#22201a;
}

.gc-refresh:disabled{
    opacity:.4;
    cursor:not-allowed;
}


/* ============================================================
   SUMMARY
   ============================================================ */

.gc-stats{
    display:grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap:8px;

    padding:12px;
}

.gc-stat{
    min-width:0;

    padding:
        12px 13px;

    border:
        1px solid #292b2c;

    border-radius:
        4px;

    background:#17191a;
}

.gc-stat-value{
    display:block;

    color:#efc45d;

    font-size:22px;
    font-weight:900;

    line-height:1;
}

.gc-stat-label{
    display:block;

    margin-top:7px;

    color:#868c90;

    font-size:9px;
    font-weight:900;

    letter-spacing:.08em;
}


/* ============================================================
   SELECTION
   ============================================================ */

.gc-selection{
    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:7px;

    padding:
        0 12px 10px;
}

.gc-button{
    min-height:34px;

    border:
        1px solid #353738;

    border-radius:
        4px;

    background:#181a1b;

    color:#c8ccce;

    font-size:9px;
    font-weight:900;

    letter-spacing:.04em;

    cursor:pointer;
}

.gc-button:hover:not(:disabled){
    border-color:#7d6228;
    color:#edc25b;
    background:#211f1a;
}

.gc-button:disabled{
    opacity:.32;
    cursor:not-allowed;
}

.gc-selected-line{
    padding:
        0 13px 11px;

    color:#949a9e;

    font-size:10px;
    font-weight:800;
}


/* ============================================================
   COMMANDS
   ============================================================ */

.gc-actions{
    display:grid;

    grid-template-columns:
        repeat(3,minmax(0,1fr));

    gap:7px;

    padding:
        0 12px 12px;
}

.gc-actions .gc-button{
    min-height:36px;
}

.gc-restart-all{
    border-color:#73591f;
    color:#e2b84e;
}

.gc-warning{
    margin:
        0 12px 12px;

    padding:
        10px 12px;

    border:
        1px solid #52431f;

    border-radius:
        4px;

    background:#17150f;

    color:#ad934d;

    font-size:9px;
    font-weight:700;

    line-height:1.5;
}


/* ============================================================
   RESULT
   ============================================================ */

.gc-result{
    margin:
        0 12px 12px;

    padding:
        10px 12px;

    border:
        1px solid #2d3335;

    border-radius:
        4px;

    background:#0c0e0f;

    color:#a6adb0;

    font-family:
        Consolas,
        "Courier New",
        monospace;

    font-size:10px;

    white-space:pre-wrap;
}

.gc-result.error{
    border-color:#653030;
    color:#e79494;
}


/* ============================================================
   REGIONS
   ============================================================ */

.gc-regions{
    padding:
        0 12px 12px;
}

.gc-region{
    display:grid;

    grid-template-columns:
        28px
        minmax(180px,1.4fr)
        110px
        minmax(140px,1fr)
        80px
        100px;

    align-items:center;

    gap:10px;

    min-height:48px;

    margin-top:7px;

    padding:
        8px 11px;

    border:
        1px solid #2c3132;

    border-radius:
        4px;

    background:#151819;
}

.gc-region:hover{
    border-color:#594820;
    background:#181a1b;
}

.gc-region.selected{
    border-color:#816526;
    background:#1b1a16;
}

.gc-check{
    width:15px;
    height:15px;

    accent-color:#c49b38;
}

.gc-region-name{
    min-width:0;

    color:#e3e5e6;

    font-size:12px;
    font-weight:900;

    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.gc-status{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    width:max-content;
    min-width:62px;

    padding:
        4px 7px;

    border-radius:
        999px;

    border:
        1px solid #414546;

    background:#202324;

    color:#b6bcbe;

    font-size:8px;
    font-weight:900;

    letter-spacing:.05em;

    text-transform:uppercase;
}

.gc-status.running{
    border-color:#255638;
    background:#12281b;
    color:#70d093;
}

.gc-status.stopped{
    border-color:#623333;
    background:#2b1717;
    color:#dd8585;
}

.gc-owner,
.gc-number{
    color:#90979a;

    font-size:9px;
    font-weight:700;

    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.gc-number{
    text-align:right;
}

.gc-empty{
    padding:
        28px 15px;

    color:#7d8589;

    text-align:center;

    font-size:11px;
    font-weight:800;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media(max-width:1000px){

    .gc-actions{
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

    .gc-region{
        grid-template-columns:
            28px
            minmax(150px,1fr)
            90px
            minmax(120px,1fr);

        gap:8px;
    }

    .gc-number{
        display:none;
    }
}

@media(max-width:700px){

    body{
        padding:10px;
    }

    .gc-head{
        align-items:flex-start;
        flex-direction:column;
    }

    .gc-stats{
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

    .gc-selection,
    .gc-actions{
        grid-template-columns:
            1fr;
    }

    .gc-region{
        grid-template-columns:
            24px
            minmax(120px,1fr)
            80px;
    }

    .gc-owner{
        display:none;
    }
}

</style>

</head>


<body>

<main class="gc-page">


    <header class="gc-head">

        <div>

            <div class="gc-kicker">
                CONTROL PANEL
            </div>

            <div class="gc-title">
                Grid Control
            </div>

            <div class="gc-subtitle">
                Live DreamGrid region controls.
            </div>

        </div>

        <div class="gc-user">

            GRID OWNER ·

            <strong>
                <?=htmlspecialchars(
                    $avatar,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                )?>
            </strong>

            · LEVEL
            <?=htmlspecialchars(
                (string)$level,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )?>

        </div>

    </header>


    <section class="gc-panel">

        <div class="gc-panel-head">

            <div class="gc-panel-title">
                LIVE GRID REGIONS
            </div>

            <button
                id="gc-refresh"
                class="gc-refresh"
                type="button">

                REFRESH

            </button>

        </div>


        <div class="gc-stats">

            <div class="gc-stat">
                <strong
                    id="gc-total"
                    class="gc-stat-value">0</strong>
                <span class="gc-stat-label">
                    TOTAL REGIONS
                </span>
            </div>

            <div class="gc-stat">
                <strong
                    id="gc-running"
                    class="gc-stat-value">0</strong>
                <span class="gc-stat-label">
                    RUNNING
                </span>
            </div>

            <div class="gc-stat">
                <strong
                    id="gc-stopped"
                    class="gc-stat-value">0</strong>
                <span class="gc-stat-label">
                    STOPPED / OTHER
                </span>
            </div>

            <div class="gc-stat">
                <strong
                    id="gc-selected"
                    class="gc-stat-value">0</strong>
                <span class="gc-stat-label">
                    SELECTED
                </span>
            </div>

        </div>


        <div class="gc-selection">

            <button
                id="gc-select-all"
                class="gc-button"
                type="button">

                SELECT ALL

            </button>

            <button
                id="gc-clear"
                class="gc-button"
                type="button">

                CLEAR

            </button>

        </div>


        <div
            id="gc-selected-line"
            class="gc-selected-line">

            0 regions selected

        </div>


        <div class="gc-actions">

            <button
                id="gc-start"
                class="gc-button"
                type="button"
                disabled>

                START SELECTED

            </button>

            <button
                id="gc-stop"
                class="gc-button"
                type="button"
                disabled>

                STOP SELECTED

            </button>

            <button
                id="gc-restart"
                class="gc-button"
                type="button"
                disabled>

                RESTART SELECTED

            </button>

            <button
                id="gc-freeze"
                class="gc-button"
                type="button"
                disabled>

                FREEZE SELECTED

            </button>

            <button
                id="gc-thaw"
                class="gc-button"
                type="button"
                disabled>

                THAW SELECTED

            </button>

            <button
                id="gc-restart-all"
                class="gc-button gc-restart-all"
                type="button">

                RESTART ALL

            </button>

        </div>


        <div class="gc-warning">

            LIVE GRID CONTROLS:
            Start, Stop, Restart, Freeze and Thaw are sent
            directly to DreamGrid. Restart All affects every
            region. Commands require confirmation before being sent.

        </div>


        <div
            id="gc-result"
            class="gc-result">

            Grid Control ready.

        </div>


        <div
            id="gc-regions"
            class="gc-regions">

            <div class="gc-empty">
                Loading DreamGrid regions...
            </div>

        </div>

    </section>


</main>


<script>

(function(){

    'use strict';


    const ENDPOINT_REGIONS =
        '/Other/regions.php';

    const ENDPOINT_COMMAND =
        '/Other/command.php';


    let regions =
        [];

    let selected =
        new Set();

    let busy =
        false;


    const el =
        function(id){

            return document.getElementById(id);

        };


    function escapeHtml(value){

        return String(
            value ?? ''
        )
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#039;');

    }


    function firstValue(
        region,
        keys,
        fallback
    ){

        for(const key of keys){

            if(
                region &&
                region[key] !== undefined &&
                region[key] !== null &&
                String(region[key]).trim() !== ''
            ){
                return region[key];
            }
        }

        return fallback;
    }


    function regionName(region){

        return String(
            firstValue(
                region,
                [
                    'RegionName',
                    'region',
                    'Name',
                    'name'
                ],
                ''
            )
        ).trim();

    }


    function regionStatus(region){

        const raw =
            firstValue(
                region,
                [
                    'Status',
                    'RegionStatus',
                    'State',
                    'RegionState',
                    'Running',
                    'running'
                ],
                'Unknown'
            );

        if(raw === true){
            return 'Running';
        }

        if(raw === false){
            return 'Stopped';
        }

        return String(raw).trim() || 'Unknown';
    }


    function isRunning(region){

        const status =
            regionStatus(region)
                .toLowerCase();

        return (
            status.includes('booted') ||
            status.includes('running') ||
            status.includes('online') ||
            status.includes('started')
        );

    }


    function isStopped(region){

        const status =
            regionStatus(region)
                .toLowerCase();

        return (
            status.includes('offline') ||
            status.includes('stopped') ||
            status.includes('down') ||
            status.includes('not running')
        );

    }


    function owner(region){

        return String(
            firstValue(
                region,
                [
                    'EstateOwner',
                    'EstateOwnerName',
                    'Owner',
                    'owner'
                ],
                '—'
            )
        );

    }


    function avatars(region){

        return firstValue(
            region,
            [
                'Avatars',
                'AvatarCount',
                'Agents',
                'AgentCount',
                'AV'
            ],
            0
        );

    }


    function prims(region){

        return firstValue(
            region,
            [
                'Prims',
                'PrimCount',
                'Objects',
                'ObjectCount'
            ],
            0
        );

    }


    function setResult(
        message,
        error
    ){

        const box =
            el('gc-result');

        box.textContent =
            String(message || '');

        box.classList.toggle(
            'error',
            !!error
        );

    }


    function updateControls(){

        const count =
            selected.size;

        el('gc-selected').textContent =
            String(count);

        el('gc-selected-line').textContent =
            count +
            ' region' +
            (count === 1 ? '' : 's') +
            ' selected';


        [
            'gc-start',
            'gc-stop',
            'gc-restart',
            'gc-freeze',
            'gc-thaw'
        ].forEach(
            function(id){

                el(id).disabled =
                    busy ||
                    count === 0;

            }
        );


        el('gc-restart-all').disabled =
            busy;

        el('gc-refresh').disabled =
            busy;

        el('gc-select-all').disabled =
            busy ||
            regions.length === 0;

        el('gc-clear').disabled =
            busy ||
            count === 0;

    }


    function render(){

        const validNames =
            new Set(
                regions
                    .map(regionName)
                    .filter(Boolean)
            );

        selected =
            new Set(
                Array.from(selected)
                    .filter(
                        name =>
                            validNames.has(name)
                    )
            );


        const runningCount =
            regions.filter(
                isRunning
            ).length;


        el('gc-total').textContent =
            String(regions.length);

        el('gc-running').textContent =
            String(runningCount);

        el('gc-stopped').textContent =
            String(
                regions.length -
                runningCount
            );


        const holder =
            el('gc-regions');


        if(regions.length === 0){

            holder.innerHTML =
                '<div class="gc-empty">' +
                'No DreamGrid regions were returned.' +
                '</div>';

            updateControls();

            return;
        }


        holder.innerHTML =
            regions.map(
                function(region){

                    const name =
                        regionName(region);

                    const status =
                        regionStatus(region);

                    const running =
                        isRunning(region);

                    const stopped =
                        isStopped(region);

                    const selectedClass =
                        selected.has(name)
                            ? ' selected'
                            : '';

                    const statusClass =
                        running
                            ? ' running'
                            : (
                                stopped
                                    ? ' stopped'
                                    : ''
                            );

                    return (
                        '<div class="gc-region' +
                        selectedClass +
                        '">' +

                            '<input ' +
                                'class="gc-check" ' +
                                'type="checkbox" ' +
                                'data-region="' +
                                escapeHtml(name) +
                                '"' +
                                (
                                    selected.has(name)
                                        ? ' checked'
                                        : ''
                                ) +
                            '>' +

                            '<div class="gc-region-name">' +
                                escapeHtml(name) +
                            '</div>' +

                            '<div class="gc-status' +
                                statusClass +
                                '">' +
                                escapeHtml(status) +
                            '</div>' +

                            '<div class="gc-owner">' +
                                escapeHtml(owner(region)) +
                            '</div>' +

                            '<div class="gc-number">' +
                                'AV: ' +
                                escapeHtml(avatars(region)) +
                            '</div>' +

                            '<div class="gc-number">' +
                                'PRIMS: ' +
                                escapeHtml(prims(region)) +
                            '</div>' +

                        '</div>'
                    );

                }
            ).join('');


        holder
            .querySelectorAll(
                '.gc-check'
            )
            .forEach(
                function(box){

                    box.addEventListener(
                        'change',
                        function(){

                            const name =
                                box.dataset.region || '';

                            if(box.checked){

                                selected.add(name);

                            }else{

                                selected.delete(name);

                            }

                            render();

                        }
                    );

                }
            );


        updateControls();

    }


    async function loadRegions(
        showMessage = true
    ){

        if(showMessage){

            setResult(
                'Loading DreamGrid regions...',
                false
            );
        }

        try{

            const response =
                await fetch(
                    ENDPOINT_REGIONS +
                    '?nocache=' +
                    Date.now(),
                    {
                        credentials:
                            'same-origin',

                        cache:
                            'no-store'
                    }
                );


            const raw =
                await response.text();


            let data;

            try{

                data =
                    JSON.parse(raw);

            }catch(error){

                throw new Error(
                    raw ||
                    'Invalid response from regions.php'
                );
            }


            if(
                !response.ok ||
                !data.ok
            ){

                throw new Error(
                    data.error ||
                    'Unable to load DreamGrid regions.'
                );
            }


            regions =
                Array.isArray(
                    data.regions
                )
                    ? data.regions
                    : [];


            render();


            if(showMessage){

                setResult(
                    'Loaded ' +
                    regions.length +
                    ' DreamGrid region' +
                    (
                        regions.length === 1
                            ? '.'
                            : 's.'
                    ),
                    false
                );
            }

        }catch(error){

            regions = [];

            render();

            setResult(
                error.message,
                true
            );
        }

    }


    async function sendCommand(
        command,
        region
    ){

        const body =
            new URLSearchParams();

        body.set(
            'command',
            command
        );

        if(region){

            body.set(
                'region',
                region
            );
        }


        const response =
            await fetch(
                ENDPOINT_COMMAND,
                {
                    method:
                        'POST',

                    credentials:
                        'same-origin',

                    cache:
                        'no-store',

                    headers:{
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8'
                    },

                    body:
                        body
                }
            );


        const raw =
            await response.text();


        let data;

        try{

            data =
                JSON.parse(raw);

        }catch(error){

            throw new Error(
                raw ||
                'DreamGrid returned an invalid response.'
            );
        }


        if(
            !response.ok ||
            !data.ok
        ){

            throw new Error(
                data.error ||
                data.message ||
                'DreamGrid command failed.'
            );
        }


        return data;

    }


    async function runSelected(
        command,
        label
    ){

        if(busy){
            return;
        }


        const names =
            Array.from(selected);


        if(names.length === 0){
            return;
        }


        if(
            !window.confirm(
                label +
                '\n\n' +
                names.join('\n') +
                '\n\nContinue?'
            )
        ){
            return;
        }


        busy = true;

        updateControls();


        const successful =
            [];

        const failed =
            [];


        setResult(
            'Sending ' +
            command +
            ' to ' +
            names.length +
            ' region' +
            (
                names.length === 1
                    ? '...'
                    : 's...'
            ),
            false
        );


        for(const name of names){

            try{

                const response =
                    await sendCommand(
                        command,
                        name
                    );

                successful.push(
                    response.message ||
                    (
                        command +
                        ' ' +
                        name +
                        ' accepted.'
                    )
                );

            }catch(error){

                failed.push(
                    name +
                    ': ' +
                    error.message
                );
            }
        }


        let message =
            successful.length +
            ' completed';


        if(failed.length){

            message +=
                '\n\nFAILED:\n' +
                failed.join('\n');

        }else{

            message += '.';
        }


        setResult(
            message,
            failed.length > 0
        );


        busy = false;

        updateControls();


        setTimeout(
            function(){

                loadRegions(false);

            },
            2000
        );

    }


    async function restartAll(){

        if(busy){
            return;
        }


        if(
            !window.confirm(
                'RESTART ALL\n\n' +
                'EVERY REGION ON THE GRID WILL BE RESTARTED.\n\n' +
                'Continue?'
            )
        ){
            return;
        }


        busy = true;

        updateControls();

        setResult(
            'Sending RestartAll to DreamGrid...',
            false
        );


        try{

            const response =
                await sendCommand(
                    'RestartAll',
                    ''
                );


            setResult(
                response.message ||
                'DreamGrid accepted RestartAll.',
                false
            );


            setTimeout(
                function(){

                    loadRegions(false);

                },
                4000
            );

        }catch(error){

            setResult(
                error.message,
                true
            );

        }finally{

            busy = false;

            updateControls();
        }

    }


    el('gc-refresh')
        .addEventListener(
            'click',
            function(){

                loadRegions(true);

            }
        );


    el('gc-select-all')
        .addEventListener(
            'click',
            function(){

                selected =
                    new Set(
                        regions
                            .map(regionName)
                            .filter(Boolean)
                    );

                render();

            }
        );


    el('gc-clear')
        .addEventListener(
            'click',
            function(){

                selected.clear();

                render();

            }
        );


    el('gc-start')
        .addEventListener(
            'click',
            function(){

                runSelected(
                    'StartRegion',
                    'START SELECTED'
                );

            }
        );


    el('gc-stop')
        .addEventListener(
            'click',
            function(){

                runSelected(
                    'StopRegion',
                    'STOP SELECTED'
                );

            }
        );


    el('gc-restart')
        .addEventListener(
            'click',
            function(){

                runSelected(
                    'RestartRegion',
                    'RESTART SELECTED'
                );

            }
        );


    el('gc-freeze')
        .addEventListener(
            'click',
            function(){

                runSelected(
                    'Freeze',
                    'FREEZE SELECTED'
                );

            }
        );


    el('gc-thaw')
        .addEventListener(
            'click',
            function(){

                runSelected(
                    'Thaw',
                    'THAW SELECTED'
                );

            }
        );


    el('gc-restart-all')
        .addEventListener(
            'click',
            restartAll
        );


    loadRegions(true);

})();

</script>

</body>

</html>