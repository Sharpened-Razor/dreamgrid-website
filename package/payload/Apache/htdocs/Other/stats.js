(function(){

"use strict";


const el =
    id =>
        document.getElementById(id);


function esc(value){

    return String(
        value === undefined ||
        value === null
            ? ""
            : value
    )
    .replace(/&/g,"&amp;")
    .replace(/</g,"&lt;")
    .replace(/>/g,"&gt;")
    .replace(/"/g,"&quot;")
    .replace(/'/g,"&#039;");
}


function num(value){

    const n =
        Number(value);

    return Number.isFinite(n)
        ? n
        : null;
}


function firstNumber(
    object,
    names
){

    if(!object){
        return null;
    }

    for(const name of names){

        if(
            Object.prototype.hasOwnProperty.call(
                object,
                name
            )
        ){

            const value =
                num(
                    object[name]
                );

            if(value !== null){
                return value;
            }
        }
    }

    return null;
}


function firstText(
    object,
    names
){

    if(!object){
        return "";
    }

    for(const name of names){

        const value =
            object[name];

        if(
            value !== undefined &&
            value !== null &&
            String(value).trim() !== ""
        ){

            return String(value).trim();
        }
    }

    return "";
}


function bool(value){

    if(
        value === true ||
        value === 1
    ){
        return true;
    }

    return [
        "true",
        "1",
        "yes",
        "enabled"
    ].includes(
        String(value ?? "")
        .trim()
        .toLowerCase()
    );
}


function rowsFrom(data){

    if(Array.isArray(data)){
        return data;
    }

    if(
        !data ||
        typeof data !==
        "object"
    ){
        return [];
    }

    for(const name of [
        "regions",
        "rows",
        "files",
        "backups",
        "items",
        "records",
        "data"
    ]){

        if(Array.isArray(data[name])){
            return data[name];
        }
    }

    return [];
}


function regionKey(value){

    return String(
        value ?? ""
    )
    .trim()
    .toLowerCase()
    .replace(
        /[^a-z0-9]/g,
        ""
    );
}


function formatBytes(value){

    let bytes =
        Number(value);

    if(
        !Number.isFinite(bytes) ||
        bytes < 0
    ){
        return "—";
    }

    const units =
        [
            "B",
            "KB",
            "MB",
            "GB",
            "TB",
            "PB"
        ];

    let index =
        0;

    while(
        bytes >= 1024 &&
        index <
        units.length - 1
    ){

        bytes /=
            1024;

        index++;
    }

    return (
        (
            index === 0
                ? bytes.toFixed(0)
                : bytes.toFixed(2)
        ) +
        " " +
        units[index]
    );
}


function formatDuration(seconds){

    const total =
        Math.max(
            0,
            Math.floor(
                Number(seconds) || 0
            )
        );

    const days =
        Math.floor(
            total / 86400
        );

    const hours =
        Math.floor(
            (total % 86400) /
            3600
        );

    const minutes =
        Math.floor(
            (total % 3600) /
            60
        );

    if(days > 0){

        return (
            days +
            "d " +
            hours +
            "h " +
            minutes +
            "m"
        );
    }

    return (
        hours +
        "h " +
        minutes +
        "m"
    );
}


function dateValue(value){

    if(
        value === undefined ||
        value === null ||
        value === ""
    ){
        return null;
    }

    if(
        typeof value ===
        "number"
    ){

        return new Date(
            value < 20000000000
                ? value * 1000
                : value
        );
    }

    const numeric =
        Number(value);

    if(
        Number.isFinite(numeric) &&
        String(value).trim() !== ""
    ){

        return new Date(
            numeric < 20000000000
                ? numeric * 1000
                : numeric
        );
    }

    const date =
        new Date(value);

    return Number.isNaN(
        date.getTime()
    )
        ? null
        : date;
}


function dateText(value){

    const date =
        dateValue(value);

    return date
        ? date.toLocaleString()
        : "None";
}


function average(values){

    const valid =
        values.filter(
            value =>
                Number.isFinite(value)
        );

    if(valid.length === 0){
        return null;
    }

    return (
        valid.reduce(
            (sum,value) =>
                sum + value,
            0
        ) /
        valid.length
    );
}


function statusHtml(
    value,
    tone
){

    return (
        '<span class="status ' +
        tone +
        '">' +
            '<span class="status-dot"></span>' +
            '<span>' +
                esc(value) +
            '</span>' +
        '</span>'
    );
}


function statRow(
    label,
    value,
    tone = ""
){

    return (
        '<div class="stat-row">' +
            '<div class="stat-label">' +
                esc(label) +
            '</div>' +
            '<div class="stat-value">' +
                (
                    tone
                        ? statusHtml(
                            value,
                            tone
                        )
                        : esc(value)
                ) +
            '</div>' +
        '</div>'
    );
}


function summary(
    id,
    value,
    detail,
    tone
){

    const node =
        el(id);

    if(node){

        node.textContent =
            value;

        node.classList.remove(
            "good",
            "ok",
            "warn",
            "bad"
        );

        if(tone){
            node.classList.add(tone);
        }
    }


    const detailNode =
        el(
            id +
            "-detail"
        );


    if(
        detailNode &&
        detail !== undefined
    ){
        detailNode.textContent =
            detail;
    }
}


async function getJson(path){

    const response =
        await fetch(
            new URL(
                path,
                window.location.href
            ),
            {
                credentials:
                    "same-origin",

                cache:
                    "no-store"
            }
        );


    if(!response.ok){

        throw new Error(
            "HTTP " +
            response.status +
            " " +
            path
        );
    }


    return response.json();
}


function isOnline(region){

    const state =
        firstText(
            region,
            [
                "Status",
                "RegionStatus",
                "State",
                "status"
            ]
        )
        .toLowerCase();


    return (
        state.includes("booted") ||
        state.includes("running") ||
        state.includes("online") ||
        state.includes("started") ||
        state.includes("backingup")
    );
}


function buildRegions(
    baseRows,
    telemetryRows
){

    const telemetry =
        new Map();


    telemetryRows.forEach(
        item => {

            const name =
                firstText(
                    item,
                    [
                        "RegionName",
                        "regionName",
                        "Name",
                        "name"
                    ]
                );

            if(name){

                telemetry.set(
                    regionKey(name),
                    item
                );
            }
        }
    );


    return baseRows.map(
        region => {

            const name =
                firstText(
                    region,
                    [
                        "RegionName",
                        "regionName",
                        "Name",
                        "name"
                    ]
                );


            const perf =
                telemetry.get(
                    regionKey(name)
                ) ||
                {};


            return {
                name,

                status:
                    firstText(
                        region,
                        [
                            "Status",
                            "RegionStatus",
                            "State",
                            "status"
                        ]
                    ) ||
                    (
                        perf.Available === false
                            ? "Offline"
                            : "Unknown"
                    ),

                online:
                    isOnline(region),

                avatars:
                    firstNumber(
                        perf,
                        [
                            "RootAgents",
                            "RootAg"
                        ]
                    ) ??
                    firstNumber(
                        region,
                        [
                            "AvatarCount",
                            "Avatars",
                            "AgentCount"
                        ]
                    ) ??
                    0,

                prims:
                    firstNumber(
                        perf,
                        [
                            "Prims",
                            "PrimCount"
                        ]
                    ) ??
                    firstNumber(
                        region,
                        [
                            "PrimCount",
                            "Prims"
                        ]
                    ) ??
                    0,

                simFPS:
                    firstNumber(
                        perf,
                        [
                            "SimFPS",
                            "Sim FPS"
                        ]
                    ),

                physicsFPS:
                    firstNumber(
                        perf,
                        [
                            "PhysicsFPS",
                            "PhyFPS",
                            "PhysFPS"
                        ]
                    ),

                frameMS:
                    firstNumber(
                        perf,
                        [
                            "FrameMS",
                            "FrameTime",
                            "TotlFt"
                        ]
                    ),

                scripts:
                    firstNumber(
                        perf,
                        [
                            "ActiveScripts",
                            "AtvScr",
                            "Scripts"
                        ]
                    ) ??
                    0,

                scriptEPS:
                    firstNumber(
                        perf,
                        [
                            "ScriptEvents",
                            "ScriptEPS",
                            "ScrEPS"
                        ]
                    ) ??
                    0
            };
        }
    );
}


function renderServer(api){

    const system =
        api.system ||
        {};

    const memoryTotal =
        num(
            system.memoryTotal
        );

    const memoryFree =
        num(
            system.memoryFree
        );

    const memoryUsed =
        (
            memoryTotal !== null &&
            memoryFree !== null
        )
            ? Math.max(
                0,
                memoryTotal -
                memoryFree
            )
            : null;


    const memoryPct =
        (
            memoryTotal &&
            memoryUsed !== null
        )
            ? (
                memoryUsed /
                memoryTotal *
                100
            )
            : null;


    const boot =
        dateValue(
            system.boot
        );


    const uptime =
        boot
            ? (
                (
                    Date.now() -
                    boot.getTime()
                ) /
                1000
            )
            : null;


    const cpu =
        num(
            system.cpu
        );


    el("server-load").innerHTML =
        statRow(
            "CPU usage",
            cpu === null
                ? "Unavailable"
                : cpu.toFixed(1) + "%",
            cpu === null
                ? "warn"
                : (
                    cpu < 80
                        ? "good"
                        : (
                            cpu < 92
                                ? "warn"
                                : "bad"
                        )
                )
        ) +
        statRow(
            "RAM used",
            memoryUsed === null
                ? "Unavailable"
                : (
                    formatBytes(memoryUsed) +
                    (
                        memoryPct !== null
                            ? " · " +
                              memoryPct.toFixed(1) +
                              "%"
                            : ""
                    )
                ),
            memoryPct === null
                ? "warn"
                : (
                    memoryPct < 80
                        ? "good"
                        : (
                            memoryPct < 92
                                ? "warn"
                                : "bad"
                        )
                )
        ) +
        statRow(
            "RAM free",
            formatBytes(
                memoryFree
            )
        ) +
        statRow(
            "System uptime",
            uptime === null
                ? "Unavailable"
                : formatDuration(uptime)
        ) +
        statRow(
            "OpenSim processes",
            String(
                api.processes?.opensim ??
                "—"
            )
        ) +
        statRow(
            "OpenSim threads",
            String(
                system.openSimThreads ??
                "—"
            )
        );


    summary(
        "summary-cpu",
        cpu === null
            ? "—"
            : cpu.toFixed(0) + "%",
        undefined,
        cpu === null
            ? "warn"
            : (
                cpu < 80
                    ? "good"
                    : "warn"
            )
    );


    return {
        cpu,
        memoryPct
    };
}


function renderPerformance(
    regions
){

    const sim =
        regions
        .map(r => r.simFPS)
        .filter(
            value =>
                Number.isFinite(value)
        );


    const physics =
        regions
        .map(r => r.physicsFPS)
        .filter(
            value =>
                Number.isFinite(value)
        );


    const frames =
        regions
        .map(r => r.frameMS)
        .filter(
            value =>
                Number.isFinite(value)
        );


    const avgSim =
        average(sim);

    const avgPhysics =
        average(physics);

    const avgFrame =
        average(frames);


    const scripts =
        regions.reduce(
            (sum,r) =>
                sum +
                (
                    Number(r.scripts) ||
                    0
                ),
            0
        );


    const eps =
        regions.reduce(
            (sum,r) =>
                sum +
                (
                    Number(r.scriptEPS) ||
                    0
                ),
            0
        );


    el("opensim-performance").innerHTML =
        statRow(
            "Average Sim FPS",
            avgSim === null
                ? "—"
                : avgSim.toFixed(1),
            avgSim === null
                ? ""
                : (
                    avgSim >= 45
                        ? "good"
                        : (
                            avgSim >= 30
                                ? "warn"
                                : "bad"
                        )
                )
        ) +
        statRow(
            "Average Physics FPS",
            avgPhysics === null
                ? "—"
                : avgPhysics.toFixed(1),
            avgPhysics === null
                ? ""
                : (
                    avgPhysics >= 40
                        ? "good"
                        : "warn"
                )
        ) +
        statRow(
            "Average frame time",
            avgFrame === null
                ? "—"
                : avgFrame.toFixed(2) + " ms"
        ) +
        statRow(
            "Active scripts",
            scripts.toLocaleString()
        ) +
        statRow(
            "Script events / sec",
            eps.toLocaleString()
        ) +
        statRow(
            "Telemetry regions",
            String(sim.length)
        );


    summary(
        "summary-fps",
        avgSim === null
            ? "—"
            : avgSim.toFixed(1),
        undefined,
        avgSim === null
            ? "warn"
            : (
                avgSim >= 45
                    ? "good"
                    : "warn"
            )
    );


    return {
        avgSim,
        avgPhysics,
        avgFrame
    };
}


function renderRegionHealth(
    regions
){

    const online =
        regions.filter(
            r => r.online
        );


    const lowestFPS =
        online
        .filter(
            r =>
                Number.isFinite(
                    r.simFPS
                )
        )
        .sort(
            (a,b) =>
                a.simFPS -
                b.simFPS
        )[0];


    const highestFrame =
        online
        .filter(
            r =>
                Number.isFinite(
                    r.frameMS
                )
        )
        .sort(
            (a,b) =>
                b.frameMS -
                a.frameMS
        )[0];


    const busiest =
        [...regions]
        .sort(
            (a,b) =>
                b.avatars -
                a.avatars
        )[0];


    const mostPrims =
        [...regions]
        .sort(
            (a,b) =>
                b.prims -
                a.prims
        )[0];


    const offlineCount =
        Math.max(
            0,
            regions.length -
            online.length
        );


    el("region-health").innerHTML =
        statRow(
            "Regions online",
            online.length +
            " / " +
            regions.length,
            offlineCount === 0
                ? "good"
                : "warn"
        ) +
        statRow(
            "Regions offline",
            String(offlineCount),
            offlineCount === 0
                ? "good"
                : "bad"
        ) +
        statRow(
            "Lowest Sim FPS",
            lowestFPS
                ? (
                    lowestFPS.name +
                    " · " +
                    lowestFPS.simFPS.toFixed(1)
                )
                : "—"
        ) +
        statRow(
            "Highest frame time",
            highestFrame
                ? (
                    highestFrame.name +
                    " · " +
                    highestFrame.frameMS.toFixed(2) +
                    " ms"
                )
                : "—"
        ) +
        statRow(
            "Most avatars",
            busiest
                ? (
                    busiest.name +
                    " · " +
                    busiest.avatars
                )
                : "—"
        ) +
        statRow(
            "Most prims",
            mostPrims
                ? (
                    mostPrims.name +
                    " · " +
                    mostPrims.prims.toLocaleString()
                )
                : "—"
        );


    summary(
        "summary-regions",
        online.length +
        " / " +
        regions.length,
        offlineCount === 0
            ? "All regions online"
            : offlineCount +
              " region(s) offline",
        offlineCount === 0
            ? "good"
            : "warn"
    );


    summary(
        "summary-grid",
        offlineCount === 0 &&
        regions.length > 0
            ? "ONLINE"
            : "WARNING",
        regions.length +
        " regions detected",
        offlineCount === 0 &&
        regions.length > 0
            ? "good"
            : "warn"
    );


    return {
        onlineCount:
            online.length,

        offlineCount
    };
}


function renderNetwork(
    api
){

    const p =
        api.processes ||
        {};


    const n =
        api.network ||
        {};


    el("network-services").innerHTML =
        statRow(
            "Apache",
            p.apache === null
                ? "Unavailable"
                : (
                    p.apache > 0
                        ? "RUNNING · " +
                          p.apache
                        : "STOPPED"
                ),
            p.apache > 0
                ? "good"
                : "bad"
        ) +
        statRow(
            "MySQL",
            p.mysql === null
                ? "Unavailable"
                : (
                    p.mysql > 0
                        ? "RUNNING · " +
                          p.mysql
                        : "STOPPED"
                ),
            p.mysql > 0
                ? "good"
                : "bad"
        ) +
        statRow(
            "Robust",
            p.robust === null
                ? "Unavailable"
                : (
                    p.robust > 0
                        ? "RUNNING · " +
                          p.robust
                        : "STOPPED"
                ),
            p.robust > 0
                ? "good"
                : "bad"
        ) +
        statRow(
            "DNS",
            n.dnsOk
                ? (
                    n.resolvedIp ||
                    "RESOLVED"
                )
                : "FAILED",
            n.dnsOk
                ? "good"
                : "bad"
        ) +
        statRow(
            "Grid login service",
            n.login?.detail ||
            "NO RESPONSE",
            n.login?.ok
                ? "good"
                : "bad"
        ) +
        statRow(
            "Diagnostics port " +
            (
                n.diagnosticsPort ??
                "Diagnostics"
            ),
            n.diagnostics?.detail ||
            "NO RESPONSE",
            n.diagnostics?.ok
                ? "good"
                : "bad"
        );
}


function backupRecords(data){

    return rowsFrom(data)
        .map(
            item =>
                typeof item ===
                "string"
                    ? {
                        name:item
                    }
                    : (
                        item ||
                        {}
                    )
        );
}


function backupName(item){

    return firstText(
        item,
        [
            "name",
            "Name",
            "file",
            "File",
            "filename",
            "path",
            "FullName"
        ]
    );
}


function backupTime(item){

    for(const key of [
        "timestamp",
        "mtime",
        "modified",
        "Modified",
        "date",
        "Date",
        "time",
        "lastWriteTime",
        "LastWriteTime"
    ]){

        if(
            item[key] !== undefined &&
            item[key] !== null
        ){

            const date =
                dateValue(
                    item[key]
                );

            if(date){
                return date;
            }
        }
    }

    return null;
}


function backupSize(item){

    return firstNumber(
        item,
        [
            "size",
            "Size",
            "bytes",
            "Length",
            "length"
        ]
    ) ?? 0;
}


function backupSummary(records){

    const result = {
        oarCount:0,
        iarCount:0,
        otherCount:0,
        oarBytes:0,
        iarBytes:0,
        otherBytes:0,
        latestOar:null,
        latestIar:null
    };


    for(const item of records){

        const name =
            backupName(item);

        const lower =
            name.toLowerCase();

        const size =
            backupSize(item);

        const date =
            backupTime(item);


        if(lower.endsWith(".oar")){

            result.oarCount++;
            result.oarBytes += size;

            if(
                !result.latestOar ||
                (
                    date &&
                    (
                        !result.latestOar.date ||
                        date >
                        result.latestOar.date
                    )
                )
            ){

                result.latestOar = {
                    name,
                    date,
                    size
                };
            }
        }
        else if(lower.endsWith(".iar")){

            result.iarCount++;
            result.iarBytes += size;

            if(
                !result.latestIar ||
                (
                    date &&
                    (
                        !result.latestIar.date ||
                        date >
                        result.latestIar.date
                    )
                )
            ){

                result.latestIar = {
                    name,
                    date,
                    size
                };
            }
        }
        else{

            result.otherCount++;
            result.otherBytes += size;
        }
    }


    return result;
}


function scheduleNext(config){

    if(
        !config ||
        !bool(config.enabled)
    ){
        return null;
    }


    const match =
        String(
            config.time ??
            ""
        )
        .match(
            /^(\d{1,2}):(\d{2})$/
        );


    if(!match){
        return null;
    }


    const days =
        Array.isArray(
            config.days
        )
            ? config.days.map(Number)
            : [];


    if(days.length === 0){
        return null;
    }


    const now =
        new Date();


    for(
        let offset = 0;
        offset <= 7;
        offset++
    ){

        const candidate =
            new Date(now);


        candidate.setDate(
            now.getDate() +
            offset
        );


        candidate.setHours(
            Number(match[1]),
            Number(match[2]),
            0,
            0
        );


        if(
            days.includes(
                candidate.getDay()
            ) &&
            candidate > now
        ){

            return candidate;
        }
    }


    return null;
}


function renderBackups(
    schedule,
    health,
    archive
){

    const config =
        schedule?.config &&
        typeof schedule.config ===
        "object"
            ? schedule.config
            : {};


    const runner =
        schedule?.runner &&
        typeof schedule.runner ===
        "object"
            ? schedule.runner
            : {};


    const state =
        runner.state &&
        typeof runner.state ===
        "object"
            ? runner.state
            : {};


    const enabled =
        bool(
            config.enabled
        );


    const next =
        schedule?.nextRun ||
        runner.nextRun ||
        state.nextRun ||
        scheduleNext(config);


    const last =
        state.lastBackupFinished ||
        state.lastBackupRun ||
        state.lastRun ||
        null;


    const healthValue =
        String(
            health?.overall ??
            health?.status ??
            health?.health ??
            "UNKNOWN"
        );


    const healthText =
        healthValue ===
        "[object Object]"
            ? "UNKNOWN"
            : healthValue;


    el("backup-activity").innerHTML =
        statRow(
            "Automatic backups",
            enabled
                ? "ENABLED"
                : "DISABLED",
            enabled
                ? "ok"
                : "warn"
        ) +
        statRow(
            "Scheduler runner",
            runner.installed === false
                ? "NOT INSTALLED"
                : "INSTALLED",
            runner.installed === false
                ? "bad"
                : "good"
        ) +
        statRow(
            "Selected regions",
            String(
                Array.isArray(config.regions)
                    ? config.regions.length
                    : 0
            )
        ) +
        statRow(
            "Next scheduled",
            next
                ? dateText(next)
                : "Not scheduled",
            next
                ? "good"
                : "warn"
        ) +
        statRow(
            "Last scheduled",
            last
                ? dateText(last)
                : "None"
        ) +
        statRow(
            "Backup health",
            healthText.toUpperCase(),
            /healthy|good|ready|ok/i.test(
                healthText
            )
                ? "good"
                : "warn"
        );


    summary(
        "summary-backups",
        enabled
            ? "ENABLED"
            : "DISABLED",
        next
            ? "Next " +
              dateText(next)
            : "No backup scheduled",
        enabled
            ? "ok"
            : "warn"
    );


    return {
        enabled,
        next,
        last,
        healthText,
        archive
    };
}


function renderStorage(
    api,
    archive
){

    const disk =
        api.disk ||
        {};


    const total =
        num(
            disk.total
        );


    const used =
        num(
            disk.used
        );


    const free =
        num(
            disk.free
        );


    const freePct =
        num(
            disk.percentFree
        );


    const archiveBytes =
        archive.oarBytes +
        archive.iarBytes +
        archive.otherBytes;


    function bar(
        label,
        bytes,
        base
    ){

        const pct =
            (
                base &&
                base > 0
            )
                ? Math.min(
                    100,
                    bytes / base * 100
                )
                : 0;


        return (
            '<div class="storage-item">' +
                '<div class="storage-head">' +
                    '<span>' +
                        esc(label) +
                    '</span>' +
                    '<strong>' +
                        esc(
                            formatBytes(bytes)
                        ) +
                    '</strong>' +
                '</div>' +
                '<div class="storage-bar">' +
                    '<div class="storage-fill" style="width:' +
                        pct.toFixed(2) +
                        '%"></div>' +
                '</div>' +
            '</div>'
        );
    }


    el("storage-breakdown").innerHTML =
        bar(
            "OAR archives",
            archive.oarBytes,
            total
        ) +
        bar(
            "IAR archives",
            archive.iarBytes,
            total
        ) +
        bar(
            "All reported archives",
            archiveBytes,
            total
        ) +
        statRow(
            "Drive used",
            formatBytes(used)
        ) +
        statRow(
            "Drive free",
            formatBytes(free),
            freePct !== null &&
            freePct >= 15
                ? "good"
                : "warn"
        ) +
        statRow(
            "Free space",
            freePct === null
                ? "—"
                : freePct.toFixed(1) + "%"
        );


    summary(
        "summary-storage",
        formatBytes(free),
        freePct === null
            ? "Available storage"
            : freePct.toFixed(1) +
              "% free",
        freePct !== null &&
        freePct >= 15
            ? "good"
            : "warn"
    );


    return {
        freePct
    };
}


function renderRegionTable(
    regions
){

    const body =
        el(
            "region-table-body"
        );


    el("region-table-count").textContent =
        regions.length +
        " REGION" +
        (
            regions.length === 1
                ? ""
                : "S"
        );


    if(regions.length === 0){

        body.innerHTML =
            '<tr><td colspan="9" class="table-loading">' +
            'No region data returned.' +
            '</td></tr>';

        return;
    }


    body.innerHTML =
        [...regions]
        .sort(
            (a,b) =>
                a.name.localeCompare(
                    b.name
                )
        )
        .map(
            region => {

                const statusTone =
                    region.online
                        ? "good"
                        : "bad";


                const fpsTone =
                    region.simFPS === null
                        ? ""
                        : (
                            region.simFPS >= 45
                                ? "good"
                                : (
                                    region.simFPS >= 30
                                        ? "warn"
                                        : "bad"
                                )
                        );


                return (
                    '<tr>' +
                        '<td class="region-name">' +
                            esc(region.name) +
                        '</td>' +
                        '<td>' +
                            statusHtml(
                                region.status,
                                statusTone
                            ) +
                        '</td>' +
                        '<td class="number">' +
                            esc(region.avatars) +
                        '</td>' +
                        '<td class="number">' +
                            esc(
                                region.prims.toLocaleString()
                            ) +
                        '</td>' +
                        '<td class="number ' +
                            fpsTone +
                        '">' +
                            (
                                region.simFPS === null
                                    ? "—"
                                    : esc(
                                        region.simFPS.toFixed(1)
                                    )
                            ) +
                        '</td>' +
                        '<td class="number">' +
                            (
                                region.physicsFPS === null
                                    ? "—"
                                    : esc(
                                        region.physicsFPS.toFixed(1)
                                    )
                            ) +
                        '</td>' +
                        '<td class="number">' +
                            (
                                region.frameMS === null
                                    ? "—"
                                    : esc(
                                        region.frameMS.toFixed(2)
                                    )
                            ) +
                        '</td>' +
                        '<td class="number">' +
                            esc(
                                region.scripts.toLocaleString()
                            ) +
                        '</td>' +
                        '<td class="number">' +
                            esc(
                                region.scriptEPS.toLocaleString()
                            ) +
                        '</td>' +
                    '</tr>'
                );
            }
        )
        .join("");
}


function renderAlerts(
    api,
    regions,
    performance,
    backup,
    storage,
    server
){

    const alerts = [];


    if(
        regions.some(
            region =>
                !region.online
        )
    ){

        alerts.push({
            tone:"bad",
            title:"REGION OFFLINE",
            detail:
                regions
                .filter(
                    region =>
                        !region.online
                )
                .map(
                    region =>
                        region.name
                )
                .join(", ")
        });
    }


    if(!backup.enabled){

        alerts.push({
            tone:"warn",
            title:"AUTOMATIC BACKUPS DISABLED",
            detail:
                "The scheduler is not currently enabled."
        });
    }


    if(
        !/healthy|good|ready|ok/i.test(
            backup.healthText
        )
    ){

        alerts.push({
            tone:"warn",
            title:"BACKUP HEALTH",
            detail:
                backup.healthText
        });
    }


    if(
        storage.freePct !== null &&
        storage.freePct < 15
    ){

        alerts.push({
            tone:"warn",
            title:"LOW DISK SPACE",
            detail:
                storage.freePct.toFixed(1) +
                "% free."
        });
    }


    if(!api.network?.login?.ok){

        alerts.push({
            tone:"bad",
            title:"LOGIN PORT",
            detail:
                "Grid login service is not responding."
        });
    }


    if(!api.network?.diagnostics?.ok){

        alerts.push({
            tone:"bad",
            title:"DIAGNOSTICS PORT",
            detail:
                "DreamGrid diagnostics is not responding."
        });
    }


    if(
        performance.avgSim !== null &&
        performance.avgSim < 40
    ){

        alerts.push({
            tone:"warn",
            title:"LOW GRID FPS",
            detail:
                "Average Sim FPS is " +
                performance.avgSim.toFixed(1) +
                "."
        });
    }


    if(
        server.cpu !== null &&
        server.cpu >= 92
    ){

        alerts.push({
            tone:"bad",
            title:"HIGH CPU",
            detail:
                server.cpu.toFixed(1) +
                "%."
        });
    }


    if(
        server.memoryPct !== null &&
        server.memoryPct >= 92
    ){

        alerts.push({
            tone:"bad",
            title:"HIGH MEMORY USE",
            detail:
                server.memoryPct.toFixed(1) +
                "%."
        });
    }


    el("alert-count").textContent =
        String(alerts.length);


    if(alerts.length === 0){

        el("alerts").innerHTML =
            '<div class="empty">' +
                statusHtml(
                    "NO ACTIVE ALERTS",
                    "good"
                ) +
            '</div>';

        return;
    }


    el("alerts").innerHTML =
        '<ul class="message-list">' +
        alerts.map(
            alert =>
                '<li class="message-item">' +
                    '<span class="message-dot ' +
                        alert.tone +
                    '"></span>' +
                    '<div>' +
                        '<div class="message-title">' +
                            esc(alert.title) +
                        '</div>' +
                        '<div class="message-detail">' +
                            esc(alert.detail) +
                        '</div>' +
                    '</div>' +
                '</li>'
        )
        .join("") +
        '</ul>';
}


function renderEvents(
    backup,
    archive
){

    const events = [];


    if(backup.last){

        events.push({
            tone:"good",
            title:"LAST SCHEDULED BACKUP",
            detail:
                dateText(
                    backup.last
                )
        });
    }


    if(archive.latestOar){

        events.push({
            tone:"good",
            title:"LATEST OAR",
            detail:
                (
                    archive.latestOar.name ||
                    "OAR archive"
                ) +
                (
                    archive.latestOar.date
                        ? " · " +
                          dateText(
                              archive.latestOar.date
                          )
                        : ""
                )
        });
    }


    if(archive.latestIar){

        events.push({
            tone:"good",
            title:"LATEST IAR",
            detail:
                (
                    archive.latestIar.name ||
                    "IAR archive"
                ) +
                (
                    archive.latestIar.date
                        ? " · " +
                          dateText(
                              archive.latestIar.date
                          )
                        : ""
                )
        });
    }


    if(backup.next){

        events.push({
            tone:"warn",
            title:"NEXT SCHEDULED BACKUP",
            detail:
                dateText(
                    backup.next
                )
        });
    }


    events.push({
        tone:"good",
        title:"STATS REFRESHED",
        detail:
            new Date()
            .toLocaleString()
    });


    el("recent-events").innerHTML =
        '<ul class="message-list">' +
        events.map(
            event =>
                '<li class="message-item">' +
                    '<span class="message-dot ' +
                        event.tone +
                    '"></span>' +
                    '<div>' +
                        '<div class="message-title">' +
                            esc(event.title) +
                        '</div>' +
                        '<div class="message-detail">' +
                            esc(event.detail) +
                        '</div>' +
                    '</div>' +
                '</li>'
        )
        .join("") +
        '</ul>';
}


function renderErrors(api){

    const rawErrors =
        Array.isArray(
            api.errors
        )
            ? api.errors
            : [];

    const seenErrors =
        new Set();

    const errors =
        rawErrors
        .filter(
            error => {

                const key =
                    String(
                        error?.source ??
                        ""
                    ) +
                    "|" +
                    String(
                        error?.message ??
                        ""
                    );

                if (
                    seenErrors.has(
                        key
                    )
                ) {
                    return false;
                }

                seenErrors.add(
                    key
                );

                return true;
            }
        )
        .slice(
            0,
            8
        );


    if(errors.length === 0){

        el("recent-errors").innerHTML =
            '<div class="empty">' +
                statusHtml(
                    "NO RECENT IMPORTANT ERRORS FOUND",
                    "good"
                ) +
            '</div>';

        return;
    }


    el("recent-errors").innerHTML =
        '<ul class="message-list">' +
        errors.map(
            error =>
                '<li class="message-item">' +
                    '<span class="message-dot warn"></span>' +
                    '<div>' +
                        '<div class="error-source">' +
                            esc(
                                error.source ||
                                "LOG"
                            ) +
                        '</div>' +
                        '<div class="error-line">' +
                            esc(
                                error.message ||
                                ""
                            ) +
                        '</div>' +
                    '</div>' +
                '</li>'
        )
        .join("") +
        '</ul>';
}


let busy =
    false;


async function load(){

    if(busy){
        return;
    }


    busy =
        true;


    const button =
        el(
            "refresh-button"
        );


    if(button){

        button.disabled =
            true;

        button.textContent =
            "REFRESHING...";
    }


    try{

        const stamp =
            Date.now();


        const results =
            await Promise.allSettled(
                [
                    getJson(
                        "stats-api.php?_=" +
                        stamp
                    ),

                    getJson(
                        "admin-regions-data.php?_=" +
                        stamp
                    ),

                    getJson(
                        "admin-region-live-direct.php?_=" +
                        stamp
                    ),

                    getJson(
                        "backup-schedule.php?_=" +
                        stamp
                    ),

                    getJson(
                        "backup-health.php?_=" +
                        stamp
                    ),

                    getJson(
                        "list-backups.php?_=" +
                        stamp
                    )
                ]
            );


        const api =
            results[0].status ===
            "fulfilled"
                ? results[0].value
                : {};


        const base =
            results[1].status ===
            "fulfilled"
                ? rowsFrom(
                    results[1].value
                )
                : [];


        const telemetry =
            results[2].status ===
            "fulfilled"
                ? rowsFrom(
                    results[2].value
                )
                : [];


        const schedule =
            results[3].status ===
            "fulfilled"
                ? results[3].value
                : {};


        const health =
            results[4].status ===
            "fulfilled"
                ? results[4].value
                : {};


        const backups =
            results[5].status ===
            "fulfilled"
                ? results[5].value
                : {};


        const regions =
            buildRegions(
                base,
                telemetry
            );


        /*
         * STATS V3 STALE BACKUP STATUS FIX
         *
         * DreamGrid can leave a region reporting BackingUp after
         * the backup operation has finished. On the Stats page,
         * when automatic backups are disabled, show that stale
         * state as the normal Booted state.
         */

        const statsScheduleConfig =
            schedule?.config &&
            typeof schedule.config ===
            "object"
                ? schedule.config
                : schedule;


        const statsSchedulerEnabled =
            bool(
                statsScheduleConfig?.enabled
            );


        if (!statsSchedulerEnabled) {

            for (const region of regions) {

                const status =
                    String(
                        region.status ??
                        ""
                    )
                    .trim()
                    .toLowerCase();


                if (
                    status === "backingup" ||
                    status === "backing up"
                ) {

                    region.status =
                        "Booted";

                    region.online =
                        true;
                }
            }
        }


        const archives =
            backupSummary(
                backupRecords(
                    backups
                )
            );


        const server =
            renderServer(
                api
            );


        const performance =
            renderPerformance(
                regions
            );


        renderRegionHealth(
            regions
        );


        renderNetwork(
            api
        );


        const backup =
            renderBackups(
                schedule,
                health,
                archives
            );


        const storage =
            renderStorage(
                api,
                archives
            );


        renderRegionTable(
            regions
        );


        renderAlerts(
            api,
            regions,
            performance,
            backup,
            storage,
            server
        );


        renderEvents(
            backup,
            archives
        );


        renderErrors(
            api
        );


        el("last-refresh").textContent =
            new Date()
            .toLocaleString();


        el("live-state").innerHTML =
            '<span class="live-dot"></span>LIVE';
    }
    catch(error){

        console.error(
            "Stats V3:",
            error
        );


        el("live-state").innerHTML =
            '<span class="live-dot"></span>DATA ERROR';

        el("live-state").classList.add(
            "bad"
        );
    }
    finally{

        busy =
            false;


        if(button){

            button.disabled =
                false;

            button.textContent =
                "REFRESH ALL";
        }
    }
}


el("refresh-button")
?.addEventListener(
    "click",
    load
);


load();


window.setInterval(
    function(){

        if(
            document.visibilityState ===
            "visible"
        ){
            load();
        }
    },
    30000
);

})();