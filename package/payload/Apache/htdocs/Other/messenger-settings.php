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


function ag_messenger_json(
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


$session =
    ag_current_session();

if(
    !$session
){
    ag_messenger_json(
        array(
            'ok' =>
                false,

            'error' =>
                'Not signed in.'
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
    ag_messenger_json(
        array(
            'ok' =>
                false,

            'error' =>
                'Signed-in avatar UUID is unavailable.'
        ),
        400
    );
}


if(
    $_SERVER['REQUEST_METHOD'] ===
    'GET'
){
    ag_messenger_json(
        array(
            'ok' =>
                true,

            'preferences' =>
                ag_messenger_load_preferences(
                    $principalId
                )
        )
    );
}


if(
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
){
    ag_messenger_json(
        array(
            'ok' =>
                false,

            'error' =>
                'Unsupported request method.'
        ),
        405
    );
}


$action =
    trim(
        (string)(
            $_POST['action'] ??
            ''
        )
    );


/* MESSENGER WINDOW ACTIONS V2 START */

/*
 * ============================================================
 * FRIENDS + CHAT SEPARATE SETTINGS
 * ============================================================
 */

$windowActions =
    array(
        'get_window',
        'save_window',
        'upload_window_background',
        'reset_window'
    );


if(
    in_array(
        $action,
        $windowActions,
        true
    )
){
    $window =
        strtolower(
            trim(
                (string)(
                    $_POST['window'] ??
                    ''
                )
            )
        );


    if(
        !ag_messenger_valid_window(
            $window
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Invalid Messenger window.'
            ),
            400
        );
    }


    /*
     * ---------------------------------------------------------
     * GET ONE WINDOW
     * ---------------------------------------------------------
     */

    if(
        $action ===
        'get_window'
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    true,

                'window' =>
                    $window,

                'preferences' =>
                    ag_messenger_load_window_preferences(
                        $principalId,
                        $window
                    )
            )
        );
    }


    /*
     * ---------------------------------------------------------
     * SAVE ONE WINDOW
     * ---------------------------------------------------------
     */

    if(
        $action ===
        'save_window'
    ){
        $existing =
            ag_messenger_load_window_preferences(
                $principalId,
                $window
            );


        $incoming =
            array(
                'backgroundMode' =>
                    $_POST['backgroundMode'] ??
                    $existing['backgroundMode'],

                'backgroundPosition' =>
                    $_POST['backgroundPosition'] ??
                    $existing['backgroundPosition'],

                'darkness' =>
                    $_POST['darkness'] ??
                    $existing['darkness'],

                'blur' =>
                    $_POST['blur'] ??
                    $existing['blur'],

                'bubbleOpacity' =>
                    $_POST['bubbleOpacity'] ??
                    $existing['bubbleOpacity'],

                'fontSize' =>
                    $_POST['fontSize'] ??
                    $existing['fontSize'],

                'left' =>
                    $_POST['left'] ??
                    ($existing['left'] ?? ''),

                'top' =>
                    $_POST['top'] ??
                    ($existing['top'] ?? ''),

                'width' =>
                    $_POST['width'] ??
                    ($existing['width'] ?? ''),

                'height' =>
                    $_POST['height'] ??
                    ($existing['height'] ?? '')
            );


        if(
            !ag_messenger_save_window_preferences(
                $principalId,
                $window,
                $incoming
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Could not save Messenger window settings.'
                ),
                500
            );
        }


        ag_messenger_json(
            array(
                'ok' =>
                    true,

                'window' =>
                    $window,

                'preferences' =>
                    ag_messenger_load_window_preferences(
                        $principalId,
                        $window
                    )
            )
        );
    }


    /*
     * ---------------------------------------------------------
     * UPLOAD BACKGROUND FOR ONE WINDOW
     * ---------------------------------------------------------
     */

    if(
        $action ===
        'upload_window_background'
    ){
        if(
            !isset(
                $_FILES['background']
            ) ||
            !is_array(
                $_FILES['background']
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Choose an image first.'
                ),
                400
            );
        }


        $upload =
            $_FILES['background'];


        $error =
            (int)(
                $upload['error'] ??
                UPLOAD_ERR_NO_FILE
            );


        if(
            $error !==
            UPLOAD_ERR_OK
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Image upload failed.'
                ),
                400
            );
        }


        $size =
            (int)(
                $upload['size'] ??
                0
            );


        if(
            $size <= 0 ||
            $size > 8 * 1024 * 1024
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Background images must be 8 MB or smaller.'
                ),
                400
            );
        }


        $temp =
            (string)(
                $upload['tmp_name'] ??
                ''
            );


        if(
            $temp ===
            '' ||
            !is_uploaded_file(
                $temp
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Invalid uploaded file.'
                ),
                400
            );
        }


        $imageInfo =
            @getimagesize(
                $temp
            );


        if(
            !is_array(
                $imageInfo
            ) ||
            empty(
                $imageInfo['mime']
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'The uploaded file is not a valid image.'
                ),
                400
            );
        }


        $mime =
            strtolower(
                (string)$imageInfo['mime']
            );


        $extensions =
            array(
                'image/jpeg' =>
                    'jpg',

                'image/png' =>
                    'png',

                'image/webp' =>
                    'webp'
            );


        if(
            !isset(
                $extensions[$mime]
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Only JPG, PNG and WebP backgrounds are supported.'
                ),
                400
            );
        }


        $width =
            (int)(
                $imageInfo[0] ??
                0
            );


        $height =
            (int)(
                $imageInfo[1] ??
                0
            );


        if(
            $width < 1 ||
            $height < 1 ||
            $width > 10000 ||
            $height > 10000
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Invalid image dimensions.'
                ),
                400
            );
        }


        $backgroundDir =
            __DIR__ .
            '/custom/MessengerBackgrounds';


        if(
            !is_dir(
                $backgroundDir
            ) &&
            !@mkdir(
                $backgroundDir,
                0755,
                true
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Could not create the Messenger background folder.'
                ),
                500
            );
        }


        /*
         * Delete only this window's previous image.
         *
         * FRIENDS never deletes CHAT.
         * CHAT never deletes FRIENDS.
         */

        foreach(
            glob(
                $backgroundDir .
                '/' .
                $principalId .
                '-' .
                $window .
                '.*'
            ) ?: array()
            as
            $oldFile
        ){
            @unlink(
                $oldFile
            );
        }


        $extension =
            $extensions[$mime];


        $fileName =
            $principalId .
            '-' .
            $window .
            '.' .
            $extension;


        $destination =
            $backgroundDir .
            '/' .
            $fileName;


        if(
            !@move_uploaded_file(
                $temp,
                $destination
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Could not store the Messenger background.'
                ),
                500
            );
        }


        $preferences =
            ag_messenger_load_window_preferences(
                $principalId,
                $window
            );


        $preferences['backgroundUrl'] =
            '/Other/custom/MessengerBackgrounds/' .
            $fileName .
            '?v=' .
            time();


        if(
            !ag_messenger_save_window_preferences(
                $principalId,
                $window,
                $preferences
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Background uploaded but preferences could not be saved.'
                ),
                500
            );
        }


        ag_messenger_json(
            array(
                'ok' =>
                    true,

                'window' =>
                    $window,

                'preferences' =>
                    ag_messenger_load_window_preferences(
                        $principalId,
                        $window
                    )
            )
        );
    }


    /*
     * ---------------------------------------------------------
     * RESET ONE WINDOW ONLY
     * ---------------------------------------------------------
     */

    if(
        $action ===
        'reset_window'
    ){
        $backgroundDir =
            __DIR__ .
            '/custom/MessengerBackgrounds';


        foreach(
            glob(
                $backgroundDir .
                '/' .
                $principalId .
                '-' .
                $window .
                '.*'
            ) ?: array()
            as
            $oldFile
        ){
            @unlink(
                $oldFile
            );
        }


        $defaults =
            ag_messenger_window_defaults();


        $defaults['backgroundUrl'] =
            '';


        if(
            !ag_messenger_save_window_preferences(
                $principalId,
                $window,
                $defaults
            )
        ){
            ag_messenger_json(
                array(
                    'ok' =>
                        false,

                    'error' =>
                        'Could not reset Messenger window settings.'
                ),
                500
            );
        }


        ag_messenger_json(
            array(
                'ok' =>
                    true,

                'window' =>
                    $window,

                'preferences' =>
                    ag_messenger_load_window_preferences(
                        $principalId,
                        $window
                    )
            )
        );
    }
}

/* MESSENGER WINDOW ACTIONS V2 END */

/*
 * ============================================================
 * SAVE SETTINGS
 * ============================================================
 */

if(
    $action ===
    'save'
){
    $existing =
        ag_messenger_load_preferences(
            $principalId
        );

    $incoming =
        array(
            'backgroundMode' =>
                $_POST['backgroundMode'] ??
                $existing['backgroundMode'],

            'backgroundPosition' =>
                $_POST['backgroundPosition'] ??
                $existing['backgroundPosition'],

            'darkness' =>
                $_POST['darkness'] ??
                $existing['darkness'],

            'blur' =>
                $_POST['blur'] ??
                $existing['blur'],

            'bubbleOpacity' =>
                $_POST['bubbleOpacity'] ??
                $existing['bubbleOpacity'],

            'fontSize' =>
                $_POST['fontSize'] ??
                $existing['fontSize'],

            'enterToSend' =>
                $_POST['enterToSend'] ??
                $existing['enterToSend'],

            'showTimestamps' =>
                $_POST['showTimestamps'] ??
                $existing['showTimestamps']
        );

    $safe =
        ag_messenger_sanitize_preferences(
            $incoming,
            $existing
        );

    $safe['backgroundUrl'] =
        $existing['backgroundUrl'];

    if(
        !ag_messenger_save_preferences(
            $principalId,
            $safe
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Could not save Messenger preferences.'
            ),
            500
        );
    }

    ag_messenger_json(
        array(
            'ok' =>
                true,

            'preferences' =>
                ag_messenger_load_preferences(
                    $principalId
                )
        )
    );
}


/*
 * ============================================================
 * UPLOAD PERSONAL BACKGROUND
 * ============================================================
 */

if(
    $action ===
    'upload_background'
){
    if(
        !isset(
            $_FILES['background']
        ) ||
        !is_array(
            $_FILES['background']
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Choose an image first.'
            ),
            400
        );
    }

    $upload =
        $_FILES['background'];

    $error =
        (int)(
            $upload['error'] ??
            UPLOAD_ERR_NO_FILE
        );

    if(
        $error !==
        UPLOAD_ERR_OK
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Image upload failed.'
            ),
            400
        );
    }

    $size =
        (int)(
            $upload['size'] ??
            0
        );

    if(
        $size <= 0 ||
        $size > 8 * 1024 * 1024
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Background images must be 8 MB or smaller.'
            ),
            400
        );
    }

    $temp =
        (string)(
            $upload['tmp_name'] ??
            ''
        );

    if(
        $temp === '' ||
        !is_uploaded_file(
            $temp
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Invalid uploaded file.'
            ),
            400
        );
    }

    $imageInfo =
        @getimagesize(
            $temp
        );

    if(
        !is_array(
            $imageInfo
        ) ||
        empty(
            $imageInfo['mime']
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'The uploaded file is not a valid image.'
            ),
            400
        );
    }

    $mime =
        strtolower(
            (string)$imageInfo['mime']
        );

    $extensions =
        array(
            'image/jpeg' =>
                'jpg',

            'image/png' =>
                'png',

            'image/webp' =>
                'webp'
        );

    if(
        !isset(
            $extensions[$mime]
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Only JPG, PNG and WebP backgrounds are supported.'
            ),
            400
        );
    }

    $width =
        (int)(
            $imageInfo[0] ??
            0
        );

    $height =
        (int)(
            $imageInfo[1] ??
            0
        );

    if(
        $width < 1 ||
        $height < 1 ||
        $width > 10000 ||
        $height > 10000
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Invalid image dimensions.'
            ),
            400
        );
    }

    $backgroundDir =
        __DIR__ .
        '/custom/MessengerBackgrounds';

    if(
        !is_dir(
            $backgroundDir
        ) &&
        !@mkdir(
            $backgroundDir,
            0755,
            true
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Could not create the Messenger background folder.'
            ),
            500
        );
    }

    foreach(
        glob(
            $backgroundDir .
            '/' .
            $principalId .
            '.*'
        ) ?: array()
        as
        $oldFile
    ){
        @unlink(
            $oldFile
        );
    }

    $extension =
        $extensions[$mime];

    $fileName =
        $principalId .
        '.' .
        $extension;

    $destination =
        $backgroundDir .
        '/' .
        $fileName;

    if(
        !@move_uploaded_file(
            $temp,
            $destination
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Could not store the Messenger background.'
            ),
            500
        );
    }

    $preferences =
        ag_messenger_load_preferences(
            $principalId
        );

    $preferences['backgroundUrl'] =
        '/Other/custom/MessengerBackgrounds/' .
        $fileName .
        '?v=' .
        time();

    if(
        !ag_messenger_save_preferences(
            $principalId,
            $preferences
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Background uploaded but preferences could not be saved.'
            ),
            500
        );
    }

    ag_messenger_json(
        array(
            'ok' =>
                true,

            'preferences' =>
                ag_messenger_load_preferences(
                    $principalId
                )
        )
    );
}


/*
 * ============================================================
 * RESET
 * ============================================================
 */

if(
    $action ===
    'reset'
){
    $backgroundDir =
        __DIR__ .
        '/custom/MessengerBackgrounds';

    foreach(
        glob(
            $backgroundDir .
            '/' .
            $principalId .
            '.*'
        ) ?: array()
        as
        $oldFile
    ){
        @unlink(
            $oldFile
        );
    }

    $defaults =
        ag_messenger_defaults();

    if(
        !ag_messenger_save_preferences(
            $principalId,
            $defaults
        )
    ){
        ag_messenger_json(
            array(
                'ok' =>
                    false,

                'error' =>
                    'Could not reset Messenger preferences.'
            ),
            500
        );
    }

    ag_messenger_json(
        array(
            'ok' =>
                true,

            'preferences' =>
                ag_messenger_load_preferences(
                    $principalId
                )
        )
    );
}


ag_messenger_json(
    array(
        'ok' =>
            false,

        'error' =>
            'Unknown Messenger action.'
    ),
    400
);
