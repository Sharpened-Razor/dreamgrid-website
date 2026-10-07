(function(){

"use strict";


const HISTORY =
    60;


let telemetryLatency =
    [];


let scriptHistory =
    [];


let previousServices =
    {};


let previousRegionAvatars =
    new Map();


let panelReady =
    false;


let liveBusy =
    false;


let statusBusy =
    false;



/* ============================================================
   HELPERS
   ============================================================ */

function num(value){

    const n =
        Number(value);


    return Number.isFinite(n)
        ? n
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

            const n =
                num(
                    row[name]
                );


            if (n !== null) {
                return n;
            }
        }
    }


    return null;
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


function nameOf(row){

    return String(
        row?.RegionName ??
        row?.regionName ??
        row?.Name ??
        row?.name ??
        "Unknown"
    ).trim();
}


function text(
    id,
    value
){

    const node =
        document.getElementById(
            id
        );


    if (node) {

        node.textContent =
            value;
    }
}


function formatNumber(value){

    if (
        value === null ||
        !Number.isFinite(value)
    ) {
        return "—";
    }


    return Math.round(
        value
    ).toLocaleString();
}



/* ============================================================
   INSTALL PANEL
   ============================================================ */

function install(){

    if (
        document.getElementById(
            "stats-grid-services"
        )
    ) {

        panelReady =
            true;

        return true;
    }


    const active =
        document.getElementById(
            "stats-active-region"
        );


    const gauges =
        document.getElementById(
            "stats-live-gauges"
        );


    const anchor =
        active ||
        gauges;


    if (!anchor) {
        return false;
    }


    const panel =
        document.createElement(
            "section"
        );


    panel.id =
        "stats-grid-services";


    panel.className =
        "stats-grid-services";


    panel.innerHTML =
        `
        <header class="stats-grid-services-head">

            <div>

                <div class="stats-grid-services-kicker">
                    GRID INFRASTRUCTURE
                </div>

                <div class="stats-grid-services-title">
                    SERVICES & NETWORK CONTROL
                </div>

            </div>

            <div
                id="stats-grid-clock"
                class="stats-grid-services-clock">
                LIVE
            </div>

        </header>


        <div
            id="stats-grid-alarm"
            class="stats-grid-alarm">

            <span class="stats-grid-alarm-dot"></span>

            <span id="stats-grid-alarm-text">
                ATTENTION REQUIRED
            </span>

        </div>


        <div class="stats-grid-services-body">


            <div class="stats-service-rack">


                <div
                    id="stats-service-apache"
                    class="stats-service-module">

                    <div class="stats-service-top">

                        <span class="stats-service-name">
                            APACHE
                        </span>

                        <span class="stats-service-led"></span>

                    </div>

                    <div
                        id="stats-service-apache-value"
                        class="stats-service-value">
                        —
                    </div>

                    <div
                        id="stats-service-apache-state"
                        class="stats-service-state">
                        WAITING
                    </div>

                </div>


                <div
                    id="stats-service-mysql"
                    class="stats-service-module">

                    <div class="stats-service-top">

                        <span class="stats-service-name">
                            MYSQL
                        </span>

                        <span class="stats-service-led"></span>

                    </div>

                    <div
                        id="stats-service-mysql-value"
                        class="stats-service-value">
                        —
                    </div>

                    <div
                        id="stats-service-mysql-state"
                        class="stats-service-state">
                        WAITING
                    </div>

                </div>


                <div
                    id="stats-service-robust"
                    class="stats-service-module">

                    <div class="stats-service-top">

                        <span class="stats-service-name">
                            ROBUST
                        </span>

                        <span class="stats-service-led"></span>

                    </div>

                    <div
                        id="stats-service-robust-value"
                        class="stats-service-value">
                        —
                    </div>

                    <div
                        id="stats-service-robust-state"
                        class="stats-service-state">
                        WAITING
                    </div>

                </div>


                <div
                    id="stats-service-opensim"
                    class="stats-service-module">

                    <div class="stats-service-top">

                        <span class="stats-service-name">
                            OPENSIM
                        </span>

                        <span class="stats-service-led"></span>

                    </div>

                    <div
                        id="stats-service-opensim-value"
                        class="stats-service-value">
                        —
                    </div>

                    <div
                        id="stats-service-opensim-state"
                        class="stats-service-state">
                        WAITING
                    </div>

                </div>

            </div>


            <div class="stats-grid-network-layout">


                <div class="stats-grid-port-panel">

                    <div class="stats-grid-subtitle">
                        NETWORK SERVICES
                    </div>


                    <div
                        id="stats-port-login"
                        class="stats-grid-port">

                        <span class="stats-grid-port-led"></span>

                        <span class="stats-grid-port-name">
                            Grid login service
                        </span>

                        <span
                            id="stats-port-login-state"
                            class="stats-grid-port-state">
                            WAITING
                        </span>

                    </div>


                    <div
                        id="stats-port-diagnostics"
                        class="stats-grid-port">

                        <span class="stats-grid-port-led"></span>

                        <span class="stats-grid-port-name">
                            Grid diagnostics service
                        </span>

                        <span
                            id="stats-port-diagnostics-state"
                            class="stats-grid-port-state">
                            WAITING
                        </span>

                    </div>


                    <div
                        id="stats-port-dns"
                        class="stats-grid-port">

                        <span class="stats-grid-port-led"></span>

                        <span class="stats-grid-port-name">
                            GRID DNS
                        </span>

                        <span
                            id="stats-port-dns-state"
                            class="stats-grid-port-state">
                            WAITING
                        </span>

                    </div>


                    <div
                        id="stats-port-telemetry"
                        class="stats-grid-port">

                        <span class="stats-grid-port-led"></span>

                        <span class="stats-grid-port-name">
                            LIVE TELEMETRY
                        </span>

                        <span
                            id="stats-port-telemetry-state"
                            class="stats-grid-port-state">
                            WAITING
                        </span>

                    </div>

                </div>


                <div class="stats-grid-workload">

                    <div class="stats-grid-subtitle">
                        LIVE GRID WORKLOAD
                    </div>


                    <div class="stats-grid-workload-grid">


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                ROOT AVATARS
                            </div>

                            <div
                                id="stats-workload-root"
                                class="stats-grid-workload-value green">
                                —
                            </div>

                        </div>


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                CHILD AGENTS
                            </div>

                            <div
                                id="stats-workload-child"
                                class="stats-grid-workload-value cyan">
                                —
                            </div>

                        </div>


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                ACTIVE SCRIPTS
                            </div>

                            <div
                                id="stats-workload-scripts"
                                class="stats-grid-workload-value">
                                —
                            </div>

                        </div>


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                SCRIPT EPS
                            </div>

                            <div
                                id="stats-workload-eps"
                                class="stats-grid-workload-value gold">
                                —
                            </div>

                        </div>


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                TOTAL PRIMS
                            </div>

                            <div
                                id="stats-workload-prims"
                                class="stats-grid-workload-value">
                                —
                            </div>

                        </div>


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                REGIONS REPORTING
                            </div>

                            <div
                                id="stats-workload-regions"
                                class="stats-grid-workload-value cyan">
                                —
                            </div>

                        </div>


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                AVG SIM FPS
                            </div>

                            <div
                                id="stats-workload-sim"
                                class="stats-grid-workload-value green">
                                —
                            </div>

                        </div>


                        <div class="stats-grid-workload-item">

                            <div class="stats-grid-workload-label">
                                TELEMETRY LATENCY
                            </div>

                            <div
                                id="stats-workload-latency"
                                class="stats-grid-workload-value cyan">
                                —
                            </div>

                        </div>

                    </div>

                </div>


                <div class="stats-grid-events">

                    <div class="stats-grid-subtitle">
                        LIVE EVENTS
                    </div>

                    <div
                        id="stats-grid-events-list"
                        class="stats-grid-events-list">
                    </div>

                </div>

            </div>


            <div class="stats-grid-graphs">


                <div class="stats-grid-graph">

                    <div class="stats-grid-graph-head">

                        <span class="stats-grid-graph-title">
                            60 SEC TELEMETRY RESPONSE
                        </span>

                        <span
                            id="stats-latency-value"
                            class="stats-grid-graph-value">
                            —
                        </span>

                    </div>

                    <div class="stats-grid-canvas-box">

                        <canvas
                            id="stats-latency-chart"
                            class="stats-grid-canvas">
                        </canvas>

                    </div>

                </div>


                <div class="stats-grid-graph">

                    <div class="stats-grid-graph-head">

                        <span class="stats-grid-graph-title">
                            60 SEC SCRIPT ACTIVITY
                        </span>

                        <span
                            id="stats-script-value"
                            class="stats-grid-graph-value">
                            —
                        </span>

                    </div>

                    <div class="stats-grid-canvas-box">

                        <canvas
                            id="stats-script-chart"
                            class="stats-grid-canvas">
                        </canvas>

                    </div>

                </div>

            </div>


        </div>

        `;


    anchor.insertAdjacentElement(
        "afterend",
        panel
    );


    panelReady =
        true;


    addEvent(
        "Grid Services monitor started",
        "good"
    );


    return true;
}



/* ============================================================
   SERVICE MODULE
   ============================================================ */

function serviceModule(
    key,
    count
){

    const module =
        document.getElementById(
            "stats-service-" +
            key
        );


    if (!module) {
        return;
    }


    const value =
        document.getElementById(
            "stats-service-" +
            key +
            "-value"
        );


    const state =
        document.getElementById(
            "stats-service-" +
            key +
            "-state"
        );


    module.classList.remove(
        "running",
        "warning",
        "down"
    );


    const running =
        Number(count) > 0;


    module.classList.add(
        running
            ? "running"
            : "down"
    );


    if (value) {

        value.textContent =
            running
                ?
                String(count)
                :
                "0";
    }


    if (state) {

        state.textContent =
            running
                ?
                (
                    Number(count) === 1
                        ?
                        "PROCESS RUNNING"
                        :
                        "PROCESSES RUNNING"
                )
                :
                "NOT RUNNING";
    }


    const before =
        previousServices[
            "service-" +
            key
        ];


    if (
        before !== undefined &&
        before !== running
    ) {

        addEvent(
            key.toUpperCase() +
            (
                running
                    ?
                    " recovered"
                    :
                    " stopped"
            ),
            running
                ? "good"
                : "bad"
        );
    }


    previousServices[
        "service-" +
        key
    ] =
        running;
}



/* ============================================================
   PORT STATUS
   ============================================================ */

function portStatus(
    key,
    good,
    goodText,
    badText
){

    const row =
        document.getElementById(
            "stats-port-" +
            key
        );


    const state =
        document.getElementById(
            "stats-port-" +
            key +
            "-state"
        );


    if (!row) {
        return;
    }


    row.classList.remove(
        "good",
        "bad"
    );


    row.classList.add(
        good
            ? "good"
            : "bad"
    );


    if (state) {

        state.textContent =
            good
                ? goodText
                : badText;
    }


    const before =
        previousServices[
            "port-" +
            key
        ];


    if (
        before !== undefined &&
        before !== good
    ) {

        addEvent(
            key.toUpperCase() +
            (
                good
                    ?
                    " recovered"
                    :
                    " unavailable"
            ),
            good
                ? "good"
                : "bad"
        );
    }


    previousServices[
        "port-" +
        key
    ] =
        good;
}



/* ============================================================
   EVENT TICKER
   ============================================================ */

function addEvent(
    message,
    tone
){

    const list =
        document.getElementById(
            "stats-grid-events-list"
        );


    if (!list) {
        return;
    }


    const row =
        document.createElement(
            "div"
        );


    row.className =
        "stats-grid-event " +
        (
            tone ||
            ""
        );


    row.innerHTML =
        `
        <span class="stats-grid-event-time">
            ${new Date().toLocaleTimeString()}
        </span>

        <span class="stats-grid-event-text"></span>
        `;


    row.querySelector(
        ".stats-grid-event-text"
    ).textContent =
        message;


    list.prepend(
        row
    );


    while (
        list.children.length >
        12
    ) {

        list.removeChild(
            list.lastElementChild
        );
    }
}



/* ============================================================
   AVATAR ACTIVITY EVENTS
   ============================================================ */

function avatarEvents(rows){

    const current =
        new Map();


    for (const row of rows) {

        const name =
            nameOf(
                row
            );


        const root =
            metric(
                row,
                [
                    "RootAg",
                    "RootAgents"
                ]
            ) ?? 0;


        current.set(
            name,
            root
        );


        if (
            previousRegionAvatars.has(
                name
            )
        ) {

            const old =
                previousRegionAvatars.get(
                    name
                );


            if (root > old) {

                addEvent(
                    "Avatar activity increased in " +
                    name +
                    " (" +
                    old +
                    " → " +
                    root +
                    ")",
                    "good"
                );
            }
            else if (root < old) {

                addEvent(
                    "Avatar activity decreased in " +
                    name +
                    " (" +
                    old +
                    " → " +
                    root +
                    ")",
                    ""
                );
            }
        }
    }


    previousRegionAvatars =
        current;
}



/* ============================================================
   HISTORY
   ============================================================ */

function pushHistory(
    array,
    value
){

    if (
        value === null ||
        !Number.isFinite(value)
    ) {
        return;
    }


    array.push(
        value
    );


    if (
        array.length >
        HISTORY
    ) {

        array.splice(
            0,
            array.length -
            HISTORY
        );
    }
}



/* ============================================================
   CHART
   ============================================================ */

function drawChart(
    canvasId,
    values,
    colour,
    forcedMax
){

    const canvas =
        document.getElementById(
            canvasId
        );


    if (
        !canvas ||
        !values.length
    ) {
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


    const maximum =
        Math.max(
            forcedMax ||
            0,
            ...values,
            1
        );


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
                        (HISTORY - 1)
                    ) *
                    rect.width;


            const y =
                rect.height -
                (
                    value /
                    maximum
                ) *
                (
                    rect.height - 8
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



/* ============================================================
   LIVE TELEMETRY
   ============================================================ */

async function pollLive(){

    if (
        liveBusy ||
        document.visibilityState !==
        "visible"
    ) {
        return;
    }


    if (!install()) {
        return;
    }


    liveBusy =
        true;


    const started =
        performance.now();


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


        const elapsed =
            performance.now() -
            started;


        if (!response.ok) {

            portStatus(
                "telemetry",
                false,
                "",
                "DOWN"
            );


            return;
        }


        const data =
            await response.json();


        if (
            !data ||
            data.ok !== true
        ) {

            portStatus(
                "telemetry",
                false,
                "",
                "INVALID"
            );


            return;
        }


        portStatus(
            "telemetry",
            true,
            elapsed.toFixed(0) +
            " ms",
            ""
        );


        const rows =
            rowsOf(
                data
            );


        let roots =
            0;


        let children =
            0;


        let scripts =
            0;


        let eps =
            0;


        let prims =
            0;


        let simTotal =
            0;


        let simCount =
            0;


        for (const row of rows) {

            roots +=
                metric(
                    row,
                    [
                        "RootAg",
                        "RootAgents"
                    ]
                ) ?? 0;


            children +=
                metric(
                    row,
                    [
                        "ChldAg",
                        "ChildAgents"
                    ]
                ) ?? 0;


            scripts +=
                metric(
                    row,
                    [
                        "AtvScr",
                        "ActiveScripts"
                    ]
                ) ?? 0;


            eps +=
                metric(
                    row,
                    [
                        "ScrEPS",
                        "ScriptEPS",
                        "ScriptEvents"
                    ]
                ) ?? 0;


            prims +=
                metric(
                    row,
                    [
                        "Prims",
                        "PrimCount"
                    ]
                ) ?? 0;


            const sim =
                metric(
                    row,
                    [
                        "SimFPS"
                    ]
                );


            if (
                sim !== null &&
                sim > 0
            ) {

                simTotal +=
                    sim;


                simCount++;
            }
        }


        const avgSim =
            simCount > 0
                ?
                simTotal /
                simCount
                :
                null;


        text(
            "stats-workload-root",
            formatNumber(
                roots
            )
        );


        text(
            "stats-workload-child",
            formatNumber(
                children
            )
        );


        text(
            "stats-workload-scripts",
            formatNumber(
                scripts
            )
        );


        text(
            "stats-workload-eps",
            formatNumber(
                eps
            )
        );


        text(
            "stats-workload-prims",
            formatNumber(
                prims
            )
        );


        text(
            "stats-workload-regions",
            formatNumber(
                rows.length
            )
        );


        text(
            "stats-workload-sim",

            avgSim === null
                ?
                "—"
                :
                avgSim.toFixed(1)
        );


        text(
            "stats-workload-latency",

            elapsed.toFixed(0) +
            " ms"
        );


        text(
            "stats-latency-value",

            elapsed.toFixed(0) +
            " ms"
        );


        text(
            "stats-script-value",

            Math.round(
                eps
            ).toLocaleString() +
            " EPS"
        );


        pushHistory(
            telemetryLatency,
            elapsed
        );


        pushHistory(
            scriptHistory,
            eps
        );


        drawChart(
            "stats-latency-chart",
            telemetryLatency,
            "#38d8ff",
            250
        );


        drawChart(
            "stats-script-chart",
            scriptHistory,
            "#e1b445",
            null
        );


        avatarEvents(
            rows
        );


        updateAlarm(
            null,
            elapsed
        );
    }
    catch(error) {

        portStatus(
            "telemetry",
            false,
            "",
            "ERROR"
        );
    }
    finally {

        liveBusy =
            false;
    }
}



/* ============================================================
   SERVER / NETWORK STATUS
   ============================================================ */

let lastApiData =
    null;


async function pollStatus(){

    if (
        statusBusy ||
        document.visibilityState !==
        "visible"
    ) {
        return;
    }


    if (!install()) {
        return;
    }


    statusBusy =
        true;


    try {

        const response =
            await fetch(
                "stats-api.php?_=" +
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


        lastApiData =
            data;


        const processes =
            data.processes ||
            {};


        serviceModule(
            "apache",
            processes.apache
        );


        serviceModule(
            "mysql",
            processes.mysql
        );


        serviceModule(
            "robust",
            processes.robust
        );


        serviceModule(
            "opensim",
            processes.opensim
        );


        const network =
            data.network ||
            {};


        portStatus(
            "login",
            network.login?.ok === true,
            "RESPONDING",
            "DOWN"
        );


        portStatus(
            "diagnostics",
            network.diagnostics?.ok === true,
            "RESPONDING",
            "DOWN"
        );


        portStatus(
            "dns",
            network.dnsOk === true,
            "RESOLVED",
            "FAILED"
        );


        updateAlarm(
            data,
            telemetryLatency.length
                ?
                telemetryLatency[
                    telemetryLatency.length -
                    1
                ]
                :
                null
        );
    }
    catch(error) {

    }
    finally {

        statusBusy =
            false;
    }
}



/* ============================================================
   ALARM
   ============================================================ */

function updateAlarm(
    api,
    latency
){

    api =
        api ||
        lastApiData;


    const problems =
        [];


    if (api) {

        if (
            Number(
                api.processes?.apache
            ) <= 0
        ) {

            problems.push(
                "Apache not running"
            );
        }


        if (
            Number(
                api.processes?.mysql
            ) <= 0
        ) {

            problems.push(
                "MySQL not running"
            );
        }


        if (
            Number(
                api.processes?.robust
            ) <= 0
        ) {

            problems.push(
                "Robust not running"
            );
        }


        if (
            Number(
                api.processes?.opensim
            ) <= 0
        ) {

            problems.push(
                "No OpenSim processes"
            );
        }


        if (
            api.network?.login?.ok ===
            false
        ) {

            problems.push(
                "Grid login service unavailable"
            );
        }


        if (
            api.network?.diagnostics?.ok ===
            false
        ) {

            problems.push(
                "Grid diagnostics service unavailable"
            );
        }


        if (
            api.network?.dnsOk ===
            false
        ) {

            problems.push(
                "DNS resolution failed"
            );
        }
    }


    if (
        latency !== null &&
        Number.isFinite(latency) &&
        latency > 1200
    ) {

        problems.push(
            "Telemetry response slow"
        );
    }


    const alarm =
        document.getElementById(
            "stats-grid-alarm"
        );


    const alarmText =
        document.getElementById(
            "stats-grid-alarm-text"
        );


    if (
        !alarm ||
        !alarmText
    ) {
        return;
    }


    if (problems.length) {

        alarm.classList.add(
            "visible"
        );


        alarmText.textContent =
            problems.join(
                " • "
            );
    }
    else {

        alarm.classList.remove(
            "visible"
        );


        alarmText.textContent =
            "ALL SERVICES NORMAL";
    }
}



/* ============================================================
   CLOCK
   ============================================================ */

function clock(){

    text(
        "stats-grid-clock",

        new Date()
            .toLocaleTimeString()
    );
}



/* ============================================================
   START
   ============================================================ */

function start(){

    window.setTimeout(
        function(){

            install();

            pollLive();

            pollStatus();

            clock();

        },
        650
    );


    window.setInterval(
        pollLive,
        1000
    );


    window.setInterval(
        pollStatus,
        5000
    );


    window.setInterval(
        clock,
        1000
    );


    window.addEventListener(
        "resize",
        function(){

            drawChart(
                "stats-latency-chart",
                telemetryLatency,
                "#38d8ff",
                250
            );


            drawChart(
                "stats-script-chart",
                scriptHistory,
                "#e1b445",
                null
            );
        }
    );


    window.AustraliaGridServices = {

        refresh:
            function(){

                pollLive();

                pollStatus();
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