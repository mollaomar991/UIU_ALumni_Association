<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// Destroy session
session_unset();
session_destroy();

// Redirect to home page
redirectWithMessage(SITE_URL, 'You have been logged out successfully.', 'success');
?>
