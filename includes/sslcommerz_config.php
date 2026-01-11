<?php
// SSLCommerz Configuration
// For production, change SSLCZ_TESTMODE to false and update credentials

define('SSLCZ_TESTMODE', true); // Set to false for live mode

if (SSLCZ_TESTMODE) {
    // Sandbox Credentials
    define('SSLCZ_STORE_ID', 'testbox');
    define('SSLCZ_STORE_PASSWORD', 'qwerty');
    define('SSLCZ_API_URL', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php');
    define('SSLCZ_VALIDATION_URL', 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php');
} else {
    // Live Credentials (Replace with your actual credentials)
    define('SSLCZ_STORE_ID', 'your_store_id');
    define('SSLCZ_STORE_PASSWORD', 'your_store_password');
    define('SSLCZ_API_URL', 'https://securepay.sslcommerz.com/gwprocess/v4/api.php');
    define('SSLCZ_VALIDATION_URL', 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php');
}

// Callback URLs
define('SSLCZ_SUCCESS_URL', SITE_URL . '/api/payment_success.php');
define('SSLCZ_FAIL_URL', SITE_URL . '/api/payment_fail.php');
define('SSLCZ_CANCEL_URL', SITE_URL . '/api/payment_cancel.php');
define('SSLCZ_IPN_URL', SITE_URL . '/api/payment_ipn.php');
?>
