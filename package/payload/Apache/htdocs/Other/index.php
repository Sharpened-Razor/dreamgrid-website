<?php
require_once __DIR__ . '/core/grid-branding.php';
// ============================================================
// DREAMGRID DYNAMIC GRID WEBSITE URL V2
// ============================================================

$agPublicHomeScheme =
    (!empty($_SERVER['HTTPS']) &&
     strtolower((string)$_SERVER['HTTPS']) !== 'off')
        ? 'https'
        : 'http';

$agPublicHomeHost =
    trim(
        (string)(
            $_SERVER['HTTP_HOST'] ??
            $_SERVER['SERVER_NAME'] ??
            ''
        )
    );

$agPublicHomeHost =
    preg_replace(
        '/[^A-Za-z0-9.\-:\[\]]/',
        '',
        $agPublicHomeHost
    ) ?? '';

$agPublicScriptName =
    str_replace(
        '\\',
        '/',
        (string)($_SERVER['SCRIPT_NAME'] ?? '')
    );

$agPublicHomePath =
    $agPublicScriptName !== ''
        ? rtrim(
            str_replace(
                '\\',
                '/',
                dirname($agPublicScriptName)
            ),
            '/'
        )
        : '';

if (
    $agPublicHomePath === '.' ||
    $agPublicHomePath === '/'
) {
    $agPublicHomePath = '';
}

$agPublicHomeUrl =
    $agPublicHomeHost !== ''
        ? $agPublicHomeScheme .
          '://' .
          $agPublicHomeHost .
          (
              $agPublicHomePath !== ''
                  ? $agPublicHomePath
                  : ''
          ) .
          '/'
        : '';

$agPublicHomeUrlHtml =
    htmlspecialchars(
        $agPublicHomeUrl,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

/*
 * ============================================================
 * AUSTRALIA PUBLIC LOGIN SHELL PANELS V1
 * ============================================================
 */

$australiaPublicPanel =
    strtolower(
        trim(
            (string)(
                $_GET['panel'] ??
                ''
            )
        )
    );

if (
    !in_array(
        $australiaPublicPanel,
        [
            '',
            'create-account',
            'terms',
            'forgot-password'
        ],
        true
    )
) {
    $australiaPublicPanel = '';
}


if (
    $australiaPublicPanel ===
    'create-account'
) {

    define(
        'AUSTRALIA_CREATE_ACCOUNT_CONTROLLER_ONLY',
        true
    );

    require __DIR__ . '/create-account.php';
}

if (
    $australiaPublicPanel ===
    'forgot-password'
) {

    define(
        'AUSTRALIA_FORGOT_PASSWORD_CONTROLLER_ONLY',
        true
    );

    require __DIR__ . '/forgot-password.php';
}

// AUSTRALIA GRID - PUBLIC PHP FRONT PAGE
?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Grid Home</title>

<link
    rel="shortcut icon"
    href="/favicon.ico">

<link
    rel="stylesheet"
    href="/Other/assets/css/console.css">

<link
    rel="stylesheet"
    href="/Other/assets/css/fluid.css">

<link
    rel="stylesheet"
    href="/Other/assets/css/style.css">

<style>

body {
    min-height:100vh;
}

#header {
    display:none !important;
}

body.australia-home-page
#australia-home-panel {
    display:block !important;
}

body.australia-home-page
#php-home-footer {
    position:fixed;
    left:18px;
    right:388px;
    bottom:10px;
    z-index:40;
    text-align:center;
    color:#d8d8d8;
    font:700 11px Arial,sans-serif;
    text-shadow:0 2px 5px #000;
    pointer-events:none;
}

body.australia-home-page
#php-home-footer
.php-footer-main {
    color:#fff;
    font-size:12px;
    margin-bottom:5px;
}

body.australia-home-page
#php-home-footer
.php-footer-main strong {
    color:#f4b323;
}

body.australia-home-page
#php-home-footer
.php-footer-rules {
    color:#f0f0f0;
    letter-spacing:.03em;
}



@media(max-width:900px) {

    body.australia-home-page
    #php-home-footer {
        display:none;
    }
}

</style>

    <link rel="stylesheet" href="/Other/core/frontpage-screen-fit.css?v=20260827-035217">
<link rel="stylesheet" href="/Other/assets/css/ag-glow-icons.css?v=20260829-halfglow-v2">
<link rel="stylesheet" href="/Other/assets/css/ag-auto-icons.css?v=20260829-halfglow-v2">
<script defer src="/Other/assets/js/ag-auto-icons.js?v=20260830-visual-clean-v7"></script>
<link
    rel="stylesheet"
    href="/Other/assets/css/ag-uniform-site-v12.css?v=20260902-design-system-v1">
<link rel="stylesheet" href="/Other/assets/css/ag-sentinel-icons-v1.css?v=20260902-sentinel-v1">

<style id="australia-sentinel-sitewide-v3">


/* ============================================================
   AUSTRALIA SENTINEL 3D CARD SYSTEM V3
   SITE WIDE
   ============================================================ */


.dashboard-card,
.control-hub-card,
.stat-card,
.summary-card,
.inventory-card,
.profile-card,
.region-panel,
.section-card,
.manage-card{


position:relative !important;

overflow:hidden !important;


border-radius:15px !important;


border:

2px solid
rgba(210,218,220,.55) !important;


background:

linear-gradient(
145deg,
#4d5559,
#090b0c
) !important;


box-shadow:

inset 0 2px 0
rgba(255,255,255,.25),

inset 0 -22px 35px
rgba(0,0,0,.8),

0 18px 40px
rgba(0,0,0,.75) !important;


}



.dashboard-card:after,
.control-hub-card:after,
.stat-card:after,
.summary-card:after,
.inventory-card:after,
.profile-card:after,
.region-panel:after,
.section-card:after,
.manage-card:after{


content:"";

position:absolute;

inset:7px;

border-radius:10px;

pointer-events:none;


border:

1px solid
rgba(255,193,58,.45);

}



.card-title,
.card-heading,
.panel-title,
.summary-title{


color:#ffd167 !important;

font-weight:900 !important;

text-shadow:

0 2px 5px
rgba(0,0,0,.8);

}



.card-icon,
.card-icon.svg-badge{


background:

linear-gradient(
145deg,
#70777a,
#111415
) !important;


border:

1px solid
rgba(255,255,255,.3) !important;


box-shadow:

inset 0 2px 4px
rgba(255,255,255,.25),

0 8px 18px
rgba(0,0,0,.6);


}



.dashboard-card:hover,
.control-hub-card:hover,
.stat-card:hover,
.summary-card:hover{


transform:
translateY(-3px);


}


</style>


<!-- FRONT_CONTROL_PANEL_V1_CSS -->
<link rel="stylesheet" href="/Other/assets/css/front-control-panel-v1.css?v=20260910-223501">
<link
    rel="stylesheet"
    href="/Other/assets/css/front-public-panels-v1.css?v=20260915-233430-forgot-v1">

<link
    rel="stylesheet"
    href="/Other/site-design-display.php?slot=frontpage-background">
</head>


<body class="australia-home-page">
<?php if ($australiaPublicPanel === 'forgot-password'): ?>

<div
    class="ag-public-panel-layer"
    data-public-panel="forgot-password">

    <section
        class="ag-public-panel-card ag-public-forgot-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ag-forgot-title">

        <header class="ag-public-panel-header">

            <div class="ag-public-panel-heading">

                <div class="ag-public-panel-kicker">
                    GRID SERVICES
                </div>

                <div
                    class="ag-public-panel-title"
                    id="ag-forgot-title">
                    Forgot Password
                </div>

                <div class="ag-public-panel-subtitle">
                    Reset the password for your Grid avatar.
                </div>

            </div>


            <a
                class="ag-public-panel-close"
                href="/Other/index.php"
                title="Return to Login">

                <img
                    src="/Other/assets/icons/sentinel/home.png"
                    alt=""
                    aria-hidden="true">

                <span>BACK TO LOGIN</span>

            </a>

        </header>


        <div class="ag-public-panel-body">


            <?php if ($sent): ?>


                <div class="ag-public-message ag-public-message-success">

                    <div class="ag-public-message-title">
                        PASSWORD RESET REQUEST RECEIVED
                    </div>

                    <div>
                        If that email address belongs to a Grid
                        account, password reset instructions will
                        be sent to it.
                    </div>

                    <div class="ag-public-forgot-success-note">
                        Check your inbox and your spam/junk folder.
                    </div>

                </div>


                <div class="ag-public-panel-actions">

                    <a
                        class="ag-public-primary-button"
                        href="/Other/index.php">

                        <img
                            src="/Other/assets/icons/sentinel/home.png"
                            alt=""
                            aria-hidden="true">

                        <span>CLOSE</span>

                    </a>

                </div>


            <?php else: ?>


                <div class="ag-public-section-title">
                    Reset Your Password
                </div>

                <div class="ag-public-section-copy">
                    Enter the email address registered with your
                    Grid avatar. You will receive password recovery
                    instructions at that address.
                </div>


                <?php if ($error !== ''): ?>

                    <div class="ag-public-message ag-public-message-error">

                        <div class="ag-public-message-title">
                            PASSWORD RESET
                        </div>

                        <div>
                            <?=ag_public_h($error)?>
                        </div>

                    </div>

                <?php endif; ?>


                <form
                    method="post"
                    action="/Other/index.php?panel=forgot-password"
                    class="ag-public-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?=ag_public_h($csrfToken)?>">


                    <div class="ag-public-field">

                        <label for="ag_forgot_email">
                            ACCOUNT EMAIL
                        </label>

                        <input
                            id="ag_forgot_email"
                            name="email"
                            type="email"
                            maxlength="254"
                            autocomplete="email"
                            value="<?=ag_public_h($email)?>"
                            required>

                    </div>


                    <div class="ag-public-forgot-security-note">

                        <img
                            src="/Other/assets/icons/sentinel/security.png"
                            alt=""
                            aria-hidden="true">

                        <span>
                            For security, the Grid does not reveal
                            whether a particular email address is
                            registered.
                        </span>

                    </div>


                    <div class="ag-public-panel-actions">

                        <button
                            class="ag-public-primary-button"
                            type="submit">

                            <img
                                src="/Other/assets/icons/sentinel/email.png"
                                alt=""
                                aria-hidden="true">

                            <span>SEND RESET INSTRUCTIONS</span>

                        </button>


                        <a
                            class="ag-public-secondary-button"
                            href="/Other/index.php">

                            <span>CANCEL</span>

                        </a>

                    </div>

                </form>


            <?php endif; ?>


        </div>

    </section>

</div>

<?php endif; ?>

<?php if ($australiaPublicPanel === 'terms'): ?>

<div
    class="ag-public-panel-layer"
    data-public-panel="terms">

    <section
        class="ag-public-panel-card ag-public-terms-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ag-terms-title">

        <header class="ag-public-panel-header">

            <div class="ag-public-panel-heading">

                <div class="ag-public-panel-kicker">
                    GRID SERVICES
                </div>

                <div
                    class="ag-public-panel-title"
                    id="ag-terms-title">
                    Terms of Service
                </div>

                <div class="ag-public-panel-subtitle">
                    Please read the Terms of Service for
                    <?=ag_grid_name_html()?>.
                </div>

            </div>


            <a
                class="ag-public-panel-close"
                href="/Other/index.php"
                title="Return to Login">

                <img
                    src="/Other/assets/icons/sentinel/home.png"
                    alt=""
                    aria-hidden="true">

                <span>BACK TO LOGIN</span>

            </a>

        </header>


        <div class="ag-public-panel-body ag-public-terms-body">

            <div class="ag-public-terms-copy">

                <h3>Terms of Service</h3>

                <p>
                    <strong>
                        Welcome to <?=ag_grid_name_html()?> !
                    </strong>
                </p>

                <p>
                    Your access to and use of <?=ag_grid_name_html()?>
                    is based on your acceptance of and compliance with
                    these Terms. These Terms apply to all visitors,
                    users and others who access or use the Service.
                    By accessing or using the Service you agree to be
                    bound by these Terms. If you disagree with any
                    part of the terms then you may not access the
                    Service.
                </p>


                <h4>DRM in Opensimulator</h4>

                <p>
                    You acknowledge the use of Digital Rights
                    Management in Opensimulator that grants certain
                    content licenses to other users through the
                    permissions system. This may include the copy,
                    modify, and transfer settings for indicating how
                    other users may use, reproduce, distribute,
                    prepare derivative works of, display, or perform
                    your Content subject to choices you make. You
                    agree to respect, follow and allow the DRM rights
                    of others that use this system.
                </p>

                <p>
                    You acknowledge that content creators maintain
                    their rights to their content. You accept full
                    responsibility for use of provided content and
                    remain liable for unlawful usage.
                </p>


                <h4>DMCA Notices</h4>

                <p>
                    If a content provider reports a DMCA violation to
                    <?=ag_grid_name_html()?>, it will be investigated
                    and if warranted, removed from public access.
                </p>

                <p>
                    Offending accounts may be banned from use of the
                    services.
                </p>


                <h4>Copyrights</h4>

                <p>
                    You agree that you will not copy, transfer, or
                    distribute outside of <?=ag_grid_name_html()?> any
                    content in whole or in part or in modified or
                    unmodified form, that infringes or violates any
                    Intellectual Property Rights of
                    <?=ag_grid_name_html()?>, other Content Providers,
                    or any third parties.
                </p>


                <h4>Age</h4>

                <p>
                    You certify that by clicking ACCEPT that you are
                    over 18 years of age.
                </p>


                <h4>Data Rights</h4>

                <p>
                    You grant <?=ag_grid_name_html()?> permission to
                    process your data to maintain your account. This
                    includes:
                </p>

                <ul>
                    <li>
                        Registration information you provide when you
                        create an account such as:

                        <ul>
                            <li>Your Email address</li>
                            <li>Your Avatar Name</li>
                            <li>Your IP address</li>
                        </ul>
                    </li>

                    <li>
                        Information necessary to connect to your PC
                        such as session ID's and web cookies
                    </li>

                    <li>
                        Any other personal information you give us to
                        service your account.
                    </li>

                    <li>
                        Transaction information you provide when you
                        request information or purchase a product or
                        service from us, whether on our sites or
                        through our applications.
                    </li>
                </ul>


                <h4>Hyperlinks</h4>

                <p>
                    <?=ag_grid_name_html()?> may contain links or
                    hyperlinks to third-party services that are not
                    owned or controlled by
                    <?=ag_grid_name_html()?>. Hyperlinks are
                    destinations that you may travel to. Hyperlinks
                    connect to third parties and they will have access
                    to limited data. They will need this info to
                    provide Hypergrid service, and may not follow the
                    GDPR act, but may use this information by means
                    outside of our control.
                </p>

                <p>
                    You agree that your Avatar Name, Session ID,
                    IP address and other technical information can be
                    sent to other opensim grids when you travel the
                    hypergrid. <?=ag_grid_name_html()?> has no control
                    over, and assumes no responsibility for, the
                    content, privacy policies, or practices of any
                    third party web sites or services. You further
                    acknowledge and agree that
                    <?=ag_grid_name_html()?> may contain links to
                    third-party services that are not owned or
                    controlled by <?=ag_grid_name_html()?>.
                </p>


                <h4>Liability</h4>

                <p>
                    <?=ag_grid_name_html()?> shall not be responsible
                    or liable, directly or indirectly, for any damage
                    or loss caused or alleged to be caused by or in
                    connection with use of or reliance on any such
                    content, goods or services available on or through
                    any such web sites or services.
                </p>


                <h4>Right to be forgotten</h4>

                <p>
                    You have the right to request erasure of personal
                    identifying information, or to the deletion of
                    your account. <?=ag_grid_name_html()?> may retain
                    certain records that
                    <?=ag_grid_name_html()?> is required to retain by
                    law.
                </p>


                <h4>Termination</h4>

                <p>
                    We may terminate or suspend access to our Service
                    immediately, without prior notice or liability,
                    for any reason whatsoever, without limitation if
                    you breach the Terms.
                </p>

                <p>
                    All provisions of the Terms which by their nature
                    should survive termination shall survive
                    termination, including, without limitation,
                    ownership provisions, warranty disclaimers,
                    indemnity and limitations of liability. This
                    Agreement may be modified for any reason, without
                    prior notice (unless prior notice is required by
                    law), by posting the revised Agreement here. If at
                    any time you do not agree to those terms and
                    conditions, you must immediately cease your use of
                    the system.
                </p>


                <h4>Agreement</h4>

                <p>
                    You specifically give
                    <?=ag_grid_name_html()?> permission to collect and
                    use your data as indicated in this Policy and you
                    agree to be bound by this Terms of Service.
                </p>

            </div>


            <div class="ag-public-panel-actions">

                <a
                    class="ag-public-primary-button"
                    href="/Other/index.php">

                    <img
                        src="/Other/assets/icons/sentinel/home.png"
                        alt=""
                        aria-hidden="true">

                    <span>BACK TO LOGIN</span>

                </a>

            </div>

        </div>

    </section>

</div>

<?php endif; ?>

<?php if ($australiaPublicPanel === 'create-account'): ?>

<div
    class="ag-public-panel-layer"
    data-public-panel="create-account">

    <section
        class="ag-public-panel-card ag-public-create-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ag-create-title">

        <header class="ag-public-panel-header">

            <div class="ag-public-panel-heading">

                <div class="ag-public-panel-kicker">
                    GRID SERVICES
                </div>

                <div
                    class="ag-public-panel-title"
                    id="ag-create-title">
                    Create Account
                </div>

                <div class="ag-public-panel-subtitle">
                    Create your OpenSimulator avatar for
                    <?=ag_grid_name_html()?>.
                </div>

            </div>


            <a
                class="ag-public-panel-close"
                href="/Other/index.php"
                title="Return to Login">

                <img
                    src="/Other/assets/icons/sentinel/home.png"
                    alt=""
                    aria-hidden="true">

                <span>BACK TO LOGIN</span>

            </a>

        </header>


        <div class="ag-public-panel-body">


            <?php if ($success): ?>


                <div class="ag-public-message ag-public-message-success">

                    <div class="ag-public-message-title">
                        ACCOUNT CREATED
                    </div>

                    <div>
                        Your account has been created successfully.
                        You can now sign in using your new avatar
                        first name, last name and password.
                    </div>

                </div>


                <div class="ag-public-panel-actions">

                    <a
                        class="ag-public-primary-button"
                        href="/Other/index.php">

                        <img
                            src="/Other/assets/icons/sentinel/key.png"
                            alt=""
                            aria-hidden="true">

                        <span>GO TO LOGIN</span>

                    </a>


                    <a
                        class="ag-public-secondary-button"
                        href="/Other/index.php?panel=create-account">

                        <img
                            src="/Other/assets/icons/sentinel/account.png"
                            alt=""
                            aria-hidden="true">

                        <span>CREATE ANOTHER ACCOUNT</span>

                    </a>

                </div>


            <?php else: ?>


                <div class="ag-public-section-title">
                    Create Your Account
                </div>

                <div class="ag-public-section-copy">
                    Your first and last name become your
                    OpenSimulator avatar name.
                </div>


                <?php if ($error !== ''): ?>

                    <div class="ag-public-message ag-public-message-error">

                        <div class="ag-public-message-title">
                            PLEASE CHECK THE FORM
                        </div>

                        <div>
                            <?=ag_h($error)?>
                        </div>

                    </div>

                <?php endif; ?>


                <form
                    method="post"
                    action="/Other/index.php?panel=create-account"
                    autocomplete="off"
                    class="ag-public-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?=ag_h($csrfToken)?>">


                    <div class="ag-public-form-grid">


                        <div class="ag-public-field">

                            <label for="ag_first_name">
                                FIRST NAME
                            </label>

                            <input
                                id="ag_first_name"
                                name="first_name"
                                type="text"
                                maxlength="64"
                                value="<?=ag_h($firstName)?>"
                                required
                                autocomplete="off">

                            <div class="ag-public-field-help">
                                No spaces, @, periods or colons.
                            </div>

                        </div>


                        <div class="ag-public-field">

                            <label for="ag_last_name">
                                LAST NAME
                            </label>

                            <input
                                id="ag_last_name"
                                name="last_name"
                                type="text"
                                maxlength="64"
                                value="<?=ag_h($lastName)?>"
                                required
                                autocomplete="off">

                            <div class="ag-public-field-help">
                                No spaces, @, periods or colons.
                            </div>

                        </div>


                        <div class="ag-public-field ag-public-field-full">

                            <label for="ag_create_email">
                                EMAIL
                            </label>

                            <input
                                id="ag_create_email"
                                name="email"
                                type="email"
                                maxlength="254"
                                value="<?=ag_h($email)?>"
                                required
                                autocomplete="email">

                        </div>


                        <div class="ag-public-field">

                            <label for="ag_create_password">
                                PASSWORD
                            </label>

                            <input
                                id="ag_create_password"
                                name="password"
                                type="password"
                                minlength="8"
                                maxlength="128"
                                required
                                autocomplete="new-password">

                        </div>


                        <div class="ag-public-field">

                            <label for="ag_confirm_password">
                                CONFIRM PASSWORD
                            </label>

                            <input
                                id="ag_confirm_password"
                                name="confirm_password"
                                type="password"
                                minlength="8"
                                maxlength="128"
                                required
                                autocomplete="new-password">

                        </div>

                    </div>


                    <label class="ag-public-terms-check">

                        <input
                            type="checkbox"
                            name="accept_terms"
                            value="1"
                            required>

                        <span>
                            I agree to the
                            <a
                                href="/Other/index.php?panel=terms"
                                target="_blank"
                                rel="noopener">
                                Terms of Service
                            </a>.
                        </span>

                    </label>


                    <div class="ag-public-panel-actions">

                        <button
                            class="ag-public-primary-button"
                            type="submit">

                            <img
                                src="/Other/assets/icons/sentinel/account.png"
                                alt=""
                                aria-hidden="true">

                            <span>CREATE ACCOUNT</span>

                        </button>


                        <a
                            class="ag-public-secondary-button"
                            href="/Other/index.php">

                            <span>CANCEL</span>

                        </a>

                    </div>

                </form>


            <?php endif; ?>


        </div>

    </section>

</div>

<?php endif; ?>


<div id="page">


<div id="right-nav">

<div class="australia-menu-panel">

  <p class="nav-headline">MAIN MENU</p>

  <div id="main-menu" class="australia-main-menu">

    <ul>


      <li>
        <a href="/Other/index.php?panel=create-account" class="australia-public-button create-account">
    <img class="button-picture" src="/Other/assets/icons/sentinel/account.png" alt="" aria-hidden="true">
    <span class="menu-label">CREATE ACCOUNT</span>
</a>
      </li>
<li>
            <a href="/Other/login-help-centre.php" class="australia-public-button help-centre">
    <img class="button-picture" src="/Other/assets/icons/sentinel/help.png" alt="" aria-hidden="true">
    <span class="menu-label">HELP CENTRE</span>
</a>
        </li>
<?php if ($agPublicHomeUrl !== ''): ?>
        <li>
            <a
                href="<?= $agPublicHomeUrlHtml ?>"
                target="_external"
                rel="noopener noreferrer"
                class="australia-public-button website"
                title="<?= ag_grid_name_html() ?> Website">

                <img
                    class="button-picture"
                    src="/Other/assets/icons/sentinel/home.png"
                    alt=""
                    aria-hidden="true">

                <span class="menu-label">WEBSITE</span>
            </a>
        </li>
<?php endif; ?>

    </ul>

  </div>

</div>

<br>

<div class="right-nav-divider"></div>

<div class="australia-login-card">
  <p class="nav-headline">Member Login</p>
  <div id="subscribe">
    <form action="/Other/login-action.php" method="post" class="australia-login-form">
      <input name="METHOD" type="hidden" value="login" />

      <label class="field-label">First Name</label>
      <input name="firstname" type="text" value="" placeholder="First name" class="inputstyle" autocomplete="username" />

      <label class="field-label">Last Name</label>
      <input name="lastname" type="text" value="" placeholder="Last name" class="inputstyle" autocomplete="username" />

      <label class="field-label">Password</label>
      <input name="password" type="password" value="" placeholder="Password" class="inputstyle" autocomplete="current-password" />

      <label class="australia-remember-me">
        <input
          name="remember"
          type="checkbox"
          value="1" />
        <span>Remember me</span>
      </label>

      <button
    type="submit"
    class="button australia-login-picture-button australia-login-picture-button-login">

    <span class="australia-login-button-icon" aria-hidden="true">

        <img class="ag-sentinel-direct-icon ag-sentinel-lg" src="/Other/assets/icons/sentinel/key.png" alt="" aria-hidden="true" draggable="false" decoding="async">

    </span>

    <span class="australia-login-button-text">
        LOG IN
    </span>

</button>
    </form>

    <form action="/Other/index.php" method="get">
      <input type="hidden" name="panel" value="forgot-password">
      <button
    type="submit"
    class="button secondary-button australia-action-picture-button australia-forgot-picture-button">

    <span class="australia-action-icon" aria-hidden="true">

        <img class="ag-sentinel-direct-icon ag-sentinel-lg" src="/Other/assets/icons/sentinel/email.png" alt="" aria-hidden="true" draggable="false" decoding="async">

    </span>


    <span class="australia-action-button-text">
        FORGOT PASSWORD
    </span>

</button>
    </form>
    <form action="/Other/index.php" method="get" class="australia-terms-form">
      <input type="hidden" name="panel" value="terms">
      <button
    type="submit"
    class="button secondary-button australia-action-picture-button australia-terms-picture-button">

    <span class="australia-action-icon" aria-hidden="true">

        <img class="ag-sentinel-direct-icon ag-sentinel-lg" src="/Other/assets/icons/sentinel/report.png" alt="" aria-hidden="true" draggable="false" decoding="async">

    </span>


    <span class="australia-action-button-text">
        TERMS OF SERVICE
    </span>

</button>
    </form>
  </div>
</div>

</div>


<div id="content">


<!-- AUSTRALIA GRID - DreamGrid content shell + HOME-only statistics -->

<div class="box-top">
  <div class="box-top-left"></div>
  <div class="box-top-right"></div>
</div>

<div class="box australia-page-box">

  <!-- AUSTRALIA PHP PUBLIC HOME -->
  <div id="australia-home-panel" style="display:none">
    <div class="content-heading">
      <div>
        <div class="eyebrow">WELCOME TO</div>
        <h1><?=ag_grid_name_html()?></h1>
      </div>
    </div>

    <div class="content-divider"></div>

    <div id="grid-stats-cards" class="grid-stats-cards" aria-label="Grid statistics" data-australia-layout="forced-2x2-v5" style="display:grid !important; grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important; grid-template-rows:auto auto !important; grid-auto-flow:row !important;">
      <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
          <img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/users.png" alt="" aria-hidden="true" draggable="false" decoding="async">
        </div>
        <div class="stat-copy">
          <div class="stat-label">USERS ONLINE</div>
          <div class="stat-value" id="stat-users-online">0</div>
          <div class="stat-sub">Active now</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
          <img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/region.png" alt="" aria-hidden="true" draggable="false" decoding="async">
        </div>
        <div class="stat-copy">
          <div class="stat-label">REGIONS ONLINE</div>
          <div class="stat-value" id="stat-regions">0</div>
          <div class="stat-sub">Regions</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
          <img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/map.png" alt="" aria-hidden="true" draggable="false" decoding="async">
        </div>
        <div class="stat-copy">
          <div class="stat-label">TOTAL USERS</div>
          <div class="stat-value" id="stat-total-users">0</div>
          <div class="stat-sub">Registered</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
          <img class="ag-sentinel-direct-icon ag-sentinel-xl" src="/Other/assets/icons/sentinel/calendar.png" alt="" aria-hidden="true" draggable="false" decoding="async">
        </div>
        <div class="stat-copy">
          <div class="stat-label">ACTIVE USERS</div>
          <div class="stat-value" id="stat-active-users">0</div>
          <div class="stat-sub">Last 30 days</div>
        </div>
      </div>
    </div>
  </div>

  <!-- DREAMGRID'S NATIVE DYNAMIC CONTENT: DO NOT MOVE/REWRITE -->
  <p id="dreamgrid-dynamic-content"></p>

</div>

<div class="box-bottom">
  <div class="box-bottom-left"></div>
  <div class="box-bottom-right"></div>
</div>

<script type="text/javascript">
(function () {
  function isHomePage() {
    var p = (window.location.pathname || "").toLowerCase();
    // Australia PHP public front page.
    // Normalize trailing slashes.
    p = p.replace(/\/+$/, "");
    return p === "" ||
           p === "/" ||
           p === "/index.html" ||
           
           p === "/Other/index.php";
  }

  function initAustraliaHomeStats() {
    var home = document.getElementById("australia-home-panel");
    if (!isHomePage()) {
      document.body.classList.remove("australia-home-page");
      if (home) home.style.display = "none";
      return;
    }

    document.body.classList.add("australia-home-page");
    if (home) home.style.display = "";

    var content = document.getElementById("content");
    if (!content) return;

    var text = (content.textContent || content.innerText || "").replace(/\s+/g, " ");

    function pick(pattern, fallback) {
      var match = text.match(pattern);
      return match && match[1] !== undefined ? match[1] : fallback;
    }

    function setStat(id, value) {
      var el = document.getElementById(id);
      if (el) el.textContent = value;
    }

    setStat("stat-users-online", pick(/Users\s+in\s+World\s*:\s*(\d+)/i, "0"));
    setStat("stat-regions", pick(/Regions\s*:\s*(\d+)/i, "0"));
    setStat("stat-total-users", pick(/Total\s+Users\s*:\s*(\d+)/i, "0"));
    setStat("stat-active-users",
      pick(/Active\s+Users(?:\s*\(\s*Last\s*30\s*Days?\s*\))?\s*:\s*(\d+)/i, "0")
    );

    var oldPhoto = null;
    if (oldPhoto) oldPhoto.style.display = "none";

    var legacyStats = content.querySelectorAll("p.white");
    for (var i = 0; i < legacyStats.length; i++) {
      var p = legacyStats[i];
      if (/Users\s+in\s+World|Total\s+Users|Active\s+Users|Regions\s*:/i.test(p.textContent || "")) {
        p.style.display = "none";
      }
    }
  }

  if (document.readyState === "complete") {
    initAustraliaHomeStats();
  } else {
    window.addEventListener("load", initAustraliaHomeStats);
  }
})();
</script>





</div>


</div>
<script>

window.addEventListener(
    "load",
    function () {

        document.body.classList.add(
            "australia-home-page"
        );

        var home =
            document.getElementById(
                "australia-home-panel"
            );

        if (home) {

            home.style.display =
                "block";
        }
    }
);

</script>


<!-- AUSTRALIA LIVE FRONT STATS V2 START -->

<script id="australia-live-front-stats-v2">

(function () {

    "use strict";


    var endpoint =
        "/Other/front-stats.php";


    function setStat(
        id,
        value
    ) {

        var el =
            document.getElementById(
                id
            );


        if (!el) {

            return;
        }


        el.textContent =
            String(value);
    }


    function showLoading() {

        setStat(
            "stat-users-online",
            "..."
        );

        setStat(
            "stat-regions",
            "..."
        );

        setStat(
            "stat-total-users",
            "..."
        );

        setStat(
            "stat-active-users",
            "..."
        );
    }


    function showUnavailable() {

        setStat(
            "stat-users-online",
            "-"
        );

        setStat(
            "stat-regions",
            "-"
        );

        setStat(
            "stat-total-users",
            "-"
        );

        setStat(
            "stat-active-users",
            "-"
        );
    }


    function applyStats(
        data
    ) {

        if (
            !data ||
            data.ok !== true
        ) {

            showUnavailable();

            return;
        }


        setStat(
            "stat-users-online",
            data.users_online
        );


        setStat(
            "stat-regions",
            data.regions_online
        );


        setStat(
            "stat-total-users",
            data.total_users
        );


        setStat(
            "stat-active-users",
            data.active_users
        );
    }


    function loadStats() {

        fetch(
            endpoint +
            "?t=" +
            Date.now(),
            {
                method: "GET",
                cache: "no-store",
                credentials: "same-origin"
            }
        )

        .then(
            function (response) {

                if (!response.ok) {

                    throw new Error(
                        "Stats request failed"
                    );
                }


                return response.json();
            }
        )

        .then(
            function (data) {

                applyStats(
                    data
                );
            }
        )

        .catch(
            function () {

                showUnavailable();
            }
        );
    }


    function startLiveStats() {

        /*
         * The old DreamGrid-era front page code also runs
         * during window load.
         *
         * This loader is installed after it, so our live
         * database figures become the final values shown.
         */

        showLoading();

        loadStats();


        window.setInterval(
            loadStats,
            30000
        );
    }


    if (
        document.readyState ===
        "complete"
    ) {

        window.setTimeout(
            startLiveStats,
            0
        );

    }
    else {

        window.addEventListener(
            "load",
            startLiveStats
        );
    }

})();

</script>

<!-- AUSTRALIA LIVE FRONT STATS V2 END -->







<!-- FRONT_CONTROL_PANEL_V1_JS -->
<script src="/Other/assets/js/front-control-panel-v1.js?v=20261005-show-password-v1"></script></body>

</html>




