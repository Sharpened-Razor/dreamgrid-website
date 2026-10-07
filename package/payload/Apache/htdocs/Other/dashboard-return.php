<?php

/*
 * Grid
 * PERMANENT DASHBOARD RETURN ROUTER
 *
 * Admin -> Admin Dashboard
 * User  -> User Dashboard
 * Guest -> Login
 *
 * No browser history is used.
 */

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

$session = ag_current_session();


/*
 * Not signed in.
 */

if (!$session) {

    ag_redirect(
        ag_route('login')
    );
}


/*
 * Administrator / Grid Owner.
 *
 * An Admin must NEVER be returned to user-dashboard.php.
 */

if (ag_is_admin($session)) {

    ag_redirect(
        ag_route('admin_dashboard')
    );
}


/*
 * Normal signed-in user.
 */

ag_redirect(
    ag_route('user_dashboard')
);