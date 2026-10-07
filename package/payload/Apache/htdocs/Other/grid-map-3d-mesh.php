<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_login();
if (!is_string($_GET['uuid'] ?? '') || !is_string($_GET['lod'] ?? 'high')) {
    http_response_code(400); exit('{"ok":false,"error":"Invalid mesh request."}');
}
$lod = $_GET['lod'] ?? 'high';
if (!in_array($lod, ['high','medium','low','lowest'], true)) {
    http_response_code(400); exit('{"ok":false,"error":"Invalid mesh LOD."}');
}
$requestedLod = $lod . '_lod';
$requestStarted = microtime(true);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

@set_time_limit(30);

/*
 * ============================================================
 * REAL OPENSIM HIGH-LOD MULTI-SUBMESH V6A
 *
 * Proof asset only:
 * b440c6cc-3587-4b9c-b689-259cbcced075
 *
 * Source:
 * private localhost Robust FSAsset service, port 8003.
 *
 * Decodes:
 * binary LLSD asset header
 * -> high_lod offset / size
 * -> zlib data
 * -> binary LLSD high LOD
 * -> quantized positions, normals, UVs and triangle indices.
 *
 * No textures yet.
 * ============================================================
 */

$uuid =
    strtolower(
        trim(
            (string)($_GET['uuid'] ?? '')
        )
    );

if (
    !preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D',
        $uuid
    )
) {

    http_response_code(400);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'Invalid mesh asset UUID.'
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
 * ------------------------------------------------------------
 * Error helper
 * ------------------------------------------------------------
 */

function agm_fail(
    string $message,
    int $status = 500
): void {

    http_response_code(
        $status
    );

    echo json_encode(
        [
            'ok' => false,
            'error' => $message
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
 * ------------------------------------------------------------
 * Binary stream helpers
 * ------------------------------------------------------------
 */

function agm_require_bytes(
    string $data,
    int $position,
    int $length
): void {

    if (
        $position < 0 ||
        $length < 0 ||
        ($position + $length) > strlen($data)
    ) {

        throw new RuntimeException(
            'Unexpected end of binary LLSD stream.'
        );
    }
}


function agm_read_bytes(
    string $data,
    int &$position,
    int $length
): string {

    agm_require_bytes(
        $data,
        $position,
        $length
    );

    $value =
        substr(
            $data,
            $position,
            $length
        );

    $position +=
        $length;

    return $value;
}


function agm_read_byte(
    string $data,
    int &$position
): int {

    agm_require_bytes(
        $data,
        $position,
        1
    );

    $value =
        ord(
            $data[$position]
        );

    $position++;

    return $value;
}


function agm_read_i32_be(
    string $data,
    int &$position
): int {

    $raw =
        agm_read_bytes(
            $data,
            $position,
            4
        );

    $value =
        unpack(
            'Nvalue',
            $raw
        )['value'];

    if ($value >= 2147483648) {

        $value -=
            4294967296;
    }

    return (int)$value;
}


function agm_read_double_be(
    string $data,
    int &$position
): float {

    $raw =
        agm_read_bytes(
            $data,
            $position,
            8
        );

    $result =
        unpack(
            'Evalue',
            $raw
        );

    return (float)$result['value'];
}


function agm_read_string(
    string $data,
    int &$position
): string {

    $length =
        agm_read_i32_be(
            $data,
            $position
        );

    if (
        $length < 0 ||
        $length > 100000000
    ) {

        throw new RuntimeException(
            'Invalid LLSD string length.'
        );
    }

    return agm_read_bytes(
        $data,
        $position,
        $length
    );
}


/*
 * ------------------------------------------------------------
 * Binary LLSD parser
 * ------------------------------------------------------------
 */

function agm_read_value(
    string $data,
    int &$position
) {

    $marker =
        chr(
            agm_read_byte(
                $data,
                $position
            )
        );

    switch ($marker) {

        case '!':
            return null;

        case '0':
            return false;

        case '1':
            return true;

        case 'i':
            return agm_read_i32_be(
                $data,
                $position
            );

        case 'r':
        case 'd':
            return agm_read_double_be(
                $data,
                $position
            );

        case 's':
        case 'l':
            return agm_read_string(
                $data,
                $position
            );

        case 'u':

            $raw =
                agm_read_bytes(
                    $data,
                    $position,
                    16
                );

            $hex =
                bin2hex(
                    $raw
                );

            return
                substr($hex, 0, 8) . '-' .
                substr($hex, 8, 4) . '-' .
                substr($hex, 12, 4) . '-' .
                substr($hex, 16, 4) . '-' .
                substr($hex, 20, 12);

        case 'b':

            $length =
                agm_read_i32_be(
                    $data,
                    $position
                );

            if (
                $length < 0 ||
                $length > 500000000
            ) {

                throw new RuntimeException(
                    'Invalid LLSD binary length.'
                );
            }

            return
            [
                '__binary' =>
                    agm_read_bytes(
                        $data,
                        $position,
                        $length
                    )
            ];

        case '[':

            $count =
                agm_read_i32_be(
                    $data,
                    $position
                );

            if (
                $count < 0 ||
                $count > 1000000
            ) {

                throw new RuntimeException(
                    'Invalid LLSD array count.'
                );
            }

            $array =
                [];

            for (
                $i = 0;
                $i < $count;
                $i++
            ) {

                $array[] =
                    agm_read_value(
                        $data,
                        $position
                    );
            }

            $closing =
                chr(
                    agm_read_byte(
                        $data,
                        $position
                    )
                );

            if ($closing !== ']') {

                throw new RuntimeException(
                    'LLSD array closing marker missing.'
                );
            }

            return $array;

        case '{':

            $count =
                agm_read_i32_be(
                    $data,
                    $position
                );

            if (
                $count < 0 ||
                $count > 1000000
            ) {

                throw new RuntimeException(
                    'Invalid LLSD map count.'
                );
            }

            $map =
                [];

            for (
                $i = 0;
                $i < $count;
                $i++
            ) {

                $keyMarker =
                    chr(
                        agm_read_byte(
                            $data,
                            $position
                        )
                    );

                if ($keyMarker !== 'k') {

                    throw new RuntimeException(
                        'LLSD map key marker missing.'
                    );
                }

                $key =
                    agm_read_string(
                        $data,
                        $position
                    );

                $map[$key] =
                    agm_read_value(
                        $data,
                        $position
                    );
            }

            $closing =
                chr(
                    agm_read_byte(
                        $data,
                        $position
                    )
                );

            if ($closing !== '}') {

                throw new RuntimeException(
                    'LLSD map closing marker missing.'
                );
            }

            return $map;

        default:

            throw new RuntimeException(
                sprintf(
                    'Unknown LLSD marker 0x%02X.',
                    ord($marker)
                )
            );
    }
}


/*
 * ------------------------------------------------------------
 * Binary field helper
 * ------------------------------------------------------------
 */

function agm_binary_field(
    array $map,
    string $name
): string {

    if (
        !isset($map[$name]) ||
        !is_array($map[$name]) ||
        !array_key_exists(
            '__binary',
            $map[$name]
        ) ||
        !is_string(
            $map[$name]['__binary']
        )
    ) {

        throw new RuntimeException(
            'Mesh binary field missing: ' .
            $name
        );
    }

    return $map[$name]['__binary'];
}


/*
 * ------------------------------------------------------------
 * Little-endian quantized ushort array
 * ------------------------------------------------------------
 */

function agm_u16_values(
    string $binary
): array {

    if (
        (strlen($binary) % 2) !== 0
    ) {

        throw new RuntimeException(
            'Quantized mesh field has odd byte length.'
        );
    }

    if ($binary === '') {
        return [];
    }

    $values =
        unpack(
            'v*',
            $binary
        );

    return array_values(
        $values
    );
}


/*
 * ------------------------------------------------------------
 * Fetch actual mesh bytes from private Robust FSAssets.
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/core/map3d-cache.php';
$cachePath = map3d_cache_path('mesh', 'llsd-v2-basis-x-z-minusy:' . $requestedLod . ':' . $uuid);
$cachedMesh = map3d_cache_read($cachePath, $uuid);
if ($cachedMesh !== null) {
    header('X-Map3D-Cache: HIT');
    header('Server-Timing: cache;dur=' . round((microtime(true)-$requestStarted)*1000, 3));
    echo $cachedMesh; exit;
}
// Fixed stripes bound lock-file count and suppress duplicate conversions across entry points.
$stripe = hexdec(substr(hash('sha256', $uuid), 0, 2)) % 16;
$meshLock = @fopen(dirname($cachePath) . '/decode-' . $stripe . '.lock', 'c');
if (!$meshLock) agm_fail('Mesh cache lock unavailable.', 503);
$lockDeadline = microtime(true) + 15;
while (!flock($meshLock, LOCK_EX | LOCK_NB)) {
    if (microtime(true) >= $lockDeadline) agm_fail('Mesh conversion is busy. Retry shortly.', 503);
    usleep(25000);
}
$cachedMesh = map3d_cache_read($cachePath, $uuid);
if ($cachedMesh !== null) { header('X-Map3D-Cache: HIT'); echo $cachedMesh; exit; }
header('X-Map3D-Cache: MISS');
$fetchStarted = microtime(true);
$assetUrl =
    ag_dg_private_robust_base() . '/assets/' .
    rawurlencode($uuid) .
    '/data';

$context =
    stream_context_create(
        [
            'http' =>
            [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => false,
                'header' =>
                    "Connection: close\r\n"
            ]
        ]
    );

$asset =
    @file_get_contents(
        $assetUrl,
        false,
        $context
    );

if (
    !is_string($asset) ||
    strlen($asset) < 16
) {

    agm_fail(
        'Robust mesh asset could not be read.',
        502
    );
}


$fetchMilliseconds = (microtime(true) - $fetchStarted) * 1000;
$decodeStarted = microtime(true);
/*
 * ------------------------------------------------------------
 * Decode top-level mesh header.
 * ------------------------------------------------------------
 */

try {

    $position =
        0;

    $header =
        agm_read_value(
            $asset,
            $position
        );

    $chosenLod = isset($header[$requestedLod]) && is_array($header[$requestedLod])
        && ($header[$requestedLod]['size'] ?? 0) > 0 ? $requestedLod : 'high_lod';
    $headerEnd =
        $position;

    if (
        !is_array($header) ||
        !isset($header[$chosenLod]) ||
        !is_array($header[$chosenLod])
    ) {

        throw new RuntimeException(
            'Mesh high_lod descriptor missing.'
        );
    }

    $highOffset =
        (int)($header[$chosenLod]['offset'] ?? -1);

    $highSize =
        (int)($header[$chosenLod]['size'] ?? -1);

    if (
        $highOffset < 0 ||
        $highSize <= 0
    ) {

        throw new RuntimeException(
            'Mesh high_lod offset or size invalid.'
        );
    }

    $compressedStart =
        $headerEnd +
        $highOffset;

    if (
        ($compressedStart + $highSize) >
        strlen($asset)
    ) {

        throw new RuntimeException(
            'Mesh high_lod block outside asset.'
        );
    }

    $compressed =
        substr(
            $asset,
            $compressedStart,
            $highSize
        );

    $highData =
        @gzuncompress(
            $compressed
        );

    if ($highData === false) {

        $highData =
            @zlib_decode(
                $compressed
            );
    }

    if (
        !is_string($highData) ||
        $highData === ''
    ) {

        throw new RuntimeException(
            'Mesh high_lod zlib decompression failed.'
        );
    }

    $highPosition =
        0;

    $high =
        agm_read_value(
            $highData,
            $highPosition
        );

    if (!is_array($high)) {

        throw new RuntimeException(
            'Mesh high_lod LLSD root invalid.'
        );
    }

    /*
     * ========================================================
     * REAL OPENSIM HIGH-LOD MULTI-SUBMESH V6A
     *
     * Supports Linden / OpenSim high_lod LLSD containing
     * one through eight geometry submeshes.
     *
     * Submeshes are merged into one geometry response.
     * Triangle indices are offset for each merged submesh.
     *
     * LOCKED LOCAL BASIS:
     *
     * OpenSim X -> Three X
     * OpenSim Z -> Three Y
     * OpenSim Y -> Three -Z
     *
     * No material or texture reconstruction yet.
     * ========================================================
     */

    $submeshes =
        array_is_list($high)
            ? $high
            : [$high];

    $rawSubmeshCount =
        count($submeshes);

    if (
        $rawSubmeshCount < 1 ||
        $rawSubmeshCount > 8
    ) {

        throw new RuntimeException(
            'Mesh high_lod submesh count is outside supported range 1-8.'
        );
    }


    $positions =
        [];

    $normals =
        [];

    $uvs =
        [];

    $triangleValues =
        [];

    
/*
    
 * ========================================================
    
 * REAL OPENSIM MATERIAL SLOT BOUNDARIES V6C-F2
    
 *
    
 * Geometry remains merged exactly as V6A.
    
 * These records preserve each original LL material slot.
    
 * Slot is the original high_lod array index.
    
 * ========================================================
    
 */

    
$submeshMaterialSlots =
    
    [];

    $vertexOffset =
        0;

    $decodedSubmeshCount =
        0;

    $allNormalsComplete =
        true;

    $allUvsComplete =
        true;


    foreach (
        $submeshes as
        $submeshIndex => $submesh
    ) {

        if (!is_array($submesh)) {

            throw new RuntimeException(
                'Mesh high_lod contains an invalid submesh.'
            );
        }


        /*
         * Explicit empty material slot.
         */

        if (
            !empty($submesh['NoGeometry'])
        ) {

            continue;
        }


        $hasPosition =
            isset($submesh['Position']);

        $hasTriangles =
            isset($submesh['TriangleList']);


        /*
         * Empty placeholder map.
         */

        if (
            !$hasPosition &&
            !$hasTriangles
        ) {

            continue;
        }


        $submeshVertexStart =             $vertexOffset;          $submeshIndexStart =             count($triangleValues); 
        /*
         * Half-present geometry is invalid.
         */

        if (
            !$hasPosition ||
            !$hasTriangles
        ) {

            throw new RuntimeException(
                'Mesh submesh geometry fields are incomplete.'
            );
        }


        $positionBinary =
            agm_binary_field(
                $submesh,
                'Position'
            );

        $triangleBinary =
            agm_binary_field(
                $submesh,
                'TriangleList'
            );

        $normalBinary =
            isset($submesh['Normal'])
                ? agm_binary_field(
                    $submesh,
                    'Normal'
                )
                : '';

        $texBinary =
            isset($submesh['TexCoord0'])
                ? agm_binary_field(
                    $submesh,
                    'TexCoord0'
                )
                : '';


        $subPositionValues =
            agm_u16_values(
                $positionBinary
            );

        $subTriangleValues =
            agm_u16_values(
                $triangleBinary
            );

        $subNormalValues =
            $normalBinary !== ''
                ? agm_u16_values(
                    $normalBinary
                )
                : [];

        $subTexValues =
            $texBinary !== ''
                ? agm_u16_values(
                    $texBinary
                )
                : [];


        if (
            (count($subPositionValues) % 3) !== 0
        ) {

            throw new RuntimeException(
                'Mesh submesh position count invalid.'
            );
        }

        if (
            (count($subTriangleValues) % 3) !== 0
        ) {

            throw new RuntimeException(
                'Mesh submesh triangle list count invalid.'
            );
        }


        $subVertexCount =
            intdiv(
                count($subPositionValues),
                3
            );

        if ($subVertexCount <= 0) {

            continue;
        }


        /*
         * Position quantization domain.
         */

        $positionDomain =
            $submesh['PositionDomain'] ??
            null;

        if (
            !is_array($positionDomain) ||
            !isset($positionDomain['Min']) ||
            !isset($positionDomain['Max']) ||
            !is_array($positionDomain['Min']) ||
            !is_array($positionDomain['Max']) ||
            count($positionDomain['Min']) < 3 ||
            count($positionDomain['Max']) < 3
        ) {

            throw new RuntimeException(
                'Mesh submesh PositionDomain missing.'
            );
        }


        $pMin =
        [
            (float)$positionDomain['Min'][0],
            (float)$positionDomain['Min'][1],
            (float)$positionDomain['Min'][2]
        ];

        $pMax =
        [
            (float)$positionDomain['Max'][0],
            (float)$positionDomain['Max'][1],
            (float)$positionDomain['Max'][2]
        ];


        /*
         * Positions.
         *
         * OpenSim X -> Three X
         * OpenSim Z -> Three Y
         * OpenSim Y -> Three -Z
         */

        for (
            $vertex = 0;
            $vertex < $subVertexCount;
            $vertex++
        ) {

            $base =
                $vertex *
                3;

            $qx =
                $subPositionValues[$base];

            $qy =
                $subPositionValues[$base + 1];

            $qz =
                $subPositionValues[$base + 2];


            $x =
                $pMin[0] +
                (
                    ($qx / 65535.0) *
                    ($pMax[0] - $pMin[0])
                );

            $y =
                $pMin[1] +
                (
                    ($qy / 65535.0) *
                    ($pMax[1] - $pMin[1])
                );

            $z =
                $pMin[2] +
                (
                    ($qz / 65535.0) *
                    ($pMax[2] - $pMin[2])
                );


            $positions[] =
                $x;

            $positions[] =
                $z;

            $positions[] =
                -$y;
        }


        /*
         * Triangle indices.
         */

        foreach (
            $subTriangleValues as
            $indexValue
        ) {

            $indexValue =
                (int)$indexValue;

            if (
                $indexValue < 0 ||
                $indexValue >= $subVertexCount
            ) {

                throw new RuntimeException(
                    'Mesh submesh triangle index is outside its vertex range.'
                );
            }

            $triangleValues[] =
                $vertexOffset +
                $indexValue;
        }


        /*
         * Normals.
         */

        if (
            count($subNormalValues) ===
            ($subVertexCount * 3)
        ) {

            for (
                $vertex = 0;
                $vertex < $subVertexCount;
                $vertex++
            ) {

                $base =
                    $vertex *
                    3;

                $nx =
                    (
                        $subNormalValues[$base] /
                        65535.0
                    ) *
                    2.0 -
                    1.0;

                $ny =
                    (
                        $subNormalValues[$base + 1] /
                        65535.0
                    ) *
                    2.0 -
                    1.0;

                $nz =
                    (
                        $subNormalValues[$base + 2] /
                        65535.0
                    ) *
                    2.0 -
                    1.0;


                $normals[] =
                    $nx;

                $normals[] =
                    $nz;

                $normals[] =
                    -$ny;
            }
        }
        else {

            $allNormalsComplete =
                false;
        }


        /*
         * UVs.
         */

        $subUvs =
            [];

        if (
            count($subTexValues) ===
            ($subVertexCount * 2)
        ) {

            $texDomain =
                $submesh['TexCoord0Domain'] ??
                null;

            if (
                is_array($texDomain) &&
                isset($texDomain['Min']) &&
                isset($texDomain['Max']) &&
                is_array($texDomain['Min']) &&
                is_array($texDomain['Max']) &&
                count($texDomain['Min']) >= 2 &&
                count($texDomain['Max']) >= 2
            ) {

                $uMin =
                    (float)$texDomain['Min'][0];

                $vMin =
                    (float)$texDomain['Min'][1];

                $uMax =
                    (float)$texDomain['Max'][0];

                $vMax =
                    (float)$texDomain['Max'][1];


                for (
                    $vertex = 0;
                    $vertex < $subVertexCount;
                    $vertex++
                ) {

                    $base =
                        $vertex *
                        2;

                    $u =
                        $uMin +
                        (
                            (
                                $subTexValues[$base] /
                                65535.0
                            ) *
                            ($uMax - $uMin)
                        );

                    $v =
                        $vMin +
                        (
                            (
                                $subTexValues[$base + 1] /
                                65535.0
                            ) *
                            ($vMax - $vMin)
                        );


                    $subUvs[] =
                        $u;

                    $subUvs[] =
                        $v;
                }
            }
        }


        if (
            count($subUvs) ===
            ($subVertexCount * 2)
        ) {

            foreach (
                $subUvs as
                $uvValue
            ) {

                $uvs[] =
                    $uvValue;
            }
        }
        else {

            $allUvsComplete =
                false;
        }


        $submeshIndexCount =             count($triangleValues) -             $submeshIndexStart;          $submeshMaterialSlots[] =         [             'Slot' =>                 (int)$submeshIndex,              'VertexStart' =>                 $submeshVertexStart,              'VertexCount' =>                 $subVertexCount,              'IndexStart' =>                 $submeshIndexStart,              'IndexCount' =>                 $submeshIndexCount         ]; 
        $vertexOffset +=
            $subVertexCount;

        $decodedSubmeshCount++;
    }


    if (
        $decodedSubmeshCount <= 0 ||
        $vertexOffset <= 0 ||
        count($triangleValues) < 3
    ) {

        throw new RuntimeException(
            'Mesh contains no usable high-LOD geometry.'
        );
    }


    $vertexCount =
        $vertexOffset;


    /*
     * A partially missing normal stream cannot align with the
     * merged vertex buffer. Let Three.js regenerate normals.
     */

    if (!$allNormalsComplete) {

        $normals =
            [];
    }


    /*
     * Same rule for UVs until materials/textures are enabled.
     */

    if (!$allUvsComplete) {

        $uvs =
            [];
    }

    $encodedMesh = json_encode(
        [
            'ok' => true,
            'Lod' => $chosenLod,

            'MeshUuid' =>
                $uuid,

            'Source' =>
                'ROBUST_FSASSET_HIGH_LOD',

            'SubmeshCount' =>
                $decodedSubmeshCount,

            
'RawSubmeshCount' =>
                
$rawSubmeshCount,

            
'Submeshes' =>
                
$submeshMaterialSlots,

            'VertexCount' =>
                $vertexCount,

            'TriangleCount' =>
                intdiv(
                    count($triangleValues),
                    3
                ),

            'Positions' =>
                $positions,

            'Normals' =>
                $normals,

            'Uvs' =>
                $uvs,

            'Indices' =>
                $triangleValues
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_PRESERVE_ZERO_FRACTION
    );
    if (!is_string($encodedMesh)) throw new RuntimeException('Mesh JSON encoding failed.');
    map3d_cache_write($cachePath, $encodedMesh);
    header('Server-Timing: asset;dur=' . round($fetchMilliseconds,3) . ', decode;dur=' . round((microtime(true)-$decodeStarted)*1000,3));
    echo $encodedMesh;
}
catch (Throwable $error) {

    agm_fail(
        $error->getMessage()
    );
}
