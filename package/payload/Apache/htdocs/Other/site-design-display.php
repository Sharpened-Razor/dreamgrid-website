<?php
declare(strict_types=1);

require_once __DIR__ . '/core/site-design-assets.php';


$key =
    trim(
        (string)
        (
            isset($_GET['slot'])
                ? $_GET['slot']
                : ''
        )
    );


if (!ag_site_design_slot($key)) {

    http_response_code(404);

    exit;
}


$settings =
    ag_site_design_display_settings(
        $key
    );


$fit =
    isset($settings['fit'])
        ? (string)$settings['fit']
        : 'fill';


$position =
    isset($settings['position'])
        ? (string)$settings['position']
        : 'center';


$size =
    ag_site_design_fit_css(
        $fit,
        false
    );


$dynamicSize =
    ag_site_design_fit_css(
        $fit,
        true
    );


$positionCss =
    ag_site_design_position_css(
        $position
    );


$cleanKey =
    preg_replace(
        '/[^A-Za-z0-9_-]+/',
        '-',
        $key
    );


if (!is_string($cleanKey)) {

    http_response_code(500);

    exit;
}


$prefix =
    '--ag-site-' .
    $cleanKey;


header(
    'Content-Type: text/css; charset=UTF-8'
);

header(
    'Cache-Control: no-cache, no-store, must-revalidate'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);


echo ":root{\n";

echo
    "    " .
    $prefix .
    "-size:" .
    $size .
    ";\n";

echo
    "    " .
    $prefix .
    "-size-dynamic:" .
    $dynamicSize .
    ";\n";

echo
    "    " .
    $prefix .
    "-position:" .
    $positionCss .
    ";\n";

echo "}\n";
