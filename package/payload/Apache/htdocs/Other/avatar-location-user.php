<?php

declare(strict_types=1);

/*
 ============================================================
 Grid - USER AVATAR LOCATION V1

 SESSION BOUND.

 A normal user can obtain ONLY the location of the avatar
 currently signed into the Grid web session.

 The private RemoteAdmin map token is read SERVER SIDE from
 avatar-location.php and is never returned to the browser.
 ============================================================
*/

require_once __DIR__ . '/login/session.php';

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


$session =
    dreamGridCurrentSession();


if(!$session){

    http_response_code(
        401
    );

    echo json_encode(
        array(
            'ok' => false,
            'error' => 'Not logged in'
        )
    );

    return;
}


$avatar =
    trim(
        (string)(
            $session['avatar'] ?? ''
        )
    );


if($avatar === ''){

    http_response_code(
        401
    );

    echo json_encode(
        array(
            'ok' => false,
            'error' => 'No signed-in avatar'
        )
    );

    return;
}


$privateEndpoint =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'avatar-location.php';


$source =
    @file_get_contents(
        $privateEndpoint
    );


if(
    $source === false ||
    !preg_match(
        "/const\s+AUSTRALIA_MAP_TOKEN\s*=\s*'([a-f0-9]{32,128})'/i",
        $source,
        $match
    )
){

    http_response_code(
        500
    );

    echo json_encode(
        array(
            'ok' => false,
            'error' => 'Avatar location service unavailable'
        )
    );

    return;
}


/*
 * Force the private endpoint to query ONLY the signed-in user.
 * User supplied token/avatar parameters are overwritten.
 */

$_GET['token'] =
    $match[1];

$_GET['avatar'] =
    $avatar;


/*
 * Keep last_region if supplied.
 * It only optimises which simulator is checked first.
 */

require $privateEndpoint;
