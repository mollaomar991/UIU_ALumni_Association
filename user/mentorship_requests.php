<?php
$pageTitle = 'Mentorship Requests';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Handle Status Updates (Accept/Reject for Mentors)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $requestId = (int)$_POST['request_id'];
    $status = $_POST['status']; // 'accepted' or 'rejected'
    
    // Security check: Ensure this request belongs to the logged-in mentor
    $checkStmt = $db->prepare("SELECT id FROM mentorship_requests WHERE id = ? AND mentor_id = ?");
    $checkStmt->bind_param("ii", $requestId, $user['id']);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0 && in_array($status, ['accepted', 'rejected'])) {
        $updateStmt = $db->prepare("UPDATE mentorship_requests SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $status, $requestId);
        
        if ($updateStmt->execute()) {
            $msg = ($status === 'accepted') ? 'Request accepted!' : 'Request rejected.';
            $type = ($status === 'accepted') ? 'success' : 'info';
            redirectWithMessage(SITE_URL . '/user/mentorship_requests.php', $msg, $type);
        }
    }
}

// Fetch Incoming Requests (for Mentors)
$incomingQuery = "SELECT r.*, u.name, u.profile_image, u.department, u.batch 
                  FROM mentorship_requests r 
                  JOIN users u ON r.mentee_id = u.id 
                  WHERE r.mentor_id = ? 
                  ORDER BY r.created_at DESC";
$inStmt = $db->prepare($incomingQuery);
$inStmt->bind_param("i", $user['id']);
$inStmt->execute();
$incomingRequests = $inStmt->get_result();

// Fetch Outgoing Requests (for Mentees)
$outgoingQuery = "SELECT r.*, u.name, u.profile_image, u.department, u.batch 
                  FROM mentorship_requests r 
                  JOIN users u ON r.mentor_id = u.id 
                  WHERE r.mentee_id = ? 
                  ORDER BY r.created_at DESC";
$outStmt = $db->prepare($outgoingQuery);
$outStmt->bind_param("i", $user['id']);
$outStmt->execute();
$outgoingRequests = $outStmt->get_result();
?>

<div class="container py-4">
    <h4 class="mb-4"><i class="fas fa-clipboard-check me-2 text-primary"></i>My Mentorships</h4>
    
    <div class="row">
        <!-- Incoming Requests (If User is Alumni/Mentor) -->
        <?php if ($user['role'] !== 'student'): ?>
            <div class="col-12 mb-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-primary">Incoming Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($incomingRequests->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Batch/Dept</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($req = $incomingRequests->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo SITE_URL . '/uploads/' . ($req['profile_image'] ?: 'default-avatar.png'); ?>" 
                                                             class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                                        <div class="fw-bold"><?php echo htmlspecialchars($req['name']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small"><?php echo htmlspecialchars($req['department']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($req['batch']); ?></div>
                                                </td>
                                                <td>
                                                    <?php if ($req['message']): ?>
                                                        <button class="btn btn-sm btn-link" data-mdb-toggle="tooltip" title="<?php echo htmlspecialchars($req['message']); ?>">
                                                            View Message
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($req['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif ($req['status'] === 'accepted'): ?>
                                                        <span class="badge bg-success">Accepted</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($req['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-flex gap-2">
                                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <button type="submit" name="status" value="accepted" class="btn btn-success btn-sm btn-floating">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm btn-floating">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <!-- Send Message Button if Accepted -->
                                                        <?php if ($req['status'] === 'accepted'): ?>
                                                            <a href="<?php echo SITE_URL; ?>/user/messages.php?user=<?php echo $req['mentee_id']; ?>" class="btn btn-primary btn-sm btn-floating">
                                                                <i class="fas fa-comment"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Closed</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No incoming requests.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Outgoing Requests (My Requests - Students Only) -->
        <?php if ($user['role'] === 'student' || $outgoingRequests->num_rows > 0): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-secondary">My Requests</h5>
                </div>
                <div class="card-body">
                    <?php if ($outgoingRequests->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Mentor</th>
                                        <th>Expertise</th>
                                        <th>Sent On</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($req = $outgoingRequests->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo SITE_URL . '/uploads/' . ($req['profile_image'] ?: 'default-avatar.png'); ?>" 
                                                         class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($req['name']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small"><?php echo htmlspecialchars($req['department']); ?></div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                            <td>
                                                <?php if ($req['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($req['status'] === 'accepted'): ?>
                                                    <span class="badge bg-success">Accepted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                 <?php if ($req['status'] === 'accepted'): ?>
                                                    <a href="<?php echo SITE_URL; ?>/user/messages.php?user=<?php echo $req['mentor_id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-comment me-1"></i>Message
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">You haven't sent any mentorship requests yet.</p>
                            <a href="<?php echo SITE_URL; ?>/user/mentors.php" class="btn btn-primary">
                                Find a Mentor
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
