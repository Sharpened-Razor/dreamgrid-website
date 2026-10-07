(function(){

"use strict";


let selectedRange =
    "1h";


let currentSamples =
    [];


let currentData =
    null;


let busy =
    false;


/* ============================================================
   RANGE DEFINITIONS
   ============================================================ */

const ranges = {

    "1h":{
        ms:
            60 * 60 * 1000,

        expected:
            60
    },

    "6h":{
        ms:
            6 * 60 * 60 * 1000,

        expected:
            360
    },

    "24h":{
        ms:
            24 * 60 * 60 * 1000,

        expected:
            1440
    },

    "7d":{
        ms:
            7 * 24 * 60 * 60 * 1000,

        expected:
            10080
    }
};



/* ============================================================
   HELPERS
   ============================================================ */

function number(value){

    const n =
        Number(value);


    return Number.isFinite(n)
        ? n
        : null;
}


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


function seriesValues(
    samples,
    key
){

    return samples
        .map(
            row =>
                number(
                    row[key]
                )
        )
        .filter(
            value =>
                value !== null
        );
}


function average(list){

    if (!list.length) {
        return null;
    }


    return (
        list.reduce(
            (sum,value) =>
                sum + value,
            0
        ) /
        list.length
    );
}


function minimum(list){

    return list.length
        ?
        Math.min(
            ...list
        )
        :
        null;
}


function maximum(list){

    return list.length
        ?
        Math.max(
            ...list
        )
        :
        null;
}


function format(
    value,
    decimals,
    suffix
){

    if (
        value === null ||
        !Number.isFinite(value)
    ) {
        return "—";
    }


    return (
        value.toFixed(
            decimals
        ) +
        (
            suffix ||
            ""
        )
    );
}



/* ============================================================
   PANEL
   ============================================================ */

function panel(){

    return document.getElementById(
        "stats-history-panel"
    );
}


function ensureExtraStatus(){

    const root =
        panel();


    if (!root) {
        return;
    }


    if (
        document.getElementById(
            "stats-history-progress"
        )
    ) {
        return;
    }


    const status =
        document.getElementById(
            "stats-history-status"
        );


    if (!status) {
        return;
    }


    const progress =
        document.createElement(
            "span"
        );


    progress.id =
        "stats-history-progress";


    progress.className =
        "stats-history-progress";


    progress.textContent =
        "WAITING FOR DATA";


    status.insertAdjacentElement(
        "beforebegin",
        progress
    );
}



/* ============================================================
   CANVAS PREPARATION
   ============================================================ */

function prepareCanvas(id){

    const canvas =
        document.getElementById(
            id
        );


    if (!canvas) {
        return null;
    }


    const rect =
        canvas.getBoundingClientRect();


    if (
        rect.width <= 0 ||
        rect.height <= 0
    ) {
        return null;
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


    return {

        canvas:
            canvas,

        ctx:
            ctx,

        width:
            rect.width,

        height:
            rect.height
    };
}



/* ============================================================
   EMPTY / COLLECTING MESSAGE
   ============================================================ */

function drawMessage(
    canvas,
    message
){

    const ctx =
        canvas.ctx;


    ctx.save();


    ctx.fillStyle =
        "rgba(121,149,157,.70)";


    ctx.font =
        '700 9px Consolas, monospace';


    ctx.textAlign =
        "center";


    ctx.textBaseline =
        "middle";


    ctx.fillText(
        message,
        canvas.width / 2,
        canvas.height / 2
    );


    ctx.restore();
}



/* ============================================================
   REAL-TIME HISTORY LINE
   ============================================================ */

function drawTimeSeries(
    canvas,
    samples,
    key,
    colour,
    minValue,
    maxValue
){

    const valid =
        [];


    for (const row of samples) {

        const value =
            number(
                row[key]
            );


        if (value === null) {
            continue;
        }


        const stamp =
            new Date(
                row.ts
            ).getTime();


        if (!Number.isFinite(stamp)) {
            continue;
        }


        valid.push({

            time:
                stamp,

            value:
                value
        });
    }


    if (!valid.length) {

        drawMessage(
            canvas,
            "WAITING FOR HISTORY DATA"
        );


        return;
    }


    const definition =
        ranges[
            selectedRange
        ] ||
        ranges["1h"];


    /*
     * Anchor the graph to NOW.
     *
     * A new 1-hour history with three samples will therefore show
     * those three points at the far right side of the graph rather
     * than falsely stretching them across the entire hour.
     */

    const endTime =
        Date.now();


    const startTime =
        endTime -
        definition.ms;


    const valueRange =
        Math.max(
            .000001,
            maxValue -
            minValue
        );


    const ctx =
        canvas.ctx;


    const points =
        [];


    for (const item of valid) {

        const xNormal =
            (
                item.time -
                startTime
            ) /
            definition.ms;


        if (
            xNormal < 0 ||
            xNormal > 1.01
        ) {
            continue;
        }


        const yNormal =
            Math.max(
                0,
                Math.min(
                    1,
                    (
                        item.value -
                        minValue
                    ) /
                    valueRange
                )
            );


        points.push({

            x:
                xNormal *
                canvas.width,

            y:
                canvas.height -
                (
                    yNormal *
                    (
                        canvas.height - 10
                    )
                ) -
                5,

            value:
                item.value
        });
    }


    if (!points.length) {

        drawMessage(
            canvas,
            "NO SAMPLES IN THIS PERIOD"
        );


        return;
    }


    /*
     * CONNECT SAMPLE POINTS
     */

    if (points.length >= 2) {

        ctx.beginPath();


        points.forEach(
            function(point,index){

                if (index === 0) {

                    ctx.moveTo(
                        point.x,
                        point.y
                    );
                }
                else {

                    ctx.lineTo(
                        point.x,
                        point.y
                    );
                }
            }
        );


        ctx.strokeStyle =
            colour;


        ctx.lineWidth =
            1.6;


        ctx.shadowColor =
            colour;


        ctx.shadowBlur =
            6;


        ctx.stroke();


        ctx.shadowBlur =
            0;
    }


    /*
     * DRAW EVERY REAL SAMPLE
     */

    points.forEach(
        function(point){

            ctx.beginPath();


            ctx.arc(
                point.x,
                point.y,
                2.6,
                0,
                Math.PI * 2
            );


            ctx.fillStyle =
                colour;


            ctx.shadowColor =
                colour;


            ctx.shadowBlur =
                8;


            ctx.fill();


            ctx.shadowBlur =
                0;
        }
    );


    /*
     * Larger newest-sample indicator.
     */

    const newest =
        points[
            points.length - 1
        ];


    ctx.beginPath();


    ctx.arc(
        newest.x,
        newest.y,
        4,
        0,
        Math.PI * 2
    );


    ctx.strokeStyle =
        "#eefcff";


    ctx.lineWidth =
        1;


    ctx.stroke();
}



/* ============================================================
   AUTO-SCALE SERIES
   ============================================================ */

function autoMaximum(
    samples,
    keys,
    minimumMax
){

    let highest =
        minimumMax;


    for (const row of samples) {

        for (const key of keys) {

            const value =
                number(
                    row[key]
                );


            if (
                value !== null &&
                value > highest
            ) {

                highest =
                    value;
            }
        }
    }


    return highest;
}



/* ============================================================
   DRAW ALL CHARTS
   ============================================================ */

function drawCharts(){

    let canvas;


    /*
     * SERVER CPU + RAM
     */

    canvas =
        prepareCanvas(
            "history-server-chart"
        );


    if (canvas) {

        drawTimeSeries(
            canvas,
            currentSamples,
            "cpu",
            "#39d8ff",
            0,
            100
        );


        drawTimeSeries(
            canvas,
            currentSamples,
            "ramUsedPct",
            "#5ce39e",
            0,
            100
        );
    }


    /*
     * SIM + PHYSICS
     */

    canvas =
        prepareCanvas(
            "history-fps-chart"
        );


    if (canvas) {

        drawTimeSeries(
            canvas,
            currentSamples,
            "avgSim",
            "#39d8ff",
            0,
            55
        );


        drawTimeSeries(
            canvas,
            currentSamples,
            "avgPhysics",
            "#39ddc8",
            0,
            55
        );
    }


    /*
     * SCRIPT EPS
     */

    const epsMax =
        autoMaximum(
            currentSamples,
            [
                "scriptEps"
            ],
            100
        );


    canvas =
        prepareCanvas(
            "history-script-chart"
        );


    if (canvas) {

        drawTimeSeries(
            canvas,
            currentSamples,
            "scriptEps",
            "#e7b947",
            0,
            epsMax
        );
    }


    setText(
        "history-script-scale",

        Math.ceil(
            epsMax
        ).toLocaleString() +
        " EPS"
    );


    /*
     * AVATARS
     */

    const avatarMax =
        autoMaximum(
            currentSamples,
            [
                "rootAgents",
                "childAgents"
            ],
            1
        );


    canvas =
        prepareCanvas(
            "history-avatar-chart"
        );


    if (canvas) {

        drawTimeSeries(
            canvas,
            currentSamples,
            "rootAgents",
            "#61e3a8",
            0,
            avatarMax
        );


        drawTimeSeries(
            canvas,
            currentSamples,
            "childAgents",
            "#5dccea",
            0,
            avatarMax
        );
    }


    setText(
        "history-avatar-scale",

        String(
            Math.ceil(
                avatarMax
            )
        )
    );
}



/* ============================================================
   SUMMARY
   ============================================================ */

function updateSummary(data){

    const samples =
        Array.isArray(
            data.samples
        )
        ?
        data.samples
        :
        [];


    const cpus =
        seriesValues(
            samples,
            "cpu"
        );


    setText(
        "history-cpu",

        format(
            average(cpus),
            1,
            "%"
        ) +
        " / " +
        format(
            maximum(cpus),
            1,
            "%"
        )
    );


    setText(
        "history-opensim-cpu",

        format(
            maximum(
                seriesValues(
                    samples,
                    "opensimCpu"
                )
            ),
            1,
            "%"
        )
    );


    setText(
        "history-low-sim",

        format(
            minimum(
                seriesValues(
                    samples,
                    "lowSim"
                )
            ),
            1,
            ""
        )
    );


    setText(
        "history-low-physics",

        format(
            minimum(
                seriesValues(
                    samples,
                    "lowPhysics"
                )
            ),
            1,
            ""
        )
    );


    const peakEps =
        maximum(
            seriesValues(
                samples,
                "scriptEps"
            )
        );


    setText(
        "history-script-eps",

        peakEps === null
            ?
            "—"
            :
            Math.round(
                peakEps
            ).toLocaleString()
    );


    const peakAvatars =
        maximum(
            seriesValues(
                samples,
                "rootAgents"
            )
        );


    setText(
        "history-avatars",

        peakAvatars === null
            ?
            "—"
            :
            Math.round(
                peakAvatars
            ).toLocaleString()
    );


    const count =
        Number(
            data.fullSampleCount ??
            samples.length
        );


    setText(
        "history-samples",

        count.toLocaleString() +
        (
            count === 1
                ?
                " sample"
                :
                " samples"
        )
    );


    updateProgress(
        count
    );
}



/* ============================================================
   COLLECTION PROGRESS
   ============================================================ */

function updateProgress(count){

    ensureExtraStatus();


    const node =
        document.getElementById(
            "stats-history-progress"
        );


    if (!node) {
        return;
    }


    const definition =
        ranges[
            selectedRange
        ] ||
        ranges["1h"];


    const expected =
        definition.expected;


    if (count <= 0) {

        node.textContent =
            "NO SAMPLES YET";


        node.classList.add(
            "collecting"
        );


        return;
    }


    if (count < expected) {

        node.textContent =
            "COLLECTING " +
            count.toLocaleString() +
            " / " +
            expected.toLocaleString() +
            " SAMPLES";


        node.classList.add(
            "collecting"
        );


        return;
    }


    node.textContent =
        count.toLocaleString() +
        " SAMPLES";


    node.classList.remove(
        "collecting"
    );
}



/* ============================================================
   EVENTS
   ============================================================ */

function updateEvents(events){

    const list =
        document.getElementById(
            "stats-history-events-list"
        );


    if (!list) {
        return;
    }


    list.innerHTML =
        "";


    if (
        !Array.isArray(events) ||
        !events.length
    ) {

        list.innerHTML =
            `
            <div class="stats-history-empty">
                No service or avatar changes recorded in this period.
            </div>
            `;


        return;
    }


    events.forEach(
        function(event){

            const row =
                document.createElement(
                    "div"
                );


            row.className =
                "stats-history-event " +
                (
                    event.level ||
                    ""
                );


            const when =
                document.createElement(
                    "span"
                );


            when.className =
                "stats-history-event-time";


            const date =
                new Date(
                    event.ts
                );


            when.textContent =
                Number.isNaN(
                    date.getTime()
                )
                ?
                String(
                    event.ts ||
                    ""
                )
                :
                date.toLocaleString();


            const message =
                document.createElement(
                    "span"
                );


            message.className =
                "stats-history-event-text";


            message.textContent =
                String(
                    event.text ||
                    ""
                );


            row.appendChild(
                when
            );


            row.appendChild(
                message
            );


            list.appendChild(
                row
            );
        }
    );
}



/* ============================================================
   COLLECTOR STATUS
   ============================================================ */

function updateStatus(data){

    const node =
        document.getElementById(
            "stats-history-status"
        );


    if (!node) {
        return;
    }


    node.classList.remove(
        "stale",
        "bad"
    );


    const status =
        data.status ||
        {};


    const age =
        number(
            data.collectorAgeSec
        );


    if (status.ok === false) {

        node.classList.add(
            "bad"
        );


        node.textContent =
            "COLLECTOR ERROR";


        return;
    }


    if (
        age !== null &&
        age > 180
    ) {

        node.classList.add(
            "stale"
        );


        node.textContent =
            "LAST SAMPLE " +
            Math.round(
                age / 60
            ) +
            " MIN AGO";


        return;
    }


    if (
        Number(
            data.fullSampleCount
        ) > 0
    ) {

        node.textContent =
            "COLLECTOR LIVE";


        return;
    }


    node.classList.add(
        "stale"
    );


    node.textContent =
        "WAITING FOR FIRST SAMPLE";
}



/* ============================================================
   RANGE BUTTONS
   ============================================================ */

function wireButtons(){

    const root =
        panel();


    if (!root) {
        return;
    }


    root
        .querySelectorAll(
            ".stats-history-range"
        )
        .forEach(
            function(button){

                if (
                    button.dataset.historyV2 ===
                    "1"
                ) {
                    return;
                }


                button.dataset.historyV2 =
                    "1";


                button.addEventListener(
                    "click",
                    function(){

                        selectedRange =
                            button.dataset.range ||
                            "1h";


                        root
                            .querySelectorAll(
                                ".stats-history-range"
                            )
                            .forEach(
                                item =>
                                    item.classList.remove(
                                        "active"
                                    )
                            );


                        button.classList.add(
                            "active"
                        );


                        load();
                    }
                );
            }
        );
}



/* ============================================================
   LOAD
   ============================================================ */

async function load(){

    if (busy) {
        return;
    }


    if (!panel()) {
        return;
    }


    wireButtons();

    ensureExtraStatus();


    busy =
        true;


    try {

        const response =
            await fetch(
                "stats-history-api.php?range=" +
                encodeURIComponent(
                    selectedRange
                ) +
                "&_=" +
                Date.now(),
                {
                    credentials:
                        "same-origin",

                    cache:
                        "no-store"
                }
            );


        if (!response.ok) {

            throw new Error(
                "History API HTTP " +
                response.status
            );
        }


        const data =
            await response.json();


        if (
            !data ||
            data.ok !== true
        ) {

            throw new Error(
                data?.error ||
                "History API failed."
            );
        }


        currentData =
            data;


        currentSamples =
            Array.isArray(
                data.samples
            )
            ?
            data.samples
            :
            [];


        updateStatus(
            data
        );


        updateSummary(
            data
        );


        updateEvents(
            data.events
        );


        drawCharts();
    }
    catch(error) {

        const node =
            document.getElementById(
                "stats-history-status"
            );


        if (node) {

            node.classList.add(
                "bad"
            );


            node.textContent =
                "HISTORY ERROR";
        }


        console.error(
            "Performance History V2:",
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
     * The main history module already creates the panel.
     * Give the page a moment to finish its normal startup.
     */

    window.setTimeout(
        function(){

            if (!panel()) {

                /*
                 * Previous V1 script normally creates it before
                 * this replacement runs. If it is not present yet,
                 * try again shortly.
                 */

                window.setTimeout(
                    start,
                    500
                );


                return;
            }


            wireButtons();

            ensureExtraStatus();

            load();

        },
        500
    );
}


/*
 * Refresh the browser view every 15 seconds.
 * Persistent samples are still collected once per minute.
 */

window.setInterval(
    function(){

        if (
            document.visibilityState ===
            "visible"
        ) {

            load();
        }

    },
    15000
);


window.addEventListener(
    "resize",
    drawCharts
);


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


window.AustraliaStatsHistoryV2 = {

    refresh:
        load,

    samples:
        function(){

            return currentSamples.length;
        }
};

})();