<?php

/*
 * Grid
 * Permanent home redirect.
 *
 * The permanent PHP login router decides whether the
 * signed-in visitor belongs on Admin Home or User Home.
 */

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

ag_redirect(
    ag_route('login')
);