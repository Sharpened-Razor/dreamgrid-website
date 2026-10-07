<?php

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ag_redirect(ag_route('login'));
}

ag_require_same_origin_post();

$avatar =
    trim(
        (string)(
            $_POST['avatar'] ??
            ''
        )
    );

/*
 * Public Australia front page uses separate First Name and
 * Last Name fields.
 *
 * Existing callers may still submit one combined "avatar"
 * field, so support both formats.
 */
if ($avatar === '') {

    $submittedFirstName =
        trim(
            (string)(
                $_POST['firstname'] ??
                ''
            )
        );


    $submittedLastName =
        trim(
            (string)(
                $_POST['lastname'] ??
                ''
            )
        );


    $avatar =
        trim(
            $submittedFirstName .
            ' ' .
            $submittedLastName
        );
}


$password =
    (string)(
        $_POST['password'] ??
        ''
    );


$remember =
    !empty(
        $_POST['remember']
    );

if ($avatar === '' || $password === '') {
    ag_redirect(ag_route('login') . '?error=1', 303);
}

/*
 * Reuse the existing Grid credential validator/session issuer.
 * There is one password-validation implementation only.
 */
$oldPost = $_POST;

$_POST = [
    'avatar' => $avatar,
    'password' => $password,
    'remember' => $remember ? '1' : '0',
];

ob_start();
require __DIR__ . '/login/login.php';
$result = trim((string)ob_get_clean());

$_POST = $oldPost;

if (strcasecmp($result, 'Success') !== 0) {
    ag_redirect(ag_route('login') . '?error=1', 303);
}

/*
 * Authentication succeeded.
 *
 * The shared login validator runs in this file's scope, so the validated
 * session array is already available here. The signed dg_session cookie
 * has also already been issued.
 *
 * Route directly to the correct home page instead of displaying a
 * second login screen.
 */
if (!isset($session) || !is_array($session)) {
    ag_redirect(ag_route('login') . '?error=1', 303);
}

if (ag_is_admin($session)) {
    ag_redirect(ag_route('admin_home'), 303);
}

ag_redirect(ag_route('user_dashboard'), 303);