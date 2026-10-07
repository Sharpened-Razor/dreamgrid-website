<?php

declare(strict_types=1);

/*
 * ============================================================
 * AUSTRALIA CONTROL CENTER
 * CLEAN REGION MAP V2
 *
 * Fresh implementation.
 *
 * No old DreamGrid white map window.
 * No Administration header.
 * No Admin Home navigation.
 * No old Region Map CSS.
 * No old Region Map JavaScript.
 *
 * This page is the actual content displayed inside the shared
 * charcoal/gold Control Center popup.
 * ============================================================
 */

require_once
    __DIR__ .
    '/core/dreamgrid-env.php';

require_once
    __DIR__ .
    '/core/bootstrap.php';


ag_no_cache();

$session =
    ag_require_admin();


if (isset($_GET['region']) && !is_string($_GET['region'])) {
    http_response_code(400);
    exit('A valid Region Name is required.');
}

$regionName =
    trim(
        (string)(
            $_GET['region'] ??
            ''
        )
    );


if (
    $regionName === '' ||
    strlen(
        $regionName
    ) > 255
) {

    http_response_code(
        400
    );

    exit(
        'A valid Region Name is required.'
    );
}


/*
 * ============================================================
 * DREAMGRID LIVE REGION LIST
 * ============================================================
 */

function drm_region_list(): array
{
    $url =
        ag_dg_diagnostics_base() .
        '/?command=regionlist&page=1&rp=500&sortorder=asc';


    $json =
        null;


    if (
        function_exists(
            'curl_init'
        )
    ) {

        $curl =
            curl_init(
                $url
            );


        if (
            $curl !== false
        ) {

            curl_setopt_array(
                $curl,
                [
                    CURLOPT_RETURNTRANSFER =>
                        true,

                    CURLOPT_CONNECTTIMEOUT =>
                        3,

                    CURLOPT_TIMEOUT =>
                        8,
                ]
            );


            $result =
                curl_exec(
                    $curl
                );


            if (
                is_string(
                    $result
                )
            ) {

                $json =
                    $result;
            }


            curl_close(
                $curl
            );
        }
    }


    if (
        !is_string(
            $json
        ) ||
        trim(
            $json
        ) === ''
    ) {

        $fallback =
            @file_get_contents(
                $url,
                false,
                stream_context_create(['http' => ['timeout' => 8]])
            );


        if (
            is_string(
                $fallback
            )
        ) {

            $json =
                $fallback;
        }
    }


    if (
        !is_string(
            $json
        ) ||
        trim(
            $json
        ) === ''
    ) {

        return [];
    }


    $decoded =
        json_decode(
            $json,
            true
        );


    if (
        !is_array(
            $decoded
        ) ||
        !isset(
            $decoded['rows']
        ) ||
        !is_array(
            $decoded['rows']
        )
    ) {

        return [];
    }


    return
        $decoded['rows'];
}


/*
 * ============================================================
 * EXACT REGION LOOKUP
 * ============================================================
 */

$region =
    null;


foreach (
    drm_region_list()
    as
    $row
) {

    if (
        !is_array(
            $row
        )
    ) {

        continue;
    }


    $cell =
        $row['cell'] ??
        null;


    if (
        !is_array(
            $cell
        )
    ) {

        continue;
    }


    $candidateName =
        trim(
            (string)(
                $cell['RegionName'] ??
                ''
            )
        );


    if (
        $candidateName !== '' &&
        strcasecmp(
            $candidateName,
            $regionName
        ) === 0
    ) {

        $region =
            $cell;

        break;
    }
}


if (
    !is_array(
        $region
    )
) {

    http_response_code(
        404
    );

    exit(
        'Region was not found in the running DreamGrid region list.'
    );
}


/*
 * ============================================================
 * LIVE MAP IMAGE
 * ============================================================
 */

$mapHtml =
    (string)(
        $region['Map'] ??
        ''
    );


$mapUrl =
    '';


if (
    preg_match(
        "~src\s*=\s*['\"]([^'\"]+)['\"]~i",
        $mapHtml,
        $match
    )
) {

    $mapUrl = trim(html_entity_decode((string)($match[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}


if (
    $mapUrl === ''
) {

    http_response_code(
        404
    );

    exit(
        'No live map image is currently available for this region.'
    );
}


/*
 * ============================================================
 * REGION SIZE
 * ============================================================
 */

$regionSize =
    trim(
        strip_tags(
            (string)(
                $region['Size'] ??
                ''
            )
        )
    );


if (
    $regionSize === ''
) {

    $regionSize =
        '-';
}


function drm_h(
    string
    $value
): string
{
    return
        htmlspecialchars(
            $value,
            ENT_QUOTES |
            ENT_SUBSTITUTE,
            'UTF-8'
        );
}


$title =
    $regionName .
    ' - Region Map';

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title><?=drm_h($title)?></title>

<style id="australia-clean-region-map-v2">

:root{
    color-scheme:dark;

    --rm-page:#080a08;
    --rm-panel:#0d0f0d;
    --rm-panel2:#151713;

    --rm-border:#4a402d;
    --rm-border2:#6c5524;

    --rm-gold:#c99f43;
    --rm-gold-bright:#e5b84e;

    --rm-text:#d7d7d1;
    --rm-muted:#90938d;
}


*{
    box-sizing:border-box;
}


html,
body{
    width:100%;
    min-width:0;
    min-height:100%;

    margin:0;
    padding:0;
}


html{
    background:var(--rm-page);
}


body{
    min-height:100vh;

    padding:12px;

    color:var(--rm-text);

    background:
        linear-gradient(
            180deg,
            #0c0e0c 0%,
            #070907 100%
        );

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}


.region-map-content{
    width:100%;
    min-width:0;

    min-height:
        calc(
            100vh - 24px
        );

    display:grid;

    grid-template-rows:
        auto
        minmax(0,1fr)
        auto;

    gap:10px;
}


.region-map-toolbar{
    min-height:52px;

    display:flex;

    align-items:center;
    justify-content:space-between;

    gap:14px;

    padding:
        8px 10px;

    border:
        1px solid
        var(--rm-border);

    border-radius:7px;

    background:
        linear-gradient(
            180deg,
            #181a16 0%,
            #0c0e0c 100%
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.035),
        0 3px 12px
        rgba(0,0,0,.45);
}


.region-map-identity{
    min-width:0;

    display:flex;
    align-items:center;

    gap:11px;
}


.region-map-identity-icon{
    width:34px;
    height:34px;

    flex:
        0 0 34px;

    object-fit:contain;
}


.region-map-heading{
    min-width:0;
}


.region-map-title{
    overflow:hidden;

    color:
        var(--rm-gold-bright);

    font-size:16px;
    font-weight:700;

    text-overflow:ellipsis;
    white-space:nowrap;
}


.region-map-subtitle{
    margin-top:2px;

    color:
        var(--rm-muted);

    font-size:11px;
    font-weight:600;

    letter-spacing:.08em;

    text-transform:uppercase;
}


.region-map-actions{
    flex:
        0 0 auto;

    display:flex;

    gap:7px;
}


.region-map-button{
    min-width:96px;
    height:34px;

    display:inline-flex;

    align-items:center;
    justify-content:center;

    gap:7px;

    padding:
        0 12px;

    border:
        1px solid
        #665429;

    border-radius:5px;

    color:
        #efe5ca;

    background:
        linear-gradient(
            180deg,
            #2b291d 0%,
            #17150f 100%
        );

    box-shadow:
        inset 0 1px 0
        rgba(255,240,180,.07);

    font:
        inherit;

    font-size:12px;
    font-weight:700;

    cursor:pointer;
}


.region-map-button:hover{
    border-color:
        var(--rm-gold);

    background:
        linear-gradient(
            180deg,
            #39331f 0%,
            #1f1b11 100%
        );
}


.region-map-button:disabled{
    opacity:.55;

    cursor:default;
}


.region-map-button img{
    width:18px;
    height:18px;

    object-fit:contain;
}


.region-map-stage{
    min-width:0;
    min-height:420px;

    display:flex;

    align-items:center;
    justify-content:center;

    overflow:auto;

    padding:12px;

    border:
        1px solid
        #3d3727;

    border-radius:7px;

    background:
        radial-gradient(
            circle at center,
            #161914 0%,
            #080a08 72%
        );

    box-shadow:
        inset 0 0 25px
        rgba(0,0,0,.74);
}


.region-map-image{
    display:block;

    width:auto;
    height:auto;

    max-width:100%;

    max-height:
        calc(
            100vh - 145px
        );

    object-fit:contain;

    border:
        1px solid
        #594923;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.7);
}


.region-map-status{
    min-height:27px;

    display:flex;

    align-items:center;

    padding:
        4px 8px;

    border:
        1px solid
        #343630;

    border-radius:5px;

    color:
        var(--rm-muted);

    background:
        #0a0c0a;

    font-size:11px;
}


.region-map-status:empty{
    display:none;
}


@media(max-width:700px){

    body{
        padding:8px;
    }


    .region-map-toolbar{
        align-items:flex-start;

        flex-direction:column;
    }


    .region-map-actions{
        width:100%;
    }


    .region-map-button{
        flex:1;
    }
}


@media print{

    body{
        padding:0;

        background:#ffffff;
    }


    .region-map-toolbar,
    .region-map-status{
        display:none !important;
    }


    .region-map-content{
        display:block;

        min-height:0;
    }


    .region-map-stage{
        min-height:0;

        padding:0;

        border:0;

        background:#ffffff;

        box-shadow:none;
    }


    .region-map-image{
        max-width:100%;
        max-height:none;

        border:0;

        box-shadow:none;
    }
}

</style>

</head>


<body>

<main
    class="region-map-content">

    <header
        class="region-map-toolbar">

        <div
            class="region-map-identity">

            <img
                class="region-map-identity-icon"
                src="/Other/assets/icons/sentinel/map.png"
                alt=""
                aria-hidden="true">

            <div
                class="region-map-heading">

                <div
                    class="region-map-title">
                    <?=drm_h($regionName)?>
                </div>

                <div
                    class="region-map-subtitle">
                    REGION MAP · <?=drm_h($regionSize)?>
                </div>

            </div>

        </div>


        <div
            class="region-map-actions">

            <button
                class="region-map-button"
                id="regionMapPrint"
                type="button">

                <img
                    src="/Other/assets/icons/sentinel/report.png"
                    alt=""
                    aria-hidden="true">

                <span>
                    PRINT
                </span>

            </button>


            <button
                class="region-map-button"
                id="regionMapExport"
                type="button">

                <img
                    src="/Other/assets/icons/sentinel/export.png"
                    alt=""
                    aria-hidden="true">

                <span>
                    EXPORT
                </span>

            </button>

        </div>

    </header>


    <section
        class="region-map-stage">

        <img
            class="region-map-image"
            id="regionMapImage"
            src="<?=drm_h($mapUrl)?>"
            alt="<?=drm_h($regionName)?> region map">

    </section>


    <div
        class="region-map-status"
        id="regionMapStatus"
        role="status"
        aria-live="polite"></div>

</main>


<script id="australia-clean-region-map-js-v2">
(function(){

    "use strict";


    const mapUrl =
        <?=json_encode(
            $mapUrl,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        )?>;


    const regionName =
        <?=json_encode(
            $regionName,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        )?>;


    const status =
        document.getElementById(
            "regionMapStatus"
        );


    const printButton =
        document.getElementById(
            "regionMapPrint"
        );


    const exportButton =
        document.getElementById(
            "regionMapExport"
        );


    printButton.addEventListener(
        "click",
        function(){

            window.print();
        }
    );


    exportButton.addEventListener(
        "click",
        async function(){

            status.textContent =
                "Exporting map...";


            exportButton.disabled =
                true;


            try {

                const response =
                    await fetch(
                        mapUrl,
                        {
                            credentials:
                                "same-origin",

                            cache:
                                "no-store"
                        }
                    );


                if (
                    !response.ok
                ) {

                    throw new Error(
                        "Map image could not be downloaded."
                    );
                }


                const blob =
                    await response.blob();


                const objectUrl =
                    URL.createObjectURL(
                        blob
                    );


                const safeName =
                    String(
                        regionName ||
                        "Region_Map"
                    )
                    .replace(
                        /[^a-z0-9 _.-]+/gi,
                        ""
                    )
                    .trim()
                    .replace(
                        /\s+/g,
                        "_"
                    );


                const link =
                    document.createElement(
                        "a"
                    );


                link.href =
                    objectUrl;


                link.download =
                    (
                        safeName ||
                        "Region_Map"
                    ) +
                    ".png";


                document.body.appendChild(
                    link
                );


                link.click();

                link.remove();


                setTimeout(
                    function(){

                        URL.revokeObjectURL(
                            objectUrl
                        );
                    },
                    500
                );


                status.textContent =
                    "Map exported.";
            }
            catch(error){

                status.textContent =
                    error &&
                    error.message
                        ? error.message
                        : "Map export failed.";
            }
            finally {

                exportButton.disabled =
                    false;
            }
        }
    );

})();
</script>

</body>
</html>