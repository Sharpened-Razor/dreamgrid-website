<?php
declare(strict_types=1);

require_once
    __DIR__ .
    '/core/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    ag_require_same_origin_post();
}

require_once
    __DIR__ .
    '/core/messenger-preferences.php';

header(
    'Content-Type: application/json; charset=UTF-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate'
);


function ag_layout_json(
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


function ag_layout_limit(
    int $value,
    int $minimum,
    int $maximum
): int
{
    return
        max(
            $minimum,
            min(
                $maximum,
                $value
            )
        );
}


$session =
    ag_current_session();

if(
    !$session
){
    ag_layout_json(
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
    !ag_messenger_valid_uuid(
        $principalId
    )
){
    ag_layout_json(
        array(
            'ok' => false,
            'error' => 'Avatar UUID unavailable.'
        ),
        400
    );
}


$dir =
    ag_messenger_private_root() .
    DIRECTORY_SEPARATOR .
    'Layout';

if(
    !is_dir(
        $dir
    )
){
    @mkdir(
        $dir,
        0700,
        true
    );
}


$file =
    $dir .
    DIRECTORY_SEPARATOR .
    $principalId .
    '.json';


$defaults =
    array(
        'friendsX' => 18,
        'friendsY' => 18,
        'friendsWidth' => 360,
        'friendsHeight' => 590,

        'chatX' => 402,
        'chatY' => 18,
        'chatWidth' => 720,
        'chatHeight' => 590
    );


$layout =
    $defaults;

if(
    is_file(
        $file
    )
){
    $raw =
        @file_get_contents(
            $file
        );

    if(
        is_string(
            $raw
        )
    ){
        $decoded =
            json_decode(
                $raw,
                true
            );

        if(
            is_array(
                $decoded
            )
        ){
            $layout =
                array_merge(
                    $defaults,
                    $decoded
                );
        }
    }
}


if(
    $_SERVER['REQUEST_METHOD'] ===
    'GET'
){
    ag_layout_json(
        array(
            'ok' => true,
            'layout' => $layout
        )
    );
}


if(
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
){
    ag_layout_json(
        array(
            'ok' => false,
            'error' => 'Unsupported request.'
        ),
        405
    );
}


$layout =
    array(
        'friendsX' =>
            ag_layout_limit(
                (int)(
                    $_POST['friendsX'] ??
                    $layout['friendsX']
                ),
                0,
                4000
            ),

        'friendsY' =>
            ag_layout_limit(
                (int)(
                    $_POST['friendsY'] ??
                    $layout['friendsY']
                ),
                0,
                4000
            ),

        'friendsWidth' =>
            ag_layout_limit(
                (int)(
                    $_POST['friendsWidth'] ??
                    $layout['friendsWidth']
                ),
                300,
                850
            ),

        'friendsHeight' =>
            ag_layout_limit(
                (int)(
                    $_POST['friendsHeight'] ??
                    $layout['friendsHeight']
                ),
                380,
                950
            ),

        'chatX' =>
            ag_layout_limit(
                (int)(
                    $_POST['chatX'] ??
                    $layout['chatX']
                ),
                0,
                4000
            ),

        'chatY' =>
            ag_layout_limit(
                (int)(
                    $_POST['chatY'] ??
                    $layout['chatY']
                ),
                0,
                4000
            ),

        'chatWidth' =>
            ag_layout_limit(
                (int)(
                    $_POST['chatWidth'] ??
                    $layout['chatWidth']
                ),
                440,
                1300
            ),

        'chatHeight' =>
            ag_layout_limit(
                (int)(
                    $_POST['chatHeight'] ??
                    $layout['chatHeight']
                ),
                400,
                1000
            )
    );


$json =
    json_encode(
        $layout,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_SLASHES
    );

if(
    !is_string(
        $json
    )
){
    ag_layout_json(
        array(
            'ok' => false,
            'error' => 'Could not encode layout.'
        ),
        500
    );
}


$temp =
    $file .
    '.tmp-' .
    bin2hex(
        random_bytes(
            5
        )
    );


if(
    @file_put_contents(
        $temp,
        $json,
        LOCK_EX
    ) === false
){
    ag_layout_json(
        array(
            'ok' => false,
            'error' => 'Could not save layout.'
        ),
        500
    );
}


if(
    !@rename(
        $temp,
        $file
    )
){
    @unlink(
        $temp
    );

    ag_layout_json(
        array(
            'ok' => false,
            'error' => 'Could not install layout.'
        ),
        500
    );
}


ag_layout_json(
    array(
        'ok' => true,
        'layout' => $layout
    )
);