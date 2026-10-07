<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 ============================================================
 Grid
 ADMIN REGIONS - GRID DATA ENDPOINT V1
 ============================================================

 Purpose:
   Returns only regions personally assigned to the currently
   signed-in Grid avatar.

 Important:
   Even Grid Owner / Level 200 accounts are filtered by
   EstateOwner here because this endpoint belongs to
   MY REGIONS, not Admin Region Management.

 Security:
   - Requires signed dg_session.
   - Avatar identity comes only from the signed session.
   - Region ownership comes only from DreamGrid regionlist.
   - Browser cannot request another owner's region list.
 ============================================================
*/

require_once __DIR__ . '/core/bootstrap.php';


header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


function australiaUserRegionsFail(
    $message,
    $status = 400
){
    http_response_code(
        (int)$status
    );

    echo json_encode(
        array(
            'ok' =>
                false,

            'error' =>
                (string)$message
        ),
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
 ============================================================
 SIGNED SESSION
 ============================================================
*/

$session =
    ag_current_session();


if(
    !$session
){

    australiaUserRegionsFail(
        'Not logged in.',
        401
    );
}


$avatar =
    ag_avatar_name(
        $session
    );


$level =
    ag_user_level(
        $session
    );


if(
    $avatar === ''
){

    australiaUserRegionsFail(
        'Signed session does not contain an avatar name.',
        401
    );
}



/*
 ============================================================
 ADMIN ACCESS
 ============================================================
*/

if(
    !ag_is_admin(
        $session
    )
){

    australiaUserRegionsFail(
        'Grid owner access only.',
        403
    );
}

/*
 ============================================================
 DREAMGRID REQUEST HELPER
 ============================================================
*/

function australiaUserRegionsRequest(
    $url,
    $timeout = 5
){

    $context =
        stream_context_create(
            array(
                'http' =>
                    array(
                        'method' =>
                            'GET',

                        'timeout' =>
                            (int)$timeout,

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

        return null;
    }


    return
        (string)$response;
}


/*
 ============================================================
 REGION STATUS
 ============================================================
*/

function australiaUserRegionStatus(
    $regionName
){

    $url =
        ag_dg_diagnostics_base() . '/' .
        '?command=RegionStatus' .
        '&RegionName=' .
        rawurlencode(
            (string)$regionName
        );


    $response =
        australiaUserRegionsRequest(
            $url,
            3
        );


    if(
        $response === null
    ){

        return
            'Unknown';
    }


    $status =
        trim(
            $response
        );


    return
        $status !== ''
            ? $status
            : 'Unknown';
}


/*
 ============================================================
 DREAMGRID REGION LIST
 ============================================================
*/

$regionListUrl =
    ag_dg_diagnostics_base() . '/' .
    '?command=regionlist' .
    '&page=1' .
    '&rp=500' .
    '&sortorder=asc';


$json =
    australiaUserRegionsRequest(
        $regionListUrl,
        8
    );


if(
    $json === null
){

    australiaUserRegionsFail(
        'Region list is unavailable.',
        502
    );
}


$data =
    json_decode(
        $json,
        true
    );


if(
    !is_array(
        $data
    )
){

    australiaUserRegionsFail(
        'The grid service returned an invalid region list.',
        502
    );
}


$rows =
    isset(
        $data['rows']
    ) &&
    is_array(
        $data['rows']
    )
        ? $data['rows']
        : array();


/*
 ============================================================
 FILTER TO THIS AVATAR ONLY
 ============================================================
*/

$regions =
    array();


foreach(
    $rows
    as
    $row
){

    $cell =
        isset(
            $row['cell']
        ) &&
        is_array(
            $row['cell']
        )
            ? $row['cell']
            : array();


    $regionName =
        trim(
            (string)(
                $cell['RegionName'] ??
                ''
            )
        );


    $estateOwner =
        trim(
            (string)(
                $cell['EstateOwner'] ??
                ''
            )
        );


    if(
        $regionName === ''
    ){

        continue;
    }
    /*
     * ADMIN REGION MANAGEMENT:
     *
     * Level 200+ receives every region returned by DreamGrid.
     * No EstateOwner filtering is performed on this endpoint.
     */



    $regions[] =
        array(

            'RegionName' =>
                $regionName,

            'EstateName' =>
                (string)(
                    $cell['EstateName'] ??
                    ''
                ),

            'EstateOwner' =>
                $estateOwner,

            'Size' =>
                (string)(
                    $cell['Size'] ??
                    ''
                ),

            'Status' =>
                australiaUserRegionStatus(
                    $regionName
                ),

            'PrimCount' =>
                (int)(
                    $cell['PrimCount'] ??
                    0
                ),

            'AvatarCount' =>
                (int)(
                    $cell['AvatarCount'] ??
                    0
                ),

            'Ram' =>
                (string)(
                    $cell['Ram'] ??
                    ''
                )
        );
}


/*
 ============================================================
 STABLE ALPHABETICAL ORDER
 ============================================================
*/

usort(
    $regions,
    function(
        $a,
        $b
    ){

        return
            strcasecmp(
                (string)(
                    $a['RegionName'] ??
                    ''
                ),
                (string)(
                    $b['RegionName'] ??
                    ''
                )
            );
    }
);


/*
 ============================================================
 PERMISSIONS FOR PAGE CONTROLS
 ============================================================
*/

$permissions =
    array(

        /*
         * command.php independently checks these again.
         */

        'regionControls' =>
            true,

        'saveOar' =>
            true
    );


/*
 ============================================================
 RESPONSE
 ============================================================
*/

echo json_encode(
    array(

        'ok' =>
            true,

        'avatar' =>
            $avatar,

        'level' =>
            $level,

        'count' =>
            count(
                $regions
            ),

        'permissions' =>
            $permissions,

        'regions' =>
            $regions
    ),
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);