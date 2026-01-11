<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

// Get featured alumni
$db = getDB();
$featuredAlumni = $db->query("SELECT * FROM users WHERE status = 'active' ORDER BY RAND() LIMIT 3");

// Get stats
$totalAlumni = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$totalBatches = $db->query("SELECT COUNT(DISTINCT batch_name) as count FROM batches")->fetch_assoc()['count'];
$totalPosts = $db->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-12 text-center">
                <h1 class="display-4 fw-bold mb-4 fade-in">
                    Connect with UIU Alumni Worldwide
                </h1>
                <p class="lead mb-4">
                    Join thousands of United International University alumni sharing experiences, 
                    building networks, and creating opportunities together.
                </p>
                <?php if (!isLoggedIn()): ?>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-light btn-lg px-5">
                            <i class="fas fa-user-plus me-2"></i>Join Now
                        </a>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/user/feed.php" class="btn btn-light btn-lg px-5">
                        <i class="fas fa-newspaper me-2"></i>Go to Feed
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($totalAlumni); ?>+</div>
                    <div class="stat-label">Active Alumni</div>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($totalBatches); ?>+</div>
                    <div class="stat-label">Batches</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($totalPosts); ?>+</div>
                    <div class="stat-label">Stories Shared</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Why Join UIU Alumni Connect?</h2>
            <p class="text-muted">Everything you need to stay connected with your alma mater</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h5 class="card-title">Network</h5>
                        <p class="card-text text-muted">
                            Connect with alumni across different batches and departments globally.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h5 class="card-title">Career Growth</h5>
                        <p class="card-text text-muted">
                            Discover job opportunities and career guidance from successful alumni.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h5 class="card-title">Mentorship</h5>
                        <p class="card-text text-muted">
                            Get guidance from experienced alumni and mentor juniors in return.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h5 class="card-title">Events</h5>
                        <p class="card-text text-muted">
                            Stay updated with reunions, webinars, and university events.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Alumni Section -->
<?php if ($featuredAlumni->num_rows > 0): ?>
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Featured Alumni</h2>
            <p class="text-muted">Meet some of our successful alumni</p>
        </div>
        
        <div class="row g-4">
            <?php while ($alumni = $featuredAlumni->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card alumni-card h-100">
                        <?php 
                        $avatarUrl = SITE_URL . '/uploads/' . $alumni['profile_image'];
                        if ($alumni['profile_image'] == 'default-avatar.png' || !file_exists(UPLOAD_PATH . $alumni['profile_image'])) {
                            $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($alumni['name']) . '&size=200&background=FF6622&color=fff';
                        }
                        ?>
                        <img src="<?php echo $avatarUrl; ?>" 
                             class="alumni-avatar" alt="<?php echo htmlspecialchars($alumni['name']); ?>"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($alumni['name']); ?>&size=200&background=FF6622&color=fff'">
                        <div class="alumni-name"><?php echo htmlspecialchars($alumni['name']); ?></div>
                        <div class="alumni-details">
                            <div class="mb-1">
                                <i class="fas fa-graduation-cap me-1 text-primary"></i>
                                <?php echo htmlspecialchars($alumni['batch']); ?>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-building me-1 text-primary"></i>
                                <?php echo htmlspecialchars($alumni['department']); ?>
                            </div>
                            <?php if ($alumni['work_experience']): ?>
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars(truncateText($alumni['work_experience'], 50)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if (!isLoggedIn()): ?>
            <div class="text-center mt-5">
                <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Join to See More Alumni
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<?php if (!isLoggedIn()): ?>
<section class="py-5" style="background: linear-gradient(135deg, <?php echo PRIMARY_COLOR; ?> 0%, <?php echo ACCENT_COLOR; ?> 100%);">
    <div class="container text-center text-white">
        <h2 class="fw-bold mb-3">Ready to Join the Network?</h2>
        <p class="lead mb-4">
            Become part of the UIU alumni community today and unlock endless opportunities.
        </p>
        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-light btn-lg px-5">
            <i class="fas fa-rocket me-2"></i>Get Started Now
        </a>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
