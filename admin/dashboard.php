<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Get statistics
$totalAlumni = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$pendingAlumni = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch_assoc()['count'];
$totalPosts = $db->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];
$pendingJobs = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'pending'")->fetch_assoc()['count'];
$totalBatches = $db->query("SELECT COUNT(*) as count FROM batches")->fetch_assoc()['count'];
$newFeedback = $db->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'new'")->fetch_assoc()['count'];

// Recent activities
$recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentPosts = $db->query("SELECT p.*, u.name FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
?>

<div class="container-fluid">
    <h4 class="mb-4">
        <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard Overview
    </h4>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalAlumni; ?></div>
                    <div class="stat-label">Active Alumni</div>
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-primary mt-2">View</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo $pendingAlumni; ?></div>
                    <div class="stat-label">Pending Approval</div>
                    <a href="<?php echo SITE_URL; ?>/admin/users.php?status=pending" class="btn btn-sm btn-outline-primary mt-2">Review</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalPosts; ?></div>
                    <div class="stat-label">Total Posts</div>
                    <a href="<?php echo SITE_URL; ?>/admin/posts.php" class="btn btn-sm btn-outline-primary mt-2">View</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-number"><?php echo $pendingJobs; ?></div>
                    <div class="stat-label">Pending Jobs</div>
                    <a href="<?php echo SITE_URL; ?>/admin/jobs.php?status=pending" class="btn btn-sm btn-outline-primary mt-2">Review</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalBatches; ?></div>
                    <div class="stat-label">Batches</div>
                    <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-number"><?php echo $newFeedback; ?></div>
                    <div class="stat-label">New Feedback</div>
                    <a href="<?php echo SITE_URL; ?>/admin/feedback.php" class="btn btn-sm btn-outline-primary mt-2">View</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Users -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-user-plus me-2 text-primary"></i>Recent Registrations
                    </h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Batch</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $recentUsers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['batch']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Posts -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-newspaper me-2 text-primary"></i>Recent Posts
                    </h5>
                    
                    <div class="list-group list-group-flush">
                        <?php while ($post = $recentPosts->fetch_assoc()): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($post['name']); ?></h6>
                                        <p class="mb-1 small">
                                            <?php echo htmlspecialchars(truncateText($post['content'], 80)); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo timeAgo($post['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
