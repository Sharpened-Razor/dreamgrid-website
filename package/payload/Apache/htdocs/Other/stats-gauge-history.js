(function(){

"use strict";


/*
 ============================================================
 AUSTRALIA CONTROL CENTER
 LIVE GAUGE HISTORY V1

 Stores:
   60 rolling samples per gauge

 Updates:
   once per second

 Displays:
   live trace
   rolling MIN
   rolling MAX
 ============================================================
*/


const MAX_SAMPLES =
    60;


const configs = {

    cpu:{
        minimum:0,
        maximum:100,
        decimals:1,
        unit:"%"
    },

    ram:{
        minimum:0,
        maximum:100,
        decimals:1,
        unit:"%"
    },

    sim:{
        minimum:0,
        maximum:55,
        decimals:1,
        unit:""
    },

    physics:{
        minimum:0,
        maximum:55,
        decimals:1,
        unit:""
    },

    frame:{
        minimum:0,
        maximum:30,
        decimals:2,
        unit:""
    },

    disk:{
        minimum:0,
        maximum:100,
        decimals:1,
        unit:"%"
    }
};


const histories =
    new Map();



function numberFrom(text){

    const match =
        String(
            text ??
            ""
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



/* ============================================================
   BUILD HISTORY UI
   ============================================================ */

function ensureHistory(card){

    let wrap =
        card.querySelector(
            ".stats-history-wrap"
        );


    if (wrap) {
        return wrap;
    }


    wrap =
        document.createElement(
            "div"
        );


    wrap.className =
        "stats-history-wrap";


    wrap.innerHTML =
        `
        <div class="stats-history-header">

            <div class="stats-history-title">
                60 SEC HISTORY
            </div>

            <div class="stats-history-minmax">

                <span class="stats-history-min">
                    MIN
                    <strong>—</strong>
                </span>

                <span class="stats-history-max">
                    MAX
                    <strong>—</strong>
                </span>

            </div>

        </div>

        <div class="stats-history-canvas-box">

            <canvas
                class="stats-history-canvas">
            </canvas>

        </div>
        `;


    card.appendChild(
        wrap
    );


    return wrap;
}



/* ============================================================
   COLOUR
   ============================================================ */

function traceColour(key){

    switch(key){

        case "cpu":
            return "#28d7ff";

        case "ram":
            return "#57e394";

        case "sim":
            return "#36d8ff";

        case "physics":
            return "#32ddce";

        case "frame":
            return "#efbb3d";

        case "disk":
            return "#d9ab39";

        default:
            return "#38cbff";
    }
}



/* ============================================================
   CANVAS SIZE
   ============================================================ */

function sizeCanvas(canvas){

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


    const width =
        Math.max(
            1,
            Math.round(
                rect.width *
                ratio
            )
        );


    const height =
        Math.max(
            1,
            Math.round(
                rect.height *
                ratio
            )
        );


    if (
        canvas.width !== width ||
        canvas.height !== height
    ) {

        canvas.width =
            width;


        canvas.height =
            height;
    }


    const context =
        canvas.getContext(
            "2d"
        );


    context.setTransform(
        ratio,
        0,
        0,
        ratio,
        0,
        0
    );


    return {

        context:
            context,

        width:
            rect.width,

        height:
            rect.height
    };
}



/* ============================================================
   DRAW TRACE
   ============================================================ */

function draw(
    key,
    canvas,
    values
){

    const info =
        sizeCanvas(
            canvas
        );


    if (!info) {
        return;
    }


    const ctx =
        info.context;


    const width =
        info.width;


    const height =
        info.height;


    ctx.clearRect(
        0,
        0,
        width,
        height
    );


    if (
        !values.length
    ) {
        return;
    }


    const config =
        configs[key];


    const range =
        config.maximum -
        config.minimum;


    const colour =
        traceColour(
            key
        );


    /*
     * Subtle area fill.
     */

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
                        (MAX_SAMPLES - 1)
                    ) *
                    width;


            const normal =
                clamp(
                    (
                        value -
                        config.minimum
                    ) /
                    range,
                    0,
                    1
                );


            const y =
                height -
                (
                    normal *
                    (
                        height - 4
                    )
                ) -
                2;


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


    const finalX =
        values.length <= 1
            ?
            0
            :
            (
                (
                    values.length -
                    1
                ) /
                (MAX_SAMPLES - 1)
            ) *
            width;


    ctx.lineTo(
        finalX,
        height
    );


    ctx.lineTo(
        0,
        height
    );


    ctx.closePath();


    const gradient =
        ctx.createLinearGradient(
            0,
            0,
            0,
            height
        );


    gradient.addColorStop(
        0,
        colour + "32"
    );


    gradient.addColorStop(
        1,
        colour + "03"
    );


    ctx.fillStyle =
        gradient;


    ctx.fill();


    /*
     * Main glowing trace.
     */

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
                        (MAX_SAMPLES - 1)
                    ) *
                    width;


            const normal =
                clamp(
                    (
                        value -
                        config.minimum
                    ) /
                    range,
                    0,
                    1
                );


            const y =
                height -
                (
                    normal *
                    (
                        height - 4
                    )
                ) -
                2;


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
        1.35;


    ctx.shadowColor =
        colour;


    ctx.shadowBlur =
        5;


    ctx.stroke();


    ctx.shadowBlur =
        0;


    /*
     * Latest-value dot.
     */

    const lastValue =
        values[
            values.length - 1
        ];


    const lastNormal =
        clamp(
            (
                lastValue -
                config.minimum
            ) /
            range,
            0,
            1
        );


    const lastY =
        height -
        (
            lastNormal *
            (
                height - 4
            )
        ) -
        2;


    ctx.beginPath();


    ctx.arc(
        finalX,
        lastY,
        2,
        0,
        Math.PI * 2
    );


    ctx.fillStyle =
        colour;


    ctx.shadowColor =
        colour;


    ctx.shadowBlur =
        7;


    ctx.fill();


    ctx.shadowBlur =
        0;
}



/* ============================================================
   UPDATE MIN MAX
   ============================================================ */

function updateMinMax(
    card,
    key,
    values
){

    if (!values.length) {
        return;
    }


    const config =
        configs[key];


    const minimum =
        Math.min(
            ...values
        );


    const maximum =
        Math.max(
            ...values
        );


    const minNode =
        card.querySelector(
            ".stats-history-min strong"
        );


    const maxNode =
        card.querySelector(
            ".stats-history-max strong"
        );


    if (minNode) {

        minNode.textContent =
            minimum.toFixed(
                config.decimals
            ) +
            config.unit;
    }


    if (maxNode) {

        maxNode.textContent =
            maximum.toFixed(
                config.decimals
            ) +
            config.unit;
    }
}



/* ============================================================
   SAMPLE ONE GAUGE
   ============================================================ */

function sampleCard(card){

    const key =
        card.dataset.gauge;


    if (
        !key ||
        !configs[key]
    ) {
        return;
    }


    const reading =
        card.querySelector(
            ".stats-gauge-reading strong"
        );


    if (!reading) {
        return;
    }


    const value =
        numberFrom(
            reading.textContent
        );


    if (value === null) {
        return;
    }


    ensureHistory(
        card
    );


    let values =
        histories.get(
            key
        );


    if (!values) {

        values =
            [];


        histories.set(
            key,
            values
        );
    }


    values.push(
        value
    );


    if (
        values.length >
        MAX_SAMPLES
    ) {

        values.splice(
            0,
            values.length -
            MAX_SAMPLES
        );
    }


    const canvas =
        card.querySelector(
            ".stats-history-canvas"
        );


    if (canvas) {

        draw(
            key,
            canvas,
            values
        );
    }


    updateMinMax(
        card,
        key,
        values
    );
}



/* ============================================================
   SAMPLE ALL
   ============================================================ */

function sampleAll(){

    document
        .querySelectorAll(
            ".stats-gauge-card"
        )
        .forEach(
            sampleCard
        );
}



/* ============================================================
   REDRAW ON WINDOW RESIZE
   ============================================================ */

function redrawAll(){

    document
        .querySelectorAll(
            ".stats-gauge-card"
        )
        .forEach(
            function(card){

                const key =
                    card.dataset.gauge;


                const values =
                    histories.get(
                        key
                    );


                const canvas =
                    card.querySelector(
                        ".stats-history-canvas"
                    );


                if (
                    values &&
                    canvas
                ) {

                    draw(
                        key,
                        canvas,
                        values
                    );
                }
            }
        );
}



/* ============================================================
   START
   ============================================================ */

function start(){

    /*
     * Give the gauge deck and V5 telemetry time to initialise.
     */

    window.setTimeout(
        sampleAll,
        700
    );


    /*
     * One sample per second = 60 seconds on screen.
     */

    window.setInterval(
        sampleAll,
        1000
    );


    window.addEventListener(
        "resize",
        redrawAll
    );


    window.AustraliaGaugeHistory = {

        reset:
            function(){

                histories.clear();


                document
                    .querySelectorAll(
                        ".stats-history-min strong, .stats-history-max strong"
                    )
                    .forEach(
                        function(node){

                            node.textContent =
                                "—";
                        }
                    );


                redrawAll();
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