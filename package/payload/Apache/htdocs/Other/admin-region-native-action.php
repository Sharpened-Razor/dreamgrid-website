<?php

require_once __DIR__ . '/core/dreamgrid-env.php';
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';

ag_no_cache();

header(
    'Content-Type: application/json; charset=utf-8'
);

$session =
    ag_require_admin();


function native_reply(
    bool $ok,
    string $message,
    int $status = 200,
    array $extra = []
): never
{
    http_response_code(
        $status
    );

    echo json_encode(
        array_merge(
            [
                'ok' => $ok,
                $ok
                    ? 'message'
                    : 'error' => $message,
            ],
            $extra
        ),
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


if (
    !ag_is_admin(
        $session
    )
) {
    native_reply(
        false,
        'Grid Owner permission required.',
        403
    );
}


function native_ini_value(
    string $raw,
    string $key
): ?string
{
    if (
        !preg_match(
            '/^\s*' .
            preg_quote(
                $key,
                '/'
            ) .
            '\s*=\s*["\']?([^"\'\r\n;]+)["\']?\s*(?:;.*)?$/mi',
            $raw,
            $match
        )
    ) {
        return null;
    }

    return trim(
        (string)$match[1]
    );
}


function native_section_name(
    string $raw
): string
{
    if (
        preg_match(
            '/^\s*\[([^\]]+)\]\s*$/m',
            $raw,
            $match
        )
    ) {
        return trim(
            (string)$match[1]
        );
    }

    return '';
}


function native_find_ini(
    string $regionName,
    string $uuid
): ?array
{
    $root =
        ag_dg_regions_root();

    if (
        !is_string($root) ||
        $root === ''
    ) {
        return null;
    }

    $files =
        glob(
            rtrim(
                $root,
                '/\\'
            ) .
            DIRECTORY_SEPARATOR .
            '*' .
            DIRECTORY_SEPARATOR .
            'Region' .
            DIRECTORY_SEPARATOR .
            '*.ini'
        );

    if (
        !is_array(
            $files
        )
    ) {
        return null;
    }

    $matches =
        [];

    foreach (
        $files
        as
        $file
    ) {
        $raw =
            @file_get_contents(
                $file
            );

        if (
            !is_string(
                $raw
            )
        ) {
            continue;
        }

        $fileUuid =
            strtolower(
                trim(
                    (string)(
                        native_ini_value(
                            $raw,
                            'RegionUUID'
                        ) ??
                        ''
                    )
                )
            );

        $fileName =
            native_section_name(
                $raw
            );

        if ($fileName === '') {
            $fileName =
                pathinfo(
                    $file,
                    PATHINFO_FILENAME
                );
        }

        if (
            strcasecmp(
                $fileName,
                $regionName
            ) === 0
            &&
            $fileUuid ===
                strtolower(
                    $uuid
                )
        ) {
            $matches[] =
                [
                    'path' =>
                        $file,

                    'raw' =>
                        $raw,
                ];
        }
    }

    if (
        count(
            $matches
        ) !== 1
    ) {
        return null;
    }

    return $matches[0];
}


function native_marker_path(
    string $uuid
): string
{
    $path =
        ag_dg_path(
            '_WEB_CONTROL',
            'DeregisteredRegions',
            strtolower($uuid) .
            '.flag'
        );

    if (
        !is_string(
            $path
        ) ||
        $path === ''
    ) {
        throw new RuntimeException(
            'Could not resolve the web map deregistration marker path.'
        );
    }

    return $path;
}


function native_write_marker(
    string $regionName,
    string $uuid
): void
{
    $path =
        native_marker_path(
            $uuid
        );

    $folder =
        dirname(
            $path
        );

    if (
        !is_dir(
            $folder
        ) &&
        !@mkdir(
            $folder,
            0775,
            true
        ) &&
        !is_dir(
            $folder
        )
    ) {
        throw new RuntimeException(
            'Could not create the web map deregistration marker folder.'
        );
    }

    $text =
        $regionName .
        PHP_EOL .
        $uuid .
        PHP_EOL .
        date(
            DATE_ATOM
        ) .
        PHP_EOL;

    if (
        @file_put_contents(
            $path,
            $text,
            LOCK_EX
        ) === false
    ) {
        throw new RuntimeException(
            'Could not create the web map deregistration marker.'
        );
    }
}


function native_remove_marker(
    string $uuid
): void
{
    $path =
        native_marker_path(
            $uuid
        );

    if (
        is_file(
            $path
        )
    ) {
        @unlink(
            $path
        );
    }
}


function native_bridge_request(
    string $route,
    array $fields = [],
    int $timeout = 15
): array
{
    $portFile =
        ag_dg_path(
            '_WEB_CONTROL',
            'DreamGrid.NativeBridge.port'
        );

    $keyFile =
        ag_dg_path(
            '_WEB_CONTROL',
            'DreamGrid.NativeBridge.key'
        );

    if (
        !is_string($portFile) ||
        !is_file($portFile) ||
        !is_string($keyFile) ||
        !is_file($keyFile)
    ) {
        throw new RuntimeException(
            'DreamGrid Native Bridge configuration is missing.'
        );
    }

    $port =
        (int)trim(
            (string)file_get_contents(
                $portFile
            )
        );

    $key =
        trim(
            (string)file_get_contents(
                $keyFile
            )
        );

    if (
        $port < 1 ||
        $port > 65535 ||
        $key === ''
    ) {
        throw new RuntimeException(
            'DreamGrid Native Bridge configuration is invalid.'
        );
    }

    $body =
        http_build_query(
            $fields,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

    $context =
        stream_context_create(
            [
                'http' =>
                    [
                        'method' =>
                            'POST',

                        'timeout' =>
                            $timeout,

                        'ignore_errors' =>
                            true,

                        'header' =>
                            "Content-Type: application/x-www-form-urlencoded\r\n" .
                            "X-DreamGrid-Bridge-Key: " .
                            $key .
                            "\r\n" .
                            "Connection: close\r\n",

                        'content' =>
                            $body,
                    ],
            ]
        );

    $raw =
        @file_get_contents(
            ag_web_local_base($port) .
            '/' .
            $route,
            false,
            $context
        );

    if (
        !is_string(
            $raw
        ) ||
        trim(
            $raw
        ) === ''
    ) {
        throw new RuntimeException(
            'DreamGrid Native Bridge is not running.'
        );
    }

    $data =
        json_decode(
            $raw,
            true
        );

    if (
        !is_array(
            $data
        )
    ) {
        throw new RuntimeException(
            'DreamGrid Native Bridge returned invalid JSON.'
        );
    }

    return $data;
}


function native_db_has_uuid(
    string $uuid
): ?bool
{
    $db =
        ag_db_connect();

    if (!$db) {
        return null;
    }

    try {
        $statement =
            $db->prepare(
                'SELECT uuid FROM regions WHERE uuid = ? LIMIT 1'
            );

        if (!$statement) {
            return null;
        }

        $statement->bind_param(
            's',
            $uuid
        );

        if (
            !$statement->execute()
        ) {
            $statement->close();
            return null;
        }

        $statement->store_result();

        $exists =
            $statement->num_rows >
            0;

        $statement->close();

        return $exists;
    }
    finally {
        $db->close();
    }
}


function native_dreamgrid_knows(
    string $regionName,
    string $uuid
): ?bool
{
    $url =
        rtrim(
            ag_dg_diagnostics_base(),
            '/'
        ) .
        '/?command=regionlist&page=1&rp=500&sortorder=asc';

    $context =
        stream_context_create(
            [
                'http' =>
                    [
                        'method' =>
                            'GET',

                        'timeout' =>
                            6,

                        'ignore_errors' =>
                            true,
                    ],
            ]
        );

    $raw =
        @file_get_contents(
            $url,
            false,
            $context
        );

    if (
        !is_string(
            $raw
        )
    ) {
        return null;
    }

    $data =
        json_decode(
            $raw,
            true
        );

    if (
        !is_array(
            $data
        )
    ) {
        return null;
    }

    foreach (
        (
            $data['rows'] ??
            []
        )
        as
        $row
    ) {
        $cell =
            $row['cell'] ??
            [];

        if (
            !is_array(
                $cell
            )
        ) {
            continue;
        }

        $name =
            trim(
                (string)(
                    $cell[
                        'RegionName'
                    ] ??
                    ''
                )
            );

        $rowUuid =
            strtolower(
                trim(
                    (string)(
                        $cell[
                            'RegionUUID'
                        ] ??
                        $cell[
                            'UUID'
                        ] ??
                        ''
                    )
                )
            );

        if (
            strcasecmp(
                $name,
                $regionName
            ) === 0
            ||
            (
                $rowUuid !== ''
                &&
                $rowUuid ===
                    strtolower(
                        $uuid
                    )
            )
        ) {
            return true;
        }
    }

    return false;
}


$action =
    trim(
        (string)(
            $_POST['action'] ??
            $_GET['action'] ??
            ''
        )
    );


if ($action === 'health') {

    try {
        $result =
            native_bridge_request(
                'health',
                [],
                3
            );

        native_reply(
            !empty(
                $result['ok']
            ),
            (string)(
                $result['message'] ??
                'DreamGrid Native Bridge status returned.'
            ),
            !empty(
                $result['ok']
            )
                ? 200
                : 503,
            [
                'ready' =>
                    !empty(
                        $result['ready']
                    ),
            ]
        );
    }
    catch (
        Throwable
        $e
    ) {
        native_reply(
            false,
            $e->getMessage(),
            503,
            [
                'ready' =>
                    false,
            ]
        );
    }
}


if (
    (
        $_SERVER[
            'REQUEST_METHOD'
        ] ??
        ''
    ) !==
    'POST'
) {
    native_reply(
        false,
        'POST required.',
        405
    );
}


if (
    function_exists(
        'ag_require_same_origin_post'
    )
) {
    ag_require_same_origin_post();
}


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {
    session_start();
}


$postedToken =
    (string)(
        $_POST[
            'csrf_token'
        ] ??
        ''
    );

$expectedToken =
    (string)(
        $_SESSION[
            'ag_region_edit_csrf'
        ] ??
        ''
    );


if (
    $postedToken === ''
    ||
    $expectedToken === ''
    ||
    !hash_equals(
        $expectedToken,
        $postedToken
    )
) {
    native_reply(
        false,
        'Your security token expired. Reload the Region Edit window.',
        403
    );
}


if (
    !in_array(
        $action,
        [
            'deregister',
            'delete',
        ],
        true
    )
) {
    native_reply(
        false,
        'Unsupported native region action.',
        400
    );
}


@set_time_limit(
    170
);


$regionName =
    trim(
        (string)(
            $_POST[
                'region'
            ] ??
            ''
        )
    );

$uuid =
    strtolower(
        trim(
            (string)(
                $_POST[
                    'uuid'
                ] ??
                ''
            )
        )
    );


if (
    $regionName === ''
    ||
    !preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
        $uuid
    )
) {
    native_reply(
        false,
        'Valid region name and RegionUUID are required.',
        400
    );
}


$ini =
    native_find_ini(
        $regionName,
        $uuid
    );


if ($ini === null) {

    native_reply(
        false,
        'The selected Region Name and RegionUUID did not match exactly one DreamGrid Region INI.',
        409
    );
}


$knownBefore =
    native_dreamgrid_knows(
        $regionName,
        $uuid
    );



/* dreamgrid-deregister-delete-workflow-v2 */

/*
 * A region which has already completed native Deregister no
 * longer has a Robust regions row and may no longer appear in
 * DreamGrid's diagnostics RegionList.
 *
 * That is a valid post-Deregister state, not a reason to make
 * native permanent Delete unavailable.
 */
if ($knownBefore !== true) {

    $alreadyDeregisteredDbState =
        native_db_has_uuid(
            $uuid
        );


    if ($alreadyDeregisteredDbState === null) {

        native_reply(
            false,
            'The Robust regions table could not be verified. Nothing was changed.',
            503
        );
    }


    if (
        $alreadyDeregisteredDbState === false &&
        $action === 'deregister'
    ) {

        try {

            native_write_marker(
                $regionName,
                $uuid
            );
        }
        catch (
            Throwable
            $e
        ) {

            native_reply(
                false,
                'The region is already deregistered, but its web deregistration marker could not be repaired: ' .
                $e->getMessage(),
                500
            );
        }


        native_reply(
            true,
            'DEREGISTER is already complete for ' .
            $regionName .
            '. Its Robust registration is absent, its Region files are being kept, and its web deregistration marker has been repaired. DELETE remains available.'
        );
    }


    if (
        $alreadyDeregisteredDbState === false &&
        $action === 'delete'
    ) {

        /*
         * Keep the region hidden from web Region lists even if
         * permanent native Delete reports an error later.
         *
         * The region is already deregistered at this point.
         */
        try {

            native_write_marker(
                $regionName,
                $uuid
            );
        }
        catch (
            Throwable
            $e
        ) {

            native_reply(
                false,
                'The region is deregistered, but its web deregistration marker could not be created before Delete: ' .
                $e->getMessage(),
                500
            );
        }


        /*
         * The original known-before guard immediately below is
         * a web safety guard. The native bridge itself receives
         * the exact verified UUID.
         *
         * Allow the permanent native Delete path to continue.
         */
        $knownBefore =
            true;
    }
}

if ($knownBefore !== true) {

    native_reply(
        false,
        'The running DreamGrid instance does not currently contain this region in its native RegionList. Nothing was changed.',
        409
    );
}


$dbBefore =
    native_db_has_uuid(
        $uuid
    );


if ($dbBefore === null) {

    native_reply(
        false,
        'The Robust regions table could not be verified. Nothing was changed.',
        503
    );
}


try {
    $result =
        native_bridge_request(
            $action,
            [
                'uuid' =>
                    $uuid,
            ],
            $action ===
            'deregister'
                ? 140
                : 100
        );
}
catch (
    Throwable
    $e
) {
    native_reply(
        false,
        $e->getMessage(),
        502
    );
}


if (
    empty(
        $result['ok']
    )
) {
    native_reply(
        false,
        (string)(
            $result['message'] ??
            'DreamGrid native operation failed.'
        ),
        502
    );
}


$dbAfter =
    native_db_has_uuid(
        $uuid
    );


if ($dbAfter !== false) {

    native_reply(
        false,
        'DreamGrid returned from the native operation, but the Robust regions row could not be confirmed removed.',
        502
    );
}


if ($action === 'deregister') {

    try {
        native_write_marker(
            $regionName,
            $uuid
        );
    }
    catch (
        Throwable
        $e
    ) {
        native_reply(
            false,
            'DreamGrid deregistered the region, but the web map marker could not be written: ' .
            $e->getMessage(),
            500
        );
    }

    native_reply(
        true,
        'DEREGISTER completed for ' .
        $regionName .
        '. The selected region was stopped, its Robust registration was removed, and it has been removed from the web Global Map. Its Region files were kept.'
    );
}


$knownAfter =
    native_dreamgrid_knows(
        $regionName,
        $uuid
    );


if ($knownAfter !== false) {

    native_reply(
        false,
        'DreamGrid native Delete returned, but the region is still present in DreamGrid RegionList.',
        502
    );
}


/*
 * Native DreamGrid normally removes the region folder when
 * appropriate. If a shared group layout causes an INI to remain,
 * keep the web-map marker so the deleted region cannot reappear
 * on the web Global Map.
 */
if (
    is_file(
        (string)$ini['path']
    )
) {
    native_write_marker(
        $regionName,
        $uuid
    );
}
else {
    native_remove_marker(
        $uuid
    );
}


native_reply(
    true,
    'DELETE completed for ' .
    $regionName .
    '. DreamGrid ran its native FileStuff.DeleteAllContents routine and removed the region from its live RegionList.'
);