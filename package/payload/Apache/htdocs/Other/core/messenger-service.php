<?php
require_once __DIR__ . '/dreamgrid-env.php';

function australiaImXml(
    $value
){

    return
        htmlspecialchars(
            (string)$value,
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );
}


function australiaImUuid()
{

    $data =
        random_bytes(
            16
        );

    $data[6] =
        chr(
            (ord($data[6]) & 0x0f) |
            0x40
        );

    $data[8] =
        chr(
            (ord($data[8]) & 0x3f) |
            0x80
        );


    return
        vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(
                bin2hex(
                    $data
                ),
                4
            )
        );
}


function australiaSendGridInstantMessage(
    $fromId,
    $fromName,
    $toId,
    $message,
    &$error,
    $targetUrl = ''
){

    $error =
        '';


    $zero =
        '00000000-0000-0000-0000-000000000000';


    $fields =
        array(

            'from_agent_id' =>
                (string)$fromId,

            'from_agent_session' =>
                $zero,

            'to_agent_id' =>
                (string)$toId,

            'im_session_id' =>
                australiaImUuid(),

            'timestamp' =>
                (string)time(),

            'from_agent_name' =>
                (string)$fromName,

            'message' =>
                (string)$message,

            /*
             * InstantMessageDialog.MessageFromAgent = byte 0.
             *
             * OpenSim's XML-RPC connector expects dialog and
             * offline bytes encoded as Base64 strings.
             */

            'dialog' =>
                base64_encode(
                    chr(0)
                ),

            'from_group' =>
                'FALSE',

            'offline' =>
                base64_encode(
                    chr(0)
                ),

            'parent_estate_id' =>
                '0',

            'position_x' =>
                '0',

            'position_y' =>
                '0',

            'position_z' =>
                '0',

            'region_id' =>
                $zero,

            'binary_bucket' =>
                '',

            'region_handle' =>
                '0'
        );


    $members =
        '';


    foreach(
        $fields
        as
        $name =>
        $value
    ){

        $members .=
            '<member>' .
                '<name>' .
                    australiaImXml(
                        $name
                    ) .
                '</name>' .
                '<value><string>' .
                    australiaImXml(
                        $value
                    ) .
                '</string></value>' .
            '</member>';
    }


    $request =
        '<?xml version="1.0"?>' .
        '<methodCall>' .
            '<methodName>grid_instant_message</methodName>' .
            '<params>' .
                '<param>' .
                    '<value>' .
                        '<struct>' .
                            $members .
                        '</struct>' .
                    '</value>' .
                '</param>' .
            '</params>' .
        '</methodCall>';


    /*
     * Robust public port on this grid.
     * Localhost keeps this server-side only.
     */

    $url =
        trim(
            (string)$targetUrl
        );


    if(
        $url === ''
    ){

        $url =
            ag_web_local_base(ag_dg_robust_port()) . '/';
    }
    else {

        $url =
            rtrim(
                $url,
                '/'
            ) .
            '/';
    }


    $context =
        stream_context_create(
            array(
                'http' =>
                    array(
                        'method' =>
                            'POST',

                        'header' =>
                            "Content-Type: text/xml\r\n" .
                            "User-Agent: DreamGridWebsiteMessenger\r\n",

                        'content' =>
                            $request,

                        'timeout' =>
                            10,

                        'ignore_errors' =>
                            true
                    )
            )
        );


    $response =
        @file_get_contents(
            $url,
            false,
            $context
        );


    if(
        $response === false
    ){

        $error =
            'Robust Instant Message service could not be contacted.';

        return
            false;
    }


    $previous =
        libxml_use_internal_errors(
            true
        );


    $xml =
        simplexml_load_string(
            $response
        );


    if(
        $xml === false
    ){

        libxml_clear_errors();

        libxml_use_internal_errors(
            $previous
        );

        $error =
            'Robust returned an invalid Instant Message response.';

        return
            false;
    }


    $successful =
        false;


    if(
        isset(
            $xml->params->param->value->struct->member
        )
    ){

        foreach(
            $xml->params->param->value->struct->member
            as
            $member
        ){

            if(
                trim(
                    (string)$member->name
                ) ===
                'success'
            ){

                $value =
                    trim(
                        (string)$member->value->string
                    );


                $successful =
                    strtoupper(
                        $value
                    ) ===
                    'TRUE';

                break;
            }
        }
    }


    libxml_clear_errors();

    libxml_use_internal_errors(
        $previous
    );


    return
        $successful;
}


/*
 ============================================================
 OPENSIM FRIENDS SERVICE
 ============================================================
*/

function australiaParseFriend(
    $raw,
    $myFlags = 0,
    $theirFlags = 0
){

    $raw =
        trim(
            (string)$raw
        );


    if(
        $raw === ''
    ){

        return
            null;
    }


    $parts =
        explode(
            ';',
            $raw
        );


    $friendId =
        trim(
            (string)(
                $parts[0] ??
                ''
            )
        );


    if(
        !preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $friendId
        )
    ){

        return
            null;
    }


    $homeUri =
        trim(
            (string)(
                $parts[1] ??
                ''
            )
        );


    $name =
        trim(
            (string)(
                $parts[2] ??
                ''
            )
        );


    $isHypergrid =
        $homeUri !== '';


    $gridLabel =
        '';


    if(
        $isHypergrid
    ){

        $host =
            parse_url(
                $homeUri,
                PHP_URL_HOST
            );


        $port =
            parse_url(
                $homeUri,
                PHP_URL_PORT
            );


        if(
            $host
        ){

            $gridLabel =
                '@' .
                $host;


            if(
                $port
            ){

                $gridLabel .=
                    ':' .
                    $port;
            }
        }
        else {

            $gridLabel =
                '@' .
                preg_replace(
                    '#^https?://#i',
                    '',
                    rtrim(
                        $homeUri,
                        '/'
                    )
                );
        }
    }


    return
        array(

            'id' =>
                $friendId,

            'raw' =>
                $raw,

            /*
             * SHA256 key lets the browser identify the
             * friendship without exposing any HG secret
             * which may exist later in the UUI string.
             */

            'key' =>
                hash(
                    'sha256',
                    $raw
                ),

            'name' =>
                $name,

            'homeUri' =>
                $homeUri,

            'gridLabel' =>
                $gridLabel,

            'isHypergrid' =>
                $isHypergrid,

            'myFlags' =>
                (int)$myFlags,

            'theirFlags' =>
                (int)$theirFlags
        );
}


function australiaGetFriends(
    $principalId,
    &$warning
){

    $warning =
        '';


    $body =
        http_build_query(
            array(
                'METHOD' =>
                    'getfriends',

                'PRINCIPALID' =>
                    (string)$principalId
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );


    $context =
        stream_context_create(
            array(
                'http' =>
                    array(
                        'method' =>
                            'POST',

                        'header' =>
                            "Content-Type: application/x-www-form-urlencoded\r\n" .
                            "Content-Length: " .
                            strlen(
                                $body
                            ) .
                            "\r\n",

                        'content' =>
                            $body,

                        'timeout' =>
                            6,

                        'ignore_errors' =>
                            true
                    )
            )
        );


    /*
     * DreamGrid Robust private port.
     */

    $response =
        @file_get_contents(
            ag_dg_private_robust_base() . '/friends',
            false,
            $context
        );


    if(
        $response === false ||
        trim(
            $response
        ) === ''
    ){

        $warning =
            'OpenSim Friends Service is unavailable.';

        return
            array();
    }


    $previous =
        libxml_use_internal_errors(
            true
        );


    $xml =
        simplexml_load_string(
            $response,
            'SimpleXMLElement',
            LIBXML_NOCDATA |
            LIBXML_NONET
        );


    if(
        $xml === false
    ){

        libxml_clear_errors();

        libxml_use_internal_errors(
            $previous
        );

        $warning =
            'OpenSim returned an invalid Friends Service response.';

        return
            array();
    }


    $friends =
        array();


    foreach(
        $xml->children()
        as
        $nodeName =>
        $node
    ){

        if(
            stripos(
                (string)$nodeName,
                'friend'
            ) !== 0
        ){

            continue;
        }


        $friendRaw =
            trim(
                (string)(
                    $node->Friend ??
                    ''
                )
            );


        $myFlags =
            (int)(
                $node->MyFlags ??
                0
            );


        $theirFlags =
            (int)(
                $node->TheirFlags ??
                0
            );


        $friend =
            australiaParseFriend(
                $friendRaw,
                $myFlags,
                $theirFlags
            );


        if(
            $friend
        ){

            $friends[] =
                $friend;
        }
    }


    libxml_clear_errors();

    libxml_use_internal_errors(
        $previous
    );


    return
        $friends;
}
