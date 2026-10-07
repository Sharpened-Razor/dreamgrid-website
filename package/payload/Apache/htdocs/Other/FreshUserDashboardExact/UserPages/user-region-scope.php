<?php
declare(strict_types=1);

require_once
    dirname(__DIR__, 2) .
    '/core/bootstrap.php';

require_once
    dirname(__DIR__, 2) .
    '/core/dreamgrid-env.php';


function fur_json_fail(
    string $message,
    int $status = 400
): never {

    http_response_code(
        $status
    );

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        [
            'ok' =>
                false,

            'error' =>
                $message,

            'regions' =>
                [],

            'rows' =>
                [],
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function fur_require_user_session(): array
{
    $session =
        ag_current_session();

    if (!$session) {

        fur_json_fail(
            'Not logged in.',
            401
        );
    }

    $avatar =
        trim(
            (string)(
                $session['avatar'] ??
                ''
            )
        );

    if ($avatar === '') {

        fur_json_fail(
            'Signed session does not contain an avatar name.',
            401
        );
    }

    return $session;
}


function fur_region_key(
    string $name
): string {

    return strtolower(
        trim(
            $name
        )
    );
}


function fur_request(
    string $url,
    int $timeout = 8
): ?string {

    $context =
        stream_context_create(
            [
                'http' => [
                    'method' =>
                        'GET',

                    'timeout' =>
                        $timeout,

                    'ignore_errors' =>
                        true,
                ],
            ]
        );

    $result =
        @file_get_contents(
            $url,
            false,
            $context
        );

    return $result === false
        ?
        null
        :
        (string)$result;
}


function fur_owned_region_map(
    array $session
): array {

    $avatar =
        trim(
            (string)(
                $session['avatar'] ??
                ''
            )
        );

    if ($avatar === '') {

        fur_json_fail(
            'Signed session does not contain an avatar name.',
            401
        );
    }


    $url =
        rtrim(
            ag_dg_diagnostics_base(),
            '/'
        ) .
        '/?command=regionlist' .
        '&page=1' .
        '&rp=500' .
        '&sortorder=asc';


    $raw =
        fur_request(
            $url,
            8
        );


    if ($raw === null) {

        fur_json_fail(
            'Region list is unavailable.',
            502
        );
    }


    $data =
        json_decode(
            $raw,
            true
        );


    if (!is_array($data)) {

        fur_json_fail(
            'The grid service returned an invalid region list.',
            502
        );
    }


    $rows =
        isset($data['rows']) &&
        is_array($data['rows'])
            ?
            $data['rows']
            :
            [];


    $owned =
        [];


    foreach ($rows as $row) {

        $cell =
            isset($row['cell']) &&
            is_array($row['cell'])
                ?
                $row['cell']
                :
                [];


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


        if (
            $regionName === '' ||
            strcasecmp(
                $estateOwner,
                $avatar
            ) !== 0
        ) {

            continue;
        }


        $owned[
            fur_region_key(
                $regionName
            )
        ] =
            $regionName;
    }


    return $owned;
}


function fur_filter_region_rows(
    array $rows,
    array $owned
): array {

    $result =
        [];


    foreach ($rows as $row) {

        if (!is_array($row)) {
            continue;
        }


        $regionName =
            trim(
                (string)(
                    $row['RegionName'] ??
                    ''
                )
            );


        if ($regionName === '') {
            continue;
        }


        if (
            !isset(
                $owned[
                    fur_region_key(
                        $regionName
                    )
                ]
            )
        ) {

            continue;
        }


        $result[] =
            $row;
    }


    return $result;
}


function fur_filter_requested_regions(
    array $requested,
    array $owned
): array {

    $result =
        [];


    foreach ($requested as $region) {

        if (!is_string($region)) {
            continue;
        }


        $key =
            fur_region_key(
                $region
            );


        if (
            $key === '' ||
            !isset($owned[$key])
        ) {

            continue;
        }


        $result[$key] =
            $owned[$key];
    }


    return array_values(
        $result
    );
}