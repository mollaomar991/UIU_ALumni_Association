<?php
$pageTitle = 'Find a Mentor';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Only students can look for mentors
if ($user['role'] !== 'student') {
    redirectWithMessage(SITE_URL . '/user/mentorship_requests.php', 'Alumni can only provide mentorship, not request it.', 'info');
}

// Handle Mentorship Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_mentor'])) {
    $mentorId = (int)$_POST['mentor_id'];
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Check if request already exists
    $checkStmt = $db->prepare("SELECT id FROM mentorship_requests WHERE mentor_id = ? AND mentee_id = ?");
    $checkStmt->bind_param("ii", $mentorId, $user['id']);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows == 0) {
        $stmt = $db->prepare("INSERT INTO mentorship_requests (mentor_id, mentee_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $mentorId, $user['id'], $message);
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/user/mentors.php', 'Mentorship request sent successfully!', 'success');
        }
    } else {
        redirectWithMessage(SITE_URL . '/user/mentors.php', 'Request already pending or processed.', 'warning');
    }
}

// Search and Filter
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';

$query = "SELECT u.*, 
          (SELECT status FROM mentorship_requests WHERE mentor_id = u.id AND mentee_id = ?) as request_status
          FROM users u 
          WHERE u.role = 'alumni' AND u.id != ?";
$params = [$user['id'], $user['id']];
$types = "ii";

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.skills LIKE ? OR u.work_experience LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($department)) {
    $query .= " AND u.department = ?";
    $params[] = $department;
    $types .= "s";
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$mentors = $stmt->get_result();
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4><i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Find a Mentor</h4>
            <p class="text-muted">Connect with experienced alumni for guidance and career support.</p>
        </div>
        <div class="col-md-4 text-md-end">
             <a href="<?php echo SITE_URL; ?>/user/mentorship_requests.php" class="btn btn-outline-primary">
                <i class="fas fa-clipboard-list me-2"></i>My Requests
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" placeholder="Search by name, skills, or company..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-5">
                    <select class="form-select" name="department">
                        <option value="">All Departments</option>
                        <option value="Computer Science & Engineering" <?php echo $department === 'Computer Science & Engineering' ? 'selected' : ''; ?>>CSE</option>
                        <option value="Electrical & Electronic Engineering" <?php echo $department === 'Electrical & Electronic Engineering' ? 'selected' : ''; ?>>EEE</option>
                        <option value="Civil Engineering" <?php echo $department === 'Civil Engineering' ? 'selected' : ''; ?>>Civil</option>
                        <option value="Business Administration" <?php echo $department === 'Business Administration' ? 'selected' : ''; ?>>BBA</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Mentor List -->
    <div class="row g-4">
        <?php if ($mentors->num_rows > 0): ?>
            <?php while ($mentor = $mentors->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center">
                            <?php 
                            $avatarUrl = SITE_URL . '/uploads/' . ($mentor['profile_image'] ?: 'default-avatar.png');
                            if (($mentor['profile_image'] ?: 'default-avatar.png') == 'default-avatar.png') {
                                $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($mentor['name']) . '&size=100&background=FF6622&color=fff';
                            }
                            ?>
                            <img src="<?php echo $avatarUrl; ?>" class="rounded-circle mb-3 border border-primary p-1" width="100" height="100" style="object-fit: cover;">
                            
                            <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($mentor['name']); ?></h5>
                            <p class="text-primary small mb-2"><?php echo htmlspecialchars($mentor['work_experience'] ?: 'Alumni'); ?></p>
                            
                            <div class="mb-3">
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($mentor['batch']); ?></span>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($mentor['department']); ?></span>
                            </div>
                            
                            <?php if ($mentor['skills']): ?>
                                <div class="mb-4">
                                    <?php foreach (array_slice(explode(',', $mentor['skills']), 0, 3) as $skill): ?>
                                        <span class="badge bg-secondary mb-1"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small mb-4">No skills listed yet.</p>
                            <?php endif; ?>
                            
                            <div class="d-grid">
                                <?php if ($mentor['request_status'] === 'pending'): ?>
                                    <button class="btn btn-warning" disabled>
                                        <i class="fas fa-clock me-2"></i>Request Pending
                                    </button>
                                <?php elseif ($mentor['request_status'] === 'accepted'): ?>
                                    <a href="<?php echo SITE_URL; ?>/user/messages.php?user=<?php echo $mentor['id']; ?>" class="btn btn-success">
                                        <i class="fas fa-comment me-2"></i>Message
                                    </a>
                                <?php elseif ($mentor['request_status'] === 'rejected'): ?>
                                    <button class="btn btn-danger" disabled>
                                        <i class="fas fa-times me-2"></i>Request Declined
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#requestModal-<?php echo $mentor['id']; ?>">
                                        <i class="fas fa-user-plus me-2"></i>Request Mentorship
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Request Modal -->
                <div class="modal fade" id="requestModal-<?php echo $mentor['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Request Mentorship</h5>
                                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Send a request to <strong><?php echo htmlspecialchars($mentor['name']); ?></strong>.</p>
                                    <div class="mb-3">
                                        <label class="form-label">Message (Optional)</label>
                                        <textarea class="form-control" name="message" rows="3" placeholder="Explain why you'd like their mentorship..."></textarea>
                                    </div>
                                    <input type="hidden" name="mentor_id" value="<?php echo $mentor['id']; ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                                    <button type="submit" name="request_mentor" class="btn btn-primary">Send Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-search text-muted" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted">No mentors found matching your criteria.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
