<?php
ob_start(); // Start output buffering to allow headers to be sent later
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
startSession();

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'UIU CIRCLE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- MDB Bootstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <style>
        :root {
            --primary-color: <?php echo PRIMARY_COLOR; ?>;
            --secondary-color: <?php echo SECONDARY_COLOR; ?>;
            --text-color: <?php echo TEXT_COLOR; ?>;
            --accent-color: <?php echo ACCENT_COLOR; ?>;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light" style="background-color: <?php echo PRIMARY_COLOR; ?>;">
        <div class="container-fluid">
            <a class="navbar-brand text-white fw-bold" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-graduation-cap me-2"></i>UIU CIRCLE
            </a>
            
            <button class="navbar-toggler text-white" type="button" data-mdb-toggle="collapse" 
                    data-mdb-target="#navbarNav" aria-controls="navbarNav" 
                    aria-expanded="false" aria-label="Toggle navigation"
                    style="border-color: white;">
                <i class="fas fa-bars text-white"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/feed.php">
                                <i class="fas fa-newspaper me-1"></i>Feed
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/explore.php">
                                <i class="fas fa-users me-1"></i>Alumni
                            </a>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/mentors.php">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Mentors
                            </a>
                        </li>
                        <?php if ($currentUser['role'] !== 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/groups.php">
                                <i class="fas fa-user-friends me-1"></i>Groups
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/events.php">
                                <i class="fas fa-calendar-alt me-1"></i>Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/jobs.php">
                                <i class="fas fa-briefcase me-1"></i>Jobs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/donations.php">
                                <i class="fas fa-hand-holding-heart me-1"></i>Donate
                            </a>
                        </li>
                        <?php if ($currentUser['role'] === 'alumni'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/user/gallery.php">
                                    <i class="fas fa-images me-1"></i>Gallery
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-white position-relative" href="<?php echo SITE_URL; ?>/user/messages.php">
                                <i class="fas fa-envelope me-1"></i>Messages
                                <?php 
                                $unreadCount = 0; // Will be implemented
                                if ($unreadCount > 0): 
                                ?>
                                    <span class="badge bg-danger badge-pill position-absolute top-0 start-100 translate-middle">
                                        <?php echo $unreadCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white d-flex align-items-center" 
                               href="#" id="navbarDropdown" role="button" 
                               data-mdb-toggle="dropdown" aria-expanded="false">
                                <?php
                                $headerAvatarUrl = SITE_URL . '/uploads/' . ($currentUser['profile_image'] ?? 'default-avatar.png');
                                if (($currentUser['profile_image'] ?? 'default-avatar.png') == 'default-avatar.png' || 
                                    !file_exists(UPLOAD_PATH . ($currentUser['profile_image'] ?? 'default-avatar.png'))) {
                                    $headerAvatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&size=50&background=FF6622&color=fff';
                                }
                                ?>
                                <img src="<?php echo $headerAvatarUrl; ?>" 
                                     class="rounded-circle me-2" 
                                     style="width: 30px; height: 30px; object-fit: cover;"
                                     alt="Profile"
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['name']); ?>&size=50&background=FF6622&color=fff'">
                                <?php echo htmlspecialchars($currentUser['name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/profile.php">
                                        <i class="fas fa-user me-2"></i>My Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-light btn-sm ms-2" href="<?php echo SITE_URL; ?>/auth/register.php">
                                Join Now
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
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
        <div class="container mt-3">
            <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main>
