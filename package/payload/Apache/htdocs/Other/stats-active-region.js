(function(){

"use strict";


const MAX_HISTORY =
    60;


let simHistory =
    [];


let physicsHistory =
    [];


let currentName =
    "";


function num(value){

    const number =
        Number(value);


    return Number.isFinite(number)
        ? number
        : null;
}


function metric(
    row,
    names
){

    for (const name of names) {

        if (
            row &&
            Object.prototype.hasOwnProperty.call(
                row,
                name
            )
        ) {

            const value =
                num(
                    row[name]
                );


            if (value !== null) {
                return value;
            }
        }
    }


    return null;
}


function regionName(row){

    return String(
        row?.RegionName ??
        row?.regionName ??
        row?.Name ??
        row?.name ??
        "Unknown Region"
    ).trim();
}


function rowsOf(data){

    if (
        data &&
        Array.isArray(data.rows)
    ) {
        return data.rows;
    }


    if (
        data &&
        Array.isArray(data.regions)
    ) {
        return data.regions;
    }


    return [];
}



/* ============================================================
   INSTALL PANEL
   ============================================================ */

function installPanel(){

    if (
        document.getElementById(
            "stats-active-region"
        )
    ) {
        return true;
    }


    const gauges =
        document.getElementById(
            "stats-live-gauges"
        );


    if (!gauges) {
        return false;
    }


    const panel =
        document.createElement(
            "section"
        );


    panel.id =
        "stats-active-region";


    panel.className =
        "stats-active-region";


    panel.innerHTML =
        `
        <header class="stats-active-region-head">

            <div>

                <div class="stats-active-region-kicker">
                    LIVE REGION
                </div>

                <div class="stats-active-region-title">
                    ACTIVE REGION MONITOR
                </div>

            </div>

            <div class="stats-active-region-live">
                LIVE
            </div>

        </header>


        <div class="stats-active-region-body">


            <div class="stats-active-hero">

                <div
                    id="stats-active-mode"
                    class="stats-active-mode">
                    WAITING FOR TELEMETRY
                </div>

                <div
                    id="stats-active-name"
                    class="stats-active-name">
                    —
                </div>

                <div class="stats-active-avatar-line">

                    <span class="stats-active-avatar-dot"></span>

                    <span id="stats-active-avatars">
                        0 avatars
                    </span>

                </div>

                <div class="stats-active-region-status">

                    <span id="stats-active-healthbar"></span>

                </div>

                <div
                    id="stats-active-updated"
                    class="stats-active-updated">
                    Waiting...
                </div>

            </div>


            <div class="stats-active-metrics">

                <div class="stats-active-metric">

                    <div class="stats-active-metric-label">
                        SIM FPS
                    </div>

                    <div
                        id="stats-active-sim"
                        class="stats-active-metric-value cyan">
                        —
                    </div>

                    <div class="stats-active-metric-sub">
                        Simulator FPS
                    </div>

                </div>


                <div class="stats-active-metric">

                    <div class="stats-active-metric-label">
                        PHYSICS FPS
                    </div>

                    <div
                        id="stats-active-physics"
                        class="stats-active-metric-value green">
                        —
                    </div>

                    <div class="stats-active-metric-sub">
                        Physics engine
                    </div>

                </div>


                <div class="stats-active-metric">

                    <div class="stats-active-metric-label">
                        FRAME TIME
                    </div>

                    <div
                        id="stats-active-frame"
                        class="stats-active-metric-value gold">
                        —
                    </div>

                    <div class="stats-active-metric-sub">
                        Milliseconds
                    </div>

                </div>


                <div class="stats-active-metric">

                    <div class="stats-active-metric-label">
                        ACTIVE SCRIPTS
                    </div>

                    <div
                        id="stats-active-scripts"
                        class="stats-active-metric-value">
                        —
                    </div>

                    <div class="stats-active-metric-sub">
                        Running scripts
                    </div>

                </div>


                <div class="stats-active-metric">

                    <div class="stats-active-metric-label">
                        SCRIPT EPS
                    </div>

                    <div
                        id="stats-active-eps"
                        class="stats-active-metric-value">
                        —
                    </div>

                    <div class="stats-active-metric-sub">
                        Events / second
                    </div>

                </div>


                <div class="stats-active-metric">

                    <div class="stats-active-metric-label">
                        PRIMS
                    </div>

                    <div
                        id="stats-active-prims"
                        class="stats-active-metric-value">
                        —
                    </div>

                    <div class="stats-active-metric-sub">
                        Scene primitives
                    </div>

                </div>

            </div>


            <div class="stats-active-trend">

                <div class="stats-active-trend-head">

                    <div class="stats-active-trend-title">
                        60 SECOND PERFORMANCE
                    </div>

                    <div class="stats-active-trend-legend">

                        <span class="sim">
                            <i></i>
                            SIM
                        </span>

                        <span class="physics">
                            <i></i>
                            PHYSICS
                        </span>

                    </div>

                </div>


                <div class="stats-active-chart-wrap">

                    <canvas id="stats-active-chart"></canvas>

                </div>


                <div class="stats-active-minmax">

                    <div>
                        <span>SIM MIN / MAX</span>
                        <strong id="stats-active-sim-range">—</strong>
                    </div>

                    <div>
                        <span>PHYS MIN / MAX</span>
                        <strong id="stats-active-physics-range">—</strong>
                    </div>

                </div>

            </div>

        </div>
        `;


    gauges.insertAdjacentElement(
        "afterend",
        panel
    );


    return true;
}



/* ============================================================
   SELECT REGION
   ============================================================ */

function selectRegion(rows){

    const online =
        rows.filter(
            function(row){

                const sim =
                    metric(
                        row,
                        [
                            "SimFPS",
                            "simFPS"
                        ]
                    );


                return (
                    sim !== null &&
                    sim > 0
                );
            }
        );


    if (!online.length) {

        return {
            row:null,
            active:false
        };
    }


    const occupied =
        online
        .map(
            function(row){

                return {
                    row:row,

                    avatars:
                        metric(
                            row,
                            [
                                "RootAg",
                                "RootAgents"
                            ]
                        ) ?? 0
                };
            }
        )
        .filter(
            item =>
                item.avatars > 0
        )
        .sort(
            function(a,b){

                return (
                    b.avatars -
                    a.avatars
                );
            }
        );


    if (occupied.length) {

        return {
            row:
                occupied[0].row,

            active:
                true
        };
    }


    online.sort(
        function(a,b){

            const av =
                metric(
                    a,
                    ["SimFPS"]
                ) ?? 999;


            const bv =
                metric(
                    b,
                    ["SimFPS"]
                ) ?? 999;


            return av - bv;
        }
    );


    return {
        row:
            online[0],

        active:
            false
    };
}



/* ============================================================
   TEXT
   ============================================================ */

function setText(
    id,
    value
){

    const node =
        document.getElementById(
            id
        );


    if (node) {
        node.textContent = value;
    }
}



/* ============================================================
   HISTORY
   ============================================================ */

function addHistory(
    sim,
    physics
){

    if (sim !== null) {

        simHistory.push(
            sim
        );
    }


    if (physics !== null) {

        physicsHistory.push(
            physics
        );
    }


    if (
        simHistory.length >
        MAX_HISTORY
    ) {

        simHistory =
            simHistory.slice(
                -MAX_HISTORY
            );
    }


    if (
        physicsHistory.length >
        MAX_HISTORY
    ) {

        physicsHistory =
            physicsHistory.slice(
                -MAX_HISTORY
            );
    }
}



/* ============================================================
   CANVAS
   ============================================================ */

function drawLine(
    ctx,
    values,
    width,
    height,
    colour
){

    if (!values.length) {
        return;
    }


    ctx.beginPath();


    values.forEach(
        function(value,index){

            const x =
                values.length <= 1
                    ?
                    0
                    :
                    (
                        index /
                        (MAX_HISTORY - 1)
                    ) *
                    width;


            /*
             * OpenSim FPS graph focuses on useful 40-55 range
             * so small movements are clearly visible.
             */

            const normal =
                Math.max(
                    0,
                    Math.min(
                        1,
                        (
                            value -
                            40
                        ) /
                        15
                    )
                );


            const y =
                height -
                (
                    normal *
                    (
                        height - 8
                    )
                ) -
                4;


            if (index === 0) {

                ctx.moveTo(
                    x,
                    y
                );
            }
            else {

                ctx.lineTo(
                    x,
                    y
                );
            }
        }
    );


    ctx.strokeStyle =
        colour;


    ctx.lineWidth =
        1.5;


    ctx.shadowColor =
        colour;


    ctx.shadowBlur =
        6;


    ctx.stroke();


    ctx.shadowBlur =
        0;
}


function drawChart(){

    const canvas =
        document.getElementById(
            "stats-active-chart"
        );


    if (!canvas) {
        return;
    }


    const rect =
        canvas.getBoundingClientRect();


    if (
        rect.width <= 0 ||
        rect.height <= 0
    ) {
        return;
    }


    const ratio =
        window.devicePixelRatio ||
        1;


    canvas.width =
        Math.round(
            rect.width *
            ratio
        );


    canvas.height =
        Math.round(
            rect.height *
            ratio
        );


    const ctx =
        canvas.getContext(
            "2d"
        );


    ctx.setTransform(
        ratio,
        0,
        0,
        ratio,
        0,
        0
    );


    ctx.clearRect(
        0,
        0,
        rect.width,
        rect.height
    );


    drawLine(
        ctx,
        simHistory,
        rect.width,
        rect.height,
        "#39d8ff"
    );


    drawLine(
        ctx,
        physicsHistory,
        rect.width,
        rect.height,
        "#39ddc8"
    );
}



/* ============================================================
   MIN MAX
   ============================================================ */

function rangeText(
    values,
    decimals
){

    if (!values.length) {
        return "—";
    }


    const minimum =
        Math.min(
            ...values
        );


    const maximum =
        Math.max(
            ...values
        );


    return (
        minimum.toFixed(
            decimals
        ) +
        " / " +
        maximum.toFixed(
            decimals
        )
    );
}



/* ============================================================
   UPDATE PANEL
   ============================================================ */

function updatePanel(
    row,
    active
){

    if (!row) {
        return;
    }


    const name =
        regionName(
            row
        );


    /*
     * When monitored region changes, start a clean graph
     * rather than mixing two regions together.
     */

    if (
        currentName !== "" &&
        currentName !== name
    ) {

        simHistory =
            [];


        physicsHistory =
            [];
    }


    currentName =
        name;


    const avatars =
        metric(
            row,
            [
                "RootAg",
                "RootAgents"
            ]
        ) ?? 0;


    const childAgents =
        metric(
            row,
            [
                "ChldAg",
                "ChildAgents"
            ]
        ) ?? 0;


    const sim =
        metric(
            row,
            [
                "SimFPS"
            ]
        );


    const physics =
        metric(
            row,
            [
                "PhysicsFPS",
                "PhyFPS",
                "PhysFPS"
            ]
        );


    const frame =
        metric(
            row,
            [
                "TotlFt",
                "FrameMS",
                "FrameTime"
            ]
        );


    const scripts =
        metric(
            row,
            [
                "AtvScr",
                "ActiveScripts"
            ]
        );


    const eps =
        metric(
            row,
            [
                "ScrEPS",
                "ScriptEPS",
                "ScriptEvents"
            ]
        );


    const prims =
        metric(
            row,
            [
                "Prims",
                "PrimCount"
            ]
        );


    setText(
        "stats-active-mode",

        active
            ?
            "ACTIVE REGION"
            :
            "MONITORING LOWEST FPS REGION"
    );


    setText(
        "stats-active-name",
        name
    );


    setText(
        "stats-active-avatars",

        avatars +
        (
            avatars === 1
                ?
                " root avatar"
                :
                " root avatars"
        ) +
        (
            childAgents > 0
                ?
                " • " +
                childAgents +
                " child"
                :
                ""
        )
    );


    setText(
        "stats-active-sim",

        sim === null
            ?
            "—"
            :
            sim.toFixed(1)
    );


    setText(
        "stats-active-physics",

        physics === null
            ?
            "—"
            :
            physics.toFixed(1)
    );


    setText(
        "stats-active-frame",

        frame === null
            ?
            "—"
            :
            frame.toFixed(2) +
            " ms"
    );


    setText(
        "stats-active-scripts",

        scripts === null
            ?
            "—"
            :
            Math.round(
                scripts
            ).toLocaleString()
    );


    setText(
        "stats-active-eps",

        eps === null
            ?
            "—"
            :
            Math.round(
                eps
            ).toLocaleString()
    );


    setText(
        "stats-active-prims",

        prims === null
            ?
            "—"
            :
            Math.round(
                prims
            ).toLocaleString()
    );


    setText(
        "stats-active-updated",

        "Updated " +
        new Date()
            .toLocaleTimeString()
    );


    /*
     * Health bar uses Sim FPS.
     */

    const health =
        sim === null
            ?
            0
            :
            Math.max(
                0,
                Math.min(
                    100,
                    sim /
                    55 *
                    100
                )
            );


    const bar =
        document.getElementById(
            "stats-active-healthbar"
        );


    if (bar) {

        bar.style.width =
            health.toFixed(1) +
            "%";
    }


    addHistory(
        sim,
        physics
    );


    setText(
        "stats-active-sim-range",

        rangeText(
            simHistory,
            1
        )
    );


    setText(
        "stats-active-physics-range",

        rangeText(
            physicsHistory,
            1
        )
    );


    drawChart();
}



/* ============================================================
   FETCH
   ============================================================ */

let busy =
    false;


async function poll(){

    if (busy) {
        return;
    }


    if (
        document.visibilityState !==
        "visible"
    ) {
        return;
    }


    if (!installPanel()) {
        return;
    }


    busy =
        true;


    try {

        const response =
            await fetch(
                "/Other/admin-region-live-direct.php?_=" +
                Date.now(),
                {
                    credentials:
                        "same-origin",

                    cache:
                        "no-store"
                }
            );


        if (!response.ok) {
            return;
        }


        const data =
            await response.json();


        if (
            !data ||
            data.ok !== true
        ) {
            return;
        }


        const selected =
            selectRegion(
                rowsOf(
                    data
                )
            );


        updatePanel(
            selected.row,
            selected.active
        );
    }
    catch(error) {

        console.debug(
            "Active Region monitor:",
            error
        );
    }
    finally {

        busy =
            false;
    }
}



/* ============================================================
   START
   ============================================================ */

function start(){

    /*
     * Gauge deck is dynamically created, so allow it to
     * initialise before inserting the Active Region panel.
     */

    window.setTimeout(
        function(){

            installPanel();

            poll();

        },
        500
    );


    window.setInterval(
        poll,
        1000
    );


    window.addEventListener(
        "resize",
        drawChart
    );


    window.AustraliaActiveRegion = {

        refresh:
            poll,

        name:
            function(){

                return currentName;
            }
    };
}


if (
    document.readyState ===
    "loading"
) {

    document.addEventListener(
        "DOMContentLoaded",
        start,
        {
            once:true
        }
    );
}
else {

    start();
}

})();