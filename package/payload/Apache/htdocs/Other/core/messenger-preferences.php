<?php
declare(strict_types=1);

require_once __DIR__ . '/dreamgrid-env.php';

/*
 * ============================================================
 * SHARED MESSENGER PREFERENCES
 * ============================================================
 */

function ag_messenger_private_root(): string
{
    $base=ag_dg_root();if(!$base)throw new RuntimeException('Grid root was not found.');
    $parent=$base.DIRECTORY_SEPARATOR.'_WEB_PRIVATE';
    $folders=glob($parent.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'Messenger',GLOB_ONLYDIR) ?: array();
    $namespace=preg_replace('/[^a-zA-Z0-9_.-]+/','-',ag_grid_name());
    foreach($folders as $folder)if(strcasecmp(basename(dirname($folder)),$namespace)===0)return $folder;
    if(count($folders)===1)return $folders[0];
    if(count($folders)>1)throw new RuntimeException('Multiple messenger namespaces require configuration.');
    $root=$parent.DIRECTORY_SEPARATOR.$namespace.DIRECTORY_SEPARATOR.'Messenger';
    if(!is_dir($root)&&!mkdir($root,0700,true))throw new RuntimeException('Messenger storage is unavailable.');
    return $root;
}


function ag_messenger_preferences_dir(): string
{
    $dir =
        ag_messenger_private_root() .
        DIRECTORY_SEPARATOR .
        'Preferences';

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

    return
        $dir;
}


function ag_messenger_valid_uuid(
    string $uuid
): bool
{
    return
        preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            trim(
                $uuid
            )
        ) === 1;
}


function ag_messenger_defaults(): array
{
    return
        array(
            'backgroundUrl' =>
                '',

            'backgroundMode' =>
                'cover',

            'backgroundPosition' =>
                'center center',

            'darkness' =>
                0.50,

            'blur' =>
                0,

            'bubbleOpacity' =>
                0.88,

            'fontSize' =>
                15,

            'enterToSend' =>
                true,

            'showTimestamps' =>
                true
        );
}


function ag_messenger_clamp(
    float $value,
    float $minimum,
    float $maximum
): float
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


function ag_messenger_sanitize_preferences(
    array $input,
    array $existing = array()
): array
{
    $defaults =
        ag_messenger_defaults();

    $base =
        array_merge(
            $defaults,
            $existing
        );

    $output =
        $base;

    if(
        array_key_exists(
            'backgroundMode',
            $input
        )
    ){
        $mode =
            trim(
                (string)$input['backgroundMode']
            );

        if(
            in_array(
                $mode,
                array(
                    'cover',
                    'contain',
                    'stretch'
                ),
                true
            )
        ){
            $output['backgroundMode'] =
                $mode;
        }
    }


    if(
        array_key_exists(
            'backgroundPosition',
            $input
        )
    ){
        $position =
            trim(
                (string)$input['backgroundPosition']
            );

        $allowed =
            array(
                'center center',
                'center top',
                'center bottom',
                'left center',
                'right center'
            );

        if(
            in_array(
                $position,
                $allowed,
                true
            )
        ){
            $output['backgroundPosition'] =
                $position;
        }
    }


    if(
        array_key_exists(
            'darkness',
            $input
        )
    ){
        $output['darkness'] =
            ag_messenger_clamp(
                (float)$input['darkness'],
                0.00,
                0.90
            );
    }


    if(
        array_key_exists(
            'blur',
            $input
        )
    ){
        $output['blur'] =
            (int)round(
                ag_messenger_clamp(
                    (float)$input['blur'],
                    0,
                    20
                )
            );
    }


    if(
        array_key_exists(
            'bubbleOpacity',
            $input
        )
    ){
        $output['bubbleOpacity'] =
            ag_messenger_clamp(
                (float)$input['bubbleOpacity'],
                0.40,
                1.00
            );
    }


    if(
        array_key_exists(
            'fontSize',
            $input
        )
    ){
        $output['fontSize'] =
            (int)round(
                ag_messenger_clamp(
                    (float)$input['fontSize'],
                    12,
                    22
                )
            );
    }


    if(
        array_key_exists(
            'enterToSend',
            $input
        )
    ){
        $output['enterToSend'] =
            filter_var(
                $input['enterToSend'],
                FILTER_VALIDATE_BOOLEAN
            );
    }


    if(
        array_key_exists(
            'showTimestamps',
            $input
        )
    ){
        $output['showTimestamps'] =
            filter_var(
                $input['showTimestamps'],
                FILTER_VALIDATE_BOOLEAN
            );
    }


    /*
     * backgroundUrl is NOT accepted from arbitrary browser
     * preference data.
     *
     * It is changed only after a validated server upload.
     */

    return
        $output;
}


function ag_messenger_preferences_file(
    string $principalId
): string
{
    $principalId =
        strtolower(
            trim(
                $principalId
            )
        );

    if(
        !ag_messenger_valid_uuid(
            $principalId
        )
    ){
        throw new RuntimeException(
            'Invalid messenger principal ID.'
        );
    }

    return
        ag_messenger_preferences_dir() .
        DIRECTORY_SEPARATOR .
        $principalId .
        '.json';
}


function ag_messenger_load_preferences(
    string $principalId
): array
{
    $defaults =
        ag_messenger_defaults();

    try{
        $file =
            ag_messenger_preferences_file(
                $principalId
            );
    }
    catch(Throwable $e){
        return
            $defaults;
    }

    if(
        !is_file(
            $file
        )
    ){
        return
            $defaults;
    }

    $raw =
        @file_get_contents(
            $file
        );

    if(
        !is_string(
            $raw
        ) ||
        trim(
            $raw
        ) === ''
    ){
        return
            $defaults;
    }

    $decoded =
        json_decode(
            $raw,
            true
        );

    if(
        !is_array(
            $decoded
        )
    ){
        return
            $defaults;
    }

    $safe =
        ag_messenger_sanitize_preferences(
            $decoded,
            $defaults
        );

    if(
        isset(
            $decoded['backgroundUrl']
        )
    ){
        $url =
            trim(
                (string)$decoded['backgroundUrl']
            );

        if(
            $url === '' ||
            preg_match(
                '#^/Other/custom/MessengerBackgrounds/[0-9a-f\-]+\.(?:jpg|jpeg|png|webp)(?:\?v=[0-9]+)?$#i',
                $url
            ) === 1
        ){
            $safe['backgroundUrl'] =
                $url;
        }
    }

    return
        $safe;
}


/* MESSENGER WINDOW PREFERENCES V2 START */

/*
 * FRIENDS and CHAT are intentionally stored separately.
 *
 * Existing V1 preferences remain untouched for backwards
 * compatibility.
 */

function ag_messenger_window_names(): array
{
    return
        array(
            'friends',
            'chat'
        );
}


function ag_messenger_valid_window(
    string $window
): bool
{
    return
        in_array(
            strtolower(
                trim(
                    $window
                )
            ),
            ag_messenger_window_names(),
            true
        );
}


function ag_messenger_window_defaults(): array
{
    return
        array(
            'backgroundUrl' =>
                '',

            'backgroundMode' =>
                'cover',

            'backgroundPosition' =>
                'center center',

            'darkness' =>
                0.50,

            'blur' =>
                0,

            'bubbleOpacity' =>
                0.88,

            'fontSize' =>
                15
        );
}


function ag_messenger_sanitize_window_preferences(
    array $input,
    array $existing = array()
): array
{
    $base =
        array_merge(
            ag_messenger_window_defaults(),
            $existing
        );

    $output =
        $base;


    if(
        array_key_exists(
            'backgroundMode',
            $input
        )
    ){
        $mode =
            trim(
                (string)$input['backgroundMode']
            );

        if(
            in_array(
                $mode,
                array(
                    'cover',
                    'contain',
                    'stretch'
                ),
                true
            )
        ){
            $output['backgroundMode'] =
                $mode;
        }
    }


    if(
        array_key_exists(
            'backgroundPosition',
            $input
        )
    ){
        $position =
            trim(
                (string)$input['backgroundPosition']
            );

        if(
            in_array(
                $position,
                array(
                    'center center',
                    'center top',
                    'center bottom',
                    'left center',
                    'right center'
                ),
                true
            )
        ){
            $output['backgroundPosition'] =
                $position;
        }
    }


    if(
        array_key_exists(
            'darkness',
            $input
        )
    ){
        $output['darkness'] =
            ag_messenger_clamp(
                (float)$input['darkness'],
                0.00,
                0.90
            );
    }


    if(
        array_key_exists(
            'blur',
            $input
        )
    ){
        $output['blur'] =
            (int)round(
                ag_messenger_clamp(
                    (float)$input['blur'],
                    0,
                    20
                )
            );
    }


    if(
        array_key_exists(
            'bubbleOpacity',
            $input
        )
    ){
        $output['bubbleOpacity'] =
            ag_messenger_clamp(
                (float)$input['bubbleOpacity'],
                0.40,
                1.00
            );
    }


    if(
        array_key_exists(
            'fontSize',
            $input
        )
    ){
        $output['fontSize'] =
            (int)round(
                ag_messenger_clamp(
                    (float)$input['fontSize'],
                    12,
                    22
                )
            );
    }


    foreach(
        array(
            'left'   => array(0,5000),
            'top'    => array(0,5000),
            'width'  => array(300,2000),
            'height' => array(300,1600)
        )
        as $geometryKey => $geometryRange
    ){
        if(
            array_key_exists($geometryKey,$input) &&
            $input[$geometryKey] !== '' &&
            is_numeric($input[$geometryKey])
        ){
            $output[$geometryKey] =
                (int)round(
                    ag_messenger_clamp(
                        (float)$input[$geometryKey],
                        $geometryRange[0],
                        $geometryRange[1]
                    )
                );
        }
    }


    /*
     * Never trust backgroundUrl from arbitrary browser
     * appearance data. It is handled separately.
     */

    return
        $output;
}


function ag_messenger_window_preferences_file(
    string $principalId
): string
{
    $principalId =
        strtolower(
            trim(
                $principalId
            )
        );

    if(
        !ag_messenger_valid_uuid(
            $principalId
        )
    ){
        throw new RuntimeException(
            'Invalid messenger principal ID.'
        );
    }

    return
        ag_messenger_preferences_dir() .
        DIRECTORY_SEPARATOR .
        $principalId .
        '-windows.json';
}


function ag_messenger_valid_window_background_url(
    string $url,
    string $principalId,
    string $window
): bool
{
    $url =
        trim(
            $url
        );

    if(
        $url ===
        ''
    ){
        return
            true;
    }

    if(
        !ag_messenger_valid_uuid(
            $principalId
        ) ||
        !ag_messenger_valid_window(
            $window
        )
    ){
        return
            false;
    }

    $fileBase =
        preg_quote(
            strtolower(
                $principalId
            ) .
            '-' .
            strtolower(
                $window
            ),
            '#'
        );

    return
        preg_match(
            '#^/Other/custom/MessengerBackgrounds/' .
            $fileBase .
            '\.(?:jpg|jpeg|png|webp)(?:\?v=[0-9]+)?$#i',
            $url
        ) === 1;
}


function ag_messenger_load_window_preferences_all(
    string $principalId
): array
{
    $result =
        array(
            'friends' =>
                ag_messenger_window_defaults(),

            'chat' =>
                ag_messenger_window_defaults()
        );

    try{
        $file =
            ag_messenger_window_preferences_file(
                $principalId
            );
    }
    catch(Throwable $e){
        return
            $result;
    }


    if(
        !is_file(
            $file
        )
    ){
        return
            $result;
    }


    $raw =
        @file_get_contents(
            $file
        );


    if(
        !is_string(
            $raw
        ) ||
        trim(
            $raw
        ) ===
        ''
    ){
        return
            $result;
    }


    $decoded =
        json_decode(
            $raw,
            true
        );


    if(
        !is_array(
            $decoded
        )
    ){
        return
            $result;
    }


    foreach(
        ag_messenger_window_names()
        as
        $window
    ){
        if(
            !isset(
                $decoded[$window]
            ) ||
            !is_array(
                $decoded[$window]
            )
        ){
            continue;
        }


        $safe =
            ag_messenger_sanitize_window_preferences(
                $decoded[$window],
                $result[$window]
            );


        $url =
            trim(
                (string)(
                    $decoded[$window]['backgroundUrl'] ??
                    ''
                )
            );


        if(
            ag_messenger_valid_window_background_url(
                $url,
                $principalId,
                $window
            )
        ){
            $safe['backgroundUrl'] =
                $url;
        }


        $result[$window] =
            $safe;
    }


    return
        $result;
}


function ag_messenger_load_window_preferences(
    string $principalId,
    string $window
): array
{
    $window =
        strtolower(
            trim(
                $window
            )
        );

    if(
        !ag_messenger_valid_window(
            $window
        )
    ){
        throw new RuntimeException(
            'Invalid Messenger window.'
        );
    }

    $all =
        ag_messenger_load_window_preferences_all(
            $principalId
        );

    return
        $all[$window];
}


function ag_messenger_save_window_preferences(
    string $principalId,
    string $window,
    array $preferences
): bool
{
    $window =
        strtolower(
            trim(
                $window
            )
        );

    if(
        !ag_messenger_valid_window(
            $window
        )
    ){
        return
            false;
    }


    $all =
        ag_messenger_load_window_preferences_all(
            $principalId
        );


    $existing =
        $all[$window];


    $safe =
        ag_messenger_sanitize_window_preferences(
            $preferences,
            $existing
        );


    if(
        array_key_exists(
            'backgroundUrl',
            $preferences
        )
    ){
        $url =
            trim(
                (string)$preferences['backgroundUrl']
            );

        if(
            ag_messenger_valid_window_background_url(
                $url,
                $principalId,
                $window
            )
        ){
            $safe['backgroundUrl'] =
                $url;
        }
    }


    $all[$window] =
        $safe;


    $json =
        json_encode(
            $all,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );


    if(
        !is_string(
            $json
        )
    ){
        return
            false;
    }


    try{
        $file =
            ag_messenger_window_preferences_file(
                $principalId
            );
    }
    catch(Throwable $e){
        return
            false;
    }


    return
        @file_put_contents(
            $file,
            $json,
            LOCK_EX
        ) !== false;
}

/* MESSENGER WINDOW PREFERENCES V2 END */

function ag_messenger_save_preferences(
    string $principalId,
    array $preferences
): bool
{
    $file =
        ag_messenger_preferences_file(
            $principalId
        );

    $safe =
        ag_messenger_sanitize_preferences(
            $preferences,
            ag_messenger_load_preferences(
                $principalId
            )
        );

    if(
        array_key_exists(
            'backgroundUrl',
            $preferences
        )
    ){
        $safe['backgroundUrl'] =
            trim(
                (string)$preferences['backgroundUrl']
            );
    }

    $json =
        json_encode(
            $safe,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );

    if(
        !is_string(
            $json
        )
    ){
        return
            false;
    }

    $temp =
        $file .
        '.tmp-' .
        bin2hex(
            random_bytes(
                6
            )
        );

    if(
        @file_put_contents(
            $temp,
            $json,
            LOCK_EX
        ) === false
    ){
        return
            false;
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

        return
            false;
    }

    return
        true;
}
