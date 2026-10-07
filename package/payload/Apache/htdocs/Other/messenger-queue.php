<?php
declare(strict_types=1);

require_once
    __DIR__ .
    '/core/bootstrap.php';


header(
    'Content-Type: application/json; charset=UTF-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate'
);


function ag_queue_json(
    array $data,
    int $status = 200
): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}
function ag_queue_message_text($value): string
{
    $value = trim((string)$value);

    if($value === '' || $value[0] !== '<'){
        return $value;
    }

    $previous = libxml_use_internal_errors(true);

    $xml = simplexml_load_string(
        $value,
        'SimpleXMLElement',
        LIBXML_NOCDATA | LIBXML_NONET
    );

    $text = $value;

    if($xml !== false && isset($xml->message)){
        $text = trim((string)$xml->message);
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $text;
}


$session =
    ag_current_session();


if(
    !$session ||
    ag_user_level($session) < 200
){
    ag_queue_json(
        array(
            'ok' => false,
            'error' => 'Administrator access required.'
        ),
        403
    );
}


$con =
    null;


try {

    require
        __DIR__ .
        '/../../MetroMap/includes/config.php';


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
        ag_queue_json(
            array(
                'ok' => false,
                'error' => 'Unable to connect to the Robust database.'
            ),
            500
        );
    }


    @mysqli_set_charset(
        $con,
        'utf8mb4'
    );


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

        ORDER BY
            i.TMStamp DESC,
            i.ID DESC

        LIMIT 1000
        ";


    $result =
        @mysqli_query(
            $con,
            $sql
        );


    if(
        !$result
    ){
        ag_queue_json(
            array(
                'ok' => false,
                'error' => 'Unable to read the offline delivery queue.'
            ),
            500
        );
    }


    $messages =
        array();


    while(
        $row =
            mysqli_fetch_assoc(
                $result
            )
    ){
        $recipientName =
            trim(
                (string)$row['RecipientName']
            );


        $senderName =
            trim(
                (string)$row['SenderName']
            );


        $messages[] =
            array(
                'id' =>
                    (int)$row['ID'],

                'recipientId' =>
                    (string)$row['PrincipalID'],

                'recipientName' =>
                    $recipientName !== ''
                        ? $recipientName
                        : (string)$row['PrincipalID'],

                'senderId' =>
                    (string)$row['FromID'],

                'senderName' =>
                    $senderName !== ''
                        ? $senderName
                        : (string)$row['FromID'],

                'message' => ag_queue_message_text($row['Message']),

                'timestamp' =>
                    (string)$row['TMStamp']
            );
    }


    mysqli_free_result(
        $result
    );


    ag_queue_json(
        array(
            'ok' => true,
            'messages' => $messages,
            'count' => count($messages)
        )
    );

}
finally {

    if(
        $con instanceof mysqli
    ){
        mysqli_close(
            $con
        );
    }
}
