<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';

// Redirect if already logged in
if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
} elseif (isLoggedIn()) {
    redirect(SITE_URL . '/user/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($loginInput) || empty($password)) {
        $errors[] = "Email/Username and password are required";
    } else {
        $db = getDB();
        
        // 1. Check if Admin
        $stmt = $db->prepare("SELECT * FROM admin WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $loginInput, $loginInput);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (verifyPassword($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                redirectWithMessage(SITE_URL . '/admin/dashboard.php', 'Welcome, ' . $admin['username'] . '!', 'success');
            }
        }
        
        // 2. Check if User (if not admin or admin password failed - though ideally we shouldn't fall through if admin found but pass wrong. 
        // However, for security obscurement, we can just continue or simpler: Check both, if no match in either, fail. 
        // If match in admin but wrong pass, fail. If match in user but wrong pass, fail.
        // Let's keep it simple: Try Admin first. If not found or pass wrong, try User. 
        // Wait, if I try Admin and found but wrong pass, I shouldn't try User? 
        // Actually, a user might have same email as admin? Unlikely given unique constraints usually but separate tables.
        // Schema says admin email unique, users email unique. But technically one person could be in both tables with same email.
        // If they are in both, we should prioritize Admin? Or ask? 
        // prioritized Admin above. If admin found and password verifies, we return.
        
        // If we are here, either not admin OR admin password wrong.
        // Let's check User table now.
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        // Users table technically doesn't have username column in schema provided (only name, email).
        $stmt->bind_param("s", $loginInput);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] === 'blocked') {
                $errors[] = "Your account has been blocked. Please contact admin.";
            } elseif ($user['status'] === 'pending') {
                $errors[] = "Your account is pending approval. Please wait for admin approval.";
            } elseif (verifyPassword($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                redirectWithMessage(SITE_URL . '/user/dashboard.php', 'Welcome back, ' . $user['name'] . '!', 'success');
            } else {
                // Determine if we should show generic error or specific
                $errors[] = "Invalid email/username or password";
            }
        } else {
             // Not found in Admin (or pass wrong there) AND Not found in User.
             // But wait, if found in Admin but pass wrong, we fell through to here.
             // And if not found in User, we add generic error.
             // This is fine. generic error covers all cases.
             $errors[] = "Invalid email/username or password";
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">Welcome Back</h3>
                        <p class="text-muted">Login to your account</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email or Username</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="Enter email or username" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                            </div>
                        </div>
                        
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                        
                        <div class="text-center mb-3">
                            <a href="<?php echo SITE_URL; ?>/auth/forgot_password.php" class="text-decoration-none">
                                <i class="fas fa-key me-1"></i>Forgot Password?
                            </a>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? 
                                <a href="<?php echo SITE_URL; ?>/auth/register.php" class="text-primary fw-bold">Register</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
