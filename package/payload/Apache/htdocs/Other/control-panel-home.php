<?php

declare(strict_types=1);

/*
 * AUSTRALIA CONTROL CENTER
 * Control Panel route loader.
 *
 * admin-home.php already opens:
 * /Other/control-panel-home.php
 *
 * This loader keeps that frozen route intact and loads
 * the fresh Control Panel implementation.
 */

require __DIR__ . '/control-panel-clean.php';