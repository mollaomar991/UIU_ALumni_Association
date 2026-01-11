<?php
$pageTitle = 'Manage Fundraisers';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Handle Create Fundraiser
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_fundraiser'])) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $goal = (float)$_POST['goal_amount'];
    $endDate = sanitizeInput($_POST['end_date']);
    
    // Handle Image Upload
    $image = 'default-fundraiser.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['image']);
        if ($upload['success']) {
            $image = $upload['filename'];
        }
    }
    
    $stmt = $db->prepare("INSERT INTO fundraisers (title, description, goal_amount, image_url, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssi", $title, $description, $goal, $image, $endDate, $_SESSION['admin_id']);
    
    if ($stmt->execute()) {
        redirectWithMessage(SITE_URL . '/admin/fundraisers.php', 'Campaign created successfully!', 'success');
    } else {
        redirectWithMessage(SITE_URL . '/admin/fundraisers.php', 'Failed to create campaign.', 'error');
    }
}

// Handle Delete Fundraiser
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM fundraisers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        redirectWithMessage(SITE_URL . '/admin/fundraisers.php', 'Campaign deleted.', 'success');
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['toggle_status'] === 'active' ? 'completed' : 'active';
    
    $stmt = $db->prepare("UPDATE fundraisers SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        redirectWithMessage(SITE_URL . '/admin/fundraisers.php', 'Campaign status updated.', 'success');
    }
}

// Fetch Fundraisers
$query = "SELECT f.*, a.username as creator_name 
          FROM fundraisers f 
          JOIN admin a ON f.created_by = a.id 
          ORDER BY f.created_at DESC";
$fundraisers = $db->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-hand-holding-heart me-2 text-primary"></i>Manage Fundraisers</h4>
        <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#createModal">
            <i class="fas fa-plus me-2"></i>New Campaign
        </button>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Campaign</th>
                            <th>Goal / Raised</th>
                            <th>Status</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fundraisers->num_rows > 0): ?>
                            <?php while ($row = $fundraisers->fetch_assoc()): ?>
                                <?php 
                                    $percent = ($row['current_amount'] / $row['goal_amount']) * 100;
                                    $percent = min(100, $percent); 
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo SITE_URL . '/uploads/' . $row['image_url']; ?>" 
                                                 class="rounded me-3" width="60" height="40" style="object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></div>
                                                <small class="text-muted">By <?php echo htmlspecialchars($row['creator_name']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="min-width: 200px;">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span>৳<?php echo number_format($row['current_amount']); ?></span>
                                            <span class="text-muted">of ৳<?php echo number_format($row['goal_amount']); ?></span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                    </td>
                                    <td>
                                        <a href="?id=<?php echo $row['id']; ?>&toggle_status=<?php echo $row['status']; ?>" 
                                           class="btn btn-sm btn-<?php echo $row['status'] === 'active' ? 'warning' : 'success'; ?> btn-floating"
                                           data-mdb-toggle="tooltip" title="<?php echo $row['status'] === 'active' ? 'Close Campaign' : 'Activate Campaign'; ?>">
                                            <i class="fas fa-<?php echo $row['status'] === 'active' ? 'stop' : 'play'; ?>"></i>
                                        </a>
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger btn-floating" 
                                           onclick="return confirm('Are you sure? This will delete all donation records linked to this campaign!');"
                                           data-mdb-toggle="tooltip" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No fundraising campaigns found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Campaign</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Goal Amount (BDT)</label>
                            <input type="number" class="form-control" name="goal_amount" min="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_fundraiser" class="btn btn-primary">Launch Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
