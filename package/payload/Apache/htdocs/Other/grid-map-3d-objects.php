<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

require_once __DIR__ . '/core/bootstrap.php';

ag_require_login();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

@set_time_limit(240);

/*
 * ============================================================
 * MULTI-REGION LIVE OPENSIM OBJECTS V4
 *
 * All current DreamGrid regions.
 *
 * Primary:
 *   running OpenSim
 *   -> RemoteAdmin save xml2
 *   -> streaming XMLReader
 *   -> individual current prim transforms.
 *
 * Fallback:
 *   last-known-good live compact JSON.
 *
 * FARM SHOP also retains its proven Stage-2 snapshot as
 * an additional final fallback.
 *
 * Per-region nonblocking flock prevents duplicate exports.
 *
 * Cache lifetime: 15 seconds.
 * ============================================================
 */

$definitions =
[
    [
        'name' => 'Builder Box',
        'aliases' => ['Builder Box'],
        'folder' => 'Builder Box',
        'cache' => 'Builder_Box'
    ],

    [
        'name' => 'Caribia',
        'aliases' => ['Caribia'],
        'folder' => 'Caribia',
        'cache' => 'Caribia'
    ],

    [
        'name' => 'FARM SHOP',
        'aliases' => ['FARM SHOP'],
        'folder' => 'FARM SHOP',
        'cache' => 'FARM_SHOP'
    ],

    [
        'name' => 'Invisable',
        'aliases' => ['Invisable'],
        'folder' => 'Invisable',
        'cache' => 'Invisable'
    ],

    [
        'name' => 'Kansho',
        'aliases' => ['Kansho'],
        'folder' => 'Kansho',
        'cache' => 'Kansho'
    ],

    [
        'name' => 'NO ENTRY',
        'aliases' => ['NO ENTRY'],
        'folder' => 'NO ENTRY',
        'cache' => 'NO_ENTRY'
    ],

    [
        'name' => 'NORTI FARM',
        'aliases' => ['NORTI FARM'],
        'folder' => 'NORTI FARM',
        'cache' => 'NORTI_FARM'
    ],

    [
        'name' => 'Norti Farm 2',
        'aliases' => ['Norti Farm 2'],
        'folder' => 'Norti Farm 2',
        'cache' => 'Norti_Farm_2'
    ],

    [
        'name' => 'Offical Region',
        'aliases' => ['Offical Region', 'Official Region'],
        'folder' => 'Offical Region',
        'cache' => 'Offical_Region'
    ],

    [
        'name' => 'TESTER',
        'aliases' => ['TESTER'],
        'folder' => 'TESTER',
        'cache' => 'TESTER'
    ],

    [
        'name' => 'Welcome',
        'aliases' => ['Welcome'],
        'folder' => 'Welcome',
        'cache' => 'Welcome'
    ]
];

$requestRegion =
    trim(
        (string)($_GET['region'] ?? '')
    );

$definition =
    null;

foreach ($definitions as $candidate) {

    foreach ($candidate['aliases'] as $alias) {

        if (
            strcasecmp(
                $requestRegion,
                $alias
            ) === 0
        ) {

            $definition =
                $candidate;

            break 2;
        }
    }
}

if (!$definition) {

    http_response_code(404);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'Live object region was not recognised.'
        ]
    );

    exit;
}

$regionName =
    (string)$definition['name'];

$regionIni =
    rtrim((string)ag_dg_path('Opensim' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'Regions'), '/\\') . DIRECTORY_SEPARATOR .
    $definition['folder'] .
    '/OpenSim.ini';

$cacheDirectory =
    rtrim((string)ag_dg_path('LiveObject3DCache'), '/\\') . DIRECTORY_SEPARATOR .
    $definition['cache'];

$liveXml =
    $cacheDirectory .
    '/scene.xml';

$liveJson =
    $cacheDirectory .
    '/prims.json';

$lockFile =
    $cacheDirectory .
    '/refresh.lock';

/*
 * LARGE REGION OBJECT CACHE V4B
 *
 * NORTI FARM and TESTER contain tens of thousands of prims
 * and generate XML2 exports larger than 400 MB.
 *
 * Keep smaller regions at the original 15 seconds.
 * Heavy regions refresh every 120 seconds.
 */

$cacheSeconds =
    (
        strcasecmp(
            $regionName,
            'NORTI FARM'
        ) === 0 ||
        strcasecmp(
            $regionName,
            'TESTER'
        ) === 0
    )
        ? 120
        : 15;

$snapshotJson =
    null;

if (
    strcasecmp(
        $regionName,
        'FARM SHOP'
    ) === 0
) {

    $snapshotJson =
        (string)ag_dg_path('LiveObject3DCache' . DIRECTORY_SEPARATOR . 'FARM_SHOP' . DIRECTORY_SEPARATOR . 'fallback-prims.json');
}


/*
 * ------------------------------------------------------------
 * DOM helpers
 * ------------------------------------------------------------
 */

function ag3d_child(
    DOMNode $parent,
    string $name
): ?DOMElement {

    foreach ($parent->childNodes as $child) {

        if (
            $child instanceof DOMElement &&
            $child->localName === $name
        ) {

            return $child;
        }
    }

    return null;
}


function ag3d_number(
    DOMElement $node,
    array $path
): float {

    $current =
        $node;

    foreach ($path as $name) {

        $next =
            ag3d_child(
                $current,
                $name
            );

        if (!$next) {
            return 0.0;
        }

        $current =
            $next;
    }

    $value =
        trim(
            (string)$current->textContent
        );

    if (
        $value === '' ||
        !is_numeric($value)
    ) {

        return 0.0;
    }

    $number =
        (float)$value;

    return is_finite($number)
        ? $number
        : 0.0;
}


function ag3d_vec3(
    DOMElement $part,
    string $name
): array {

    return
    [
        ag3d_number(
            $part,
            [$name, 'X']
        ),

        ag3d_number(
            $part,
            [$name, 'Y']
        ),

        ag3d_number(
            $part,
            [$name, 'Z']
        )
    ];
}


function ag3d_normalize_q(
    array $q
): array {

    $length =
        sqrt(
            ($q[0] * $q[0]) +
            ($q[1] * $q[1]) +
            ($q[2] * $q[2]) +
            ($q[3] * $q[3])
        );

    if ($length < 0.0000001) {

        return [0.0, 0.0, 0.0, 1.0];
    }

    return
    [
        $q[0] / $length,
        $q[1] / $length,
        $q[2] / $length,
        $q[3] / $length
    ];
}


function ag3d_quaternion(
    DOMElement $part,
    string $name
): array {

    return ag3d_normalize_q(
        [
            ag3d_number(
                $part,
                [$name, 'X']
            ),

            ag3d_number(
                $part,
                [$name, 'Y']
            ),

            ag3d_number(
                $part,
                [$name, 'Z']
            ),

            ag3d_number(
                $part,
                [$name, 'W']
            )
        ]
    );
}


function ag3d_q_multiply(
    array $a,
    array $b
): array {

    return ag3d_normalize_q(
        [
            ($a[3] * $b[0]) +
            ($a[0] * $b[3]) +
            ($a[1] * $b[2]) -
            ($a[2] * $b[1]),

            ($a[3] * $b[1]) -
            ($a[0] * $b[2]) +
            ($a[1] * $b[3]) +
            ($a[2] * $b[0]),

            ($a[3] * $b[2]) +
            ($a[0] * $b[1]) -
            ($a[1] * $b[0]) +
            ($a[2] * $b[3]),

            ($a[3] * $b[3]) -
            ($a[0] * $b[0]) -
            ($a[1] * $b[1]) -
            ($a[2] * $b[2])
        ]
    );
}


function ag3d_rotate_vector(
    array $v,
    array $q
): array {

    $tx =
        2.0 *
        (
            ($q[1] * $v[2]) -
            ($q[2] * $v[1])
        );

    $ty =
        2.0 *
        (
            ($q[2] * $v[0]) -
            ($q[0] * $v[2])
        );

    $tz =
        2.0 *
        (
            ($q[0] * $v[1]) -
            ($q[1] * $v[0])
        );

    return
    [
        $v[0] +
        ($q[3] * $tx) +
        (($q[1] * $tz) - ($q[2] * $ty)),

        $v[1] +
        ($q[3] * $ty) +
        (($q[2] * $tx) - ($q[0] * $tz)),

        $v[2] +
        ($q[3] * $tz) +
        (($q[0] * $ty) - ($q[1] * $tx))
    ];
}


/*
 * ------------------------------------------------------------
 * Compact JSON loader
 * ------------------------------------------------------------
 */

function ag3d_load_json(
    string $path,
    string $regionName
): ?array {

    if (!is_file($path)) {
        return null;
    }

    $raw =
        @file_get_contents(
            $path
        );

    if (
        !is_string($raw) ||
        $raw === ''
    ) {

        return null;
    }

    $data =
        json_decode(
            $raw,
            true
        );

    if (
        !is_array($data) ||
        !isset($data['Parts']) ||
        !is_array($data['Parts'])
    ) {

        return null;
    }

    $storedRegion =
        trim(
            (string)($data['Region'] ?? '')
        );

    /*
     * Handle historical Official / Offical spelling.
     */

    $regionMatch =
        strcasecmp(
            $storedRegion,
            $regionName
        ) === 0;

    if (
        !$regionMatch &&
        (
            strcasecmp(
                $storedRegion,
                'Official Region'
            ) === 0 ||
            strcasecmp(
                $storedRegion,
                'Offical Region'
            ) === 0
        ) &&
        (
            strcasecmp(
                $regionName,
                'Official Region'
            ) === 0 ||
            strcasecmp(
                $regionName,
                'Offical Region'
            ) === 0
        )
    ) {

        $regionMatch =
            true;
    }

    if (!$regionMatch) {
        return null;
    }

    $groundParts =
        (int)($data['GroundParts'] ?? -1);

    if (
        $groundParts < 0 ||
        count($data['Parts']) !== $groundParts
    ) {

        return null;
    }

    foreach ($data['Parts'] as $part) {

        if (
            !is_array($part) ||
            count($part) < 10
        ) {

            return null;
        }

        for ($i = 0; $i < 10; $i++) {

            if (
                !isset($part[$i]) ||
                !is_numeric($part[$i]) ||
                !is_finite(
                    (float)$part[$i]
                )
            ) {

                return null;
            }
        }
    }

    return $data;
}


/*
 * ------------------------------------------------------------
 * Browser response
 * ------------------------------------------------------------
 */

function ag3d_emit(
    array $data,
    string $regionName,
    string $source,
    bool $liveAttempted,
    string $liveError = ''
): void {

    echo json_encode(
        [
            'ok' => true,

            'RegionName' =>
                $regionName,

            'Source' =>
                $source,

            'LiveAttempted' =>
                $liveAttempted,

            'LiveError' =>
                $liveError,

            'GroupsTotal' =>
                (int)($data['GroupsTotal'] ?? 0),

            'PartsTotal' =>
                (int)($data['PartsTotal'] ?? 0),

            'GroundGroups' =>
                (int)($data['GroundGroups'] ?? 0),

            'GroundParts' =>
                (int)($data['GroundParts'] ?? 0),

            'MidGroups' =>
                (int)($data['MidGroups'] ?? 0),

            'MidParts' =>
                (int)($data['MidParts'] ?? 0),

            'SkyGroups' =>
                (int)($data['SkyGroups'] ?? 0),

            'SkyParts' =>
                (int)($data['SkyParts'] ?? 0),

            'Generated' =>
                (string)($data['Generated'] ?? ''),

            'Parts' =>
                $data['Parts']
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
 * ------------------------------------------------------------
 * RemoteAdmin settings
 * ------------------------------------------------------------
 */

function ag3d_remote_settings(
    string $regionIni
): array {

    $text =
        @file_get_contents(
            $regionIni
        );

    if (
        !is_string($text) ||
        $text === ''
    ) {

        throw new RuntimeException(
            'Region configuration could not be read.'
        );
    }

    if (
        !preg_match(
            '/(?ms)^\s*\[RemoteAdmin\]\s*\R(.*?)(?=^\s*\[[^\]]+\]\s*$|\z)/',
            $text,
            $sectionMatch
        )
    ) {

        throw new RuntimeException(
            'RemoteAdmin section missing.'
        );
    }

    $remote =
        (string)$sectionMatch[1];

    if (
        !preg_match(
            '/^\s*access_password\s*=\s*"?([^"\r\n;]+)"?\s*(?:;.*)?$/mi',
            $remote,
            $passwordMatch
        )
    ) {

        throw new RuntimeException(
            'RemoteAdmin password missing.'
        );
    }

    $password =
        trim(
            (string)$passwordMatch[1]
        );

    $port =
        0;

    if (
        preg_match(
            '/^\s*port\s*=\s*(\d+)\s*(?:;.*)?$/mi',
            $remote,
            $portMatch
        )
    ) {

        $port =
            (int)$portMatch[1];
    }

    if (
        $port <= 0 &&
        preg_match(
            '/^\s*http_listener_port\s*=\s*(\d+)\s*(?:;.*)?$/mi',
            $text,
            $listenerMatch
        )
    ) {

        $port =
            (int)$listenerMatch[1];
    }

    if (
        $password === '' ||
        $port <= 0
    ) {

        throw new RuntimeException(
            'RemoteAdmin configuration incomplete.'
        );
    }

    return
    [
        'password' => $password,
        'port' => $port
    ];
}


function ag3d_xml_escape(
    string $value
): string {

    return htmlspecialchars(
        $value,
        ENT_XML1 |
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
 * ------------------------------------------------------------
 * RemoteAdmin console command
 * ------------------------------------------------------------
 */

function ag3d_remote_command(
    array $remote,
    string $command
): void {

    $xml =
        '<?xml version="1.0"?>' .
        '<methodCall>' .
        '<methodName>admin_console_command</methodName>' .
        '<params><param><value><struct>' .

        '<member>' .
        '<name>password</name>' .
        '<value><string>' .
        ag3d_xml_escape(
            (string)$remote['password']
        ) .
        '</string></value>' .
        '</member>' .

        '<member>' .
        '<name>command</name>' .
        '<value><string>' .
        ag3d_xml_escape(
            $command
        ) .
        '</string></value>' .
        '</member>' .

        '</struct></value></param></params>' .
        '</methodCall>';

    $context =
        stream_context_create(
            [
                'http' =>
                [
                    'method' => 'POST',

                    'header' =>
                        "Content-Type: text/xml\r\n" .
                        "Connection: close\r\n",

                    'content' => $xml,

                    'timeout' => 60,

                    'ignore_errors' => true
                ]
            ]
        );

    @file_get_contents(
        ag_web_local_base((int)$remote['port']) .
        '/',
        false,
        $context
    );
}


/*
 * ------------------------------------------------------------
 * Wait for completed XML2
 * ------------------------------------------------------------
 */

function ag3d_wait_stable(
    string $path,
    float $seconds = 60.0
): bool {

    $lastSize =
        -1;

    $stable =
        0;

    $deadline =
        microtime(true) +
        $seconds;

    while (
        microtime(true) <
        $deadline
    ) {

        clearstatcache(
            true,
            $path
        );

        if (is_file($path)) {

            $size =
                @filesize(
                    $path
                );

            if (
                is_int($size) ||
                is_float($size)
            ) {

                if (
                    $size > 0 &&
                    $size === $lastSize
                ) {

                    $stable++;
                }
                else {

                    $stable =
                        0;
                }

                $lastSize =
                    $size;

                if ($stable >= 8) {
                    return true;
                }
            }
        }

        usleep(
            250000
        );
    }

    return false;
}


/*
 * ------------------------------------------------------------
 * Streaming XML2 parser
 *
 * Same proven world transform maths as FARM SHOP V2/V3.
 * ------------------------------------------------------------
 */

function ag3d_parse_scene(
    string $xmlFile,
    string $regionName
): array {

    libxml_use_internal_errors(
        true
    );

    libxml_clear_errors();

    $reader =
        new XMLReader();

    if (
        !$reader->open(
            $xmlFile,
            null,
            LIBXML_NONET |
            LIBXML_COMPACT
        )
    ) {

        throw new RuntimeException(
            'Live XML2 could not be opened.'
        );
    }

    $groupsTotal = 0;
    $partsTotal = 0;

    $groundGroups = 0;
    $groundParts = 0;

    $midGroups = 0;
    $midParts = 0;

    $skyGroups = 0;
    $skyParts = 0;

    $failedGroups = 0;

    $outputParts =
        [];

    while ($reader->read()) {

        if (
            $reader->nodeType !== XMLReader::ELEMENT ||
            $reader->localName !== 'SceneObjectGroup'
        ) {

            continue;
        }

        $expanded =
            @$reader->expand();

        if (!$expanded) {

            $failedGroups++;
            continue;
        }

        $document =
            new DOMDocument();

        $group =
            $document->importNode(
                $expanded,
                true
            );

        if (!$group) {

            $failedGroups++;
            continue;
        }

        $document->appendChild(
            $group
        );

        $rootPart =
            ag3d_child(
                $group,
                'SceneObjectPart'
            );

        if (!$rootPart) {

            $failedGroups++;
            continue;
        }

        $parts =
            [$rootPart];

        $otherParts =
            ag3d_child(
                $group,
                'OtherParts'
            );

        if ($otherParts) {

            foreach (
                $otherParts->childNodes
                as $child
            ) {

                if (
                    $child instanceof DOMElement &&
                    $child->localName === 'SceneObjectPart'
                ) {

                    $parts[] =
                        $child;
                }
            }
        }

        $groupsTotal++;

        $partCount =
            count(
                $parts
            );

        $partsTotal +=
            $partCount;

        $groupPosition =
            ag3d_vec3(
                $rootPart,
                'GroupPosition'
            );

        $rootRotation =
            ag3d_quaternion(
                $rootPart,
                'RotationOffset'
            );

        /*
         * Current public map rule:
         *
         * <256m      ground
         * 256-511m   mid
         * 512m+      sky
         */

        if ($groupPosition[2] < 256.0) {

            $class =
                'GROUND';

            $groundGroups++;
            $groundParts += $partCount;
        }
        elseif ($groupPosition[2] < 512.0) {

            $class =
                'MID';

            $midGroups++;
            $midParts += $partCount;
        }
        else {

            $class =
                'SKY';

            $skyGroups++;
            $skyParts += $partCount;
        }

        if ($class !== 'GROUND') {
            continue;
        }

        foreach ($parts as $index => $part) {

            $scale =
                ag3d_vec3(
                    $part,
                    'Scale'
                );

            if (
                $scale[0] <= 0 ||
                $scale[1] <= 0 ||
                $scale[2] <= 0
            ) {

                throw new RuntimeException(
                    'A ground prim had an invalid scale.'
                );
            }

            if ($index === 0) {

                $worldPosition =
                    $groupPosition;

                $worldRotation =
                    $rootRotation;
            }
            else {

                $offset =
                    ag3d_vec3(
                        $part,
                        'OffsetPosition'
                    );

                $rotatedOffset =
                    ag3d_rotate_vector(
                        $offset,
                        $rootRotation
                    );

                $worldPosition =
                [
                    $groupPosition[0] +
                    $rotatedOffset[0],

                    $groupPosition[1] +
                    $rotatedOffset[1],

                    $groupPosition[2] +
                    $rotatedOffset[2]
                ];

                $localRotation =
                    ag3d_quaternion(
                        $part,
                        'RotationOffset'
                    );

                $worldRotation =
                    ag3d_q_multiply(
                        $rootRotation,
                        $localRotation
                    );
            }

            /*
             * REAL OPENSIM MESH ASSET ID V5
             *
             * Existing transform array indices 0-9 remain
             * unchanged. Index 10 contains the mesh asset UUID
             * only when this SceneObjectPart is LL mesh.
             */

            $meshAssetUuid =
                '';

            
$textureEntryBase64 =
            
    '';

            $shapeNode =
                ag3d_child(
                    $part,
                    'Shape'
                );

            if ($shapeNode) {

                
/*
                
 * ========================================================
                
 * REAL OPENSIM TEXTUREENTRY BRIDGE V6C-F1
                
 *
                
 * Raw OpenSim TextureEntry is preserved as Base64.
                
 * Existing array indices 0-10 remain unchanged.
                
 * New index 11 contains TextureEntry or empty string.
                
 * ========================================================
                
 */

                
$textureEntryNode =
                    
ag3d_child(
                    
    $shapeNode,
                    
    'TextureEntry'
                    
);

                
if ($textureEntryNode) {

                    
$candidateTextureEntry =
                    
    trim(
                    
        (string)$textureEntryNode->textContent
                    
    );

                    
if (
                    
    $candidateTextureEntry !== '' &&
                    
    base64_decode(
                    
        $candidateTextureEntry,
                    
        true
                    
    ) !== false
                    
) {

                    
    $textureEntryBase64 =
                    
        $candidateTextureEntry;
                    
}
                
}


                $sculptEntryNode =
                    ag3d_child(
                        $shapeNode,
                        'SculptEntry'
                    );

                $sculptTypeNode =
                    ag3d_child(
                        $shapeNode,
                        'SculptType'
                    );

                $sculptTextureNode =
                    ag3d_child(
                        $shapeNode,
                        'SculptTexture'
                    );

                $sculptEntryText =
                    $sculptEntryNode
                        ? strtolower(
                            trim(
                                (string)$sculptEntryNode->textContent
                            )
                        )
                        : '';

                $sculptEntry =
                    (
                        $sculptEntryText === 'true' ||
                        $sculptEntryText === '1'
                    );

                $sculptType =
                    $sculptTypeNode
                        ? (int)trim(
                            (string)$sculptTypeNode->textContent
                        )
                        : 0;

                $baseSculptType =
                    (
                        $sculptType &
                        7
                    );

                if (
                    $sculptEntry &&
                    $baseSculptType === 5 &&
                    $sculptTextureNode
                ) {

                    $candidateMeshUuid =
                        strtolower(
                            trim(
                                (string)$sculptTextureNode->textContent
                            )
                        );

                    if (
                        preg_match(
                            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                            $candidateMeshUuid
                        )
                    ) {

                        $meshAssetUuid =
                            $candidateMeshUuid;
                    }
                }
            }
            $uuidNode =
                ag3d_child(
                    $part,
                    'UUID'
                );

            $nameNode =
                ag3d_child(
                    $part,
                    'Name'
                );

            $linkNumNode =
                ag3d_child(
                    $part,
                    'LinkNum'
                );

            $rootUuidNode =
                ag3d_child(
                    $rootPart,
                    'UUID'
                );

            $identity = [
                'uuid' =>
                    trim(
                        (string)(
                            $uuidNode
                                ? $uuidNode->textContent
                                : ''
                        )
                    ),

                'name' =>
                    trim(
                        (string)(
                            $nameNode
                                ? $nameNode->textContent
                                : ''
                        )
                    ),

                'link' =>
                    (int)(
                        $linkNumNode
                            ? $linkNumNode->textContent
                            : 0
                    ),

                'rootUuid' =>
                    trim(
                        (string)(
                            $rootUuidNode
                                ? $rootUuidNode->textContent
                                : ''
                        )
                    ),

                'shape' => []
            ];
            if ($shapeNode) {
                foreach (['PCode','State','ProfileCurve','PathCurve','ProfileBegin','ProfileEnd','ProfileHollow','PathBegin','PathEnd','PathScaleX','PathScaleY','PathShearX','PathShearY','PathTwist','PathTwistBegin','PathRadiusOffset','PathTaperX','PathTaperY','PathRevolutions','PathSkew','SculptType','SculptTexture','SculptEntry'] as $field) {
                    $node = ag3d_child($shapeNode, $field);
                    $value = $node ? trim($node->textContent) : '';
                    $identity['shape'][$field] = is_numeric($value) ? 0 + $value : $value;
                }
            }
            $outputParts[] =
            [
                round($worldPosition[0], 5),
                round($worldPosition[1], 5),
                round($worldPosition[2], 5),

                round($scale[0], 5),
                round($scale[1], 5),
                round($scale[2], 5),

                round($worldRotation[0], 8),
                round($worldRotation[1], 8),
                round($worldRotation[2], 8),
                round($worldRotation[3], 8),

                $meshAssetUuid,
                $textureEntryBase64,
                json_encode([$identity['uuid'], $identity['name'], $identity['link'], $identity['rootUuid'], array_values($identity['shape'])], JSON_INVALID_UTF8_SUBSTITUTE)
            ];
        }
    }

    $reader->close();

    $errors =
        libxml_get_errors();

    $xmlErrorCount =
        is_array($errors)
            ? count($errors)
            : 0;

    libxml_clear_errors();

    if ($failedGroups !== 0) {

        throw new RuntimeException(
            'One or more SceneObjectGroups failed parsing.'
        );
    }

    if ($xmlErrorCount !== 0) {

        throw new RuntimeException(
            'Live XML2 contained parser errors.'
        );
    }

    if (
        ($groundGroups + $midGroups + $skyGroups) !==
        $groupsTotal
    ) {

        throw new RuntimeException(
            'SceneObjectGroup classification failed.'
        );
    }

    if (
        ($groundParts + $midParts + $skyParts) !==
        $partsTotal
    ) {

        throw new RuntimeException(
            'SceneObjectPart classification failed.'
        );
    }

    if (
        count($outputParts) !==
        $groundParts
    ) {

        throw new RuntimeException(
            'Ground prim output count failed.'
        );
    }

    return
    [
        'FormatVersion' => 2,
        'Region' => $regionName,

        'Source' =>
            'LIVE_OPENSIM_XML2',

        'Generated' =>
            date(DATE_ATOM),

        'GeneratedUnix' =>
            time(),

        'GroupsTotal' =>
            $groupsTotal,

        'PartsTotal' =>
            $partsTotal,

        'GroundGroups' =>
            $groundGroups,

        'GroundParts' =>
            $groundParts,

        'MidGroups' =>
            $midGroups,

        'MidParts' =>
            $midParts,

        'SkyGroups' =>
            $skyGroups,

        'SkyParts' =>
            $skyParts,

        'Parts' =>
            $outputParts
    ];
}


/*
 * ------------------------------------------------------------
 * Fresh live export
 * ------------------------------------------------------------
 */

function ag3d_refresh(
    string $regionIni,
    string $cacheDirectory,
    string $liveXml,
    string $liveJson,
    string $regionName
): array {

    $remote =
        ag3d_remote_settings(
            $regionIni
        );

    $token =
        date('Ymd-His') .
        '-' .
        bin2hex(
            random_bytes(4)
        );

    $exportFile =
        $cacheDirectory .
        '/scene-' .
        $token .
        '.xml';

    /*
     * Export path deliberately contains no spaces.
     */

    $command =
        'save xml2 ' .
        str_replace(
            '\\',
            '/',
            $exportFile
        );

    ag3d_remote_command(
        $remote,
        $command
    );

    if (
        !ag3d_wait_stable(
            $exportFile,
            60.0
        )
    ) {

        @unlink($exportFile);

        throw new RuntimeException(
            'Live XML2 export did not stabilise.'
        );
    }

    $data =
        ag3d_parse_scene(
            $exportFile,
            $regionName
        );

    $encoded =
        json_encode(
            $data,
            JSON_UNESCAPED_SLASHES
        );

    if (
        !is_string($encoded) ||
        $encoded === ''
    ) {

        @unlink($exportFile);

        throw new RuntimeException(
            'Compact live JSON could not be encoded.'
        );
    }

    $jsonTemp =
        $liveJson .
        '.tmp-' .
        $token;

    if (
        @file_put_contents(
            $jsonTemp,
            $encoded,
            LOCK_EX
        ) === false
    ) {

        @unlink($exportFile);

        throw new RuntimeException(
            'Compact live JSON could not be written.'
        );
    }

    @unlink($liveJson);

    if (
        !@rename(
            $jsonTemp,
            $liveJson
        )
    ) {

        @unlink($jsonTemp);
        @unlink($exportFile);

        throw new RuntimeException(
            'Compact live JSON could not be activated.'
        );
    }

    @unlink($liveXml);

    if (
        !@rename(
            $exportFile,
            $liveXml
        )
    ) {

        @unlink($exportFile);
    }

    return $data;
}


/*
 * ------------------------------------------------------------
 * Ensure cache directory
 * ------------------------------------------------------------
 */

if (!is_dir($cacheDirectory)) {

    @mkdir(
        $cacheDirectory,
        0775,
        true
    );
}


/*
 * ------------------------------------------------------------
 * Existing last-known-good
 * ------------------------------------------------------------
 */

$existingLive =
    ag3d_load_json(
        $liveJson,
        $regionName
    );

$cacheAge =
    PHP_INT_MAX;

if (
    $existingLive &&
    is_file($liveJson)
) {

    $mtime =
        @filemtime(
            $liveJson
        );

    if (is_int($mtime)) {

        $cacheAge =
            max(
                0,
                time() - $mtime
            );
    }
}


/*
 * Fresh cache.
 */

if (
    $existingLive &&
    $cacheAge <= $cacheSeconds && ($existingLive['FormatVersion'] ?? 0) === 2
) {

    ag3d_emit(
        $existingLive,
        $regionName,
        'LIVE_CACHE_FRESH',
        false
    );
}


/*
 * ------------------------------------------------------------
 * Per-region export lock
 * ------------------------------------------------------------
 */

$lockHandle =
    @fopen(
        $lockFile,
        'c+'
    );

$haveLock =
    false;

if ($lockHandle) {

    $haveLock =
        @flock(
            $lockHandle,
            LOCK_EX |
            LOCK_NB
        );
}


/*
 * Another request is refreshing this same region.
 */

if (!$haveLock) {

    if ($lockHandle) {
        @fclose($lockHandle);
    }

    if ($existingLive) {

        ag3d_emit(
            $existingLive,
            $regionName,
            'LIVE_CACHE_BUSY',
            false
        );
    }

    if ($snapshotJson) {

        $snapshot =
            ag3d_load_json(
                $snapshotJson,
                $regionName
            );

        if ($snapshot) {

            ag3d_emit(
                $snapshot,
                $regionName,
                'SNAPSHOT_FALLBACK_BUSY',
                false
            );
        }
    }

    http_response_code(503);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'Live object refresh is already running.'
        ]
    );

    exit;
}


/*
 * ------------------------------------------------------------
 * Fresh current running OpenSim scene.
 * ------------------------------------------------------------
 */

$liveError =
    '';

try {

    // The fallback is re-read below if export fails; do not retain two large scenes.
    unset($existingLive);

    $fresh =
        ag3d_refresh(
            $regionIni,
            $cacheDirectory,
            $liveXml,
            $liveJson,
            $regionName
        );

    @flock(
        $lockHandle,
        LOCK_UN
    );

    @fclose(
        $lockHandle
    );

    ag3d_emit(
        $fresh,
        $regionName,
        'LIVE_OPENSIM',
        true
    );
}
catch (Throwable $error) {

    $liveError =
        $error->getMessage();
}

@flock(
    $lockHandle,
    LOCK_UN
);

@fclose(
    $lockHandle
);


/*
 * Last-known-good live fallback.
 */

$existingLive =
    ag3d_load_json(
        $liveJson,
        $regionName
    );

if ($existingLive) {

    ag3d_emit(
        $existingLive,
        $regionName,
        'LIVE_CACHE_STALE',
        true,
        $liveError
    );
}


/*
 * FARM SHOP historical snapshot fallback.
 */

if ($snapshotJson) {

    $snapshot =
        ag3d_load_json(
            $snapshotJson,
            $regionName
        );

    if ($snapshot) {

        ag3d_emit(
            $snapshot,
            $regionName,
            'SNAPSHOT_FALLBACK',
            true,
            $liveError
        );
    }
}


http_response_code(503);

echo json_encode(
    [
        'ok' => false,
        'RegionName' => $regionName,
        'error' =>
            $liveError !== ''
                ? $liveError
                : 'No live object data is available.'
    ],
    JSON_UNESCAPED_SLASHES
);
