<?php
$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/user/dashboard.php');
}

$errors = [];
$success = false;

// Department Codes Mapping
$deptCodes = [
    'Computer Science & Engineering' => 'CSE',
    'Electrical & Electronic Engineering' => 'EEE',
    'Business Administration' => 'BBA',
    'Civil Engineering' => 'CE',
    'Economics' => 'ECO'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'student'); // Default to student
    $department = sanitizeInput($_POST['department'] ?? '');
    $batch = sanitizeInput($_POST['batch_number'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    
    // Batch is required only for Alumni
    if ($role === 'alumni' && empty($batch)) {
        $errors[] = "Batch is required for Alumni";
    }
    
    // File Upload Validation
    if (!isset($_FILES['id_card']) || $_FILES['id_card']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "ID Card image is required";
    }
    
    // Check if email exists
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    // Process Registration
    if (empty($errors)) {
        // Upload ID Card
        $uploadResult = uploadFile($_FILES['id_card'], ['jpg', 'jpeg', 'png'], 5 * 1024 * 1024); // 5MB max
        
        if ($uploadResult['success']) {
            $idCardImage = $uploadResult['filename'];
            $hashedPassword = hashPassword($password);
            
            // Construct Batch Name (e.g., "CSE 231")
            $finalBatch = null;
            // Now we save batch for both students and alumni
            if (!empty($_POST['batch_number'])) {
                $batchNum = sanitizeInput($_POST['batch_number'] ?? '');
                $deptCode = $deptCodes[$department] ?? 'Unknown';
                $finalBatch = $deptCode . ' ' . $batchNum;
            }
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, department, batch, id_card_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $name, $email, $hashedPassword, $role, $department, $finalBatch, $idCardImage);
            
            if ($stmt->execute()) {
                $success = true;
                redirectWithMessage(SITE_URL . '/auth/login.php', 'Registration successful! Please wait for admin approval.', 'success');
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        } else {
            $errors[] = "ID Card Upload Failed: " . $uploadResult['message'];
        }
    }
}

// Get Departments list from DB for the dropdown
$db = getDB();
$deptResult = $db->query("SELECT DISTINCT department FROM batches ORDER BY department");
$departmentsList = [];
while ($row = $deptResult->fetch_assoc()) {
    $departmentsList[] = $row['department'];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">Join UIU Alumni Connect</h3>
                        <p class="text-muted">Register as a Student or Alumni</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Role Selection -->
                        <div class="mb-4 text-center">
                            <label class="form-label d-block fw-bold mb-3">I am a:</label>
                            <div class="btn-group w-100" role="group" aria-label="Role selection">
                                <input type="radio" class="btn-check" name="role" id="role_student" value="student" 
                                    <?php echo (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : ''; ?> 
                                    onchange="toggleFields()">
                                <label class="btn btn-outline-primary" for="role_student">
                                    <i class="fas fa-user-graduate me-2"></i>Current Student
                                </label>

                                <input type="radio" class="btn-check" name="role" id="role_alumni" value="alumni" 
                                    <?php echo (isset($_POST['role']) && $_POST['role'] === 'alumni') ? 'checked' : ''; ?> 
                                    onchange="toggleFields()">
                                <label class="btn btn-outline-primary" for="role_alumni">
                                    <i class="fas fa-graduation-cap me-2"></i>Alumni
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department" required onchange="updateBatchPrefix()">
                                    <option value="">Select Department</option>
                                    <?php
                                    foreach ($departmentsList as $deptName) {
                                        $selected = (isset($_POST['department']) && $_POST['department'] === $deptName) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($deptName) . "\" $selected>" 
                                             . htmlspecialchars($deptName) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6" id="batch_container" style="display: none;">
                                <label for="batch_number" class="form-label">Batch</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold" id="batch_prefix">CODE</span>
                                    <input type="number" class="form-control" id="batch_number" name="batch_number" 
                                           value="<?php echo htmlspecialchars($_POST['batch_number'] ?? ''); ?>"
                                           placeholder="e.g. 231" min="100" max="999">
                                </div>
                            </div>
                        </div>

                         <div class="mb-4">
                            <label for="id_card" class="form-label" id="id_card_label">Upload Student ID Card</label>
                            <input type="file" class="form-control" id="id_card" name="id_card" accept="image/*" required>
                            <div class="form-text">Please upload a clear image of your ID card for verification.</div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Min 6 chars</small>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-primary fw-bold">Login</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass PHP data to JS
const deptCodes = <?php echo json_encode($deptCodes); ?>;

function toggleFields() {
    const roleAlumni = document.getElementById('role_alumni');
    const batchContainer = document.getElementById('batch_container');
    const idCardLabel = document.getElementById('id_card_label');
    const batchInput = document.getElementById('batch_number');

    console.log('Toggling fields. Alumni checked:', roleAlumni.checked);

    if (roleAlumni.checked) {
        batchContainer.style.display = 'block';
        batchInput.required = true;
        idCardLabel.textContent = 'Upload Alumni Card / ID';
    } else {
        batchContainer.style.display = 'none';
        batchInput.required = false;
        idCardLabel.textContent = 'Upload Student ID Card';
    }
}

function updateBatchPrefix() {
    const departmentSelect = document.getElementById('department');
    const batchPrefixSpan = document.getElementById('batch_prefix');
    const selectedDept = departmentSelect.value;
    
    if (selectedDept && deptCodes[selectedDept]) {
        batchPrefixSpan.textContent = deptCodes[selectedDept];
    } else {
        batchPrefixSpan.textContent = 'CODE';
    }
}

// Run on page load
document.addEventListener('DOMContentLoaded', () => {
    // Attach event listeners
    document.querySelectorAll('input[name="role"]').forEach(radio => {
        radio.addEventListener('change', toggleFields);
    });
    
    toggleFields(); // Initial state
    updateBatchPrefix(); // Initial state
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
