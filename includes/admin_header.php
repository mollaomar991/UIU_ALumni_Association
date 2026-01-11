<?php
ob_start(); // Start output buffering to allow headers to be sent later
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
startSession();
requireAdmin();

$currentAdmin = getCurrentAdmin();
$pageTitle = $pageTitle ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Admin Panel</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <style>
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: <?php echo PRIMARY_COLOR; ?>;
            color: white;
            padding: 20px;
            overflow-y: auto;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .admin-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <h5 class="text-white mb-4">
            <i class="fas fa-shield-alt me-2"></i>Admin Panel
        </h5>
        
        <div class="mb-4 pb-3 border-bottom border-light">
            <small class="text-white-50">Logged in as</small>
            <div class="fw-bold"><?php echo htmlspecialchars($currentAdmin['username']); ?></div>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                <i class="fas fa-tachometer-alt"></i>Dashboard
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/users.php">
                <i class="fas fa-users"></i>Alumni Management
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/posts.php">
                <i class="fas fa-newspaper"></i>Post Moderation
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/jobs.php">
                <i class="fas fa-briefcase"></i>Job Management
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/events.php">
                <i class="fas fa-calendar-alt"></i>Events
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/fundraisers.php">
                <i class="fas fa-hand-holding-heart"></i>Fundraising
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/feedback.php">
                <i class="fas fa-comments"></i>Feedback
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/settings.php">
                <i class="fas fa-cog"></i>Settings
            </a>
            
            <hr class="bg-light my-3">
            
            <a class="nav-link" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-home"></i>View Site
            </a>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/logout.php">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a>
        </nav>
    </div>
    
    <!-- Admin Content -->
    <div class="admin-content">
        <?php
        // Display flash messages
        $flash = getFlashMessage();
        if ($flash):
            $alertClass = match($flash['type']) {
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                default => 'alert-info'
            };
        ?>
            <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
        <?php endif; ?>
