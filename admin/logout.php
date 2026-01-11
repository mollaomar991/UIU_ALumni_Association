<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// Destroy admin session
session_unset();
session_destroy();

// Redirect to admin login
redirectWithMessage(SITE_URL . '/auth/login.php', 'You have been logged out successfully.', 'success');
?>
