(function(){

"use strict";


/*
 ============================================================
 AUSTRALIA STATS
 DIRECT LIVE GAUGE NEEDLE ENGINE V1

 The main gauge script controls:
   - live numbers
   - meter bar width
   - heat colours
   - gauge state

 This helper reads the actual live meter percentage and
 drives the physical needle itself.

 It intentionally bypasses CSS transform transitions.
 ============================================================
*/


const needleState =
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


function meterPercent(card){

    const fill =
        card.querySelector(
            ".stats-gauge-meter-fill"
        );


    const meter =
        card.querySelector(
            ".stats-gauge-meter"
        );


    if (
        !fill ||
        !meter
    ) {
        return null;
    }


    /*
     * Preferred source:
     * existing gauge JavaScript writes an inline percentage.
     */

    const inline =
        String(
            fill.style.width ||
            ""
        )
        .trim();


    if (
        inline.endsWith("%")
    ) {

        const value =
            Number(
                inline.slice(
                    0,
                    -1
                )
            );


        if (
            Number.isFinite(value)
        ) {

            return clamp(
                value,
                0,
                100
            );
        }
    }


    /*
     * Fallback:
     * calculate actual rendered fill versus track.
     */

    const meterWidth =
        meter.getBoundingClientRect()
            .width;


    const fillWidth =
        fill.getBoundingClientRect()
            .width;


    if (
        meterWidth <= 0 ||
        !Number.isFinite(meterWidth) ||
        !Number.isFinite(fillWidth)
    ) {

        return null;
    }


    return clamp(
        fillWidth /
        meterWidth *
        100,
        0,
        100
    );
}


function percentToAngle(percent){

    /*
     * Gauge is a 180 degree semicircle.
     *
     * 0%   = -180 degrees
     * 50%  =  -90 degrees
     * 100% =    0 degrees
     */

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


function easeOutCubic(value){

    return (
        1 -
        Math.pow(
            1 - value,
            3
        )
    );
}


function setNeedleAngle(
    needle,
    angle
){

    /*
     * Disable the old CSS transform animation completely.
     * JavaScript performs the smooth movement itself.
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


    needle.style.setProperty(
        "will-change",
        "transform"
    );
}


function animateNeedle(
    needle,
    fromAngle,
    toAngle,
    duration
){

    const state =
        needleState.get(
            needle
        ) ||
        {};


    if (state.frame) {

        cancelAnimationFrame(
            state.frame
        );
    }


    const started =
        performance.now();


    function frame(now){

        const elapsed =
            now -
            started;


        const progress =
            clamp(
                elapsed /
                duration,
                0,
                1
            );


        const eased =
            easeOutCubic(
                progress
            );


        const angle =
            fromAngle +
            (
                (
                    toAngle -
                    fromAngle
                ) *
                eased
            );


        setNeedleAngle(
            needle,
            angle
        );


        const currentState =
            needleState.get(
                needle
            ) ||
            {};


        currentState.angle =
            angle;


        if (
            progress < 1
        ) {

            currentState.frame =
                requestAnimationFrame(
                    frame
                );
        }
        else {

            currentState.frame =
                null;

            currentState.angle =
                toAngle;
        }


        needleState.set(
            needle,
            currentState
        );
    }


    const newState =
        state;


    newState.frame =
        requestAnimationFrame(
            frame
        );


    needleState.set(
        needle,
        newState
    );
}


function initialiseNeedle(
    card,
    needle,
    targetAngle
){

    /*
     * First load:
     * sweep visibly from zero to the live reading.
     */

    setNeedleAngle(
        needle,
        -180
    );


    needleState.set(
        needle,
        {
            angle:
                -180,

            target:
                targetAngle,

            frame:
                null,

            ready:
                true
        }
    );


    setTimeout(
        function(){

            const state =
                needleState.get(
                    needle
                );


            if (!state) {
                return;
            }


            animateNeedle(
                needle,
                -180,
                targetAngle,
                1050
            );


            state.target =
                targetAngle;


            needleState.set(
                needle,
                state
            );
        },
        140
    );
}


function updateCard(card){

    const needle =
        card.querySelector(
            ".stats-gauge-needle"
        );


    if (!needle) {
        return;
    }


    const percent =
        meterPercent(
            card
        );


    if (percent === null) {
        return;
    }


    const targetAngle =
        percentToAngle(
            percent
        );


    let state =
        needleState.get(
            needle
        );


    if (!state) {

        initialiseNeedle(
            card,
            needle,
            targetAngle
        );

        return;
    }


    /*
     * Do nothing if the live value has not actually changed.
     */

    if (
        Number.isFinite(
            state.target
        ) &&
        Math.abs(
            targetAngle -
            state.target
        ) <
        0.15
    ) {

        return;
    }


    const currentAngle =
        Number.isFinite(
            state.angle
        )
            ? state.angle
            : targetAngle;


    state.target =
        targetAngle;


    needleState.set(
        needle,
        state
    );


    animateNeedle(
        needle,
        currentAngle,
        targetAngle,
        720
    );
}


function updateAll(){

    document
        .querySelectorAll(
            ".stats-gauge-card"
        )
        .forEach(
            updateCard
        );
}


/*
 * Gauge cards are created dynamically by stats-gauges.js.
 * Watch for them being added or rebuilt.
 */

let scheduled =
    false;


const observer =
    new MutationObserver(
        function(){

            if (scheduled) {
                return;
            }


            scheduled =
                true;


            requestAnimationFrame(
                function(){

                    scheduled =
                        false;

                    updateAll();
                }
            );
        }
    );


function start(){

    observer.observe(
        document.body,
        {
            childList:
                true,

            subtree:
                true
        }
    );


    /*
     * First sweep.
     */

    setTimeout(
        updateAll,
        250
    );


    /*
     * Existing gauge values refresh independently.
     * Poll the visible meter state frequently enough that the
     * needles react immediately when those readings change.
     */

    window.setInterval(
        updateAll,
        300
    );


    window.AustraliaStatsGaugeNeedles = {
        refresh:
            updateAll
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