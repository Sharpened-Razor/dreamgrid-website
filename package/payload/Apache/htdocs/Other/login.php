<?php

/*
 * Grid
 *
 * The old standalone login page has been retired.
 * All public authentication begins from the main front page.
 */

header(
    'Location: /Other/index.php',
    true,
    303
);

exit;
