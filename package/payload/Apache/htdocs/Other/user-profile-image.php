<?php

/*
 * ============================================================
 * Grid - PROFILE IMAGE HANDLER V1.2
 * ============================================================
 */

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/user-profile-lib.php';


/*
 * ============================================================
 * CONTROL CENTER CENTRAL SESSION V1
 * ============================================================
 *
 * Use the same authenticated session system as the rest of
 * the website. Do not maintain a second Profile-only login
 * method for image requests.
 */

$session =
    ag_require_login();


ag_no_cache();


$principalId =
    strtolower(
        trim(
            (string)(
                $session['principalId'] ??
                ''
            )
        )
    );


if (
    !preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
        $principalId
    )
) {

    http_response_code(403);

    exit(
        'Invalid signed session.'
    );
}


$db =
    auProfileDb();



header(
    'X-Content-Type-Options: nosniff'
);

$profile =
    auProfileGet(
        $db,
        $principalId
    );


$requested =
    strtolower(
        trim(
            (string)(
                $_GET['id'] ??
                ''
            )
        )
    );


$profileImage =
    strtolower(
        trim(
            (string)$profile['profileImage']
        )
    );


$firstImage =
    strtolower(
        trim(
            (string)$profile['profileFirstImage']
        )
    );


/*
 * ============================================================
 * AUSTRALIA PROFILE EXTRA IMAGE AUTHORIZATION
 * ============================================================
 *
 * A signed-in avatar may retrieve:
 *
 * - their Profile picture
 * - their First Life picture
 * - insignias for groups marked ListInProfile
 * - snapshots belonging to their Picks
 * - snapshots belonging to their Classifieds
 */

if (!auProfileIsUuid($requested)) {

    http_response_code(403);
    exit;
}


$allowedImage =
    in_array(
        $requested,
        [
            $profileImage,
            $firstImage
        ],
        true
    );


/*
 * GROUP INSIGNIA
 */

if (!$allowedImage) {

    $stmt =
        $db->prepare(
            '
            SELECT COUNT(*)

            FROM os_groups_membership AS m

            INNER JOIN os_groups_groups AS g
                ON g.GroupID = m.GroupID

            WHERE
                m.PrincipalID = ?
                AND m.ListInProfile <> 0
                AND g.InsigniaID = ?
            '
        );


    $stmt->bind_param(
        'ss',
        $principalId,
        $requested
    );


    $stmt->execute();


    $row =
        $stmt
            ->get_result()
            ->fetch_row();


    $stmt->close();


    $allowedImage =
        (int)(
            $row[0] ??
            0
        ) > 0;
}


/*
 * PICK SNAPSHOT
 */

if (!$allowedImage) {

    $stmt =
        $db->prepare(
            '
            SELECT COUNT(*)

            FROM userpicks

            WHERE
                creatoruuid = ?
                AND snapshotuuid = ?
            '
        );


    $stmt->bind_param(
        'ss',
        $principalId,
        $requested
    );


    $stmt->execute();


    $row =
        $stmt
            ->get_result()
            ->fetch_row();


    $stmt->close();


    $allowedImage =
        (int)(
            $row[0] ??
            0
        ) > 0;
}


/*
 * CLASSIFIED SNAPSHOT
 */

if (!$allowedImage) {

    $stmt =
        $db->prepare(
            '
            SELECT COUNT(*)

            FROM classifieds

            WHERE
                creatoruuid = ?
                AND snapshotuuid = ?
            '
        );


    $stmt->bind_param(
        'ss',
        $principalId,
        $requested
    );


    $stmt->execute();


    $row =
        $stmt
            ->get_result()
            ->fetch_row();


    $stmt->close();


    $allowedImage =
        (int)(
            $row[0] ??
            0
        ) > 0;
}


if (!$allowedImage) {

    http_response_code(403);
    exit;
}


function auProfileImagePlaceholder(
    string $uuid,
    string $message
): never
{
    header(
        'Content-Type: image/svg+xml; charset=UTF-8'
    );

    header(
        'Cache-Control: no-store'
    );

    $id =
        htmlspecialchars(
            $uuid,
            ENT_QUOTES,
            'UTF-8'
        );

    $message =
        htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        );

    echo
        '<?xml version="1.0" encoding="UTF-8"?>' .

        '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480">' .

        '<defs>' .

        '<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">' .

        '<stop offset="0" stop-color="#344047"/>' .

        '<stop offset=".45" stop-color="#12191c"/>' .

        '<stop offset="1" stop-color="#030506"/>' .

        '</linearGradient>' .

        '</defs>' .

        '<rect width="640" height="480" fill="url(#bg)"/>' .

        '<rect x="4" y="4" width="632" height="472" rx="15" fill="none" stroke="#d5a42c" stroke-width="3"/>' .

        '<text x="320" y="195" text-anchor="middle" fill="#efb83b" font-family="Arial" font-size="23" font-weight="bold">Grid</text>' .

        '<text x="320" y="230" text-anchor="middle" fill="#f3f5f6" font-family="Arial" font-size="18" font-weight="bold">PROFILE IMAGE</text>' .

        '<text x="320" y="275" text-anchor="middle" fill="#bdc7ca" font-family="Arial" font-size="14">' .

        $message .

        '</text>' .

        '<text x="320" y="310" text-anchor="middle" fill="#7d898e" font-family="Arial" font-size="10">' .

        $id .

        '</text>' .

        '</svg>';

    exit;
}


if (
    $requested ===
    '00000000-0000-0000-0000-000000000000'
) {
    auProfileImagePlaceholder(
        $requested,
        'No in-world image is currently set.'
    );
}


$cacheRoot =
    ag_dg_path(
        'Apache' .
        DIRECTORY_SEPARATOR .
        'AustraliaData' .
        DIRECTORY_SEPARATOR .
        'profile-image-cache'
    );

if (
    !is_string($cacheRoot) ||
    trim($cacheRoot) === ''
) {
    throw new RuntimeException(
        'Profile image cache path could not be resolved.'
    );
}


if (!is_dir($cacheRoot)) {
    @mkdir(
        $cacheRoot,
        0700,
        true
    );
}


$cacheFile =
    $cacheRoot .
    '/' .
    $requested .
    '.jpg';


if (
    is_file($cacheFile)
    &&
    filesize($cacheFile) > 100
) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: private, max-age=86400');

    readfile($cacheFile);

    exit;
}


$data =
    auProfileFetchAsset(
        $requested
    );


if (
    $data === null
    ||
    strlen($data) < 20
) {
    auProfileImagePlaceholder(
        $requested,
        'The in-world texture could not be retrieved.'
    );
}


/*
 * Browser-native image formats.
 */

if (
    str_starts_with(
        $data,
        "\xFF\xD8\xFF"
    )
) {
    header('Content-Type: image/jpeg');

    echo $data;

    exit;
}


if (
    substr($data, 0, 8) ===
    "\x89PNG\r\n\x1A\n"
) {
    header('Content-Type: image/png');

    echo $data;

    exit;
}


if (
    str_starts_with($data, 'GIF87a')
    ||
    str_starts_with($data, 'GIF89a')
) {
    header('Content-Type: image/gif');

    echo $data;

    exit;
}


/*
 * OpenSim textures are commonly JPEG2000.
 */


/*
 * ============================================================
 * Grid COREJ2K PROFILE DECODER
 * ============================================================
 */

$isJpeg2000 =
    strlen($data) >= 2
    &&
    substr(
        $data,
        0,
        2
    ) ===
    "\xFF\x4F";


if ($isJpeg2000) {

    $decoderDll =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        'ProfileDecoderV3' .
        DIRECTORY_SEPARATOR .
        'publish' .
        DIRECTORY_SEPARATOR .
        'decoder.dll';


    $dotnet =
        ag_dg_dotnet_exe();


    $workRoot =
        $cacheRoot .
        '/work';


    if (!is_dir($workRoot)) {
        @mkdir(
            $workRoot,
            0700,
            true
        );
    }


    $inputFile = null;
    $outputFile = null;


    try {

        if (!is_file($decoderDll)) {
            throw new RuntimeException(
                'CoreJ2K decoder.dll was not found.'
            );
        }


        if (!is_file($dotnet)) {
            throw new RuntimeException(
                'dotnet.exe was not found.'
            );
        }


        $token =
            bin2hex(
                random_bytes(8)
            );


        $inputFile =
            $workRoot .
            '/' .
            $requested .
            '-' .
            $token .
            '.j2k';


        $outputFile =
            $workRoot .
            '/' .
            $requested .
            '-' .
            $token .
            '.jpg';


        if (
            @file_put_contents(
                $inputFile,
                $data,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                'Could not create temporary JPEG2000 file.'
            );
        }


        $command = [
            $dotnet,
            $decoderDll,
            $inputFile,
            $outputFile
        ];


        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];


        $process =
            proc_open(
                $command,
                $descriptors,
                $pipes,
                dirname($decoderDll),
                null,
                [
                    'bypass_shell' => true
                ]
            );


        if (!is_resource($process)) {
            throw new RuntimeException(
                'Could not start CoreJ2K decoder.'
            );
        }


        fclose($pipes[0]);

        $stdout =
            stream_get_contents(
                $pipes[1]
            );

        fclose($pipes[1]);

        $stderr =
            stream_get_contents(
                $pipes[2]
            );

        fclose($pipes[2]);


        $exitCode =
            proc_close(
                $process
            );


        if (
            $exitCode !== 0
            ||
            !is_file($outputFile)
            ||
            filesize($outputFile) <= 100
        ) {
            throw new RuntimeException(
                'CoreJ2K decoder failed. Exit code: ' .
                $exitCode .
                ' STDERR: ' .
                trim($stderr)
            );
        }


        $jpeg =
            @file_get_contents(
                $outputFile
            );


        if (
            $jpeg === false
            ||
            strlen($jpeg) <= 100
            ||
            !str_starts_with(
                $jpeg,
                "\xFF\xD8\xFF"
            )
        ) {
            throw new RuntimeException(
                'CoreJ2K output was not a valid JPEG.'
            );
        }


        if (
            @file_put_contents(
                $cacheFile,
                $jpeg,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                'Could not write profile JPEG cache.'
            );
        }


        @unlink($inputFile);
        @unlink($outputFile);


        header(
            'Content-Type: image/jpeg'
        );

        header(
            'Cache-Control: private, max-age=86400'
        );


        echo $jpeg;

        exit;
    }
    catch (Throwable $error) {

        if (
            $inputFile !== null
            &&
            is_file($inputFile)
        ) {
            @unlink($inputFile);
        }


        if (
            $outputFile !== null
            &&
            is_file($outputFile)
        ) {
            @unlink($outputFile);
        }


        @error_log(
            'AUSTRALIA PROFILE DECODER: ' .
            $error->getMessage()
        );

        /*
         * Fall through to the existing Imagick fallback.
         */
    }
}

if (class_exists('Imagick')) {

    try {

        $image =
            new Imagick();


        $image->readImageBlob(
            $data
        );


        $image->setIteratorIndex(0);

        $image->setImageFormat('jpeg');

        $image->setImageCompressionQuality(90);

        $image->thumbnailImage(
            900,
            900,
            true
        );


        $jpeg =
            $image->getImageBlob();


        $image->clear();
        $image->destroy();


        if (strlen($jpeg) > 100) {

            @file_put_contents(
                $cacheFile,
                $jpeg,
                LOCK_EX
            );


            header('Content-Type: image/jpeg');

            header(
                'Cache-Control: private, max-age=86400'
            );


            echo $jpeg;

            exit;
        }
    }
    catch(Throwable $error) {

        /*
         * Keep going to our placeholder.
         */
    }
}


auProfileImagePlaceholder(
    $requested,
    'Image is synced. JPEG2000 web decoding is not available yet.'
);
