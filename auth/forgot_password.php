<?php
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
startSession();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'warning';
    } else {
        $db = getDB();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing tokens for this email
            $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Insert new token
            $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires);
            $stmt->execute();
            
            // Create reset link
            $resetLink = SITE_URL . "/auth/reset_password.php?token=" . $token;
            
            $subject = "Password Reset - " . SITE_NAME;
            $emailMessage = "<h2>Password Reset Request</h2>";
            $emailMessage .= "<p>Hello <strong>{$user['name']}</strong>,</p>";
            $emailMessage .= "<p>You recently requested to reset your password for your account. Click the button below to proceed:</p>";
            $emailMessage .= "<p><a href='{$resetLink}' style='background-color:#FF6622; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Reset Password</a></p>";
            $emailMessage .= "<p>Or copy and paste this link into your browser:<br>{$resetLink}</p>";
            $emailMessage .= "<p>This link will expire in 1 hour.</p>";
            $emailMessage .= "<p>If you did not request a password reset, please ignore this email or contact support if you have questions.</p>";
            
            if (sendEmail($email, $subject, $emailMessage)) {
                $message = "A password reset link has been sent to your email address. Please check your inbox and spam folder.";
                $messageType = 'success';
            } else {
                // If email fails (e.g., incomplete config), show the link for development purposes
                $message = "Could not send email. Verify your SMTP settings in config.php.";
                $message .= "<br><strong>Development Fallback:</strong> <a href='$resetLink' class='alert-link'>Click here to reset password</a>";
                $messageType = 'warning';
            }
        } else {
            // Don't reveal if email exists or not (security)
            $message = "If your email is registered, you will receive a password reset link.";
            $messageType = 'info';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - UIU Alumni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, <?php echo PRIMARY_COLOR; ?> 0%, <?php echo SECONDARY_COLOR; ?> 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .forgot-card {
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-card mx-auto">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-key fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">Forgot Password?</h3>
                        <p class="text-muted">Enter your email to receive a reset link</p>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="your.email@example.com" required>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-white">
                    <i class="fas fa-info-circle me-1"></i>
                    Reset link will expire in 1 hour
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
</body>
</html>
