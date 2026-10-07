<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/region-inspection.php';

ag_no_cache();

$session = ag_require_admin();

date_default_timezone_set(
    ag_dg_timezone()
);


function log_h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function log_decode(
    string $bytes,
    string $encoding
): string
{
    $sources = [
        'ascii'     => 'ASCII',
        'ansi'      => 'Windows-1252',
        'utf8'      => 'UTF-8',
        'unicode'   => 'UTF-16LE',
        'unicodebe' => 'UTF-16BE',
    ];

    $source =
        $sources[$encoding] ??
        'Windows-1252';


    if (
        $encoding === 'utf8' &&
        str_starts_with($bytes, "\xEF\xBB\xBF")
    ) {
        $bytes = substr($bytes, 3);
    }


    if (
        $encoding === 'unicode' &&
        str_starts_with($bytes, "\xFF\xFE")
    ) {
        $bytes = substr($bytes, 2);
    }


    if (
        $encoding === 'unicodebe' &&
        str_starts_with($bytes, "\xFE\xFF")
    ) {
        $bytes = substr($bytes, 2);
    }


    if ($source === 'UTF-8') {

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding(
                $bytes,
                'UTF-8',
                'UTF-8'
            );
        }

        return $bytes;
    }


    if (function_exists('mb_convert_encoding')) {

        return mb_convert_encoding(
            $bytes,
            'UTF-8',
            $source
        );
    }


    if (function_exists('iconv')) {

        $converted =
            @iconv(
                $source,
                'UTF-8//IGNORE',
                $bytes
            );

        if (is_string($converted)) {
            return $converted;
        }
    }


    return $bytes;
}


try { $target=ri_select(ri_regions(),$_GET['region']??'',$_GET['uuid']??''); }
catch (InvalidArgumentException $e) { http_response_code(400); exit('Invalid region selection.'); }
catch (Throwable $e) { http_response_code(404); exit('Region not found.'); }
$regionName=$target['RegionName'];
if(isset($_GET['encoding'])&&!is_string($_GET['encoding'])) { http_response_code(400); exit('Invalid encoding.'); }
$encoding =
    strtolower(
        trim(
            (string)(
                $_GET['encoding'] ??
                'ansi'
            )
        )
    );


/*
 * Remove credential-like values before log data is sent
 * to the browser.
 *
 * This does NOT alter OpenSim.log on disk.
 */
function log_redact_sensitive(string $text): string
{
    /*
     * Authorization headers need their own rule because a
     * value can contain a scheme plus credential.
     */
    $text =
        preg_replace(
            '~(?i)\b(authorization)\b(\s*:\s*)[^\r\n]+~',
            '$1$2[REDACTED]',
            $text
        ) ?? $text;


    /*
     * Bearer credentials appearing outside a normal
     * Authorization header.
     */
    $text =
        preg_replace(
            '~(?i)\b(bearer)(\s+)[A-Za-z0-9._\~+/=-]+~',
            '$1$2[REDACTED]',
            $text
        ) ?? $text;


    /*
     * Common secret/key/password labels.
     *
     * Supports forms such as:
     *
     * Secret: value
     * Password=value
     * "ApiKey":"value"
     * access_token=value
     */
    $pattern =
        '~(?i)(["\x27]?\b(' .
        'secret|' .
        'password|' .
        'passwd|' .
        'api[_ -]?key|' .
        'access[_ -]?token|' .
        'refresh[_ -]?token|' .
        'auth[_ -]?token|' .
        'machinehash|' .
        'session[_ -]?secret|' .
        'remote[_ -]?admin[_ -]?password|' .
        'database[_ -]?password|' .
        'db[_ -]?password' .
        ')\b["\x27]?\s*[:=]\s*)' .
        '(?:"[^"\r\n]*"|' .
        '\x27[^\x27\r\n]*\x27|' .
        '[^\s,;&]+)' .
        '~';


    $text =
        preg_replace(
            $pattern,
            '$1[REDACTED]',
            $text
        ) ?? $text;


    return $text;
}

$allowedEncodings = [
    'ascii',
    'ansi',
    'utf8',
    'unicode',
    'unicodebe',
];


if (
    !in_array(
        $encoding,
        $allowedEncodings,
        true
    )
) {
    $encoding = 'ansi';
}


$iniPath=$target['IniPath'];

$regionFolder =
    dirname(
        dirname($iniPath)
    );


$logPath =
    $regionFolder .
    DIRECTORY_SEPARATOR .
    'OpenSim.log';


$logReal=realpath($logPath);
if ($logReal!==false && strcasecmp(dirname($logReal),$regionFolder)!==0) { http_response_code(403); exit('Log source is outside the selected region.'); }
$logExists = is_file($logPath);


$logText = '';
$logSize = 0;
$lastModified = null;

$maxBytes = 1024 * 1024;
$maxLines = 3000;


if ($logExists) {

    $logSize =
        (int)(
            @filesize($logPath) ?: 0
        );


    $mtime =
        @filemtime($logPath);


    if ($mtime !== false) {
        $lastModified = $mtime;
    }


    $handle =
        @fopen(
            $logPath,
            'rb'
        );


    if ($handle) {

        $start =
            max(
                0,
                $logSize - $maxBytes
            );


        if (
            (
                $encoding === 'unicode' ||
                $encoding === 'unicodebe'
            ) &&
            ($start % 2) !== 0
        ) {
            $start++;
        }


        if ($start > 0) {

            fseek(
                $handle,
                $start
            );


            if (
                $encoding !== 'unicode' &&
                $encoding !== 'unicodebe'
            ) {
                // Discard the partial first line after decoding the bounded chunk.
            }
        }


        $chunk =
            stream_get_contents(
                $handle, $maxBytes
            );


        fclose($handle);


        if (is_string($chunk)) {

            $chunk =
                log_decode(
                    $chunk,
                    $encoding
                );


            if ($start > 0) {
                $boundary = strpos($chunk, "\n");
                $chunk = $boundary === false ? '' : substr($chunk, $boundary + 1);
            }
            $lines =
                preg_split(
                    '/\r\n|\n|\r/',
                    $chunk
                );


            if (is_array($lines)) {

                if (
                    count($lines) >
                    $maxLines
                ) {
                    $lines =
                        array_slice(
                            $lines,
                            -$maxLines
                        );
                }


                $logText =
                    implode(
                        PHP_EOL,
                        $lines
                    );
            }
        }
    }
}


if ($logText !== '') {

    $logText =
        log_redact_sensitive(
            $logText
        );
}

$folderName =
    basename($regionFolder);


$sizeDisplay =
    $logSize > 0
        ? number_format(
            $logSize / 1024 / 1024,
            2
        ) . ' MB'
        : '0 MB';


$modifiedDisplay =
    $lastModified
        ? date(
            'd/m/Y g:i:s A',
            $lastModified
        )
        : '-';


if (
    ($_GET['ajax'] ?? '') === '1'
) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );


    echo json_encode(
        [
            'ok' => true,

            'exists' =>
                $logExists,

            'log' =>
                $logExists
                    ? $logText
                    : 'OpenSim.log does not exist for this region.',

            'size' =>
                $sizeDisplay,

            'modified' =>
                $modifiedDisplay,

            'encoding' =>
                $encoding,
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
    );


    exit;
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title><?=log_h($regionName)?> - OpenSim.log</title>

<style>

*{
    box-sizing:border-box;
}

html,
body{
    width:100%;
    height:100%;
    margin:0;
}

body{
    display:grid;

    grid-template-rows:
        24px
        36px
        24px
        minmax(0,1fr);

    overflow:hidden;

    background:#ffffff;
    color:#111111;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


/* ==========================================================
   WEB LOG MENU BAR
   ========================================================== */

.bt-menubar{
    height:24px;

    display:flex;
    align-items:center;

    padding:0 4px;

    background:#f4f4f4;

    border-bottom:1px solid #c8c8c8;

    font-size:11px;

    position:relative;
    z-index:30;
}


.bt-menu{
    position:relative;
}


.bt-menu-button{
    height:22px;

    padding:0 8px;

    border:0;

    background:transparent;

    color:#222222;

    font-size:11px;

    cursor:pointer;
}


.bt-menu-button:hover,
.bt-menu.open .bt-menu-button{
    background:#dceaf4;
}


.bt-menu-list{
    display:none;

    position:absolute;

    top:22px;
    left:0;

    min-width:175px;

    padding:3px 0;

    background:#ffffff;

    border:1px solid #9f9f9f;

    box-shadow:
        2px 2px 5px
        rgba(0,0,0,.25);

    z-index:100;
}


.bt-menu.open .bt-menu-list{
    display:block;
}


.bt-menu-item{
    width:100%;
    height:25px;

    display:block;

    border:0;

    padding:0 24px 0 24px;

    background:#ffffff;

    color:#111111;

    font-size:11px;

    text-align:left;

    cursor:pointer;

    white-space:nowrap;
}


.bt-menu-item:hover{
    background:#d9eaf7;
}


.bt-menu-separator{
    height:1px;

    margin:3px 5px;

    background:#d1d1d1;
}


/* ==========================================================
   WEB LOG TOOLBAR
   ========================================================== */

.bt-toolbar{
    height:36px;

    display:flex;
    align-items:center;

    gap:8px;

    padding:3px 6px;

    border-bottom:1px solid #bcbcbc;

    background:
        linear-gradient(
            180deg,
            #fafafa,
            #e7e7e7
        );
}


.bt-tool{
    height:28px;

    display:grid;

    grid-template-columns:
        20px
        minmax(0,1fr);

    align-items:center;

    column-gap:4px;

    padding:0 6px;

    border:1px solid transparent;

    background:transparent;

    color:#111111;

    font-size:11px;

    cursor:pointer;
}


.bt-tool:hover{
    border-color:#9bbfd3;

    background:#e3f1fa;
}


.bt-tool.active{
    border-color:#8ab5cd;

    background:#d7ebf7;
}


.bt-tool svg{
    width:17px;
    height:17px;

    fill:none;

    stroke:#111111;
    stroke-width:1.6;

    stroke-linecap:round;
    stroke-linejoin:round;
}


.bt-open svg{
    stroke:#b59b00;
}


.bt-highlight svg{
    stroke:#d0b600;
}


.bt-follow{
    height:28px;

    display:flex;
    align-items:center;

    gap:4px;

    padding:0 3px;

    font-size:11px;

    white-space:nowrap;
}


.bt-follow input{
    width:14px;
    height:14px;

    margin:0;

    cursor:pointer;
}


.bt-encoding{
    height:26px;
    min-width:120px;

    border:1px solid #999999;

    background:#ffffff;

    color:#111111;

    font-size:11px;

    padding:0 4px;
}


.bt-title{
    min-width:0;

    overflow:hidden;

    text-overflow:ellipsis;
    white-space:nowrap;

    color:#555555;

    font-size:10px;
}


/* ==========================================================
   META BAR
   ========================================================== */

.log-meta{
    height:24px;

    display:flex;
    align-items:center;

    gap:18px;

    padding:0 7px;

    border-bottom:1px solid #d0d0d0;

    background:#f8f8f8;

    color:#555555;

    font-size:10px;

    overflow:hidden;
    white-space:nowrap;
}


/* ==========================================================
   LOG
   ========================================================== */

.log-output{
    width:100%;
    height:100%;

    margin:0;

    padding:3px 6px 18px;

    overflow:auto;

    background:#ffffff;
    color:#111111;

    font-family:
        Consolas,
        "Courier New",
        monospace;

    font-size:12px;
    line-height:1.35;

    white-space:pre;

    tab-size:4;
}


.log-output.wrap{
    white-space:pre-wrap;
    overflow-wrap:anywhere;
}


.log-line-error{
    display:block;
    background:#ffd6d6;
    color:#7d0000;
}


.log-line-warn{
    display:block;
    background:#fff1b8;
    color:#5e4400;
}


.log-line-info{
    display:block;
}


.log-empty{
    color:#9c5600;
}


#localFileInput{
    display:none;
}

</style>

<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=<?= filemtime(__DIR__ . '/assets/css/ag-uniform-site-v12.css') ?>">
<link rel="stylesheet" href="/Other/assets/css/ag-sentinel-icons-v1.css?v=20260902-sentinel-v1">



</head>

<body>


<!-- ========================================================
     MENU BAR
     ======================================================== -->

<div class="bt-menubar">


    <div class="bt-menu">

        <button class="bt-menu-button">
            File
        </button>

        <div class="bt-menu-list">

            <button
                class="bt-menu-item"
                id="menuOpen">
                Open...
            </button>

            <button
                class="bt-menu-item"
                id="menuReload">
                Reload Current Log
            </button>

            <div class="bt-menu-separator"></div>

            <button
                class="bt-menu-item"
                onclick="window.close()">
                Close
            </button>

        </div>

    </div>


    <div class="bt-menu">

        <button class="bt-menu-button">
            Edit
        </button>

        <div class="bt-menu-list">

            <button
                class="bt-menu-item"
                id="menuFind">
                Find...
            </button>

            <button
                class="bt-menu-item"
                id="menuFindNext">
                Find Next
            </button>

            <div class="bt-menu-separator"></div>

            <button
                class="bt-menu-item"
                id="menuCopyAll">
                Copy All
            </button>

        </div>

    </div>


    <div class="bt-menu">

        <button class="bt-menu-button">
            View
        </button>

        <div class="bt-menu-list">

            <button
                class="bt-menu-item"
                id="menuTop">
                Go To Top
            </button>

            <button
                class="bt-menu-item"
                id="menuBottom">
                Go To Bottom
            </button>

            <div class="bt-menu-separator"></div>

            <button
                class="bt-menu-item"
                id="menuFollow">
                Toggle Follow Tail
            </button>

        </div>

    </div>


    <div class="bt-menu">

        <button class="bt-menu-button">
            Preferences
        </button>

        <div class="bt-menu-list">

            <button
                class="bt-menu-item"
                id="menuFontPlus">
                Increase Font
            </button>

            <button
                class="bt-menu-item"
                id="menuFontMinus">
                Decrease Font
            </button>

            <button
                class="bt-menu-item"
                id="menuFontReset">
                Reset Font
            </button>

            <div class="bt-menu-separator"></div>

            <button
                class="bt-menu-item"
                id="menuWrap">
                Toggle Line Wrap
            </button>

        </div>

    </div>


    <div class="bt-menu">

        <button class="bt-menu-button">
            Help
        </button>

        <div class="bt-menu-list">

            <button
                class="bt-menu-item"
                id="menuAbout">
                About
            </button>

        </div>

    </div>


</div>


<!-- ========================================================
     TOOLBAR
     ======================================================== -->

<div class="bt-toolbar">


    <button
        class="bt-tool bt-open"
        id="openButton"
        type="button">

        <img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/files.png" alt="" aria-hidden="true" draggable="false" decoding="async">

        <span>Open</span>

    </button>


    <button
        class="bt-tool bt-highlight active"
        id="highlightButton"
        type="button">

        <img class="ag-sentinel-direct-icon ag-sentinel-sm" src="/Other/assets/icons/sentinel/edit.png" alt="" aria-hidden="true" draggable="false" decoding="async">

        <span>Highlighting</span>

    </button>


    <label class="bt-follow">

        <input
            id="followTail"
            type="checkbox"
            checked>

        <span>
            Follow Tail
        </span>

    </label>


    <select
        class="bt-encoding"
        id="encodingSelect">

        <option
            value="ascii"
            <?=$encoding === 'ascii' ? 'selected' : ''?>>
            ASCII
        </option>

        <option
            value="ansi"
            <?=$encoding === 'ansi' ? 'selected' : ''?>>
            ANSI
        </option>

        <option
            value="utf8"
            <?=$encoding === 'utf8' ? 'selected' : ''?>>
            UTF-8
        </option>

        <option
            value="unicode"
            <?=$encoding === 'unicode' ? 'selected' : ''?>>
            Unicode
        </option>

        <option
            value="unicodebe"
            <?=$encoding === 'unicodebe' ? 'selected' : ''?>>
            Unicode Big Endian
        </option>

    </select>


    <div class="bt-title" id="viewerTitle">

        <?=log_h($regionName . ' — OpenSim.log')?>

    </div>


</div>


<p id="refreshStatus" role="status"></p>
<div class="log-meta">

    <span>
        Folder:
        <?=log_h($folderName)?>
    </span>

    <span>
        Size:
        <strong id="logSize">
            <?=log_h($sizeDisplay)?>
        </strong>
    </span>

    <span>
        Last Updated:
        <strong id="logModified">
            <?=log_h($modifiedDisplay)?>
        </strong>
    </span>

</div>


<pre
    class="log-output"
    id="regionLog"><?=log_h(
        $logExists
            ? $logText
            : 'OpenSim.log does not exist for this region.'
    )?></pre>


<input
    id="localFileInput"
    type="file"
    accept=".log,.txt,text/plain">


<script>

const regionName =
    <?=json_encode(
        $regionName,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
    )?>;


const regionLog =
    document.getElementById(
        "regionLog"
    );


const followTail =
    document.getElementById(
        "followTail"
    );


const encodingSelect =
    document.getElementById(
        "encodingSelect"
    );


const highlightButton =
    document.getElementById(
        "highlightButton"
    );


const openButton =
    document.getElementById(
        "openButton"
    );


const localFileInput =
    document.getElementById(
        "localFileInput"
    );


const logSize =
    document.getElementById(
        "logSize"
    );


const logModified =
    document.getElementById(
        "logModified"
    );


const viewerTitle =
    document.getElementById(
        "viewerTitle"
    );


let currentText =
    regionLog.textContent;


let highlighting =
    true;


let refreshBusy =
    false;


let localMode =
    false;


let localBuffer =
    null;


let lastFind =
    "";


let logFontSize =
    12;


/* ==========================================================
   MENUS
   ========================================================== */

const menus =
    document.querySelectorAll(
        ".bt-menu"
    );


menus.forEach(
    function(menu){

        const button =
            menu.querySelector(
                ".bt-menu-button"
            );


        button.addEventListener(
            "click",
            function(event){

                event.stopPropagation();


                menus.forEach(
                    function(other){

                        if (other !== menu) {
                            other.classList.remove(
                                "open"
                            );
                        }
                    }
                );


                menu.classList.toggle(
                    "open"
                );
            }
        );
    }
);


document.addEventListener(
    "click",
    closeMenus
);


function closeMenus()
{
    menus.forEach(
        function(menu){

            menu.classList.remove(
                "open"
            );
        }
    );
}


/* ==========================================================
   SAFE LOG RENDERING + HIGHLIGHTING
   ========================================================== */

function escapeHtml(value)
{
    return String(value)
        .replaceAll("&","&amp;")
        .replaceAll("<","&lt;")
        .replaceAll(">","&gt;")
        .replaceAll('"',"&quot;")
        .replaceAll("'","&#039;");
}


function renderLog(text)
{
    currentText =
        String(text || "");


    if (!highlighting) {

        regionLog.textContent =
            currentText;

        return;
    }


    const lines =
        currentText.split(
            /\r\n|\n|\r/
        );


    regionLog.innerHTML =
        lines.map(
            function(line){

                let cls =
                    "log-line-info";


                if (
                    /\b(ERROR|FATAL|EXCEPTION)\b/i.test(
                        line
                    )
                ) {
                    cls =
                        "log-line-error";
                }
                else if (
                    /\b(WARN|WARNING)\b/i.test(
                        line
                    )
                ) {
                    cls =
                        "log-line-warn";
                }


                return (
                    '<span class="' +
                    cls +
                    '">' +
                    escapeHtml(line) +
                    '</span>'
                );
            }
        ).join("");
}


/* ==========================================================
   FOLLOW TAIL
   ========================================================== */

function scrollToTail()
{
    if (!followTail.checked) {
        return;
    }


    regionLog.scrollTop =
        regionLog.scrollHeight;
}


function goTop()
{
    followTail.checked =
        false;

    regionLog.scrollTop =
        0;
}


function goBottom()
{
    regionLog.scrollTop =
        regionLog.scrollHeight;
}


/* ==========================================================
   SERVER LOG REFRESH
   ========================================================== */

async function refreshServerLog()
{
    if (
        refreshBusy ||
        localMode || document.hidden
    ) {
        return;
    }


    const requestedEncoding = encodingSelect.value;
    refreshBusy =
        true;


    try {

        const url =
            "/Other/admin-region-log.php" +
            "?region=" +
            encodeURIComponent(regionName) +
            "&encoding=" +
            encodeURIComponent(
                encodingSelect.value
            ) +
            "&ajax=1&_=" +
            Date.now();


        const response =
            await fetch(
                url,
                {
                    credentials:
                        "same-origin",

                    cache:
                        "no-store"
                }
            );


        if (!response.ok) {

            throw new Error(
                "HTTP " +
                response.status
            );
        }


        const data =
            await response.json();


        if (
            !data ||
            data.ok !== true
        ) {
            throw new Error(
                "Invalid log response"
            );
        }


        if (localMode || requestedEncoding !== encodingSelect.value) return;
        if ((data.log || "") !== currentText) renderLog(data.log || "");
        document.getElementById("refreshStatus").textContent = "";


        logSize.textContent =
            data.size || "-";


        logModified.textContent =
            data.modified || "-";


        scrollToTail();

    }
    catch (error) {
        document.getElementById("refreshStatus").textContent = "Log refresh failed. Check your session or retry.";
        console.error(
            "Log refresh failed:",
            error
        );

    }
    finally {

        refreshBusy =
            false;
    }
}


/* ==========================================================
   OPEN LOCAL FILE
   ========================================================== */

function openLocalFile()
{
    localFileInput.click();
}


function decoderName()
{
    switch (
        encodingSelect.value
    ) {

        case "unicode":
            return "utf-16le";

        case "unicodebe":
            return "utf-16be";

        case "utf8":
            return "utf-8";

        case "ascii":
            return "windows-1252";

        default:
            return "windows-1252";
    }
}


function decodeLocalBuffer()
{
    if (!localBuffer) {
        return;
    }


    try {

        const decoder =
            new TextDecoder(
                decoderName()
            );


        const text =
            decoder.decode(
                localBuffer
            );


        renderLog(text);

        scrollToTail();

    }
    catch (error) {

        alert(
            "Unable to decode this file using the selected encoding."
        );
    }
}


localFileInput.addEventListener(
    "change",
    async function(){

        const file =
            localFileInput.files[0];


        if (file && file.size > 1048576) { alert("Choose a local log no larger than 1 MB."); return; }
        if (!file) {
            return;
        }


        localMode =
            true;


        followTail.checked =
            false;


        localBuffer =
            await file.arrayBuffer();


        viewerTitle.textContent =
            file.name;


        logSize.textContent =
            (
                file.size /
                1024 /
                1024
            ).toFixed(2) +
            " MB";


        logModified.textContent =
            new Date(
                file.lastModified
            ).toLocaleString();


        decodeLocalBuffer();
    }
);


openButton.addEventListener(
    "click",
    openLocalFile
);


document.getElementById(
    "menuOpen"
).addEventListener(
    "click",
    openLocalFile
);


/* ==========================================================
   HIGHLIGHTING
   ========================================================== */

highlightButton.addEventListener(
    "click",
    function(){

        highlighting =
            !highlighting;


        highlightButton.classList.toggle(
            "active",
            highlighting
        );


        renderLog(
            currentText
        );


        scrollToTail();
    }
);


/* ==========================================================
   ENCODING
   ========================================================== */

encodingSelect.addEventListener(
    "change",
    function(){

        if (localMode) {

            decodeLocalBuffer();

            return;
        }


        refreshServerLog();
    }
);


/* ==========================================================
   FILE MENU
   ========================================================== */

document.getElementById(
    "menuReload"
).addEventListener(
    "click",
    function(){

        localMode =
            false;


        localBuffer =
            null;


        viewerTitle.textContent =
            <?=json_encode(
                $regionName . ' — OpenSim.log',
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
            )?>;


        refreshServerLog();
    }
);


/* ==========================================================
   EDIT MENU
   ========================================================== */

function findText()
{
    const query =
        prompt(
            "Find:",
            lastFind
        );


    if (!query) {
        return;
    }


    lastFind =
        query;


    window.find(
        query,
        false,
        false,
        true,
        false,
        false,
        false
    );
}


document.getElementById(
    "menuFind"
).addEventListener(
    "click",
    findText
);


document.getElementById(
    "menuFindNext"
).addEventListener(
    "click",
    function(){

        if (!lastFind) {

            findText();

            return;
        }


        window.find(
            lastFind,
            false,
            false,
            true,
            false,
            false,
            false
        );
    }
);


document.getElementById(
    "menuCopyAll"
).addEventListener(
    "click",
    async function(){

        try {

            await navigator.clipboard.writeText(
                currentText
            );

        }
        catch (error) {

            const area =
                document.createElement(
                    "textarea"
                );


            area.value =
                currentText;


            document.body.appendChild(
                area
            );


            area.select();


            document.execCommand(
                "copy"
            );


            area.remove();
        }
    }
);


/* ==========================================================
   VIEW MENU
   ========================================================== */

document.getElementById(
    "menuTop"
).addEventListener(
    "click",
    goTop
);


document.getElementById(
    "menuBottom"
).addEventListener(
    "click",
    goBottom
);


document.getElementById(
    "menuFollow"
).addEventListener(
    "click",
    function(){

        followTail.checked =
            !followTail.checked;


        if (followTail.checked) {

            localMode
                ? scrollToTail()
                : refreshServerLog();
        }
    }
);


followTail.addEventListener(
    "change",
    function(){

        if (followTail.checked) {

            localMode
                ? scrollToTail()
                : refreshServerLog();
        }
    }
);


/* ==========================================================
   PREFERENCES MENU
   ========================================================== */

function applyFontSize()
{
    regionLog.style.fontSize =
        logFontSize +
        "px";
}


document.getElementById(
    "menuFontPlus"
).addEventListener(
    "click",
    function(){

        logFontSize =
            Math.min(
                24,
                logFontSize + 1
            );


        applyFontSize();
    }
);


document.getElementById(
    "menuFontMinus"
).addEventListener(
    "click",
    function(){

        logFontSize =
            Math.max(
                8,
                logFontSize - 1
            );


        applyFontSize();
    }
);


document.getElementById(
    "menuFontReset"
).addEventListener(
    "click",
    function(){

        logFontSize =
            12;


        applyFontSize();
    }
);


document.getElementById(
    "menuWrap"
).addEventListener(
    "click",
    function(){

        regionLog.classList.toggle(
            "wrap"
        );
    }
);


/* ==========================================================
   HELP
   ========================================================== */

document.getElementById(
    "menuAbout"
).addEventListener(
    "click",
    function(){

        alert(
            "Region Log Viewer\n\n" +
            "Web log viewer for OpenSim.\n\n" +
            "Region: " +
            regionName
        );
    }
);


/* ==========================================================
   INITIAL DISPLAY
   ========================================================== */

renderLog(
    currentText
);


scrollToTail();


/*
 * Follow real OpenSim.log every 3 seconds.
 */
const logTimer = setInterval(
    function(){

        if (
            followTail.checked &&
            !localMode
        ) {
            refreshServerLog();
        }

    },
    5000
);
window.addEventListener("pagehide",()=>clearInterval(logTimer),{once:true});

</script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=<?= filemtime(__DIR__ . '/assets/js/ag-uniform-site-v12.js') ?>"></script>
</body>

</html>



