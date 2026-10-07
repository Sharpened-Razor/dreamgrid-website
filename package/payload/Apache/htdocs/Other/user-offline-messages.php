<?php

/*
 ============================================================
 Grid
 OFFLINE MESSAGES
 ============================================================

 Reads the real OpenSim Offline Message Module V2 queue.

 Database:
   Robust

 Table:
   im_offline

 Access:
   Signed-in Grid user

 IMPORTANT:
   Deleting a message here removes it from OpenSim's offline
   delivery queue. The recipient will no longer receive it.
 ============================================================
*/


require_once __DIR__ . '/core/bootstrap.php';


$session =
    ag_current_session();


if(
    !$session
){

    header(
        'Location: /Other/login.php'
    );

    exit;
}


$avatar =
    trim(
        (string)(
            $session['avatar'] ??
            ''
        )
    );


$principalId =
    trim(
        (string)(
            $session['principalId'] ??
            ''
        )
    );


if(
    $principalId === ''
){

    http_response_code(
        403
    );

    echo
        'Signed account ID is unavailable.';

    exit;
}


function australiaUserOfflineCsrfToken(
    $principalId
){

    $cookie =
        (string)(
            $_COOKIE['dg_session'] ??
            ''
        );

    if(
        $cookie === '' ||
        $principalId === ''
    ){

        return
            '';
    }

    return
        hash(
            'sha256',
            'australia:user-offline-messages:' .
            $principalId .
            ':' .
            $cookie
        );
}


$csrfToken =
    australiaUserOfflineCsrfToken(
        $principalId
    );


if(
    $_SERVER['REQUEST_METHOD'] === 'POST'
){

    $postedCsrf =
        trim(
            (string)(
                $_POST['csrf_token'] ??
                ''
            )
        );

    if(
        $csrfToken === '' ||
        $postedCsrf === '' ||
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ){

        http_response_code(
            403
        );

        echo
            'Invalid request token.';

        exit;
    }
}

$level =
    (int)(
        $session['level'] ??
        0
    );





/*
 ============================================================
 REAL OPENSIM INSTANT MESSAGE DELIVERY
 ============================================================
*/

require_once __DIR__ . '/core/messenger-service.php';


$friendsWarning =
    '';


$friends =
    australiaGetFriends(
        $principalId,
        $friendsWarning
    );


/*
 ============================================================
 SEND MESSAGE TO A FRIEND
 ============================================================
*/

if(
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'friend_send'
){

    $friendKey =
        trim(
            (string)(
                $_POST['friend_key'] ??
                ''
            )
        );


    $friendMessage =
        trim(
            (string)(
                $_POST['friend_message'] ??
                ''
            )
        );


    $messageLength =
        function_exists(
            'mb_strlen'
        )
            ? mb_strlen(
                $friendMessage,
                'UTF-8'
            )
            : strlen(
                $friendMessage
            );


    $selectedFriend =
        null;


    foreach(
        $friends
        as
        $friend
    ){

        if(
            hash_equals(
                (string)$friend['key'],
                $friendKey
            )
        ){

            $selectedFriend =
                $friend;

            break;
        }
    }


    if(
        !$selectedFriend ||
        $friendMessage === '' ||
        $messageLength > 1000
    ){

        header(
            'Location: /Other/user-offline-messages.php?friend=invalid'
        );

    }
    else {

        $targetUrl =
            $selectedFriend['isHypergrid']
                ? (string)$selectedFriend['homeUri']
                : '';


        /*
         * Only allow HTTP/HTTPS home-grid addresses.
         */

        if(
            $targetUrl !== '' &&
            !preg_match(
                '#^https?://#i',
                $targetUrl
            )
        ){

            header(
                'Location: /Other/user-offline-messages.php?friend=failed'
            );

        }
        else {

            $sendError =
                '';


            $delivered =
                australiaSendGridInstantMessage(
                    $principalId,
                    $avatar,
                    (string)$selectedFriend['id'],
                    $friendMessage,
                    $sendError,
                    $targetUrl
                );


            if(
                $delivered
            ){

                header(
                    'Location: /Other/user-offline-messages.php?friend=delivered'
                );

            }
            elseif(
                $sendError === '' &&
                !empty(
                    $selectedFriend['isHypergrid']
                )
            ){

                /*
                 * A foreign grid can return FALSE when live
                 * delivery was not confirmed even though its
                 * Gatekeeper may have accepted/queued the IM.
                 */

                header(
                    'Location: /Other/user-offline-messages.php?friend=submitted'
                );

            }
            elseif(
                $sendError === ''
            ){

                /*
                 * Local Robust returned a valid response but
                 * live delivery was not reported. With the
                 * configured OfflineIM service this normally
                 * means OpenSim handled it as undelivered.
                 */

                header(
                    'Location: /Other/user-offline-messages.php?friend=queued'
                );

            }
            else {

                header(
                    'Location: /Other/user-offline-messages.php?friend=failed'
                );
            }
        }
    }


    return;
}


/*
 ============================================================
 DATABASE
 ============================================================
*/

$dbWarning =
    '';

$messages =
    array();

$deleted =
    isset($_GET['deleted']) &&
    $_GET['deleted'] === '1';


$con =
    null;


try {

    require __DIR__ . '/../MetroMap/includes/config.php';


    $con =
        @mysqli_connect(
            $CONF_db_server,
            $CONF_db_user,
            $CONF_db_pass,
            $CONF_db_database,
            (int)$CONF_db_port
        );


    if(
        !$con
    ){

        $dbWarning =
            'Unable to connect to the Robust database.';
    }
    else {

        @mysqli_set_charset(
            $con,
            'utf8mb4'
        );


        /*
         ====================================================
         RESOLVE LOCAL FRIEND NAMES
         ====================================================
        */

        foreach(
            $friends
            as
            &$friend
        ){

            if(
                !empty(
                    $friend['isHypergrid']
                )
            ){

                continue;
            }


            $friendId =
                (string)$friend['id'];


            $friendStmt =
                @mysqli_prepare(
                    $con,
                    '
                    SELECT FirstName, LastName
                    FROM UserAccounts
                    WHERE PrincipalID = ?
                    LIMIT 1
                    '
                );


            if(
                $friendStmt
            ){

                mysqli_stmt_bind_param(
                    $friendStmt,
                    's',
                    $friendId
                );


                @mysqli_stmt_execute(
                    $friendStmt
                );


                $friendResult =
                    mysqli_stmt_get_result(
                        $friendStmt
                    );


                if(
                    $friendResult &&
                    $friendRow =
                        mysqli_fetch_assoc(
                            $friendResult
                        )
                ){

                    $resolvedName =
                        trim(
                            (string)(
                                ($friendRow['FirstName'] ?? '') .
                                ' ' .
                                ($friendRow['LastName'] ?? '')
                            )
                        );


                    if(
                        $resolvedName !== ''
                    ){

                        $friend['name'] =
                            $resolvedName;
                    }
                }


                if(
                    $friendResult
                ){

                    mysqli_free_result(
                        $friendResult
                    );
                }


                mysqli_stmt_close(
                    $friendStmt
                );
            }
        }


        unset(
            $friend
        );


        foreach(
            $friends
            as
            &$friend
        ){

            if(
                trim(
                    (string)$friend['name']
                ) === ''
            ){

                $friend['name'] =
                    (string)$friend['id'];
            }
        }


        unset(
            $friend
        );


        usort(
            $friends,
            function(
                $a,
                $b
            ){

                return
                    strcasecmp(
                        (string)$a['name'],
                        (string)$b['name']
                    );
            }
        );


        /*
         ====================================================
         REPLY THROUGH OPENSIM
         ====================================================
        */

        if(
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['action']) &&
            $_POST['action'] === 'reply'
        ){

            $recipientId =
                trim(
                    (string)(
                        $_POST['recipient_id'] ??
                        ''
                    )
                );


            $replyMessage =
                trim(
                    (string)(
                        $_POST['reply_message'] ??
                        ''
                    )
                );


            $uuidPattern =
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';


            $messageLength =
                function_exists(
                    'mb_strlen'
                )
                    ? mb_strlen(
                        $replyMessage,
                        'UTF-8'
                    )
                    : strlen(
                        $replyMessage
                    );


            if(
                !preg_match(
                    $uuidPattern,
                    $recipientId
                ) ||
                $replyMessage === '' ||
                $messageLength > 1000
            ){

                header(
                    'Location: /Other/user-offline-messages.php?reply=invalid'
                );

                exit;
            }


            /*
             * Before replying, verify that the requested
             * recipient really is the sender of a message
             * belonging to this signed-in avatar's queue.
             */
            $replyAllowed =
                false;

            $replyAuth =
                @mysqli_prepare(
                    $con,
                    '
                    SELECT ID
                    FROM im_offline
                    WHERE PrincipalID = ?
                      AND FromID = ?
                    LIMIT 1
                    '
                );

            if(
                $replyAuth
            ){

                mysqli_stmt_bind_param(
                    $replyAuth,
                    'ss',
                    $principalId,
                    $recipientId
                );

                if(
                    @mysqli_stmt_execute(
                        $replyAuth
                    )
                ){

                    mysqli_stmt_store_result(
                        $replyAuth
                    );

                    $replyAllowed =
                        mysqli_stmt_num_rows(
                            $replyAuth
                        ) > 0;
                }

                mysqli_stmt_close(
                    $replyAuth
                );
            }

            if(
                !$replyAllowed
            ){

                header(
                    'Location: /Other/user-offline-messages.php?reply=invalid'
                );

                exit;
            }


            /*
             * Remember the current highest queue ID.
             * If Robust returns FALSE because the avatar is
             * offline, we can verify whether OpenSim stored
             * the reply into im_offline.
             */

            $beforeId =
                0;


            $beforeResult =
                @mysqli_query(
                    $con,
                    'SELECT COALESCE(MAX(ID),0) AS MaxID FROM im_offline'
                );


            if(
                $beforeResult
            ){

                $beforeRow =
                    mysqli_fetch_assoc(
                        $beforeResult
                    );


                $beforeId =
                    (int)(
                        $beforeRow['MaxID'] ??
                        0
                    );


                mysqli_free_result(
                    $beforeResult
                );
            }


            $sendError =
                '';


            $delivered =
                australiaSendGridInstantMessage(
                    $principalId,
                    $avatar,
                    $recipientId,
                    $replyMessage,
                    $sendError
                );


            if(
                $delivered
            ){

                header(
                    'Location: /Other/user-offline-messages.php?reply=delivered'
                );

                exit;
            }


            /*
             * OpenSim's HG IM service can return FALSE for
             * immediate delivery while still passing the IM
             * to OfflineIMService. Verify the queue instead
             * of falsely reporting failure.
             */

            $queued =
                false;


            $verify =
                @mysqli_prepare(
                    $con,
                    '
                    SELECT ID, Message
                    FROM im_offline
                    WHERE ID > ?
                      AND PrincipalID = ?
                      AND FromID = ?
                    ORDER BY ID DESC
                    LIMIT 10
                    '
                );


            if(
                $verify
            ){

                mysqli_stmt_bind_param(
                    $verify,
                    'iss',
                    $beforeId,
                    $recipientId,
                    $principalId
                );


                @mysqli_stmt_execute(
                    $verify
                );


                $verifyResult =
                    mysqli_stmt_get_result(
                        $verify
                    );


                if(
                    $verifyResult
                ){

                    while(
                        $verifyRow =
                            mysqli_fetch_assoc(
                                $verifyResult
                            )
                    ){

                        $storedText =
                            australiaOfflineMessageText(
                                $verifyRow['Message'] ??
                                ''
                            );


                        if(
                            trim(
                                $storedText
                            ) ===
                            $replyMessage
                        ){

                            $queued =
                                true;

                            break;
                        }
                    }


                    mysqli_free_result(
                        $verifyResult
                    );
                }


                mysqli_stmt_close(
                    $verify
                );
            }


            if(
                $queued
            ){

                header(
                    'Location: /Other/user-offline-messages.php?reply=queued'
                );

                exit;
            }


            header(
                'Location: /Other/user-offline-messages.php?reply=failed'
            );

            exit;
        }


        /*
         ====================================================
         DELETE ONE QUEUED MESSAGE
         ====================================================
        */

        if(
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['action']) &&
            $_POST['action'] === 'delete'
        ){

            $messageId =
                isset($_POST['message_id'])
                    ? (int)$_POST['message_id']
                    : 0;


            if(
                $messageId > 0
            ){

                $stmt =
                    @mysqli_prepare(
                        $con,
                        'DELETE FROM im_offline WHERE ID = ? AND PrincipalID = ? LIMIT 1'
                    );


                if(
                    $stmt
                ){

                    mysqli_stmt_bind_param(
                        $stmt,
                        'is',
                        $messageId,
                        $principalId
                    );


                    @mysqli_stmt_execute(
                        $stmt
                    );


                    mysqli_stmt_close(
                        $stmt
                    );


                    header(
                        'Location: /Other/user-offline-messages.php?deleted=1'
                    );

                    exit;
                }
            }
        }


        /*
         ====================================================
         LOAD CURRENT OFFLINE QUEUE
         ====================================================
        */

        $sql =
            "
            SELECT
                i.ID,
                i.PrincipalID,
                i.FromID,
                i.Message,
                i.TMStamp,

                TRIM(
                    CONCAT(
                        COALESCE(r.FirstName, ''),
                        ' ',
                        COALESCE(r.LastName, '')
                    )
                ) AS RecipientName,

                TRIM(
                    CONCAT(
                        COALESCE(s.FirstName, ''),
                        ' ',
                        COALESCE(s.LastName, '')
                    )
                ) AS SenderName

            FROM im_offline i

            LEFT JOIN UserAccounts r
                ON r.PrincipalID = i.PrincipalID

            LEFT JOIN UserAccounts s
                ON s.PrincipalID = i.FromID

            WHERE i.PrincipalID = ?

            ORDER BY
                i.TMStamp DESC,
                i.ID DESC

            LIMIT 1000
            ";


        $queueStmt =
            @mysqli_prepare(
                $con,
                $sql
            );

        $result =
            false;

        if(
            $queueStmt
        ){

            mysqli_stmt_bind_param(
                $queueStmt,
                's',
                $principalId
            );

            if(
                @mysqli_stmt_execute(
                    $queueStmt
                )
            ){

                $result =
                    mysqli_stmt_get_result(
                        $queueStmt
                    );
            }
        }


        if(
            $result === false
        ){

            $dbWarning =
                'The OpenSim offline message queue could not be read.';
        }
        else {

            while(
                $row =
                    mysqli_fetch_assoc(
                        $result
                    )
            ){

                $messages[] =
                    $row;
            }


            mysqli_free_result(
                $result
            );
        }
    }

}
catch(
    Throwable $e
){

    $dbWarning =
        'Offline message database service is unavailable.';
}


if(
    $con
){

    @mysqli_close(
        $con
    );
}


/*
 ============================================================
 STATISTICS
 ============================================================
*/

$totalMessages =
    count(
        $messages
    );


$recipientIds =
    array();

$senderIds =
    array();


foreach(
    $messages
    as
    $message
){

    $recipientId =
        trim(
            (string)(
                $message['PrincipalID'] ??
                ''
            )
        );

    $senderId =
        trim(
            (string)(
                $message['FromID'] ??
                ''
            )
        );


    if(
        $recipientId !== ''
    ){

        $recipientIds[
            strtolower(
                $recipientId
            )
        ] =
            true;
    }


    if(
        $senderId !== ''
    ){

        $senderIds[
            strtolower(
                $senderId
            )
        ] =
            true;
    }
}


$totalRecipients =
    count(
        $recipientIds
    );

$totalSenders =
    count(
        $senderIds
    );


function australiaOfflineName(
    $name,
    $uuid
){

    $name =
        trim(
            (string)$name
        );


    if(
        $name !== ''
    ){

        return
            $name;
    }


    return
        (string)$uuid;
}


function australiaOfflineMessageText(
    $value
){

    $value =
        trim(
            (string)$value
        );


    if(
        $value === ''
    ){

        return
            '';
    }


    /*
     * OpenSim Offline Message Module V2 stores the complete
     * GridInstantMessage XML packet in im_offline.Message.
     *
     * Extract only the actual <message> text for display.
     */

    if(
        isset($value[0]) &&
        $value[0] === '<'
    ){

        $previous =
            libxml_use_internal_errors(
                true
            );


        $xml =
            simplexml_load_string(
                $value,
                'SimpleXMLElement',
                LIBXML_NOCDATA |
                LIBXML_NONET
            );


        if(
            $xml !== false &&
            isset($xml->message)
        ){

            $messageText =
                trim(
                    (string)$xml->message
                );


            libxml_clear_errors();

            libxml_use_internal_errors(
                $previous
            );


            if(
                $messageText !== ''
            ){

                return
                    $messageText;
            }
        }


        libxml_clear_errors();

        libxml_use_internal_errors(
            $previous
        );
    }


    /*
     * Fallback:
     * If it is ever a normal plain-text message,
     * display it unchanged.
     */

    return
        $value;
}


function australiaOfflineDate(
    $value
){

    $value =
        trim(
            (string)$value
        );


    if(
        $value === ''
    ){

        return
            'Unknown';
    }


    $time =
        strtotime(
            $value
        );


    if(
        $time === false
    ){

        return
            $value;
    }


    return
        date(
            'd/m/Y g:i A',
            $time
        );
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

<title>
    Grid - Offline Messages
</title>

<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css"
>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    color: #f5f5f5;
    font-family: Arial, Helvetica, sans-serif;

    background-color: #07354c;

    background-image:
        linear-gradient(
            rgba(0, 0, 0, .35),
            rgba(0, 0, 0, .55)
        ),
        url('/Other/images/Water-Texture.png?v=3');

    background-repeat:
        repeat,
        repeat;

    background-size:
        auto,
        700px 700px;

    background-position:
        0 0,
        0 0;
}

.offline-shell {
    width: min(1500px, calc(100% - 32px));
    margin: 32px auto 60px;
}

.offline-title-wrap {
    display: flex;
    align-items: center;
    gap: 16px;
}

.offline-icon {
    font-size: 36px;
    line-height: 1;
}

.offline-title {
    margin: 0;
    color: #e3bd62;
    font-size: clamp(25px, 3vw, 38px);
    letter-spacing: .04em;
}

.offline-subtitle {
    margin: 5px 0 0;
    color: #bcbcbc;
    font-size: 14px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.friends-card {
    margin-bottom: 20px;

    border:
        1px solid
        rgba(218,177,79,.62);

    border-radius: 18px;

    overflow: hidden;

    background:
        rgba(4,6,10,.94);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.52);
}

.friends-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;

    padding:
        19px
        22px;

    border-bottom:
        1px solid
        rgba(218,177,79,.25);
}

.friends-heading-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.friends-heading-icon {
    font-size: 28px;
    line-height: 1;
}

.friends-heading h2 {
    margin: 0;

    color: #e3bd62;

    font-size: 22px;
}

.friends-count {
    color: #aaa;

    font-size: 12px;
    font-weight: 800;

    letter-spacing: .08em;
}

.friends-filter-wrap {
    padding:
        16px
        22px;

    border-bottom:
        1px solid
        rgba(255,255,255,.07);
}

.friends-filter {
    width: 100%;

    padding:
        12px
        14px;

    border:
        1px solid
        rgba(218,177,79,.45);

    border-radius: 9px;

    outline: none;

    color: #fff;

    background:
        rgba(0,0,0,.42);

    font-size: 15px;
}

.friends-filter:focus {
    border-color: #e3bd62;

    box-shadow:
        0 0 0 2px
        rgba(227,189,98,.12);
}

.friends-list {
    max-height: 430px;
    overflow-y: auto;
}

.friend-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;

    padding:
        13px
        22px;

    border-bottom:
        1px solid
        rgba(255,255,255,.065);
}

.friend-row:last-child {
    border-bottom: 0;
}

.friend-row:hover {
    background:
        rgba(255,255,255,.025);
}

.friend-info {
    min-width: 0;
}

.friend-name {
    color: #f3f3f3;

    font-size: 15px;
    font-weight: 800;
}

.friend-grid {
    margin-top: 4px;

    color: #858585;

    font-size: 11px;

    overflow-wrap: anywhere;
}

.friend-local {
    color: #6ea8ff;
}

.friend-hg {
    color: #c886ff;
}

.friend-send-button {
    flex: 0 0 auto;

    min-width: 128px;

    padding:
        9px
        13px;

    border:
        1px solid
        #e0b64d;

    border-radius: 8px;

    color: #111;

    background:
        linear-gradient(
            180deg,
            #f4d77a 0%,
            #d8a931 100%
        );

    cursor: pointer;

    font-weight: 900;
}

.friend-send-button:hover {
    filter: brightness(1.12);
}

.friends-empty {
    padding:
        32px
        22px;

    color: #999;

    text-align: center;
}




/* ==========================================================
   FRIENDS POPUP
   ========================================================== */

.friends-header-button {
    cursor: pointer;
    font-family: Arial, Helvetica, sans-serif;
}




.stats-grid {
    display: grid;
    grid-template-columns:
        repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}

.stat-card {
    padding: 18px;
    border: 1px solid rgba(218,177,79,.52);
    border-radius: 15px;
    background: rgba(5,7,11,.91);
    text-align: center;
}

.stat-value {
    display: block;
    color: #f0c85f;
    font-size: 30px;
    font-weight: 900;
}

.stat-label {
    display: block;
    margin-top: 4px;
    color: #aaa;
    font-size: 12px;
    letter-spacing: .10em;
}

.queue-card {
    border: 1px solid rgba(218,177,79,.62);
    border-radius: 18px;
    overflow: hidden;
    background: rgba(4,6,10,.94);
    box-shadow: 0 20px 50px rgba(0,0,0,.52);
}

.queue-heading {
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(218,177,79,.25);
}

.queue-heading h2 {
    margin: 0;
    color: #e3bd62;
    font-size: 22px;
}

.queue-heading p {
    margin: 7px 0 0;
    color: #aaa;
    line-height: 1.55;
}

.queue-warning {
    margin: 16px 22px;
    padding: 13px 15px;
    border: 1px solid rgba(255,175,65,.42);
    border-radius: 10px;
    background: rgba(114,67,0,.18);
    color: #ffd58a;
    line-height: 1.5;
}

.success-box {
    margin: 16px 22px;
    padding: 12px 15px;
    border: 1px solid rgba(86,214,127,.45);
    border-radius: 10px;
    background: rgba(28,101,51,.2);
    color: #9cf0b7;
}

.error-box {
    margin: 16px 22px;
    padding: 12px 15px;
    border: 1px solid rgba(255,86,86,.45);
    border-radius: 10px;
    background: rgba(120,20,20,.25);
    color: #ffaaaa;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

.message-table {
    width: 100%;
    min-width: 980px;
    border-collapse: collapse;
}

.message-table th {
    padding: 13px 14px;
    border-bottom: 1px solid rgba(218,177,79,.35);
    color: #e6c66c;
    background: rgba(218,177,79,.07);
    font-size: 12px;
    text-align: left;
    letter-spacing: .06em;
}

.message-table td {
    padding: 14px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    vertical-align: top;
}

.message-table tr:hover td {
    background: rgba(255,255,255,.025);
}

.name-main {
    color: #f3f3f3;
    font-weight: 700;
}

.uuid {
    display: block;
    margin-top: 4px;
    color: #737373;
    font-size: 10px;
    word-break: break-all;
}

.message-text {
    max-width: 560px;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    color: #dedede;
    line-height: 1.5;
}

.date-cell {
    white-space: nowrap;
    color: #bdbdbd;
}

.message-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.message-actions form {
    margin: 0;
}

.reply-button {
    padding: 8px 12px;
    border: 1px solid #e0b64d;
    border-radius: 7px;
    color: #111111;
    background:
        linear-gradient(
            180deg,
            #f4d77a 0%,
            #d8a931 100%
        );
    cursor: pointer;
    font-weight: 900;
}

.reply-button:hover {
    filter: brightness(1.12);
}

.aus-reply-overlay {
    position: fixed;
    inset: 0;
    z-index: 100000;

    display: none;
    align-items: center;
    justify-content: center;

    padding: 24px;

    background: rgba(0, 8, 14, .82);

    backdrop-filter: blur(5px);
}

.aus-reply-overlay.show {
    display: flex;
}

.aus-reply-modal {
    width: min(620px, 100%);

    border: 1px solid rgba(226, 178, 52, .95);
    border-radius: 17px;

    background:
        linear-gradient(
            145deg,
            rgba(20, 28, 32, .99),
            rgba(3, 8, 11, .99)
        );

    box-shadow:
        0 24px 70px rgba(0,0,0,.80),
        0 0 25px rgba(218,164,31,.20);

    overflow: hidden;
}

.aus-reply-header {
    padding: 24px 26px 17px;

    border-bottom:
        1px solid rgba(221,170,36,.25);

    text-align: center;
}

.aus-reply-icon {
    margin-bottom: 8px;
    font-size: 40px;
}

.aus-reply-title {
    margin: 0;

    color: #f4c750;

    font-size: 23px;
    font-weight: 900;

    letter-spacing: .045em;
}

.aus-reply-body {
    padding: 22px 28px 14px;
}

.aus-reply-to-label {
    color: #999;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .09em;
}

.aus-reply-to {
    margin-top: 5px;
    margin-bottom: 18px;

    color: #ffffff;

    font-size: 18px;
    font-weight: 800;
}

.aus-reply-textarea {
    width: 100%;
    min-height: 150px;

    resize: vertical;

    padding: 14px;

    border:
        1px solid
        rgba(218,177,79,.55);

    border-radius: 10px;

    outline: none;

    color: #ffffff;

    background:
        rgba(0,0,0,.48);

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 15px;
    line-height: 1.5;
}

.aus-reply-textarea:focus {
    border-color: #e4bc55;

    box-shadow:
        0 0 0 2px
        rgba(228,188,85,.14);
}

.aus-reply-count {
    margin-top: 7px;

    color: #777;

    font-size: 11px;

    text-align: right;
}

.aus-reply-actions {
    display: flex;
    justify-content: center;
    gap: 12px;

    padding:
        10px
        28px
        27px;
}

.aus-reply-cancel,
.aus-reply-send {
    min-width: 150px;
    min-height: 44px;

    padding: 10px 18px;

    border-radius: 9px;

    cursor: pointer;

    font-size: 13px;
    font-weight: 900;
}

.aus-reply-cancel {
    border: 1px solid rgba(210,210,210,.45);

    color: #fff;

    background:
        linear-gradient(
            180deg,
            #3b4449,
            #20272b
        );
}

.aus-reply-send {
    border: 1px solid #f1c451;

    color: #111;

    background:
        linear-gradient(
            180deg,
            #ffe17d,
            #daa72b
        );
}

.aus-reply-cancel:hover,
.aus-reply-send:hover {
    filter: brightness(1.12);
}


.delete-button {
    padding: 8px 12px;
    border: 1px solid #b74747;
    border-radius: 7px;
    color: #ffd2d2;
    background: rgba(118,23,23,.48);
    cursor: pointer;
    font-weight: 800;
}

.delete-button:hover {
    background: rgba(155,31,31,.68);
}

.empty {
    padding: 45px 22px;
    color: #aaa;
    text-align: center;
}

.footer-note {
    padding: 16px 22px 20px;
    color: #777;
    font-size: 12px;
    line-height: 1.5;
}

@media (max-width: 800px) {

    .friends-card {
    margin-bottom: 20px;

    border:
        1px solid
        rgba(218,177,79,.62);

    border-radius: 18px;

    overflow: hidden;

    background:
        rgba(4,6,10,.94);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.52);
}

.friends-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;

    padding:
        19px
        22px;

    border-bottom:
        1px solid
        rgba(218,177,79,.25);
}

.friends-heading-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.friends-heading-icon {
    font-size: 28px;
    line-height: 1;
}

.friends-heading h2 {
    margin: 0;

    color: #e3bd62;

    font-size: 22px;
}

.friends-count {
    color: #aaa;

    font-size: 12px;
    font-weight: 800;

    letter-spacing: .08em;
}

.friends-filter-wrap {
    padding:
        16px
        22px;

    border-bottom:
        1px solid
        rgba(255,255,255,.07);
}

.friends-filter {
    width: 100%;

    padding:
        12px
        14px;

    border:
        1px solid
        rgba(218,177,79,.45);

    border-radius: 9px;

    outline: none;

    color: #fff;

    background:
        rgba(0,0,0,.42);

    font-size: 15px;
}

.friends-filter:focus {
    border-color: #e3bd62;

    box-shadow:
        0 0 0 2px
        rgba(227,189,98,.12);
}

.friends-list {
    max-height: 430px;
    overflow-y: auto;
}

.friend-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;

    padding:
        13px
        22px;

    border-bottom:
        1px solid
        rgba(255,255,255,.065);
}

.friend-row:last-child {
    border-bottom: 0;
}

.friend-row:hover {
    background:
        rgba(255,255,255,.025);
}

.friend-info {
    min-width: 0;
}

.friend-name {
    color: #f3f3f3;

    font-size: 15px;
    font-weight: 800;
}

.friend-grid {
    margin-top: 4px;

    color: #858585;

    font-size: 11px;

    overflow-wrap: anywhere;
}

.friend-local {
    color: #6ea8ff;
}

.friend-hg {
    color: #c886ff;
}

.friend-send-button {
    flex: 0 0 auto;

    min-width: 128px;

    padding:
        9px
        13px;

    border:
        1px solid
        #e0b64d;

    border-radius: 8px;

    color: #111;

    background:
        linear-gradient(
            180deg,
            #f4d77a 0%,
            #d8a931 100%
        );

    cursor: pointer;

    font-weight: 900;
}

.friend-send-button:hover {
    filter: brightness(1.12);
}

.friends-empty {
    padding:
        32px
        22px;

    color: #999;

    text-align: center;
}




/* ==========================================================
   FRIENDS POPUP
   ========================================================== */

.friends-header-button {
    cursor: pointer;
    font-family: Arial, Helvetica, sans-serif;
}




.stats-grid {
        grid-template-columns: 1fr;
    }
}


/* ==========================================================
   Grid DELETE CONFIRMATION
   ========================================================== */

.aus-delete-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;

    display: none;
    align-items: center;
    justify-content: center;

    padding: 24px;

    background:
        rgba(0, 8, 14, .78);

    backdrop-filter:
        blur(5px);
}

.aus-delete-overlay.show {
    display: flex;
}

.aus-delete-modal {
    width: min(520px, 100%);

    overflow: hidden;

    border:
        1px solid
        rgba(226, 178, 52, .92);

    border-radius: 16px;

    background:
        linear-gradient(
            145deg,
            rgba(20, 28, 32, .99),
            rgba(3, 8, 11, .99)
        );

    box-shadow:
        0 24px 70px rgba(0, 0, 0, .78),
        0 0 24px rgba(218, 164, 31, .18),
        inset 0 0 25px rgba(255, 255, 255, .025);

    text-align: center;
}

.aus-delete-modal-header {
    padding:
        25px
        25px
        17px;

    border-bottom:
        1px solid
        rgba(221, 170, 36, .25);
}

.aus-delete-modal-icon {
    margin-bottom: 10px;

    font-size: 42px;
    line-height: 1;
}

.aus-delete-modal-title {
    margin: 0;

    color: #f4c750;

    font-size: 23px;
    font-weight: 900;

    letter-spacing: .045em;

    text-shadow:
        0 2px 5px rgba(0,0,0,.7);
}

.aus-delete-modal-body {
    padding:
        20px
        30px
        24px;
}

.aus-delete-modal-body p {
    margin:
        0
        auto;

    max-width: 430px;

    color: #d8dde0;

    font-size: 15px;
    line-height: 1.6;
}

.aus-delete-warning {
    display: block;

    margin-top: 13px;

    color: #ffcf66;

    font-weight: 800;
}

.aus-delete-actions {
    display: flex;
    justify-content: center;
    gap: 12px;

    padding:
        0
        25px
        26px;
}

.aus-modal-button {
    min-width: 145px;
    min-height: 44px;

    padding:
        10px
        18px;

    border-radius: 9px;

    cursor: pointer;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 13px;
    font-weight: 900;

    letter-spacing: .025em;
}

.aus-modal-cancel {
    border:
        1px solid
        rgba(210, 210, 210, .45);

    color: #ffffff;

    background:
        linear-gradient(
            180deg,
            #3b4449,
            #20272b
        );

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.4);
}

.aus-modal-delete {
    border:
        1px solid
        #f1c451;

    color: #111111;

    background:
        linear-gradient(
            180deg,
            #ffe17d,
            #daa72b
        );

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.45);
}

.aus-modal-cancel:hover,
.aus-modal-delete:hover {
    filter:
        brightness(1.12);
}

@media (max-width: 520px) {

    .aus-delete-actions {
        flex-direction: column;
    }

    .aus-modal-button {
        width: 100%;
    }
}

</style>


<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-message-inline-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-messenger-v6-real-flags.css?v=20260918-real-flags-v1">

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

</head>


<body>


<div class="offline-shell">


    <?php
$siteHeaderKicker = "ACCOUNT";
$siteHeaderTitle = "OFFLINE MESSAGES";
$siteHeaderRole = "MEMBER";
$siteHeaderLevel = $level ?? 1;
$siteHeaderButton = "REFRESH";
$siteHeaderLink = "/Other/user-offline-messages.php";
require_once __DIR__ . "/includes/site-header.php";
?>



    <!-- ====================================================
         FRIENDS
         ==================================================== -->


    <section
        id="ausFriendsPanel"
        hidden
        aria-hidden="true"
    >
        <?php foreach ($friends as $friend): ?>

            <?php
            $friendDisplay =
                trim(
                    (string)$friend['name']
                );

            if(
                $friendDisplay === ''
            ){
                $friendDisplay =
                    (string)$friend['id'];
            }
            ?>

            <span
                data-friend-key="<?php echo htmlspecialchars((string)$friend['key'], ENT_QUOTES, 'UTF-8'); ?>"
                data-friend-name="<?php echo htmlspecialchars($friendDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                data-friend-grid="<?php echo htmlspecialchars((string)($friend['gridLabel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            ></span>

        <?php endforeach; ?>
    </section>



    <section class="stats-grid">

        <div class="stat-card">

            <span class="stat-value">
                <?php echo $totalMessages; ?>
            </span>

            <span class="stat-label">
                QUEUED MESSAGES
            </span>

        </div>


        <div class="stat-card">

            <span class="stat-value">
                <?php echo $totalRecipients; ?>
            </span>

            <span class="stat-label">
                RECIPIENTS
            </span>

        </div>


        <div class="stat-card">

            <span class="stat-value">
                <?php echo $totalSenders; ?>
            </span>

            <span class="stat-label">
                SENDERS
            </span>

        </div>

    </section>



    <section class="queue-card">

        <div class="queue-heading">

            <h2>
                OPENSIM OFFLINE DELIVERY QUEUE
            </h2>

            <p>
                These are messages currently waiting for your avatar in
                OpenSim's Offline Message Module V2 queue.
            </p>

        </div>


        <?php

        $replyState =
            trim(
                (string)(
                    $_GET['reply'] ??
                    ''
                )
            );

        ?>


        <?php if ($replyState === 'delivered'): ?>

            <div class="success-box">
                <strong>MESSAGE SENT.</strong>
                The reply was delivered through OpenSim.
            </div>

        <?php elseif ($replyState === 'queued'): ?>

            <div class="success-box">
                <strong>MESSAGE QUEUED.</strong>
                The recipient is offline, so OpenSim saved the
                reply for delivery when they log in.
            </div>

        <?php elseif ($replyState === 'invalid'): ?>

            <div class="error-box">
                Reply was not sent. Enter a message of 1 to 1000 characters.
            </div>

        <?php elseif ($replyState === 'failed'): ?>

            <div class="error-box">
                OpenSim could not deliver or queue the reply.
            </div>

        <?php endif; ?>


        <div class="queue-warning">

            <strong>IMPORTANT:</strong>

            Removing a message here permanently deletes it
            from OpenSim's offline delivery queue.
            The recipient will not receive that message.

        </div>


        <?php if ($deleted): ?>

            <div class="success-box">
                Offline message removed from the delivery queue.
            </div>

        <?php endif; ?>


        <?php if ($dbWarning !== ''): ?>

            <div class="error-box">

                <?php
                echo htmlspecialchars(
                    $dbWarning,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>

        <?php endif; ?>


        <?php if ($totalMessages > 0): ?>

            <div class="table-wrap">

                <table class="message-table">

                    <thead>

                    <tr>
                        <th>ID</th>
                        <th>RECIPIENT</th>
                        <th>SENDER</th>
                        <th>DATE / TIME</th>
                        <th>MESSAGE</th>
                        <th>ACTION</th>
                    </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($messages as $message): ?>

                        <tr>

                            <td>
                                <?php
                                echo (int)(
                                    $message['ID'] ??
                                    0
                                );
                                ?>
                            </td>


                            <td>

                                <span class="name-main">

                                    <?php
                                    echo htmlspecialchars(
                                        australiaOfflineName(
                                            $message['RecipientName'] ?? '',
                                            $message['PrincipalID'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>

                                <span class="uuid">

                                    <?php
                                    echo htmlspecialchars(
                                        (string)(
                                            $message['PrincipalID'] ??
                                            ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <span class="name-main">

                                    <?php
                                    echo htmlspecialchars(
                                        australiaOfflineName(
                                            $message['SenderName'] ?? '',
                                            $message['FromID'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>

                                <span class="uuid">

                                    <?php
                                    echo htmlspecialchars(
                                        (string)(
                                            $message['FromID'] ??
                                            ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>

                            </td>


                            <td class="date-cell">

                                <?php
                                echo htmlspecialchars(
                                    australiaOfflineDate(
                                        $message['TMStamp'] ??
                                        ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </td>


                            <td>

                                <div class="message-text">

                                    <?php
                                    echo htmlspecialchars(
                                        australiaOfflineMessageText(
                                            $message['Message'] ??
                                            ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </div>

                            </td>


                            <td>

                                <div class="message-actions">

                                    <button
                                        class="reply-button"
                                        type="button"
                                        data-recipient-id="<?php echo htmlspecialchars((string)($message['FromID'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-recipient-name="<?php echo htmlspecialchars(australiaOfflineName($message['SenderName'] ?? '', $message['FromID'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="australiaOpenReply(this);"
                                    >
                                        REPLY
                                    </button>


                                    <form
                                        method="post"
                                        action="/Other/user-offline-messages.php"
                                        onsubmit="return australiaConfirmDelete(event, this);"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="message_id"
                                        value="<?php echo (int)($message['ID'] ?? 0); ?>"
                                    >

                                    <button
                                        class="delete-button"
                                        type="submit"
                                    >
                                        DELETE
                                    </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>


        <div class="footer-note">

            OpenSim table:
            <strong>Robust.im_offline</strong>

            &nbsp; &#8226; &nbsp;

            This page displays the current delivery queue,
            not a permanent message-history archive.

        </div>

    </section>


</div>



<!-- ========================================================
     Grid FRIEND MESSAGE
     ======================================================== -->

<div id="ausFriendOverlay" hidden aria-hidden="true">
<form method="post" action="/Other/user-offline-messages.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="action" value="friend_send">
    <input id="ausFriendKey" type="hidden" name="friend_key" value="">
    <span id="ausFriendName"></span>
    <textarea id="ausFriendMessage" name="friend_message" maxlength="1000" required></textarea>
    <button class="aus-friend-send" type="submit" hidden>SEND</button>
</form>
</div>



<!-- ========================================================
     Grid REPLY MESSAGE
     ======================================================== -->

<div
    id="ausReplyOverlay"
    class="aus-reply-overlay"
    aria-hidden="true"
>

    <div
        class="aus-reply-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ausReplyTitle"
    >

        <form
            method="post"
            action="/Other/user-offline-messages.php"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
            >

            <input
                type="hidden"
                name="action"
                value="reply"
            >

            <input
                id="ausReplyRecipientId"
                type="hidden"
                name="recipient_id"
                value=""
            >


            <div class="aus-reply-header">

                <div class="aus-reply-icon"><img src="/Other/assets/icons/sentinel/chat.png" alt="" aria-hidden="true" style="width:40px;height:40px;object-fit:contain;"></div>

                <h2
                    id="ausReplyTitle"
                    class="aus-reply-title"
                >
                    REPLY TO MESSAGE
                </h2>

            </div>


            <div class="aus-reply-body">

                <div class="aus-reply-to-label">
                    REPLY TO
                </div>

                <div
                    id="ausReplyRecipientName"
                    class="aus-reply-to"
                >
                </div>


                <textarea
                    id="ausReplyMessage"
                    class="aus-reply-textarea"
                    name="reply_message"
                    maxlength="1000"
                    required
                    placeholder="Type your reply..."
                ></textarea>


                <div class="aus-reply-count">

                    <span id="ausReplyCount">
                        0
                    </span>

                    / 1000

                </div>

            </div>


            <div class="aus-reply-actions">

                <button
                    id="ausReplyCancel"
                    class="aus-reply-cancel"
                    type="button"
                >
                    CANCEL
                </button>


                <button
                    class="aus-reply-send"
                    type="submit"
                >
                    SEND MESSAGE
                </button>

            </div>

        </form>

    </div>

</div>



<!-- ========================================================
     Grid DELETE CONFIRMATION
     ======================================================== -->

<div
    id="ausDeleteOverlay"
    class="aus-delete-overlay"
    aria-hidden="true"
>

    <div
        class="aus-delete-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ausDeleteTitle"
    >

        <div class="aus-delete-modal-header">

            <div class="aus-delete-modal-icon"><img src="/Other/assets/icons/sentinel/delete.png" alt="" aria-hidden="true" style="width:42px;height:42px;object-fit:contain;"></div>

            <h2
                id="ausDeleteTitle"
                class="aus-delete-modal-title"
            >
                DELETE OFFLINE MESSAGE?
            </h2>

        </div>


        <div class="aus-delete-modal-body">

            <p>

                This will permanently remove this message
                from OpenSim's offline delivery queue.

                <span class="aus-delete-warning">
                    The recipient will not receive this message.
                </span>

            </p>

        </div>


        <div class="aus-delete-actions">

            <button
                id="ausDeleteCancel"
                class="aus-modal-button aus-modal-cancel"
                type="button"
            >
                CANCEL
            </button>


            <button
                id="ausDeleteConfirm"
                class="aus-modal-button aus-modal-delete"
                type="button"
            >
                DELETE MESSAGE
            </button>

        </div>

    </div>

</div>


<script>

/* ==========================================================
   FRIENDS POPUP
   ========================================================== */



/*
 * If we just sent a friend a message, reopen the Friends
 * window so the delivery result is immediately visible.
 */

try {

    const australiaFriendParams =
        new URLSearchParams(
            window.location.search
        );


    if(
        australiaFriendParams.has(
            'friend'
        )
    ){

        australiaOpenFriendsList();
    }

}
catch(error){
}


/* ==========================================================
   FRIENDS FILTER + SEND MESSAGE
   ========================================================== */

const australiaFriendsFilter =
    document.getElementById(
        'friendsFilter'
    );


if(
    australiaFriendsFilter
){

    australiaFriendsFilter.addEventListener(
        'input',
        function(){

            const value =
                this.value
                    .trim()
                    .toLowerCase();


            document
                .querySelectorAll(
                    '.friend-row'
                )
                .forEach(
                    function(row){

                        const search =
                            (
                                row.dataset.friendSearch ||
                                ''
                            ).toLowerCase();


                        row.style.display =
                            search.includes(
                                value
                            )
                                ? ''
                                : 'none';
                    }
                );
        }
    );
}


function australiaOpenFriendMessage(
    button
){
    const key = document.getElementById('ausFriendKey');
    const name = document.getElementById('ausFriendName');
    const message = document.getElementById('ausFriendMessage');

    if(
        !button ||
        !key ||
        !name ||
        !message
    ){
        return;
    }

    key.value =
        button.dataset.friendKey ||
        '';

    name.textContent =
        button.dataset.friendName ||
        'Friend';

    message.value =
        '';
}



let australiaPendingDeleteForm = null;


function australiaOpenReply(
    button
){

    const overlay =
        document.getElementById(
            'ausReplyOverlay'
        );

    const recipientId =
        document.getElementById(
            'ausReplyRecipientId'
        );

    const recipientName =
        document.getElementById(
            'ausReplyRecipientName'
        );

    const message =
        document.getElementById(
            'ausReplyMessage'
        );


    if(
        !overlay ||
        !recipientId ||
        !recipientName ||
        !message
    ){

        return;
    }


    recipientId.value =
        button.dataset.recipientId || '';


    recipientName.textContent =
        button.dataset.recipientName ||
        button.dataset.recipientId ||
        'Unknown';


    message.value =
        '';


    australiaUpdateReplyCount();


    overlay.classList.add(
        'show'
    );


    overlay.setAttribute(
        'aria-hidden',
        'false'
    );


    message.focus();
}


function australiaCloseReply(){

    const overlay =
        document.getElementById(
            'ausReplyOverlay'
        );


    if(overlay){

        overlay.classList.remove(
            'show'
        );

        overlay.setAttribute(
            'aria-hidden',
            'true'
        );
    }
}


function australiaUpdateReplyCount(){

    const message =
        document.getElementById(
            'ausReplyMessage'
        );

    const count =
        document.getElementById(
            'ausReplyCount'
        );


    if(
        message &&
        count
    ){

        count.textContent =
            message.value.length;
    }
}


document
    .getElementById(
        'ausReplyCancel'
    )
    .addEventListener(
        'click',
        australiaCloseReply
    );


document
    .getElementById(
        'ausReplyMessage'
    )
    .addEventListener(
        'input',
        australiaUpdateReplyCount
    );


document
    .getElementById(
        'ausReplyOverlay'
    )
    .addEventListener(
        'click',
        function(event){

            if(
                event.target === this
            ){

                australiaCloseReply();
            }
        }
    );




function australiaConfirmDelete(
    event,
    form
){

    if(event){
        event.preventDefault();
    }


    australiaPendingDeleteForm =
        form;


    const overlay =
        document.getElementById(
            'ausDeleteOverlay'
        );


    if(!overlay){

        return false;
    }


    overlay.classList.add(
        'show'
    );


    overlay.setAttribute(
        'aria-hidden',
        'false'
    );


    const cancelButton =
        document.getElementById(
            'ausDeleteCancel'
        );


    if(cancelButton){

        cancelButton.focus();
    }


    return false;
}


function australiaCloseDelete(){

    const overlay =
        document.getElementById(
            'ausDeleteOverlay'
        );


    if(overlay){

        overlay.classList.remove(
            'show'
        );


        overlay.setAttribute(
            'aria-hidden',
            'true'
        );
    }


    australiaPendingDeleteForm =
        null;
}


document
    .getElementById(
        'ausDeleteCancel'
    )
    .addEventListener(
        'click',
        australiaCloseDelete
    );


document
    .getElementById(
        'ausDeleteConfirm'
    )
    .addEventListener(
        'click',
        function(){

            if(
                australiaPendingDeleteForm
            ){

                const form =
                    australiaPendingDeleteForm;


                australiaPendingDeleteForm =
                    null;


                form.submit();
            }
        }
    );


document
    .getElementById(
        'ausDeleteOverlay'
    )
    .addEventListener(
        'click',
        function(event){

            if(
                event.target === this
            ){

                australiaCloseDelete();
            }
        }
    );


document.addEventListener(
    'keydown',
    function(event){

        if(
            event.key === 'Escape'
        ){

            australiaCloseFriendsList();
            australiaCloseReply();
            australiaCloseDelete();
        }
    }
);

</script>

<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>

<script id="ag-friends-popup-cleanup-v1">
(function () {
    "use strict";

    function cleanFriendsPopup() {

        var panel =
            document.getElementById(
                "ausFriendsPanel"
            );

        if (!panel) {
            return;
        }

        panel
            .querySelectorAll(
                ".friend-row *"
            )
            .forEach(function (node) {

                if (
                    node.children.length !== 0
                ) {
                    return;
                }

                var text =
                    String(
                        node.textContent || ""
                    )
                    .replace(/\s+/g, " ")
                    .trim()
                    .toUpperCase();

                if (
                    text === String(<?php echo json_encode(function_exists('ag_grid_name') ? ag_grid_name() : '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>).toUpperCase()
                ) {

                    node.remove();
                }
            });
    }

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            cleanFriendsPopup
        );

    } else {

        cleanFriendsPopup();
    }

    window.addEventListener(
        "load",
        cleanFriendsPopup
    );

    window.setTimeout(
        cleanFriendsPopup,
        150
    );
})();
</script>
<script src="/Other/assets/js/ag-messenger-v6-no-flags.js?v=20260918-no-flags-v1" defer></script>
</body>
</html>




