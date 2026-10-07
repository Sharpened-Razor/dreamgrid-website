<?php

/*
 ============================================================
 Grid - SAFE USER REGION API V1.1
 ============================================================

 Signed-in USER Grid Map endpoint.

 Only map-safe region information is returned.
 ============================================================
*/

require_once __DIR__ . '/login/session.php';
require_once __DIR__ . '/core/dreamgrid-env.php';


header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


$s =
    dreamGridCurrentSession();


if(!$s){

    http_response_code(
        401
    );

    echo json_encode(
        array(
            'ok' => false,
            'error' => 'Not logged in'
        ),
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    return;
}


/*
 ============================================================
 REGION STATUS
 ============================================================
*/

function dgUserRegionStatus(
    $regionName
){

    $url =
        ag_web_local_base(ag_dg_diagnostics_port()) . '/' .
        '?command=RegionStatus' .
        '&RegionName=' .
        rawurlencode(
            $regionName
        );


    $ctx =
        stream_context_create(
            array(
                'http' =>
                    array(
                        'method' => 'GET',
                        'timeout' => 3,
                        'ignore_errors' => true
                    )
            )
        );


    $response =
        @file_get_contents(
            $url,
            false,
            $ctx
        );


    if(
        $response === false
    ){

        return 'Unknown';
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

$url =
    ag_web_local_base(ag_dg_diagnostics_port()) . '/' .
    '?command=regionlist' .
    '&page=1' .
    '&rp=500' .
    '&sortorder=asc';


$json =
    @file_get_contents(
        $url
    );


if(
    $json === false
){

    http_response_code(
        502
    );

    echo json_encode(
        array(
            'ok' => false,
            'error' => 'Region list unavailable'
        ),
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    return;
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

    http_response_code(
        502
    );

    echo json_encode(
        array(
            'ok' => false,
            'error' => 'Invalid grid service response'
        ),
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    return;
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


$out =
    array();


foreach(
    $rows
    as
    $row
){

    $c =
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
                $c['RegionName'] ??
                ''
            )
        );


    if(
        $regionName === ''
    ){

        continue;
    }


    /*
     * USER MAP SAFE OUTPUT.
     */

    $out[] =
        array(

            'RegionName' =>
                $regionName,

            'EstateName' =>
                (string)(
                    $c['EstateName'] ??
                    ''
                ),

            'AvatarCount' =>
                (int)(
                    $c['AvatarCount'] ??
                    0
                ),

            'Size' =>
                (string)(
                    $c['Size'] ??
                    ''
                ),

            'Status' =>
                dgUserRegionStatus(
                    $regionName
                )

        );
}


/*
 ============================================================
 STABLE REGION ORDER
 ============================================================
*/

usort(
    $out,
    function(
        $a,
        $b
    ){

        return strcasecmp(
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


echo json_encode(
    array(
        'ok' => true,
        'regions' => $out
    ),
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);
