(function () {
'use strict';
if (window.__dg3dSharedEngine) return;
window.__dg3dSharedEngine = true;
function endpoint(name) { return '/Other/grid-map-3d-' + name + (window.AG_3D_MAP?.userMode ? '-user' : '') + '.php'; }
/*
 * ============================================================
 * PERSISTENT 3D SCENE CACHE V6C-H12-R2
 *
 * Persistent client cache:
 *
 *   - High-LOD mesh JSON
 *   - Decoded material JSON
 *   - Last successful region object snapshot
 *
 * Protected page/auth responses are NEVER cached here.
 *
 * Region snapshots use stale-while-revalidate:
 * last successful data displays immediately, fresh data is
 * fetched quietly and stored for the next visit.
 *
 * ============================================================
 */

(function(){

    "use strict";

    if(window.__ag3dPersistentSceneCacheH12R2 === true){
        return;
    }

    window.__ag3dPersistentSceneCacheH12R2 = true;

    const DB_NAME =
        "Australia3DSceneCacheV6CH12R2";

    const DB_VERSION =
        1;

    const STORE_MESH =
        "mesh";

    const STORE_MATERIAL =
        "material";

    const STORE_OBJECTS =
        "objects";

    let dbPromise =
        null;

    function openDb(){

        if(dbPromise){
            return dbPromise;
        }

        dbPromise =
            new Promise(
                function(resolve){

                    if(!window.indexedDB){

                        resolve(null);
                        return;
                    }

                    let request;

                    try{

                        request =
                            indexedDB.open(
                                DB_NAME,
                                DB_VERSION
                            );
                    }
                    catch(error){

                        resolve(null);
                        return;
                    }

                    request.onupgradeneeded =
                        function(event){

                            const db =
                                event.target.result;

                            [
                                STORE_MESH,
                                STORE_MATERIAL,
                                STORE_OBJECTS
                            ].forEach(
                                function(storeName){

                                    if(
                                        !db.objectStoreNames.contains(
                                            storeName
                                        )
                                    ){

                                        db.createObjectStore(
                                            storeName
                                        );
                                    }
                                }
                            );
                        };

                    request.onsuccess =
                        function(){

                            resolve(
                                request.result
                            );
                        };

                    request.onerror =
                        function(){

                            resolve(null);
                        };
                }
            );

        return dbPromise;
    }

    async function cacheGet(
        storeName,
        key
    ){

        const db =
            await openDb();

        if(!db){
            return null;
        }

        return await new Promise(
            function(resolve){

                try{

                    const transaction =
                        db.transaction(
                            storeName,
                            "readonly"
                        );

                    const request =
                        transaction
                            .objectStore(
                                storeName
                            )
                            .get(
                                key
                            );

                    request.onsuccess =
                        function(){

                            resolve(
                                request.result ||
                                null
                            );
                        };

                    request.onerror =
                        function(){

                            resolve(null);
                        };
                }
                catch(error){

                    resolve(null);
                }
            }
        );
    }

    async function cachePut(
        storeName,
        key,
        value
    ){

        const db =
            await openDb();

        if(!db){
            return false;
        }

        return await new Promise(
            function(resolve){

                try{

                    const transaction =
                        db.transaction(
                            storeName,
                            "readwrite"
                        );

                    transaction
                        .objectStore(
                            storeName
                        )
                        .put(
                            value,
                            key
                        );

                    transaction.oncomplete =
                        function(){

                            resolve(true);
                        };

                    transaction.onerror =
                        function(){

                            resolve(false);
                        };

                    transaction.onabort =
                        function(){

                            resolve(false);
                        };
                }
                catch(error){

                    resolve(false);
                }
            }
        );
    }

    function validJsonText(
        text
    ){

        if(
            typeof text !== "string" ||
            text.length < 2
        ){
            return false;
        }

        try{

            const decoded =
                JSON.parse(
                    text
                );

            return (
                decoded !== null &&
                typeof decoded === "object"
            );
        }
        catch(error){

            return false;
        }
    }

    function makeJsonResponse(
        text,
        cacheState
    ){

        return new Response(
            text,
            {
                status:
                    200,

                headers: {
                    "Content-Type":
                        "application/json; charset=utf-8",

                    "X-Australia-3D-Cache":
                        cacheState
                }
            }
        );
    }

    function simpleHash(
        text
    ){

        let hash =
            2166136261;

        for(
            let index = 0;
            index < text.length;
            index++
        ){

            hash ^=
                text.charCodeAt(
                    index
                );

            hash =
                Math.imul(
                    hash,
                    16777619
                );
        }

        return (
            hash >>> 0
        ).toString(16);
    }

    function canonicalUrlKey(
        rawUrl
    ){

        try{

            const url =
                new URL(
                    rawUrl,
                    window.location.href
                );

            [
                "_",
                "v",
                "ts",
                "t",
                "cb",
                "cache"
            ].forEach(
                function(parameter){

                    url.searchParams.delete(
                        parameter
                    );
                }
            );

            /*
             * Stable alphabetical parameter order.
             */

            const entries =
                Array.from(
                    url.searchParams.entries()
                ).sort(
                    function(a,b){

                        return (
                            a[0] + "=" + a[1]
                        ).localeCompare(
                            b[0] + "=" + b[1]
                        );
                    }
                );

            const stableParams =
                new URLSearchParams();

            entries.forEach(
                function(entry){

                    stableParams.append(
                        entry[0],
                        entry[1]
                    );
                }
            );

            return (
                url.pathname +
                (
                    stableParams.toString()
                        ? "?" +
                          stableParams.toString()
                        : ""
                )
            );
        }
        catch(error){

            return String(
                rawUrl
            );
        }
    }

    const originalFetch =
        window.fetch.bind(
            window
        );

    async function storeResponse(
        response,
        storeName,
        key,
        extra
    ){

        if(
            !response ||
            response.ok !== true
        ){
            return;
        }

        try{

            const text =
                await response
                    .clone()
                    .text();

            if(
                !validJsonText(
                    text
                )
            ){
                return;
            }

            await cachePut(
                storeName,
                key,
                Object.assign(
                    {
                        text:
                            text,

                        savedAt:
                            Date.now()
                    },
                    extra ||
                    {}
                )
            );
        }
        catch(error){
        }
    }

    async function refreshObjectSnapshot(
        input,
        init,
        key
    ){

        try{

            let refreshInput =
                input;

            if(
                typeof Request !== "undefined" &&
                input instanceof Request
            ){

                refreshInput =
                    input.clone();
            }

            const response =
                await originalFetch(
                    refreshInput,
                    init
                );

            await storeResponse(
                response,
                STORE_OBJECTS,
                key,
                null
            );

            console.info(
                "PERSISTENT 3D SCENE CACHE V6C-H12-R2",
                "region snapshot refreshed",
                key
            );
        }
        catch(error){

            /*
             * Existing last-known-good snapshot remains.
             */
        }
    }

    window.fetch =
        async function(
            input,
            init
        ){

            let rawUrl =
                "";

            let method =
                "GET";

            try{

                if(
                    typeof Request !== "undefined" &&
                    input instanceof Request
                ){

                    rawUrl =
                        input.url;

                    method =
                        String(
                            input.method ||
                            "GET"
                        ).toUpperCase();
                }
                else{

                    rawUrl =
                        String(
                            input
                        );
                }

                if(
                    init &&
                    init.method
                ){

                    method =
                        String(
                            init.method
                        ).toUpperCase();
                }
            }
            catch(error){

                return originalFetch(
                    input,
                    init
                );
            }

            let url;

            try{

                url =
                    new URL(
                        rawUrl,
                        window.location.href
                    );
            }
            catch(error){

                return originalFetch(
                    input,
                    init
                );
            }

            const pathname =
                String(
                    url.pathname ||
                    ""
                ).toLowerCase();

            /*
             * ==================================================
             * HIGH-LOD MESH JSON
             * ==================================================
             */

            if(
                method === "GET" &&
                pathname.endsWith(
                    endpoint('mesh').toLowerCase()
                )
            ){

                const key =
                    canonicalUrlKey(
                        rawUrl
                    );

                const cached =
                    await cacheGet(
                        STORE_MESH,
                        key
                    );

                if(
                    cached &&
                    validJsonText(
                        cached.text
                    )
                ){

                    window.dg3dMetrics?.cache('meshBrowser');
                    return makeJsonResponse(
                        cached.text,
                        "H12-R2-MESH-HIT"
                    );
                }

                const response =
                    await originalFetch(
                        input,
                        init
                    );

                await storeResponse(
                    response,
                    STORE_MESH,
                    key,
                    null
                );

                return response;
            }

            /*
             * ==================================================
             * MATERIAL DECODER
             * Supports both GET and POST usage.
             * ==================================================
             */

            if(
                pathname.endsWith(
                    endpoint('material').toLowerCase()
                )
            ){

                let key =
                    "";

                let requestBody =
                    "";

                if(
                    method === "GET"
                ){

                    key =
                        canonicalUrlKey(
                            rawUrl
                        );
                }
                else if(
                    init &&
                    typeof init.body === "string"
                ){

                    requestBody =
                        init.body;

                    key =
                        "POST:" +
                        simpleHash(
                            requestBody
                        ) +
                        ":" +
                        requestBody.length;
                }

                if(key){

                    const cached =
                        await cacheGet(
                            STORE_MATERIAL,
                            key
                        );

                    if(
                        cached &&
                        (
                            !requestBody ||
                            cached.body ===
                                requestBody
                        ) &&
                        validJsonText(
                            cached.text
                        )
                    ){

                        return makeJsonResponse(
                            cached.text,
                            "H12-R2-MATERIAL-HIT"
                        );
                    }

                    const response =
                        await originalFetch(
                            input,
                            init
                        );

                    await storeResponse(
                        response,
                        STORE_MATERIAL,
                        key,
                        {
                            body:
                                requestBody
                        }
                    );

                    return response;
                }
            }

            /*
             * ==================================================
             * REGION OBJECT SNAPSHOT
             *
             * Cached copy returns immediately.
             * New copy refreshes quietly in background.
             * ==================================================
             */

            if(
                method === "GET" &&
                pathname.endsWith(
                    endpoint('objects').toLowerCase()
                )
            ){

                const key =
                    canonicalUrlKey(
                        rawUrl
                    );

                const cached =
                    await cacheGet(
                        STORE_OBJECTS,
                        key
                    );

                if(
                    cached &&
                    validJsonText(
                        cached.text
                    )
                ){

                    refreshObjectSnapshot(
                        input,
                        init,
                        key
                    );

                    return makeJsonResponse(
                        cached.text,
                        "H12-R2-OBJECT-SNAPSHOT-HIT"
                    );
                }

                const response =
                    await originalFetch(
                        input,
                        init
                    );

                await storeResponse(
                    response,
                    STORE_OBJECTS,
                    key,
                    null
                );

                return response;
            }

            return originalFetch(
                input,
                init
            );
        };

    /*
     * Ask the browser not to evict this database casually.
     * Permission is browser-controlled and failure is harmless.
     */

    try{

        if(
            navigator.storage &&
            typeof navigator.storage.persist ===
                "function"
        ){

            navigator.storage
                .persist()
                .catch(
                    function(){}
                );
        }
    }
    catch(error){
    }

    /*
     * Main-thread yielding helper.
     */

    window.ag3dH12YieldToBrowser =
        function(){

            return new Promise(
                function(resolve){

                    if(
                        typeof window.requestAnimationFrame ===
                            "function"
                    ){

                        window.requestAnimationFrame(
                            function(){

                                window.setTimeout(
                                    resolve,
                                    0
                                );
                            }
                        );
                    }
                    else{

                        window.setTimeout(
                            resolve,
                            0
                        );
                    }
                }
            );
        };

    console.info(
        "PERSISTENT 3D SCENE CACHE V6C-H12-R2 - ACTIVE"
    );

})();

(function(){

"use strict";

const cfg = window.AG_3D_MAP || {};
cfg.meshLod = cfg.meshLod || 'medium';


const dataUrl       = cfg.dataUrl || endpoint('data');

const canvasHost    = document.getElementById("map3d-canvas");
const resultsHost   = document.getElementById("map3d-results");
const searchInput   = document.getElementById("map3d-search");
const searchButton  = document.getElementById("map3d-search-button");

const refreshButton = document.getElementById("map3d-refresh");
const homeButton    = document.getElementById("map3d-home");
const topButton     = document.getElementById("map3d-top");
const angleButton   = document.getElementById("map3d-angle");

const statusPill    = document.getElementById("map3d-status");

const copyHopButton = document.getElementById("map3d-copy-hop");
const focusButton   = document.getElementById("map3d-focus");

const infoName      = document.getElementById("map3d-info-name");
const infoStatus    = document.getElementById("map3d-info-status");
const infoLocation  = document.getElementById("map3d-info-location");
const infoSize      = document.getElementById("map3d-info-size");
const infoEstate    = document.getElementById("map3d-info-estate");
const infoAvatars   = document.getElementById("map3d-info-avatars");
const infoHop       = document.getElementById("map3d-info-hop");


let scene;
let camera;
let renderer;
let controls;

let regionGroup;

let allRegions = [];
let selectedRegion = null;
let sceneParts = [];
let map3dPrioritizeObject=null;
let map3dTextureBytes=0, map3dGeneration=0;
const map3dTransformedTextures=new Map();
const map3dIdentityCache=new WeakMap();
const meshDiagnostics = new Map(), textureDiagnostics = new Map();
let inspectMode = false, inspectOutput;
function map3dIdentity(part) {
    if(map3dIdentityCache.has(part))return map3dIdentityCache.get(part);
    let identity=part[12]||{metadata:'Older snapshot; refresh required'};
    if(typeof identity==='string'){
        const value=JSON.parse(identity);
        const fields=['PCode','State','ProfileCurve','PathCurve','ProfileBegin','ProfileEnd','ProfileHollow','PathBegin','PathEnd','PathScaleX','PathScaleY','PathShearX','PathShearY','PathTwist','PathTwistBegin','PathRadiusOffset','PathTaperX','PathTaperY','PathRevolutions','PathSkew','SculptType','SculptTexture','SculptEntry'];
        identity={uuid:value[0],name:value[1],link:value[2],rootUuid:value[3],shape:Object.fromEntries(fields.map((key,i)=>[key,value[4][i]]))};
    }
    map3dIdentityCache.set(part,identity);
    return identity;
}
function map3dAsset(part) {
    const identity=map3dIdentity(part), shape=identity.shape||{};
    const type=Number(shape.SculptType)||0, topology=type&7;
    const sculpt=topology>=1&&topology<=4&&String(shape.SculptEntry).toLowerCase()==='true';
    const uuid=String(sculpt?shape.SculptTexture:part[10]||'').toLowerCase();
    if (!uuid && Number(shape.PCode)===9) {
        const values=Object.values(shape).slice(0,20).map(value=>Number(value)||0);
        const key='prim:'+values.join('_');
        return {uuid:key,type:0,key,kind:'prim'};
    }
    return {uuid,type:sculpt?type:0,key:sculpt?uuid+':sculpt:'+type:uuid,kind:sculpt?'sculpt':'mesh'};
}
function inspectObject(event) {
    if (!inspectMode) return;
    const rect=renderer.domElement.getBoundingClientRect();
    const ray=new THREE.Raycaster();
    ray.setFromCamera(new THREE.Vector2((event.clientX-rect.left)/rect.width*2-1,1-(event.clientY-rect.top)/rect.height*2),camera);
    const hits=ray.intersectObjects(regionGroup.children,true).filter(hit=>hit.object.userData.partIndices);
    if(!hits.length){inspectOutput.textContent='No inspectable object at this point.';return;}
    displayInspection(hits[0]);
}
function displayInspection(hit) {
    const object=hit.object;
    const index=object.userData.partIndices[hit.instanceId ?? 0];
    const part=sceneParts[index] || [];
    const materials=(Array.isArray(object.material)?object.material:[object.material]).filter(Boolean);
    const identity=map3dIdentity(part);
    const data={identity, worldPosition:part.slice(0,3), scale:part.slice(3,6),
        meshUuid:part[10]||null, geometry:meshDiagnostics.get(map3dAsset(part).key)||{status:'not scheduled',reason:part[10]?'outside mesh budget':'non-mesh path'},
        finalGeometry:object.geometry.type, vertices:object.geometry.attributes.position?.count,
        instanceId:hit.instanceId, renderName:object.name, fallbackReason:object.userData.fallbackReason,
        materials:materials.map(m=>({type:m.type,textureUuid:m.userData?.openSimTextureUuid,materialUuid:m.userData?.openSimMaterialId,
            face:m.userData?.openSimFace,imageAlpha:m.userData?.imageAlpha,alphaMode:m.userData?.alphaMode,materialMetadata:m.userData?.materialMetadata,textureError:m.userData?.textureError,
            texture:textureDiagnostics.get(m.userData?.openSimTextureUuid),transparent:m.transparent,opacity:m.opacity,alphaTest:m.alphaTest,
            color:m.color?.getHexString(),image:m.map?{width:m.map.image?.width,height:m.map.image?.height}:null}))};
    inspectOutput.textContent=JSON.stringify(data,null,2);
}

let currentMode = "angle";

/* ============================================================
   UNIFIED REALISTIC OCEAN V5
   Independent moving-water layers.
   Region renderer/search/list are untouched.
   ============================================================ */

let ag3dOceanBase = null;
let ag3dOceanDeep = null;
let ag3dOceanSurface = null;
let ag3dOceanStart = performance.now();


/* END UNIFIED REALISTIC OCEAN V5 */


function normalise(value){

    return String(value || "")
        .trim()
        .toLowerCase();
}


function escapeHtml(value){

    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


function setStatus(text){

    if(statusPill){
        statusPill.textContent = text;
    }
}


function statusType(region){

    const text =
        String(
            region.StatusClass ||
            region.Status ||
            ""
        ).toLowerCase();

    if(
        text.includes("online") ||
        text.includes("running") ||
        text.includes("booted")
    ){
        return "online";
    }

    if(
        text.includes("warning") ||
        text.includes("busy") ||
        text.includes("starting") ||
        text.includes("backup")
    ){
        return "warning";
    }

    return "offline";
}


function regionColour(region){

    switch(statusType(region)){

        case "online":
            return 0x38a965;

        case "warning":
            return 0xc99a32;

        default:
            return 0x596572;
    }
}


function init3D(){

    if(typeof THREE === "undefined"){

        setStatus("Three.js failed to load");

        resultsHost.innerHTML =
            '<div class="empty-message">' +
            'THREE.JS FAILED TO LOAD' +
            '</div>';

        return false;
    }


    if(typeof THREE.OrbitControls !== "function"){

        setStatus("Orbit controls failed to load");

        resultsHost.innerHTML =
            '<div class="empty-message">' +
            '3D ORBIT CONTROLS FAILED TO LOAD' +
            '</div>';

        return false;
    }


    scene =
        new THREE.Scene();

    scene.background =
        new THREE.Color(
            0x07111a
        );


    camera =
        new THREE.PerspectiveCamera(
            46,
            1,
            0.1,
            5000
        );


    renderer =
        new THREE.WebGLRenderer({
            antialias:true
        });


    renderer.setPixelRatio(
        Math.min(
            window.devicePixelRatio || 1,
            2
        )
    );


    canvasHost.innerHTML = "";

    canvasHost.appendChild(
        renderer.domElement
    );


    controls =
        new THREE.OrbitControls(
            camera,
            renderer.domElement
        );


    controls.enableDamping = true;

    controls.dampingFactor = 0.08;

    controls.screenSpacePanning = true;

    controls.minDistance = 0.10;

    controls.maxDistance = 1600;


    scene.add(
        new THREE.AmbientLight(
            0xffffff,
            0.75
        )
    );


    const mainLight =
        new THREE.DirectionalLight(
            0xffffff,
            0.95
        );

    mainLight.position.set(
        200,
        300,
        180
    );

    scene.add(
        mainLight
    );


    const fillLight =
        new THREE.DirectionalLight(
            0x7aaee8,
            0.30
        );

    fillLight.position.set(
        -180,
        140,
        -160
    );

    scene.add(
        fillLight
    );


    regionGroup =
        new THREE.Group();

    scene.add(
        regionGroup
    );


    window.addEventListener(
        "resize",
        resize3D
    );


    renderer.domElement.addEventListener('pointerup', inspectObject);
    resize3D();

    animate();

    return true;
}


function resize3D(){

    if(
        !renderer ||
        !camera
    ){
        return;
    }


    const width =
        Math.max(
            canvasHost.clientWidth,
            100
        );


    const height =
        Math.max(
            canvasHost.clientHeight,
            100
        );


    camera.aspect =
        width / height;


    camera.updateProjectionMatrix();


    renderer.setSize(
        width,
        height
    );
}


function clearRegion(){
    map3dPrioritizeObject=null;
    map3dGeneration++;map3dTextureBytes=0;map3dTransformedTextures.clear();
    sceneParts=[];meshDiagnostics.clear();textureDiagnostics.clear();
    document.getElementById('dg3d-loading')?.remove();
    ag3dOceanBase = ag3dOceanDeep = ag3dOceanSurface = null;
    if (!regionGroup) return;
    const geometries = new Set(), materials = new Set(), textures = new Set();
    const disposeMaterial = material => {
        if (!material || materials.has(material)) return;
        materials.add(material);
        for (const value of Object.values(material)) {
            if (value && value.isTexture && !textures.has(value)) { textures.add(value); value.dispose(); }
        }
        material.dispose();
    };
    while(regionGroup.children.length){
        const object = regionGroup.children[0];
        regionGroup.remove(object);
        object.traverse(node => {
            if(node.geometry && !geometries.has(node.geometry)){geometries.add(node.geometry);node.geometry.dispose();}
            const list=Array.isArray(node.material)?node.material:[node.material];
            list.forEach(disposeMaterial);
        });
    }
    // Dispose eventual results too: a region switch may happen during a decode.
    for(const promise of ag3dV6cSharedFaceMaterialCacheH13R2.values()) Promise.resolve(promise).then(disposeMaterial,()=>{});
    ag3dV6cSharedFaceMaterialCacheH13R2.clear();
    for(const promise of ag3dV6cTexturePromiseCache.values()) Promise.resolve(promise).then(texture=>{
        if(texture && !textures.has(texture)){textures.add(texture);texture.dispose();}
    },()=>{});
    ag3dV6cTexturePromiseCache.clear();
}

/* ============================================================
   3D FIRESTORM WATER TEXTURE V4
   Same texture used on existing Grid Maps.
   ============================================================ */

function ag3dCreateOceanTexture(
    repeatX,
    repeatY
){

    const agTexture =
        new THREE.TextureLoader().load(
            "/Other/images/Water-Texture.png?v=20"
        );

    agTexture.wrapS =
        THREE.RepeatWrapping;

    agTexture.wrapT =
        THREE.RepeatWrapping;

    agTexture.repeat.set(
        repeatX,
        repeatY
    );

    return agTexture;
}


/* END 3D FIRESTORM WATER TEXTURE V4 */


/* ============================================================
   LIVE OPENSIM REGION TEXTURE V1

   Builds the selected region surface from the real OpenSim
   map-1-X-Y-objects.jpg tiles.

   Images are fetched through grid-map-3d-tile.php so Three.js
   sees a same-origin image source.

   Water system is completely independent and untouched.
   ============================================================ */

function ag3dCreateLiveRegionTexture(
    region,
    cellsX,
    cellsY
){

    const TILE_SIZE =
        256;

    const safeCellsX =
        Math.max(
            1,
            Math.round(
                Number(
                    cellsX ||
                    1
                )
            )
        );

    const safeCellsY =
        Math.max(
            1,
            Math.round(
                Number(
                    cellsY ||
                    1
                )
            )
        );


    /*
     * One canvas becomes one texture over the entire
     * selected variable-size region.
     */

    const canvas =
        document.createElement(
            "canvas"
        );

    canvas.width =
        safeCellsX *
        TILE_SIZE;

    canvas.height =
        safeCellsY *
        TILE_SIZE;


    const ctx =
        canvas.getContext(
            "2d"
        );


    /*
     * Temporary fallback colour while tiles arrive.
     */

    ctx.fillStyle =
        "#173b2c";

    ctx.fillRect(
        0,
        0,
        canvas.width,
        canvas.height
    );


    const texture =
        new THREE.CanvasTexture(
            canvas
        );

    texture.minFilter =
        THREE.LinearFilter;

    texture.magFilter =
        THREE.LinearFilter;

    texture.wrapS =
        THREE.ClampToEdgeWrapping;

    texture.wrapT =
        THREE.ClampToEdgeWrapping;

    texture.generateMipmaps =
        true;

    texture.needsUpdate =
        true;


    const baseX =
        Number(
            region.X ||
            0
        );

    const baseY =
        Number(
            region.Y ||
            0
        );


    /*
     * OpenSim Y increases northward.
     *
     * Canvas row zero is the TOP of the image, so the highest
     * Y tile is drawn first.
     */

    for(
        let row = 0;
        row < safeCellsY;
        row++
    ){

        for(
            let column = 0;
            column < safeCellsX;
            column++
        ){

            const tileX =
                baseX +
                column;

            const tileY =
                baseY +
                (
                    safeCellsY -
                    1 -
                    row
                );


            const image =
                new Image();


            image.addEventListener(
                "load",
                function(){

                    ctx.drawImage(
                        image,
                        column *
                        TILE_SIZE,
                        row *
                        TILE_SIZE,
                        TILE_SIZE,
                        TILE_SIZE
                    );

                    texture.needsUpdate =
                        true;
                }
            );


            image.addEventListener(
                "error",
                function(){

                    /*
                     * Missing/offline cells retain the dark
                     * fallback rather than breaking the map.
                     */

                    ctx.save();

                    ctx.fillStyle =
                        "#122c23";

                    ctx.fillRect(
                        column *
                        TILE_SIZE,
                        row *
                        TILE_SIZE,
                        TILE_SIZE,
                        TILE_SIZE
                    );

                    ctx.strokeStyle =
                        "rgba(212,166,46,0.18)";

                    ctx.strokeRect(
                        (
                            column *
                            TILE_SIZE
                        ) +
                        1,
                        (
                            row *
                            TILE_SIZE
                        ) +
                        1,
                        TILE_SIZE -
                        2,
                        TILE_SIZE -
                        2
                    );

                    ctx.restore();

                    texture.needsUpdate =
                        true;
                }
            );


            image.src =
                endpoint('tile') +
                "?x=" +
                encodeURIComponent(
                    String(
                        tileX
                    )
                ) +
                "&y=" +
                encodeURIComponent(
                    String(
                        tileY
                    )
                ) +
                "&_=" +
                Date.now();
        }
    }


    return texture;
}

/* END LIVE OPENSIM REGION TEXTURE V1 */


/* ============================================================
   REAL OPENSIM TERRAIN V1

   NORTI FARM proof-of-concept.

   Uses the genuine R32 heightfield extracted from the OAR.
   Real map imagery remains draped over the terrain.

   OpenSim water height:
       20.000 metres

   Horizontal scene scale:
       70 Three.js units per 256 OpenSim metres

   Vertical scale uses the same ratio, so terrain is not
   artificially exaggerated.
   ============================================================ */


/*
 * ============================================================
 * REAL OPENSIM TERRAIN TEXTURES V6C-H26-R4
 *
 * Uses the four actual OpenSim terrain texture assets from the
 * region's regionsettings record.
 *
 * Uses the region's low/high elevation settings.
 *
 * Uses world-space triplanar texture projection so vertical
 * mountains do not smear the terrain texture down the cliff.
 *
 * IMPORTANT SAFETY:
 *
 * The existing live map-image terrain is never modified,
 * hidden or replaced.
 *
 * This renderer creates a second terrain surface over it.
 * If any setting, texture or shader fails, the original land
 * stays exactly where it is.
 * ============================================================
 */


function ag3dH26R4ValidUuid(
    value
){

    return /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(
        String(
            value ||
            ""
        ).trim()
    );
}


function ag3dH26R4Number(
    value,
    fallback
){

    const number =
        Number(
            value
        );

    return Number.isFinite(
        number
    )
        ? number
        : fallback;
}


async function ag3dH26R4GetSettings(
    regionName
){

    const response =
        await fetch(
            endpoint('terrain-settings') +
            "?region=" +
            encodeURIComponent(
                regionName
            ) +
            "&_=" +
            Date.now(),
            {
                cache:
                    "no-store",

                credentials:
                    "same-origin"
            }
        );


    if(!response.ok){

        throw new Error(
            "terrain settings HTTP " +
            response.status
        );
    }


    const data =
        await response.json();


    if(
        !data ||
        data.ok !== true ||
        !Array.isArray(
            data.TerrainTextures
        ) ||
        data.TerrainTextures.length !== 4
    ){

        throw new Error(
            (
                data &&
                data.error
            )
                ? data.error
                : "invalid terrain settings"
        );
    }


    return data;
}


async function ag3dH26R4LoadTerrainTexture(
    uuid
){

    const source =
        await ag3dV6cLoadBaseTexture(
            uuid
        );


    if(!source){

        throw new Error(
            "terrain texture unavailable " +
            uuid
        );
    }


    /*
     * Clone the cached object texture.
     *
     * Terrain RepeatWrapping must not change the same texture
     * object used by buildings/meshes elsewhere.
     */

    const texture =
        source.clone();


    texture.wrapS =
        THREE.RepeatWrapping;

    texture.wrapT =
        THREE.RepeatWrapping;

    texture.repeat.set(
        1,
        1
    );

    texture.offset.set(
        0,
        0
    );

    texture.rotation =
        0;


    texture.generateMipmaps =
        true;

    texture.minFilter =
        THREE.LinearMipmapLinearFilter;

    texture.magFilter =
        THREE.LinearFilter;


    try{

        if(
            renderer &&
            renderer.capabilities &&
            typeof renderer.capabilities
                .getMaxAnisotropy ===
                "function"
        ){

            texture.anisotropy =
                Math.max(
                    1,
                    renderer.capabilities
                        .getMaxAnisotropy()
                );
        }
    }
    catch(error){
    }


    if(
        typeof THREE.sRGBEncoding !==
        "undefined"
    ){

        texture.encoding =
            THREE.sRGBEncoding;
    }


    texture.needsUpdate =
        true;


    return texture;
}


async function ag3dH26R4Install(
    region,
    surface,
    terrainData
){

    if(
        !region ||
        !surface ||
        !terrainData ||
        !Array.isArray(
            terrainData.Heights
        )
    ){

        return false;
    }


    surface.userData =
        surface.userData ||
        {};


    if(
        surface.userData
            .agH26R4Installed ===
            true ||
        surface.userData
            .agH26R4Loading ===
            true
    ){

        return true;
    }


    surface.userData
        .agH26R4Loading =
        true;


    const regionName =
        String(
            region.RegionName ||
            ""
        ).trim();


    try{

        const settings =
            await ag3dH26R4GetSettings(
                regionName
            );


        if(
            selectedRegion !== region ||
            surface.parent !== regionGroup
        ){

            surface.userData
                .agH26R4Loading =
                false;

            return false;
        }


        const textureUuids =
            settings.TerrainTextures
                .map(
                    function(
                        uuid
                    ){

                        return String(
                            uuid ||
                            ""
                        )
                        .trim()
                        .toLowerCase();
                    }
                );


        if(
            textureUuids.some(
                function(
                    uuid
                ){

                    return !ag3dH26R4ValidUuid(
                        uuid
                    );
                }
            )
        ){

            throw new Error(
                "invalid terrain texture UUID"
            );
        }


        const textures =
            await Promise.all(
                textureUuids.map(
                    function(
                        uuid
                    ){

                        return ag3dH26R4LoadTerrainTexture(
                            uuid
                        );
                    }
                )
            );


        if(
            selectedRegion !== region ||
            surface.parent !== regionGroup
        ){

            textures.forEach(
                function(
                    texture
                ){

                    try{
                        texture.dispose();
                    }
                    catch(error){
                    }
                }
            );


            surface.userData
                .agH26R4Loading =
                false;


            return false;
        }


        /*
         * Clone the FINISHED live terrain geometry.
         *
         * The original terrain geometry remains untouched.
         */

        const overlayGeometry =
            surface.geometry.clone();


        if(
            !overlayGeometry ||
            !overlayGeometry.attributes ||
            !overlayGeometry.attributes.position ||
            !overlayGeometry.attributes.uv
        ){

            throw new Error(
                "terrain geometry clone unavailable"
            );
        }


        const vertexCount =
            overlayGeometry.attributes
                .position.count;


        if(
            terrainData.Heights.length !==
            vertexCount
        ){

            overlayGeometry.dispose();


            throw new Error(
                "terrain height count mismatch " +
                terrainData.Heights.length +
                " / " +
                vertexCount
            );
        }


        /*
         * Give the shader each vertex's REAL simulator height
         * in metres.
         */

        const openSimHeights =
            new Float32Array(
                vertexCount
            );


        for(
            let index = 0;
            index < vertexCount;
            index++
        ){

            openSimHeights[index] =
                ag3dH26R4Number(
                    terrainData.Heights[index],
                    0
                );
        }


        overlayGeometry.setAttribute(
            "agOpenSimHeight",
            new THREE.BufferAttribute(
                openSimHeights,
                1
            )
        );


        /*
         * Recalculate normals on the overlay only.
         */

        overlayGeometry.computeVertexNormals();


        const low =
            settings.Elevation1 ||
            {};


        const high =
            settings.Elevation2 ||
            {};


        /*
         * Existing proven scene scale:
         *
         * 67.2 Three units = 256 OpenSim metres.
         *
         * Terrain detail repeats approximately every 12m.
         */

        const metresToScene =
            (
                70 *
                0.96
            ) /
            256;


        const repeatScale =
            1 /
            (
                12 *
                metresToScene
            );


        const material =
            new THREE.ShaderMaterial({

                uniforms:{

                    terrainTexture1:{
                        value:
                            textures[0]
                    },

                    terrainTexture2:{
                        value:
                            textures[1]
                    },

                    terrainTexture3:{
                        value:
                            textures[2]
                    },

                    terrainTexture4:{
                        value:
                            textures[3]
                    },


                    /*
                     * x SW
                     * y SE
                     * z NW
                     * w NE
                     */

                    terrainLow:{
                        value:
                            new THREE.Vector4(

                                ag3dH26R4Number(
                                    low.SW,
                                    10
                                ),

                                ag3dH26R4Number(
                                    low.SE,
                                    10
                                ),

                                ag3dH26R4Number(
                                    low.NW,
                                    10
                                ),

                                ag3dH26R4Number(
                                    low.NE,
                                    10
                                )
                            )
                    },


                    terrainHigh:{
                        value:
                            new THREE.Vector4(

                                ag3dH26R4Number(
                                    high.SW,
                                    60
                                ),

                                ag3dH26R4Number(
                                    high.SE,
                                    60
                                ),

                                ag3dH26R4Number(
                                    high.NW,
                                    60
                                ),

                                ag3dH26R4Number(
                                    high.NE,
                                    60
                                )
                            )
                    },


                    terrainRepeat:{
                        value:
                            repeatScale
                    }
                },


                vertexShader:
`
attribute float agOpenSimHeight;

varying vec3 agTerrainWorldPosition;
varying vec3 agTerrainWorldNormal;
varying vec2 agTerrainRegionUv;
varying float agTerrainHeight;


void main(){

    agTerrainRegionUv =
        uv;

    agTerrainHeight =
        agOpenSimHeight;


    vec4 worldPosition =
        modelMatrix *
        vec4(
            position,
            1.0
        );


    agTerrainWorldPosition =
        worldPosition.xyz;


    agTerrainWorldNormal =
        normalize(
            mat3(
                modelMatrix
            ) *
            normal
        );


    gl_Position =
        projectionMatrix *
        viewMatrix *
        worldPosition;
}
`,


                fragmentShader:
`
precision highp float;


uniform sampler2D terrainTexture1;
uniform sampler2D terrainTexture2;
uniform sampler2D terrainTexture3;
uniform sampler2D terrainTexture4;

uniform vec4 terrainLow;
uniform vec4 terrainHigh;

uniform float terrainRepeat;


varying vec3 agTerrainWorldPosition;
varying vec3 agTerrainWorldNormal;
varying vec2 agTerrainRegionUv;
varying float agTerrainHeight;


float agCornerBlend(
    vec4 values,
    vec2 uv
){

    float south =
        mix(
            values.x,
            values.y,
            uv.x
        );


    float north =
        mix(
            values.z,
            values.w,
            uv.x
        );


    return mix(
        south,
        north,
        uv.y
    );
}


vec4 agTerrainSample(
    sampler2D sourceTexture,
    vec3 worldPosition,
    vec3 worldNormal
){

    /*
     * World-space triplanar projection.
     *
     * This is what stops steep cliffs from stretching one
     * giant map-image pixel down a vertical mountain.
     */

    vec3 weights =
        pow(
            abs(
                normalize(
                    worldNormal
                )
            ),
            vec3(
                5.0
            )
        );


    weights /=
        max(
            weights.x +
            weights.y +
            weights.z,
            0.0001
        );


    vec4 projectedX =
        texture2D(
            sourceTexture,
            worldPosition.zy *
            terrainRepeat
        );


    vec4 projectedY =
        texture2D(
            sourceTexture,
            worldPosition.xz *
            terrainRepeat
        );


    vec4 projectedZ =
        texture2D(
            sourceTexture,
            worldPosition.xy *
            terrainRepeat
        );


    return (
        projectedX *
        weights.x +

        projectedY *
        weights.y +

        projectedZ *
        weights.z
    );
}


void main(){

    float lowHeight =
        agCornerBlend(
            terrainLow,
            agTerrainRegionUv
        );


    float highHeight =
        agCornerBlend(
            terrainHigh,
            agTerrainRegionUv
        );


    highHeight =
        max(
            highHeight,
            lowHeight +
            0.01
        );


    /*
     * Convert simulator terrain height into four layers.
     */

    float normalizedHeight =
        clamp(
            (
                agTerrainHeight -
                lowHeight
            ) /
            (
                highHeight -
                lowHeight
            ),
            0.0,
            1.0
        );


    float layer =
        normalizedHeight *
        3.0;


    float weight1 =
        max(
            1.0 -
            abs(
                layer -
                0.0
            ),
            0.0
        );


    float weight2 =
        max(
            1.0 -
            abs(
                layer -
                1.0
            ),
            0.0
        );


    float weight3 =
        max(
            1.0 -
            abs(
                layer -
                2.0
            ),
            0.0
        );


    float weight4 =
        max(
            1.0 -
            abs(
                layer -
                3.0
            ),
            0.0
        );


    float total =
        max(
            weight1 +
            weight2 +
            weight3 +
            weight4,
            0.0001
        );


    weight1 /=
        total;

    weight2 /=
        total;

    weight3 /=
        total;

    weight4 /=
        total;


    vec3 colour =

        agTerrainSample(
            terrainTexture1,
            agTerrainWorldPosition,
            agTerrainWorldNormal
        ).rgb *
        weight1

        +

        agTerrainSample(
            terrainTexture2,
            agTerrainWorldPosition,
            agTerrainWorldNormal
        ).rgb *
        weight2

        +

        agTerrainSample(
            terrainTexture3,
            agTerrainWorldPosition,
            agTerrainWorldNormal
        ).rgb *
        weight3

        +

        agTerrainSample(
            terrainTexture4,
            agTerrainWorldPosition,
            agTerrainWorldNormal
        ).rgb *
        weight4;


    /*
     * Simple viewer-style directional terrain lighting.
     */

    vec3 terrainNormal =
        normalize(
            agTerrainWorldNormal
        );


    vec3 lightDirection =
        normalize(
            vec3(
                -0.35,
                0.88,
                0.30
            )
        );


    float diffuse =
        max(
            dot(
                terrainNormal,
                lightDirection
            ),
            0.0
        );


    colour *=
        (
            0.74 +
            diffuse *
            0.34
        );


    gl_FragColor =
        vec4(
            colour,
            1.0
        );
}
`,


                side:
                    THREE.DoubleSide,

                transparent:
                    false,

                depthTest:
                    true,

                /*
                 * =================================================
                 * TERRAIN GROUND PRIM DEPTH SEPARATION V6C-H27
                 *
                 * Layer order:
                 *
                 * ORIGINAL TERRAIN
                 *      â†“
                 * H26 REAL TERRAIN TEXTURE OVERLAY
                 *      â†“
                 * PATHS / GROUND PRIMS / MESHES
                 *
                 * H26 previously used NEGATIVE polygon offset,
                 * pulling terrain toward the camera. That caused
                 * near-coplanar paths and ground prims to disappear
                 * at different camera distances.
                 *
                 * The overlay now writes depth but is pushed slightly
                 * BACK from normal objects.
                 *
                 * Original H6 terrain:
                 *     factor 1 / units 4
                 *
                 * H26 overlay:
                 *     factor 1 / units 2
                 *
                 * Therefore H26 remains slightly in front of the
                 * original terrain but behind ordinary geometry.
                 * =================================================
                 */

                depthWrite:
                    true,

                polygonOffset:
                    true,

                polygonOffsetFactor:
                    1,

                polygonOffsetUnits:
                    4,

                toneMapped:
                    false
            });


        const overlay =
            new THREE.Mesh(
                overlayGeometry,
                material
            );


        overlay.name =
            regionName +
            " REAL OPENSIM TERRAIN TEXTURES V6C-H26-R4";


        /*
         * Child of original terrain:
         *
         * exact same position
         * exact same rotation
         * exact same scale
         */

        overlay.position.set(
            0,
            0,
            0
        );

        overlay.rotation.set(
            0,
            0,
            0
        );

        overlay.scale.set(
            1,
            1,
            1
        );


        overlay.renderOrder =
            Number(
                surface.renderOrder ||
                0
            ) +
            5;


        /*
         * H8 terrain raycasts must continue hitting only the
         * proven original terrain.
         */

        overlay.raycast =
            function(){
            };


        surface.add(
            overlay
        );


        /*
         * =====================================================
         * SINGLE VISIBLE TERRAIN SURFACE V6C-H28
         *
         * H26-R4 is now proven to render successfully.
         *
         * The original map-image terrain and H26 terrain occupy
         * the exact same geometry. Rendering both causes classic
         * Z-fighting / flashing while the camera moves.
         *
         * Keep the original Mesh + geometry alive for:
         *
         *     H8 terrain raycasts
         *     terrain metadata
         *     region lifecycle
         *
         * But stop drawing its OLD material after H26 has been
         * successfully attached.
         *
         * Children remain visible when only Material.visible is
         * false, so the H26 overlay continues rendering.
         * =====================================================
         */

        if(
            Array.isArray(
                surface.material
            )
        ){

            surface.material.forEach(
                function(
                    originalMaterial
                ){

                    if(originalMaterial){

                        originalMaterial.visible =
                            false;
                    }
                }
            );
        }
        else if(
            surface.material
        ){

            surface.material.visible =
                false;
        }


        surface.userData
            .agH28OriginalTerrainMaterialHidden =
            true;


        surface.userData
            .agH26R4Overlay =
            overlay;


        surface.userData
            .agH26R4Installed =
            true;


        surface.userData
            .agH26R4Loading =
            false;


        console.info(
            "REAL OPENSIM TERRAIN TEXTURES V6C-H26-R4",
            regionName,
            textureUuids,
            settings.Elevation1,
            settings.Elevation2
        );


        return true;
    }
    catch(error){

        surface.userData
            .agH26R4Loading =
            false;


        /*
         * The original terrain remains untouched and visible.
         */

        console.warn(
            "REAL OPENSIM TERRAIN TEXTURES V6C-H26-R4",
            regionName,
            "ORIGINAL TERRAIN FALLBACK",
            error
        );


        return false;
    }
}


/* END REAL OPENSIM TERRAIN TEXTURES V6C-H26-R4 */
async function ag3dApplyRealTerrainV1(
    region,
    surface,
    base,
    outline,
    cellsX,
    cellsY
){

    if(
        !region ||
        !String(
            region.RegionName ||
            ""
        ).trim()
    ){
        return false;
    }


    const safeCellsX =
        Math.max(
            1,
            Math.round(
                Number(
                    cellsX ||
                    1
                )
            )
        );


    const safeCellsY =
        Math.max(
            1,
            Math.round(
                Number(
                    cellsY ||
                    1
                )
            )
        );


    const segmentsX =
        Math.min(
            192,
            Math.max(
                64,
                safeCellsX * 64
            )
        );


    const segmentsY =
        Math.min(
            192,
            Math.max(
                64,
                safeCellsY * 64
            )
        );


    const columns =
        segmentsX + 1;


    const rows =
        segmentsY + 1;


    const requestUrl =
        endpoint('terrain') +
        "?region=" +
        encodeURIComponent(
            String(
                region.RegionName
            )
        ) +
        "&cols=" +
        encodeURIComponent(
            String(
                columns
            )
        ) +
        "&rows=" +
        encodeURIComponent(
            String(
                rows
            )
        ) +
        "&_=" +
        Date.now();


    try{

        const response =
            await fetch(
                requestUrl,
                {
                    cache:
                        "no-store",

                    credentials:
                        "same-origin"
                }
            );


        if(
            !response.ok
        ){
            return false;
        }


        const data =
            await response.json();


        if(
            !data ||
            data.ok !== true ||
            !Array.isArray(
                data.Heights
            )
        ){
            return false;
        }


        /*
         * Region may have been changed while terrain was loading.
         */

        if(
            selectedRegion !== region ||
            !surface ||
            surface.parent !== regionGroup
        ){
            return false;
        }


        const geometry =
            surface.geometry;


        if(
            !geometry ||
            !geometry.attributes ||
            !geometry.attributes.position
        ){
            return false;
        }


        const position =
            geometry.attributes.position;


        if(
            position.count !==
                (
                    columns *
                    rows
                ) ||
            data.Heights.length !==
                (
                    columns *
                    rows
                )
        ){
            console.warn(
                "3D terrain vertex count mismatch."
            );

            return false;
        }


        const waterHeight =
            Number(
                data.WaterHeight
            );


        /*
         * Current map representation:
         *
         * 256 OpenSim metres = 70 Three.js units.
         *
         * The rendered top surface is 96% of that width,
         * therefore use the matching physical vertical ratio.
         */

        const metresToScene =
            (
                70 *
                0.96
            ) /
            256;


        /*
         * The moving ocean's main surface is already at 2.08.
         *
         * A terrain sample equal to WaterHeight therefore lands
         * exactly at the ocean surface.
         */

        const sceneWaterY =
            2.08;


        for(
            let index = 0;
            index < position.count;
            index++
        ){

            const terrainMetres =
                Number(
                    data.Heights[
                        index
                    ]
                );


            const elevationFromWater =
                (
                    terrainMetres -
                    waterHeight
                ) *
                metresToScene;


            /*
             * PlaneGeometry is rotated -90 degrees around X.
             * Local Z therefore becomes vertical world Y.
             */

            position.setZ(
                index,
                elevationFromWater
            );
        }


        position.needsUpdate =
            true;


        geometry.computeVertexNormals();


        if(
            geometry.attributes.normal
        ){
            geometry.attributes.normal.needsUpdate =
                true;
        }


        /*
         * Put the zero-height reference of the terrain exactly
         * at the existing moving water plane.
         */

        surface.position.y =
            sceneWaterY;


        /*
         * Lower the old solid Stage-1 slab underneath the real
         * terrain so it cannot cover low terrain.
         */

        const minimumHeight =
            Number(
                data.MinHeight
            );


        const minimumSceneY =
            sceneWaterY +
            (
                (
                    minimumHeight -
                    waterHeight
                ) *
                metresToScene
            );


        if(
            base
        ){

            base.position.y =
                minimumSceneY -
                5.5;
        }


        if(
            outline &&
            base
        ){

            outline.position.copy(
                base.position
            );
        }


        /*
         * REAL TERRAIN V2
         *
         * The original Stage-1 box was only a placeholder.
         * Once genuine R32 terrain has loaded it must disappear.
         */

        if(
            base
        ){
            base.visible =
                false;
        }


        if(
            outline
        ){
            outline.visible =
                false;
        }


        surface.userData.realTerrain =
            true;


        surface.userData.waterHeight =
            waterHeight;


        surface.userData.minimumHeight =
            Number(
                data.MinHeight
            );


        surface.userData.maximumHeight =
            Number(
                data.MaxHeight
            );

        /*
         * =====================================================
         * REAL OPENSIM TERRAIN TEXTURE OVERLAY V6C-H26-R4
         *
         * Original terrain is already complete at this point.
         * Overlay starts asynchronously.
         * =====================================================
         */

        ag3dH26R4Install(
            region,
            surface,
            data
        ).catch(
            function(error){

                console.warn(
                    "REAL OPENSIM TERRAIN TEXTURES V6C-H26-R4",
                    "overlay startup failed",
                    error
                );
            }
        );


console.info(
            "REAL OPENSIM TERRAIN V1",
            region.RegionName,
            "water",
            waterHeight,
            "min",
            data.MinHeight,
            "max",
            data.MaxHeight
        );


        return true;
    }
    catch(error){

        console.warn(
            "Could not load real terrain.",
            error
        );

        return false;
    }
}

/* END REAL OPENSIM TERRAIN V1 */

/* ============================================================
   MULTI-REGION LIVE OPENSIM PRIMS V4
   LARGE REGION OBJECT LOD V4B
   REAL OPENSIM DYNAMIC HIGH-LOD MESH V6B

   All regions use the same proven coordinate system and
   quaternion conversion established with FARM SHOP.

       OpenSim X -> Three.js X
       OpenSim Z -> Three.js Y
       OpenSim Y -> Three.js -Z

   The backend supplies current individual SceneObjectParts.
   ============================================================ */

/* ============================================================
   REAL OPENSIM TEXTURED MESH MATERIALS V6C-H3

   OpenSim TextureEntry
   -> authenticated material endpoint
   -> original LL mesh material slot
   -> authenticated JPEG2000/PNG texture endpoint
   -> Three.js material array

   Existing V6B gold geometry remains the failure fallback.
   ============================================================ */

const ag3dV6cMaterialEntryCache =
    new Map();

const ag3dV6cTexturePromiseCache =
    new Map();


function ag3dV6cValidUuid(
    value
){

    return /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/.test(
        String(
            value ||
            ""
        ).trim().toLowerCase()
    );
}


const materialBatcher = new DG3DPipeline.MaterialBatcher(async entries => {
    const response = await fetch(endpoint('material'), {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: {'Content-Type':'application/json'}, body: JSON.stringify({entries})
    });
    if (!response.ok) throw new Error('Material HTTP ' + response.status);
    const data = await response.json();
    if (!data || data.ok !== true) throw new Error('Invalid material response.');
    return data.Results;
}, ag3dV6cMaterialEntryCache);
async function ag3dV6cDecodeTextureEntries(entries) {
    await materialBatcher.load(Array.isArray(entries) ? entries : []);
    return true;
}

/* ============================================================
   REAL OPENSIM BROWSER TEXTURE CACHE V6C-H5

   Authenticated texture PNGs are still fetched through the
   existing PHP endpoint.

   The returned image Blob is persisted in browser IndexedDB
   under the immutable OpenSim texture UUID.

   Result:
       first use  -> PHP / server cache
       later use  -> local browser IndexedDB

   Server authentication / no-store behaviour remains unchanged.
   ============================================================ */

const ag3dV6cTextureDbName =
    "Australia3DTextureCacheV1";

const ag3dV6cTextureDbStore =
    "textures";

let ag3dV6cTextureDbPromise =
    null;


function ag3dV6cOpenTextureDb(){

    if(
        !window.indexedDB
    ){

        return Promise.resolve(
            null
        );
    }

    if(ag3dV6cTextureDbPromise){

        return ag3dV6cTextureDbPromise;
    }

    ag3dV6cTextureDbPromise =
        new Promise(
            function(resolve){

                let request;

                try{

                    request =
                        window.indexedDB.open(
                            ag3dV6cTextureDbName,
                            1
                        );
                }
                catch(error){

                    resolve(
                        null
                    );

                    return;
                }

                request.onupgradeneeded =
                    function(){

                        const db =
                            request.result;

                        if(
                            !db.objectStoreNames.contains(
                                ag3dV6cTextureDbStore
                            )
                        ){

                            db.createObjectStore(
                                ag3dV6cTextureDbStore
                            );
                        }
                    };

                request.onsuccess =
                    function(){

                        resolve(
                            request.result
                        );
                    };

                request.onerror =
                    function(){

                        resolve(
                            null
                        );
                    };

                request.onblocked =
                    function(){

                        console.warn(
                            "REAL OPENSIM BROWSER TEXTURE CACHE V6C-H5 - database blocked"
                        );
                    };
            }
        );

    return ag3dV6cTextureDbPromise;
}


async function ag3dV6cGetCachedTextureBlob(
    uuid
){

    const db =
        await ag3dV6cOpenTextureDb();

    if(!db){

        return null;
    }

    return await new Promise(
        function(resolve){

            try{

                const transaction =
                    db.transaction(
                        ag3dV6cTextureDbStore,
                        "readonly"
                    );

                const request =
                    transaction
                        .objectStore(
                            ag3dV6cTextureDbStore
                        )
                        .get(
                            uuid
                        );

                request.onsuccess =
                    function(){

                        const value =
                            request.result;

                        if(
                            value &&
                            value.blob instanceof Blob &&
                            value.blob.size > 8
                        ){

                            resolve(
                                value.blob
                            );
                        }
                        else{

                            resolve(
                                null
                            );
                        }
                    };

                request.onerror =
                    function(){

                        resolve(
                            null
                        );
                    };
            }
            catch(error){

                resolve(
                    null
                );
            }
        }
    );
}


async function ag3dV6cStoreTextureBlob(
    uuid,
    blob
){

    const db =
        await ag3dV6cOpenTextureDb();

    if(
        !db ||
        !(blob instanceof Blob) ||
        blob.size < 8
    ){

        return false;
    }

    return await new Promise(
        function(resolve){

            try{

                const transaction =
                    db.transaction(
                        ag3dV6cTextureDbStore,
                        "readwrite"
                    );

                transaction
                    .objectStore(
                        ag3dV6cTextureDbStore
                    )
                    .put(
                        {
                            uuid:
                                uuid,

                            blob:
                                blob,

                            stored:
                                Date.now()
                        },
                        uuid
                    );

                transaction.oncomplete =
                    function(){

                        resolve(
                            true
                        );
                    };

                transaction.onerror =
                    function(){

                        resolve(
                            false
                        );
                    };

                transaction.onabort =
                    function(){

                        resolve(
                            false
                        );
                    };
            }
            catch(error){

                resolve(
                    false
                );
            }
        }
    );
}


async function ag3dV6cDeleteCachedTextureBlob(
    uuid
){

    const db =
        await ag3dV6cOpenTextureDb();

    if(!db){

        return;
    }

    try{

        const transaction =
            db.transaction(
                ag3dV6cTextureDbStore,
                "readwrite"
            );

        transaction
            .objectStore(
                ag3dV6cTextureDbStore
            )
            .delete(
                uuid
            );
    }
    catch(error){
    }
}


function ag3dV6cTextureFromBlob(
    blob
){

    return new Promise(
        function(resolve,reject){

            const objectUrl =
                URL.createObjectURL(
                    blob
                );

            const loader =
                new THREE.TextureLoader();

            loader.load(
                objectUrl,

                function(texture){

                    URL.revokeObjectURL(
                        objectUrl
                    );

                    texture.wrapS =
                        THREE.RepeatWrapping;

                    texture.wrapT =
                        THREE.RepeatWrapping;

                    if(
                        typeof THREE.sRGBEncoding !==
                        "undefined"
                    ){

                        texture.encoding =
                            THREE.sRGBEncoding;
                    }

                    texture.generateMipmaps =
                        true;

                    texture.minFilter =
                        THREE.LinearMipmapLinearFilter;

                    texture.magFilter =
                        THREE.LinearFilter;

                    texture.needsUpdate =
                        true;

                    resolve(
                        texture
                    );
                },

                undefined,

                function(error){

                    URL.revokeObjectURL(
                        objectUrl
                    );

                    reject(
                        error ||
                        new Error(
                            "Texture Blob could not be decoded."
                        )
                    );
                }
            );
        }
    );
}


/* ============================================================
   REAL OPENSIM TEXTURE RECOVERY V6C-H9

   Large regions can request hundreds of independent OpenSim
   texture assets at once.

   H9 prevents the browser from hammering the PHP/JPEG2000
   conversion path with an uncontrolled number of simultaneous
   texture requests.

   Behaviour:
       - maximum 6 active texture HTTP requests
       - maximum 3 attempts per texture UUID
       - short increasing delay between failed attempts
       - returned data must have a valid PNG signature
       - only valid PNGs continue into the H5 browser cache
       - final failure is logged once after all attempts

   Existing H5 IndexedDB caching remains unchanged.
   ============================================================ */

const ag3dV6cTextureFetchLimitH9 =
    2;

let ag3dV6cTextureFetchActiveH9 =
    0;

const ag3dV6cTextureFetchQueueH9 =
    [];


function ag3dV6cPumpTextureFetchQueueH9(){

    while(
        ag3dV6cTextureFetchActiveH9 <
            ag3dV6cTextureFetchLimitH9 &&
        ag3dV6cTextureFetchQueueH9.length >
            0
    ){

        const resolve =
            ag3dV6cTextureFetchQueueH9.shift();

        ag3dV6cTextureFetchActiveH9++;

        resolve();
    }
}


function ag3dV6cAcquireTextureFetchSlotH9(){

    return new Promise(
        function(resolve){

            ag3dV6cTextureFetchQueueH9.push(
                resolve
            );

            ag3dV6cPumpTextureFetchQueueH9();
        }
    );
}


function ag3dV6cReleaseTextureFetchSlotH9(){

    ag3dV6cTextureFetchActiveH9 =
        Math.max(
            0,
            ag3dV6cTextureFetchActiveH9 - 1
        );

    ag3dV6cPumpTextureFetchQueueH9();
}


function ag3dV6cTextureRetryDelayH9(
    milliseconds
){

    return new Promise(
        function(resolve){

            window.setTimeout(
                resolve,
                milliseconds
            );
        }
    );
}


async function ag3dV6cValidatePngBlobH9(
    blob
){

    if(
        !(blob instanceof Blob) ||
        blob.size < 8
    ){

        return false;
    }

    let signature;

    try{

        signature =
            new Uint8Array(
                await blob
                    .slice(
                        0,
                        8
                    )
                    .arrayBuffer()
            );
    }
    catch(error){

        return false;
    }

    return (
        signature.length >= 8 &&
        signature[0] === 137 &&
        signature[1] === 80 &&
        signature[2] === 78 &&
        signature[3] === 71 &&
        signature[4] === 13 &&
        signature[5] === 10 &&
        signature[6] === 26 &&
        signature[7] === 10
    );
}


async function ag3dV6cFetchTextureBlob(
    textureUuid
){

    const uuid =
        String(
            textureUuid ||
            ""
        ).trim().toLowerCase();

    if(!ag3dV6cValidUuid(uuid)){

        throw new Error(
            "Invalid texture UUID."
        );
    }

    const maximumAttempts =
        2;

    let lastError =
        null;

    for(
        let attempt = 1;
        attempt <= maximumAttempts;
        attempt++
    ){

        let response =
            null;

        let blob =
            null;

        await ag3dV6cAcquireTextureFetchSlotH9();
        const controller=new AbortController();
        const requestTimeout=setTimeout(()=>controller.abort(),20000);

        try{

            response =
                await fetch(
                    endpoint('texture') +
                    "?uuid=" +
                    encodeURIComponent(
                        uuid
                    ) + "&size=256",
                    {
                        signal:controller.signal,
                        cache:
                            "no-store",

                        credentials:
                            "same-origin"
                    }
                );

            textureDiagnostics.set(uuid,{status:response.status,cache:response.headers.get('X-Map3D-Preview')});
            if(!response.ok){

                throw new Error(
                    "Texture HTTP " +
                    response.status
                );
            }

            blob =
                await response.blob();
        }
        catch(error){

            lastError =
                error instanceof Error
                    ? error
                    : new Error(
                        String(
                            error ||
                            "Texture request failed."
                        )
                    );
        }
        finally{

            clearTimeout(requestTimeout);
            ag3dV6cReleaseTextureFetchSlotH9();
        }

        /*
         * Authentication failures are not transient.
         */

        if(
            response &&
            (
                response.status === 401 ||
                response.status === 403 || response.status === 404 || response.status === 422 || response.redirected
            )
        ){

            break;
        }

        if(blob){

            const validPng =
                await ag3dV6cValidatePngBlobH9(
                    blob
                );

            if(validPng){

                if(attempt > 1){

                    console.info(
                        "REAL OPENSIM TEXTURE RECOVERY V6C-H9",
                        uuid,
                        "recovered on attempt",
                        attempt
                    );
                }

                return blob;
            }

            lastError =
                new Error(
                    "Texture response was not a valid PNG."
                );
        }

        if(attempt < maximumAttempts){

            /*
             * 350ms after attempt 1
             * 900ms after attempt 2
             */

            const delay =
                attempt === 1
                    ? 350
                    : 900;

            await ag3dV6cTextureRetryDelayH9(
                delay
            );
        }
    }

    console.warn(
        "REAL OPENSIM TEXTURE RECOVERY V6C-H9 - final failure",
        uuid,
        lastError
            ? lastError.message
            : "unknown error"
    );

    throw (
        lastError ||
        new Error(
            "Texture failed after retries."
        )
    );
}

/* END REAL OPENSIM TEXTURE RECOVERY V6C-H9 */

function ag3dV6cLoadBaseTexture(
    textureUuid
){

    const uuid =
        String(
            textureUuid ||
            ""
        ).trim().toLowerCase();

    if(!ag3dV6cValidUuid(uuid)){

        return Promise.reject(
            new Error(
                "Invalid texture UUID."
            )
        );
    }

    if(
        ag3dV6cTexturePromiseCache.has(
            uuid
        )
    ){

        return ag3dV6cTexturePromiseCache.get(
            uuid
        );
    }

    const cacheKey = "preview256-v2:" + uuid;
    const promise =
        (
            async function(){

                /*
                 * ------------------------------------------------
                 * H5:
                 * Try persistent browser storage first.
                 * ------------------------------------------------
                 */

                let blob =
                    await ag3dV6cGetCachedTextureBlob(
                        cacheKey
                    );

                if(blob){
                    window.dg3dMetrics?.cache('previewBrowser');

                    try{

                        return await ag3dV6cTextureFromBlob(
                            blob
                        );
                    }
                    catch(error){

                        await ag3dV6cDeleteCachedTextureBlob(
                            cacheKey
                        );

                        blob =
                            null;
                    }
                }

                /*
                 * ------------------------------------------------
                 * Not in the browser yet.
                 *
                 * Fetch once through the authenticated endpoint,
                 * then persist the returned PNG Blob.
                 * ------------------------------------------------
                 */

                blob =
                    await ag3dV6cFetchTextureBlob(
                        uuid
                    );

                /*
                 * IndexedDB quota failure is deliberately harmless.
                 * The live texture still renders.
                 */

                try{

                    await ag3dV6cStoreTextureBlob(
                        cacheKey,
                        blob
                    );
                }
                catch(error){
                }

                return await ag3dV6cTextureFromBlob(
                    blob
                );
            }
        )();

    ag3dV6cTexturePromiseCache.set(
        uuid,
        promise
    );

    // Remember failure for this build; another face must not restart three retries.
    promise.catch(function(){});
    return promise;
}

/* END REAL OPENSIM BROWSER TEXTURE CACHE V6C-H5 */

/* ============================================================
   REAL OPENSIM TEXTURE LOAD PERFORMANCE V6C-H4

   Bounded background texture warming.

   Texture decoding/loading is allowed to overlap with the
   existing V6B/H3 mesh workers instead of waiting until each
   individual material group asks for the image.
   ============================================================ */

/* ============================================================
   REAL OPENSIM GROUND COVER TERRAIN ALIGNMENT V6C-H7

   Some thin dirt / grass / road-style mesh surfaces can have
   their real mesh bottom slightly beneath the live terrain.

   This correction applies ONLY to:
       - real mesh geometry
       - wide/thin geometry
       - approximately horizontal geometry
       - geometry within 2 metres below the terrain

   It does NOT modify the OpenSim coordinate transform.
   It does NOT modify the quaternion conversion.
   It does NOT modify terrain geometry.

   Only the final rendered instance matrix of a qualifying
   near-ground cover mesh may receive a small upward correction.
   ============================================================ */

const ag3dV6cGroundCoverRaycaster =
    new THREE.Raycaster();

const ag3dV6cGroundCoverRayDown =
    new THREE.Vector3(
        0,
        -1,
        0
    );


function ag3dV6cGroundCoverMatrix(
    sourceMatrix,
    geometry,
    terrainSurface,
    metresToScene
){

    if(
        !sourceMatrix ||
        !geometry ||
        !terrainSurface ||
        !Number.isFinite(
            Number(
                metresToScene
            )
        ) ||
        Number(
            metresToScene
        ) <= 0
    ){

        return sourceMatrix;
    }

    const scenePerMetre =
        Number(
            metresToScene
        );

    if(!geometry.boundingBox){

        geometry.computeBoundingBox();
    }

    const box =
        geometry.boundingBox;

    if(!box){

        return sourceMatrix;
    }

    const position =
        new THREE.Vector3();

    const rotation =
        new THREE.Quaternion();

    const scale =
        new THREE.Vector3();

    sourceMatrix.decompose(
        position,
        rotation,
        scale
    );

    const boxSize =
        new THREE.Vector3();

    box.getSize(
        boxSize
    );

    /*
     * Real dimensions after instance scaling.
     *
     * Local Y is OpenSim Z / vertical in the locked basis.
     */

    const width =
        Math.abs(
            boxSize.x *
            scale.x
        );

    const height =
        Math.abs(
            boxSize.y *
            scale.y
        );

    const depth =
        Math.abs(
            boxSize.z *
            scale.z
        );

    const longestHorizontal =
        Math.max(
            width,
            depth
        );

    const shortestHorizontal =
        Math.min(
            width,
            depth
        );

    /*
     * Do not classify tiny objects as ground cover.
     */

    if(
        longestHorizontal <
        (
            1.0 *
            scenePerMetre
        )
    ){

        return sourceMatrix;
    }

    /*
     * Must be genuinely thin relative to its footprint.
     *
     * Absolute allowance:
     *     <= 0.35 metres
     *
     * Relative allowance:
     *     <= 25% of the short horizontal dimension
     */

    const maximumGroundThickness =
        Math.max(
            0.35 *
            scenePerMetre,

            shortestHorizontal *
            0.25
        );

    if(
        height >
        maximumGroundThickness
    ){

        return sourceMatrix;
    }

    /*
     * Make sure the mesh's local vertical axis is still
     * approximately world vertical. This excludes walls and
     * other thin meshes that are standing upright.
     */

    const localUp =
        new THREE.Vector3(
            0,
            1,
            0
        );

    localUp.applyQuaternion(
        rotation
    );

    if(
        Math.abs(
            localUp.y
        ) <
        0.85
    ){

        return sourceMatrix;
    }

    /*
     * Test the actual bottom-centre of the mesh.
     */

    const bottomLocal =
        new THREE.Vector3(
            (
                box.min.x +
                box.max.x
            ) / 2,

            box.min.y,

            (
                box.min.z +
                box.max.z
            ) / 2
        );

    const bottomWorld =
        bottomLocal.clone();

    bottomWorld.applyMatrix4(
        sourceMatrix
    );

    /*
     * Terrain already exists before objects are loaded.
     * Refresh its world matrix once for H7.
     */

    if(
        !terrainSurface.userData
    ){

        terrainSurface.userData =
            {};
    }

    if(
        terrainSurface.userData
            .agGroundCoverTerrainMatrixH7 !==
        true
    ){

        terrainSurface.updateMatrixWorld(
            true
        );

        terrainSurface.userData
            .agGroundCoverTerrainMatrixH7 =
            true;
    }

    /*
     * Cast vertically down to the real terrain.
     */

    const rayOrigin =
        new THREE.Vector3(
            bottomWorld.x,

            bottomWorld.y +
            (
                1000 *
                scenePerMetre
            ),

            bottomWorld.z
        );

    ag3dV6cGroundCoverRaycaster.set(
        rayOrigin,
        ag3dV6cGroundCoverRayDown
    );

    ag3dV6cGroundCoverRaycaster.near =
        0;

    ag3dV6cGroundCoverRaycaster.far =
        2000 *
        scenePerMetre;

    let intersections =
        [];

    try{

        intersections =
            ag3dV6cGroundCoverRaycaster
                .intersectObject(
                    terrainSurface,
                    false
                );
    }
    catch(error){

        return sourceMatrix;
    }

    if(
        !Array.isArray(
            intersections
        ) ||
        intersections.length < 1
    ){

        return sourceMatrix;
    }

    const terrainY =
        Number(
            intersections[0]
                .point
                .y
        );

    if(
        !Number.isFinite(
            terrainY
        )
    ){

        return sourceMatrix;
    }

    /*
     * Leave roughly 8 cm between the mesh bottom and terrain.
     */

    const clearance =
        0.08 *
        scenePerMetre;

    const requiredLift =
        (
            terrainY +
            clearance
        ) -
        bottomWorld.y;

    /*
     * Already clear of the terrain.
     */

    if(
        requiredLift <= 0
    ){

        return sourceMatrix;
    }

    /*
     * Do not rescue intentionally underground geometry.
     *
     * H7 only corrects near-ground cover surfaces buried by
     * at most 2 metres.
     */

    const maximumLift =
        2.0 *
        scenePerMetre;

    if(
        requiredLift >
        maximumLift
    ){

        return sourceMatrix;
    }

    const correctedPosition =
        position.clone();

    correctedPosition.y +=
        requiredLift;

    const correctedMatrix =
        new THREE.Matrix4();

    correctedMatrix.compose(
        correctedPosition,
        rotation,
        scale
    );

    terrainSurface.userData
        .agGroundCoverLiftedH7 =
        Number(
            terrainSurface.userData
                .agGroundCoverLiftedH7 ||
            0
        ) +
        1;

    return correctedMatrix;
}

/* END REAL OPENSIM GROUND COVER TERRAIN ALIGNMENT V6C-H7 */

/* ============================================================
   REAL OPENSIM GROUND COVER TOP ALIGNMENT V6C-H8

   H7 used the mesh bottom.

   H8 instead checks the TOP surface of a broad, low,
   approximately horizontal TEXTURED mesh.

   A mesh is changed only when its top is genuinely below the
   live terrain.

   Terrain ray testing is recursive so a real terrain child
   underneath the requestSurface wrapper can also be found.

   No OpenSim source position is changed.
   No coordinate conversion is changed.
   No quaternion conversion is changed.
   ============================================================ */

const ag3dV6cGroundCoverRaycasterH8 =
    new THREE.Raycaster();

const ag3dV6cGroundCoverRayDownH8 =
    new THREE.Vector3(
        0,
        -1,
        0
    );


function ag3dV6cGroundCoverTopMatrixH8(
    sourceMatrix,
    geometry,
    terrainSurface,
    metresToScene,
    isTextured
){

    if(
        isTextured !== true ||
        !sourceMatrix ||
        !geometry ||
        !terrainSurface
    ){

        return sourceMatrix;
    }

    const scenePerMetre =
        Number(
            metresToScene
        );

    if(
        !Number.isFinite(
            scenePerMetre
        ) ||
        scenePerMetre <= 0
    ){

        return sourceMatrix;
    }

    if(!geometry.boundingBox){

        geometry.computeBoundingBox();
    }

    const box =
        geometry.boundingBox;

    if(!box){

        return sourceMatrix;
    }

    const position =
        new THREE.Vector3();

    const rotation =
        new THREE.Quaternion();

    const scale =
        new THREE.Vector3();

    sourceMatrix.decompose(
        position,
        rotation,
        scale
    );

    const boxSize =
        new THREE.Vector3();

    box.getSize(
        boxSize
    );

    const width =
        Math.abs(
            boxSize.x *
            scale.x
        );

    const height =
        Math.abs(
            boxSize.y *
            scale.y
        );

    const depth =
        Math.abs(
            boxSize.z *
            scale.z
        );

    /*
     * Ground-cover candidates must have a useful footprint.
     */

    if(
        width <
        (
            1.0 *
            scenePerMetre
        ) ||
        depth <
        (
            1.0 *
            scenePerMetre
        )
    ){

        return sourceMatrix;
    }

    const shortHorizontal =
        Math.min(
            width,
            depth
        );

    /*
     * Allow considerably more vertical thickness than H7,
     * but still reject tall buildings / trees / walls.
     */

    const maximumHeight =
        Math.min(
            2.0 *
            scenePerMetre,

            Math.max(
                0.50 *
                scenePerMetre,

                shortHorizontal *
                0.50
            )
        );

    if(
        height >
        maximumHeight
    ){

        return sourceMatrix;
    }

    /*
     * Must still be approximately horizontal.
     */

    const localUp =
        new THREE.Vector3(
            0,
            1,
            0
        );

    localUp.applyQuaternion(
        rotation
    );

    if(
        Math.abs(
            localUp.y
        ) <
        0.78
    ){

        return sourceMatrix;
    }

    /*
     * Use the TOP centre rather than the bottom.
     *
     * If this point is already above terrain, leave the
     * OpenSim mesh exactly where it is.
     */

    const topLocal =
        new THREE.Vector3(
            (
                box.min.x +
                box.max.x
            ) / 2,

            box.max.y,

            (
                box.min.z +
                box.max.z
            ) / 2
        );

    const topWorld =
        topLocal.clone();

    topWorld.applyMatrix4(
        sourceMatrix
    );

    /*
     * Ensure parent + terrain world matrices are current.
     */

    try{

        if(terrainSurface.parent){

            terrainSurface.parent.updateMatrixWorld(
                true
            );
        }

        terrainSurface.updateMatrixWorld(
            true
        );
    }
    catch(error){
    }

    const rayOrigin =
        new THREE.Vector3(
            topWorld.x,

            topWorld.y +
            (
                1000 *
                scenePerMetre
            ),

            topWorld.z
        );

    ag3dV6cGroundCoverRaycasterH8.set(
        rayOrigin,
        ag3dV6cGroundCoverRayDownH8
    );

    ag3dV6cGroundCoverRaycasterH8.near =
        0;

    ag3dV6cGroundCoverRaycasterH8.far =
        2000 *
        scenePerMetre;

    let intersections =
        [];

    try{

        /*
         * H8 IMPORTANT:
         *
         * recursive = true
         *
         * This also catches real terrain geometry if it is
         * stored below the supplied requestSurface object.
         */

        intersections =
            ag3dV6cGroundCoverRaycasterH8
                .intersectObject(
                    terrainSurface,
                    true
                );
    }
    catch(error){

        return sourceMatrix;
    }

    if(
        !Array.isArray(
            intersections
        ) ||
        intersections.length < 1
    ){

        return sourceMatrix;
    }

    const terrainY =
        Number(
            intersections[0]
                .point
                .y
        );

    if(
        !Number.isFinite(
            terrainY
        )
    ){

        return sourceMatrix;
    }

    /*
     * Put the TOP of the dirt / grass mesh 6 cm above terrain.
     */

    const clearance =
        0.06 *
        scenePerMetre;

    const requiredLift =
        (
            terrainY +
            clearance
        ) -
        topWorld.y;

    /*
     * Already visible above terrain.
     */

    if(
        requiredLift <= 0
    ){

        return sourceMatrix;
    }

    /*
     * Only rescue near-ground objects.
     * Anything buried more than four metres remains untouched.
     */

    const maximumLift =
        4.0 *
        scenePerMetre;

    if(
        requiredLift >
        maximumLift
    ){

        return sourceMatrix;
    }

    const correctedPosition =
        position.clone();

    correctedPosition.y +=
        requiredLift;

    const correctedMatrix =
        new THREE.Matrix4();

    correctedMatrix.compose(
        correctedPosition,
        rotation,
        scale
    );

    if(!terrainSurface.userData){

        terrainSurface.userData =
            {};
    }

    terrainSurface.userData
        .agGroundCoverTopLiftedH8 =
        Number(
            terrainSurface.userData
                .agGroundCoverTopLiftedH8 ||
            0
        ) +
        1;

    return correctedMatrix;
}

/* END REAL OPENSIM GROUND COVER TOP ALIGNMENT V6C-H8 */


function ag3dV6cNumber(
    value,
    fallback
){

    const number =
        Number(
            value
        );

    return Number.isFinite(number)
        ? number
        : fallback;
}


const materialPropertiesCache = new Map();
const materialPropertiesBatcher = new DG3DPipeline.MaterialBatcher(async ids => {
    const response=await fetch(endpoint('material-properties'),{method:'POST',credentials:'same-origin',
        headers:{'Content-Type':'application/json'},body:JSON.stringify({ids})});
    if(!response.ok)throw Error('Material properties HTTP '+response.status);
    const data=await response.json(); if(data.ok!==true)throw Error('Invalid material properties');
    return data.Results;
},materialPropertiesCache,2,16);
const imageAlphaCache=new WeakMap();
function map3dImageAlpha(texture){
    const image=texture?.image; if(!image)return {hasAlpha:false,zero:0,partial:0};
    if(imageAlphaCache.has(image))return imageAlphaCache.get(image);
    const canvas=document.createElement('canvas');
    canvas.width=Math.min(512,image.naturalWidth||image.width);canvas.height=Math.min(512,image.naturalHeight||image.height);
    const context=canvas.getContext('2d',{willReadFrequently:true});
    if(!context)return {hasAlpha:false,unavailable:true};
    context.drawImage(image,0,0,canvas.width,canvas.height);
    const pixels=context.getImageData(0,0,canvas.width,canvas.height).data;
    let zero=0,partial=0;for(let i=3;i<pixels.length;i+=4){if(pixels[i]===0)zero++;else if(pixels[i]<255)partial++;}
    const result={hasAlpha:zero+partial>0,zero,partial,width:canvas.width,height:canvas.height};
    imageAlphaCache.set(image,result);return result;
}
function map3dAlphaSettings(face,imageAlpha,metadata){
    const faceOpacity=ag3dV6cFaceOpacityH16(face);
    const factor=metadata?.baseColorFactor||[1,1,1,1];
    const opacity=faceOpacity*Math.max(0,Math.min(1,Number(factor[3])));
    const mode=metadata?.alphaMode||'BLEND';
    const transparent=opacity<0.999||(imageAlpha.hasAlpha&&mode==='BLEND');
    return {opacity,transparent,depthWrite:!transparent,
        alphaTest:mode==='MASK'?Math.max(0,Math.min(1,Number(metadata.alphaCutoff)||0)):0,
        ignoreImageAlpha:mode==='OPAQUE'||mode==='EMISSIVE',faceOpacity,mode,factor};
}
async function ag3dV6cCreateFaceMaterial(face){
    if(!face||face.Valid!==true)return null;
    const textureUuid=String(face.TextureID||'').trim().toLowerCase();
    const materialUuid=String(face.MaterialID||'').trim().toLowerCase();
    const zero='00000000-0000-0000-0000-000000000000';
    let metadata=null,textureError=null;
    const generation=map3dGeneration;
    const properties=ag3dV6cValidUuid(materialUuid)&&materialUuid!==zero
        ? materialPropertiesBatcher.load([materialUuid]).then(results=>results[0]).catch(error=>{console.warn('3D material properties unavailable',materialUuid,String(error));return null;})
        : Promise.resolve(null);
    let baseTexture=null;
    if(ag3dV6cValidUuid(textureUuid)&&textureUuid!==zero){
        try {baseTexture=await ag3dV6cLoadBaseTexture(textureUuid);}catch(error){textureError=String(error);}
    }
    metadata=await properties;
    const textureKey=textureUuid;
    const existing=generation===map3dGeneration?map3dTransformedTextures.get(textureKey):null;
    const texture=existing||baseTexture;
    if(texture&&!existing){
        // Map detail needs a bounded GPU image, independently of the reusable 256px download.
        const limit=cfg.meshLod==='high'?256:128;
        if(texture.image.width>limit||texture.image.height>limit){
            const source=texture.image,canvas=document.createElement('canvas');
            const ratio=limit/Math.max(source.width,source.height);
            canvas.width=Math.max(1,Math.round(source.width*ratio));canvas.height=Math.max(1,Math.round(source.height*ratio));
            const context=canvas.getContext('2d');
            if(context){context.drawImage(source,0,0,canvas.width,canvas.height);texture.image=canvas;}
        }
        if(generation===map3dGeneration){map3dTransformedTextures.set(textureKey,texture);map3dTextureBytes+=(texture.image.width||256)*(texture.image.height||256)*4*4/3;}
        texture.wrapS=texture.wrapT=THREE.RepeatWrapping;
        if(THREE.sRGBEncoding!==undefined)texture.encoding=THREE.sRGBEncoding;
        texture.needsUpdate=true;
    }
    const imageAlpha=map3dImageAlpha(baseTexture),alpha=map3dAlphaSettings(face,imageAlpha,metadata);
    const rgba=face.RGBA||{},tint=key=>Math.max(0,Math.min(1,ag3dV6cNumber(rgba[key],1)));
    const options={map:texture,color:new THREE.Color(tint('R')*alpha.factor[0],tint('G')*alpha.factor[1],tint('B')*alpha.factor[2]),
        side:THREE.DoubleSide,opacity:alpha.opacity,transparent:alpha.transparent,alphaTest:alpha.alphaTest,depthWrite:alpha.depthWrite};
    const material=face.Fullbright===true?new THREE.MeshBasicMaterial(options):new THREE.MeshPhongMaterial({...options,shininess:String(face.Shiny||'').toLowerCase()==='none'?18:70});
    if(texture){
        // UVs belong to the face material; the GPU image is shared across all faces.
        const uvMatrix=new THREE.Matrix3().setUvTransform(ag3dV6cNumber(face.OffsetU,0),ag3dV6cNumber(face.OffsetV,0),
            ag3dV6cNumber(face.RepeatU,1),ag3dV6cNumber(face.RepeatV,1),ag3dV6cNumber(face.Rotation,0),0.5,0.5);
        material.onBeforeCompile=shader=>{
            shader.uniforms.map3dFaceUv={value:uvMatrix};
            shader.vertexShader=shader.vertexShader.replace('#include <common>','#include <common>\nuniform mat3 map3dFaceUv;')
                .replace('#include <uv_vertex>','#include <uv_vertex>\n#ifdef USE_UV\nvUv = (map3dFaceUv * vec3(uv, 1.0)).xy;\n#endif');
            if(alpha.ignoreImageAlpha)shader.fragmentShader=shader.fragmentShader.replace('#include <map_fragment>','#include <map_fragment>\n diffuseColor.a = opacity;');
        };
        material.customProgramCacheKey=()=> 'map3d-face-uv-v2-'+alpha.ignoreImageAlpha;
    }
    material.userData={map3dShared:true,openSimTextureUuid:textureUuid,openSimMaterialId:materialUuid,
        openSimFace:face,openSimGlow:ag3dV6cNumber(face.Glow,0),imageAlpha,alphaMode:alpha.mode,materialMetadata:metadata,textureError};
    return material;
}
function ag3dV6cFaceForSlot(
    decodedEntry,
    slot
){

    if(
        !decodedEntry ||
        !Array.isArray(
            decodedEntry.Faces
        )
    ){

        return null;
    }

    const found =
        decodedEntry.Faces.find(
            function(face){

                return Number(
                    face &&
                    face.Slot
                ) === slot;
            }
        );

    if(found){

        return found;
    }

    if(
        decodedEntry.Default &&
        decodedEntry.Default.Valid === true
    ){

        return decodedEntry.Default;
    }

    return null;
}


function ag3dV6cApplyGeometryGroups(
    geometry,
    meshData
){

    if(
        !geometry ||
        !meshData ||
        !Array.isArray(
            meshData.Submeshes
        ) ||
        meshData.Submeshes.length < 1
    ){

        return false;
    }

    geometry.clearGroups();

    for(
        let index = 0;
        index < meshData.Submeshes.length;
        index++
    ){

        const submesh =
            meshData.Submeshes[index];

        const start =
            Number(
                submesh.IndexStart
            );

        const count =
            Number(
                submesh.IndexCount
            );

        if(
            !Number.isInteger(start) ||
            !Number.isInteger(count) ||
            start < 0 ||
            count < 0 ||
            start + count > geometry.index.count
        ){

            geometry.clearGroups();

            return false;
        }

        // Native prims retain empty face slots. Keep material indexes stable.
        if (count === 0) continue;
        geometry.addGroup(
            start,
            count,
            index
        );
    }

    return geometry.groups.length > 0;
}


/*
 * ============================================================
 * FAST SHARED MATERIALS + CLASSIC TRANSPARENT PANELS V6C-H13-R2
 *
 * PERFORMANCE
 * -----------
 * H3 can encounter the same effective OpenSim material face
 * many times. Previously each use could create another cloned
 * transformed THREE.Texture and another GPU texture upload.
 *
 * H13-R2 creates one SOURCE material for each unique effective
 * face definition. Later uses clone only the lightweight
 * material while sharing the same transformed texture object.
 *
 * CLASSIC WINDOWS / PANELS
 * ------------------------
 * H10 remains untouched for its proven broad/thin ground class.
 *
 * H13-R2 additionally reconstructs thin NON-MESH classic boxes
 * such as:
 *
 *     windows
 *     glass panels
 *     doors
 *     signs
 *     thin wall panels
 *
 * Sculpt objects are not converted by this patch.
 *
 * ============================================================
 */

const ag3dV6cSharedFaceMaterialCacheH13R2 =
    new Map();


function ag3dV6cStableFaceSignatureH13R2(
    face
){

    if(!face){
        return "";
    }

    function normalize(
        value
    ){

        if(Array.isArray(value)){

            return value.map(
                normalize
            );
        }

        if(
            value &&
            typeof value === "object"
        ){

            const result =
                {};

            Object.keys(value).filter(key => key !== 'Slot' && key !== 'Valid')
                .sort()
                .forEach(
                    function(key){

                        result[key] =
                            normalize(
                                value[key]
                            );
                    }
                );

            return result;
        }

        return value;
    }

    try{

        return JSON.stringify(
            normalize(
                face
            )
        );
    }
    catch(error){

        return "";
    }
}


/*
 * ============================================================
 * REAL OPENSIM PARTIAL CLASSIC FACES + REAL ALPHA V6C-H15
 *
 * Detect actual transparency in the decoded PNG image rather
 * than assuming TextureEntry face alpha tells the whole story.
 *
 * This is performed once per THREE.Texture and then cached.
 * ============================================================
 */


function ag3dV6cNormaliseFaceAlphaH16(
    value,
    fallback
){

    let number =
        Number(
            value
        );

    if(!Number.isFinite(number)){

        return fallback;
    }

    /*
     * Support both normalized 0..1 and byte 0..255 forms.
     */

    if(
        number > 1 &&
        number <= 255
    ){

        number /=
            255;
    }

    return Math.max(
        0,
        Math.min(
            1,
            number
        )
    );
}


function ag3dV6cFaceOpacityH16(
    face
){

    if(!face){
        return 1;
    }

    const directCandidates =
        [
            face.Alpha,
            face.alpha,
            face.Opacity,
            face.opacity,
            face.ColorA,
            face.colorA
        ];

    for(
        const candidate of
            directCandidates
    ){

        if(
            candidate !== undefined &&
            candidate !== null &&
            candidate !== ""
        ){

            const alpha =
                ag3dV6cNormaliseFaceAlphaH16(
                    candidate,
                    NaN
                );

            if(Number.isFinite(alpha)){

                return alpha;
            }
        }
    }

    const rgbaCandidates =
        [
            face.RGBA,
            face.rgba,
            face.Color,
            face.color,
            face.Tint,
            face.tint
        ];

    for(
        const rgba of
            rgbaCandidates
    ){

        if(
            rgba === undefined ||
            rgba === null
        ){

            continue;
        }

        /*
         * Array:
         *
         * [R,G,B,A]
         */

        if(
            Array.isArray(rgba) &&
            rgba.length >= 4
        ){

            const alpha =
                ag3dV6cNormaliseFaceAlphaH16(
                    rgba[3],
                    NaN
                );

            if(Number.isFinite(alpha)){

                return alpha;
            }
        }

        /*
         * Object:
         *
         * { R:1,G:1,B:1,A:0.3 }
         */

        if(
            typeof rgba === "object"
        ){

            const objectAlpha =
                (
                    rgba.A !== undefined
                        ? rgba.A
                        : rgba.a !== undefined
                            ? rgba.a
                            : rgba.Alpha !== undefined
                                ? rgba.Alpha
                                : rgba.alpha
                );

            if(
                objectAlpha !== undefined
            ){

                const alpha =
                    ag3dV6cNormaliseFaceAlphaH16(
                        objectAlpha,
                        NaN
                    );

                if(Number.isFinite(alpha)){

                    return alpha;
                }
            }
        }

        /*
         * Hex RGBA:
         *
         * #RRGGBBAA
         */

        if(
            typeof rgba === "string"
        ){

            const text =
                rgba.trim();

            const hexMatch =
                text.match(
                    /^#?([0-9a-f]{8})$/i
                );

            if(hexMatch){

                return (
                    parseInt(
                        hexMatch[1].slice(
                            6,
                            8
                        ),
                        16
                    ) /
                    255
                );
            }

            /*
             * String numeric form:
             *
             * "1, 1, 1, 0.3"
             */

            const numbers =
                text.match(
                    /-?\d+(?:\.\d+)?/g
                );

            if(
                numbers &&
                numbers.length >= 4
            ){

                const alpha =
                    ag3dV6cNormaliseFaceAlphaH16(
                        numbers[
                            numbers.length - 1
                        ],
                        NaN
                    );

                if(Number.isFinite(alpha)){

                    return alpha;
                }
            }
        }
    }

    return 1;
}

async function ag3dV6cSharedFaceMaterialH13R2(
    face
){

    if(!face || face.Valid !== true){
        return null;
    }

    const signature =
        ag3dV6cStableFaceSignatureH13R2(
            face
        );

    if(!signature){

        return await ag3dV6cCreateFaceMaterial(
            face
        );
    }

    let sourcePromise =
        ag3dV6cSharedFaceMaterialCacheH13R2.get(
            signature
        );

    if(!sourcePromise){

        sourcePromise =
            Promise.resolve()
                .then(
                    function(){

                        return ag3dV6cCreateFaceMaterial(
                            face
                        );
                    }
                )
                .catch(
                    function(error){

                        ag3dV6cSharedFaceMaterialCacheH13R2.delete(
                            signature
                        );

                        throw error;
                    }
                );

        ag3dV6cSharedFaceMaterialCacheH13R2.set(
            signature,
            sourcePromise
        );
    }

    const sourceMaterial =
        await sourcePromise;

    if(!sourceMaterial){
        return null;
    }

    // The builder owns alpha and mode; this wrapper only shares the result.
    sourceMaterial.userData.map3dShared = true;
    return sourceMaterial;
}


/*
 * ============================================================
 * EFFECTIVE TEXTUREENTRY FACE LOOKUP
 *
 * H1-R3 returns Faces as an array in the current decoder.
 * Keep object-key fallback as well for safety.
 * ============================================================
 */


async function ag3dV6cBuildMaterialSet(
    meshData,
    decodedEntry,
    fallbackMaterial
){

    if(
        !meshData ||
        !Array.isArray(
            meshData.Submeshes
        ) ||
        meshData.Submeshes.length < 1 ||
        !decodedEntry ||
        decodedEntry.ok !== true
    ){

        return null;
    }

    /*
     * ========================================================
     * REAL OPENSIM TEXTURE LOAD PERFORMANCE V6C-H4
     *
     * H3 waited for each material slot one after another.
     * H4 starts every submesh texture/material request together.
     * ========================================================
     */

    const materialPromises =
        meshData.Submeshes.map(
            async function(submesh){

                const slot =
                    Number(
                        submesh.Slot
                    );

                if(
                    !Number.isInteger(slot) ||
                    slot < 0 ||
                    slot > 31
                ){

                    return null;
                }

                const face =
                    ag3dV6cFaceForSlot(
                        decodedEntry,
                        slot
                    );

                return await ag3dV6cSharedFaceMaterialH13R2(
                    face
                );
            }
        );

    /*
     * ========================================================
     * REAL OPENSIM PARTIAL MATERIAL RECOVERY V6C-H11
     *
     * H3 previously used Promise.all() and then rejected the
     * complete material set when ANY single submesh material
     * was missing or failed.
     *
     * H11 keeps every successful textured material.
     *
     * Only an individual failed submesh receives the existing
     * gold fallback material.
     *
     * If ALL submesh materials fail, return null exactly as H3
     * did so the existing complete gold fallback remains.
     * ========================================================
     */

    const materialResults =
        await Promise.allSettled(
            materialPromises
        );

    let texturedMaterialCount =
        0;

    let recoveredMaterialCount =
        0;

    const materials =
        materialResults.map(
            function(result,index){

                if(
                    result &&
                    result.status === "fulfilled" &&
                    result.value
                ){

                    texturedMaterialCount++;

                    return result.value;
                }

                recoveredMaterialCount++;

                const failedSubmesh =
                    (
                        meshData.Submeshes &&
                        meshData.Submeshes[index]
                    )
                        ? meshData.Submeshes[index]
                        : null;

                const failedSlot =
                    failedSubmesh
                        ? Number(
                            failedSubmesh.Slot
                        )
                        : -1;

                if(
                    result &&
                    result.status === "rejected"
                ){

                    console.warn(
                        "REAL OPENSIM PARTIAL MATERIAL RECOVERY V6C-H11 - submesh fallback",
                        "slot",
                        failedSlot,
                        result.reason
                    );
                }
                else{

                    console.warn(
                        "REAL OPENSIM PARTIAL MATERIAL RECOVERY V6C-H11 - submesh fallback",
                        "slot",
                        failedSlot
                    );
                }

                return fallbackMaterial ||
                    null;
            }
        );

    /*
     * No material succeeded at all:
     * preserve the existing full-mesh H3 gold fallback.
     */

    if(
        materials.length < 1 ||
        texturedMaterialCount < 1 ||
        materials.some(
            function(material){

                return !material;
            }
        )
    ){

        return null;
    }

    if(recoveredMaterialCount > 0){

        console.info(
            "REAL OPENSIM PARTIAL MATERIAL RECOVERY V6C-H11",
            "textured slots",
            texturedMaterialCount,
            "gold fallback slots",
            recoveredMaterialCount
        );
    }

    if(materials.length === 1){

        return materials[0];
    }

    return materials;
}

/* END REAL OPENSIM TEXTURED MESH MATERIALS V6C-H3 */

/* ============================================================
   REAL OPENSIM CLASSIC GROUND TEXTURES V6C-H10

   FARM SHOP proof:
       121 broad/thin ground candidates
       106 mesh
        15 non-mesh
        15 non-mesh have real TextureEntry data

   H10 reconstructs ONLY that narrow missing class:

       - no LL mesh UUID
       - real TextureEntry present
       - >= 2m X footprint
       - >= 2m Y footprint
       - <= 1m OpenSim vertical thickness

   Existing placeholder geometry is retained until the real
   texture has successfully loaded.

   For classic box-style ground cover, OpenSim face 0 is used
   as the top-surface material. The decoder already returns the
   effective face after TextureEntry default/override handling.

   No OpenSim scene data is modified.
   ============================================================ */


async function ag3dLoadLiveRegionPrimsV4(region, surface, cellsX, cellsY) {
    if (!region || !surface) return false;
    const current = () => selectedRegion === region && surface.parent === regionGroup;
    const regionName = String(region.RegionName || '').trim();
    window.ag3dCurrentResetRegionH21R2 = regionName;
    const metrics = window.dg3dMetrics;
    const needsSignIn = response => response.status === 401 || response.status === 403 ||
        response.redirected || /text\/html/i.test(response.headers?.get('Content-Type') || '');
    try {
        // Snapshot transfer and terrain can run together; neither changes region contents.
        const response = await fetch(endpoint('objects')+'?format=2&region='+encodeURIComponent(regionName),
            {cache:'no-store',credentials:'same-origin'});
        if (needsSignIn(response)) { setStatus('Session unavailable. Sign in and reload the map.'); return false; }
        if (!response.ok) throw Error('Object snapshot HTTP '+response.status);
        const data = await response.json();
        if (!data?.ok || !Array.isArray(data.Parts) || Number(data.GroundParts)!==data.Parts.length) throw Error('Invalid object snapshot');
        if (!current()) return false;
        for (let attempt=0; attempt<100 && !surface.userData.realTerrain; attempt++) {
            await new Promise(resolve=>setTimeout(resolve,100));
            if (!current()) return false;
        }
        if (!surface.userData.realTerrain) throw Error('Terrain not ready');
        sceneParts=data.Parts;
        const unit=(70*0.96)/256, sizeX=Math.max(1,Number(cellsX||region.CellsX||1))*256,
            sizeY=Math.max(1,Number(cellsY||region.CellsY||1))*256;
        const dummy=new THREE.Object3D(), basis=new THREE.Quaternion(-Math.SQRT1_2,0,0,Math.SQRT1_2), inverse=basis.clone().invert();
        const groups=new Map(), failedTasks=new Map();
        const state={totalParts:sceneParts.length,totalAssets:0,loadedAssets:0,loadedParts:0,failedAssets:0,
            fallbackParts:0,unsupportedParts:0,invalidParts:0,materialFailures:0,materialFailureExamples:[],geometryBytes:0,completedAssets:0,remainingAssets:0};
        const box=new THREE.BoxGeometry(1,1,1), fallback=new THREE.MeshBasicMaterial({color:0xb58d45,wireframe:true});
        function fallbackMesh(entries,reason) {
            const mesh=new THREE.InstancedMesh(box,fallback,entries.length);
            mesh.name=regionName+' — '+reason; mesh.frustumCulled=false;
            mesh.userData.partIndices=entries.map(e=>e.index);mesh.userData.fallbackReason=reason;
            entries.forEach((e,i)=>mesh.setMatrixAt(i,e.matrix));mesh.instanceMatrix.needsUpdate=true;
            regionGroup.add(mesh);state.fallbackParts+=entries.length;return mesh;
        }
        const unsupported=[];
        sceneParts.forEach((part,index)=>{
            if(!Array.isArray(part)||part.length<12||!part.slice(0,10).every(v=>Number.isFinite(Number(v)))||part.slice(3,6).some(v=>Number(v)<=0)){state.invalidParts++;return;}
            dummy.position.set((Number(part[0])-sizeX/2)*unit,surface.position.y+(Number(part[2])-Number(surface.userData.waterHeight))*unit,(sizeY/2-Number(part[1]))*unit);
            dummy.scale.set(Number(part[3])*unit,Number(part[5])*unit,Number(part[4])*unit);
            const q=new THREE.Quaternion(...part.slice(6,10).map(Number));
            if(q.lengthSq()<1e-8)q.set(0,0,0,1);else q.normalize();
            dummy.quaternion.copy(basis).multiply(q).multiply(inverse).normalize();dummy.updateMatrix();
            const entry={index,matrix:dummy.matrix.clone(),textureEntry:String(part[11]||'').trim()};
            const asset=map3dAsset(part);
            if(asset.kind!=='prim'&&(!ag3dV6cValidUuid(asset.uuid)||asset.uuid==='00000000-0000-0000-0000-000000000000')){
                unsupported.push(entry);state.unsupportedParts++;return;
            }
            if(!groups.has(asset.key))groups.set(asset.key,{asset,entries:[],priority:0});
            const group=groups.get(asset.key);group.entries.push(entry);
            group.priority=Math.max(group.priority,Number(part[3])**2+Number(part[4])**2+Number(part[5])**2);
        });
        if(unsupported.length)fallbackMesh(unsupported,'Unsupported object type');
        const taskVisualScore=task=>Math.max(1,task.priority)*(1+Math.log2(task.entries.length+1));
        const tasks=[...groups.values()].sort((a,b)=>taskVisualScore(b)-taskVisualScore(a)||b.priority-a.priority||b.entries.length-a.entries.length);
        state.totalAssets=tasks.length;state.remainingAssets=tasks.length;
        let next=0,busy=false,budgetReached=false,authRequired=false;
        const refinement=new DG3DPipeline.WorkQueue(4);
        const pendingMaterial=new THREE.MeshPhongMaterial({color:0xada48d,side:THREE.DoubleSide});
        const panel=document.createElement('div');panel.id='dg3d-loading';
        const label=document.createElement('span'), more=document.createElement('button');more.type='button';more.textContent='LOAD MORE DETAIL';
        const retry=document.createElement('button');retry.type='button';retry.textContent='RETRY FAILED DETAIL';
        panel.append(label,more,retry);document.querySelector('.ag3d-v1-note')?.prepend(panel);
        let memoryLimit=(cfg.meshLod==='high'?512:384)*1024*1024;
        const memoryStep=256*1024*1024;
        const memoryCap=(cfg.meshLod==='high'?2048:1024)*1024*1024;
        function report() {
            if(!current())return;
            state.remainingAssets=tasks.length-next;
            state.completedAssets=state.loadedAssets+state.failedAssets;
            state.estimatedBytes=state.geometryBytes+map3dTextureBytes;
            state.gpuTextures=renderer.info.memory.textures;
            state.previewImages=map3dTransformedTextures.size;
            state.oversizedImages=[...map3dTransformedTextures.values()].filter(t=>t.image.width>256||t.image.height>256).length;
            state.budgetReached=budgetReached;state.busy=busy;state.authRequired=authRequired;
            const canGrowBudget=budgetReached&&memoryLimit<memoryCap&&state.remainingAssets>0;
            label.textContent=`${state.loadedParts.toLocaleString()} / ${state.totalParts.toLocaleString()} objects; ${state.failedAssets} failed assets; ${state.remainingAssets} assets waiting. `+
                (authRequired?'Session unavailable. Sign in and reload the map. ':busy?'Loading detail… ':budgetReached?(canGrowBudget?`Memory budget reached at ${Math.round(memoryLimit/1048576)} MiB. Use LOAD MORE DETAIL to continue. `:'Maximum detail budget reached. '):state.remainingAssets?'More detail available. ':'Loading finished. ');
            more.disabled=busy||authRequired||next>=tasks.length||(budgetReached&&!canGrowBudget);
            retry.disabled=busy||budgetReached||authRequired||!failedTasks.size;
            metrics?.coverage({...state});
        }
        function failure(task,status,reason) {
            if(!current())return;
            const category=status===404?'missing asset':status===422?'decode failure':task.asset.kind+' failure';
            meshDiagnostics.set(task.asset.key,{ok:false,kind:task.asset.kind,status,category,reason,
                objectUuids:task.entries.map(e=>map3dIdentity(sceneParts[e.index]).uuid)});
            failedTasks.set(task.asset.key,task);
            if(!task.fallbackMesh){state.failedAssets++;task.fallbackMesh=fallbackMesh(task.entries,category);}
            console.warn('3D geometry failure',task.asset.key,category,reason);
        }
        function materialFailure(entries,reason){
            state.materialFailures++;
            if(state.materialFailureExamples.length<5)state.materialFailureExamples.push({objectUuid:map3dIdentity(sceneParts[entries[0].index]).uuid,reason});
        }
        async function load(task,appearances) {
            const asset=task.asset;
            // A task that was budget-paused is no longer deferred once it is retried.
            // If it hits the next ceiling, the checks below set deferred=true again.
            task.deferred=false;
            try {
                // Medium LOD can be an intentionally aggressive upload-time simplification.
                // Use High LOD for physically large mesh assets so buildings do not collapse
                // into giant low-detail triangles while small props remain lightweight.
                const requestedMeshLod=cfg.meshLod==='high'?'high':
                    (asset.kind==='mesh'&&Math.sqrt(Math.max(0,task.priority))>=8?'high':'medium');
                const url=asset.kind==='prim'?endpoint('shape')+'?shape='+encodeURIComponent(asset.key.slice(5).replace(/_/g,',')):
                    asset.kind==='sculpt'?endpoint('texture')+'?uuid='+asset.uuid+'&sculpt='+asset.type:
                    endpoint('mesh')+'?uuid='+asset.uuid+'&lod='+encodeURIComponent(requestedMeshLod);
                const response=await fetch(url,{cache:'no-store',credentials:'same-origin'});
                if(needsSignIn(response)){authRequired=true;task.deferred=true;return;}
                if(authRequired){task.deferred=true;return;}
                if(!response.ok){failure(task,response.status,(await response.text()).slice(0,200));return;}
                const meshData=await response.json();if(!current())return;
                if(!meshData?.ok||meshData.MeshUuid!==asset.uuid||!Array.isArray(meshData.Positions)||!Array.isArray(meshData.Indices)||meshData.Positions.length<9||meshData.Indices.length<3)throw Error('Invalid geometry payload');
                const geometry=new THREE.BufferGeometry();
                geometry.setAttribute('position',new THREE.BufferAttribute(new Float32Array(meshData.Positions),3));
                geometry.setIndex(meshData.Indices);
                if(meshData.Normals?.length===meshData.Positions.length)geometry.setAttribute('normal',new THREE.BufferAttribute(new Float32Array(meshData.Normals),3));else geometry.computeVertexNormals();
                if(meshData.Uvs?.length*3===meshData.Positions.length*2)geometry.setAttribute('uv',new THREE.BufferAttribute(new Float32Array(meshData.Uvs),2));
                geometry.computeBoundingBox();geometry.computeBoundingSphere();
                const bytes=Object.values(geometry.attributes).reduce((n,a)=>n+a.array.byteLength,geometry.index.array.byteLength)+task.entries.length*64;
                if(state.geometryBytes+map3dTextureBytes+bytes>memoryLimit){geometry.dispose();budgetReached=true;task.deferred=true;return;}
                state.geometryBytes+=bytes;
                const hasSlots=ag3dV6cApplyGeometryGroups(geometry,meshData);
                meshDiagnostics.set(asset.key,{ok:true,kind:asset.kind,status:200,cache:response.headers.get('X-Map3D-Cache')||response.headers.get('X-Australia-3D-Cache'),
                    lod:meshData.Lod,vertices:meshData.VertexCount,faces:meshData.Submeshes,timing:response.headers.get('Server-Timing')});
                const materialGroups=new Map();
                for(const entry of task.entries){if(!materialGroups.has(entry.textureEntry))materialGroups.set(entry.textureEntry,[]);materialGroups.get(entry.textureEntry).push(entry);}
                for(const [textureEntry,entries] of materialGroups){
                    const mesh=new THREE.InstancedMesh(geometry,pendingMaterial,entries.length);
                    mesh.name=regionName+' '+asset.kind+' '+asset.uuid;mesh.frustumCulled=false;
                    mesh.userData={partIndices:entries.map(e=>e.index),meshUuid:asset.uuid,textured:false};
                    entries.forEach((e,i)=>mesh.setMatrixAt(i,asset.kind==='mesh'?ag3dV6cGroundCoverTopMatrixH8(e.matrix,geometry,surface,unit,false):e.matrix));
                    mesh.instanceMatrix.needsUpdate=true;regionGroup.add(mesh);
                    if(textureEntry&&hasSlots){
                        appearances.push(refinement.run(async()=>{
                            if(!current())return;
                            await ag3dV6cDecodeTextureEntries([textureEntry]);if(!current())return;
                            const materials=await ag3dV6cBuildMaterialSet(meshData,ag3dV6cMaterialEntryCache.get(textureEntry),pendingMaterial);
                            if(!current())return;
                            if(!materials){materialFailure(entries,'Decoded TextureEntry unavailable');return;}
                            mesh.material=materials;mesh.userData.textured=true;
                            entries.forEach((e,i)=>mesh.setMatrixAt(i,asset.kind==='mesh'?ag3dV6cGroundCoverTopMatrixH8(e.matrix,geometry,surface,unit,true):e.matrix));
                            mesh.instanceMatrix.needsUpdate=true;
                            metrics?.mark('firstAppearance','First geometry with final face material');
                        }).catch(error=>{if(current()){materialFailure(entries,String(error));console.warn('3D appearance failure',asset.key,String(error));}}));
                    }else materialFailure(entries,textureEntry?'Invalid geometry face groups':'No TextureEntry in source snapshot');
                }
                if(task.fallbackMesh){regionGroup.remove(task.fallbackMesh);state.fallbackParts-=task.entries.length;state.failedAssets--;task.fallbackMesh=null;failedTasks.delete(asset.key);}
                state.loadedAssets++;state.loadedParts+=task.entries.length;
                metrics?.mark('firstGeometry','First native mesh/sculpt/prim geometry');
            }catch(error){failure(task,0,String(error));}
        }
        async function batch(count=200) {
            if(busy||!current()||budgetReached||authRequired)return;
            busy=true;report();
            const end=Math.min(next+count,tasks.length),appearances=[];
            const entries=[...new Set(tasks.slice(next,end).flatMap(t=>t.entries.map(e=>e.textureEntry)).filter(Boolean))];
            ag3dV6cDecodeTextureEntries(entries).catch(error=>console.warn('3D material batch',String(error)));
            async function worker(){while(current()&&next<end&&!budgetReached&&!authRequired){const task=tasks[next++];await load(task,appearances);state.completedAssets++;report();await new Promise(resolve=>setTimeout(resolve,0));}}
            await Promise.all(Array.from({length:4},worker));
            await Promise.allSettled(appearances);
            if(!current())return;
            busy=false;
            // A memory-limited task remains queued instead of being silently counted as rendered.
            const deferred=tasks.splice(0,next).filter(t=>t.deferred);tasks.unshift(...deferred);next=0;
            if(state.geometryBytes+map3dTextureBytes>=memoryLimit)budgetReached=true;
            report();if(state.loadedAssets)metrics?.mark('usable','First 200-asset batch with settled appearance; navigation available throughout');
            if(!tasks.length)metrics?.mark('complete','All supported queued assets settled; failure/unsupported counts are separate');
        }
        more.addEventListener('click',()=>{
            if(budgetReached&&memoryLimit<memoryCap){
                memoryLimit=Math.min(memoryCap,memoryLimit+memoryStep);
                budgetReached=false;
                report();
            }
            batch();
        });
        retry.addEventListener('click',()=>{
            if(busy||budgetReached||authRequired||!current())return;
            const pending=new Set(tasks.map(t=>t.asset.key));
            const failures=[...failedTasks.values()].filter(t=>!pending.has(t.asset.key));
            tasks.unshift(...failures);batch(Math.min(200,failures.length));
        });
        map3dPrioritizeObject=async index=>{
            const asset=map3dAsset(sceneParts[index]);
            let position=tasks.findIndex((task,i)=>i>=next&&task.asset.key===asset.key);
            if(position<0&&failedTasks.has(asset.key)){tasks.push(failedTasks.get(asset.key));position=tasks.length-1;}
            if(position<0||budgetReached||authRequired)return;
            const [task]=tasks.splice(position,1);tasks.splice(next,0,task);
            if(!busy)await batch(1);
        };
        metrics?.mark('structure',`${sceneParts.length} transforms grouped without the old 30000-part truncation`);
        report();
        // Map Detail keeps the bounded four-batch startup. Full Detail is an explicit
        // request for complete high-detail loading, so continue automatically until
        // completion or the current memory ceiling. If the ceiling is reached the user
        // can raise it safely in 256 MiB steps with LOAD MORE DETAIL.
        const automaticBatches=cfg.meshLod==='high'?Math.ceil(tasks.length/200):4;
        for(let initial=0;initial<automaticBatches&&tasks.length&&!budgetReached&&!authRequired&&current();initial++)await batch();
        return true;
    }catch(error){if(current()){console.warn('3D scene load',regionName,String(error));setStatus('Scene unavailable');}return false;}
}


function createRegionLabel(text){

    const canvas =
        document.createElement(
            "canvas"
        );


    canvas.width = 1024;

    canvas.height = 160;


    const ctx =
        canvas.getContext(
            "2d"
        );


    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );


    ctx.fillStyle = "rgba(18,18,18,0.96)";


    ctx.fillRect(
        0,
        0,
        canvas.width,
        canvas.height
    );


    ctx.strokeStyle = "#b8892e";


    ctx.lineWidth = 6;


    ctx.strokeRect(
        4,
        4,
        canvas.width - 8,
        canvas.height - 8
    );


    ctx.font =
        "900 62px Arial";


    ctx.textAlign =
        "center";


    ctx.textBaseline =
        "middle";


    ctx.fillStyle =
        "#ffffff";


    ctx.fillText(
        String(text || ""),
        canvas.width / 2,
        canvas.height / 2
    );


    const texture =
        new THREE.CanvasTexture(
            canvas
        );


    const material =
        new THREE.SpriteMaterial({
            map:texture,
            transparent:true
        });


    const sprite =
        new THREE.Sprite(
            material
        );


    sprite.scale.set(45,7,1);


    return sprite;
}


function buildSelectedRegion(region){

    if(!region){
        return;
    }


    clearRegion();


    selectedRegion =
        region;


    /*
     * One OpenSim cell = 256m.
     *
     * We scale it visually instead of using
     * literal metres in WebGL.
     */

    const cellsX =
        Math.max(
            1,
            Number(
                region.CellsX ||
                1
            )
        );


    const cellsY =
        Math.max(
            1,
            Number(
                region.CellsY ||
                1
            )
        );


    const CELL =
        70;


    const width =
        cellsX * CELL;


    const depth =
        cellsY * CELL;


    /*
     * Stage 1 terrain base.
     *
     * Later this flat mesh becomes the real
     * terrain height mesh for this ONE region.
     */

    const baseGeometry =
        new THREE.BoxGeometry(
            width,
            10,
            depth
        );


    const baseMaterial =
        new THREE.MeshPhongMaterial({
            color:0x173b2c,
            shininess:25
        });


    const base =
        new THREE.Mesh(
            baseGeometry,
            baseMaterial
        );


    base.position.y =
        5;


    regionGroup.add(
        base
    );


    /*
     * Top land surface.
     */

    /* TERRAIN GEOMETRY SYNC V3 */
    const agTerrainSegmentsX =
        Math.min(
            192,
            Math.max(
                64,
                cellsX * 64
            )
        );


    const agTerrainSegmentsY =
        Math.min(
            192,
            Math.max(
                64,
                cellsY * 64
            )
        );


    const surfaceGeometry =
        new THREE.PlaneGeometry(
            width * 0.96,
            depth * 0.96,
            agTerrainSegmentsX,
            agTerrainSegmentsY
        );


    const agRegionMapTexture =
        ag3dCreateLiveRegionTexture(
            region,
            cellsX,
            cellsY
        );


    const surfaceMaterial =
        new THREE.MeshPhongMaterial({

            map:
                agRegionMapTexture,

            color:
                0xffffff,

            
            /*
             * REAL OPENSIM TERRAIN DEPTH BIAS V6C-H6
             *
             * Push only the terrain a tiny amount backward
             * in the depth buffer so near-coplanar OpenSim
             * ground-cover prims remain visible.
             *
             * No geometry or Z position is changed.
             */

            polygonOffset:
                true,

            polygonOffsetFactor:
                1,

            polygonOffsetUnits:
                4,

            side:
                THREE.DoubleSide,

            shininess:
                8,

            specular:
                0x20282c
        });


    const surface =
        new THREE.Mesh(
            surfaceGeometry,
            surfaceMaterial
        );


    surface.rotation.x =
        -Math.PI / 2;


    surface.position.y =
        10.2;


    regionGroup.add(
        surface
    );


    /*
     * Gold region outline.
     */

    const outline =
        new THREE.LineSegments(
            new THREE.EdgesGeometry(
                baseGeometry
            ),
            new THREE.LineBasicMaterial({
                color:0xe2b642
            })
        );


    outline.position.copy(
        base.position
    );


    regionGroup.add(
        outline
    );


    /*
     * Apply genuine OpenSim R32 terrain when cached.
     * Flat terrain remains the fallback for uncached regions.
     */

    ag3dApplyRealTerrainV1(
        region,
        surface,
        base,
        outline,
        cellsX,
        cellsY
    );

    /*
     * MULTI-REGION LIVE OPENSIM PRIMS V4
     */

ag3dLoadLiveRegionPrimsV4(
        region,
        surface,
        cellsX,
        cellsY
    );


    /*
     * Water surround.
     */

    const waterGeometry =
        new THREE.PlaneGeometry(
            width + 35,
            depth + 35
        );


    const waterMaterial =
        new THREE.MeshPhongMaterial({
            color:0x0b4d79,
            transparent:true,
            opacity:0.72,
            side:THREE.DoubleSide,
            shininess:90
        });


    const water =
        new THREE.Mesh(
            waterGeometry,
            waterMaterial
        );


    water.rotation.x =
        -Math.PI / 2;


    water.position.y =
        2;


    regionGroup.add(
        water
    );

    /*
     * UNIFIED REALISTIC OCEAN V5
     * Animated ocean replaces the original static plane.
     */

    water.visible = false;
    /*
     * ========================================================
     * UNIFIED REALISTIC OCEAN V5
     *
     * Existing static water stays underneath.
     * These three planes provide visible moving texture.
     * ========================================================
     */

    const agOceanWidth =
        Math.max(
            width + 260,
            width * 5
        );

    const agOceanDepth =
        Math.max(
            depth + 260,
            depth * 5
        );


    /* BIGGER MORE NOTICEABLE WAVES V6 */

    const agOceanRepeatX =
        Math.max(
            1.8,
            agOceanWidth / 155
        );

    const agOceanRepeatY =
        Math.max(
            1.8,
            agOceanDepth / 155
        );


    /*
     * MAIN OCEAN
     */

    const agOceanBaseTexture =
        ag3dCreateOceanTexture(
            agOceanRepeatX,
            agOceanRepeatY
        );

    const agOceanBaseGeometry =
        new THREE.PlaneGeometry(
            agOceanWidth,
            agOceanDepth
        );

    const agOceanBaseMaterial =
        new THREE.MeshPhongMaterial({

            map:
                agOceanBaseTexture,

            color:
                0x167fa8,

            transparent:
                true,

            opacity:
                0.66,

            side:
                THREE.DoubleSide,

            depthWrite:
                false,

            shininess:
                105,

            specular:
                0x9bdfff

        });


    ag3dOceanBase =
        new THREE.Mesh(
            agOceanBaseGeometry,
            agOceanBaseMaterial
        );

    ag3dOceanBase.rotation.x =
        -Math.PI / 2;

    ag3dOceanBase.position.y =
        2.08;

    regionGroup.add(
        ag3dOceanBase
    );


    /*
     * DEEP CURRENT
     */

    const agOceanDeepTexture =
        ag3dCreateOceanTexture(
            agOceanRepeatX * 0.82,
            agOceanRepeatY * 0.82
        );

    const agOceanDeepGeometry =
        new THREE.PlaneGeometry(
            agOceanWidth,
            agOceanDepth
        );

    const agOceanDeepMaterial =
        new THREE.MeshPhongMaterial({

            map:
                agOceanDeepTexture,

            color:
                0x075678,

            transparent:
                true,

            opacity:
                0.23,

            side:
                THREE.DoubleSide,

            depthWrite:
                false,

            shininess:
                75

        });


    ag3dOceanDeep =
        new THREE.Mesh(
            agOceanDeepGeometry,
            agOceanDeepMaterial
        );

    ag3dOceanDeep.rotation.x =
        -Math.PI / 2;

    ag3dOceanDeep.position.y =
        2.085;

    regionGroup.add(
        ag3dOceanDeep
    );


    /*
     * BRIGHT SURFACE RIPPLE
     */

    const agOceanSurfaceTexture =
        ag3dCreateOceanTexture(
            agOceanRepeatX * 0.70,
            agOceanRepeatY * 0.70
        );

    const agOceanSurfaceGeometry =
        new THREE.PlaneGeometry(
            agOceanWidth,
            agOceanDepth
        );

    const agOceanSurfaceMaterial =
        new THREE.MeshPhongMaterial({

            map:
                agOceanSurfaceTexture,

            color:
                0x58c9e5,

            transparent:
                true,

            opacity:
                0.15,

            side:
                THREE.DoubleSide,

            depthWrite:
                false,

            blending:
                THREE.AdditiveBlending,

            shininess:
                130,

            specular:
                0xdaf8ff

        });


    ag3dOceanSurface =
        new THREE.Mesh(
            agOceanSurfaceGeometry,
            agOceanSurfaceMaterial
        );

    ag3dOceanSurface.rotation.x =
        -Math.PI / 2;

    ag3dOceanSurface.position.y =
        2.090;

    regionGroup.add(
        ag3dOceanSurface
    );


    ag3dOceanStart =
        performance.now();

    /*
     * Add decorative fish beneath the ocean.
     */
const label =
        createRegionLabel(
            region.RegionName
        );


    label.position.set(0,18,-(depth / 2) - 18);


    regionGroup.add(
        label
    );


    updateInformation(
        region
    );


    renderRegionList(
        currentFilteredRegions()
    );


    setView(
        "angle"
    );


    setStatus(
        "Viewing " +
        region.RegionName
    );
}


function updateInformation(region){

    if(!region){

        infoName.textContent =
            "Nothing selected";

        infoStatus.textContent =
            "-";

        infoLocation.textContent =
            "-";

        infoSize.textContent =
            "-";

        infoEstate.textContent =
            "-";

        infoAvatars.textContent =
            "-";

        infoHop.textContent =
            "-";


        copyHopButton.disabled =
            true;


        focusButton.disabled =
            true;


        return;
    }


    infoName.textContent =
        region.RegionName ||
        "-";


    infoStatus.textContent =
        String(
            region.Status ||
            "Unknown"
        ).toUpperCase();


    infoLocation.textContent =
        region.X +
        ", " +
        region.Y;


    infoSize.textContent =
        region.SizeX +
        " x " +
        region.SizeY +
        " metres (" +
        region.CellsX +
        "x" +
        region.CellsY +
        ")";


    infoEstate.textContent =
        region.EstateName ||
        "-";


    infoAvatars.textContent =
        String(
            Number(
                region.AvatarCount ||
                0
            )
        );


    infoHop.textContent =
        region.HopUrl ||
        "-";


    copyHopButton.disabled =
        !region.HopUrl;


    focusButton.disabled =
        false;
}


function currentFilteredRegions(){

    const term =
        normalise(
            searchInput.value
        );


    if(!term){

        return allRegions.slice();
    }


    return allRegions.filter(
        function(region){

            return normalise(
                region.RegionName
            ).includes(
                term
            );
        }
    );
}


function renderRegionList(regions){

    if(!regions.length){

        resultsHost.innerHTML =
            '<div class="empty-message">' +
            'No matching regions.' +
            '</div>';

        return;
    }


    const selectedKey =
        selectedRegion
            ? normalise(
                selectedRegion.RegionName
            )
            : "";


    resultsHost.innerHTML =
        regions.map(
            function(region){

                const active =
                    normalise(
                        region.RegionName
                    ) === selectedKey
                        ? " active"
                        : "";


                return (
                    '<button ' +
                    'type="button" ' +
                    'class="result-button' +
                    active +
                    '" ' +
                    'data-region="' +
                    escapeHtml(
                        region.RegionName
                    ) +
                    '">' +

                    escapeHtml(
                        region.RegionName
                    ) +

                    '<small>' +
                    'Grid ' +
                    escapeHtml(
                        region.X
                    ) +
                    ', ' +
                    escapeHtml(
                        region.Y
                    ) +
                    ' | ' +
                    escapeHtml(
                        String(
                            region.Status ||
                            "Unknown"
                        ).toUpperCase()
                    ) +
                    '</small>' +

                    '</button>'
                );
            }
        ).join("");


    resultsHost
        .querySelectorAll(
            ".result-button"
        )
        .forEach(
            function(button){

                button.addEventListener(
                    "click",
                    function(){

                        const name =
                            button.getAttribute(
                                "data-region"
                            ) || "";


                        const region =
                            allRegions.find(
                                function(item){

                                    return normalise(
                                        item.RegionName
                                    ) ===
                                    normalise(
                                        name
                                    );
                                }
                            );


                        if(region){buildSelectedRegion(region);}
                    }
                );
            }
        );
}


function runSearch(){

    const filtered =
        currentFilteredRegions();


    renderRegionList(
        filtered
    );


    /*
     * If one exact/unique region is found,
     * display it immediately.
     */

    if(filtered.length === 1){

        buildSelectedRegion(
            filtered[0]
        );

        return;
    }


    const term =
        normalise(
            searchInput.value
        );


    if(term){

        const exact =
            filtered.find(
                function(region){

                    return normalise(
                        region.RegionName
                    ) === term;
                }
            );


        if(exact){

            buildSelectedRegion(
                exact
            );
        }
    }
}


function setView(mode){

    currentMode =
        mode;


    if(!selectedRegion){

        controls.target.set(
            0,
            0,
            0
        );


        camera.position.set(
            150,
            130,
            150
        );


        camera.lookAt(
            0,
            0,
            0
        );


        controls.update();

        return;
    }


    const cellsX =
        Math.max(
            1,
            Number(
                selectedRegion.CellsX ||
                1
            )
        );


    const cellsY =
        Math.max(
            1,
            Number(
                selectedRegion.CellsY ||
                1
            )
        );


    const largest =
        Math.max(
            cellsX,
            cellsY
        ) * 70;


    controls.target.set(
        0,
        5,
        0
    );


    if(mode === "top"){

        camera.position.set(
            0,
            Math.max(
                170,
                largest * 1.7
            ),
            0.01
        );

    }
    else{

        camera.position.set(
            largest * 1.05,
            Math.max(
                100,
                largest * 0.80
            ),
            largest * 1.05
        );
    }


    camera.lookAt(
        controls.target
    );


    controls.update();
}


function focusSelected(){

    if(
        selectedRegion
    ){

        setView(
            currentMode
        );
    }
}


async function loadRegions(){

    setStatus(
        "Loading regions..."
    );


    resultsHost.innerHTML =
        '<div class="empty-message">' +
        'Loading regions...' +
        '</div>';


    refreshButton.disabled =
        true;


    try{

        const response =
            await fetch(
                dataUrl +
                "?_=" +
                Date.now(),
                {
                    cache:"no-store",
                    credentials:"same-origin"
                }
            );


        const data =
            await response.json();


        if(!data.ok){

            throw new Error(
                data.error ||
                "Unable to load regions."
            );
        }


        allRegions =
            Array.isArray(
                data.regions
            )
                ? data.regions
                : [];


        allRegions.sort(
            function(a,b){

                return String(
                    a.RegionName ||
                    ""
                ).localeCompare(
                    String(
                        b.RegionName ||
                        ""
                    )
                );
            }
        );


        selectedRegion =
            null;


        clearRegion();


        updateInformation(
            null
        );


        renderRegionList(
            allRegions
        );


        setView(
            "angle"
        );


        setStatus(
            allRegions.length +
            " regions - select one to view"
        );

    }
    catch(error){

        console.error(
            error
        );


        resultsHost.innerHTML =
            '<div class="empty-message">' +
            escapeHtml(
                error.message ||
                "Unable to load regions."
            ) +
            '</div>';


        setStatus(
            "Region load failed"
        );
    }
    finally{

        refreshButton.disabled =
            false;
    }
}


async function copyHop(){

    if(
        !selectedRegion ||
        !selectedRegion.HopUrl
    ){
        return;
    }


    try{

        if(
            navigator.clipboard &&
            navigator.clipboard.writeText
        ){

            await navigator.clipboard.writeText(
                selectedRegion.HopUrl
            );


            setStatus(
                "Hop URL copied"
            );

        }
        else{

            window.prompt(
                "Copy Hop URL",
                selectedRegion.HopUrl
            );
        }

    }
    catch(error){

        window.prompt(
            "Copy Hop URL",
            selectedRegion.HopUrl
        );
    }
}


function animate(){

    requestAnimationFrame(
        animate
    );


    if(controls){

        controls.update();
    }


        /*
     * ========================================================
     * FIRESTORM OCEAN DRIFT
     *
     * Matches existing map movement:
     *
     * Base     +18 / +8
     * Deep     +10 / +5
     * Surface  -25 / +14
     *
     * CSS map texture sizes:
     * Base       700px
     * Deep       790px
     * Surface    560px
     * ========================================================
     */

    const agOceanSeconds =
        (
            performance.now() -
            ag3dOceanStart
        ) / 1000;


    if(
        ag3dOceanBase &&
        ag3dOceanBase.material &&
        ag3dOceanBase.material.map
    ){

        ag3dOceanBase.material.map.offset.x =
            (agOceanSeconds * 18) / 700;

        ag3dOceanBase.material.map.offset.y =
            (agOceanSeconds * 8) / 700;
    }


    if(
        ag3dOceanDeep &&
        ag3dOceanDeep.material &&
        ag3dOceanDeep.material.map
    ){

        ag3dOceanDeep.material.map.offset.x =
            (agOceanSeconds * 10) / 790;

        ag3dOceanDeep.material.map.offset.y =
            (agOceanSeconds * 5) / 790;
    }


    if(
        ag3dOceanSurface &&
        ag3dOceanSurface.material &&
        ag3dOceanSurface.material.map
    ){

        ag3dOceanSurface.material.map.offset.x =
            (agOceanSeconds * -25) / 560;

        ag3dOceanSurface.material.map.offset.y =
            (agOceanSeconds * 14) / 560;
    }

    /*
     * Animate decorative underwater fish.
     */
if(
        renderer &&
        scene &&
        camera
    ){

        renderer.render(
            scene,
            camera
        );
    }
}


function wireEvents(){
    const inspectButton=document.createElement('button'); inspectButton.textContent='INSPECT OBJECT'; inspectButton.type='button';
    inspectOutput=document.createElement('pre'); inspectOutput.id='dg3d-object-inspection';
    inspectOutput.style.cssText='white-space:pre-wrap;font:11px monospace;color:inherit';
    document.querySelector('.ag3d-v1-note')?.appendChild(inspectOutput);
    const objectSearch=document.createElement('input'); objectSearch.placeholder='Object UUID'; objectSearch.setAttribute('aria-label','Inspect object UUID');
    const objectFind=document.createElement('button'); objectFind.type='button'; objectFind.textContent='FIND OBJECT';
    objectFind.addEventListener('click',async()=>{
        const uuid=objectSearch.value.trim().toLowerCase();
        const partIndex=sceneParts.findIndex(part=>map3dIdentity(part).uuid===uuid);
        let found;
        regionGroup.traverse(object=>{const instanceId=object.userData.partIndices?.indexOf(partIndex);if(instanceId>=0&&!found)found={object,instanceId};});
        if((!found||found.object.userData.fallbackReason)&&partIndex>=0&&map3dPrioritizeObject){
            inspectOutput.textContent='Prioritising this object in the bounded detail queue…';
            await map3dPrioritizeObject(partIndex);
            found=undefined;
            regionGroup.traverse(object=>{const instanceId=object.userData.partIndices?.indexOf(partIndex);if(instanceId>=0&&!found)found={object,instanceId};});
        }
        if(found)displayInspection(found);
        else { const part=sceneParts[partIndex]; inspectOutput.textContent=JSON.stringify({uuid,part:part?map3dIdentity(part):'Outside current selected-part budget',geometry:part?meshDiagnostics.get(map3dAsset(part).key):null},null,2); }
    });
    inspectOutput.before(objectSearch,objectFind);
    inspectButton.addEventListener('click',()=>{inspectMode=!inspectMode;inspectButton.textContent=inspectMode?'EXIT INSPECTION':'INSPECT OBJECT';inspectOutput.textContent=inspectMode?'Click a visible object in the map to inspect it.':'';});
    if(refreshButton)refreshButton.parentNode.insertBefore(inspectButton,refreshButton);
    const detail = document.createElement('select');
    detail.setAttribute('aria-label', 'Mesh detail');
    detail.style.cssText = 'background:#101413;color:#eef2f3;border:1px solid #796331;border-radius:4px;padding:6px;font:inherit';
    for (const [value,label] of [['medium','MAP DETAIL'],['high','FULL DETAIL']]) {
        const option=document.createElement('option'); option.value=value; option.textContent=label; detail.appendChild(option);
    }
    detail.value=cfg.meshLod;
    detail.addEventListener('change',()=>{cfg.meshLod=detail.value;if(selectedRegion){window.dg3dMetrics?.start(selectedRegion.RegionName);buildSelectedRegion(selectedRegion);}});
    if(refreshButton) refreshButton.parentNode.insertBefore(detail,refreshButton);

    searchInput.addEventListener(
        "input",
        function(){

            renderRegionList(
                currentFilteredRegions()
            );
        }
    );


    searchInput.addEventListener(
        "keydown",
        function(event){

            if(
                event.key === "Enter"
            ){

                event.preventDefault();

                runSearch();
            }
        }
    );


    searchButton.addEventListener(
        "click",
        runSearch
    );


    refreshButton.addEventListener(
        "click",
        loadRegions
    );


    homeButton.addEventListener(
        "click",
        function(){

            setView(
                "angle"
            );
        }
    );


    topButton.addEventListener(
        "click",
        function(){

            setView(
                "top"
            );
        }
    );


    angleButton.addEventListener(
        "click",
        function(){

            setView(
                "angle"
            );
        }
    );


    focusButton.addEventListener(
        "click",
        focusSelected
    );


    copyHopButton.addEventListener(
        "click",
        copyHop
    );
}


if(
    init3D()
){

    wireEvents();

    loadRegions();
}

})();


})();
