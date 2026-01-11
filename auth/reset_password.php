<?php
$pageTitle = 'Reset Password';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
startSession();

$message = '';
$messageType = '';
$validToken = false;
$email = '';

// Check token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = getDB();
    
    // Verify token
    $stmt = $db->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();
    
    if ($reset) {
        // Check if token expired
        if (strtotime($reset['expires_at']) > time()) {
            $validToken = true;
            $email = $reset['email'];
        } else {
            $message = 'This reset link has expired. Please request a new one.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Invalid reset link.';
        $messageType = 'danger';
    }
} else {
    $message = 'No reset token provided.';
    $messageType = 'danger';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Please fill in all fields.';
        $messageType = 'warning';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'warning';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'warning';
    } else {
        // Update password
        $hashedPassword = hashPassword($newPassword);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        
        if ($stmt->execute()) {
            // Delete used token
            $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = 'Password reset successful! You can now login with your new password.';
            $messageType = 'success';
            $validToken = false; // Hide form
        } else {
            $message = 'Failed to reset password. Please try again.';
            $messageType = 'danger';
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
        .reset-card {
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-card mx-auto">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">Reset Password</h3>
                        <p class="text-muted">Enter your new password</p>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($validToken): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" 
                                           placeholder="Enter new password" required minlength="6">
                                </div>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           placeholder="Confirm new password" required minlength="6">
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check me-2"></i>Reset Password
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="<?php echo SITE_URL; ?>/auth/forgot_password.php" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-2"></i>Request New Link
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
</body>
</html>
