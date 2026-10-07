<?php
declare(strict_types=1);

require_once
    __DIR__ .
    '/core/bootstrap.php';

ag_require_same_origin_post();

require_once
    __DIR__ .
    '/core/messenger-service.php';


header(
    'Content-Type: application/json; charset=UTF-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate'
);


function ag_messenger_send_json(
    array $data,
    int $status = 200
): void
{
    http_response_code(
        $status
    );

    echo
        json_encode(
            $data,
            JSON_UNESCAPED_SLASHES
        );

    exit;
}


if(
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
){
    ag_messenger_send_json(
        array(
            'ok' => false,
            'error' => 'POST required.'
        ),
        405
    );
}


if(
    (
        $_SERVER['HTTP_X_AUSTRALIA_MESSENGER'] ??
        ''
    ) !==
    '1'
){
    ag_messenger_send_json(
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
    !$session
){
    ag_messenger_send_json(
        array(
            'ok' => false,
            'error' => 'Not signed in.'
        ),
        401
    );
}


$principalId =
    strtolower(
        trim(
            (string)(
                $session['principalId'] ??
                ''
            )
        )
    );


if(
    !preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
        $principalId
    )
){
    ag_messenger_send_json(
        array(
            'ok' => false,
            'error' => 'Signed-in avatar UUID is unavailable.'
        ),
        400
    );
}


$avatar =
    ag_avatar_name(
        $session
    );


$friendKey =
    trim(
        (string)(
            $_POST['friend_key'] ??
            ''
        )
    );


$message =
    trim(
        (string)(
            $_POST['friend_message'] ??
            ''
        )
    );


if(
    $friendKey === '' ||
    $message === ''
){
    ag_messenger_send_json(
        array(
            'ok' => false,
            'error' => 'Friend and message are required.'
        ),
        400
    );
}


if(
    strlen(
        $message
    ) >
    1000
){
    ag_messenger_send_json(
        array(
            'ok' => false,
            'error' => 'Message is too long.'
        ),
        400
    );
}


$friendsWarning =
    '';


$friends =
    australiaGetFriends(
        $principalId,
        $friendsWarning
    );


$selectedFriend =
    null;


foreach(
    $friends
    as
    $friend
){
    if(
        isset(
            $friend['key']
        ) &&
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
    !$selectedFriend
){
    ag_messenger_send_json(
        array(
            'ok' => false,
            'error' => 'Friend could not be verified.'
        ),
        400
    );
}


$targetUrl =
    '';


if(
    !empty(
        $selectedFriend['isHypergrid']
    )
){
    $homeUri =
        trim(
            (string)(
                $selectedFriend['homeUri'] ??
                ''
            )
        );


    if(
        $homeUri !== ''
    ){
        $parts =
            parse_url(
                $homeUri
            );


        $scheme =
            strtolower(
                (string)(
                    $parts['scheme'] ??
                    ''
                )
            );


        if(
            !is_array(
                $parts
            ) ||
            !in_array(
                $scheme,
                array(
                    'http',
                    'https'
                ),
                true
            )
        ){
            ag_messenger_send_json(
                array(
                    'ok' => false,
                    'error' => 'Friend home grid address is invalid.'
                ),
                400
            );
        }


        $targetUrl =
            $homeUri;
    }
}


$sendError =
    '';


$delivered =
    australiaSendGridInstantMessage(
        $principalId,
        $avatar,
        (string)$selectedFriend['id'],
        $message,
        $sendError,
        $targetUrl
    );


if(
    $delivered
){
    ag_messenger_send_json(
        array(
            'ok' => true,
            'status' => 'delivered'
        )
    );
}


if(
    $sendError === ''
){
    ag_messenger_send_json(
        array(
            'ok' => true,
            'status' =>
                $targetUrl !== ''
                    ? 'submitted'
                    : 'queued'
        )
    );
}


ag_messenger_send_json(
    array(
        'ok' => false,
        'error' => $sendError
    ),
    502
);