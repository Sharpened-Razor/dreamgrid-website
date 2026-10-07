<?php

declare(strict_types=1);
require_once __DIR__.'/core/website-runtime.php';

/*
 * ============================================================
 * AUSTRALIA REGIONS V7.7F
 * FAST DIRECT OPENSIM TELEMETRY
 *
 * - No cURL dependency
 * - No proxy
 * - No DNS
 * - No DreamGrid /Stats
 * - Reads HTTP Content-Length instead of waiting for EOF
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');


function v77f_dechunk(string $body): string
{
    $output = '';
    $position = 0;
    $length = strlen($body);

    while ($position < $length) {

        $lineEnd =
            strpos(
                $body,
                "\r\n",
                $position
            );

        if ($lineEnd === false) {
            break;
        }

        $sizeText =
            trim(
                substr(
                    $body,
                    $position,
                    $lineEnd - $position
                )
            );

        $semicolon =
            strpos(
                $sizeText,
                ';'
            );

        if ($semicolon !== false) {

            $sizeText =
                substr(
                    $sizeText,
                    0,
                    $semicolon
                );
        }

        if ($sizeText === '') {
            break;
        }

        $chunkSize =
            hexdec(
                $sizeText
            );

        $position =
            $lineEnd + 2;

        if ($chunkSize === 0) {
            break;
        }

        if (($position + $chunkSize) > $length) {
            break;
        }

        $output .=
            substr(
                $body,
                $position,
                $chunkSize
            );

        $position +=
            $chunkSize + 2;
    }

    return $output;
}


function v77f_fetch(int $port): ?array
{
    $errno = 0;
    $errstr = '';

    $socket =
        @stream_socket_client(
            'tcp://'.ag_web_authority(ag_web_local_host(),$port),
            $errno,
            $errstr,
            0.45,
            STREAM_CLIENT_CONNECT
        );

    if ($socket === false) {
        return null;
    }


    stream_set_blocking(
        $socket,
        true
    );


    stream_set_timeout(
        $socket,
        1
    );


    $request =
        "GET /jsonSimStats HTTP/1.1\r\n"
        .
        "Host: ".ag_web_authority(ag_web_local_host(),$port)
        .
        "\r\n"
        .
        "Accept: application/json\r\n"
        .
        "Connection: close\r\n"
        .
        "\r\n";


    $written =
        @fwrite(
            $socket,
            $request
        );


    if ($written === false) {

        fclose(
            $socket
        );

        return null;
    }


    /*
     * Read only until the HTTP headers are complete.
     */

    $raw = '';

    while (
        strpos(
            $raw,
            "\r\n\r\n"
        ) === false
    ) {

        $chunk =
            fread(
                $socket,
                4096
            );


        if ($chunk === false || $chunk === '') {

            $meta =
                stream_get_meta_data(
                    $socket
                );


            if (
                !empty(
                    $meta['timed_out']
                )
                ||
                feof(
                    $socket
                )
            ) {

                fclose(
                    $socket
                );

                return null;
            }


            continue;
        }


        $raw .=
            $chunk;


        if (strlen($raw) > 65536) {

            fclose(
                $socket
            );

            return null;
        }
    }


    $headerEnd =
        strpos(
            $raw,
            "\r\n\r\n"
        );


    $headers =
        substr(
            $raw,
            0,
            $headerEnd
        );


    $body =
        substr(
            $raw,
            $headerEnd + 4
        );


    if (
        !preg_match(
            '#^HTTP/[0-9.]+\s+(\d+)#i',
            $headers,
            $statusMatch
        )
    ) {

        fclose(
            $socket
        );

        return null;
    }


    $status =
        (int)$statusMatch[1];


    if ($status < 200 || $status >= 300) {

        fclose(
            $socket
        );

        return null;
    }


    /*
     * If OpenSim supplied Content-Length, read exactly that many
     * bytes and STOP.  Do not sit around waiting for EOF.
     */

    $contentLength = null;


    if (
        preg_match(
            '/^Content-Length:\s*(\d+)\s*$/mi',
            $headers,
            $lengthMatch
        )
    ) {

        $contentLength =
            (int)$lengthMatch[1];
    }


    if ($contentLength !== null) {

        while (
            strlen($body) <
            $contentLength
        ) {

            $remaining =
                $contentLength -
                strlen($body);


            $chunk =
                fread(
                    $socket,
                    min(
                        8192,
                        $remaining
                    )
                );


            if ($chunk === false || $chunk === '') {

                $meta =
                    stream_get_meta_data(
                        $socket
                    );


                if (
                    !empty(
                        $meta['timed_out']
                    )
                    ||
                    feof(
                        $socket
                    )
                ) {

                    break;
                }


                continue;
            }


            $body .=
                $chunk;
        }


        if (
            strlen($body) >
            $contentLength
        ) {

            $body =
                substr(
                    $body,
                    0,
                    $contentLength
                );
        }
    }
    elseif (
        stripos(
            $headers,
            'Transfer-Encoding: chunked'
        )
        !==
        false
    ) {

        while (
            strpos(
                $body,
                "\r\n0\r\n\r\n"
            )
            ===
            false
        ) {

            $chunk =
                fread(
                    $socket,
                    8192
                );


            if ($chunk === false || $chunk === '') {

                $meta =
                    stream_get_meta_data(
                        $socket
                    );


                if (
                    !empty(
                        $meta['timed_out']
                    )
                    ||
                    feof(
                        $socket
                    )
                ) {

                    break;
                }


                continue;
            }


            $body .=
                $chunk;
        }


        $body =
            v77f_dechunk(
                $body
            );
    }
    else {

        /*
         * Unknown response framing.
         *
         * Keep reading, but stop immediately as soon as a complete
         * JSON document can be decoded.
         */

        while (true) {

            $decoded =
                json_decode(
                    trim($body),
                    true
                );


            if (is_array($decoded)) {

                fclose(
                    $socket
                );

                return $decoded;
            }


            $chunk =
                fread(
                    $socket,
                    8192
                );


            if ($chunk === false || $chunk === '') {

                break;
            }


            $body .=
                $chunk;
        }
    }


    fclose(
        $socket
    );


    $json =
        json_decode(
            trim($body),
            true
        );


    if (!is_array($json)) {
        return null;
    }


    return $json;
}


function v77f_value(array $json, string $key)
{
    if (!array_key_exists($key, $json)) {
        return null;
    }


    $value =
        $json[$key];


    if (
        is_int($value)
        ||
        is_float($value)
    ) {

        return $value;
    }


    if (
        is_string($value)
        &&
        is_numeric(
            trim($value)
        )
    ) {

        $value =
            trim($value);


        if (
            strpos(
                $value,
                '.'
            )
            !==
            false
        ) {

            return (float)$value;
        }


        return (int)$value;
    }


    return $value;
}


/*
 * ============================================================
 * DISCOVER OPENSIM HTTP PORTS
 * ============================================================
 */

$outworldzRoot =
    dirname(
        __DIR__,
        3
    );


$regionsRoot =
    $outworldzRoot
    .
    DIRECTORY_SEPARATOR
    .
    'Opensim'
    .
    DIRECTORY_SEPARATOR
    .
    'bin'
    .
    DIRECTORY_SEPARATOR
    .
    'Regions';


$portMap = [];


if (
    is_dir(
        $regionsRoot
    )
) {

    try {

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $regionsRoot,
                    FilesystemIterator::SKIP_DOTS
                )
            );


        foreach (
            $iterator
            as
            $file
        ) {

            if (!$file->isFile()) {
                continue;
            }


            if (
                strtolower(
                    $file->getExtension()
                )
                !==
                'ini'
            ) {

                continue;
            }


            $contents =
                @file_get_contents(
                    $file->getPathname()
                );


            if (!is_string($contents)) {
                continue;
            }


            if (
                preg_match(
                    '/^\s*http_listener_port\s*=\s*"?(\d+)"?/mi',
                    $contents,
                    $match
                )
            ) {

                $port =
                    (int)$match[1];


                if (
                    $port > 0
                    &&
                    $port <= 65535
                ) {

                    $portMap[$port] =
                        true;
                }
            }
        }
    }
    catch (
        Throwable $error
    ) {

        /*
         * Fallback below.
         */
    }
}


/*
 * Current installation fallback.
 */

if (count($portMap) === 0) {

    foreach (
        range(
            8004,
            8014
        )
        as
        $port
    ) {

        $portMap[$port] =
            true;
    }
}


$ports =
    array_keys(
        $portMap
    );


sort(
    $ports,
    SORT_NUMERIC
);


/*
 * ============================================================
 * COLLECT LIVE REGION TELEMETRY
 * ============================================================
 */

$rows = [];


foreach (
    $ports
    as
    $port
) {

    $json =
        v77f_fetch(
            (int)$port
        );


    if ($json === null) {
        continue;
    }


    $regionName =
        trim(
            (string)(
                $json['RegionName']
                ??
                ''
            )
        );


    if ($regionName === '') {
        continue;
    }


    $rows[] =
        [
            'RegionName' =>
                $regionName,

            'Port' =>
                (int)$port,

            'Available' =>
                true,


            /*
             * Names already expected by panel-regions.php
             */

            'SimFPS' =>
                v77f_value(
                    $json,
                    'SimFPS'
                ),

            'PhysicsFPS' =>
                v77f_value(
                    $json,
                    'PhyFPS'
                ),

            'TimeDilation' =>
                v77f_value(
                    $json,
                    'Dilatn'
                ),

            'FrameTime' =>
                v77f_value(
                    $json,
                    'TotlFt'
                ),

            'ActiveScripts' =>
                v77f_value(
                    $json,
                    'AtvScr'
                ),

            'ScriptEvents' =>
                v77f_value(
                    $json,
                    'ScrEPS'
                ),

            'ScriptLPS' =>
                v77f_value(
                    $json,
                    'ScrLPS'
                ),

            'RootAgents' =>
                v77f_value(
                    $json,
                    'RootAg'
                ),

            'ChildAgents' =>
                v77f_value(
                    $json,
                    'ChldAg'
                ),

            'NPCAgents' =>
                v77f_value(
                    $json,
                    'NPCAg'
                ),

            'Prims' =>
                v77f_value(
                    $json,
                    'Prims'
                ),

            'ActivePrims' =>
                v77f_value(
                    $json,
                    'AtvPrm'
                ),

            'NetIn' =>
                v77f_value(
                    $json,
                    'PktsIn'
                ),

            'NetOut' =>
                v77f_value(
                    $json,
                    'PktOut'
                ),


            /*
             * Original OpenSim names for diagnostics.
             */

            'PhyFPS' =>
                v77f_value(
                    $json,
                    'PhyFPS'
                ),

            'AtvScr' =>
                v77f_value(
                    $json,
                    'AtvScr'
                ),

            'ScrEPS' =>
                v77f_value(
                    $json,
                    'ScrEPS'
                ),

            'Dilatn' =>
                v77f_value(
                    $json,
                    'Dilatn'
                ),

            'TotlFt' =>
                v77f_value(
                    $json,
                    'TotlFt'
                ),
        ];
}


echo json_encode(
    [
        'ok' =>
            true,

        'source' =>
            'OpenSim fast direct jsonSimStats V7.7F',

        'regionCount' =>
            count(
                $rows
            ),

        'regions' =>
            $rows,

        'rows' =>
            $rows,
    ],
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);
