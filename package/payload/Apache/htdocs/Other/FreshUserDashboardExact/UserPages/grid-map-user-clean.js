(function(){

"use strict";

const map = document.getElementById("grid-map");
const refreshButton = document.getElementById("gridmap-refresh");
const updated = document.getElementById("gridmap-updated");
const details = document.getElementById("gridmap-details-body");

const CELL = 42;
const PADDING = 42;

let allRegions = [];
let selectedName = null;


function escapeHtml(value){
    return String(value ?? "")
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");
}


function normaliseName(value){
    return String(value || "")
        .trim()
        .toLowerCase();
}


function statusClass(region){

    const status = String(region.Status || "").toLowerCase();

    if(
        status.includes("booted") ||
        status.includes("online") ||
        status.includes("running")
    ){
        return "online";
    }

    if(
        status.includes("starting") ||
        status.includes("stopping") ||
        status.includes("backup") ||
        status.includes("busy") ||
        status.includes("warning")
    ){
        return "warning";
    }

    return "offline";
}


function showDetails(region){

    selectedName = region.RegionName;

    document.querySelectorAll(".map-region").forEach(el => {
        el.classList.toggle(
            "selected",
            el.dataset.region === region.RegionName
        );
    });

    const status = region.Status || "Unknown";

    details.innerHTML = `
        <div class="gridmap-detail-grid">

            <div class="gridmap-detail">
                <span>REGION</span>
                <strong>${escapeHtml(region.RegionName)}</strong>
            </div>

            <div class="gridmap-detail">
                <span>STATUS</span>
                <strong>
                    <span class="map-dot ${statusClass(region)}"></span>
                    ${escapeHtml(status)}
                </strong>
            </div>

            <div class="gridmap-detail">
                <span>GRID LOCATION</span>
                <strong>${region.X}, ${region.Y}</strong>
            </div>

            <div class="gridmap-detail">
                <span>REGION SIZE</span>
                <strong>
                    ${region.SizeX} \u00D7 ${region.SizeY} metres
                    (${region.CellsX}\u00D7${region.CellsY})
                </strong>
            </div>

            <div class="gridmap-detail">
                <span>ESTATE</span>
                <strong>${escapeHtml(region.EstateName || "\u2014")}</strong>
            </div>

            <div class="gridmap-detail">
                <span>ESTATE OWNER</span>
                <strong>${escapeHtml(region.EstateOwner || "\u2014")}</strong>
            </div>

            <div class="gridmap-detail">
                <span>AVATARS</span>
                <strong>${Number(region.AvatarCount || 0).toLocaleString()}</strong>
            </div>

            <div class="gridmap-detail">
                <span>PRIMS</span>
                <strong>${Number(region.PrimCount || 0).toLocaleString()}</strong>
            </div>

            <div class="gridmap-detail">
                <span>RAM</span>
                <strong>${escapeHtml(region.Ram || "\u2014")}</strong>
            </div>

            <div class="gridmap-detail">
                <span>PORT</span>
                <strong>${region.Port || "\u2014"}</strong>
            </div>

            <div class="gridmap-detail">
                <span>HOST</span>
                <strong>${escapeHtml(region.ExternalHostName || "\u2014")}</strong>
            </div>

        </div>
    `;
}


function drawMap(regions){

    if(!regions.length){
        map.innerHTML =
            '<div class="gridmap-message">No regions found.</div>';
        return;
    }

    const minX = Math.min(...regions.map(r => r.X));
    const maxX = Math.max(...regions.map(r => r.X + r.CellsX));

    const minY = Math.min(...regions.map(r => r.Y));
    const maxY = Math.max(...regions.map(r => r.Y + r.CellsY));

    const width =
        ((maxX - minX) * CELL) +
        (PADDING * 2);

    const height =
        ((maxY - minY) * CELL) +
        (PADDING * 2);

    map.style.width = width + "px";
    map.style.height = height + "px";

    map.innerHTML = "";

    regions.forEach(region => {

        const el = document.createElement("div");

        el.className = "map-region";
        el.dataset.region = region.RegionName;

        /*
         * OpenSim Y increases north.
         * Browser Y increases downward.
         * Therefore the map Y axis is reversed.
         */

        const left =
            PADDING +
            ((region.X - minX) * CELL);

        const top =
            PADDING +
            ((maxY - (region.Y + region.CellsY)) * CELL);

        const width =
            Math.max(region.CellsX * CELL, CELL);

        const height =
            Math.max(region.CellsY * CELL, CELL);

        el.style.left = left + "px";
        el.style.top = top + "px";
        el.style.width = width + "px";
        el.style.height = height + "px";

        const status = region.Status || "Unknown";

        el.innerHTML = `
            <div class="map-region-head">

                <span class="map-dot ${statusClass(region)}"></span>

                <span class="map-region-name">
                    ${escapeHtml(region.RegionName)}
                </span>

            </div>

            <div class="map-region-body">

                <div>
                    <strong>${region.CellsX}\u00D7${region.CellsY}</strong>
                </div>

                <div>
                    ${Number(region.AvatarCount || 0)} avatar(s)
                </div>

                <div>
                    ${Number(region.PrimCount || 0).toLocaleString()} prims
                </div>

            </div>
        `;

        el.addEventListener("click", () => {
            showDetails(region);
        });

        if(selectedName === region.RegionName){
            el.classList.add("selected");
        }

        map.appendChild(el);
    });
}


async function loadMap(){

    refreshButton.disabled = true;
    refreshButton.textContent = "REFRESHING...";

    try{

        const [mapResponse, liveResponse] = await Promise.all([
            fetch(
                "/Other/grid-map-data-user.php?_=" + Date.now(),
                {cache:"no-store"}
            ),
            fetch(
                "/Other/grid-regions-user.php?_=" + Date.now(),
                {cache:"no-store"}
            )
        ]);

        const mapData = await mapResponse.json();
        const liveData = await liveResponse.json();

        if(!mapData.ok){
            throw new Error(
                mapData.error || "Unable to load grid coordinates."
            );
        }

        if(!liveData.ok){
            throw new Error(
                liveData.error || "Unable to load live region status."
            );
        }

        const liveLookup = new Map();

        (liveData.regions || []).forEach(region => {
            liveLookup.set(
                normaliseName(region.RegionName),
                region
            );
        });

        allRegions = (mapData.regions || []).map(region => {

            const live =
                liveLookup.get(
                    normaliseName(region.RegionName)
                ) || {};

            return Object.assign({}, region, live);
        });

        drawMap(allRegions);

        if(selectedName){
            const selected = allRegions.find(
                r => r.RegionName === selectedName
            );

            if(selected){
                showDetails(selected);
            }
        }

        updated.textContent =
            "Last updated " +
            new Date().toLocaleTimeString();

    }
    catch(error){

        map.innerHTML =
            '<div class="gridmap-message">' +
            escapeHtml(error.message) +
            '</div>';

        updated.textContent = "Map update failed";
    }
    finally{

        refreshButton.disabled = false;
        refreshButton.textContent = "REFRESH MAP";
    }
}


refreshButton.addEventListener("click", loadMap);

loadMap();

})();
