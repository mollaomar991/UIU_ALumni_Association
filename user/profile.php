<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();
$errors = [];
$success = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $education = sanitizeInput($_POST['education'] ?? '');
    $work_experience = sanitizeInput($_POST['work_experience'] ?? '');
    $skills = sanitizeInput($_POST['skills'] ?? '');
    $linkedin = sanitizeInput($_POST['linkedin'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Check if email is already taken by another user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already taken";
    }
    
    // Handle profile image upload
    $profileImage = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['profile_image']);
        if ($uploadResult['success']) {
            $profileImage = $uploadResult['filename'];
        }
    }
    
    // Handle password change
    if (!empty($_POST['new_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!verifyPassword($currentPassword, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        } else {
            $hashedPassword = hashPassword($newPassword);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $user['id']);
            $stmt->execute();
        }
    }
    
    // Update profile
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, 
                             bio = ?, education = ?, work_experience = ?, skills = ?, linkedin = ?, profile_image = ? 
                             WHERE id = ?");
        $stmt->bind_param("ssssssssssi", $name, $email, $phone, $location, $bio, 
                         $education, $work_experience, $skills, $linkedin, $profileImage, $user['id']);
        
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/user/profile.php', 'Profile updated successfully!', 'success');
        } else {
            $errors[] = "Failed to update profile";
        }
    }
}

// Refresh user data
$user = getCurrentUser();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <?php 
                    $avatarUrl = SITE_URL . '/uploads/' . $user['profile_image'];
                    if ($user['profile_image'] == 'default-avatar.png' || !file_exists(UPLOAD_PATH . $user['profile_image'])) {
                        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&size=200&background=FF6622&color=fff';
                    }
                    ?>
                    <img src="<?php echo $avatarUrl; ?>" 
                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                         class="profile-avatar" id="currentProfileImage"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&size=200&background=FF6622&color=fff'">
                    <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="profile-info">
                        <?php echo htmlspecialchars($user['batch']); ?> | <?php echo htmlspecialchars($user['department']); ?>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <h5 class="mb-4">
                        <i class="fas fa-user-edit me-2 text-primary"></i>Edit Profile
                    </h5>
                    
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
                        <!-- Basic Information -->
                        <h6 class="text-primary mb-3">Basic Information</h6>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">
                                <i class="fas fa-image me-2"></i>Profile Picture
                            </label>
                            <div class="text-center mb-3">
                                <img id="imagePreview" 
                                     src="<?php echo $avatarUrl; ?>" 
                                     alt="Preview" 
                                     style="max-width: 200px; max-height: 200px; border-radius: 50%; border: 3px solid <?php echo PRIMARY_COLOR; ?>;"
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&size=200&background=FF6622&color=fff'">
                            </div>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(event)">
                            <small class="text-muted">Supported: JPG, PNG, GIF (Max 5MB). Leave empty to keep current image.</small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Professional Information -->
                        <h6 class="text-primary mb-3">Professional Information</h6>
                        
                        <div class="mb-3">
                            <label for="education" class="form-label">Education</label>
                            <textarea class="form-control" id="education" name="education" rows="3"><?php echo htmlspecialchars($user['education'] ?? ''); ?></textarea>
                            <small class="text-muted">e.g., BSc in CSE from UIU, MSc from MIT</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="work_experience" class="form-label">Work Experience</label>
                            <textarea class="form-control" id="work_experience" name="work_experience" rows="3"><?php echo htmlspecialchars($user['work_experience'] ?? ''); ?></textarea>
                            <small class="text-muted">e.g., Software Engineer at Google</small>
                        </div>

                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills & Expertise</label>
                            <input type="text" class="form-control" id="skills" name="skills" 
                                   value="<?php echo htmlspecialchars($user['skills'] ?? ''); ?>" 
                                   placeholder="e.g., PHP, JavaScript, Career Counseling, Resume Review">
                            <small class="text-muted">Comma-separated list of skills you can mentor on.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="linkedin" class="form-label">LinkedIn Profile</label>
                            <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                   value="<?php echo htmlspecialchars($user['linkedin'] ?? ''); ?>" 
                                   placeholder="https://linkedin.com/in/yourprofile">
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Change Password -->
                        <h6 class="text-primary mb-3">Change Password</h6>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        // Check file size (5MB)
        if (file.size > 5242880) {
            alert('File size must be less than 5MB');
            event.target.value = '';
            return;
        }
        
        // Check file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Only JPG, PNG, and GIF images are allowed');
            event.target.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
