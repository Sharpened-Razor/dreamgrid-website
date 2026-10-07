<?php
/*
 * ============================================================
 * GRID - STATE-CHANGING REQUEST SECURITY
 * ============================================================
 *
 * Used by browser endpoints which control DreamGrid/OpenSim.
 *
 * Requirements:
 *   - POST only
 *   - same-origin browser request
 *   - reject cross-site Fetch Metadata
 *   - verify Origin or Referer
 *
 * Authentication/authorization remains the responsibility
 * of the endpoint itself.
 * ============================================================
 */

if (!function_exists('ag_request_reject_json')) {

    function ag_request_reject_json(
        string $message,
        int $status
    ): never
    {
        http_response_code($status);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        header(
            'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
        );

        echo json_encode(
            [
                'ok' => false,
                'error' => $message
            ]
        );

        exit;
    }
}


if (!function_exists('ag_request_server_origin')) {

    function ag_request_server_origin(): array
    {
        $hostHeader =
            trim(
                (string)(
                    $_SERVER['HTTP_HOST']
                    ?? ''
                )
            );

        if ($hostHeader === '') {
            return [
                'scheme' => '',
                'host'   => '',
                'port'   => 0
            ];
        }

        $https =
            !empty($_SERVER['HTTPS']) &&
            strtolower(
                (string)$_SERVER['HTTPS']
            ) !== 'off';

        $scheme =
            $https
                ? 'https'
                : 'http';

        $parsed =
            parse_url(
                $scheme .
                '://' .
                $hostHeader
            );

        if (!is_array($parsed)) {
            return [
                'scheme' => '',
                'host'   => '',
                'port'   => 0
            ];
        }

        $host =
            strtolower(
                (string)(
                    $parsed['host']
                    ?? ''
                )
            );

        if (isset($parsed['port'])) {
            $port =
                (int)$parsed['port'];
        }
        else {
            $port =
                $scheme === 'https'
                    ? 443
                    : 80;
        }

        return [
            'scheme' => $scheme,
            'host'   => $host,
            'port'   => $port
        ];
    }
}


if (!function_exists('ag_request_parse_origin')) {

    function ag_request_parse_origin(
        string $url
    ): ?array
    {
        $parsed =
            parse_url(
                trim($url)
            );

        if (
            !is_array($parsed) ||
            empty($parsed['scheme']) ||
            empty($parsed['host'])
        ) {
            return null;
        }

        $scheme =
            strtolower(
                (string)$parsed['scheme']
            );

        $host =
            strtolower(
                (string)$parsed['host']
            );

        if (isset($parsed['port'])) {
            $port =
                (int)$parsed['port'];
        }
        elseif ($scheme === 'https') {
            $port = 443;
        }
        elseif ($scheme === 'http') {
            $port = 80;
        }
        else {
            return null;
        }

        return [
            'scheme' => $scheme,
            'host'   => $host,
            'port'   => $port
        ];
    }
}


if (!function_exists('ag_request_same_origin')) {

    function ag_request_same_origin(
        string $url
    ): bool
    {
        $expected =
            ag_request_server_origin();

        $supplied =
            ag_request_parse_origin(
                $url
            );

        if (
            $supplied === null ||
            $expected['host'] === ''
        ) {
            return false;
        }

        return
            hash_equals(
                $expected['scheme'],
                $supplied['scheme']
            ) &&
            hash_equals(
                $expected['host'],
                $supplied['host']
            ) &&
            (int)$expected['port'] ===
            (int)$supplied['port'];
    }
}


if (!function_exists('ag_require_same_origin_post')) {

    function ag_require_same_origin_post(): void
    {
        if (
            strtoupper(
                (string)(
                    $_SERVER['REQUEST_METHOD']
                    ?? ''
                )
            ) !== 'POST'
        ) {
            header(
                'Allow: POST'
            );

            ag_request_reject_json(
                'POST required.',
                405
            );
        }


        /*
         * Modern browsers provide Sec-Fetch-Site.
         *
         * The normal control-panel fetches are same-origin.
         * Cross-site and merely same-site origins are rejected.
         */
        $fetchSite =
            strtolower(
                trim(
                    (string)(
                        $_SERVER['HTTP_SEC_FETCH_SITE']
                        ?? ''
                    )
                )
            );

        if (
            $fetchSite !== '' &&
            $fetchSite !== 'same-origin'
        ) {
            ag_request_reject_json(
                'Cross-site request rejected.',
                403
            );
        }


        /*
         * POST fetch normally supplies Origin.
         * Fall back to Referer for compatible browsers.
         */
        $origin =
            trim(
                (string)(
                    $_SERVER['HTTP_ORIGIN']
                    ?? ''
                )
            );

        if ($origin !== '') {

            if (
                !ag_request_same_origin(
                    $origin
                )
            ) {
                ag_request_reject_json(
                    'Request origin rejected.',
                    403
                );
            }

            return;
        }


        $referer =
            trim(
                (string)(
                    $_SERVER['HTTP_REFERER']
                    ?? ''
                )
            );

        if ($referer !== '') {

            if (
                !ag_request_same_origin(
                    $referer
                )
            ) {
                ag_request_reject_json(
                    'Request referer rejected.',
                    403
                );
            }

            return;
        }


        ag_request_reject_json(
            'Request origin could not be verified.',
            403
        );
    }
}