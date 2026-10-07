(function(){

"use strict";


/*
 ============================================================
 AUSTRALIA CONTROL CENTER
 TRUE LIVE GAUGE DRIVER V5

 Data sources:

   /Other/admin-region-live-direct.php
   /Other/admin-region-performance.php

 Poll interval:
   1000 ms

 The live needle is physically separate from the old needle.
 ============================================================
*/


let busy =
    false;


let lastActiveRegion =
    "";


/* ============================================================
   HELPERS
   ============================================================ */

function num(value){

    const number =
        Number(value);


    return Number.isFinite(number)
        ? number
        : null;
}


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


function regionName(row){

    return String(
        row?.RegionName ??
        row?.regionName ??
        row?.Name ??
        row?.name ??
        ""
    ).trim();
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
   CREATE INDEPENDENT NEEDLE
   ============================================================ */

function liveNeedle(card){

    let needle =
        card.querySelector(
            ".stats-live-v5-needle"
        );


    if (needle) {
        return needle;
    }


    const stage =
        card.querySelector(
            ".stats-gauge-stage"
        );


    if (!stage) {
        return null;
    }


    needle =
        document.createElement(
            "div"
        );


    needle.className =
        "stats-live-v5-needle";


    stage.appendChild(
        needle
    );


    /*
     * Force browser to commit the zero position.
     */

    needle.style.transform =
        "rotate(-180deg)";


    needle.getBoundingClientRect();


    return needle;
}



/* ============================================================
   SET GAUGE
   ============================================================ */

function setGauge(
    key,
    displayValue,
    percentage,
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
        liveNeedle(
            card
        );


    if (!needle) {
        return;
    }


    const value =
        card.querySelector(
            ".stats-gauge-reading strong"
        );


    const state =
        card.querySelector(
            ".stats-gauge-state"
        );


    const bar =
        card.querySelector(
            ".stats-gauge-meter-fill"
        );


    card.classList.add(
        "v5-live"
    );


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


    const p =
        clamp(
            percentage,
            0,
            100
        );


    const degrees =
        -180 +
        (
            p *
            1.8
        );


    if (value) {

        value.textContent =
            displayValue;
    }


    if (state) {

        state.textContent =
            stateText;
    }


    if (bar) {

        bar.style.width =
            p.toFixed(2) +
            "%";
    }


    card.style.setProperty(
        "--gauge-progress",
        (
            p *
            1.8
        ).toFixed(3) +
        "deg"
    );


    /*
     * This is the ONLY needle we care about.
     */

    requestAnimationFrame(
        function(){

            needle.style.transform =
                "rotate(" +
                degrees.toFixed(3) +
                "deg)";
        }
    );
}



/* ============================================================
   SELECT REGION TO FOLLOW
   ============================================================ */

function selectLiveRegion(rows){

    const good =
        rows.filter(
            function(row){

                const sim =
                    metric(
                        row,
                        [
                            "SimFPS",
                            "simFPS",
                            "simFps"
                        ]
                    );


                return (
                    sim !== null &&
                    sim > 0
                );
            }
        );


    if (!good.length) {
        return null;
    }


    /*
     * FIRST CHOICE:
     * A region that currently has a real root agent.
     *
     * This means when Brett logs in, the gauges follow the
     * region he is actually standing in.
     */

    const occupied =
        good
        .map(
            function(row){

                return {

                    row:
                        row,

                    avatars:
                        metric(
                            row,
                            [
                                "RootAg",
                                "RootAgents",
                                "rootAgents"
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

        return occupied[0].row;
    }


    /*
     * SECOND CHOICE:
     * lowest current Sim FPS.
     *
     * This keeps the dashboard useful even with no avatars.
     */

    good.sort(
        function(a,b){

            const av =
                metric(
                    a,
                    [
                        "SimFPS",
                        "simFPS",
                        "simFps"
                    ]
                ) ?? 999;


            const bv =
                metric(
                    b,
                    [
                        "SimFPS",
                        "simFPS",
                        "simFps"
                    ]
                ) ?? 999;


            return av - bv;
        }
    );


    return good[0];
}



/* ============================================================
   LIVE OPENSIM
   ============================================================ */

function updateOpenSim(data){

    const rows =
        rowsOf(
            data
        );


    const row =
        selectLiveRegion(
            rows
        );


    if (!row) {
        return;
    }


    const name =
        regionName(
            row
        ) ||
        "REGION";


    lastActiveRegion =
        name;


    const sim =
        metric(
            row,
            [
                "SimFPS",
                "simFPS",
                "simFps"
            ]
        );


    const physics =
        metric(
            row,
            [
                "PhysicsFPS",
                "PhyFPS",
                "PhysFPS",
                "physicsFPS"
            ]
        );


    const frame =
        metric(
            row,
            [
                "TotlFt",
                "FrameMS",
                "FrameTime",
                "TotalFrameTime"
            ]
        );


    /* --------------------------------------------------------
       SIM FPS
       -------------------------------------------------------- */

    if (sim !== null) {

        setGauge(
            "sim",

            sim.toFixed(1),

            sim / 55 * 100,

            (
                sim >= 45
                    ? "GOOD"
                    : sim >= 30
                        ? "WARNING"
                        : "LOW"
            ) +
            " • " +
            name,

            sim >= 45
                ? "gauge-good"
                : sim >= 30
                    ? "gauge-warn"
                    : "gauge-bad"
        );
    }


    /* --------------------------------------------------------
       PHYSICS FPS
       -------------------------------------------------------- */

    if (physics !== null) {

        setGauge(
            "physics",

            physics.toFixed(1),

            physics / 55 * 100,

            (
                physics >= 45
                    ? "GOOD"
                    : physics >= 30
                        ? "WARNING"
                        : "LOW"
            ) +
            " • " +
            name,

            physics >= 45
                ? "gauge-good"
                : physics >= 30
                    ? "gauge-warn"
                    : "gauge-bad"
        );
    }


    /* --------------------------------------------------------
       FRAME TIME
       -------------------------------------------------------- */

    if (frame !== null) {

        setGauge(
            "frame",

            frame.toFixed(2),

            frame / 30 * 100,

            (
                frame <= 20
                    ? "GOOD"
                    : frame <= 25
                        ? "HOT"
                        : "CRITICAL"
            ) +
            " • " +
            name,

            frame <= 20
                ? "gauge-good"
                : frame <= 25
                    ? "gauge-warn"
                    : "gauge-bad"
        );
    }
}



/* ============================================================
   LIVE OPENSIM CPU
   ============================================================ */

function updateCpu(data){

    const rows =
        rowsOf(
            data
        );


    if (!rows.length) {
        return;
    }


    /*
     * CpuHostPercent is a host-normalised percentage for each
     * OpenSim process.
     *
     * Add all running OpenSim regions together so this gauge
     * reacts when avatars arrive, scripts wake up, physics
     * increases, etc.
     */

    let total =
        0;


    let found =
        false;


    for (const row of rows) {

        if (
            row?.Running === false
        ) {
            continue;
        }


        const value =
            metric(
                row,
                [
                    "CpuHostPercent",
                    "cpuHostPercent"
                ]
            );


        if (value === null) {
            continue;
        }


        total +=
            value;


        found =
            true;
    }


    if (!found) {
        return;
    }


    total =
        clamp(
            total,
            0,
            100
        );


    setGauge(
        "cpu",

        total.toFixed(1),

        total,

        "LIVE OPENSIM CPU",

        total < 65
            ? "gauge-good"
            : total < 85
                ? "gauge-warn"
                : "gauge-bad"
    );


    /*
     * Change title so there is no confusion with the slower
     * Windows system CPU card elsewhere on the page.
     */

    const card =
        document.querySelector(
            '.stats-gauge-card[data-gauge="cpu"]'
        );


    const title =
        card?.querySelector(
            ".stats-gauge-label"
        );


    if (title) {

        title.textContent =
            "OPENSIM CPU LOAD";
    }
}



/* ============================================================
   POLL
   ============================================================ */

async function poll(){

    if (
        busy ||
        document.visibilityState !==
        "visible"
    ) {
        return;
    }


    busy =
        true;


    try {

        const stamp =
            Date.now();


        const telemetryUrl =
            "/Other/admin-region-live-direct.php?_=" +
            stamp;


        const performanceUrl =
            "/Other/admin-region-performance.php?_=" +
            stamp;


        const results =
            await Promise.allSettled(
                [
                    fetch(
                        telemetryUrl,
                        {
                            credentials:
                                "same-origin",

                            cache:
                                "no-store"
                        }
                    ),

                    fetch(
                        performanceUrl,
                        {
                            credentials:
                                "same-origin",

                            cache:
                                "no-store"
                        }
                    )
                ]
            );


        /* ----------------------------------------------------
           OPENSIM TELEMETRY
           ---------------------------------------------------- */

        if (
            results[0].status ===
            "fulfilled" &&
            results[0].value.ok
        ) {

            const telemetry =
                await results[0]
                    .value
                    .json();


            if (
                telemetry &&
                telemetry.ok === true
            ) {

                updateOpenSim(
                    telemetry
                );
            }
        }


        /* ----------------------------------------------------
           PROCESS CPU
           ---------------------------------------------------- */

        if (
            results[1].status ===
            "fulfilled" &&
            results[1].value.ok
        ) {

            const performance =
                await results[1]
                    .value
                    .json();


            if (
                performance &&
                performance.ok === true
            ) {

                updateCpu(
                    performance
                );
            }
        }
    }
    catch(error) {

        console.debug(
            "Stats V5 live gauge poll:",
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
     * Existing gauge deck may take a moment to appear.
     */

    window.setTimeout(
        poll,
        300
    );


    /*
     * True live update.
     */

    window.setInterval(
        poll,
        1000
    );


    window.AustraliaStatsLiveV5 = {

        poll:
            poll,

        activeRegion:
            function(){

                return lastActiveRegion;
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
            once:
                true
        }
    );
}
else {

    start();
}

})();