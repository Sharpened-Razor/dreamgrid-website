<?php

require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 * ============================================================
 * Grid - MY PROFILE V1.11
 * FIRESTORM / OPENSIM SYNC
 * ============================================================
 */

require_once __DIR__ . '/user-profile-lib.php';


$session =
    auProfileSession();


$principalId =
    (string)$session['principalId'];


$db =
    auProfileDb();


$account =
    auProfileAccount(
        $db,
        $principalId
    );

$agRoleLevel = function_exists('ag_user_level')
    ? (int) ag_user_level($session)
    : (int)($session['level'] ?? 0);

$agRoleIsAdmin = function_exists('ag_is_admin')
    ? (bool) ag_is_admin($session)
    : ($agRoleLevel >= 200);

$agRoleLabel =
    ($agRoleLevel >= 250)
        ? "GRID OWNER"
        : ($agRoleIsAdmin ? "ADMIN" : "USER");


if (
    session_status()
    !==
    PHP_SESSION_ACTIVE
) {
    session_start();
}


if (
    empty(
        $_SESSION['australia_profile_csrf']
    )
) {
    $_SESSION['australia_profile_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrf =
    (string)
    $_SESSION['australia_profile_csrf'];


$status = '';
$statusError = false;


/*
 * ============================================================
 * SAVE REAL OPENSIM PROFILE
 * ============================================================
 */

if (
    $_SERVER['REQUEST_METHOD']
    ===
    'POST'
) {
    try {

        $postedCsrf =
            (string)(
                $_POST['csrf'] ??
                ''
            );


        if (
            $postedCsrf === ''
            ||
            !hash_equals(
                $csrf,
                $postedCsrf
            )
        ) {
            throw new RuntimeException(
                'Security token expired. Reload the page and try again.'
            );
        }


        $about =
            trim(
                (string)(
                    $_POST['profileAboutText'] ??
                    ''
                )
            );


        $url =
            trim(
                (string)(
                    $_POST['profileURL'] ??
                    ''
                )
            );


        $languages =
            trim(
                (string)(
                    $_POST['profileLanguages'] ??
                    ''
                )
            );


        $wants =
            trim(
                (string)(
                    $_POST['profileWantToText'] ??
                    ''
                )
            );


        $skills =
            trim(
                (string)(
                    $_POST['profileSkillsText'] ??
                    ''
                )
            );


        $firstText =
            trim(
                (string)(
                    $_POST['profileFirstText'] ??
                    ''
                )
            );


        if (mb_strlen($url) > 255) {
            throw new RuntimeException(
                'Profile URL is too long.'
            );
        }


        if (
            $url !== ''
            &&
            !filter_var(
                $url,
                FILTER_VALIDATE_URL
            )
        ) {
            throw new RuntimeException(
                'Profile URL is not valid.'
            );
        }


        if (
            mb_strlen($about) > 8000
            ||
            mb_strlen($firstText) > 8000
        ) {
            throw new RuntimeException(
                'Profile text is too long.'
            );
        }


        if (
            mb_strlen($languages) > 2000
            ||
            mb_strlen($wants) > 4000
            ||
            mb_strlen($skills) > 4000
        ) {
            throw new RuntimeException(
                'An interests field is too long.'
            );
        }


        $allow =
            isset(
                $_POST['profileAllowPublish']
            )
            ?
            "\x01"
            :
            "\x00";


        $mature =
            isset(
                $_POST['profileMaturePublish']
            )
            ?
            "\x01"
            :
            "\x00";


        $existing =
            auProfileGet(
                $db,
                $principalId
            );


        $check =
            $db->prepare(
                '
                SELECT COUNT(*)
                FROM userprofile
                WHERE useruuid = ?
                '
            );


        $check->bind_param(
            's',
            $principalId
        );


        $check->execute();


        $checkRow =
            $check
                ->get_result()
                ->fetch_row();


        $exists =
            (int)(
                $checkRow[0] ??
                0
            );


        $check->close();


        if ($exists > 0) {

            /*
             * Do NOT change:
             * profileImage
             * profileFirstImage
             * partner
             * interest bit masks
             *
             * Those remain under in-world control.
             */

            $stmt =
                $db->prepare(
                    '
                    UPDATE userprofile
                    SET
                        profileAllowPublish = ?,
                        profileMaturePublish = ?,
                        profileURL = ?,
                        profileWantToText = ?,
                        profileSkillsText = ?,
                        profileLanguages = ?,
                        profileAboutText = ?,
                        profileFirstText = ?
                    WHERE useruuid = ?
                    '
                );


            $stmt->bind_param(
                'sssssssss',
                $allow,
                $mature,
                $url,
                $wants,
                $skills,
                $languages,
                $about,
                $firstText,
                $principalId
            );


            $stmt->execute();

            $stmt->close();
        }
        else {

            $zero =
                '00000000-0000-0000-0000-000000000000';

            $wantMask = 0;
            $skillsMask = 0;


            $stmt =
                $db->prepare(
                    '
                    INSERT INTO userprofile
                    (
                        useruuid,
                        profilePartner,
                        profileAllowPublish,
                        profileMaturePublish,
                        profileURL,
                        profileWantToMask,
                        profileWantToText,
                        profileSkillsMask,
                        profileSkillsText,
                        profileLanguages,
                        profileImage,
                        profileAboutText,
                        profileFirstImage,
                        profileFirstText
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                    '
                );


            $stmt->bind_param(
                'sssssisissssss',
                $principalId,
                $zero,
                $allow,
                $mature,
                $url,
                $wantMask,
                $wants,
                $skillsMask,
                $skills,
                $languages,
                $zero,
                $about,
                $zero,
                $firstText
            );


            $stmt->execute();

            $stmt->close();
        }


        $status =
            'PROFILE SAVED â€” your real in-world profile was updated.';
    }
    catch(Throwable $error) {

        $status =
            $error->getMessage();

        $statusError =
            true;
    }
}


/*
 * Reload fresh values after any save.
 */

$profile =
    auProfileGet(
        $db,
        $principalId
    );


$name =
    trim(
        (string)$account['FirstName'] .
        ' ' .
        (string)$account['LastName']
    );


$partner =
    auProfilePartner(
        $db,
        (string)$profile['profilePartner']
    );


$allowPublish =
    auProfileBool(
        $profile['profileAllowPublish']
    );


$maturePublish =
    auProfileBool(
        $profile['profileMaturePublish']
    );


/*
 * ============================================================
 * PICKS
 * ============================================================
 */


/*
 * ============================================================
 * AUSTRALIA PROFILE ACCOUNT + GROUPS
 * ============================================================
 */


/*
 * ------------------------------------------------------------
 * ACCOUNT
 * ------------------------------------------------------------
 */

$profileAccount = null;


$stmt =
    $db->prepare(
        '
        SELECT
            PrincipalID,
            FirstName,
            LastName,
            Created,
            UserLevel,
            UserFlags,
            UserTitle,
            active
        FROM useraccounts
        WHERE PrincipalID = ?
        LIMIT 1
        '
    );


$stmt->bind_param(
    's',
    $principalId
);


$stmt->execute();


$profileAccount =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (!is_array($profileAccount)) {

    $profileAccount = [
        'Created'   => 0,
        'UserLevel' => 0,
        'UserFlags' => 0,
        'UserTitle' => '',
        'active'    => 0
    ];
}


/*
 * Firestorm displays the account creation date from
 * the UTC OpenSim timestamp.
 */

$created =
    (int)(
        $profileAccount['Created'] ??
        0
    );


if ($created > 0) {

    $birthdate =
        gmdate(
            'm/d/Y',
            $created
        );


    $createdDate =
        new DateTimeImmutable(
            gmdate(
                'Y-m-d',
                $created
            ),
            new DateTimeZone('UTC')
        );


    $todayDate =
        new DateTimeImmutable(
            gmdate('Y-m-d'),
            new DateTimeZone('UTC')
        );


    $accountAgeDays =
        (int)$createdDate
            ->diff($todayDate)
            ->format('%a');
}
else {

    $birthdate =
        'UNKNOWN';

    $accountAgeDays =
        null;
}


$accountType =
    trim(
        (string)(
            $profileAccount['UserTitle'] ??
            ''
        )
    );


if ($accountType === '') {

    $accountType =
        'Local User';
}


/*
 * ------------------------------------------------------------
 * ONLINE / OFFLINE
 * ------------------------------------------------------------
 */

$gridUser = [
    'Online' => 'false',
    'Login'  => '0',
    'Logout' => '0'
];


$stmt =
    $db->prepare(
        '
        SELECT
            Online,
            Login,
            Logout
        FROM griduser
        WHERE UserID = ?
        LIMIT 1
        '
    );


$stmt->bind_param(
    's',
    $principalId
);


$stmt->execute();


$gridRow =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (is_array($gridRow)) {

    $gridUser =
        array_merge(
            $gridUser,
            $gridRow
        );
}


$stmt =
    $db->prepare(
        '
        SELECT COUNT(*)
        FROM presence
        WHERE UserID = ?
        '
    );


$stmt->bind_param(
    's',
    $principalId
);


$stmt->execute();


$presenceRow =
    $stmt
        ->get_result()
        ->fetch_row();


$stmt->close();


$presenceOnline =
    (int)(
        $presenceRow[0] ??
        0
    ) > 0;


$gridOnline =
    in_array(
        strtolower(
            trim(
                (string)(
                    $gridUser['Online'] ??
                    ''
                )
            )
        ),
        [
            'true',
            '1',
            'yes'
        ],
        true
    );


$isOnline =
    $presenceOnline
    ||
    $gridOnline;


$loginTimestamp =
    (int)(
        $gridUser['Login'] ??
        0
    );


$logoutTimestamp =
    (int)(
        $gridUser['Logout'] ??
        0
    );


$lastLoginText =
    $loginTimestamp > 0
    ?
    date(
        'm/d/Y g:i A',
        $loginTimestamp
    )
    :
    'UNKNOWN';


$lastLogoutText =
    $logoutTimestamp > 0
    ?
    date(
        'm/d/Y g:i A',
        $logoutTimestamp
    )
    :
    'UNKNOWN';


/*
 * ------------------------------------------------------------
 * GROUPS SHOWN IN PROFILE
 * ------------------------------------------------------------
 */

$groups = [];


$stmt =
    $db->prepare(
        '
        SELECT
            g.GroupID,
            g.Name,
            g.Charter,
            g.InsigniaID,

            m.ListInProfile,
            m.SelectedRoleID,

            COALESCE(
                r.Title,
                ""
            ) AS SelectedTitle,

            p.ActiveGroupID,

            CASE
                WHEN p.ActiveGroupID = m.GroupID
                THEN 1
                ELSE 0
            END AS ActiveGroup

        FROM os_groups_membership AS m

        INNER JOIN os_groups_groups AS g
            ON g.GroupID = m.GroupID

        LEFT JOIN os_groups_roles AS r
            ON r.GroupID = m.GroupID
            AND r.RoleID = m.SelectedRoleID

        LEFT JOIN os_groups_principals AS p
            ON p.PrincipalID = m.PrincipalID

        WHERE
            m.PrincipalID = ?
            AND m.ListInProfile <> 0

        ORDER BY
            ActiveGroup DESC,
            g.Name ASC
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

    $row['ActiveGroup'] =
        (int)(
            $row['ActiveGroup'] ??
            0
        ) === 1;


    $groups[] =
        $row;
}


$stmt->close();

$picks = [];


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
        ORDER BY sortorder, name
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
    $picks[] = $row;
}


$stmt->close();


/*
 * ============================================================
 * CLASSIFIEDS
 * ============================================================
 */

$classifieds = [];


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
    $classifieds[] = $row;
}


$stmt->close();


$profileImageUrl =
    '/Other/user-profile-image.php?id=' .
    rawurlencode(
        (string)$profile['profileImage']
    );


$firstImageUrl =
    '/Other/user-profile-image.php?id=' .
    rawurlencode(
        (string)$profile['profileFirstImage']
    );

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>My Profile - Grid</title>


<link
    rel="stylesheet"
    href="/Other/australia-3d-theme.css?v=31">


<link
    rel="stylesheet"
    href="/Other/australia-modal.css?v=1">


<style>

*{
    box-sizing:border-box;
}


body{
    margin:0;
    min-height:100vh;

    color:#e8edef;
    font-family:Arial,Helvetica,sans-serif;

    background:
        linear-gradient(
            180deg,
            #0c1418 0%,
            #071014 38%,
            #030709 72%,
            #010304 100%
        ) !important;
}


.profile-wrap{
    width:calc(100% - 40px);
    max-width:1700px;
    margin:22px auto 50px;
}


.top-row{
    display:none;
}


.back{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    min-width:96px;
    min-height:38px;

    padding:0 18px;

    border:1px solid #d6a62d;
    border-radius:7px;

    color:#070707;
    text-decoration:none;

    font-size:10px;
    font-weight:900;

    background:
        linear-gradient(
            180deg,
            #ffd766,
            #daa01e 55%,
            #825104
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.55),
        0 4px 9px rgba(0,0,0,.45);
}



.hero-controls{
    position:absolute;

    top:20px;
    right:24px;

    display:flex;
    align-items:center;
    gap:16px;

    z-index:5;
}

.signed{
    color:#8f9da2;
    font-size:9px;
    white-space:nowrap;
}


.signed strong{
    color:#e5b23c;
}


.hero,
.profile-card{
    position:relative;
    overflow:hidden;

    border:1px solid rgba(215,164,48,.55);
    border-radius:14px;

    background:
        linear-gradient(
            145deg,
            rgba(48,57,62,.96),
            rgba(13,19,22,.98) 55%,
            rgba(3,6,7,.99)
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.13),
        inset 0 -7px 12px rgba(0,0,0,.6),
        0 15px 34px rgba(0,0,0,.46);
}


.hero{
    position:relative;
    padding:28px 32px;
    min-height:118px;
}


.hero::before,
.profile-card::before{
    content:"";
    position:absolute;
    left:8%;
    top:0;
    width:84%;
    height:2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            #c99220,
            #fff0a3,
            #c99220,
            transparent
        );
}


.hero-small{
    color:#e8b43f;
    font-size:9px;
    font-weight:900;
    letter-spacing:.16em;
}


.hero h1{
    margin:6px 0 5px;

    color:#f7f9fa;

    font-family:Arial,Helvetica,sans-serif;
    font-size:clamp(34px,5vw,55px);
    line-height:1;
    font-weight:900;

    /*
     * CLEAR CRISP 3D.
     * No fuzzy glow.
     */

    text-shadow:
        0 1px 0 #eef2f3,
        0 2px 0 #aeb8bc,
        0 3px 0 #657177,
        0 4px 0 #323d42,
        0 5px 0 #11181b !important;

    filter:none !important;
}


.hero p{
    margin:0;
    color:#a7b2b6;
    font-size:11px;
}


.tabs{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:9px;
    margin:18px 0;
}


.tab{
    min-height:45px;

    border:1px solid rgba(206,157,45,.43);
    border-radius:8px;

    cursor:pointer;

    color:#cad2d5;
    font-size:10px;
    font-weight:900;

    background:
        linear-gradient(
            180deg,
            #394348,
            #1b2327 50%,
            #090d0f
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.12),
        0 5px 9px rgba(0,0,0,.34);
}


.tab.active{
    color:#080808;
    border-color:#e7b23b;

    background:
        linear-gradient(
            180deg,
            #ffd96a,
            #dfa722 50%,
            #986408
        );
}


.tab-panel{
    display:none;
}


.tab-panel.active{
    display:block;
}


.profile-card{
    padding:25px;
}


.profile-grid{
    display:grid;
    grid-template-columns:minmax(330px,410px) minmax(0,1fr);
    gap:27px;
}


.image-box{
    overflow:hidden;

    border:1px solid #d0a135;
    border-radius:10px;

    background:#050708;

    box-shadow:
        inset 0 0 0 3px rgba(0,0,0,.6),
        0 8px 18px rgba(0,0,0,.5);
}


.image-box img{
    display:block;
    width:100%;
    aspect-ratio:4/3;
    object-fit:cover;
}


.uuid{
    padding:11px 12px;

    color:#7f8c91;
    font-size:8px;
    line-height:1.5;

    border-top:
        1px solid rgba(203,157,52,.23);

    overflow-wrap:anywhere;
}


.avatar-name{
    margin:15px 0 5px;
    color:#eabb46;
    font-size:21px;
    font-weight:900;
}


.partner{
    color:#9aa7ac;
    font-size:10px;
}



.identity-card{
    margin-top:15px;
    padding:13px 14px;

    border:1px solid rgba(193,150,49,.32);
    border-radius:8px;

    background:rgba(0,0,0,.26);

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04);
}


.identity-row{
    display:grid;
    grid-template-columns:82px minmax(0,1fr);
    gap:10px;

    padding:6px 0;

    border-bottom:
        1px solid rgba(255,255,255,.05);

    font-size:9px;
    line-height:1.45;
}


.identity-row:last-child{
    border-bottom:0;
}


.identity-label{
    color:#d9a936;
    font-weight:900;
}


.identity-value{
    color:#aeb9bd;
    overflow-wrap:anywhere;
}


.online{
    color:#74d69b;
    font-weight:900;
}


.offline{
    color:#d78989;
    font-weight:900;
}


.groups-box{
    margin-top:16px;
}


.groups-heading{
    display:flex;
    align-items:center;
    justify-content:space-between;

    margin-bottom:8px;

    color:#e5b23c;
    font-size:11px;
    font-weight:900;
}


.group-entry{
    position:relative;

    margin-bottom:8px;
    padding:10px 11px;

    border:1px solid rgba(190,146,45,.27);
    border-radius:7px;

    background:
        linear-gradient(
            145deg,
            rgba(36,44,48,.86),
            rgba(8,12,14,.88)
        );
}


.group-entry.active-group{
    border-color:rgba(231,178,59,.70);

    box-shadow:
        inset 3px 0 0 #dda82d;
}



.group-entry::after{
    content:"";
    display:block;
    clear:both;
}


.group-insignia{
    float:left;

    width:52px;
    height:52px;

    margin:
        0
        11px
        5px
        0;

    overflow:hidden;

    border:
        1px solid
        rgba(218,167,47,.65);

    border-radius:6px;

    background:#020405;

    box-shadow:
        inset 0 0 0 2px rgba(0,0,0,.55),
        0 3px 7px rgba(0,0,0,.4);
}


.group-insignia img{
    display:block;

    width:100%;
    height:100%;

    object-fit:cover;
}

.group-name{
    color:#e8ecee;
    font-size:10px;
    font-weight:900;
}


.group-title{
    margin-top:3px;

    color:#8e9ba0;
    font-size:9px;
}


.group-active-label{
    margin-top:5px;

    color:#e7b23b;
    font-size:8px;
    font-weight:900;
}


.groups-empty{
    padding:12px;

    border:1px dashed rgba(190,146,45,.27);
    border-radius:7px;

    color:#7f8d92;
    font-size:9px;
    text-align:center;
}

.section-title{
    margin:0 0 18px;

    color:#edb842 !important;

    font-family:Arial,Helvetica,sans-serif;
    font-size:27px;
    font-weight:900;

    text-shadow:
        0 1px 0 #ffe7a0,
        0 2px 0 #bf8616,
        0 3px 0 #6b4407,
        0 4px 0 #281700 !important;

    filter:none !important;
}


.field{
    margin-bottom:17px;
}


.field label{
    display:block;
    margin-bottom:7px;

    color:#e5b33d;

    font-size:9px;
    font-weight:900;
    letter-spacing:.08em;
}


input[type="url"],
textarea{
    width:100%;

    border:1px solid rgba(196,152,52,.40);
    border-radius:7px;

    outline:none;

    background:rgba(0,0,0,.43);
    color:#eff3f4;

    font-family:Arial,Helvetica,sans-serif;
    font-size:12px;

    box-shadow:
        inset 0 2px 5px rgba(0,0,0,.65);
}


input[type="url"]{
    min-height:43px;
    padding:0 12px;
}


textarea{
    min-height:120px;
    padding:12px;
    line-height:1.55;
    resize:vertical;
}


textarea.big{
    min-height:215px;
}


input:focus,
textarea:focus{
    border-color:#e1ad35;
}


.two{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:17px;
}


.options{
    display:flex;
    flex-wrap:wrap;
    gap:19px;
    margin-top:10px;

    color:#b8c1c5;
    font-size:10px;
}


.options label{
    display:flex;
    align-items:center;
    gap:7px;
}


.note{
    margin-top:16px;
    padding:13px 14px;

    border-left:4px solid #d1a02d;
    border-radius:6px;

    background:rgba(209,159,43,.065);

    color:#a7b2b6;
    font-size:10px;
    line-height:1.55;
}


.status{
    margin:17px 0;
    padding:13px 15px;

    border:1px solid rgba(73,186,122,.43);
    border-radius:8px;

    color:#a9e7c3;
    background:rgba(28,123,72,.16);

    font-size:10px;
    font-weight:900;
}


.status.error{
    border-color:rgba(193,66,73,.55);
    color:#efb7bb;
    background:rgba(128,27,33,.18);
}


.actions{
    display:flex;
    justify-content:flex-end;
    gap:11px;
    margin-top:20px;
}


.actions a,
.actions button{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    min-height:45px;
    padding:0 24px;

    border-radius:8px;

    cursor:pointer;

    font-family:Arial,Helvetica,sans-serif;
    font-size:10px;
    font-weight:900;
    text-decoration:none;
}


.refresh{
    border:1px solid rgba(152,166,172,.47);

    color:#ecf1f2;

    background:
        linear-gradient(
            180deg,
            #48545a,
            #253035 52%,
            #101619
        );
}


.save{
    border:1px solid #e3ac33;

    color:#080808;

    background:
        linear-gradient(
            180deg,
            #ffda69,
            #e1a925 50%,
            #946007
        );
}


.list{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:14px;
}


.list-card{
    padding:17px;

    border:1px solid rgba(195,148,42,.36);
    border-radius:9px;

    background:rgba(0,0,0,.27);
}



/*
 * ============================================================
 * AUSTRALIA PROFILE SNAPSHOT CARDS V1.6
 * ============================================================
 */

.snapshot-wrap{
    width:100%;
    margin-bottom:14px;

    overflow:hidden;

    border:
        1px solid
        rgba(210,160,47,.55);

    border-radius:8px;

    background:#020405;

    box-shadow:
        inset 0 0 0 2px rgba(0,0,0,.55),
        0 5px 12px rgba(0,0,0,.42);
}


.snapshot-wrap img{
    display:block;

    width:100%;
    aspect-ratio:4/3;

    object-fit:cover;

    background:#020405;
}


.snapshot-empty{
    display:flex;
    align-items:center;
    justify-content:center;

    width:100%;
    aspect-ratio:4/3;

    color:#66757b;

    font-size:9px;
    font-weight:900;
    letter-spacing:.08em;

    background:
        linear-gradient(
            145deg,
            #11181b,
            #030506
        );
}


.snapshot-label{
    margin-bottom:5px;

    color:#dba62e;

    font-size:8px;
    font-weight:900;
    letter-spacing:.08em;
}

.list-card h3{
    margin:0 0 8px;
    color:#e9b540;
    font-size:16px;
}


.list-card p{
    color:#a8b2b6;
    font-size:10px;
    line-height:1.5;
    white-space:pre-wrap;
}


.list-meta{
    margin-top:10px;
    color:#78868b;
    font-size:9px;
}


.empty{
    padding:30px;

    border:1px dashed rgba(191,148,50,.35);
    border-radius:9px;

    color:#859297;
    text-align:center;
    font-size:11px;
}



@media(max-width:760px){

    .hero{
        padding-top:80px;
    }

    .hero-controls{
        top:18px;
        left:20px;
        right:20px;

        justify-content:space-between;
    }

}
@media(max-width:760px){

    .profile-grid,
    .two{
        grid-template-columns:1fr;
    }

    .tabs{
        grid-template-columns:1fr 1fr;
    }

    .top-row{
    display:none;
}
}


/* AUSTRALIA DASHBOARD BACKGROUND START */

/*
 * EXACT FULL-PAGE BACKGROUND COPIED FROM user-dashboard.php
 */

body{

    min-height:100vh !important;

    background:

        linear-gradient(
            180deg,
            rgba(0,0,0,.42),
            rgba(0,0,0,.62)
        ),

        url(
            "/Other/assets/images/control-center-teal-bg.png"
        )

        center center /
        cover
        fixed
        no-repeat !important;

}

/* AUSTRALIA DASHBOARD BACKGROUND END */

/*
 * ============================================================
 * Grid - COMPACT PICKS / CLASSIFIEDS
 * ============================================================
 */

.list{
    display:grid;

    grid-template-columns:
        repeat(
            auto-fill,
            minmax(300px,380px)
        );

    justify-content:start;
    align-items:start;

    gap:18px;
}


.list-card{
    width:100%;
    max-width:380px;

    padding:15px;

    border:1px solid rgba(195,148,42,.36);
    border-radius:9px;

    background:
        linear-gradient(
            145deg,
            rgba(26,34,38,.96),
            rgba(5,9,11,.98)
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05),
        0 6px 14px rgba(0,0,0,.38);
}


.snapshot-wrap{
    width:100%;
    max-width:350px;

    margin:
        0
        auto
        13px;

    overflow:hidden;

    border:
        1px solid
        rgba(210,160,47,.55);

    border-radius:7px;

    background:#020405;
}


.snapshot-wrap img{
    display:block;

    width:100%;
    height:auto;

    aspect-ratio:16/9;

    object-fit:cover;
}


.snapshot-empty{
    width:100%;
    aspect-ratio:16/9;
}


.list-card h3{
    margin:8px 0 8px;

    color:#e9b540;

    font-size:16px;
}


.list-card p{
    margin:0 0 10px;

    color:#a8b2b6;

    font-size:10px;
    line-height:1.5;

    white-space:pre-wrap;
}


.list-meta{
    margin-top:10px;

    padding-top:9px;

    border-top:
        1px solid
        rgba(255,255,255,.07);

    color:#849297;

    font-size:9px;
    line-height:1.6;
}


@media(max-width:760px){

    .list{
        grid-template-columns:1fr;
    }


    .list-card{
        max-width:none;
    }


    .snapshot-wrap{
        max-width:none;
    }

}

/*
 * ============================================================
 * AUSTRALIA PICK MAP TELEPORT BUTTONS
 * ============================================================
 */

.pick-location{
    margin-top:12px;
    padding-top:10px;

    border-top:
        1px solid
        rgba(255,255,255,.07);
}


.pick-location-title{
    margin-bottom:5px;

    color:#dba72f;

    font-size:8px;
    font-weight:900;
    letter-spacing:.08em;
}


.pick-location-text{
    color:#a4b0b5;

    font-size:9px;
    line-height:1.5;
}


.pick-actions{
    display:grid;

    grid-template-columns:
        1fr
        1fr;

    gap:8px;

    margin-top:12px;
}


.pick-action{
    display:flex;

    align-items:center;
    justify-content:center;

    min-height:36px;

    padding:0 10px;

    border:
        1px solid
        rgba(153,169,176,.55);

    border-radius:6px;

    color:#edf2f3;

    text-decoration:none;

    font-size:9px;
    font-weight:900;

    background:
        linear-gradient(
            180deg,
            #48545a,
            #253035 52%,
            #101619
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.12),
        0 4px 8px rgba(0,0,0,.35);
}


.pick-action:hover{
    border-color:#dba72f;

    color:#ffd56b;

    background:
        linear-gradient(
            180deg,
            #566268,
            #303a3f 52%,
            #151c1f
        );
}


.pick-action.teleport{
    border-color:
        rgba(214,166,45,.70);
}


/*
 * ============================================================
 * AUSTRALIA PROFILE FULL SNAPSHOT FIT V1
 * ============================================================
 *
 * Show the complete in-world Pick / Classified image.
 * Do not crop it to 16:9.
 */

.snapshot-wrap{
    display:flex;
    align-items:center;
    justify-content:center;

    width:100%;
    height:auto;

    background:#020405;
}


.snapshot-wrap img{
    display:block;

    width:100% !important;
    height:auto !important;

    aspect-ratio:auto !important;

    object-fit:contain !important;
    object-position:center !important;
}


/* END AUSTRALIA PROFILE FULL SNAPSHOT FIT V1 */

/*
 * ============================================================
 * AUSTRALIA PROFILE TELEPORT MODAL V1
 * ============================================================
 */

#au-teleport-modal[hidden]{
    display:none !important;
}


#au-teleport-modal{
    position:fixed;

    inset:0;

    z-index:500000;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:24px;

    background:
        rgba(0,0,0,.74);

    backdrop-filter:
        blur(5px);
}


.au-teleport-dialog{
    position:relative;

    width:min(
        520px,
        calc(100% - 28px)
    );

    overflow:hidden;

    border:
        1px solid
        #c99525;

    border-radius:14px;

    background:
        linear-gradient(
            145deg,
            #273136 0%,
            #11181c 48%,
            #05090b 100%
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.12),
        inset 0 0 0 2px rgba(0,0,0,.50),
        0 28px 70px rgba(0,0,0,.75),
        0 0 22px rgba(218,164,45,.18);
}


.au-teleport-head{
    position:relative;

    padding:
        20px
        58px
        17px
        22px;

    border-bottom:
        1px solid
        rgba(218,165,45,.28);

    background:
        linear-gradient(
            180deg,
            rgba(255,255,255,.07),
            rgba(255,255,255,.01)
        );
}


.au-teleport-eyebrow{
    margin-bottom:5px;

    color:#e3aa31;

    font-size:9px;
    font-weight:900;

    letter-spacing:.13em;
}


.au-teleport-head h2{
    margin:0;

    color:#f3f5f6;

    font-size:22px;
    font-weight:900;
}


.au-teleport-x{
    position:absolute;

    top:15px;
    right:16px;

    width:34px;
    height:34px;

    border:
        1px solid
        rgba(255,255,255,.18);

    border-radius:7px;

    color:#dce3e6;

    font-size:20px;
    font-weight:700;

    cursor:pointer;

    background:
        linear-gradient(
            180deg,
            #364147,
            #131a1e
        );
}


.au-teleport-body{
    padding:22px;
}


.au-teleport-copy{
    margin-bottom:17px;

    color:#acb8bd;

    font-size:12px;
    line-height:1.6;
}


.au-teleport-destination{
    padding:15px;

    border:
        1px solid
        rgba(214,164,45,.42);

    border-radius:9px;

    background:
        rgba(0,0,0,.32);
}


.au-teleport-destination span{
    display:block;

    margin-bottom:5px;

    color:#dca72f;

    font-size:8px;
    font-weight:900;

    letter-spacing:.10em;
}


.au-teleport-destination strong{
    display:block;

    color:#ffffff;

    font-size:14px;

    overflow-wrap:anywhere;
}


.au-teleport-note{
    margin-top:15px;

    padding:11px 12px;

    border-left:
        3px solid
        #d5a32c;

    border-radius:5px;

    color:#909da2;

    font-size:10px;
    line-height:1.5;

    background:
        rgba(214,164,45,.055);
}


.au-teleport-actions{
    display:grid;

    grid-template-columns:
        1fr
        1fr;

    gap:10px;

    padding:
        0
        22px
        22px;
}


.au-teleport-button{
    min-height:42px;

    border:
        1px solid
        rgba(150,166,173,.60);

    border-radius:7px;

    color:#eef2f3;

    font-size:10px;
    font-weight:900;

    cursor:pointer;

    background:
        linear-gradient(
            180deg,
            #475359,
            #263136 52%,
            #11171a
        );

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.12),
        0 4px 9px rgba(0,0,0,.35);
}


.au-teleport-button.gold{
    border-color:#d8a52d;

    color:#080808;

    background:
        linear-gradient(
            180deg,
            #ffe078 0%,
            #eab237 48%,
            #9d670d 100%
        );
}


.au-teleport-button:hover{
    filter:brightness(1.10);
}


body.au-teleport-open{
    overflow:hidden;
}


@media(max-width:560px){

    .au-teleport-actions{
        grid-template-columns:1fr;
    }

}


/* END AUSTRALIA PROFILE TELEPORT MODAL V1 */

/*
 * ============================================================
 * AUSTRALIA GROUP PROFILE LINK V1
 * ============================================================
 */

.group-profile-link{
    color:inherit;
    text-decoration:none;
}

.group-profile-link:hover{
    color:#f1bd48;
    text-decoration:underline;
}


/* END AUSTRALIA GROUP PROFILE LINK V1 */

/* ==========================================================
   AUSTRALIA USER BUTTON NO TEXT SHADOW V1
   ========================================================== */

button,
input[type="button"],
input[type="submit"],
input[type="reset"],
a[class*="button"],
a[class*="btn"],
a[class*="back"],
a[class*="logout"] {
    text-shadow: none !important;
}

/* END AUSTRALIA USER BUTTON NO TEXT SHADOW V1 */

</style>


<link
    rel="stylesheet"
    href="/Other/assets/css/ag-background-standard.css?v=20260826-perfectfit">
<link rel="stylesheet" href="/Other/assets/css/ag-font-standard.css?v=20260826-sharp-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-professional-layout.css?v=20260826-professional-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-uniform-site-v12.css?v=20260830-phase3c">

<style id="ag-custom-role-box-v1">

.ag-account-role-box{
    display:inline-flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    min-width:138px;
    margin-top:14px;
    padding:8px 14px;
    box-sizing:border-box;
    border:1px solid rgba(68,153,220,.48);
    border-radius:8px;
    background:rgba(12,62,98,.30);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06);
    line-height:1.2;
}

.ag-account-role-box strong{
    display:block;
    color:#77c8ff;
    font-size:11px;
    font-weight:900;
    letter-spacing:.05em;
}

.ag-account-role-box span{
    display:block;
    margin-top:3px;
    color:#f2f4f5;
    font-size:10px;
    font-weight:800;
    white-space:nowrap;
}

</style>


<style id="ag-role-display-standard-v4">

/* ============================================================
   STANDARD ROLE DISPLAY V4

   250+        GRID OWNER
   other admin ADMIN
   normal user USER
   ============================================================ */

.php-level,
.ag-account-role-box{
    display:inline-flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    min-width:145px !important;
    min-height:54px !important;
    padding:7px 14px !important;
    box-sizing:border-box !important;
    border:1px solid rgba(55,150,215,.70) !important;
    border-radius:8px !important;
    background:rgba(5,28,43,.88) !important;
    box-shadow:none !important;
    text-align:center !important;
    line-height:1.15 !important;
    color:#ffffff !important;
}

.php-level strong,
.ag-account-role-box strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    width:auto !important;
    height:auto !important;
    margin:0 0 4px 0 !important;
    padding:0 !important;
    color:#75c9ff !important;
    background:none !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1.1 !important;
    letter-spacing:.03em !important;
    text-indent:0 !important;
    clip:auto !important;
    overflow:visible !important;
}

.php-level span,
.ag-account-role-box span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1.1 !important;
    white-space:nowrap !important;
}

</style>


<style id="profile-role-position-v5">

/* PROFILE ROLE POSITION V5 */

.hero{
    position:relative !important;
    padding-right:230px !important;
}

.hero > .ag-profile-role-badge{
    position:absolute !important;
    top:50% !important;
    right:28px !important;
    left:auto !important;
    bottom:auto !important;
    transform:translateY(-50%) !important;
    display:flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    width:170px !important;
    min-width:170px !important;
    height:58px !important;
    min-height:58px !important;
    margin:0 !important;
    padding:7px 12px !important;
    box-sizing:border-box !important;
    border:1px solid #236b96 !important;
    border-radius:9px !important;
    background:#071923 !important;
    box-shadow:none !important;
    z-index:20 !important;
}

.hero > .ag-profile-role-badge strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 0 4px 0 !important;
    padding:0 !important;
    color:#79caff !important;
    background:none !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1 !important;
    white-space:nowrap !important;
}

.hero > .ag-profile-role-badge span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1 !important;
    white-space:nowrap !important;
}

@media (max-width:800px){
    .hero{
        padding-right:20px !important;
    }

    .hero > .ag-profile-role-badge{
        position:relative !important;
        top:auto !important;
        right:auto !important;
        transform:none !important;
        margin-top:14px !important;
    }
}

</style>


<style id="profile-role-final-v6">

/* MY PROFILE ROLE BADGE - FINAL V6 */

.hero{
    position:relative !important;
    min-height:245px !important;
    padding-right:260px !important;
}

.hero .ag-account-role-box{
    position:absolute !important;
    top:50% !important;
    right:34px !important;
    left:auto !important;
    bottom:auto !important;
    transform:translateY(-50%) !important;

    display:flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;

    width:175px !important;
    min-width:175px !important;
    max-width:175px !important;
    height:58px !important;
    min-height:58px !important;

    margin:0 !important;
    padding:8px 12px !important;
    box-sizing:border-box !important;

    border:1px solid #256c96 !important;
    border-radius:9px !important;
    background:#071a25 !important;
    box-shadow:none !important;

    text-align:center !important;
    z-index:999 !important;
    overflow:visible !important;
}

.hero .ag-account-role-box strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    width:auto !important;
    height:auto !important;
    margin:0 0 5px 0 !important;
    padding:0 !important;
    border:0 !important;
    background:none !important;
    box-shadow:none !important;
    color:#76caff !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1 !important;
    white-space:nowrap !important;
    text-indent:0 !important;
    clip:auto !important;
    overflow:visible !important;
}

.hero .ag-account-role-box span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    width:auto !important;
    height:auto !important;
    margin:0 !important;
    padding:0 !important;
    border:0 !important;
    background:none !important;
    box-shadow:none !important;
    color:#ffffff !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1 !important;
    white-space:nowrap !important;
    text-indent:0 !important;
    clip:auto !important;
    overflow:visible !important;
}

@media (max-width:800px){
    .hero{
        padding-right:20px !important;
    }

    .hero .ag-account-role-box{
        position:relative !important;
        top:auto !important;
        right:auto !important;
        transform:none !important;
        margin-top:16px !important;
    }
}

</style>


<style id="profile-header-layout-v7">

/* ============================================================
   MY PROFILE HEADER LAYOUT V7
   Role badge + Back button side by side.
   ============================================================ */

.hero{
    position:relative !important;
    min-height:126px !important;
    padding:28px 500px 28px 32px !important;
    overflow:hidden !important;
}

/* ROLE BADGE */
.hero .ag-account-role-box{
    position:absolute !important;
    top:50% !important;
    right:235px !important;
    left:auto !important;
    bottom:auto !important;
    transform:translateY(-50%) !important;

    width:185px !important;
    min-width:185px !important;
    max-width:185px !important;
    height:58px !important;
    min-height:58px !important;

    margin:0 !important;
    padding:8px 12px !important;

    display:flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;

    border:1px solid #286f9c !important;
    border-radius:9px !important;
    background:#071923 !important;
    box-shadow:none !important;
    z-index:30 !important;
}

.hero .ag-account-role-box strong{
    display:block !important;
    margin:0 0 5px 0 !important;
    padding:0 !important;
    color:#76caff !important;
    background:none !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1 !important;
    white-space:nowrap !important;
}

.hero .ag-account-role-box span{
    display:block !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1 !important;
    white-space:nowrap !important;
}

/* BACK TO DASHBOARD BUTTON */
.hero > a.australia-back-dashboard{
    position:absolute !important;
    top:50% !important;
    right:28px !important;
    left:auto !important;
    bottom:auto !important;
    transform:translateY(-50%) !important;

    width:185px !important;
    min-width:185px !important;
    max-width:185px !important;
    height:58px !important;
    min-height:58px !important;

    margin:0 !important;
    padding:8px 14px !important;
    z-index:31 !important;
}

@media (max-width:900px){

    .hero{
        padding:24px !important;
    }

    .hero .ag-account-role-box,
    .hero > a.australia-back-dashboard{
        position:relative !important;
        top:auto !important;
        right:auto !important;
        left:auto !important;
        transform:none !important;
        margin-top:14px !important;
    }
}

</style>


<style id="profile-header-wide-v8">

/* ============================================================
   MY PROFILE HEADER WIDE LAYOUT V8
   Extra separation between role badge and dashboard button.
   ============================================================ */

.hero{
    position:relative !important;
    min-height:138px !important;
    padding:28px 610px 28px 32px !important;
    box-sizing:border-box !important;
    overflow:hidden !important;
}

/* ROLE BADGE */
.hero .ag-account-role-box{
    position:absolute !important;
    top:50% !important;
    right:325px !important;
    left:auto !important;
    bottom:auto !important;
    transform:translateY(-50%) !important;
    width:190px !important;
    min-width:190px !important;
    max-width:190px !important;
    height:62px !important;
    min-height:62px !important;
    margin:0 !important;
    padding:8px 14px !important;
    display:flex !important;
    flex-direction:column !important;
    align-items:center !important;
    justify-content:center !important;
    box-sizing:border-box !important;
    border:1px solid #286f9c !important;
    border-radius:9px !important;
    background:#071923 !important;
    box-shadow:none !important;
    z-index:50 !important;
}

.hero .ag-account-role-box strong{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 0 5px 0 !important;
    padding:0 !important;
    color:#76caff !important;
    background:none !important;
    font-size:13px !important;
    font-weight:900 !important;
    line-height:1 !important;
    white-space:nowrap !important;
}

.hero .ag-account-role-box span{
    display:block !important;
    visibility:visible !important;
    opacity:1 !important;
    position:static !important;
    margin:0 !important;
    padding:0 !important;
    color:#ffffff !important;
    background:none !important;
    font-size:11px !important;
    font-weight:800 !important;
    line-height:1 !important;
    white-space:nowrap !important;
}

/* DASHBOARD BUTTON - catch all likely Profile button variants */
.hero a.australia-back-dashboard,
.hero a.ag-return-button,
.hero a[href*="dashboard"],
.hero .hero-controls a{
    position:absolute !important;
    top:50% !important;
    right:28px !important;
    left:auto !important;
    bottom:auto !important;
    transform:translateY(-50%) !important;
    width:250px !important;
    min-width:250px !important;
    max-width:250px !important;
    height:62px !important;
    min-height:62px !important;
    margin:0 !important;
    padding:8px 18px !important;
    box-sizing:border-box !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    white-space:nowrap !important;
    z-index:51 !important;
}

@media (max-width:1050px){
    .hero{
        padding-right:32px !important;
    }

    .hero .ag-account-role-box,
    .hero a.australia-back-dashboard,
    .hero a.ag-return-button,
    .hero a[href*="dashboard"],
    .hero .hero-controls a{
        position:relative !important;
        top:auto !important;
        right:auto !important;
        left:auto !important;
        transform:none !important;
        margin-top:14px !important;
    }
}

</style>

</head>


<body>


<main class="profile-wrap">


<section class="hero">



<a
    class="button australia-back-dashboard"
    href="/Other/dashboard-return.php<?= !empty($_GET['sid']) ? '?sid=' . rawurlencode((string)$_GET['sid']) : '' ?>"
    style="
        display:inline-flex !important;
        align-items:center !important;
        justify-content:center !important;
        min-width:170px !important;
        min-height:44px !important;
        padding:10px 18px !important;
        box-sizing:border-box !important;

        border:1px solid #ffe077 !important;
        border-radius:8px !important;

        background:
            linear-gradient(
                180deg,
                #ffe48b 0%,
                #e9ad2b 45%,
                #b46f08 100%
            ) !important;

        color:#111111 !important;

        font-family:Arial,Helvetica,sans-serif !important;
        font-size:12px !important;
        font-weight:900 !important;
        letter-spacing:.045em !important;

        text-decoration:none !important;
        text-align:center !important;

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.55),
            0 4px 10px rgba(0,0,0,.45) !important;
    ">

    BACK TO DASHBOARD

</a>

</div>

<div class="hero-small">
    Grid
</div>

<h1>
    MY PROFILE
</h1>
<div class="ag-account-role-box">
    <strong><?=htmlspecialchars($agRoleLabel, ENT_QUOTES, 'UTF-8')?></strong>
    <span>USER LEVEL <?=htmlspecialchars((string)$agRoleLevel, ENT_QUOTES, 'UTF-8')?></span>
</div>


<p>
    Firestorm / OpenSim in-world profile.
</p>

</section>


<?php if ($status !== ''): ?>

<div class="status<?= $statusError ? ' error' : '' ?>">
    <?= auProfileEsc($status) ?>
</div>

<?php endif; ?>


<nav class="tabs">

<button
    type="button"
    class="tab active"
    data-profile-tab="profile">

    PROFILE

</button>


<button
    type="button"
    class="tab"
    data-profile-tab="firstlife">

    FIRST LIFE

</button>


<button
    type="button"
    class="tab"
    data-profile-tab="picks">

    PICKS (<?= count($picks) ?>)

</button>


<button
    type="button"
    class="tab"
    data-profile-tab="classifieds">

    CLASSIFIEDS (<?= count($classifieds) ?>)

</button>

</nav>


<form method="post">

<input
    type="hidden"
    name="csrf"
    value="<?= auProfileEsc($csrf) ?>">


<section
    id="profile-tab-profile"
    class="tab-panel active">

<div class="profile-card">

<div class="profile-grid">


<div>

<div class="image-box">

<img
    src="<?= auProfileEsc($profileImageUrl) ?>"
    alt="Profile picture">

<div class="uuid">

PROFILE TEXTURE UUID<br>
<?= auProfileEsc((string)$profile['profileImage']) ?>

</div>

</div>


<div class="avatar-name">
    <?= auProfileEsc($name) ?>
</div>


<div class="partner">

PARTNER:
<?= auProfileEsc($partner) ?>

</div>

<div class="identity-card">

<div class="identity-row">

<div class="identity-label">
KEY:
</div>

<div class="identity-value">
<?= auProfileEsc($principalId) ?>
</div>

</div>


<div class="identity-row">

<div class="identity-label">
BIRTHDATE:
</div>

<div class="identity-value">

<?= auProfileEsc($birthdate) ?>

<?php if ($accountAgeDays !== null): ?>

(<?= (int)$accountAgeDays ?> <?= (int)$accountAgeDays === 1 ? 'day old' : 'days old' ?>)

<?php endif; ?>

</div>

</div>


<div class="identity-row">

<div class="identity-label">
ACCOUNT:
</div>

<div class="identity-value">
<?= auProfileEsc($accountType) ?>
</div>

</div>


<div class="identity-row">

<div class="identity-label">
STATUS:
</div>

<div class="identity-value <?= $isOnline ? 'online' : 'offline' ?>">

<?= $isOnline ? 'ONLINE' : 'OFFLINE' ?>

</div>

</div>


<div class="identity-row">

<div class="identity-label">
LAST LOGIN:
</div>

<div class="identity-value">
<?= auProfileEsc($lastLoginText) ?>
</div>

</div>


<?php if (!$isOnline): ?>

<div class="identity-row">

<div class="identity-label">
LAST LOGOUT:
</div>

<div class="identity-value">
<?= auProfileEsc($lastLogoutText) ?>
</div>

</div>

<?php endif; ?>

</div>


<div class="groups-box">

<div class="groups-heading">

<span>
GROUPS
</span>

<span>
<?= count($groups) ?>
</span>

</div>


<?php if (!$groups): ?>

<div class="groups-empty">

No groups are currently marked
SHOW IN PROFILE.

</div>

<?php else: ?>


<?php foreach ($groups as $group): ?>

<div class="group-entry<?= $group['ActiveGroup'] ? ' active-group' : '' ?>">

<?php

$insigniaId =
    strtolower(
        trim(
            (string)(
                $group['InsigniaID'] ??
                ''
            )
        )
    );

?>


<?php if (
    auProfileIsUuid($insigniaId)
    &&
    $insigniaId !==
    '00000000-0000-0000-0000-000000000000'
): ?>

<div class="group-insignia">

<img
    src="<?= auProfileEsc(
        '/Other/user-profile-image.php?id=' .
        rawurlencode($insigniaId)
    ) ?>"
    alt="Group insignia">

</div>

<?php endif; ?>

<div class="group-name">

<a
    class="group-profile-link"
    href="/Other/group-profile.php?id=<?= rawurlencode(
        (string)$group['GroupID']
    ) ?>">

<?= auProfileEsc((string)$group['Name']) ?>

</a>

</div>


<div class="group-title">

<?php
$selectedTitle =
    trim(
        (string)$group['SelectedTitle']
    );
?>

<?= auProfileEsc(
    $selectedTitle !== ''
    ?
    $selectedTitle
    :
    'Member'
) ?>

</div>


<?php if ($group['ActiveGroup']): ?>

<div class="group-active-label">
ACTIVE GROUP
</div>

<?php endif; ?>

</div>

<?php endforeach; ?>


<?php endif; ?>

</div>



<div class="note">

This picture UUID is the picture stored in your real
Firestorm/OpenSim profile.

To change the profile picture, change it in Firestorm and
press REFRESH FROM IN-WORLD.

</div>

</div>


<div>

<h2 class="section-title">
    PROFILE
</h2>


<div class="field">

<label>
    ABOUT
</label>

<textarea
    class="big"
    name="profileAboutText"><?= auProfileEsc((string)$profile['profileAboutText']) ?></textarea>

</div>


<div class="field">

<label>
    PROFILE WEB URL
</label>

<input
    type="url"
    maxlength="255"
    name="profileURL"
    value="<?= auProfileEsc((string)$profile['profileURL']) ?>">

</div>


<div class="field">

<label>
    LANGUAGES
</label>

<textarea
    name="profileLanguages"><?= auProfileEsc((string)$profile['profileLanguages']) ?></textarea>

</div>


<div class="two">

<div class="field">

<label>
    I WANT TO
</label>

<textarea
    name="profileWantToText"><?= auProfileEsc((string)$profile['profileWantToText']) ?></textarea>

</div>


<div class="field">

<label>
    SKILLS
</label>

<textarea
    name="profileSkillsText"><?= auProfileEsc((string)$profile['profileSkillsText']) ?></textarea>

</div>

</div>


<div class="options">

<label>

<input
    type="checkbox"
    name="profileAllowPublish"
    value="1"
    <?= $allowPublish ? 'checked' : '' ?>>

ALLOW PROFILE PUBLISHING

</label>


<label>

<input
    type="checkbox"
    name="profileMaturePublish"
    value="1"
    <?= $maturePublish ? 'checked' : '' ?>>

MATURE PROFILE PUBLISHING

</label>

</div>


<div class="note">

SAVE PROFILE updates your signed avatar's actual
<strong>Robust.userprofile</strong> record.

It does not create a separate website profile.

</div>

</div>


</div>

</div>

</section>


<section
    id="profile-tab-firstlife"
    class="tab-panel">

<div class="profile-card">

<div class="profile-grid">


<div>

<div class="image-box">

<img
    src="<?= auProfileEsc($firstImageUrl) ?>"
    alt="First Life picture">

<div class="uuid">

FIRST LIFE TEXTURE UUID<br>
<?= auProfileEsc((string)$profile['profileFirstImage']) ?>

</div>

</div>


<div class="note">

This is the real First Life picture UUID stored in your
OpenSim profile.

</div>

</div>


<div>

<h2 class="section-title">
    FIRST LIFE
</h2>


<div class="field">

<label>
    FIRST LIFE ABOUT
</label>

<textarea
    class="big"
    name="profileFirstText"><?= auProfileEsc((string)$profile['profileFirstText']) ?></textarea>

</div>

</div>


</div>

</div>

</section>


<section
    id="profile-tab-picks"
    class="tab-panel">

<div class="profile-card">

<h2 class="section-title">
    PICKS
</h2>


<?php if (!$picks): ?>

<div class="empty">

You currently have no in-world Profile Picks.

</div>

<?php else: ?>

<div class="list">

<?php foreach ($picks as $pick): ?>

<article class="list-card">

<?php

$pickSnapshot =
    strtolower(
        trim(
            (string)(
                $pick['snapshotuuid'] ??
                ''
            )
        )
    );

?>


<div class="snapshot-label">
IN-WORLD SNAPSHOT
</div>


<div class="snapshot-wrap">

<?php if (
    auProfileIsUuid($pickSnapshot)
    &&
    $pickSnapshot !==
    '00000000-0000-0000-0000-000000000000'
): ?>

<img
    src="<?= auProfileEsc(
        '/Other/user-profile-image.php?id=' .
        rawurlencode($pickSnapshot)
    ) ?>"
    alt="<?= auProfileEsc(
        (string)$pick['name']
    ) ?> snapshot"
    loading="lazy">

<?php else: ?>

<div class="snapshot-empty">
NO SNAPSHOT SET
</div>

<?php endif; ?>

</div>


<h3>
<?= auProfileEsc((string)$pick['name']) ?>
</h3>

<p>
<?= auProfileEsc((string)$pick['description']) ?>
</p>

<?php

/*
 * OpenSim stores Pick position as GLOBAL coordinates.
 *
 * Example:
 *
 *     <25728, 25728, 21.98>
 *
 * For a normal 256x256 region that becomes:
 *
 *     128, 128, 22
 */

$pickGlobalX = 128.0;
$pickGlobalY = 128.0;
$pickGlobalZ = 0.0;


$pickPositionRaw =
    trim(
        (string)(
            $pick['posglobal'] ??
            ''
        )
    );


if (
    preg_match(
        '/<\s*(-?[0-9]+(?:\.[0-9]+)?)\s*,\s*(-?[0-9]+(?:\.[0-9]+)?)\s*,\s*(-?[0-9]+(?:\.[0-9]+)?)\s*>/',
        $pickPositionRaw,
        $positionMatch
    )
) {

    $pickGlobalX =
        (float)$positionMatch[1];

    $pickGlobalY =
        (float)$positionMatch[2];

    $pickGlobalZ =
        (float)$positionMatch[3];
}


$pickLocalX =
    (int)round(
        fmod(
            $pickGlobalX,
            256
        )
    );


$pickLocalY =
    (int)round(
        fmod(
            $pickGlobalY,
            256
        )
    );


if ($pickLocalX < 0) {
    $pickLocalX += 256;
}


if ($pickLocalY < 0) {
    $pickLocalY += 256;
}


$pickLocalZ =
    (int)round(
        $pickGlobalZ
    );


$pickRegion =
    trim(
        (string)(
            $pick['simname'] ??
            ''
        )
    );


/*
 * Firestorm / viewer links.
 */

$pickRegionUrl =
    rawurlencode(
        $pickRegion
    );


$showMapUrl =
    '/Other/grid-map-user.php?' .
    http_build_query(
        [
            'region' => $pickRegion,
            'x'      => $pickLocalX,
            'y'      => $pickLocalY,
            'z'      => $pickLocalZ
        ]
    );


$teleportUrl =
    'hop://' . ag_dg_hop_host() . '/' .
    $pickRegionUrl .
    '/' .
    $pickLocalX .
    '/' .
    $pickLocalY .
    '/' .
    $pickLocalZ;

?>


<div class="pick-location">

<div class="pick-location-title">
LOCATION
</div>


<div class="pick-location-text">

<?= auProfileEsc((string)$pick['name']) ?>,
<?= auProfileEsc($pickRegion) ?>

(
<?= (int)$pickLocalX ?>,
<?= (int)$pickLocalY ?>,
<?= (int)$pickLocalZ ?>
)

</div>


<div class="pick-actions">

<a
    class="pick-action"
    href="<?= auProfileEsc($showMapUrl) ?>">

    SHOW ON MAP

</a>


<a
    class="pick-action teleport"
    href="<?= auProfileEsc($teleportUrl) ?>">

    TELEPORT

</a>

</div>

</div>

</article>

<?php endforeach; ?>

</div>

<?php endif; ?>


<div class="note">

These are the Picks stored in your real OpenSim
<code>userpicks</code> profile table.

</div>

</div>

</section>


<section
    id="profile-tab-classifieds"
    class="tab-panel">

<div class="profile-card">

<h2 class="section-title">
    CLASSIFIEDS
</h2>


<?php if (!$classifieds): ?>

<div class="empty">

You currently have no in-world Classifieds.

</div>

<?php else: ?>

<div class="list">

<?php foreach ($classifieds as $item): ?>

<article class="list-card">

<?php

$classifiedSnapshot =
    strtolower(
        trim(
            (string)(
                $item['snapshotuuid'] ??
                ''
            )
        )
    );


/*
 * ============================================================
 * AUSTRALIA CLASSIFIED MAP TELEPORT V1
 * ============================================================
 */

$classifiedRegion =
    trim(
        (string)(
            $item['simname'] ??
            ''
        )
    );


$classifiedPosRaw =
    trim(
        (string)(
            $item['posglobal'] ??
            ''
        )
    );


$classifiedHasPosition =
    false;


$classifiedLocalX =
    128;


$classifiedLocalY =
    128;


$classifiedLocalZ =
    25;


if (
    preg_match(
        '/<?\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*>?/',
        $classifiedPosRaw,
        $classifiedPosMatch
    )
) {

    $classifiedGlobalX =
        (float)$classifiedPosMatch[1];


    $classifiedGlobalY =
        (float)$classifiedPosMatch[2];


    $classifiedGlobalZ =
        (float)$classifiedPosMatch[3];


    $classifiedLocalX =
        (int)floor(
            fmod(
                $classifiedGlobalX,
                256.0
            )
        );


    $classifiedLocalY =
        (int)floor(
            fmod(
                $classifiedGlobalY,
                256.0
            )
        );


    if ($classifiedLocalX < 0) {
        $classifiedLocalX += 256;
    }


    if ($classifiedLocalY < 0) {
        $classifiedLocalY += 256;
    }


    $classifiedLocalZ =
        (int)round(
            $classifiedGlobalZ
        );


    $classifiedHasPosition =
        true;
}


$classifiedMapUrl =
    '';


$classifiedTeleportUrl =
    '';


if (
    $classifiedRegion !== ''
    &&
    $classifiedHasPosition
) {

    $classifiedMapUrl =
        '/Other/grid-map-user.php?' .
        http_build_query(
            [
                'region' =>
                    $classifiedRegion,

                'x' =>
                    $classifiedLocalX,

                'y' =>
                    $classifiedLocalY,

                'z' =>
                    $classifiedLocalZ
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );


    $classifiedRegionUrl =
        rawurlencode(
            $classifiedRegion
        );


    $classifiedTeleportUrl =
        'hop://' . ag_dg_hop_host() . '/' .
        $classifiedRegionUrl .
        '/' .
        $classifiedLocalX .
        '/' .
        $classifiedLocalY .
        '/' .
        $classifiedLocalZ;
}


/* END AUSTRALIA CLASSIFIED MAP TELEPORT V1 */

?>


<div class="snapshot-label">
IN-WORLD SNAPSHOT
</div>


<div class="snapshot-wrap">

<?php if (
    auProfileIsUuid($classifiedSnapshot)
    &&
    $classifiedSnapshot !==
    '00000000-0000-0000-0000-000000000000'
): ?>

<img
    src="<?= auProfileEsc(
        '/Other/user-profile-image.php?id=' .
        rawurlencode($classifiedSnapshot)
    ) ?>"
    alt="<?= auProfileEsc(
        (string)$item['name']
    ) ?> snapshot"
    loading="lazy">

<?php else: ?>

<div class="snapshot-empty">
NO SNAPSHOT SET
</div>

<?php endif; ?>

</div>


<h3>
<?= auProfileEsc((string)$item['name']) ?>
</h3>

<p>
<?= auProfileEsc((string)$item['description']) ?>
</p>

<div class="list-meta">

REGION:
<?= auProfileEsc((string)$item['simname']) ?>

<br>

PARCEL:
<?= auProfileEsc((string)$item['parcelname']) ?>

<br>

LISTING PRICE:
<?= (int)$item['priceforlisting'] ?>

</div>


<?php if (
    $classifiedHasPosition
    &&
    $classifiedRegion !== ''
): ?>

<div class="pick-location">

<div class="pick-location-title">

<?= auProfileEsc(
    (string)$item['name']
) ?>,
<?= auProfileEsc(
    $classifiedRegion
) ?>
(<?= $classifiedLocalX ?>,
<?= $classifiedLocalY ?>,
<?= $classifiedLocalZ ?>)

</div>


<div
    style="
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:10px;
    ">

<a
    class="pick-action map"
    href="<?= auProfileEsc(
        $classifiedMapUrl
    ) ?>">

    MAP

</a>


<a
    class="pick-action teleport"
    href="<?= auProfileEsc(
        $classifiedTeleportUrl
    ) ?>">

    TELEPORT

</a>

</div>

</div>

<?php endif; ?>

</article>

<?php endforeach; ?>

</div>

<?php endif; ?>


<div class="note">

These are your real OpenSim Classified listings.

</div>

</div>

</section>


<div class="actions">

<a
    class="refresh"
    href="/Other/user-profile.php">

    REFRESH FROM IN-WORLD

</a>


<button
    class="save"
    type="submit">

    SAVE PROFILE

</button>

</div>


</form>

</main>


<script>

const profileButtons =
    document.querySelectorAll(
        "[data-profile-tab]"
    );


const profilePanels =
    document.querySelectorAll(
        ".tab-panel"
    );


profileButtons.forEach(
    function(button){

        button.addEventListener(
            "click",
            function(){

                const name =
                    button.dataset.profileTab;


                profileButtons.forEach(
                    function(item){

                        item.classList.remove(
                            "active"
                        );
                    }
                );


                profilePanels.forEach(
                    function(item){

                        item.classList.remove(
                            "active"
                        );
                    }
                );


                button.classList.add(
                    "active"
                );


                const target =
                    document.getElementById(
                        "profile-tab-" +
                        name
                    );


                if(target){

                    target.classList.add(
                        "active"
                    );
                }
            }
        );
    }
);

</script>


<script
    src="/Other/australia-modal.js?v=1">
</script>



<!-- ==========================================================
     AUSTRALIA PROFILE TELEPORT MODAL V1
     ========================================================== -->

<div
    id="au-teleport-modal"
    hidden
    aria-hidden="true">

    <div
        class="au-teleport-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="au-teleport-title">

        <div class="au-teleport-head">

            <div class="au-teleport-eyebrow">
                Grid
            </div>

            <h2 id="au-teleport-title">
                TELEPORT
            </h2>

            <button
                type="button"
                class="au-teleport-x"
                id="au-teleport-x"
                aria-label="Close">

                Ã—

            </button>

        </div>


        <div class="au-teleport-body">

            <div class="au-teleport-copy">
                This will open Firestorm and send the
                destination to your OpenSim viewer.
            </div>


            <div class="au-teleport-destination">

                <span>
                    DESTINATION
                </span>

                <strong id="au-teleport-destination">
                    Grid
                </strong>

            </div>


            <div class="au-teleport-note">
                Your browser may ask permission to open
                Firestorm. That security message is controlled
                by the browser.
            </div>

        </div>


        <div class="au-teleport-actions">

            <button
                type="button"
                class="au-teleport-button"
                id="au-teleport-cancel">

                CANCEL

            </button>


            <button
                type="button"
                class="au-teleport-button gold"
                id="au-teleport-open">

                OPEN FIRESTORM

            </button>

        </div>

    </div>

</div>


<script>

/*
 * ============================================================
 * AUSTRALIA PROFILE TELEPORT MODAL V1
 * ============================================================
 */

(function(){

    "use strict";


    const modal =
        document.getElementById(
            "au-teleport-modal"
        );


    const destination =
        document.getElementById(
            "au-teleport-destination"
        );


    const openButton =
        document.getElementById(
            "au-teleport-open"
        );


    const cancelButton =
        document.getElementById(
            "au-teleport-cancel"
        );


    const closeButton =
        document.getElementById(
            "au-teleport-x"
        );


    if(
        !modal ||
        !destination ||
        !openButton
    ){
        return;
    }


    let teleportUrl = "";


    function closeTeleport(){

        modal.hidden = true;

        modal.setAttribute(
            "aria-hidden",
            "true"
        );


        document.body.classList.remove(
            "au-teleport-open"
        );


        teleportUrl = "";

    }


    function openTeleport(link){

        teleportUrl =
            String(
                link.getAttribute("href") ||
                ""
            );


        const card =
            link.closest(
                ".list-card"
            );


        const title =
            card
            ?
            String(
                card.querySelector("h3")
                    ?.textContent ||
                ""
            ).trim()
            :
            "";


        const location =
            card
            ?
            String(
                card.querySelector(
                    ".pick-location-text"
                )?.textContent ||
                ""
            )
            .replace(
                /\s+/g,
                " "
            )
            .trim()
            :
            "";


        destination.textContent =
            location ||
            title ||
            "Grid";


        modal.hidden = false;

        modal.setAttribute(
            "aria-hidden",
            "false"
        );


        document.body.classList.add(
            "au-teleport-open"
        );


        openButton.focus();

    }


    document.addEventListener(
        "click",
        function(event){

            const teleport =
                event.target.closest(
                    "a.pick-action.teleport"
                );


            if(!teleport){
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            openTeleport(
                teleport
            );

        },
        true
    );


    cancelButton?.addEventListener(
        "click",
        closeTeleport
    );


    closeButton?.addEventListener(
        "click",
        closeTeleport
    );


    modal.addEventListener(
        "click",
        function(event){

            if(event.target === modal){

                closeTeleport();

            }

        }
    );


    document.addEventListener(
        "keydown",
        function(event){

            if(modal.hidden){
                return;
            }


            if(event.key === "Escape"){

                event.preventDefault();

                closeTeleport();

            }

        }
    );


    openButton.addEventListener(
        "click",
        function(){

            const url =
                teleportUrl;


            if(!url){
                return;
            }


            /*
             * Launch Firestorm DIRECTLY from the user's
             * OPEN FIRESTORM click.
             *
             * Do not defer this through setTimeout because
             * Chromium external-protocol handling tracks
             * the initiating user gesture.
             */

            window.location.href =
                url;


            /*
             * Close our Grid popup after the
             * protocol launch has been initiated.
             */

            closeTeleport();

        }
    );


})();

/* END AUSTRALIA PROFILE TELEPORT MODAL V1 */

</script>
<script src="/Other/assets/js/ag-uniform-site-v12.js?v=20260906-role-standard-v3"></script>
</body>
</html>


