<?php
$pageTitle = 'Memory Wall';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Gallery is for alumni only
if ($user['role'] !== 'alumni') {
    redirectWithMessage(SITE_URL . '/user/dashboard.php', 'The Memory Wall is exclusively for alumni to share their memories.', 'info');
}

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $caption = sanitizeInput($_POST['caption'] ?? '');
    $tags = sanitizeInput($_POST['tags'] ?? '');
    $eventType = sanitizeInput($_POST['event_type'] ?? 'other');
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['image'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1024 * 1024); // 5MB max
        
        if ($upload['success']) {
            $stmt = $db->prepare("INSERT INTO gallery (user_id, image_url, caption, tags, batch, department, event_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $user['id'], $upload['filename'], $caption, $tags, $user['batch'], $user['department'], $eventType);
            
            if ($stmt->execute()) {
                redirectWithMessage(SITE_URL . '/user/gallery.php', 'Photo uploaded successfully!', 'success');
            } else {
                redirectWithMessage(SITE_URL . '/user/gallery.php', 'Failed to upload photo.', 'error');
            }
        } else {
            redirectWithMessage(SITE_URL . '/user/gallery.php', $upload['message'], 'error');
        }
    } else {
        redirectWithMessage(SITE_URL . '/user/gallery.php', 'Please select an image.', 'warning');
    }
}

// Handle Like/Unlike
if (isset($_GET['like'])) {
    $galleryId = (int)$_GET['like'];
    
    // Check if already liked
    $stmt = $db->prepare("SELECT id FROM gallery_likes WHERE gallery_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $galleryId, $user['id']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    
    if ($exists) {
        // Unlike
        $stmt = $db->prepare("DELETE FROM gallery_likes WHERE gallery_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $galleryId, $user['id']);
        $stmt->execute();
        
        $stmt = $db->prepare("UPDATE gallery SET likes_count = likes_count - 1 WHERE id = ?");
        $stmt->bind_param("i", $galleryId);
        $stmt->execute();
    } else {
        // Like
        $stmt = $db->prepare("INSERT INTO gallery_likes (gallery_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $galleryId, $user['id']);
        $stmt->execute();
        
        $stmt = $db->prepare("UPDATE gallery SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $galleryId);
        $stmt->execute();
    }
    
    header('Location: ' . SITE_URL . '/user/gallery.php');
    exit;
}

// Filters
$filterType = $_GET['type'] ?? 'all';
$filterBatch = $_GET['batch'] ?? 'all';

// Build query
$query = "SELECT g.*, u.name, u.profile_image,
          (SELECT COUNT(*) FROM gallery_likes WHERE gallery_id = g.id AND user_id = ?) as user_liked
          FROM gallery g 
          JOIN users u ON g.user_id = u.id 
          WHERE 1=1";

$params = [$user['id']];
$types = "i";

if ($filterType !== 'all') {
    $query .= " AND g.event_type = ?";
    $params[] = $filterType;
    $types .= "s";
}

if ($filterBatch !== 'all') {
    $query .= " AND g.batch = ?";
    $params[] = $filterBatch;
    $types .= "s";
}

$query .= " ORDER BY g.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$photos = $stmt->get_result();

// Get unique batches for filter
$batches = $db->query("SELECT DISTINCT batch FROM gallery WHERE batch IS NOT NULL ORDER BY batch");
?>

<style>
.masonry-grid {
    column-count: 3;
    column-gap: 1rem;
}

@media (max-width: 992px) {
    .masonry-grid {
        column-count: 2;
    }
}

@media (max-width: 576px) {
    .masonry-grid {
        column-count: 1;
    }
}

.masonry-item {
    break-inside: avoid;
    margin-bottom: 1rem;
}

.gallery-card {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    transition: transform 0.3s ease;
}

.gallery-card:hover {
    transform: translateY(-5px);
}

.gallery-card img {
    width: 100%;
    display: block;
    border-radius: 12px;
}

.gallery-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    padding: 1rem;
    color: white;
}

.like-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.like-btn:hover {
    background: white;
    transform: scale(1.1);
}

.like-btn.liked {
    background: #ff6b6b;
    color: white;
}
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-images me-2 text-primary"></i>Memory Wall</h2>
        <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#uploadModal">
            <i class="fas fa-upload me-2"></i>Upload Photo
        </button>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Category</label>
                    <select class="form-select" onchange="window.location.href='?type=' + this.value + '&batch=<?php echo $filterBatch; ?>'">
                        <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="reunion" <?php echo $filterType === 'reunion' ? 'selected' : ''; ?>>Reunions</option>
                        <option value="campus" <?php echo $filterType === 'campus' ? 'selected' : ''; ?>>Campus Life</option>
                        <option value="event" <?php echo $filterType === 'event' ? 'selected' : ''; ?>>Events</option>
                        <option value="achievement" <?php echo $filterType === 'achievement' ? 'selected' : ''; ?>>Achievements</option>
                        <option value="other" <?php echo $filterType === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Batch</label>
                    <select class="form-select" onchange="window.location.href='?type=<?php echo $filterType; ?>&batch=' + this.value">
                        <option value="all" <?php echo $filterBatch === 'all' ? 'selected' : ''; ?>>All Batches</option>
                        <?php while ($b = $batches->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($b['batch']); ?>" <?php echo $filterBatch === $b['batch'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['batch']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gallery Grid -->
    <?php if ($photos->num_rows > 0): ?>
        <div class="masonry-grid">
            <?php while ($photo = $photos->fetch_assoc()): ?>
                <div class="masonry-item">
                    <div class="gallery-card">
                        <img src="<?php echo SITE_URL . '/uploads/' . $photo['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($photo['caption']); ?>"
                             loading="lazy">
                        
                        <a href="?like=<?php echo $photo['id']; ?>" 
                           class="like-btn <?php echo $photo['user_liked'] > 0 ? 'liked' : ''; ?>">
                            <i class="fas fa-heart"></i>
                        </a>
                        
                        <div class="gallery-overlay">
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?php echo SITE_URL . '/uploads/' . $photo['profile_image']; ?>" 
                                     class="rounded-circle me-2" 
                                     style="width: 30px; height: 30px; object-fit: cover;">
                                <div>
                                    <div class="small fw-bold"><?php echo htmlspecialchars($photo['name']); ?></div>
                                    <div class="small opacity-75"><?php echo htmlspecialchars($photo['batch']); ?></div>
                                </div>
                            </div>
                            
                            <?php if ($photo['caption']): ?>
                                <p class="small mb-2"><?php echo htmlspecialchars($photo['caption']); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="badge bg-light text-dark">
                                        <?php echo ucfirst($photo['event_type']); ?>
                                    </span>
                                    <?php if ($photo['tags']): ?>
                                        <span class="small opacity-75"><?php echo htmlspecialchars($photo['tags']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="small">
                                    <i class="fas fa-heart me-1"></i><?php echo $photo['likes_count']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-images fa-3x text-muted mb-3"></i>
            <p class="text-muted">No photos yet. Be the first to share a memory!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Photo</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="image" accept="image/*" required>
                        <small class="text-muted">Max size: 5MB. Formats: JPG, PNG, GIF, WebP</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Caption</label>
                        <textarea class="form-control" name="caption" rows="3" placeholder="Share the story behind this photo..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="event_type" required>
                            <option value="campus">Campus Life</option>
                            <option value="reunion">Reunion</option>
                            <option value="event">Event</option>
                            <option value="achievement">Achievement</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tags (optional)</label>
                        <input type="text" class="form-control" name="tags" placeholder="e.g., #Graduation2024 #CSE">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_image" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
