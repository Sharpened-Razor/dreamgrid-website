<?php

/*
 * ============================================================
 * Grid - USER LINKED REGIONS V3
 * ============================================================
 *
 * VIEW:
 *     Any signed-in Grid account.
 *
 * ADD LINK:
 *     Grid Owner / Admin only.
 *
 * REMOVE LINK:
 *     Grid Owner / Admin only.
 *
 * Uses OpenSim's native console command:
 *
 * link-region <Xloc> <Yloc> <ServerURI> [<RemoteRegionName>]
 *
 * Hyperlink database flag:
 *     512
 *
 * ============================================================
 */

require_once __DIR__ . '/core/bootstrap.php';


if (!function_exists('lrDreamGridRoot')) {

    function lrDreamGridRoot(): string
    {
        static $root = null;

        if (
            is_string($root)
            &&
            $root !== ''
        ) {
            return $root;
        }

        $probe =
            __DIR__;

        while (true) {

            $hasSettings =
                is_file(
                    $probe .
                    DIRECTORY_SEPARATOR .
                    'Settings.ini'
                );

            $hasOpenSim =
                is_dir(
                    $probe .
                    DIRECTORY_SEPARATOR .
                    'Opensim'
                );

            if ($hasSettings && $hasOpenSim) {

                $resolved =
                    realpath(
                        $probe
                    );

                $root =
                    $resolved !== false
                    ? $resolved
                    : $probe;

                return $root;
            }

            $parent =
                dirname(
                    $probe
                );

            if ($parent === $probe) {
                break;
            }

            $probe =
                $parent;
        }

        throw new RuntimeException(
            'DreamGrid root could not be located.'
        );
    }
}


$session =
    ag_current_session();


if (!$session) {

    header(
        'Location: /Other/login.php'
    );

    exit;
}


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}


$avatar =
    (string)(
        $session['avatar'] ??
        'Grid User'
    );


$level = (int) ag_user_level($session);
$isAdmin = ag_is_admin($session);

$agRoleLevel = function_exists('ag_user_level')
    ? (int) ag_user_level($session)
    : (int)($session['level'] ?? 0);

$agRoleIsAdmin = function_exists('ag_is_admin')
    ? (bool) ag_is_admin($session)
    : ($agRoleLevel >= 200);

$agRoleLabel =
    ($agRoleLevel >= 250)
        ? "GRID OWNER"
        : ($agRoleIsAdmin ? "ADMIN" : "USER");


/*
 * ============================================================
 * CSRF
 * ============================================================
 */

if (
    empty(
        $_SESSION[
            'australia_linked_regions_csrf'
        ]
    )
) {

    $_SESSION[
        'australia_linked_regions_csrf'
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );
}


$csrf =
    (string)
    $_SESSION[
        'australia_linked_regions_csrf'
    ];


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function lrHtml(
    $value
) {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES |
        ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function lrFlash(
    string $type,
    string $title,
    string $message
): void {

    $_SESSION[
        'australia_linked_regions_flash'
    ] = [

        'type' =>
            $type,

        'title' =>
            $title,

        'message' =>
            $message
    ];
}


function lrRedirect(): void { $location = '/Other/user-linked-regions.php'; $params = []; if (!empty($_GET['from'])) { $params['from'] = (string)$_GET['from']; } if (!empty($_GET['sid'])) { $params['sid'] = (string)$_GET['sid']; } if ($params) { $location .= '?' . http_build_query($params); } header('Location: ' . $location); exit; }


/*
 * ============================================================
 * ROBUST MYSQL CONNECTION
 * ============================================================
 */

function lrRobustConnection(): array
{
    return ag_web_database();
}


/*
 * ============================================================
 * LOAD HYPERLINKS
 * ============================================================
 */

function lrLoadLinks(): array
{

    $config =
        lrRobustConnection();


    if (!class_exists('mysqli')) {

        throw new RuntimeException(
            'PHP MySQL support is unavailable.'
        );
    }


    mysqli_report(
        MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
    );


    $db =
        new mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['database'],
            $config['port']
        );


    $db->set_charset(
        'utf8mb4'
    );


    $result =
        $db->query(
            '
            SELECT
                uuid,
                regionName,
                locX,
                locY,
                serverIP,
                serverPort,
                serverURI,
                owner_uuid,
                flags,
                last_seen,
                sizeX,
                sizeY
            FROM regions
            WHERE (flags & 512) = 512
            ORDER BY regionName
            '
        );


    $rows =
        [];


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $rows[] =
            $row;
    }


    $result->free();

    $db->close();


    return
        $rows;
}


/*
 * ============================================================
 * FIND ONE VERIFIED HYPERGRID LINK
 * ============================================================
 */

function lrFindLink(
    string $uuid
): ?array {

    if (
        !preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $uuid
        )
    ) {

        return null;
    }


    $config =
        lrRobustConnection();


    mysqli_report(
        MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
    );


    $db =
        new mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['database'],
            $config['port']
        );


    $db->set_charset(
        'utf8mb4'
    );


    $statement =
        $db->prepare(
            '
            SELECT
                uuid,
                regionName,
                locX,
                locY,
                serverURI,
                flags
            FROM regions
            WHERE uuid = ?
              AND (flags & 512) = 512
            LIMIT 1
            '
        );


    $statement->bind_param(
        's',
        $uuid
    );


    $statement->execute();


    $result =
        $statement->get_result();


    $row =
        $result->fetch_assoc();


    $statement->close();

    $db->close();


    if (!$row) {

        return null;
    }


    return $row;
}

/*
 * ============================================================
 * REMOTE ADMIN
 * ============================================================
 */

function lrRemoteAdmin(): array
{

    $ini =
        lrDreamGridRoot() .
        DIRECTORY_SEPARATOR .
        'Opensim' .
        DIRECTORY_SEPARATOR .
        'bin' .
        DIRECTORY_SEPARATOR .
        'Regions' .
        DIRECTORY_SEPARATOR .
        'Welcome' .
        DIRECTORY_SEPARATOR .
        'Opensim.ini';


    if (!is_file($ini)) {

        throw new RuntimeException(
            'Welcome Opensim.ini was not found.'
        );
    }


    $text =
        @file_get_contents(
            $ini
        );


    if (!is_string($text)) {

        throw new RuntimeException(
            'Welcome Opensim.ini could not be read.'
        );
    }


    if (
        !preg_match(
            '/^\s*access_password\s*=\s*(\S+)\s*$/mi',
            $text,
            $passwordMatch
        )
    ) {

        throw new RuntimeException(
            'RemoteAdmin access_password was not found.'
        );
    }


    if (
        !preg_match(
            '/^\s*port\s*=\s*(\d+)\s*$/mi',
            $text,
            $portMatch
        )
    ) {

        throw new RuntimeException(
            'RemoteAdmin port was not found.'
        );
    }


    $password =
        trim(
            (string)
            $passwordMatch[1]
        );


    $port =
        (int)
        $portMatch[1];


    if ($password === '') {

        throw new RuntimeException(
            'RemoteAdmin access_password is blank.'
        );
    }


    if ($port <= 0) {

        throw new RuntimeException(
            'RemoteAdmin port is invalid.'
        );
    }


    return [

        'password' =>
            $password,

        'port' =>
            $port
    ];
}


/*
 * ============================================================
 * RUN OPENSIM CONSOLE COMMAND
 * ============================================================
 */
function lrConsoleCommand(
    string $command
): array {

    /*
     * ============================================================
     * LOCALHOST ROBUST CONSOLE BRIDGE
     * ============================================================
     *
     * Apache/PHP sends an authenticated localhost-only request
     * to the bridge running in the same Windows session as Robust.
     *
     * The bridge then injects the native OpenSim console command
     * directly into Robust.exe.
     * ============================================================
     */

    $tokenFile =
        dirname(
            __DIR__,
            2
        ) .
        DIRECTORY_SEPARATOR .
        'robust-console-bridge.token';


    if (!is_file($tokenFile)) {

        return [
            'success' =>
                false,

            'message' =>
                'Robust bridge token file was not found.'
        ];
    }


    $token =
        trim(
            (string)
            @file_get_contents(
                $tokenFile
            )
        );


    if ($token === '') {

        return [
            'success' =>
                false,

            'message' =>
                'Robust bridge token is blank.'
        ];
    }


    $command =
        trim(
            $command
        );


    if ($command === '') {

        return [
            'success' =>
                false,

            'message' =>
                'Robust console command is blank.'
        ];
    }


    $errno =
        0;

    $errstr =
        '';


    $socket =
        @fsockopen(
            ag_web_bridge_endpoint()['host'],
            ag_web_bridge_endpoint()['port'],
            $errno,
            $errstr,
            5.0
        );


    if (!is_resource($socket)) {

        return [
            'success' =>
                false,

            'message' =>
                'Could not connect to Robust console bridge: ' .
                $errstr .
                ' (' .
                (string)$errno .
                ')'
        ];
    }


    stream_set_timeout(
        $socket,
        5
    );


    $request =
        $token .
        "\t" .
        base64_encode(
            $command
        ) .
        "\n";


    $written =
        @fwrite(
            $socket,
            $request
        );


    if ($written === false) {

        @fclose(
            $socket
        );

        return [
            'success' =>
                false,

            'message' =>
                'Could not send command to Robust console bridge.'
        ];
    }


    $response =
        @fgets(
            $socket,
            8192
        );


    @fclose(
        $socket
    );


    $response =
        trim(
            (string)$response
        );


    if (
        preg_match(
            '/^SUCCESS\|(.+)$/',
            $response,
            $match
        )
    ) {

        return [
            'success' =>
                true,

            'message' =>
                trim(
                    $match[1]
                )
        ];
    }


    if (
        preg_match(
            '/^FAILED\|(.+)$/',
            $response,
            $match
        )
    ) {

        return [
            'success' =>
                false,

            'message' =>
                trim(
                    $match[1]
                )
        ];
    }


    return [
        'success' =>
            false,

        'message' =>
            (
                $response !== ''
                ?
                'Unexpected Robust bridge response: ' .
                $response
                :
                'Robust console bridge returned no response.'
            )
    ];
}


/*
 * ============================================================
 * INPUT VALIDATION
 * ============================================================
 */

function lrValidateCoordinate(
    $value,
    string $label
): int {

    $value =
        trim(
            (string)$value
        );


    if (
        !preg_match(
            '/^\d{1,5}$/',
            $value
        )
    ) {

        throw new RuntimeException(
            $label .
            ' must be a whole grid coordinate between 0 and 65535.'
        );
    }


    $coordinate =
        (int)$value;


    if (
        $coordinate < 0
        ||
        $coordinate > 65535
    ) {

        throw new RuntimeException(
            $label .
            ' must be between 0 and 65535.'
        );
    }


    return
        $coordinate;
}


function lrValidateServerUri(
    $value
): string {

    $value =
        trim(
            (string)$value
        );


    if ($value === '') {

        throw new RuntimeException(
            'Server URI is required.'
        );
    }


    if (
        strlen(
            $value
        ) > 240
    ) {

        throw new RuntimeException(
            'Server URI is too long.'
        );
    }


    if (
        preg_match(
            '/[\x00-\x20\x7F";|&<>]/',
            $value
        )
    ) {

        throw new RuntimeException(
            'Server URI contains unsupported characters.'
        );
    }


    $parts =
        @parse_url(
            $value
        );


    if (
        !is_array(
            $parts
        )
    ) {

        throw new RuntimeException(
            'Server URI is invalid.'
        );
    }


    $scheme =
        strtolower(
            (string)(
                $parts['scheme'] ??
                ''
            )
        );


    if (
        $scheme !== 'http'
        &&
        $scheme !== 'https'
    ) {

        throw new RuntimeException(
            'Server URI must begin with http:// or https://'
        );
    }


    if (
        empty(
            $parts['host']
        )
    ) {

        throw new RuntimeException(
            'Server URI must contain a valid host name.'
        );
    }


    if (
        isset(
            $parts['user']
        )
        ||
        isset(
            $parts['pass']
        )
    ) {

        throw new RuntimeException(
            'Server URI cannot contain embedded login credentials.'
        );
    }


    return
        $value;
}


function lrValidateRegionName(
    $value
): string {

    $value =
        trim(
            (string)$value
        );


    if ($value === '') {

        return
            '';
    }


    if (
        strlen(
            $value
        ) > 128
    ) {

        throw new RuntimeException(
            'Remote Region Name is too long.'
        );
    }


    if (
        preg_match(
            '/[\x00-\x1F\x7F";|&<>]/',
            $value
        )
    ) {

        throw new RuntimeException(
            'Remote Region Name contains unsupported characters.'
        );
    }


    return
        $value;
}


/*
 * ============================================================
 * POST - ADD LINK
 * ============================================================
 */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') ===
    'POST'
) {

    try {

        if (!$isAdmin) {

            throw new RuntimeException(
                'Grid Owner access is required to add a linked region.'
            );
        }


        $postedCsrf =
            (string)(
                $_POST['csrf'] ??
                ''
            );


        if (
            $postedCsrf === ''
            ||
            !hash_equals(
                $csrf,
                $postedCsrf
            )
        ) {

            throw new RuntimeException(
                'Security token expired. Reload the page and try again.'
            );
        }


        $action =
            trim(
                (string)(
                    $_POST['action'] ??
                    ''
                )
            );


        if (
            $action !== 'add'
            &&
            $action !== 'remove'
        ) {

            throw new RuntimeException(
                'Unknown Linked Regions action.'
            );
        }


        /*
         * ====================================================
         * REMOVE LINKED REGION
         * ====================================================
         */

        if ($action === 'remove') {

            $uuid =
                trim(
                    (string)(
                        $_POST['uuid'] ??
                        ''
                    )
                );


            $link =
                lrFindLink(
                    $uuid
                );


            if (!$link) {

                throw new RuntimeException(
                    'The selected Hypergrid link no longer exists.'
                );
            }


            $localName =
                trim(
                    (string)(
                        $link['regionName'] ??
                        ''
                    )
                );


            if ($localName === '') {

                throw new RuntimeException(
                    'The selected Hypergrid link has no valid local name.'
                );
            }


            /*
             * The name comes from the verified OpenSim
             * hyperlink record, not from browser input.
             */

            $safeLocalName =
                str_replace(
                    '"',
                    '',
                    $localName
                );


            if ($safeLocalName === '') {

                throw new RuntimeException(
                    'The linked-region name is invalid.'
                );
            }


            $command =
                'unlink-region "' .
                $safeLocalName .
                '"';


            $result =
                lrConsoleCommand(
                    $command
                );


            if (
                empty(
                    $result['success']
                )
            ) {

                throw new RuntimeException(
                    'OpenSim did not accept the unlink-region command.'
                );
            }


            $consoleMessage =
                trim(
                    (string)(
                        $result['message'] ??
                        ''
                    )
                );


            if (
                $consoleMessage !== ''
                &&
                preg_match(
                    '/\b(error|failed|failure|unable|invalid|exception)\b/i',
                    $consoleMessage
                )
            ) {

                throw new RuntimeException(
                    "OpenSim reported:\n" .
                    $consoleMessage
                );
            }


            /*
             * Allow GridService time to update the
             * persistent hyperlink record.
             */

            usleep(
                500000
            );


            $stillExists =
                lrFindLink(
                    $uuid
                );


            if ($stillExists) {

                $warning =
                    'OpenSim accepted the unlink-region command, but the Hypergrid link is still present.';


                if ($consoleMessage !== '') {

                    $warning .=
                        "\n\nOpenSim:\n" .
                        $consoleMessage;
                }


                lrFlash(
                    'warning',
                    'REMOVE COMMAND SENT',
                    $warning
                );
            }
            else {

                $success =
                    'The Hypergrid link "' .
                    $localName .
                    '" was removed successfully.';


                if ($consoleMessage !== '') {

                    $success .=
                        "\n\nOpenSim:\n" .
                        $consoleMessage;
                }


                lrFlash(
                    'success',
                    'LINKED REGION REMOVED',
                    $success
                );
            }


            lrRedirect();
        }


        /*
         * ====================================================
         * ADD LINKED REGION
         * ====================================================
         */

        $xloc =
            lrValidateCoordinate(
                $_POST['xloc'] ??
                '',
                'Location X'
            );


        $yloc =
            lrValidateCoordinate(
                $_POST['yloc'] ??
                '',
                'Location Y'
            );


        $serverUri =
            lrValidateServerUri(
                $_POST['server_uri'] ??
                ''
            );


        $remoteRegionName =
            lrValidateRegionName(
                $_POST[
                    'remote_region_name'
                ] ??
                ''
            );


        /*
         * ----------------------------------------------------
         * PREVENT LINKING ON TOP OF AN EXISTING LOCAL/LINKED
         * REGION LOCATION.
         * ----------------------------------------------------
         */

        $config =
            lrRobustConnection();


        mysqli_report(
            MYSQLI_REPORT_ERROR |
            MYSQLI_REPORT_STRICT
        );


        $db =
            new mysqli(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                $config['port']
            );


        $db->set_charset(
            'utf8mb4'
        );


        $meterX =
            $xloc *
            256;


        $meterY =
            $yloc *
            256;


        $statement =
            $db->prepare(
                '
                SELECT
                    regionName,
                    flags
                FROM regions
                WHERE locX = ?
                  AND locY = ?
                LIMIT 1
                '
            );


        $statement->bind_param(
            'ii',
            $meterX,
            $meterY
        );


        $statement->execute();


        $existingResult =
            $statement->get_result();


        $existing =
            $existingResult->fetch_assoc();


        $statement->close();

        $db->close();


        if ($existing) {

            throw new RuntimeException(
                'Grid location ' .
                $xloc .
                ', ' .
                $yloc .
                ' is already occupied by "' .
                (
                    $existing['regionName'] ??
                    'another region'
                ) .
                '".'
            );
        }


        /*
         * ----------------------------------------------------
         * BUILD EXACT COMMAND FOR THIS OPENSIM BUILD
         * ----------------------------------------------------
         */

        $command =
            'link-region ' .
            $xloc .
            ' ' .
            $yloc .
            ' ' .
            $serverUri;


        if ($remoteRegionName !== '') {

            $command .=
                ' "' .
                $remoteRegionName .
                '"';
        }


        $result =
            lrConsoleCommand(
                $command
            );


        if (
            empty(
                $result['success']
            )
        ) {

            $bridgeMessage =
                trim(
                    (string)(
                        $result['message'] ??
                        ''
                    )
                );

            throw new RuntimeException(
                'OpenSim did not accept the link-region command.' .
                (
                    $bridgeMessage !== ''
                    ?
                    "\n\nRobust bridge:\n" .
                    $bridgeMessage
                    :
                    ''
                )
            );
        }


        $consoleMessage =
            trim(
                (string)(
                    $result['message'] ??
                    ''
                )
            );


        /*
         * ----------------------------------------------------
         * CHECK FOR OBVIOUS CONSOLE FAILURE TEXT
         * ----------------------------------------------------
         */

        if (
            $consoleMessage !== ''
            &&
            preg_match(
                '/\b(error|failed|failure|unable|invalid|exception)\b/i',
                $consoleMessage
            )
        ) {

            throw new RuntimeException(
                "OpenSim reported:\n" .
                $consoleMessage
            );
        }


        /*
         * ----------------------------------------------------
         * ALLOW GRID SERVICE A MOMENT TO REGISTER THE LINK
         * ----------------------------------------------------
         */

        usleep(
            400000
        );


        $linksAfter =
            lrLoadLinks();


        $found =
            false;


        $foundName =
            '';


        foreach (
            $linksAfter
            as
            $link
        ) {

            $storedX =
                intdiv(
                    (int)(
                        $link['locX'] ??
                        0
                    ),
                    256
                );


            $storedY =
                intdiv(
                    (int)(
                        $link['locY'] ??
                        0
                    ),
                    256
                );


            if (
                $storedX === $xloc
                &&
                $storedY === $yloc
            ) {

                $found =
                    true;


                $foundName =
                    (string)(
                        $link['regionName'] ??
                        ''
                    );


                break;
            }
        }


        if ($found) {

            $successText =
                'OpenSim created the Hypergrid link at ' .
                $xloc .
                ', ' .
                $yloc .
                '.';


            if ($foundName !== '') {

                $successText .=
                    "\n\nLinked region: " .
                    $foundName;
            }


            if ($consoleMessage !== '') {

                $successText .=
                    "\n\nOpenSim:\n" .
                    $consoleMessage;
            }


            lrFlash(
                'success',
                'LINKED REGION ADDED',
                $successText
            );
        }
        else {

            $warningText =
                'OpenSim accepted the link-region command, but the new Hypergrid link was not detected in the regions table yet.';


            if ($consoleMessage !== '') {

                $warningText .=
                    "\n\nOpenSim:\n" .
                    $consoleMessage;
            }


            lrFlash(
                'warning',
                'LINK COMMAND SENT',
                $warningText
            );
        }


        lrRedirect();
    }
    catch (Throwable $exception) {

        lrFlash(
            'error',
            'LINKED REGION NOT ADDED',
            $exception->getMessage()
        );


        lrRedirect();
    }
}


/*
 * ============================================================
 * LOAD PAGE DATA
 * ============================================================
 */

$links =
    [];


$error =
    '';


try {

    $links =
        lrLoadLinks();
}
catch (Throwable $exception) {

    $error =
        $exception->getMessage();
}


$flash =
    null;


if (
    isset(
        $_SESSION[
            'australia_linked_regions_flash'
        ]
    )
    &&
    is_array(
        $_SESSION[
            'australia_linked_regions_flash'
        ]
    )
) {

    $flash =
        $_SESSION[
            'australia_linked_regions_flash'
        ];


    unset(
        $_SESSION[
            'australia_linked_regions_flash'
        ]
    );
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Linked Regions - Control Center</title>
<style id="linked-regions-control-center-v1">*{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Arial,Helvetica,sans-serif;background:#0b0d0f;color:#eef1f3}.lrcc-page{height:100vh;min-height:0;overflow:hidden;background:radial-gradient(circle at top,#202428 0,#111416 38%,#090b0d 100%);padding:10px}.lrcc-shell{width:100%;height:100%;max-width:none;margin:0;display:flex;flex-direction:column;min-height:0}.lrcc-hero,.lrcc-panel,.lrcc-notice,.lrcc-admin-only,.lrcc-info{border:1px solid rgba(184,137,46,.72);background:linear-gradient(145deg,#191d20,#111416);box-shadow:0 8px 26px rgba(0,0,0,.32)}.lrcc-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:18px 20px;border-radius:10px}.lrcc-title-row{display:flex;align-items:center;gap:14px;min-width:0}.lrcc-title-icon{display:flex;align-items:center;justify-content:center;width:46px;height:46px;flex:0 0 46px;border:1px solid #b8892e;border-radius:8px;background:#111416;color:#e5b84e}.lrcc-svg{width:24px;height:24px;fill:currentColor}.lrcc-heading h1{margin:0;color:#e7b94f;font-size:23px;font-weight:900;letter-spacing:.055em}.lrcc-heading p{margin:6px 0 0;color:#adb5ba;font-size:13px}.lrcc-account{display:flex;align-items:center;gap:14px;flex:0 0 auto}.lrcc-user{text-align:right;color:#aab3b8;font-size:10px;font-weight:800;letter-spacing:.04em}.lrcc-user strong{display:block;margin-top:3px;color:#fff;font-size:13px;letter-spacing:0}.lrcc-role{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:155px;min-height:52px;padding:7px 13px;border:1px solid #5d7f94;border-radius:8px;background:#10181d}.lrcc-role strong{color:#e7b94f;font-size:12px;font-weight:900}.lrcc-role span{margin-top:4px;color:#fff;font-size:10px;font-weight:800}.lrcc-hero-right{display:flex;align-items:center;justify-content:flex-end;gap:12px;flex:0 0 auto}.lrcc-button{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:9px 16px;border-radius:7px;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:900;letter-spacing:.04em;cursor:pointer;transition:border-color .15s ease,background .15s ease,transform .15s ease}.lrcc-button:hover{transform:translateY(-1px)}.lrcc-button-primary{border:1px solid #c89b3a;background:#b8892e;color:#101010}.lrcc-button-primary:hover{background:#d1a548}.lrcc-button-secondary{border:1px solid #566068;background:#1c2226;color:#eef2f4}.lrcc-button-secondary:hover{border-color:#b8892e;background:#242a2e}.lrcc-button-danger{border:1px solid #8f3f3f;background:#492323;color:#ffdede}.lrcc-button-danger:hover{background:#602929}.lrcc-panel{margin-top:8px;padding:12px;border-radius:10px}.lrcc-shell>section.lrcc-panel:last-of-type{flex:1 1 auto;min-height:0;overflow:auto}.lrcc-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:10px;padding-bottom:9px;border-bottom:1px solid rgba(184,137,46,.32)}.lrcc-panel-title h2{margin:0;color:#e7b94f;font-size:15px;font-weight:900;letter-spacing:.045em}.lrcc-panel-title p{margin:5px 0 0;color:#98a2a8;font-size:12px;line-height:1.45}.lrcc-count{display:inline-flex;align-items:center;justify-content:center;min-width:86px;min-height:30px;padding:6px 11px;border:1px solid #665329;border-radius:6px;background:#17191b;color:#e7b94f;font-size:10px;font-weight:900;white-space:nowrap}.lrcc-form-grid{display:grid;grid-template-columns:minmax(180px,1fr) minmax(180px,1fr);gap:8px 10px}.lrcc-field{min-width:0}.lrcc-field-full{grid-column:1/-1}.lrcc-field label{display:block;margin:0 0 6px;color:#d7ac4b;font-size:10px;font-weight:900;letter-spacing:.055em}.lrcc-field input{display:block;width:100%;height:36px;padding:7px 10px;border:1px solid #454b4f;border-radius:7px;outline:none;background:#0c0f11;color:#fff;font-size:12px}.lrcc-field input:focus{border-color:#b8892e;box-shadow:0 0 0 2px rgba(184,137,46,.12)}.lrcc-help{margin-top:4px;color:#78838a;font-size:10px;line-height:1.4}.lrcc-form-actions{display:flex;justify-content:flex-end;margin-top:10px}.lrcc-notice,.lrcc-admin-only,.lrcc-info{margin-top:14px;padding:13px 15px;border-radius:8px;color:#d9dfe2;font-size:12px;line-height:1.5}.lrcc-notice strong{display:block;margin-bottom:4px;color:#e7b94f}.lrcc-notice-success{border-color:#48765b}.lrcc-notice-error{border-color:#8a4040}.lrcc-notice-warning{border-color:#9a792e}.lrcc-admin-only{border-color:#63542e;color:#c9c1a8}.lrcc-link-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:10px}.lrcc-card{min-width:0;border:1px solid #363c40;border-radius:9px;background:#0f1214;overflow:hidden}.lrcc-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 14px;border-bottom:1px solid rgba(184,137,46,.28);background:#171a1c}.lrcc-card-name{min-width:0;color:#e7b94f;font-size:13px;font-weight:900;overflow-wrap:anywhere}.lrcc-card-tag{flex:0 0 auto;padding:4px 7px;border:1px solid #5f512b;border-radius:5px;color:#bda45d;font-size:8px;font-weight:900;letter-spacing:.04em}.lrcc-details{padding:6px 14px}.lrcc-row{display:grid;grid-template-columns:120px minmax(0,1fr);gap:12px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.055)}.lrcc-row:last-child{border-bottom:0}.lrcc-label{color:#758087;font-size:9px;font-weight:900;letter-spacing:.05em}.lrcc-value{min-width:0;color:#dce2e5;font-size:11px;overflow-wrap:anywhere}.lrcc-remove{padding:12px 14px;border-top:1px solid rgba(184,137,46,.24);text-align:right}.lrcc-empty{padding:28px 18px;border:1px dashed #4a4f52;border-radius:8px;text-align:center;color:#8d989e}.lrcc-empty strong{display:block;margin-bottom:6px;color:#d7ac4b;font-size:13px}.lrcc-info{color:#8e999f}.lrcc-info strong{color:#d7ac4b}@media(max-width:850px){.lrcc-page{height:auto;min-height:100vh;overflow:auto}.lrcc-shell{height:auto}.lrcc-shell>section.lrcc-panel:last-of-type{overflow:visible}.lrcc-hero{align-items:flex-start;flex-direction:column}.lrcc-hero-right{width:100%;justify-content:space-between}.lrcc-account{width:100%;justify-content:space-between}.lrcc-user{text-align:left}.lrcc-form-grid{grid-template-columns:1fr}.lrcc-field-full{grid-column:auto}.lrcc-link-grid{grid-template-columns:1fr}}@media(max-width:520px){.lrcc-page{padding:8px}.lrcc-account{align-items:flex-start;flex-direction:column}.lrcc-row{grid-template-columns:1fr;gap:3px}.lrcc-panel-head{flex-direction:column}.lrcc-card-head{align-items:flex-start;flex-direction:column}}</style>
</head>
<body class="lrcc-page">
<main class="lrcc-shell">
<header class="lrcc-hero"><div class="lrcc-title-row"><div class="lrcc-title-icon"><?=function_exists("ag_icon") ? ag_icon("link",null,"lrcc-svg") : ""?></div><div class="lrcc-heading"><h1>LINKED REGIONS</h1><p>Manage permanent Hypergrid destination links from the Control Center.</p></div></div><div class="lrcc-hero-right"><a class="lrcc-button lrcc-button-secondary" href="/Other/user-linked-regions.php?from=admin<?=!empty($_GET["sid"]) ? "&amp;sid=".rawurlencode((string)$_GET["sid"]) : ""?>">REFRESH</a><div class="lrcc-account"><div class="lrcc-user">SIGNED IN AS<strong><?=lrHtml($avatar)?></strong></div><div class="lrcc-role"><strong><?=lrHtml($agRoleLabel)?></strong><span>USER LEVEL <?=lrHtml($agRoleLevel)?></span></div></div></div></header>

<?php if ($flash): ?><div class="lrcc-notice lrcc-notice-<?=lrHtml($flash["type"] ?? "warning")?>"><strong><?=lrHtml($flash["title"] ?? "LINKED REGIONS")?></strong><?=lrHtml($flash["message"] ?? "")?></div><?php endif; ?>
<?php if ($isAdmin): ?>
<section class="lrcc-panel"><div class="lrcc-panel-head"><div class="lrcc-panel-title"><h2>ADD LINKED REGION</h2><p>Create a permanent Hypergrid destination using the native OpenSim link-region command.</p></div></div><form method="post" action="" autocomplete="off"><input type="hidden" name="csrf" value="<?=lrHtml($csrf)?>"><input type="hidden" name="action" value="add"><div class="lrcc-form-grid"><div class="lrcc-field"><label for="xloc">LOCATION X</label><input id="xloc" name="xloc" type="number" min="0" max="65535" step="1" required placeholder="Example: 1000"><div class="lrcc-help">Local map X coordinate where the Hypergrid link will appear.</div></div><div class="lrcc-field"><label for="yloc">LOCATION Y</label><input id="yloc" name="yloc" type="number" min="0" max="65535" step="1" required placeholder="Example: 1000"><div class="lrcc-help">Local map Y coordinate where the Hypergrid link will appear.</div></div><div class="lrcc-field lrcc-field-full"><label for="server_uri">SERVER URI</label><input id="server_uri" name="server_uri" type="text" maxlength="240" required placeholder="Enter the remote grid Hypergrid URI"><div class="lrcc-help">Enter the remote grid Gatekeeper or Hypergrid URI.</div></div><div class="lrcc-field lrcc-field-full"><label for="remote_region_name">REMOTE REGION NAME - OPTIONAL</label><input id="remote_region_name" name="remote_region_name" type="text" maxlength="128" placeholder="Example: Welcome"><div class="lrcc-help">Leave blank to use the remote grid default destination.</div></div></div><div class="lrcc-form-actions"><button class="lrcc-button lrcc-button-primary" type="submit">ADD LINKED REGION</button></div></form></section>
<?php else: ?><div class="lrcc-admin-only">Linked-region creation is restricted to the Grid Owner and administrators.</div><?php endif; ?>
<section class="lrcc-panel"><div class="lrcc-panel-head"><div class="lrcc-panel-title"><h2>LINKED HYPERGRID REGIONS</h2><p>Permanent remote destinations currently registered with OpenSim.</p></div><span class="lrcc-count"><?=count($links)?> LINKED</span></div>
<?php if ($error !== ""): ?><div class="lrcc-notice lrcc-notice-error"><?=lrHtml($error)?></div><?php elseif (count($links) === 0): ?><div class="lrcc-empty"><strong>NO LINKED REGIONS</strong>No persistent Hypergrid destination links are currently configured.</div><?php else: ?><div class="lrcc-link-grid"><?php foreach ($links as $link): ?>
<?php $locX=(int)($link["locX"] ?? 0);$locY=(int)($link["locY"] ?? 0);$gridX=intdiv($locX,256);$gridY=intdiv($locY,256);$address=trim((string)($link["serverURI"] ?? ""));if($address === ""){$address=trim((string)($link["serverIP"] ?? ""));$port=(int)($link["serverPort"] ?? 0);if($address !== "" && $port > 0){$address.=":".$port;}} ?>
<article class="lrcc-card"><div class="lrcc-card-head"><div class="lrcc-card-name"><?=lrHtml($link["regionName"] ?? "Linked Region")?></div><div class="lrcc-card-tag">HYPERGRID LINK</div></div><div class="lrcc-details"><div class="lrcc-row"><div class="lrcc-label">ADDRESS</div><div class="lrcc-value"><?=lrHtml($address)?></div></div><div class="lrcc-row"><div class="lrcc-label">MAP LOCATION</div><div class="lrcc-value"><?=lrHtml($gridX)?>, <?=lrHtml($gridY)?></div></div><div class="lrcc-row"><div class="lrcc-label">REGION UUID</div><div class="lrcc-value"><?=lrHtml($link["uuid"] ?? "")?></div></div><div class="lrcc-row"><div class="lrcc-label">OWNER UUID</div><div class="lrcc-value"><?=lrHtml($link["owner_uuid"] ?? "")?></div></div><div class="lrcc-row"><div class="lrcc-label">FLAGS</div><div class="lrcc-value"><?=lrHtml($link["flags"] ?? "")?></div></div></div><?php if ($isAdmin): ?><div class="lrcc-remove"><form method="post" action="" onsubmit="return confirm(&quot;REMOVE THIS LINKED REGION?\n\nThis removes the permanent Hypergrid link.\n\nContinue?&quot;);"><input type="hidden" name="csrf" value="<?=lrHtml($csrf)?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="uuid" value="<?=lrHtml($link["uuid"] ?? "")?>"><button class="lrcc-button lrcc-button-danger" type="submit">REMOVE LINKED REGION</button></form></div><?php endif; ?></article>
<?php endforeach; ?></div><?php endif; ?>
<div class="lrcc-info"><strong>LINKED REGIONS</strong> are permanent Hypergrid destination links stored by OpenSim. Normal local regions are intentionally excluded from this list.</div></section>
</main>
</body>
</html>


