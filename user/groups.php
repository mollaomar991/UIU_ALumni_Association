<?php
$pageTitle = 'Batch & Department Groups';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] === 'student') {
    redirectWithMessage(SITE_URL . '/user/dashboard.php', 'Students cannot access groups.', 'error');
}
$db = getDB();

// Get department total members
$deptQuery = "SELECT COUNT(*) as total_count FROM users WHERE department = ? AND status = 'active'";
$deptStmt = $db->prepare($deptQuery);
$deptStmt->bind_param("s", $user['department']);
$deptStmt->execute();
$deptStats = $deptStmt->get_result()->fetch_assoc();

// Get user's specific batch (restrict view to own batch only)
$query = "SELECT b.*, COUNT(u.id) as member_count 
          FROM batches b 
          LEFT JOIN users u ON (u.batch = b.batch_name AND u.department = b.department AND u.status = 'active')
          WHERE b.batch_name = ?
          GROUP BY b.id";
$stmt = $db->prepare($query);
$stmt->bind_param("s", $user['batch']);
$stmt->execute();
$groups = $stmt->get_result();

// Get user's batch info
$myBatch = $user['batch'];
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-4">
                <i class="fas fa-users-rectangle me-2 text-primary"></i>My Communities
            </h4>
            
            <!-- Department Group Section -->
            <h5 class="mb-4 text-muted">
                <i class="fas fa-building me-2"></i>Department Group
            </h5>
            
            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow transition-all">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="stat-icon bg-light rounded-circle p-3 text-primary" style="width: 60px; height: 60px; font-size: 1.5rem; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-building"></i>
                                </div>
                                <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo $deptStats['total_count']; ?> Members</span>
                            </div>
                            
                            <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($user['department']); ?></h5>
                            <p class="text-muted small mb-4">Connect with all alumni from your department</p>
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>/user/group_chat.php?type=department&name=<?php echo urlencode($user['department']); ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-comments me-2"></i>Department Chat
                                </a>
                                <a href="<?php echo SITE_URL; ?>/user/explore.php?department=<?php echo urlencode($user['department']); ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-users me-2"></i>View Members
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Batch Groups Section -->
            <h5 class="mb-4 text-muted">
                <i class="fas fa-layer-group me-2"></i>My Batch Group
            </h5>
            
            <?php if ($groups->num_rows > 0): ?>
                <div class="row g-4">
                <?php while ($group = $groups->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4" id="batch-<?php echo urlencode($group['batch_name']); ?>">
                        <div class="card h-100 shadow-sm hover-shadow transition-all">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="stat-icon bg-light rounded-circle p-3 text-primary" style="width: 60px; height: 60px; font-size: 1.5rem; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo $group['member_count']; ?> Members</span>
                                </div>
                                
                                <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($group['batch_name']); ?></h5>
                                
                                <?php if ($group['description']): ?>
                                    <p class="text-muted small mb-4">
                                        <?php echo htmlspecialchars($group['description']); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted small mb-4">Batch group for <?php echo htmlspecialchars($group['batch_name']); ?></p>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2">
                                    <a href="<?php echo SITE_URL; ?>/user/group_chat.php?type=batch&name=<?php echo urlencode($group['batch_name']); ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-comments me-2"></i>Group Chat
                                    </a>
                                    
                                    <?php if ($group['batch_name'] === $myBatch): ?>
                                         <a href="<?php echo SITE_URL; ?>/user/explore.php?department=<?php echo urlencode($user['department']); ?>&batch=<?php echo urlencode($group['batch_name']); ?>" 
                                           class="btn btn-success">
                                            <i class="fas fa-check me-2"></i>Your Batch (Members)
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo SITE_URL; ?>/user/explore.php?department=<?php echo urlencode($user['department']); ?>&batch=<?php echo urlencode($group['batch_name']); ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-users me-2"></i>View Members
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No batches found for your department.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
