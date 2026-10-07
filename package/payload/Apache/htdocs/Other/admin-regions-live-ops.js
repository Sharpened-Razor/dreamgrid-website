(function(){

"use strict";


/*
 ============================================================
 AUSTRALIA CONTROL CENTER
 REGION MANAGER LIVE OPERATIONS V1

 This layer does not replace the existing Region Manager
 commands.

 START / STOP / RESTART reuse the existing Region Controls
 dialog buttons already wired by admin-regions.php.
 ============================================================
*/


let root =
    null;


let grid =
    null;


let rows =
    [];


let rowMap =
    new Map();


let cardMap =
    new Map();


let telemetry =
    new Map();


let processes =
    new Map();


let searchText =
    "";


let filter =
    "all";


let telemetryBusy =
    false;


let processBusy =
    false;



/* ============================================================
   HELPERS
   ============================================================ */

function norm(value){

    return String(
        value ??
        ""
    )
    .trim()
    .toLowerCase();
}


function displayName(row){

    return String(
        row?.dataset?.name ??
        "Unknown Region"
    ).trim();
}


function numberValue(
    object,
    names
){

    if (!object) {
        return null;
    }


    for (const name of names) {

        if (
            Object.prototype.hasOwnProperty.call(
                object,
                name
            )
        ) {

            const value =
                Number(
                    object[name]
                );


            if (Number.isFinite(value)) {
                return value;
            }
        }
    }


    return null;
}


function stringValue(
    object,
    names
){

    if (!object) {
        return "";
    }


    for (const name of names) {

        if (
            Object.prototype.hasOwnProperty.call(
                object,
                name
            ) &&
            object[name] !== null &&
            object[name] !== undefined
        ) {

            return String(
                object[name]
            );
        }
    }


    return "";
}


function telemetryRows(data){

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


function setText(
    element,
    selector,
    value
){

    const node =
        element.querySelector(
            selector
        );


    if (node) {
        node.textContent = value;
    }
}


function clamp(
    value,
    min,
    max
){

    return Math.max(
        min,
        Math.min(
            max,
            value
        )
    );
}



/* ============================================================
   BUILD ROW MAP
   ============================================================ */

function readRows(){

    const table =
        document.getElementById(
            "dgRegionTable"
        );


    if (!table) {
        return false;
    }


    rows =
        Array.from(
            table.querySelectorAll(
                ".dg-row"
            )
        );


    if (!rows.length) {
        return false;
    }


    rowMap.clear();


    for (const row of rows) {

        const name =
            displayName(
                row
            );


        if (name) {

            rowMap.set(
                norm(name),
                row
            );
        }
    }


    return true;
}



/* ============================================================
   FIND INSERT LOCATION
   ============================================================ */

function insertAnchor(){

    const table =
        document.getElementById(
            "dgRegionTable"
        );


    if (!table) {
        return null;
    }


    return (
        table.closest(
            ".dg-table-frame"
        ) ||
        table
    );
}



/* ============================================================
   BUILD MAIN PANEL
   ============================================================ */

function install(){

    if (
        document.getElementById(
            "rm-live-ops"
        )
    ) {

        root =
            document.getElementById(
                "rm-live-ops"
            );


        grid =
            root.querySelector(
                ".rm-card-grid"
            );


        return true;
    }


    if (!readRows()) {
        return false;
    }


    const anchor =
        insertAnchor();


    if (!anchor) {
        return false;
    }


    root =
        document.createElement(
            "section"
        );


    root.id =
        "rm-live-ops";


    root.className =
        "rm-live-ops";


    root.innerHTML =
        `
        <header class="rm-live-head">

            <div>

                <div class="rm-live-kicker">
                    GRID OPERATIONS
                </div>

                <div class="rm-live-title">
                    LIVE REGION OPERATIONS
                </div>

            </div>


            <div class="rm-live-head-right">

                <div
                    id="rm-live-clock"
                    class="rm-live-clock">
                    —
                </div>

                <div class="rm-live-indicator">
                    LIVE
                </div>

            </div>

        </header>


        <div class="rm-live-summary">

            <div class="rm-summary-card">

                <div class="rm-summary-label">
                    TOTAL REGIONS
                </div>

                <div
                    id="rm-summary-total"
                    class="rm-summary-value">
                    0
                </div>

            </div>


            <div class="rm-summary-card">

                <div class="rm-summary-label">
                    RUNNING
                </div>

                <div
                    id="rm-summary-running"
                    class="rm-summary-value green">
                    0
                </div>

            </div>


            <div class="rm-summary-card">

                <div class="rm-summary-label">
                    STOPPED
                </div>

                <div
                    id="rm-summary-stopped"
                    class="rm-summary-value gold">
                    0
                </div>

            </div>


            <div class="rm-summary-card">

                <div class="rm-summary-label">
                    ROOT AVATARS
                </div>

                <div
                    id="rm-summary-avatars"
                    class="rm-summary-value cyan">
                    0
                </div>

            </div>


            <div class="rm-summary-card">

                <div class="rm-summary-label">
                    AVG SIM FPS
                </div>

                <div
                    id="rm-summary-sim"
                    class="rm-summary-value cyan">
                    —
                </div>

            </div>


            <div class="rm-summary-card">

                <div class="rm-summary-label">
                    OPENSIM CPU
                </div>

                <div
                    id="rm-summary-cpu"
                    class="rm-summary-value green">
                    —
                </div>

            </div>

        </div>


        <div class="rm-live-toolbar">

            <input
                id="rm-search"
                class="rm-search"
                type="search"
                autocomplete="off"
                placeholder="Search region name...">


            <button
                class="rm-filter active"
                type="button"
                data-filter="all">
                ALL
            </button>


            <button
                class="rm-filter"
                type="button"
                data-filter="running">
                RUNNING
            </button>


            <button
                class="rm-filter"
                type="button"
                data-filter="stopped">
                STOPPED
            </button>


            <button
                class="rm-filter"
                type="button"
                data-filter="avatars">
                AVATARS
            </button>

        </div>


        <div class="rm-card-grid"></div>


        <div class="rm-empty">
            No regions match this filter.
        </div>
        `;


    anchor.parentNode.insertBefore(
        root,
        anchor
    );


    grid =
        root.querySelector(
            ".rm-card-grid"
        );


    buildCards();

    wireToolbar();

    updateClock();

    updateAllCards();

    return true;
}



/* ============================================================
   BUILD CARDS
   ============================================================ */

function buildCards(){

    if (!grid) {
        return;
    }


    grid.innerHTML =
        "";


    cardMap.clear();


    const sorted =
        [...rows]
        .sort(
            function(a,b){

                return displayName(a)
                    .localeCompare(
                        displayName(b),
                        undefined,
                        {
                            sensitivity:
                                "base"
                        }
                    );
            }
        );


    for (const row of sorted) {

        const name =
            displayName(
                row
            );


        const key =
            norm(
                name
            );


        const card =
            document.createElement(
                "article"
            );


        card.className =
            "rm-region-card";


        card.dataset.region =
            name;


        card.innerHTML =
            `
            <div class="rm-card-head">

                <div
                    class="rm-card-name"
                    title="">
                    —
                </div>

                <div class="rm-state">
                    —
                </div>

            </div>


            <div class="rm-card-metrics">


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        SIM FPS
                    </div>

                    <div class="rm-metric-value cyan rm-sim">
                        —
                    </div>

                </div>


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        PHYS FPS
                    </div>

                    <div class="rm-metric-value green rm-physics">
                        —
                    </div>

                </div>


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        AVATARS
                    </div>

                    <div class="rm-metric-value rm-avatars">
                        —
                    </div>

                </div>


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        FRAME
                    </div>

                    <div class="rm-metric-value gold rm-frame">
                        —
                    </div>

                </div>


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        SCRIPTS
                    </div>

                    <div class="rm-metric-value rm-scripts">
                        —
                    </div>

                </div>


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        SCRIPT EPS
                    </div>

                    <div class="rm-metric-value rm-eps">
                        —
                    </div>

                </div>


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        PRIMS
                    </div>

                    <div class="rm-metric-value rm-prims">
                        —
                    </div>

                </div>


                <div class="rm-metric">

                    <div class="rm-metric-label">
                        THREADS
                    </div>

                    <div class="rm-metric-value rm-threads">
                        —
                    </div>

                </div>


            </div>


            <div class="rm-bars">


                <div class="rm-bar-row sim">

                    <span class="rm-bar-label">
                        SIM
                    </span>

                    <span class="rm-bar-track">
                        <span class="rm-bar-fill"></span>
                    </span>

                    <span class="rm-bar-value">
                        —
                    </span>

                </div>


                <div class="rm-bar-row physics">

                    <span class="rm-bar-label">
                        PHYSICS
                    </span>

                    <span class="rm-bar-track">
                        <span class="rm-bar-fill"></span>
                    </span>

                    <span class="rm-bar-value">
                        —
                    </span>

                </div>


                <div class="rm-bar-row cpu">

                    <span class="rm-bar-label">
                        CPU
                    </span>

                    <span class="rm-bar-track">
                        <span class="rm-bar-fill"></span>
                    </span>

                    <span class="rm-bar-value">
                        —
                    </span>

                </div>


            </div>


            <div class="rm-process-line">


                <div class="rm-process-item">

                    <div class="rm-process-label">
                        RAM
                    </div>

                    <div class="rm-process-value rm-ram">
                        —
                    </div>

                </div>


                <div class="rm-process-item">

                    <div class="rm-process-label">
                        PID
                    </div>

                    <div class="rm-process-value rm-pid">
                        —
                    </div>

                </div>


                <div class="rm-process-item">

                    <div class="rm-process-label">
                        UPTIME
                    </div>

                    <div class="rm-process-value rm-uptime">
                        —
                    </div>

                </div>


                <div class="rm-process-item">

                    <div class="rm-process-label">
                        OAR
                    </div>

                    <div class="rm-process-value rm-oar">
                        —
                    </div>

                </div>


            </div>


            <div class="rm-card-actions">

                <button
                    class="rm-action controls"
                    type="button">
                    CONTROLS
                </button>

                <button
                    class="rm-action start"
                    type="button">
                    START
                </button>

                <button
                    class="rm-action stop"
                    type="button">
                    STOP
                </button>

                <button
                    class="rm-action restart"
                    type="button">
                    RESTART
                </button>

                <button
                    class="rm-action stats"
                    type="button">
                    STATS
                </button>

                <button
                    class="rm-action log"
                    type="button">
                    LOG
                </button>

                <button
                    class="rm-action edit"
                    type="button">
                    EDIT
                </button>

                <button
                    class="rm-action oar"
                    type="button">
                    OAR
                </button>

            </div>


            <div class="rm-card-footer">

                <span class="rm-card-live">
                    TELEMETRY —
                </span>

                <span class="rm-last-update">
                    —
                </span>

            </div>
            `;


        const title =
            card.querySelector(
                ".rm-card-name"
            );


        title.textContent =
            name;


        title.title =
            name;


        wireCard(
            card,
            row
        );


        grid.appendChild(
            card
        );


        cardMap.set(
            key,
            card
        );
    }
}



/* ============================================================
   OPEN EXISTING REGION CONTROLS
   ============================================================ */

function openExistingControls(row){

    if (!row) {
        return false;
    }


    const nameCell =
        row.querySelector(
            ".dg-region-name-cell"
        );


    if (nameCell) {

        nameCell.click();

        return true;
    }


    /*
     * Fallback:
     * Enter on the current row also opens the existing dialog.
     */

    row.focus();


    row.dispatchEvent(
        new KeyboardEvent(
            "keydown",
            {
                key:
                    "Enter",

                bubbles:
                    true
            }
        )
    );


    return true;
}



/* ============================================================
   REUSE EXISTING COMMAND BUTTON
   ============================================================ */

function useExistingControl(
    row,
    buttonId,
    confirmation
){

    if (!row) {
        return;
    }


    const name =
        displayName(
            row
        );


    if (
        confirmation &&
        !window.confirm(
            confirmation.replace(
                "{region}",
                name
            )
        )
    ) {
        return;
    }


    openExistingControls(
        row
    );


    window.setTimeout(
        function(){

            const button =
                document.getElementById(
                    buttonId
                );


            if (
                !button ||
                button.disabled
            ) {
                return;
            }


            button.click();

        },
        80
    );
}



/* ============================================================
   CARD BUTTONS
   ============================================================ */

function wireCard(
    card,
    row
){

    card
        .querySelector(
            ".controls"
        )
        .addEventListener(
            "click",
            function(){

                openExistingControls(
                    row
                );
            }
        );


    card
        .querySelector(
            ".start"
        )
        .addEventListener(
            "click",
            function(){

                useExistingControl(
                    row,
                    "dgStartRegionButton",
                    null
                );
            }
        );


    card
        .querySelector(
            ".stop"
        )
        .addEventListener(
            "click",
            function(){

                useExistingControl(
                    row,
                    "dgStopRegionButton",
                    "Stop {region}?"
                );
            }
        );


    card
        .querySelector(
            ".restart"
        )
        .addEventListener(
            "click",
            function(){

                useExistingControl(
                    row,
                    "dgRestartRegionButton",
                    "Restart {region}?"
                );
            }
        );


    card
        .querySelector(
            ".stats"
        )
        .addEventListener(
            "click",
            function(){

                if (
                    card.dataset.state !==
                    "running"
                ) {
                    return;
                }


                window.open(
                    "/Other/admin-region-statistics.php?region=" +
                    encodeURIComponent(
                        displayName(
                            row
                        )
                    ),
                    "_blank"
                );
            }
        );


    card
        .querySelector(
            ".log"
        )
        .addEventListener(
            "click",
            function(){

                window.open(
                    "/Other/admin-region-log.php?region=" +
                    encodeURIComponent(
                        displayName(
                            row
                        )
                    ),
                    "_blank"
                );
            }
        );


    card
        .querySelector(
            ".edit"
        )
        .addEventListener(
            "click",
            function(){

                window.location.href = "/Other/admin-region-edit.php?region=" + encodeURIComponent(displayName(row));
            }
        );


        /* AUSTRALIA LIVE OPS SAVE OAR V3.1 */

    card
        .querySelector(
            ".oar"
        )
        .addEventListener(
            "click",
            async function(){

                if (
                    card.dataset.state !==
                    "running"
                ) {
                    return;
                }


                const button =
                    card.querySelector(
                        ".oar"
                    );


                if (
                    !button ||
                    button.dataset.oarBusy === "1"
                ) {
                    return;
                }


                const region =
                    displayName(
                        row
                    );


                if (!region) {
                    return;
                }


                const originalText =
                    String(
                        button.textContent ||
                        "OAR"
                    ).trim();


                const overlay =
                    document.createElement(
                        "div"
                    );


                overlay.className =
                    "rm-oar-v3-backdrop";


                const dialog =
                    document.createElement(
                        "section"
                    );


                dialog.className =
                    "rm-oar-v3-dialog";


                dialog.setAttribute(
                    "role",
                    "dialog"
                );


                dialog.setAttribute(
                    "aria-modal",
                    "true"
                );


                dialog.innerHTML =
                    '<div class="rm-oar-v3-goldline"></div>' +
                    '<div class="rm-oar-v3-header">' +
                        '<div class="rm-oar-v3-badge">OAR</div>' +
                        '<div class="rm-oar-v3-heading">' +
                            '<div class="rm-oar-v3-kicker">REGION BACKUP</div>' +
                            '<div class="rm-oar-v3-title">SAVE REGION OAR</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="rm-oar-v3-divider"></div>' +
                    '<div class="rm-oar-v3-label">REGION</div>' +
                    '<div class="rm-oar-v3-region"></div>' +
                    '<div class="rm-oar-v3-message">' +
                        'Create a new verified OAR backup of this region?' +
                    '</div>' +
                    '<div class="rm-oar-v3-status" hidden></div>' +
                    '<div class="rm-oar-v3-actions">' +
                        '<button type="button" class="rm-oar-v3-button cancel">' +
                            'CANCEL' +
                        '</button>' +
                        '<button type="button" class="rm-oar-v3-button save">' +
                            'SAVE OAR' +
                        '</button>' +
                        '<button type="button" class="rm-oar-v3-button close" hidden>' +
                            'CLOSE' +
                        '</button>' +
                    '</div>';


                const regionNode =
                    dialog.querySelector(
                        ".rm-oar-v3-region"
                    );


                const titleNode =
                    dialog.querySelector(
                        ".rm-oar-v3-title"
                    );


                const messageNode =
                    dialog.querySelector(
                        ".rm-oar-v3-message"
                    );


                const statusNode =
                    dialog.querySelector(
                        ".rm-oar-v3-status"
                    );


                const cancelButton =
                    dialog.querySelector(
                        ".cancel"
                    );


                const saveButton =
                    dialog.querySelector(
                        ".save"
                    );


                const closeButton =
                    dialog.querySelector(
                        ".close"
                    );


                regionNode.textContent =
                    region;


                overlay.appendChild(
                    dialog
                );


                document.body.appendChild(
                    overlay
                );


                let requestRunning =
                    false;


                function restoreOarButton(){

                    button.dataset.oarBusy =
                        "0";

                    button.textContent =
                        originalText;

                    button.disabled =
                        card.dataset.state !==
                        "running";
                }


                function closeDialog(){

                    if (requestRunning) {
                        return;
                    }

                    document.removeEventListener(
                        "keydown",
                        keyHandler
                    );

                    overlay.remove();

                    restoreOarButton();
                }


                function keyHandler(event){

                    if (
                        event.key ===
                        "Escape" &&
                        !requestRunning
                    ) {

                        closeDialog();
                    }
                }


                cancelButton.addEventListener(
                    "click",
                    closeDialog
                );


                closeButton.addEventListener(
                    "click",
                    closeDialog
                );


                overlay.addEventListener(
                    "click",
                    function(event){

                        if (
                            event.target === overlay &&
                            !requestRunning
                        ) {

                            closeDialog();
                        }
                    }
                );


                document.addEventListener(
                    "keydown",
                    keyHandler
                );


                saveButton.addEventListener(
                    "click",
                    async function(){

                        if (requestRunning) {
                            return;
                        }


                        requestRunning =
                            true;


                        button.dataset.oarBusy =
                            "1";


                        button.disabled =
                            true;


                        button.textContent =
                            "SAVING...";


                        dialog.classList.add(
                            "working"
                        );


                        titleNode.textContent =
                            "SAVING REGION OAR";


                        messageNode.textContent =
                            "DreamGrid is creating and verifying the region archive.";


                        statusNode.hidden =
                            false;


                        statusNode.innerHTML =
                            '<span class="rm-oar-v3-spinner"></span>' +
                            '<span>BACKUP IN PROGRESS</span>';


                        cancelButton.hidden =
                            true;


                        saveButton.hidden =
                            true;


                        try {

                            const body =
                                new URLSearchParams();


                            body.set(
                                "command",
                                "SaveOAR"
                            );


                            body.set(
                                "region",
                                region
                            );


                            const response =
                                await fetch(
                                    "/Other/command.php",
                                    {
                                        method:
                                            "POST",

                                        credentials:
                                            "same-origin",

                                        cache:
                                            "no-store",

                                        headers: {
                                            "Content-Type":
                                                "application/x-www-form-urlencoded;charset=UTF-8"
                                        },

                                        body:
                                            body.toString()
                                    }
                                );


                            const raw =
                                await response.text();


                            let data;


                            try {

                                data =
                                    JSON.parse(
                                        raw
                                    );
                            }
                            catch (parseError) {

                                let preview =
                                    String(
                                        raw ||
                                        ""
                                    )
                                    .replace(
                                        /\s+/g,
                                        " "
                                    )
                                    .trim();


                                if (
                                    preview.length >
                                    600
                                ) {

                                    preview =
                                        preview.slice(
                                            0,
                                            600
                                        ) +
                                        "...";
                                }


                                                                if (preview === "") {
                                    data = {
                                        ok: true,
                                        pending: true,
                                        message: "DreamGrid accepted the OAR backup and is continuing it in the background."
                                    };
                                } else {
                                    throw new Error(
                                        "Save OAR returned a non-JSON response: " + preview
                                    );
                                }
                            }


                            if (
                                
                                !data ||
                                data.ok !== true
                            ) {

                                throw new Error(
                                    data &&
                                    (
                                        data.error ||
                                        data.message
                                    )
                                        ?
                                        String(
                                            data.error ||
                                            data.message
                                        )
                                        :
                                        "DreamGrid rejected the Save OAR request."
                                );
                            }


                            dialog.classList.remove(
                                "working"
                            );


                            dialog.classList.add(
                                "success"
                            );


                            titleNode.textContent =
                                data.pending === true ? "OAR BACKUP STARTED" : "OAR BACKUP SAVED";


                            messageNode.textContent =
                                String(
                                    data.message ||
                                    "The region archive was created and verified successfully."
                                );


                            statusNode.innerHTML =
                                '<span class="rm-oar-v3-result">✓</span>' +
                                (data.pending === true ? '<span>BACKUP CONTINUING</span>' : '<span>BACKUP COMPLETE</span>');


                            button.textContent =
                                data.pending === true ? "STARTED" : "SAVED";
                        }
                        catch (saveError) {

                            const failureMessage =
                                saveError &&
                                saveError.message
                                    ?
                                    saveError.message
                                    :
                                    "Unknown Save OAR error.";


                            dialog.classList.remove(
                                "working"
                            );


                            dialog.classList.add(
                                "error"
                            );


                            titleNode.textContent =
                                "OAR BACKUP FAILED";


                            messageNode.textContent =
                                failureMessage;


                            statusNode.innerHTML =
                                '<span class="rm-oar-v3-result">!</span>' +
                                '<span>BACKUP ERROR</span>';


                            button.textContent =
                                "ERROR";
                        }
                        finally {

                            requestRunning =
                                false;


                            closeButton.hidden =
                                false;


                            button.dataset.oarBusy =
                                "0";


                            button.disabled =
                                card.dataset.state !==
                                "running";
                        }
                    }
                );


                window.setTimeout(
                    function(){

                        saveButton.focus();
                    },
                    0
                );
            }
        );

    /* END AUSTRALIA LIVE OPS SAVE OAR V3.1 */
}



/* ============================================================
   UPDATE ONE CARD
   ============================================================ */

function updateCard(
    row,
    card
){

    const name =
        displayName(
            row
        );


    const key =
        norm(
            name
        );


    const telem =
        telemetry.get(
            key
        ) ||
        null;


    const perf =
        processes.get(
            key
        ) ||
        null;


    let state =
        norm(
            row.dataset.state
        );


    /*
     * The live process endpoint wins for running state.
     */

    if (
        perf &&
        perf.Running === true
    ) {

        state =
            "running";
    }


    card.dataset.state =
        state;


    card.classList.remove(
        "running",
        "stopped",
        "disabled",
        "alert"
    );


    if (
        state ===
        "running"
    ) {

        card.classList.add(
            "running"
        );
    }
    else if (
        state ===
        "disabled"
    ) {

        card.classList.add(
            "disabled"
        );
    }
    else {

        card.classList.add(
            "stopped"
        );
    }


    const sim =
        numberValue(
            telem,
            [
                "SimFPS"
            ]
        );


    const physics =
        numberValue(
            telem,
            [
                "PhysicsFPS",
                "PhyFPS",
                "PhysFPS"
            ]
        );


    const rootAgents =
        numberValue(
            telem,
            [
                "RootAg",
                "RootAgents"
            ]
        );


    const childAgents =
        numberValue(
            telem,
            [
                "ChldAg",
                "ChildAgents"
            ]
        );


    const scripts =
        numberValue(
            telem,
            [
                "AtvScr",
                "ActiveScripts"
            ]
        );


    const eps =
        numberValue(
            telem,
            [
                "ScrEPS",
                "ScriptEPS",
                "ScriptEvents"
            ]
        );


    const prims =
        numberValue(
            telem,
            [
                "Prims",
                "PrimCount"
            ]
        );


    const frame =
        numberValue(
            telem,
            [
                "TotlFt",
                "FrameMS",
                "FrameTime"
            ]
        );


    const cpu =
        numberValue(
            perf,
            [
                "CpuHostPercent"
            ]
        );


    const ram =
        numberValue(
            perf,
            [
                "WorkingSetGb"
            ]
        );


    const threads =
        numberValue(
            perf,
            [
                "ThreadCount"
            ]
        );


    const pid =
        numberValue(
            perf,
            [
                "ProcessId"
            ]
        );


    const uptime =
        stringValue(
            perf,
            [
                "UptimeText"
            ]
        );


    const avatarCount =
        (
            rootAgents ??
            0
        ) +
        (
            childAgents ??
            0
        );


    const stateNode =
        card.querySelector(
            ".rm-state"
        );


    if (stateNode) {

        stateNode.textContent =
            state === "running"
                ?
                "RUNNING"
                :
                (
                    state === "disabled"
                        ?
                        "DISABLED"
                        :
                        "STOPPED"
                );
    }


    setText(
        card,
        ".rm-sim",

        sim === null
            ?
            "—"
            :
            sim.toFixed(1)
    );


    setText(
        card,
        ".rm-physics",

        physics === null
            ?
            "—"
            :
            physics.toFixed(1)
    );


    setText(
        card,
        ".rm-avatars",

        String(
            Math.round(
                avatarCount
            )
        )
    );


    setText(
        card,
        ".rm-frame",

        frame === null
            ?
            "—"
            :
            frame.toFixed(2)
    );


    setText(
        card,
        ".rm-scripts",

        scripts === null
            ?
            "—"
            :
            Math.round(
                scripts
            ).toLocaleString()
    );


    setText(
        card,
        ".rm-eps",

        eps === null
            ?
            "—"
            :
            Math.round(
                eps
            ).toLocaleString()
    );


    setText(
        card,
        ".rm-prims",

        prims === null
            ?
            "—"
            :
            Math.round(
                prims
            ).toLocaleString()
    );


    setText(
        card,
        ".rm-threads",

        threads === null
            ?
            "—"
            :
            Math.round(
                threads
            ).toLocaleString()
    );


    setText(
        card,
        ".rm-ram",

        ram === null
            ?
            "—"
            :
            ram.toFixed(2) +
            " GB"
    );


    setText(
        card,
        ".rm-pid",

        pid === null
            ?
            "—"
            :
            String(
                Math.round(
                    pid
                )
            )
    );


    setText(
        card,
        ".rm-uptime",

        uptime ||
        "—"
    );


    const oarName =
        row.dataset.oarName ||
        "";


    setText(
        card,
        ".rm-oar",

        oarName
            ?
            "READY"
            :
            "NONE"
    );


    /*
     * PERFORMANCE BARS
     */

    const simPercent =
        sim === null
            ?
            0
            :
            clamp(
                sim /
                55 *
                100,
                0,
                100
            );


    const physicsPercent =
        physics === null
            ?
            0
            :
            clamp(
                physics /
                55 *
                100,
                0,
                100
            );


    const cpuPercent =
        cpu === null
            ?
            0
            :
            clamp(
                cpu,
                0,
                100
            );


    const simRow =
        card.querySelector(
            ".rm-bar-row.sim"
        );


    const physicsRow =
        card.querySelector(
            ".rm-bar-row.physics"
        );


    const cpuRow =
        card.querySelector(
            ".rm-bar-row.cpu"
        );


    if (simRow) {

        simRow
            .querySelector(
                ".rm-bar-fill"
            )
            .style.width =
                simPercent.toFixed(1) +
                "%";


        simRow
            .querySelector(
                ".rm-bar-value"
            )
            .textContent =
                sim === null
                    ?
                    "—"
                    :
                    sim.toFixed(1);
    }


    if (physicsRow) {

        physicsRow
            .querySelector(
                ".rm-bar-fill"
            )
            .style.width =
                physicsPercent.toFixed(1) +
                "%";


        physicsRow
            .querySelector(
                ".rm-bar-value"
            )
            .textContent =
                physics === null
                    ?
                    "—"
                    :
                    physics.toFixed(1);
    }


    if (cpuRow) {

        cpuRow
            .querySelector(
                ".rm-bar-fill"
            )
            .style.width =
                cpuPercent.toFixed(1) +
                "%";


        cpuRow
            .querySelector(
                ".rm-bar-value"
            )
            .textContent =
                cpu === null
                    ?
                    "—"
                    :
                    cpu.toFixed(1) +
                    "%";
    }


    /*
     * WARNING
     */

    if (
        state === "running" &&
        (
            (
                sim !== null &&
                sim < 40
            ) ||
            (
                physics !== null &&
                physics < 40
            ) ||
            (
                cpu !== null &&
                cpu >= 90
            )
        )
    ) {

        card.classList.add(
            "alert"
        );
    }


    /*
     * BUTTON STATE
     */

    const start =
        card.querySelector(
            ".start"
        );


    const stop =
        card.querySelector(
            ".stop"
        );


    const restart =
        card.querySelector(
            ".restart"
        );


    const stats =
        card.querySelector(
            ".stats"
        );


    const oar =
        card.querySelector(
            ".oar"
        );


    start.disabled =
        state !== "stopped";


    stop.disabled =
        state !== "running";


    restart.disabled =
        state !== "running";


    stats.disabled =
        state !== "running";


    oar.disabled =
        state !== "running" ||
        oar.dataset.oarBusy === "1";


    setText(
        card,
        ".rm-card-live",

        telem
            ?
            "TELEMETRY LIVE"
            :
            (
                state === "running"
                    ?
                    "WAITING TELEMETRY"
                    :
                    "OFFLINE"
            )
    );


    setText(
        card,
        ".rm-last-update",

        new Date()
            .toLocaleTimeString()
    );
}



/* ============================================================
   UPDATE ALL CARDS / SUMMARY
   ============================================================ */

function updateAllCards(){

    let running =
        0;


    let stopped =
        0;


    let avatars =
        0;


    let simTotal =
        0;


    let simCount =
        0;


    let cpuTotal =
        0;


    for (const row of rows) {

        const name =
            displayName(
                row
            );


        const key =
            norm(
                name
            );


        const card =
            cardMap.get(
                key
            );


        if (!card) {
            continue;
        }


        updateCard(
            row,
            card
        );


        if (
            card.dataset.state ===
            "running"
        ) {

            running++;
        }
        else {

            stopped++;
        }


        const telem =
            telemetry.get(
                key
            );


        const perf =
            processes.get(
                key
            );


        const rootAgents =
            numberValue(
                telem,
                [
                    "RootAg",
                    "RootAgents"
                ]
            );


        avatars +=
            rootAgents ??
            0;


        const sim =
            numberValue(
                telem,
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


        const cpu =
            numberValue(
                perf,
                [
                    "CpuHostPercent"
                ]
            );


        if (cpu !== null) {

            cpuTotal +=
                cpu;
        }
    }


    const summaryTotal =
        document.getElementById(
            "rm-summary-total"
        );


    const summaryRunning =
        document.getElementById(
            "rm-summary-running"
        );


    const summaryStopped =
        document.getElementById(
            "rm-summary-stopped"
        );


    const summaryAvatars =
        document.getElementById(
            "rm-summary-avatars"
        );


    const summarySim =
        document.getElementById(
            "rm-summary-sim"
        );


    const summaryCpu =
        document.getElementById(
            "rm-summary-cpu"
        );


    if (summaryTotal) {

        summaryTotal.textContent =
            String(
                rows.length
            );
    }


    if (summaryRunning) {

        summaryRunning.textContent =
            String(
                running
            );
    }


    if (summaryStopped) {

        summaryStopped.textContent =
            String(
                stopped
            );
    }


    if (summaryAvatars) {

        summaryAvatars.textContent =
            String(
                Math.round(
                    avatars
                )
            );
    }


    if (summarySim) {

        summarySim.textContent =
            simCount
                ?
                (
                    simTotal /
                    simCount
                ).toFixed(1)
                :
                "—";
    }


    if (summaryCpu) {

        summaryCpu.textContent =
            cpuTotal.toFixed(1) +
            "%";
    }


    applyFilter();
}



/* ============================================================
   SEARCH / FILTER
   ============================================================ */

function wireToolbar(){

    const search =
        document.getElementById(
            "rm-search"
        );


    if (search) {

        search.addEventListener(
            "input",
            function(){

                searchText =
                    norm(
                        search.value
                    );


                applyFilter();
            }
        );
    }


    root
        .querySelectorAll(
            ".rm-filter"
        )
        .forEach(
            function(button){

                button.addEventListener(
                    "click",
                    function(){

                        filter =
                            button.dataset.filter ||
                            "all";


                        root
                            .querySelectorAll(
                                ".rm-filter"
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


                        applyFilter();
                    }
                );
            }
        );
}


function applyFilter(){

    if (!grid) {
        return;
    }


    let visible =
        0;


    cardMap.forEach(
        function(card,key){

            let show =
                true;


            if (
                searchText &&
                !key.includes(
                    searchText
                )
            ) {

                show =
                    false;
            }


            if (
                show &&
                filter === "running"
            ) {

                show =
                    card.dataset.state ===
                    "running";
            }


            if (
                show &&
                filter === "stopped"
            ) {

                show =
                    card.dataset.state !==
                    "running";
            }


            if (
                show &&
                filter === "avatars"
            ) {

                const value =
                    Number(
                        card
                            .querySelector(
                                ".rm-avatars"
                            )
                            ?.textContent ||
                        0
                    );


                show =
                    value > 0;
            }


            card.style.display =
                show
                    ?
                    ""
                    :
                    "none";


            if (show) {
                visible++;
            }
        }
    );


    const empty =
        root.querySelector(
            ".rm-empty"
        );


    if (empty) {

        empty.style.display =
            visible
                ?
                "none"
                :
                "block";
    }
}



/* ============================================================
   TELEMETRY POLLING
   ============================================================ */

async function pollTelemetry(){

    if (
        telemetryBusy ||
        document.visibilityState !==
        "visible"
    ) {
        return;
    }


    telemetryBusy =
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


        telemetry.clear();


        for (
            const region of
            telemetryRows(
                data
            )
        ) {

            const name =
                stringValue(
                    region,
                    [
                        "RegionName",
                        "regionName",
                        "Name",
                        "name"
                    ]
                );


            if (name) {

                telemetry.set(
                    norm(
                        name
                    ),
                    region
                );
            }
        }


        updateAllCards();
    }
    catch(error) {

        console.debug(
            "Region telemetry:",
            error
        );
    }
    finally {

        telemetryBusy =
            false;
    }
}



/* ============================================================
   PROCESS PERFORMANCE POLLING
   ============================================================ */

async function pollProcesses(){

    if (
        processBusy ||
        document.visibilityState !==
        "visible"
    ) {
        return;
    }


    processBusy =
        true;


    try {

        const response =
            await fetch(
                "/Other/admin-region-performance.php?_=" +
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


        processes.clear();


        const processRows =
            Array.isArray(
                data.rows
            )
            ?
            data.rows
            :
            (
                Array.isArray(
                    data.regions
                )
                    ?
                    data.regions
                    :
                    []
            );


        for (
            const region of
            processRows
        ) {

            const name =
                stringValue(
                    region,
                    [
                        "RegionName",
                        "regionName",
                        "Name",
                        "name"
                    ]
                );


            if (name) {

                processes.set(
                    norm(
                        name
                    ),
                    region
                );
            }
        }


        updateAllCards();
    }
    catch(error) {

        console.debug(
            "Region performance:",
            error
        );
    }
    finally {

        processBusy =
            false;
    }
}



/* ============================================================
   CLOCK
   ============================================================ */

function updateClock(){

    const clock =
        document.getElementById(
            "rm-live-clock"
        );


    if (clock) {

        clock.textContent =
            new Date()
                .toLocaleTimeString();
    }
}



/* ============================================================
   START
   ============================================================ */

function start(){

    let attempts =
        0;


    const boot =
        window.setInterval(
            function(){

                attempts++;


                if (
                    install()
                ) {

                    window.clearInterval(
                        boot
                    );


                    pollTelemetry();

                    pollProcesses();


                    window.setInterval(
                        pollTelemetry,
                        1000
                    );


                    window.setInterval(
                        pollProcesses,
                        1500
                    );


                    window.setInterval(
                        updateClock,
                        1000
                    );
                }


                if (
                    attempts >= 30
                ) {

                    window.clearInterval(
                        boot
                    );
                }

            },
            300
        );


    window.AustraliaRegionLiveOps = {

        refresh:
            function(){

                pollTelemetry();

                pollProcesses();
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