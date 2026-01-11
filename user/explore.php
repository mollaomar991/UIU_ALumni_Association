<?php
$pageTitle = 'Explore Alumni';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Get search filters
$searchQuery = $_GET['search'] ?? '';
$filterBatch = $_GET['batch'] ?? '';
$filterDepartment = $_GET['department'] ?? '';

// Build query
$conditions = ["u.status = 'active'"];
$params = [];
$types = '';

if (!empty($searchQuery)) {
    $conditions[] = "(u.name LIKE ? OR u.education LIKE ? OR u.work_experience LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!empty($filterBatch)) {
    $conditions[] = "u.batch = ?";
    $params[] = $filterBatch;
    $types .= 's';
}

if (!empty($filterDepartment)) {
    $conditions[] = "u.department = ?";
    $params[] = $filterDepartment;
    $types .= 's';
}

$whereClause = implode(' AND ', $conditions);
$query = "SELECT * FROM users u WHERE $whereClause ORDER BY u.name ASC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$alumni = $stmt->get_result();

// Get all batches and departments from USERS table (so we see batches that actually exist among users)
$batchesResult = $db->query("SELECT DISTINCT batch as batch_name, department FROM users WHERE batch IS NOT NULL AND batch != '' AND status = 'active' ORDER BY batch");
$batchesData = [];
while ($row = $batchesResult->fetch_assoc()) {
    $batchesData[] = $row;
}
$departments = $db->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' AND status = 'active' ORDER BY department");
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <!-- Filters Sidebar -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-filter me-2 text-primary"></i>Filters
                    </h5>
                    
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                   placeholder="Name, company...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department" onchange="filterBatches()">
                                <option value="">All Departments</option>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>"
                                            <?php echo $filterDepartment === $dept['department'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="batch" class="form-label">Batch</label>
                            <select class="form-select" id="batch" name="batch">
                                <option value="">All Batches</option>
                                <!-- Populated by JS -->
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                        <a href="<?php echo SITE_URL; ?>/user/explore.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fas fa-users me-2 text-primary"></i>Alumni Network
                </h4>
                <span class="badge bg-primary"><?php echo $alumni->num_rows; ?> Alumni</span>
            </div>
            
            <?php if ($alumni->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($person = $alumni->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card alumni-card h-100">
                                <div class="card-body">
                                    <img src="<?php echo SITE_URL . '/uploads/' . $person['profile_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($person['name']); ?>" 
                                         class="alumni-avatar">
                                    
                                    <div class="alumni-name"><?php echo htmlspecialchars($person['name']); ?></div>
                                    
                                    <div class="alumni-details">
                                        <div class="mb-2">
                                            <i class="fas fa-graduation-cap me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($person['batch']); ?>
                                        </div>
                                        <div class="mb-2">
                                            <i class="fas fa-building me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($person['department']); ?>
                                        </div>
                                        
                                        <?php if ($person['work_experience']): ?>
                                            <div class="text-muted small mb-3">
                                                <i class="fas fa-briefcase me-1"></i>
                                                <?php echo htmlspecialchars(truncateText($person['work_experience'], 60)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($person['location']): ?>
                                            <div class="text-muted small mb-3">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($person['location']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex gap-2">
                                            <?php if ($person['id'] !== $user['id']): ?>
                                                <a href="<?php echo SITE_URL; ?>/user/messages.php?user=<?php echo $person['id']; ?>" 
                                                   class="btn btn-primary btn-sm flex-grow-1">
                                                    <i class="fas fa-envelope me-1"></i>Message
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($person['linkedin']): ?>
                                                <a href="<?php echo htmlspecialchars($person['linkedin']); ?>" 
                                                   target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fab fa-linkedin"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="empty-state-title">No alumni found</div>
                    <div class="empty-state-text">Try adjusting your search filters</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const batchesData = <?php echo json_encode($batchesData); ?>;
const currentBatch = "<?php echo htmlspecialchars($filterBatch); ?>";

function filterBatches() {
    const departmentSelect = document.getElementById('department');
    const batchSelect = document.getElementById('batch');
    const selectedDept = departmentSelect.value;
    
    // Clear current options except "All Batches"
    batchSelect.length = 1;

    // Filter and populate
    batchesData.forEach(item => {
        if (!selectedDept || item.department === selectedDept) {
            const option = document.createElement('option');
            option.value = item.batch_name;
            option.textContent = item.batch_name;
            if (item.batch_name === currentBatch) {
                option.selected = true;
            }
            batchSelect.appendChild(option);
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', filterBatches);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
