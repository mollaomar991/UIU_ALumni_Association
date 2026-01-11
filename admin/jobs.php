<?php
$pageTitle = 'Job Management';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_job'])) {
        $jobId = (int)$_POST['job_id'];
        $stmt = $db->prepare("UPDATE jobs SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $jobId);
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/admin/jobs.php', 'Job approved successfully!', 'success');
        }
    } elseif (isset($_POST['reject_job'])) {
        $jobId = (int)$_POST['job_id'];
        $stmt = $db->prepare("UPDATE jobs SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $jobId);
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/admin/jobs.php', 'Job rejected successfully!', 'success');
        }
    } elseif (isset($_POST['delete_job'])) {
        $jobId = (int)$_POST['job_id'];
        $stmt = $db->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->bind_param("i", $jobId);
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/admin/jobs.php', 'Job deleted successfully!', 'success');
        }
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? '';

$condition = "1=1";
if (!empty($statusFilter)) {
    $condition = "j.status = '$statusFilter'";
}

$query = "SELECT j.*, u.name as posted_by_name, u.email as posted_by_email 
          FROM jobs j 
          JOIN users u ON j.posted_by = u.id 
          WHERE $condition
          ORDER BY j.created_at DESC";
$jobs = $db->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-briefcase me-2 text-primary"></i>Job Management
        </h4>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo empty($statusFilter) ? 'active' : ''; ?>" 
               href="<?php echo SITE_URL; ?>/admin/jobs.php">All Jobs</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" 
               href="<?php echo SITE_URL; ?>/admin/jobs.php?status=pending">Pending</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>" 
               href="<?php echo SITE_URL; ?>/admin/jobs.php?status=approved">Approved</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" 
               href="<?php echo SITE_URL; ?>/admin/jobs.php?status=rejected">Rejected</a>
        </li>
    </ul>
    
    <?php if ($jobs->num_rows > 0): ?>
        <?php while ($job = $jobs->fetch_assoc()): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <span class="badge bg-<?php 
                                    echo $job['status'] === 'approved' ? 'success' : 
                                        ($job['status'] === 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </div>
                            
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($job['company']); ?>
                            </h6>
                            
                            <div class="mb-3">
                                <span class="badge bg-secondary me-2">
                                    <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                                </span>
                                <?php if ($job['location']): ?>
                                    <span class="text-muted me-2">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($job['salary_range']): ?>
                                    <span class="text-muted">
                                        <i class="fas fa-dollar-sign me-1"></i><?php echo htmlspecialchars($job['salary_range']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="mb-3"><?php echo nl2br(htmlspecialchars(truncateText($job['description'], 300))); ?></p>
                            
                            <div class="text-muted small">
                                Posted by <strong><?php echo htmlspecialchars($job['posted_by_name']); ?></strong> 
                                (<?php echo htmlspecialchars($job['posted_by_email']); ?>)
                                on <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <div class="d-grid gap-2">
                                <?php if ($job['status'] === 'pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" name="approve_job" class="btn btn-success w-100">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" name="reject_job" class="btn btn-warning w-100">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" name="delete_job" class="btn btn-danger w-100" 
                                            onclick="return confirm('Delete this job?')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="empty-state-title">No jobs found</div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
