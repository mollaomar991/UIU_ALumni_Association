<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Get user stats
$stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$myPostsCount = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $db->prepare("SELECT COUNT(DISTINCT receiver_id) as count FROM messages WHERE sender_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$connectionsCount = $stmt->get_result()->fetch_assoc()['count'];

$unreadNotifications = getUnreadNotificationCount($user['id']);

// Get recent posts by user
$stmt = $db->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$recentPosts = $stmt->get_result();

// Get notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<div class="container py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Welcome Card -->
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-3">
                        <i class="fas fa-hand-wave me-2 text-primary"></i>
                        Welcome back, <?php echo htmlspecialchars($user['name']); ?>!
                    </h4>
                    <p class="text-muted mb-0">
                        Stay connected with your fellow alumni and explore new opportunities.
                    </p>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="stat-number"><?php echo $myPostsCount; ?></div>
                            <div class="stat-label">My Posts</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="stat-number"><?php echo $connectionsCount; ?></div>
                            <div class="stat-label">Connections</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="stat-number"><?php echo $unreadNotifications; ?></div>
                            <div class="stat-label">Notifications</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-bolt me-2 text-primary"></i>Quick Actions
                    </h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo SITE_URL; ?>/user/feed.php" class="btn btn-primary">
                            <i class="fas fa-newspaper me-1"></i>View Feed
                        </a>
                        <a href="<?php echo SITE_URL; ?>/user/explore.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-1"></i>Explore Alumni
                        </a>
                        <a href="<?php echo SITE_URL; ?>/user/profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-edit me-1"></i>Edit Profile
                        </a>
                        <a href="<?php echo SITE_URL; ?>/user/jobs.php" class="btn btn-outline-primary">
                            <i class="fas fa-briefcase me-1"></i>Browse Jobs
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Posts -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-history me-2 text-primary"></i>My Recent Posts
                    </h5>
                    
                    <?php if ($recentPosts->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($post = $recentPosts->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <p class="mb-1"><?php echo htmlspecialchars(truncateText($post['content'], 100)); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo timeAgo($post['created_at']); ?>
                                            </small>
                                        </div>
                                        <a href="<?php echo SITE_URL; ?>/user/feed.php#post-<?php echo $post['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/user/feed.php" class="btn btn-link text-primary">
                                View All Posts <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state py-4">
                            <div class="empty-state-icon">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="empty-state-title">No posts yet</div>
                            <div class="empty-state-text mb-3">Share your first post with the community</div>
                            <a href="<?php echo SITE_URL; ?>/user/feed.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Create Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo SITE_URL . '/uploads/' . $user['profile_image']; ?>" 
                         class="rounded-circle mb-3" 
                         style="width: 100px; height: 100px; object-fit: cover; border: 3px solid <?php echo PRIMARY_COLOR; ?>;"
                         alt="<?php echo htmlspecialchars($user['name']); ?>">
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted small mb-2">
                        <?php echo htmlspecialchars($user['batch']); ?> | <?php echo htmlspecialchars($user['department']); ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>/user/profile.php" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-user-edit me-1"></i>Edit Profile
                    </a>
                </div>
            </div>
            
            <!-- Notifications -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-bell me-2 text-primary"></i>Recent Notifications
                    </h6>
                    
                    <?php if ($notifications->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($notif = $notifications->fetch_assoc()): ?>
                                <div class="list-group-item px-0 <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-2">
                                            <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <small class="text-muted">
                                                <?php echo timeAgo($notif['created_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">No notifications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
