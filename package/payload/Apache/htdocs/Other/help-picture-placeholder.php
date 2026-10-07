<?php

require_once __DIR__ . '/core/help-pictures.php';

$key =
    trim(
        (string)
        ($_GET['slot'] ?? '')
    );


$slot =
    ag_help_picture_slot(
        $key
    );


if (!$slot) {

    http_response_code(404);

    header(
        'Content-Type: text/plain; charset=UTF-8'
    );

    echo 'Unknown Help picture slot.';

    exit;
}


$topics =
    ag_help_picture_topics();


$topicId =
    (string)
    ($slot['topic'] ?? '');


$topicLabel =
    isset($topics[$topicId]['label'])
        ? (string)$topics[$topicId]['label']
        : (string)
          ($slot['label'] ?? 'Help Picture');


$slotLabel =
    (string)
    ($slot['label'] ?? 'Help Picture');


function ag_help_picture_svg_escape(
    string $value
): string {

    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_XML1,
        'UTF-8'
    );
}


header(
    'Content-Type: image/svg+xml; charset=UTF-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


$topicLabel =
    ag_help_picture_svg_escape(
        $topicLabel
    );


$slotLabel =
    ag_help_picture_svg_escape(
        $slotLabel
    );

?>
<svg
    xmlns="http://www.w3.org/2000/svg"
    width="1280"
    height="720"
    viewBox="0 0 1280 720">

    <defs>

        <linearGradient
            id="bg"
            x1="0"
            y1="0"
            x2="1"
            y2="1">

            <stop
                offset="0%"
                stop-color="#101719"/>

            <stop
                offset="100%"
                stop-color="#050809"/>

        </linearGradient>

    </defs>

    <rect
        width="1280"
        height="720"
        fill="url(#bg)"/>

    <rect
        x="38"
        y="38"
        width="1204"
        height="644"
        rx="22"
        fill="none"
        stroke="#8a722d"
        stroke-width="3"
        stroke-dasharray="15 11"/>

    <text
        x="640"
        y="275"
        text-anchor="middle"
        font-family="Arial, Helvetica, sans-serif"
        font-size="36"
        font-weight="700"
        fill="#d8b94f">

        HELP PICTURE

    </text>

    <text
        x="640"
        y="345"
        text-anchor="middle"
        font-family="Arial, Helvetica, sans-serif"
        font-size="48"
        font-weight="700"
        fill="#f0f3f1">

        <?=$topicLabel?>

    </text>

    <text
        x="640"
        y="405"
        text-anchor="middle"
        font-family="Arial, Helvetica, sans-serif"
        font-size="22"
        fill="#aab4ae">

        <?=$slotLabel?>

    </text>

    <text
        x="640"
        y="490"
        text-anchor="middle"
        font-family="Arial, Helvetica, sans-serif"
        font-size="20"
        font-weight="700"
        fill="#737e78">

        PICTURE COMING SOON

    </text>

</svg>
