<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 ============================================================
 Grid - REGION MANAGER V1

 OpenSimulator-first Region Manager.

 Current provider:
 DreamGrid regionlist compatibility endpoint.

 V1:
 - Live region list
 - Search/filter
 - DreamGrid-style management columns
 - Add Region button
 - Region diagnostics link
 - No region configuration writes yet
 ============================================================
*/

require_once __DIR__ . '/core/bootstrap.php';

$session =
    ag_require_admin();




$avatar =
    ag_avatar_name(
        $session
    );


$level =
    ag_user_level(
        $session
    );


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


function ag_rm_h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function ag_rm_bool($value)
{
    $text =
        strtolower(
            trim(
                (string)$value
            )
        );

    return in_array(
        $text,
        array(
            '1',
            'true',
            'yes',
            'on',
            'enabled',
            'checked'
        ),
        true
    );
}


function ag_rm_request($url, $timeout = 8)
{
    $context =
        stream_context_create(
            array(
                'http' =>
                    array(
                        'method' =>
                            'GET',

                        'timeout' =>
                            $timeout,

                        'ignore_errors' =>
                            true
                    )
            )
        );

    $result =
        @file_get_contents(
            $url,
            false,
            $context
        );

    if ($result === false) {
        return null;
    }

    return $result;
}


$regionListUrl =
    ag_dg_diagnostics_base() . '/' .
    '?command=regionlist' .
    '&page=1' .
    '&rp=500' .
    '&sortorder=asc';


$json =
    ag_rm_request(
        $regionListUrl,
        10
    );


$regions =
    array();

$error =
    '';


if ($json === null) {

    $error =
        'OpenSimulator region provider is unavailable.';
}
else {

    $data =
        json_decode(
            $json,
            true
        );


    if (!is_array($data)) {

        $error =
            'Region provider returned invalid data.';
    }
    else {

        $rows =
            $data['rows'] ??
            array();


        if (is_array($rows)) {

            foreach ($rows as $row) {

                $cell =
                    $row['cell'] ??
                    array();


                if (!is_array($cell)) {
                    continue;
                }


                $regions[] =
                    $cell;
            }
        }
    }
}


usort(
    $regions,
    function($a, $b){

        return strcasecmp(
            (string)(
                $a['RegionName'] ??
                ''
            ),
            (string)(
                $b['RegionName'] ??
                ''
            )
        );
    }
);


$total =
    count(
        $regions
    );


$enabled =
    0;


foreach ($regions as $region) {

    if (
        ag_rm_bool(
            $region['Enabled'] ??
            true
        )
    ) {
        $enabled++;
    }
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>Region Manager</title>

<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css?v=20260820-022508"
>

<style>

*{
    box-sizing:border-box;
}

html,
body{
    min-height:100%;
}

body{

    margin:0;

    background:#050708;

    color:#e9eef0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


.shell{

    width:
        min(
            1900px,
            calc(100% - 28px)
        );

    margin:
        24px auto 60px;
}


.header{

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:20px;

    padding:
        22px 24px;

    border:
        1px solid
        #71531b;

    border-radius:
        14px 14px 0 0;

    background:
        linear-gradient(
            180deg,
            #20272a,
            #0a0e10
        );

    box-shadow:
        0 12px 35px
        rgba(0,0,0,.45);
}


.eyebrow{

    color:#e3aa31;

    font-size:11px;

    font-weight:900;

    letter-spacing:.18em;
}


h1{

    margin:
        5px 0;

    color:#fff;

    font-size:
        clamp(
            30px,
            4vw,
            48px
        );
}


.subtitle{

    margin:0;

    color:#a8b3b8;

    font-size:13px;
}


.actions{

    display:flex;

    flex-wrap:wrap;

    gap:8px;

    justify-content:flex-end;
}


.btn{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:42px;

    padding:
        9px 15px;

    border:
        1px solid
        #6d531e;

    border-radius:
        7px;

    background:
        linear-gradient(
            #252d31,
            #101517
        );

    color:#f1f4f5;

    text-decoration:none;

    font-size:11px;

    font-weight:900;

    letter-spacing:.06em;

    cursor:pointer;
}


.btn.gold{

    border-color:
        #dda329;

    background:
        linear-gradient(
            #f3bd47,
            #b8750d
        );

    color:#171108;
}


.btn.disabled{

    opacity:.38;

    pointer-events:none;
}


.stats{

    display:grid;

    grid-template-columns:
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap:10px;

    padding:
        15px 0;
}


.stat{

    padding:
        14px 16px;

    border:
        1px solid
        #374147;

    border-radius:
        9px;

    background:
        linear-gradient(
            #171d20,
            #0b1012
        );
}


.stat span{

    display:block;

    color:#87959c;

    font-size:10px;

    font-weight:900;

    letter-spacing:.08em;
}


.stat strong{

    display:block;

    margin-top:5px;

    color:#fff;

    font-size:23px;
}


.toolbar{

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:12px;

    padding:
        13px;

    border:
        1px solid
        #374147;

    border-bottom:0;

    background:#101517;
}


.search{

    width:min(
        500px,
        100%
    );

    min-height:40px;

    padding:
        9px 12px;

    border:
        1px solid
        #465258;

    border-radius:
        7px;

    background:#080c0e;

    color:#fff;

    outline:none;
}


.table-wrap{

    overflow:auto;

    border:
        1px solid
        #374147;

    background:#080b0d;

    box-shadow:
        0 18px 45px
        rgba(0,0,0,.45);
}


table{

    width:100%;

    min-width:2700px;

    border-collapse:
        collapse;

    font-size:11px;
}


thead{

    position:sticky;

    top:0;

    z-index:3;

    background:
        linear-gradient(
            #263034,
            #13191c
        );
}


th{

    padding:
        11px 9px;

    border-right:
        1px solid
        #465158;

    border-bottom:
        1px solid
        #5c4821;

    color:#f1c45b;

    text-align:left;

    white-space:nowrap;

    font-size:10px;

    letter-spacing:.05em;
}


td{

    padding:
        9px;

    border-right:
        1px solid
        #263036;

    border-bottom:
        1px solid
        #222b30;

    white-space:nowrap;

    color:#d9dfe2;
}


tbody tr:nth-child(even){

    background:
        rgba(255,255,255,.025);
}


tbody tr:hover{

    background:
        rgba(221,163,41,.09);
}


.name{

    color:#fff;

    font-weight:900;
}


.online{

    color:#76dc92;

    font-weight:900;
}


.offline{

    color:#ef8e8e;

    font-weight:900;
}


.tick{

    width:16px;

    height:16px;

    accent-color:#d9a32e;
}


.small-action{

    display:inline-flex;

    padding:
        6px 9px;

    border:
        1px solid
        #745719;

    border-radius:
        5px;

    background:#201a0b;

    color:#efc35d;

    text-decoration:none;

    font-size:9px;

    font-weight:900;
}


.error{

    margin:
        15px 0;

    padding:
        15px;

    border:
        1px solid
        #7a3232;

    border-radius:
        8px;

    background:
        #261011;

    color:#ffb4b4;
}


.phase{

    margin-top:15px;

    padding:
        13px 15px;

    border:
        1px solid
        #664f20;

    border-radius:
        8px;

    background:
        #151106;

    color:#cbbd90;

    font-size:11px;

    line-height:1.5;
}


@media(max-width:950px){

    .header{

        flex-direction:
            column;

        align-items:
            flex-start;
    }

    .actions{
        justify-content:flex-start;
    }

    .stats{

        grid-template-columns:
            repeat(
                2,
                1fr
            );
    }

    .toolbar{

        align-items:
            stretch;

        flex-direction:
            column;
    }
}

</style>

<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>


<style id="australia-user-level-badge-v1">

.php-level{

    min-width:128px;

    padding:
        8px 12px;

    text-align:center;

    border:
        1px solid
        rgba(67,151,220,.38);

    border-radius:
        8px;

    background:
        rgba(15,68,104,.22);

    font-size:10px;

    font-weight:800;

}


.php-level strong{

    display:block;

    margin-bottom:2px;

    color:#7bc6ff;

}


</style>

</head>


<body>


<div class="shell">


<?php
$siteHeaderKicker = "ADMINISTRATION";
$siteHeaderTitle = "Region Manager";
$siteHeaderRole = "GRID OWNER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "ADMIN DASHBOARD";
$siteHeaderLink = "/Other/admin-dashboard.php";
require_once __DIR__ . "/includes/site-header.php";
?>



<div class="stats">

    <div class="stat">

        <span>
            TOTAL REGIONS
        </span>

        <strong>
            <?=$total?>
        </strong>

    </div>

<div class="php-level">

    <strong>
        <?=($level >= 200 ? 'GRID OWNER' : 'MEMBER')?>
    </strong>

    USER LEVEL
    <?=ag_h($level)?>

</div>



    <div class="stat">

        <span>
            ENABLED
        </span>

        <strong>
            <?=$enabled?>
        </strong>

    </div>


    <div class="stat">

        <span>
            DISABLED
        </span>

        <strong>
            <?=$total - $enabled?>
        </strong>

    </div>


    <div class="stat">

        <span>
            REGION PROVIDER
        </span>

        <strong style="font-size:15px;margin-top:9px;">
            REGION / OPENSIM
        </strong>

    </div>

</div>



<?php if ($error !== ''): ?>

<div class="error">
    <?=ag_rm_h($error)?>
</div>

<?php endif; ?>



<div class="toolbar">

    <input
        id="regionSearch"
        class="search"
        type="search"
        placeholder="Search region, owner, estate, location, port..."
    >


    <div class="actions">

        <span
            class="btn disabled"
            title="Safe live controls will be connected next."
        >
            RUN
        </span>

        <span
            class="btn disabled"
            title="Safe live controls will be connected next."
        >
            STOP
        </span>

        <span
            class="btn disabled"
            title="Safe live controls will be connected next."
        >
            RESTART
        </span>

        <a
            class="btn"
            href="/Other/admin-region-manager.php"
        >
            REFRESH
        </a>

    </div>

</div>



<div class="table-wrap">

<table id="regionTable">

<thead>

<tr>

    <th>ENABLE</th>
    <th>REGION NAME</th>
    <th>DOS BOX</th>
    <th>AGENTS</th>
    <th>STATUS</th>
    <th>SMART START</th>
    <th>RAM</th>
    <th>X</th>
    <th>Y</th>
    <th>SIZE</th>
    <th>ESTATE</th>
    <th>OWNER</th>
    <th>PARCELS</th>
    <th>PRIMS</th>
    <th>REGION PORT</th>
    <th>GROUP PORT</th>
    <th>MAPS</th>
    <th>PHYSICS</th>
    <th>BIRDS</th>
    <th>TIDES</th>
    <th>TELEPORT</th>
    <th>ALLOW GODS</th>
    <th>OWNER GOD</th>
    <th>MANAGER GOD</th>
    <th>AUTO BACKUP</th>
    <th>PUBLICITY</th>
    <th>SCRIPT RATE</th>
    <th>FRAME RATE</th>
    <th>OPENSIMWORLD KEY</th>
    <th>ACTION</th>

</tr>

</thead>


<tbody>

<?php foreach ($regions as $region): ?>

<?php

$name =
    (string)(
        $region['RegionName'] ??
        ''
    );


$status =
    trim(
        (string)(
            $region['Status'] ??
            ''
        )
    );


$location =
    (string)(
        $region['Location'] ??
        ''
    );


$x =
    (string)(
        $region['CoordX'] ??
        ''
    );


$y =
    (string)(
        $region['CoordY'] ??
        ''
    );


if (
    ($x === '' || $y === '') &&
    strpos($location, ',') !== false
) {

    $parts =
        explode(
            ',',
            $location,
            2
        );

    $x =
        trim(
            $parts[0] ??
            ''
        );

    $y =
        trim(
            $parts[1] ??
            ''
        );
}


$size =
    (string)(
        $region['Size'] ??
        ''
    );


if ($size === '') {

    $sx =
        (string)(
            $region['SizeX'] ??
            ''
        );

    $sy =
        (string)(
            $region['SizeY'] ??
            ''
        );

    if ($sx !== '' && $sy !== '') {
        $size = $sx . ' x ' . $sy;
    }
}


$enabledValue =
    $region['Enabled'] ??
    true;

?>

<tr>

<td>
    <input
        class="tick"
        type="checkbox"
        disabled
        <?=ag_rm_bool($enabledValue) ? 'checked' : ''?>
    >
</td>


<td class="name">
    <?=ag_rm_h($name)?>
</td>


<td>
    <?=ag_rm_h(
        $region['DOSBox'] ??
        $region['DosBox'] ??
        $name
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['AvatarCount'] ??
        $region['Agents'] ??
        '0'
    )?>
</td>


<td class="<?=stripos($status, 'running') !== false ? 'online' : 'offline'?>">
    <?=ag_rm_h($status !== '' ? $status : 'UNKNOWN')?>
</td>


<td>
    <?=ag_rm_h(
        $region['SmartStart'] ??
        'Off'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['Ram'] ??
        $region['RAM'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h($x)?>
</td>


<td>
    <?=ag_rm_h($y)?>
</td>


<td>
    <?=ag_rm_h($size)?>
</td>


<td>
    <?=ag_rm_h(
        $region['EstateName'] ??
        $region['Estate'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['EstateOwner'] ??
        $region['Owner'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['ParcelSettings'] ??
        $region['Parcels'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['PrimCount'] ??
        $region['Prims'] ??
        '0'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['InternalPort'] ??
        $region['RegionPort'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['GroupPort'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['MapType'] ??
        $region['Maps'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['Physics'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_bool($region['Birds'] ?? false) ? 'YES' : 'NO'?>
</td>


<td>
    <?=ag_rm_bool($region['Tides'] ?? false) ? 'YES' : 'NO'?>
</td>


<td>
    <?=ag_rm_bool($region['Teleport'] ?? false) ? 'YES' : 'NO'?>
</td>


<td>
    <?=ag_rm_bool($region['AllowGods'] ?? false) ? 'YES' : 'NO'?>
</td>


<td>
    <?=ag_rm_bool($region['RegionGod'] ?? false) ? 'YES' : 'NO'?>
</td>


<td>
    <?=ag_rm_bool($region['ManagerGod'] ?? false) ? 'YES' : 'NO'?>
</td>


<td>
    <?=ag_rm_bool($region['SkipAutoBackup'] ?? false) ? 'NO' : 'YES'?>
</td>


<td>
    <?=ag_rm_h(
        $region['Publicity'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['MinTimerInterval'] ??
        $region['ScriptRate'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['FrameTime'] ??
        $region['FrameRate'] ??
        '-'
    )?>
</td>


<td>
    <?=ag_rm_h(
        $region['OpenSimWorldAPIKey'] ??
        $region['OpensimWorldAPIKey'] ??
        '-'
    )?>
</td>


<td>

    <a
        class="small-action"
        href="/Other/admin-regions.php?region=<?=rawurlencode($name)?>"
    >
        DETAILS
    </a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>



<div class="phase">

    REGION MANAGER V1 is reading the live region provider only.
    ADD REGION opens the new Grid Create Region interface.
    Start, Stop, Restart and region configuration writes remain locked
    until the OpenSimulator backend adapter is connected.

</div>


</div>


<script>

(function(){

    "use strict";


    const search =
        document.getElementById(
            "regionSearch"
        );


    const rows =
        Array.from(
            document.querySelectorAll(
                "#regionTable tbody tr"
            )
        );


    search.addEventListener(
        "input",
        function(){

            const query =
                search.value
                    .trim()
                    .toLowerCase();


            rows.forEach(
                function(row){

                    const text =
                        row.textContent
                            .toLowerCase();


                    row.style.display =
                        text.includes(query)
                            ? ""
                            : "none";
                }
            );
        }
    );

})();

</script>


<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>

</html>





