<?php

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/icons.php';
require_once __DIR__ . '/user-profile-lib.php';


$session =
    ag_require_admin();


ag_no_cache();


if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}


/*
 * ============================================================
 * CSRF
 * ============================================================
 */

if (
    empty(
        $_SESSION['cc_profile_csrf']
    )
    ||
    !is_string(
        $_SESSION['cc_profile_csrf']
    )
) {

    $_SESSION['cc_profile_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


if (
    empty(
        $_SESSION['cc_profile_notes_csrf']
    )
    ||
    !is_string(
        $_SESSION['cc_profile_notes_csrf']
    )
) {

    $_SESSION['cc_profile_notes_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


$profileCsrf =
    $_SESSION['cc_profile_csrf'];


$notesCsrf =
    $_SESSION['cc_profile_notes_csrf'];


/*
 * ============================================================
 * SESSION
 * ============================================================
 */

$principalId =
    strtolower(
        trim(
            (string)(
                $session['principalId']
                ??
                ''
            )
        )
    );


$avatar =
    ag_avatar_name(
        $session
    );


$sid =
    trim(
        (string)(
            $_GET['sid']
            ??
            ''
        )
    );


$zeroUuid =
    '00000000-0000-0000-0000-000000000000';


function cpv4_h(
    $value
): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function cpv4_uuid(
    $value
): bool {

    return
        preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            trim(
                (string)$value
            )
        )
        ===
        1;
}


function cpv4_bool(
    $value
): bool {

    if (
        $value === true
        ||
        $value === 1
        ||
        $value === '1'
        ||
        $value === "\x01"
    ) {

        return true;
    }


    $text =
        strtolower(
            trim(
                (string)$value
            )
        );


    return
        $text === 'true'
        ||
        $text === 'yes'
        ||
        $text === 'on';
}


function cpv4_image(
    string $uuid,
    string $sid
): string {

    $url =
        '/Other/user-profile-image.php?id=' .
        rawurlencode(
            $uuid
        );


    if ($sid !== '') {

        $url .=
            '&sid=' .
            rawurlencode(
                $sid
            );
    }


    $url .=
        '&v=5';


    return $url;
}


/*
 * ============================================================
 * DEFAULT DATA
 * ============================================================
 */

$profile = [

    'profilePartner'       => '',
    'profileImage'         => '',
    'profileFirstImage'    => '',
    'profileAboutText'     => '',
    'profileURL'           => '',
    'profileLanguages'     => '',
    'profileWantToText'    => '',
    'profileSkillsText'    => '',
    'profileFirstText'     => '',
    'profileAllowPublish'  => false,
    'profileMaturePublish' => false
];


$account =
    [];


$partner =
    '';


$picks =
    [];


$classifieds =
    [];


$groups =
    [];


$dbError =
    '';


/*
 * ============================================================
 * LOAD REAL PROFILE DATA
 * ============================================================
 */

try {

    $db =
        auProfileDb();


    $profile =
        auProfileGet(
            $db,
            $principalId
        );


    $account =
        auProfileAccount(
            $db,
            $principalId
        );


    if (!is_array($account)) {
        $account = [];
    }


    $partnerId =
        trim(
            (string)(
                $profile['profilePartner']
                ??
                ''
            )
        );


    if (
        cpv4_uuid(
            $partnerId
        )
        &&
        strtolower(
            $partnerId
        )
        !==
        $zeroUuid
    ) {

        $partner =
            auProfilePartner(
                $db,
                $partnerId
            );
    }


    /*
     * --------------------------------------------------------
     * PICKS
     * --------------------------------------------------------
     */

    $stmt =
        $db->prepare(
            '
            SELECT
                pickuuid,
                name,
                description,
                snapshotuuid,
                simname,
                posglobal,
                enabled

            FROM userpicks

            WHERE creatoruuid = ?

            ORDER BY
                sortorder,
                name
            '
        );


    $stmt->bind_param(
        's',
        $principalId
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $picks[] =
            $row;
    }


    $stmt->close();


    /*
     * --------------------------------------------------------
     * CLASSIFIEDS
     * --------------------------------------------------------
     */

    $stmt =
        $db->prepare(
            '
            SELECT
                classifieduuid,
                creationdate,
                expirationdate,
                category,
                name,
                description,
                snapshotuuid,
                simname,
                posglobal,
                parcelname,
                priceforlisting

            FROM classifieds

            WHERE creatoruuid = ?

            ORDER BY creationdate DESC
            '
        );


    $stmt->bind_param(
        's',
        $principalId
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $classifieds[] =
            $row;
    }


    $stmt->close();


    /*
     * --------------------------------------------------------
     * GROUPS SHOWN IN PROFILE
     * --------------------------------------------------------
     */

    $stmt =
        $db->prepare(
            '
            SELECT
                g.GroupID,
                g.Name,
                g.InsigniaID

            FROM os_groups_membership AS m

            INNER JOIN os_groups_groups AS g
                ON g.GroupID = m.GroupID

            WHERE
                m.PrincipalID = ?
                AND m.ListInProfile <> 0

            ORDER BY g.Name
            '
        );


    $stmt->bind_param(
        's',
        $principalId
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $groups[] =
            $row;
    }


    $stmt->close();


    $db->close();

}
catch (
    Throwable $error
) {

    $dbError =
        $error->getMessage();
}


/*
 * ============================================================
 * ACCOUNT DETAILS
 * ============================================================
 */

$created =
    (int)(
        $account['Created']
        ??
        0
    );


$birthdate =
    $created > 0
        ?
        gmdate(
            'm/d/Y',
            $created
        )
        :
        'Unknown';


$accountAgeDays =
    $created > 0
        ?
        max(
            0,
            (int)floor(
                (
                    time() -
                    $created
                )
                /
                86400
            )
        )
        :
        null;


$accountType =
    trim(
        (string)(
            $account['UserTitle']
            ??
            ''
        )
    );


if ($accountType === '') {

    $accountType =
        'Local User';
}


/*
 * ============================================================
 * IMAGES
 * ============================================================
 */

$profileImage =
    strtolower(
        trim(
            (string)(
                $profile['profileImage']
                ??
                ''
            )
        )
    );


$firstImage =
    strtolower(
        trim(
            (string)(
                $profile['profileFirstImage']
                ??
                ''
            )
        )
    );


$hasProfileImage =
    cpv4_uuid(
        $profileImage
    )
    &&
    $profileImage !==
    $zeroUuid;


$hasFirstImage =
    cpv4_uuid(
        $firstImage
    )
    &&
    $firstImage !==
    $zeroUuid;


$profileImageUrl =
    $hasProfileImage
        ?
        cpv4_image(
            $profileImage,
            $sid
        )
        :
        '';


$firstImageUrl =
    $hasFirstImage
        ?
        cpv4_image(
            $firstImage,
            $sid
        )
        :
        '';


/*
 * ============================================================
 * PRIVATE CONTROL CENTER NOTES
 *
 * Separate from Firestorm viewer-side Notes.
 * ============================================================
 */

$notesFile =
    __DIR__ .
    '/private/profile-notes/' .
    $principalId .
    '.txt';


$privateNotes =
    '';


if (
    is_file(
        $notesFile
    )
) {

    $privateNotes =
        (string)@file_get_contents(
            $notesFile
        );
}


/*
 * ============================================================
 * STATUS
 * ============================================================
 */

$status =
    trim(
        (string)(
            $_GET['result']
            ??
            ''
        )
    );


$statusMessage =
    '';


$statusClass =
    '';


switch ($status) {

    case 'saved':

        $statusMessage =
            'Profile updated successfully.';

        $statusClass =
            'success';

        break;


    case 'notes-saved':

        $statusMessage =
            'Private notes saved.';

        $statusClass =
            'success';

        break;


    case 'security-error':

        $statusMessage =
            'Security validation failed.';

        $statusClass =
            'error';

        break;


    case 'db-error':

        $statusMessage =
            'The profile update could not be completed.';

        $statusClass =
            'error';

        break;


    case 'too-long':

        $statusMessage =
            'One or more fields are too long.';

        $statusClass =
            'error';

        break;
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Profile</title>


<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-panel-v1.css?v=7">


<link
    rel="stylesheet"
    href="/Other/assets/css/control-center-profile-v1.css?v=4">

</head>


<body>


<main class="cp-page">


<!-- ========================================================
     HEADER
     ======================================================== -->

<section class="cp-intro">


    <div class="cp-intro-left">


        <div class="cp-intro-icon">

            <?=ag_icon(
                'avatar',
                null,
                'cp-intro-svg'
            )?>

        </div>


        <div>

            <div class="cp-intro-kicker">
                DASHBOARD
            </div>

            <div class="cp-intro-title">
                My Profile
            </div>

        </div>


    </div>


    <div class="cp-intro-note">
        Firestorm / OpenSim Profile
    </div>


</section>


<?php if ($statusMessage !== ''): ?>

<div
    class="pv4-alert <?=cpv4_h($statusClass)?>"
>

    <?=cpv4_h(
        $statusMessage
    )?>

</div>

<?php endif; ?>


<?php if ($dbError !== ''): ?>

<div class="pv4-alert error">

    Profile data error:
    <?=cpv4_h($dbError)?>

</div>

<?php endif; ?>


<!-- ========================================================
     TABS
     ======================================================== -->

<nav class="pv4-tabs">


    <button
        type="button"
        class="pv4-tab active"
        data-profile-tab="secondlife"
    >
        2ND LIFE
    </button>


    <button
        type="button"
        class="pv4-tab"
        data-profile-tab="feed"
    >
        FEED
    </button>


    <button
        type="button"
        class="pv4-tab"
        data-profile-tab="picks"
    >
        PICKS
    </button>


    <button
        type="button"
        class="pv4-tab"
        data-profile-tab="classifieds"
    >
        CLASSIFIEDS
    </button>


    <button
        type="button"
        class="pv4-tab"
        data-profile-tab="firstlife"
    >
        1ST LIFE
    </button>


    <button
        type="button"
        class="pv4-tab"
        data-profile-tab="notes"
    >
        NOTES
    </button>


</nav>


<!-- ========================================================
     2ND LIFE
     ======================================================== -->

<section
    class="pv4-panel active"
    data-profile-panel="secondlife"
>


<div class="pv4-profile-grid">


    <aside class="pv4-left-card">


        <div class="pv4-profile-picture">


            <?php if ($hasProfileImage): ?>

            <img
                src="<?=cpv4_h($profileImageUrl)?>"
                alt="Profile picture">

            <?php else: ?>

            <div class="pv4-no-picture">

                <?=ag_icon(
                    'avatar',
                    null,
                    'pv4-placeholder-icon'
                )?>

            </div>

            <?php endif; ?>


        </div>


        <div class="pv4-name">

            <?=cpv4_h(
                $avatar
            )?>

        </div>


        <div class="pv4-subtitle">
            RESIDENT PROFILE
        </div>


        <div class="pv4-line"></div>


        <div class="pv4-info-row">

            <span>
                KEY
            </span>

            <strong class="pv4-small-value">

                <?=cpv4_h(
                    $principalId
                )?>

            </strong>

        </div>


        <div class="pv4-info-row">

            <span>
                BORN
            </span>

            <strong>

                <?=cpv4_h(
                    $birthdate
                )?>

            </strong>

        </div>


        <?php if ($accountAgeDays !== null): ?>

        <div class="pv4-info-row">

            <span>
                AGE
            </span>

            <strong>

                <?=(int)$accountAgeDays?>
                DAYS

            </strong>

        </div>

        <?php endif; ?>


        <div class="pv4-info-row">

            <span>
                ACCOUNT
            </span>

            <strong>

                <?=cpv4_h(
                    $accountType
                )?>

            </strong>

        </div>


        <div class="pv4-info-row">

            <span>
                PARTNER
            </span>

            <strong>

                <?= $partner !== ''
                    ?
                    cpv4_h(
                        $partner
                    )
                    :
                    'None' ?>

            </strong>

        </div>


    </aside>



    <div class="pv4-main-column">


        <section class="pv4-card">


            <div class="pv4-kicker">
                2ND LIFE
            </div>


            <h2>
                About
            </h2>


            <textarea
                form="pv4-profile-form"
                name="profileAboutText"
                maxlength="8000"
                class="pv4-about"
            ><?=cpv4_h(
                $profile['profileAboutText']
                ??
                ''
            )?></textarea>


        </section>



        <div class="pv4-two">


            <section class="pv4-card">


                <div class="pv4-kicker">
                    WEB
                </div>


                <h2>
                    Profile URL
                </h2>


                <input
                    form="pv4-profile-form"
                    type="url"
                    name="profileURL"
                    maxlength="255"
                    value="<?=cpv4_h(
                        $profile['profileURL']
                        ??
                        ''
                    )?>">


            </section>



            <section class="pv4-card">


                <div class="pv4-kicker">
                    LANGUAGES
                </div>


                <h2>
                    Languages
                </h2>


                <textarea
                    form="pv4-profile-form"
                    name="profileLanguages"
                    maxlength="2000"
                    class="pv4-short"
                ><?=cpv4_h(
                    $profile['profileLanguages']
                    ??
                    ''
                )?></textarea>


            </section>


        </div>


    </div>


</div>



<div class="pv4-two pv4-gap-top">


    <section class="pv4-card">


        <div class="pv4-kicker">
            INTERESTS
        </div>


        <h2>
            I Want To
        </h2>


        <textarea
            form="pv4-profile-form"
            name="profileWantToText"
            maxlength="4000"
            class="pv4-medium"
        ><?=cpv4_h(
            $profile['profileWantToText']
            ??
            ''
        )?></textarea>


    </section>



    <section class="pv4-card">


        <div class="pv4-kicker">
            EXPERIENCE
        </div>


        <h2>
            Skills
        </h2>


        <textarea
            form="pv4-profile-form"
            name="profileSkillsText"
            maxlength="4000"
            class="pv4-medium"
        ><?=cpv4_h(
            $profile['profileSkillsText']
            ??
            ''
        )?></textarea>


    </section>


</div>



<section class="pv4-card pv4-gap-top">


    <div class="pv4-card-head">


        <div>

            <div class="pv4-kicker">
                GROUPS
            </div>

            <h2>
                Groups
            </h2>

        </div>


        <div class="pv4-count">
            <?=count($groups)?>
        </div>


    </div>


    <?php if (!$groups): ?>


    <div class="pv4-empty">

        No groups are currently
        listed in your profile.

    </div>


    <?php else: ?>


    <div class="pv4-groups">


    <?php foreach ($groups as $group): ?>


    <?php

    $insignia =
        strtolower(
            trim(
                (string)(
                    $group['InsigniaID']
                    ??
                    ''
                )
            )
        );


    $hasInsignia =
        cpv4_uuid(
            $insignia
        )
        &&
        $insignia !==
        $zeroUuid;

    ?>


    <div class="pv4-group">


        <div class="pv4-group-logo">


            <?php if ($hasInsignia): ?>


            <img
                src="<?=cpv4_h(
                    cpv4_image(
                        $insignia,
                        $sid
                    )
                )?>"
                alt="">


            <?php else: ?>


            <?=ag_icon(
                'groups',
                null,
                'pv4-group-system-icon'
            )?>


            <?php endif; ?>


        </div>


        <div>

            <strong>

                <?=cpv4_h(
                    $group['Name']
                    ??
                    ''
                )?>

            </strong>


            <span>
                GROUP
            </span>

        </div>


    </div>


    <?php endforeach; ?>


    </div>


    <?php endif; ?>


</section>



<section class="pv4-card pv4-options pv4-gap-top">


    <div class="pv4-option">


        <div>

            <strong>
                Publish Profile
            </strong>

            <span>
                Allow this profile to be published.
            </span>

        </div>


        <label class="pv4-switch">


            <input
                form="pv4-profile-form"
                type="checkbox"
                name="profileAllowPublish"
                value="1"
                <?=cpv4_bool(
                    $profile['profileAllowPublish']
                    ??
                    false
                )
                    ?
                    'checked'
                    :
                    '' ?>>


            <span></span>


        </label>


    </div>



    <div class="pv4-option">


        <div>

            <strong>
                Mature Profile
            </strong>

            <span>
                Mark published content as mature.
            </span>

        </div>


        <label class="pv4-switch">


            <input
                form="pv4-profile-form"
                type="checkbox"
                name="profileMaturePublish"
                value="1"
                <?=cpv4_bool(
                    $profile['profileMaturePublish']
                    ??
                    false
                )
                    ?
                    'checked'
                    :
                    '' ?>>


            <span></span>


        </label>


    </div>


</section>



<div class="pv4-savebar">


    <span>
        Updates your OpenSim profile.
    </span>


    <button
        type="submit"
        form="pv4-profile-form"
        class="cp-button"
    >
        SAVE PROFILE
    </button>


</div>


</section>


<!-- ========================================================
     FEED
     ======================================================== -->

<section
    class="pv4-panel"
    data-profile-panel="feed"
>


<section class="pv4-card pv4-large-card">


    <div class="pv4-kicker">
        FEED
    </div>


    <h2>
        Profile Feed
    </h2>


    <div class="pv4-feed-empty">


        <div class="pv4-feed-icon">
            ◉
        </div>


        <strong>
            No grid profile feed source is configured
        </strong>


        <p>
            This tab mirrors the Firestorm profile layout.
            The current OpenSim profile database does not
            expose a Feed data source to this website.
        </p>


    </div>


</section>


</section>


<!-- ========================================================
     PICKS
     ======================================================== -->

<section
    class="pv4-panel"
    data-profile-panel="picks"
>


<section class="pv4-card">


    <div class="pv4-card-head">


        <div>

            <div class="pv4-kicker">
                PLACES
            </div>

            <h2>
                Picks
            </h2>

        </div>


        <div class="pv4-count">
            <?=count($picks)?>
        </div>


    </div>


    <?php if (!$picks): ?>


    <div class="pv4-empty">
        No Picks are stored in your profile.
    </div>


    <?php else: ?>


    <div class="pv4-pick-grid">


    <?php foreach ($picks as $pick): ?>


    <?php

    $snapshot =
        strtolower(
            trim(
                (string)(
                    $pick['snapshotuuid']
                    ??
                    ''
                )
            )
        );


    $hasSnapshot =
        cpv4_uuid(
            $snapshot
        )
        &&
        $snapshot !==
        $zeroUuid;

    ?>


    <article class="pv4-list-card">


        <div class="pv4-list-image">


            <?php if ($hasSnapshot): ?>


            <img
                src="<?=cpv4_h(
                    cpv4_image(
                        $snapshot,
                        $sid
                    )
                )?>"
                alt="<?=cpv4_h(
                    $pick['name']
                    ??
                    ''
                )?>">


            <?php else: ?>


            <div class="pv4-image-empty">
                NO SNAPSHOT
            </div>


            <?php endif; ?>


        </div>


        <div class="pv4-list-body">


            <div class="pv4-list-heading">


                <h3>

                    <?=cpv4_h(
                        $pick['name']
                        ??
                        ''
                    )?>

                </h3>


                <span class="<?=cpv4_bool(
                    $pick['enabled']
                    ??
                    false
                )
                    ?
                    'pv4-live'
                    :
                    'pv4-off' ?>">

                    <?=cpv4_bool(
                        $pick['enabled']
                        ??
                        false
                    )
                        ?
                        'ACTIVE'
                        :
                        'DISABLED' ?>

                </span>


            </div>


            <p>

                <?=nl2br(
                    cpv4_h(
                        $pick['description']
                        ??
                        ''
                    )
                )?>

            </p>


            <div class="pv4-meta">


                <div>

                    <span>
                        REGION
                    </span>

                    <strong>

                        <?=cpv4_h(
                            $pick['simname']
                            ??
                            ''
                        )?>

                    </strong>

                </div>


                <div>

                    <span>
                        POSITION
                    </span>

                    <strong>

                        <?=cpv4_h(
                            $pick['posglobal']
                            ??
                            ''
                        )?>

                    </strong>

                </div>


            </div>


        </div>


    </article>


    <?php endforeach; ?>


    </div>


    <?php endif; ?>


</section>


</section>


<!-- ========================================================
     CLASSIFIEDS
     ======================================================== -->

<section
    class="pv4-panel"
    data-profile-panel="classifieds"
>


<section class="pv4-card">


    <div class="pv4-card-head">


        <div>

            <div class="pv4-kicker">
                LISTINGS
            </div>

            <h2>
                Classifieds
            </h2>

        </div>


        <div class="pv4-count">
            <?=count($classifieds)?>
        </div>


    </div>


    <?php if (!$classifieds): ?>


    <div class="pv4-empty">
        You currently have no Classified listings.
    </div>


    <?php else: ?>


    <div class="pv4-pick-grid">


    <?php foreach ($classifieds as $item): ?>


    <?php

    $snapshot =
        strtolower(
            trim(
                (string)(
                    $item['snapshotuuid']
                    ??
                    ''
                )
            )
        );


    $hasSnapshot =
        cpv4_uuid(
            $snapshot
        )
        &&
        $snapshot !==
        $zeroUuid;

    ?>


    <article class="pv4-list-card">


        <div class="pv4-list-image">


            <?php if ($hasSnapshot): ?>


            <img
                src="<?=cpv4_h(
                    cpv4_image(
                        $snapshot,
                        $sid
                    )
                )?>"
                alt="<?=cpv4_h(
                    $item['name']
                    ??
                    ''
                )?>">


            <?php else: ?>


            <div class="pv4-image-empty">
                NO SNAPSHOT
            </div>


            <?php endif; ?>


        </div>


        <div class="pv4-list-body">


            <h3>

                <?=cpv4_h(
                    $item['name']
                    ??
                    ''
                )?>

            </h3>


            <p>

                <?=nl2br(
                    cpv4_h(
                        $item['description']
                        ??
                        ''
                    )
                )?>

            </p>


            <div class="pv4-meta pv4-meta-three">


                <div>

                    <span>
                        REGION
                    </span>

                    <strong>

                        <?=cpv4_h(
                            $item['simname']
                            ??
                            ''
                        )?>

                    </strong>

                </div>


                <div>

                    <span>
                        PARCEL
                    </span>

                    <strong>

                        <?=cpv4_h(
                            $item['parcelname']
                            ??
                            ''
                        )?>

                    </strong>

                </div>


                <div>

                    <span>
                        PRICE
                    </span>

                    <strong>

                        L$
                        <?=(int)(
                            $item['priceforlisting']
                            ??
                            0
                        )?>

                    </strong>

                </div>


            </div>


        </div>


    </article>


    <?php endforeach; ?>


    </div>


    <?php endif; ?>


</section>


</section>


<!-- ========================================================
     1ST LIFE
     ======================================================== -->

<section
    class="pv4-panel"
    data-profile-panel="firstlife"
>


<div class="pv4-profile-grid">


    <aside class="pv4-left-card">


        <div class="pv4-profile-picture">


            <?php if ($hasFirstImage): ?>


            <img
                src="<?=cpv4_h($firstImageUrl)?>"
                alt="First Life picture">


            <?php else: ?>


            <div class="pv4-person-silhouette">

                <div class="pv4-silhouette-head"></div>
                <div class="pv4-silhouette-body"></div>

            </div>


            <?php endif; ?>


        </div>


        <div class="pv4-name">

            <?=cpv4_h(
                $avatar
            )?>

        </div>


        <div class="pv4-subtitle">
            1ST LIFE
        </div>


    </aside>



    <section class="pv4-card pv4-first-card">


        <div class="pv4-kicker">
            1ST LIFE
        </div>


        <h2>
            About
        </h2>


        <textarea
            form="pv4-profile-form"
            name="profileFirstText"
            maxlength="8000"
            class="pv4-first-text"
        ><?=cpv4_h(
            $profile['profileFirstText']
            ??
            ''
        )?></textarea>


    </section>


</div>



<div class="pv4-savebar">


    <span>
        Updates your OpenSim First Life profile.
    </span>


    <button
        type="submit"
        form="pv4-profile-form"
        class="cp-button"
    >
        SAVE PROFILE
    </button>


</div>


</section>


<!-- ========================================================
     NOTES
     ======================================================== -->

<section
    class="pv4-panel"
    data-profile-panel="notes"
>


<section class="pv4-card pv4-large-card">


    <div class="pv4-kicker">
        PRIVATE
    </div>


    <h2>
        Notes
    </h2>


    <div class="pv4-notes-message">

        These are private Control Center notes.
        They are not synchronised with Firestorm's local
        viewer Notes field.

    </div>


    <form
        method="post"
        action="/Other/panel-profile-notes-action.php"
    >


        <input
            type="hidden"
            name="csrf"
            value="<?=cpv4_h($notesCsrf)?>">


        <input
            type="hidden"
            name="sid"
            value="<?=cpv4_h($sid)?>">


        <textarea
            name="notes"
            maxlength="20000"
            class="pv4-notes"
        ><?=cpv4_h($privateNotes)?></textarea>


        <div class="pv4-notes-actions">


            <span>
                Private to this signed-in account.
            </span>


            <button
                type="submit"
                class="cp-button"
            >
                SAVE NOTES
            </button>


        </div>


    </form>


</section>


</section>


<!-- ========================================================
     PROFILE SAVE FORM
     ======================================================== -->

<form
    id="pv4-profile-form"
    method="post"
    action="/Other/panel-profile-action.php"
>


    <input
        type="hidden"
        name="csrf"
        value="<?=cpv4_h($profileCsrf)?>">


    <input
        type="hidden"
        name="sid"
        value="<?=cpv4_h($sid)?>">


</form>


</main>


<script>

(function(){

    "use strict";


    const tabs =
        Array.from(
            document.querySelectorAll(
                "[data-profile-tab]"
            )
        );


    const panels =
        Array.from(
            document.querySelectorAll(
                "[data-profile-panel]"
            )
        );


    function activate(
        name
    ){

        tabs.forEach(
            function(tab){

                tab.classList.toggle(
                    "active",

                    tab.dataset.profileTab ===
                    name
                );
            }
        );


        panels.forEach(
            function(panel){

                panel.classList.toggle(
                    "active",

                    panel.dataset.profilePanel ===
                    name
                );
            }
        );


        try {

            sessionStorage.setItem(
                "control-center-profile-tab",
                name
            );

        }
        catch(error){
        }
    }


    tabs.forEach(
        function(tab){

            tab.addEventListener(
                "click",
                function(){

                    activate(
                        tab.dataset.profileTab
                    );
                }
            );
        }
    );


    try {

        const saved =
            sessionStorage.getItem(
                "control-center-profile-tab"
            );


        if (
            saved
            &&
            tabs.some(
                function(tab){

                    return (
                        tab.dataset.profileTab ===
                        saved
                    );
                }
            )
        ) {

            activate(
                saved
            );
        }

    }
    catch(error){
    }

})();

</script>


</body>

</html>