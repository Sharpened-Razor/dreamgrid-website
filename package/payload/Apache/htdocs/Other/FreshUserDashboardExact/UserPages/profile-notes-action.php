<?php
// Only this server-side entry point enables signed-in self-service.
define('AG_USER_PROFILE_ACTION', true);
require dirname(__DIR__, 2) . '/core/profile-notes-action.php';
