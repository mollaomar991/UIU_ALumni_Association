<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'uiu_alumni_connect');

// Site Configuration
define('SITE_NAME', 'UIU Alumni Connect');
// Dynamic Site URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Assuming the app is in 'AlumniAccociation' folder. Adjust if needed or root.
// If running from root, verify path. For now, strict match to folder name.
define('SITE_URL', $protocol . "://" . $host . "/AlumniAccociation");
define('SITE_EMAIL', 'info@uiu.ac.bd');

// SMTP Configuration (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');      // e.g., smtp.gmail.com
define('SMTP_USER', 'mollaomar2009@gmail.com'); // Your email
define('SMTP_PASS', 'osdkwntcimrmhtpt');    // Your email password or app password
define('SMTP_PORT', 587);                   // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls');               // 'tls' or 'ssl'
define('SMTP_FROM_NAME', SITE_NAME);        // Name to appear in "From" field

// UIU Theme Colors
define('PRIMARY_COLOR', '#FF6622');
define('SECONDARY_COLOR', '#FFFFFF');
define('TEXT_COLOR', '#222222');
define('ACCENT_COLOR', '#E65100');

// Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
