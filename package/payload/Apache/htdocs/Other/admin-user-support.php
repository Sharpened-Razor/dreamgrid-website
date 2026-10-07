<?php

require_once __DIR__ . '/core/bootstrap.php';

ag_no_cache();

ag_require_admin();

ag_redirect(
    ag_route('admin_dashboard')
);