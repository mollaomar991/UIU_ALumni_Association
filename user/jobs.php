<?php
$pageTitle = 'Job Board';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Handle job posting
// Handle job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    if ($user['role'] === 'student') {
        redirectWithMessage(SITE_URL . '/user/jobs.php', 'Students cannot post jobs.', 'error');
    }

    $title = sanitizeInput($_POST['title'] ?? '');
    $company = sanitizeInput($_POST['company'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $jobType = sanitizeInput($_POST['job_type'] ?? 'full-time');
    $salaryRange = sanitizeInput($_POST['salary_range'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (!empty($title) && !empty($company) && !empty($description)) {
        $stmt = $db->prepare("INSERT INTO jobs (title, company, location, job_type, salary_range, description, posted_by) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $title, $company, $location, $jobType, $salaryRange, $description, $user['id']);
        
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/user/jobs.php', 'Job posted successfully! Awaiting admin approval.', 'success');
        }
    }
}

// Get jobs
$filterType = $_GET['type'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$conditions = ["j.status = 'approved'"];
$params = [];
$types = '';

if (!empty($filterType)) {
    $conditions[] = "j.job_type = ?";
    $params[] = $filterType;
    $types .= 's';
}

if (!empty($searchQuery)) {
    $conditions[] = "(j.title LIKE ? OR j.company LIKE ? OR j.description LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

$whereClause = implode(' AND ', $conditions);
$query = "SELECT j.*, u.name as posted_by_name, u.profile_image 
          FROM jobs j 
          JOIN users u ON j.posted_by = u.id 
          WHERE $whereClause 
          ORDER BY j.created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$jobs = $stmt->get_result();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <!-- Post Job Card -->
            <?php if ($user['role'] !== 'student'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Post a Job
                    </h6>
                    <button type="button" class="btn btn-primary w-100" data-mdb-toggle="modal" data-mdb-target="#postJobModal">
                        <i class="fas fa-briefcase me-2"></i>Post Job
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-filter me-2 text-primary"></i>Filters
                    </h6>
                    
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                   placeholder="Job title, company...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Job Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="full-time" <?php echo $filterType === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="part-time" <?php echo $filterType === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="internship" <?php echo $filterType === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                <option value="contract" <?php echo $filterType === 'contract' ? 'selected' : ''; ?>>Contract</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                        <a href="<?php echo SITE_URL; ?>/user/jobs.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fas fa-briefcase me-2 text-primary"></i>Job Opportunities
                </h4>
                <span class="badge bg-primary"><?php echo $jobs->num_rows; ?> Jobs</span>
            </div>
            
            <?php if ($jobs->num_rows > 0): ?>
                <?php while ($job = $jobs->fetch_assoc()): ?>
                    <div class="card job-card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <div class="job-company">
                                        <i class="fas fa-building me-1"></i>
                                        <?php echo htmlspecialchars($job['company']); ?>
                                    </div>
                                </div>
                                <span class="badge bg-primary"><?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></span>
                            </div>
                            
                            <div class="job-meta">
                                <?php if ($job['location']): ?>
                                    <div class="job-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($job['location']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($job['salary_range']): ?>
                                    <div class="job-meta-item">
                                        <i class="fas fa-dollar-sign"></i>
                                        <?php echo htmlspecialchars($job['salary_range']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="job-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <?php echo timeAgo($job['created_at']); ?>
                                </div>
                            </div>
                            
                            <p class="mb-3"><?php echo nl2br(htmlspecialchars(truncateText($job['description'], 200))); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo SITE_URL . '/uploads/' . $job['profile_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($job['posted_by_name']); ?>" 
                                         class="rounded-circle me-2" 
                                         style="width: 30px; height: 30px; object-fit: cover;">
                                    <small class="text-muted">Posted by <?php echo htmlspecialchars($job['posted_by_name']); ?></small>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm" 
                                            data-mdb-toggle="modal" 
                                            data-mdb-target="#jobDetailsModal<?php echo $job['id']; ?>">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                    <?php if ($job['posted_by'] !== $user['id']): ?>
                                        <a href="<?php echo SITE_URL; ?>/user/messages.php?user=<?php echo $job['posted_by']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i>Contact
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Job Details Modal -->
                    <div class="modal fade" id="jobDetailsModal<?php echo $job['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($job['company']); ?>
                                    </h6>
                                    
                                    <div class="mb-3">
                                        <strong>Job Type:</strong> <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                                    </div>
                                    
                                    <?php if ($job['location']): ?>
                                        <div class="mb-3">
                                            <strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['salary_range']): ?>
                                        <div class="mb-3">
                                            <strong>Salary Range:</strong> <?php echo htmlspecialchars($job['salary_range']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <strong>Description:</strong>
                                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Posted by <strong><?php echo htmlspecialchars($job['posted_by_name']); ?></strong> 
                                        on <?php echo date('F j, Y', strtotime($job['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Close</button>
                                    <?php if ($job['posted_by'] !== $user['id']): ?>
                                        <a href="<?php echo SITE_URL; ?>/user/messages.php?user=<?php echo $job['posted_by']; ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-envelope me-2"></i>Contact Poster
                                        </a>
                                    <?php endif; ?>
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
                    <div class="empty-state-text">Be the first to post a job opportunity!</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Post Job Modal -->
<div class="modal fade" id="postJobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-briefcase me-2 text-primary"></i>Post a Job
                </h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="company" name="company" required>
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="job_type" class="form-label">Job Type</label>
                            <select class="form-select" id="job_type" name="job_type" required>
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="internship">Internship</option>
                                <option value="contract">Contract</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="salary_range" class="form-label">Salary Range (Optional)</label>
                            <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                   placeholder="e.g., $50k - $70k">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Job Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Your job posting will be reviewed by admin before being published.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="post_job" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Post Job
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
