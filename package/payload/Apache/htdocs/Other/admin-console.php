<?php
require_once __DIR__ . '/core/grid-branding.php';


require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/dreamgrid-env.php';

ag_no_cache();

$session = ag_require_admin();

$avatar = ag_avatar_name($session);
$level  = ag_user_level($session);

$cookie =
    (string)(
        $_COOKIE['dg_session'] ??
        ''
    );

$csrf =
    hash_hmac(
        'sha256',
        $cookie,
        'AUSTRALIA_ADMIN_CONSOLE_V1'
    );

$regionsRoot = ag_dg_regions_root();

$regionFolders = [];

if (is_dir($regionsRoot)) {

    $folders =
        glob(
            $regionsRoot .
            DIRECTORY_SEPARATOR .
            '*',
            GLOB_ONLYDIR
        );

    if (is_array($folders)) {

        foreach ($folders as $folder) {

            $ini =
                $folder .
                DIRECTORY_SEPARATOR .
                'Opensim.ini';

            if (!is_file($ini)) {
                continue;
            }

            $regionFolders[] =
                basename($folder);
        }
    }
}

natcasesort($regionFolders);

$regionFolders =
    array_values(
        $regionFolders
    );

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta
    name="viewport"
    content="width=device-width,initial-scale=1">
<title>Admin Console</title>

<style id="admin-console-clean-v2">

html,
body{
    margin:0;
    padding:0;
    background:transparent !important;
    color:#ece7da;
    font-family:Segoe UI,Arial,Helvetica,sans-serif;
}

*{
    box-sizing:border-box;
}

.cc-console-clean{
    padding:18px 18px 16px;
}

.cc-console-card{
    margin:0 0 18px 0;
    border:1px solid #8f6c26 !important;
    border-radius:10px;
    background:#171717 !important;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04),
        0 8px 18px rgba(0,0,0,.38);
    overflow:hidden;
}

.cc-console-card:last-child{
    margin-bottom:0;
}

.cc-console-card-inner{
    padding:16px 16px 14px;
}

.cc-console-strip{
    height:2px;
    background:linear-gradient(
        90deg,
        #5e4614 0%,
        #d7ab46 20%,
        #f3cb74 50%,
        #d7ab46 80%,
        #5e4614 100%
    );
    opacity:.95;
}

.cc-console-info{
    display:flex;
    align-items:flex-start;
    gap:14px;
}

.cc-console-icon{
    flex:0 0 40px;
    width:40px;
    height:40px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px solid #8f6c26;
    border-radius:8px;
    background:#232323 !important;
    color:#e5ba53;
    font:700 18px Consolas,monospace;
}

.cc-console-kicker{
    margin:0 0 5px 0;
    color:#d6aa45;
    font-size:11px;
    font-weight:900;
    letter-spacing:.12em;
    text-transform:uppercase;
}

.cc-console-text{
    margin:0;
    color:#cac3b4;
    font-size:13px;
    line-height:1.55;
}

.cc-console-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin:0 0 14px 0;
    padding:0 0 12px 0;
    border-bottom:1px solid rgba(255,255,255,.06);
}

.cc-console-head-left{
    min-width:0;
}

.cc-console-eyebrow{
    margin:0 0 4px 0;
    color:#d6aa45;
    font-size:10px;
    font-weight:900;
    letter-spacing:.16em;
    text-transform:uppercase;
}

.cc-console-title{
    margin:0;
    color:#f2d07a;
    font-size:17px;
    font-weight:800;
    line-height:1.2;
}

.cc-console-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:28px;
    padding:0 10px;
    border:1px solid #8f6c26;
    border-radius:999px;
    background:#1f1a10 !important;
    color:#efc763;
    font-size:10px;
    font-weight:900;
    letter-spacing:.06em;
    white-space:nowrap;
}

.cc-console-form{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.cc-console-field{
    min-width:0;
}

.cc-console-field.full{
    grid-column:1 / -1;
}

.cc-console-label{
    display:block;
    margin:0 0 6px 0;
    color:#ddb04a;
    font-size:11px;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
}

.cc-console-input,
.cc-console-select{
    width:100%;
    min-height:40px;
    padding:9px 11px;
    border:1px solid #4a3b18 !important;
    border-radius:7px;
    outline:none;
    background:#090909 !important;
    color:#f1f1f1 !important;
    box-shadow:
        inset 0 1px 3px rgba(0,0,0,.85);
    font:13px Consolas,"Courier New",monospace;
}

.cc-console-input:focus,
.cc-console-select:focus{
    border-color:#d5a844 !important;
    box-shadow:
        inset 0 1px 3px rgba(0,0,0,.85),
        0 0 0 2px rgba(213,168,68,.10);
}

.cc-console-input::placeholder{
    color:#787066;
}

.cc-console-select{
    color-scheme:dark;
    appearance:auto;
}

.cc-console-select option,
.cc-console-select optgroup{
    background:#090909 !important;
    color:#f1f1f1 !important;
}

.cc-console-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    padding-top:2px;
}

.cc-console-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:132px;
    min-height:36px;
    padding:0 15px;
    border:1px solid #c79934 !important;
    border-radius:7px;
    background:linear-gradient(180deg,#e8bb57 0%,#b58021 100%) !important;
    color:#17120a !important;
    font-size:11px;
    font-weight:900;
    letter-spacing:.04em;
    text-transform:uppercase;
    cursor:pointer;
    text-decoration:none;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.30),
        0 4px 10px rgba(0,0,0,.25);
}

.cc-console-button:hover{
    filter:brightness(1.05);
}

.cc-console-button.secondary{
    border:1px solid #5d4b21 !important;
    background:#2b2417 !important;
    color:#e4ba56 !important;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05),
        0 4px 10px rgba(0,0,0,.25);
}

.cc-console-output-shell{
    border:1px solid #3d3219 !important;
    border-radius:8px;
    overflow:hidden;
    background:#050505 !important;
}

.cc-console-output-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    min-height:34px;
    padding:0 11px;
    border-bottom:1px solid #342a16;
    background:#141414 !important;
}

.cc-console-output-name{
    color:#d7d2c9;
    font:700 10px Consolas,"Courier New",monospace;
    letter-spacing:.08em;
    text-transform:uppercase;
}

.cc-console-output-state{
    color:#e6bc58;
    font-size:10px;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
}

.cc-console-output{
    min-height:320px;
    max-height:520px;
    overflow:auto;
    margin:0;
    padding:14px 12px;
    background:#050505 !important;
    color:#ddd8ca !important;
    white-space:pre-wrap;
    overflow-wrap:anywhere;
    font:12px/1.6 Consolas,"Courier New",monospace;
}

.cc-console-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-top:10px;
    color:#948a77;
    font-size:10px;
    letter-spacing:.03em;
}

.cc-console-footer strong{
    color:#d2a84a;
    font-weight:700;
}

@media (max-width:900px){

    .cc-console-form{
        grid-template-columns:1fr;
    }
}

@media (max-width:640px){

    .cc-console-clean{
        padding:12px;
    }

    .cc-console-head{
        flex-direction:column;
        align-items:flex-start;
    }

    .cc-console-actions{
        flex-direction:column;
    }

    .cc-console-button{
        width:100%;
    }

    .cc-console-footer{
        flex-direction:column;
        align-items:flex-start;
    }
}

</style>
</head>
<body>

<div class="cc-console-clean">

    <section class="cc-console-card">
        <div class="cc-console-strip"></div>
        <div class="cc-console-card-inner">
            <div class="cc-console-info">
                <div class="cc-console-icon">&gt;_</div>
                <div>
                    <div class="cc-console-kicker">
                        Secure RemoteAdmin Console
                    </div>
                    <p class="cc-console-text">
                        Commands are submitted server-side directly to OpenSimulator RemoteAdmin.
                        The RemoteAdmin password never leaves the server.
                        This console does not depend on the native grid service generated website pages.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="cc-console-card">
        <div class="cc-console-strip"></div>
        <div class="cc-console-card-inner">

            <div class="cc-console-head">
                <div class="cc-console-head-left">
                    <div class="cc-console-eyebrow">Command Control</div>
                    <h2 class="cc-console-title">Simulator Console</h2>
                </div>
                <div class="cc-console-pill">Server-Side</div>
            </div>

            <form
                id="console-form"
                class="cc-console-form">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?=ag_h($csrf)?>">

                <div class="cc-console-field">
                    <label
                        class="cc-console-label"
                        for="region">
                        Simulator / Region Process
                    </label>

                    <select
                        id="region"
                        name="region"
                        class="cc-console-select"
                        required>

<?php if (!$regionFolders): ?>

                        <option
                            value=""
                            selected
                            disabled>
                            No region processes found
                        </option>

<?php else: ?>

<?php foreach ($regionFolders as $folder): ?>

                        <option
                            value="<?=ag_h($folder)?>"
                            <?=strcasecmp($folder, 'Welcome') === 0 ? 'selected' : ''?>>
                            <?=ag_h($folder)?>
                        </option>

<?php endforeach; ?>

<?php endif; ?>

                    </select>
                </div>

                <div class="cc-console-field">
                    <label
                        class="cc-console-label"
                        for="command">
                        Console Command
                    </label>

                    <input
                        id="command"
                        name="command"
                        type="text"
                        class="cc-console-input"
                        autocomplete="off"
                        maxlength="1200"
                        placeholder="Example: show users"
                        required>
                </div>

                <div class="cc-console-field full">
                    <div class="cc-console-actions">
                        <button
                            class="cc-console-button"
                            type="submit">
                            Send Command
                        </button>

                        <button
                            class="cc-console-button secondary"
                            id="clear-output"
                            type="button">
                            Clear Output
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </section>

    <section class="cc-console-card">
        <div class="cc-console-strip"></div>
        <div class="cc-console-card-inner">

            <div class="cc-console-head">
                <div class="cc-console-head-left">
                    <div class="cc-console-eyebrow">RemoteAdmin Response</div>
                    <h2 class="cc-console-title">Console Output</h2>
                </div>
                <div class="cc-console-pill">Live Session</div>
            </div>

            <div class="cc-console-output-shell">
                <div class="cc-console-output-top">
                    <div class="cc-console-output-name">OpenSimulator Console</div>
                    <div class="cc-console-output-state">Ready</div>
                </div>

                <div
                    id="console-output"
                    class="cc-console-output"
                    role="log"
                    aria-live="polite">PHP Admin Console ready.</div>
            </div>

            <div class="cc-console-footer">
                <span>
                    ADMIN SESSION:
                    <strong><?=ag_h($avatar)?></strong>
                </span>
                <span>
                    <?=ag_grid_name_html()?> CONTROL CENTER
                </span>
            </div>

        </div>
    </section>

</div>

<script>
(function(){

    const form =
        document.getElementById(
            "console-form"
        );

    const output =
        document.getElementById(
            "console-output"
        );

    const clear =
        document.getElementById(
            "clear-output"
        );

    clear.addEventListener(
        "click",
        function(){
            output.textContent = "";
        }
    );

    let pending = false;
    form.addEventListener(
        "submit",
        async function(event){

            event.preventDefault();
            if (pending) return;
            pending = true;

            const formData =
                new FormData(
                    form
                );

            const command =
                String(
                    formData.get(
                        "command"
                    ) || ""
                );

            const region =
                String(
                    formData.get(
                        "region"
                    ) || ""
                );

            output.textContent +=
                "\n> [" +
                region +
                "] " +
                command +
                "\n";

            try{

                const response =
                    await fetch(
                        "/Other/admin-console-api.php",
                        {
                            method:
                                "POST",

                            body:
                                new URLSearchParams(
                                    formData
                                ),

                            credentials:
                                "same-origin"
                        }
                    );

                const data =
                    await response.json();

                if(
                    !response.ok ||
                    !data.ok
                ){
                    throw new Error(
                        data.error ||
                        "Command failed."
                    );
                }

                output.textContent +=
                    data.message +
                    "\n";

                if(data.response){

                    let responseText =
                        String(data.response);

                    /*
                     * Extract only OpenSim's XML-RPC
                     * "message" member.
                     *
                     * Handles both:
                     *
                     * <string>text</string>
                     *
                     * and:
                     *
                     * <string />
                     */
                    const messageMatch =
                        responseText.match(
                            /<member>\s*<name>\s*message\s*<\/name>\s*<value>\s*(?:<string>([\s\S]*?)<\/string>|<string\s*\/>)\s*<\/value>\s*<\/member>/i
                        );

                    if(messageMatch){

                        if(
                            typeof messageMatch[1] ===
                            "string"
                        ){

                            const decoder =
                                document.createElement(
                                    "textarea"
                                );

                            decoder.innerHTML =
                                messageMatch[1];

                            responseText =
                                decoder.value
                                    .replace(
                                        /\r\n/g,
                                        "\n"
                                    )
                                    .replace(
                                        /\r/g,
                                        "\n"
                                    );

                            let responseLines =
                                responseText
                                    .split("\n")
                                    .map(
                                        function(line){

                                            /*
                                             * Remove OpenSim console
                                             * prompt prefixes such as:
                                             *
                                             * Region (Welcome) #
                                             */
                                            line =
                                                line.replace(
                                                    /^Region \([^)]+\) #\s*/,
                                                    ""
                                                );

                                            /*
                                             * Remove trailing padding
                                             * spaces without disturbing
                                             * the actual command data.
                                             */
                                            line =
                                                line.replace(
                                                    /[ \t]+$/g,
                                                    ""
                                                );

                                            return line;
                                        }
                                    );

                            /*
                             * Remove blank lines left behind by
                             * the final OpenSim prompt.
                             */
                            while(
                                responseLines.length > 0 &&
                                responseLines[0].trim() ===
                                    ""
                            ){
                                responseLines.shift();
                            }

                            while(
                                responseLines.length > 0 &&
                                responseLines[
                                    responseLines.length - 1
                                ].trim() ===
                                    ""
                            ){
                                responseLines.pop();
                            }

                            responseText =
                                responseLines.join(
                                    "\n"
                                );
                        }
                        else{

                            responseText =
                                "";
                        }
                    }

                    if(
                        responseText.trim() !==
                        ""
                    ){

                        output.textContent +=
                            responseText +
                            "\n";
                    }
                    else{

                        output.textContent +=
                            "No console output returned.\n";
                    }
                }
                output.scrollTop =
                    output.scrollHeight;
            }
            catch(error){

                output.textContent +=
                    "ERROR: " +
                    error.message +
                    "\n";

                output.scrollTop =
                    output.scrollHeight;
            }
            finally { pending=false; output.textContent=output.textContent.slice(-262144); }
        }
    );

})();
</script>

</body>
</html>





