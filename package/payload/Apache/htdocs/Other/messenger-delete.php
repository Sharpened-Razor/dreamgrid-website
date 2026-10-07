<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

ag_require_same_origin_post();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function ag_delete_json(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    ag_delete_json(
        array(
            'ok' => false,
            'error' => 'POST required.'
        ),
        405
    );
}


if(
    trim(
        (string)(
            $_SERVER['HTTP_X_AUSTRALIA_MESSENGER'] ??
            ''
        )
    ) !== '1'
){
    ag_delete_json(
        array(
            'ok' => false,
            'error' => 'Invalid Messenger request.'
        ),
        403
    );
}


$session =
    ag_current_session();


if(
    !$session ||
    ag_user_level($session) < 200
){
    ag_delete_json(
        array(
            'ok' => false,
            'error' => 'Administrator access required.'
        ),
        403
    );
}


$messageId =
    isset($_POST['message_id'])
        ? (int)$_POST['message_id']
        : 0;


if($messageId <= 0){
    ag_delete_json(
        array(
            'ok' => false,
            'error' => 'Invalid message ID.'
        ),
        400
    );
}


$con = null;


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


    if(!$con){
        ag_delete_json(
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


    $stmt =
        @mysqli_prepare(
            $con,
            'DELETE FROM im_offline WHERE ID = ? LIMIT 1'
        );


    if(!$stmt){
        ag_delete_json(
            array(
                'ok' => false,
                'error' => 'Unable to prepare delete request.'
            ),
            500
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $messageId
    );


    if(
        !@mysqli_stmt_execute(
            $stmt
        )
    ){
        mysqli_stmt_close(
            $stmt
        );

        ag_delete_json(
            array(
                'ok' => false,
                'error' => 'Unable to delete the offline message.'
            ),
            500
        );
    }


    $deleted =
        mysqli_stmt_affected_rows(
            $stmt
        ) > 0;


    mysqli_stmt_close(
        $stmt
    );


    ag_delete_json(
        array(
            'ok' => true,
            'deleted' => $deleted,
            'messageId' => $messageId
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