<?php
declare(strict_types=1);

/*
 * USER 3D MAP READ-ONLY ROUTE
 *
 * Authentication:
 *     logged-in USER session required
 *
 * Browser methods:
 *     GET
 *
 * The rendering implementation remains in the current
 * DreamGrid 3D backend master.
 *
 * The USER 3D page does NOT expose the region reset endpoint.
 */

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

ag_require_login();

$method =
    strtoupper(
        (string)(
            $_SERVER['REQUEST_METHOD'] ??
            'GET'
        )
    );

if (
    !in_array(
        $method,
        ['GET'],
        true
    )
) {
    http_response_code(405);

    header(
        'Allow: GET'
    );

    exit;
}

/*
 * Material batching uses POST, but it is still a
 * read-only metadata operation. Require same-origin POST.
 */
if (
    $method === 'POST' &&
    function_exists(
        'ag_require_same_origin_post'
    )
) {
    ag_require_same_origin_post();
}

require __DIR__ . '/grid-map-3d-objects.php';