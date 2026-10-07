(function(){

"use strict";


/*
 ============================================================
 AUSTRALIA CONTROL CENTER
 STATS GAUGE ENGINE V3

 ONE SCRIPT OWNS:
   - gauge deck
   - live values
   - needle movement
   - meter movement
   - gauge state
   - heat colours

 STARTUP:
   Needles sweep from zero to maximum, then settle at the
   actual live reading.

 LIVE:
   Needles animate whenever a value changes.
 ============================================================
*/


const needleStates =
    new WeakMap();



function clamp(
    value,
    minimum,
    maximum
){

    return Math.max(
        minimum,
        Math.min(
            maximum,
            value
        )
    );
}



function numberFrom(text){

    const match =
        String(
            text ?? ""
        )
        .replace(
            /,/g,
            ""
        )
        .match(
            /-?\d+(?:\.\d+)?/
        );


    if (!match) {
        return null;
    }


    const value =
        Number(
            match[0]
        );


    return Number.isFinite(value)
        ? value
        : null;
}



function percentageFrom(text){

    const match =
        String(
            text ?? ""
        )
        .match(
            /(\d+(?:\.\d+)?)\s*%/
        );


    if (!match) {
        return null;
    }


    const value =
        Number(
            match[1]
        );


    return Number.isFinite(value)
        ? value
        : null;
}



function textOf(selector){

    const node =
        document.querySelector(
            selector
        );


    return node
        ? node.textContent.trim()
        : "";
}



function panelValue(
    panelId,
    wantedLabel
){

    const panel =
        document.getElementById(
            panelId
        );


    if (!panel) {
        return "";
    }


    const wanted =
        String(
            wantedLabel
        )
        .trim()
        .toLowerCase();


    for (
        const row
        of panel.querySelectorAll(
            ".stat-row"
        )
    ) {

        const label =
            row.querySelector(
                ".stat-label"
            );


        const value =
            row.querySelector(
                ".stat-value"
            );


        if (
            !label ||
            !value
        ) {
            continue;
        }


        if (
            label.textContent
                .trim()
                .toLowerCase()
            === wanted
        ) {

            return value.textContent.trim();
        }
    }


    return "";
}



/* ============================================================
   CREATE GAUGE
   ============================================================ */

function createGauge(
    key,
    title,
    unit,
    minimum,
    maximum
){

    const card =
        document.createElement(
            "article"
        );


    card.className =
        "stats-gauge-card";


    card.dataset.gauge =
        key;


    card.innerHTML =
        `
        <div class="stats-gauge-label">
            ${title}
        </div>

        <div class="stats-gauge-stage">

            <div class="stats-gauge-arc"></div>

            <div class="stats-gauge-needle"></div>

            <div class="stats-gauge-hub"></div>

            <div class="stats-gauge-ticks">

                <span>
                    ${minimum}
                </span>

                <span>
                    ${maximum}
                </span>

            </div>

        </div>

        <div class="stats-gauge-reading">

            <strong>
                —
            </strong>

            <span>
                ${unit}
            </span>

        </div>

        <div class="stats-gauge-state">
            WAITING
        </div>

        <div class="stats-gauge-meter">

            <span class="stats-gauge-meter-fill"></span>

        </div>
        `;


    return card;
}



/* ============================================================
   INSTALL DECK
   ============================================================ */

function installDeck(){

    if (
        document.getElementById(
            "stats-live-gauges"
        )
    ) {
        return;
    }


    const summaries =
        document.querySelector(
            ".summary-grid"
        );


    if (!summaries) {
        return;
    }


    const panel =
        document.createElement(
            "section"
        );


    panel.id =
        "stats-live-gauges";


    panel.className =
        "stats-gauges-panel";


    panel.innerHTML =
        `
        <header class="stats-gauges-head">

            <div>

                <div class="stats-gauges-kicker">
                    LIVE TELEMETRY
                </div>

                <div class="stats-gauges-title">
                    PERFORMANCE INSTRUMENTS
                </div>

            </div>

            <div class="stats-gauges-live">
                LIVE
            </div>

        </header>

        <div class="stats-gauge-grid"></div>
        `;


    const grid =
        panel.querySelector(
            ".stats-gauge-grid"
        );


    grid.appendChild(
        createGauge(
            "cpu",
            "CPU LOAD",
            "%",
            "0",
            "100"
        )
    );


    grid.appendChild(
        createGauge(
            "ram",
            "MEMORY USED",
            "%",
            "0",
            "100"
        )
    );


    grid.appendChild(
        createGauge(
            "sim",
            "SIM FPS",
            "FPS",
            "0",
            "55"
        )
    );


    grid.appendChild(
        createGauge(
            "physics",
            "PHYSICS FPS",
            "FPS",
            "0",
            "55"
        )
    );


    grid.appendChild(
        createGauge(
            "frame",
            "FRAME TIME",
            "ms",
            "0",
            "30"
        )
    );


    grid.appendChild(
        createGauge(
            "disk",
            "DRIVE USED",
            "%",
            "0",
            "100"
        )
    );


    summaries.insertAdjacentElement(
        "afterend",
        panel
    );
}



/* ============================================================
   ANGLE
   ============================================================ */

function percentToAngle(percent){

    return (
        -180 +
        (
            clamp(
                percent,
                0,
                100
            ) *
            1.8
        )
    );
}



/* ============================================================
   EASING
   ============================================================ */

function easeInOutCubic(value){

    return value < .5
        ?
        4 *
        value *
        value *
        value
        :
        1 -
        Math.pow(
            -2 * value + 2,
            3
        ) /
        2;
}



/* ============================================================
   PHYSICAL NEEDLE POSITION
   ============================================================ */

function applyNeedleAngle(
    needle,
    angle
){

    /*
     * IMPORTANT:
     *
     * Inline !important means no CSS heat rule or old
     * transition rule can steal control of the transform.
     */

    needle.style.setProperty(
        "transition",
        "none",
        "important"
    );


    needle.style.setProperty(
        "transform",
        "rotate(" +
        angle.toFixed(3) +
        "deg)",
        "important"
    );
}



/* ============================================================
   ANIMATE NEEDLE
   ============================================================ */

function animateNeedle(
    needle,
    targetAngle,
    duration,
    finished
){

    let state =
        needleStates.get(
            needle
        );


    if (!state) {

        state = {
            angle:
                -180,

            animation:
                null,

            started:
                false,

            target:
                -180,

            serial:
                0
        };


        needleStates.set(
            needle,
            state
        );
    }


    state.serial++;


    const serial =
        state.serial;


    if (state.animation !== null) {

        cancelAnimationFrame(
            state.animation
        );


        state.animation =
            null;
    }


    const fromAngle =
        Number.isFinite(
            state.angle
        )
            ? state.angle
            : -180;


    const startedAt =
        performance.now();


    state.target =
        targetAngle;


    function step(now){

        const liveState =
            needleStates.get(
                needle
            );


        if (
            !liveState ||
            liveState.serial !==
            serial
        ) {
            return;
        }


        const elapsed =
            now -
            startedAt;


        const progress =
            clamp(
                elapsed /
                duration,
                0,
                1
            );


        const eased =
            easeInOutCubic(
                progress
            );


        const angle =
            fromAngle +
            (
                (
                    targetAngle -
                    fromAngle
                ) *
                eased
            );


        liveState.angle =
            angle;


        applyNeedleAngle(
            needle,
            angle
        );


        if (
            progress < 1
        ) {

            liveState.animation =
                requestAnimationFrame(
                    step
                );
        }
        else {

            liveState.angle =
                targetAngle;


            liveState.animation =
                null;


            applyNeedleAngle(
                needle,
                targetAngle
            );


            if (
                typeof finished ===
                "function"
            ) {

                finished();
            }
        }


        needleStates.set(
            needle,
            liveState
        );
    }


    state.animation =
        requestAnimationFrame(
            step
        );


    needleStates.set(
        needle,
        state
    );
}



/* ============================================================
   STARTUP SWEEP
   ============================================================ */

function startupSweep(
    needle,
    targetAngle,
    delay
){

    let state =
        needleStates.get(
            needle
        );


    if (
        state &&
        state.started
    ) {

        animateNeedle(
            needle,
            targetAngle,
            650
        );


        return;
    }


    state =
        state ||
        {
            angle:
                -180,

            animation:
                null,

            started:
                false,

            target:
                -180,

            serial:
                0
        };


    state.started =
        true;


    state.angle =
        -180;


    needleStates.set(
        needle,
        state
    );


    applyNeedleAngle(
        needle,
        -180
    );


    /*
     * Instrument-cluster style startup:
     *
     * LEFT -> FULL RIGHT -> LIVE VALUE
     */

    window.setTimeout(
        function(){

            animateNeedle(
                needle,
                0,
                700,
                function(){

                    window.setTimeout(
                        function(){

                            animateNeedle(
                                needle,
                                targetAngle,
                                850
                            );
                        },
                        100
                    );
                }
            );
        },
        delay
    );
}



/* ============================================================
   SET GAUGE
   ============================================================ */

function setGauge(
    key,
    displayValue,
    percent,
    stateText,
    tone
){

    const card =
        document.querySelector(
            '.stats-gauge-card[data-gauge="' +
            key +
            '"]'
        );


    if (!card) {
        return;
    }


    const needle =
        card.querySelector(
            ".stats-gauge-needle"
        );


    const reading =
        card.querySelector(
            ".stats-gauge-reading strong"
        );


    const status =
        card.querySelector(
            ".stats-gauge-state"
        );


    const fill =
        card.querySelector(
            ".stats-gauge-meter-fill"
        );


    if (
        !needle ||
        !reading ||
        !status ||
        !fill
    ) {
        return;
    }


    card.classList.remove(
        "gauge-good",
        "gauge-warn",
        "gauge-bad"
    );


    if (tone) {

        card.classList.add(
            tone
        );
    }


    if (
        percent === null ||
        !Number.isFinite(
            Number(percent)
        )
    ) {

        reading.textContent =
            "—";


        status.textContent =
            "WAITING";


        fill.style.width =
            "0%";


        return;
    }


    const p =
        clamp(
            Number(percent),
            0,
            100
        );


    reading.textContent =
        String(
            displayValue
        );


    status.textContent =
        stateText;


    /*
     * Animated Regions-style bottom bar.
     */

    fill.style.setProperty(
        "transition",
        "width .65s ease",
        "important"
    );


    fill.style.width =
        p.toFixed(2) +
        "%";


    /*
     * Gauge arc still follows live percentage.
     */

    card.style.setProperty(
        "--gauge-progress",
        (
            p *
            1.8
        ).toFixed(2) +
        "deg"
    );


    const targetAngle =
        percentToAngle(
            p
        );


    const state =
        needleStates.get(
            needle
        );


    if (
        !state ||
        !state.started
    ) {

        const cards =
            Array.from(
                document.querySelectorAll(
                    ".stats-gauge-card"
                )
            );


        const index =
            Math.max(
                0,
                cards.indexOf(
                    card
                )
            );


        startupSweep(
            needle,
            targetAngle,
            index * 70
        );


        return;
    }


    /*
     * Only physically animate if the reading moved enough
     * to actually be visible.
     */

    if (
        Number.isFinite(
            state.target
        ) &&
        Math.abs(
            state.target -
            targetAngle
        ) <
        .10
    ) {

        return;
    }


    animateNeedle(
        needle,
        targetAngle,
        650
    );
}



/* ============================================================
   READ CURRENT STATS VALUES
   ============================================================ */

function refreshGauges(){

    installDeck();


    /* --------------------------------------------------------
       CPU
       -------------------------------------------------------- */

    const cpu =
        numberFrom(
            textOf(
                "#summary-cpu"
            )
        );


    setGauge(
        "cpu",

        cpu === null
            ? "—"
            : cpu.toFixed(0),

        cpu,

        cpu === null
            ? "WAITING"
            : cpu < 70
                ? "NORMAL"
                : cpu < 90
                    ? "HOT"
                    : "CRITICAL",

        cpu === null
            ? ""
            : cpu < 70
                ? "gauge-good"
                : cpu < 90
                    ? "gauge-warn"
                    : "gauge-bad"
    );



    /* --------------------------------------------------------
       RAM
       -------------------------------------------------------- */

    const ram =
        percentageFrom(
            panelValue(
                "server-load",
                "RAM used"
            )
        );


    setGauge(
        "ram",

        ram === null
            ? "—"
            : ram.toFixed(1),

        ram,

        ram === null
            ? "WAITING"
            : ram < 75
                ? "NORMAL"
                : ram < 90
                    ? "HOT"
                    : "CRITICAL",

        ram === null
            ? ""
            : ram < 75
                ? "gauge-good"
                : ram < 90
                    ? "gauge-warn"
                    : "gauge-bad"
    );



    /* --------------------------------------------------------
       SIM FPS
       -------------------------------------------------------- */

    const sim =
        numberFrom(
            textOf(
                "#summary-fps"
            )
        );


    const simPercent =
        sim === null
            ? null
            : clamp(
                sim /
                55 *
                100,
                0,
                100
            );


    setGauge(
        "sim",

        sim === null
            ? "—"
            : sim.toFixed(1),

        simPercent,

        sim === null
            ? "WAITING"
            : sim >= 45
                ? "GOOD"
                : sim >= 30
                    ? "WARNING"
                    : "LOW",

        sim === null
            ? ""
            : sim >= 45
                ? "gauge-good"
                : sim >= 30
                    ? "gauge-warn"
                    : "gauge-bad"
    );



    /* --------------------------------------------------------
       PHYSICS FPS
       -------------------------------------------------------- */

    const physics =
        numberFrom(
            panelValue(
                "opensim-performance",
                "Average Physics FPS"
            )
        );


    const physicsPercent =
        physics === null
            ? null
            : clamp(
                physics /
                55 *
                100,
                0,
                100
            );


    setGauge(
        "physics",

        physics === null
            ? "—"
            : physics.toFixed(1),

        physicsPercent,

        physics === null
            ? "WAITING"
            : physics >= 45
                ? "GOOD"
                : physics >= 30
                    ? "WARNING"
                    : "LOW",

        physics === null
            ? ""
            : physics >= 45
                ? "gauge-good"
                : physics >= 30
                    ? "gauge-warn"
                    : "gauge-bad"
    );



    /* --------------------------------------------------------
       FRAME TIME
       -------------------------------------------------------- */

    const frame =
        numberFrom(
            panelValue(
                "opensim-performance",
                "Average frame time"
            )
        );


    const framePercent =
        frame === null
            ? null
            : clamp(
                frame /
                30 *
                100,
                0,
                100
            );


    setGauge(
        "frame",

        frame === null
            ? "—"
            : frame.toFixed(2),

        framePercent,

        frame === null
            ? "WAITING"
            : frame <= 20
                ? "GOOD"
                : frame <= 25
                    ? "HOT"
                    : "CRITICAL",

        frame === null
            ? ""
            : frame <= 20
                ? "gauge-good"
                : frame <= 25
                    ? "gauge-warn"
                    : "gauge-bad"
    );



    /* --------------------------------------------------------
       DISK
       -------------------------------------------------------- */

    const free =
        percentageFrom(
            textOf(
                "#summary-storage-detail"
            )
        );


    const diskUsed =
        free === null
            ? null
            : clamp(
                100 -
                free,
                0,
                100
            );


    setGauge(
        "disk",

        diskUsed === null
            ? "—"
            : diskUsed.toFixed(1),

        diskUsed,

        diskUsed === null
            ? "WAITING"
            : diskUsed < 75
                ? "GOOD"
                : diskUsed < 90
                    ? "HOT"
                    : "CRITICAL",

        diskUsed === null
            ? ""
            : diskUsed < 75
                ? "gauge-good"
                : diskUsed < 90
                    ? "gauge-warn"
                    : "gauge-bad"
    );
}



/* ============================================================
   START
   ============================================================ */

function start(){

    installDeck();


    /*
     * Wait briefly for Stats V3 to put its first live
     * values into the page, then perform the startup sweep.
     */

    window.setTimeout(
        refreshGauges,
        250
    );


    window.setTimeout(
        refreshGauges,
        700
    );


    /*
     * Gauge display follows Stats values once a second.
     */

    window.setInterval(
        refreshGauges,
        1000
    );


    window.AustraliaStatsGauges = {

        refresh:
            refreshGauges,

        /*
         * Allows a manual sweep from DevTools if ever needed:
         *
         * AustraliaStatsGauges.test()
         */

        test:
            function(){

                document
                    .querySelectorAll(
                        ".stats-gauge-needle"
                    )
                    .forEach(
                        function(needle,index){

                            let state =
                                needleStates.get(
                                    needle
                                );


                            state =
                                state ||
                                {
                                    angle:-180,
                                    animation:null,
                                    started:true,
                                    target:-180,
                                    serial:0
                                };


                            state.started =
                                true;


                            state.angle =
                                -180;


                            needleStates.set(
                                needle,
                                state
                            );


                            applyNeedleAngle(
                                needle,
                                -180
                            );


                            window.setTimeout(
                                function(){

                                    animateNeedle(
                                        needle,
                                        0,
                                        700,
                                        function(){

                                            window.setTimeout(
                                                refreshGauges,
                                                100
                                            );
                                        }
                                    );
                                },
                                index *
                                80
                            );
                        }
                    );
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