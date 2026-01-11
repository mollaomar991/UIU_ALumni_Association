<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Handle batch creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_batch'])) {
    $batchName = sanitizeInput($_POST['batch_name'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (!empty($batchName) && !empty($department)) {
        $stmt = $db->prepare("INSERT INTO batches (batch_name, department, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $batchName, $department, $description);
        
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/admin/settings.php', 'Batch added successfully!', 'success');
        }
    }
}

// Handle batch deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
    $batchId = (int)$_POST['batch_id'];
    $stmt = $db->prepare("DELETE FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batchId);
    if ($stmt->execute()) {
        redirectWithMessage(SITE_URL . '/admin/settings.php', 'Batch deleted successfully!', 'success');
    }
}

// Get batches
$batches = $db->query("SELECT * FROM batches ORDER BY department, batch_name");

// Get departments
$departments = $db->query("SELECT DISTINCT department FROM batches ORDER BY department");
?>

<div class="container-fluid">
    <h4 class="mb-4">
        <i class="fas fa-cog me-2 text-primary"></i>System Settings
    </h4>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Batch Management -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-graduation-cap me-2 text-primary"></i>Batch Management
                        </h5>
                        <button type="button" class="btn btn-primary btn-sm" data-mdb-toggle="modal" data-mdb-target="#addBatchModal">
                            <i class="fas fa-plus me-1"></i>Add Batch
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Batch Name</th>
                                    <th>Department</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $batches->data_seek(0);
                                while ($batch = $batches->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo $batch['id']; ?></td>
                                        <td><?php echo htmlspecialchars($batch['batch_name']); ?></td>
                                        <td><?php echo htmlspecialchars($batch['department']); ?></td>
                                        <td><?php echo htmlspecialchars($batch['description'] ?? '-'); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                                <button type="submit" name="delete_batch" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Delete this batch?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- System Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-info-circle me-2 text-primary"></i>System Information
                    </h5>
                    
                    <div class="mb-3">
                        <small class="text-muted">Platform Name</small>
                        <div class="fw-bold"><?php echo SITE_NAME; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Primary Color</small>
                        <div>
                            <span class="badge" style="background-color: <?php echo PRIMARY_COLOR; ?>;">
                                <?php echo PRIMARY_COLOR; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Database</small>
                        <div class="fw-bold"><?php echo DB_NAME; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">PHP Version</small>
                        <div class="fw-bold"><?php echo phpversion(); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>Quick Stats
                    </h5>
                    
                    <?php
                    $stats = [
                        'Total Alumni' => $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
                        'Active Alumni' => $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'],
                        'Total Posts' => $db->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'],
                        'Total Jobs' => $db->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'],
                        'Total Batches' => $db->query("SELECT COUNT(*) as count FROM batches")->fetch_assoc()['count'],
                    ];
                    
                    foreach ($stats as $label => $value):
                    ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><?php echo $label; ?></span>
                            <span class="fw-bold text-primary"><?php echo $value; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Batch Modal -->
<div class="modal fade" id="addBatchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2 text-primary"></i>Add New Batch
                </h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="batch_name" class="form-label">Batch Name</label>
                        <input type="text" class="form-control" id="batch_name" name="batch_name" 
                               placeholder="e.g., CSE 201" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" 
                               placeholder="e.g., Computer Science & Engineering" required
                               list="departmentList">
                        <datalist id="departmentList">
                            <?php 
                            $departments->data_seek(0);
                            while ($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="2" 
                                  placeholder="e.g., CSE Spring 2020 Batch"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_batch" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
