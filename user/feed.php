<?php
$pageTitle = 'Feed';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content = sanitizeInput($_POST['content'] ?? '');
    $link = sanitizeInput($_POST['link'] ?? '');
    $visibility = sanitizeInput($_POST['visibility'] ?? 'public');
    
    if (!empty($content)) {
        $image = null;
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['image']);
            if ($uploadResult['success']) {
                $image = $uploadResult['filename'];
            }
        }
        
        $stmt = $db->prepare("INSERT INTO posts (user_id, content, image, link, visibility) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user['id'], $content, $image, $link, $visibility);
        
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/user/feed.php', 'Post created successfully!', 'success');
        }
    }
}

// Get posts
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$query = "SELECT p.*, u.name, u.profile_image, u.batch, u.department,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
          (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
          FROM posts p
          JOIN users u ON p.user_id = u.id
          WHERE p.visibility = 'public' 
          OR (p.visibility = 'batch' AND u.batch = ?)
          OR (p.visibility = 'department' AND u.department = ?)
          ORDER BY p.is_pinned DESC, p.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$stmt->bind_param("issii", $user['id'], $user['batch'], $user['department'], $perPage, $offset);
$stmt->execute();
$posts = $stmt->get_result();
?>

<div class="container py-4">
    <div class="row">
        <!-- Main Feed -->
        <div class="col-lg-8">
            <!-- Create Post Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-pencil-alt me-2 text-primary"></i>Share Something
                    </h5>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <textarea class="form-control" name="content" rows="3" 
                                      placeholder="What's on your mind, <?php echo htmlspecialchars($user['name']); ?>?" 
                                      required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-image me-2"></i>Add Photo
                                    <input type="file" name="image" accept="image/*" class="d-none" onchange="previewImage(this)">
                                </label>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" name="visibility">
                                    <option value="public">Public</option>
                                    <option value="batch">My Batch Only</option>
                                    <option value="department">My Department</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <input type="url" class="form-control" name="link" 
                                   placeholder="Add a link (optional)">
                        </div>
                        
                        <img id="imagePreview" src="" alt="Preview" style="max-width: 200px; display: none;" class="mb-3 rounded">
                        
                        <button type="submit" name="create_post" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Post
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Posts Feed -->
            <div id="posts-container">
                <?php if ($posts->num_rows > 0): ?>
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <div class="post-card" id="post-<?php echo $post['id']; ?>">
                            <!-- Post Header -->
                            <div class="post-header">
                                <img src="<?php echo SITE_URL . '/uploads/' . $post['profile_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($post['name']); ?>" 
                                     class="post-avatar">
                                <div class="post-author">
                                    <div class="post-author-name">
                                        <?php echo htmlspecialchars($post['name']); ?>
                                        <?php if ($post['is_pinned']): ?>
                                            <span class="badge badge-primary ms-2">
                                                <i class="fas fa-thumbtack me-1"></i>Pinned
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-time">
                                        <?php echo htmlspecialchars($post['batch']); ?> | 
                                        <?php echo timeAgo($post['created_at']); ?>
                                    </div>
                                </div>
                                <?php if ($post['user_id'] == $user['id']): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" 
                                                data-mdb-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" 
                                                   onclick="deletePost(<?php echo $post['id']; ?>); return false;">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Post Content -->
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>
                            
                            <!-- Post Image -->
                            <?php if ($post['image']): ?>
                                <img src="<?php echo SITE_URL . '/uploads/' . $post['image']; ?>" 
                                     alt="Post image" class="post-image">
                            <?php endif; ?>
                            
                            <!-- Post Link -->
                            <?php if ($post['link']): ?>
                                <a href="<?php echo htmlspecialchars($post['link']); ?>" 
                                   target="_blank" class="btn btn-outline-primary btn-sm mb-3">
                                    <i class="fas fa-external-link-alt me-1"></i>View Link
                                </a>
                            <?php endif; ?>
                            
                            <!-- Post Actions -->
                            <div class="post-actions">
                                <button class="post-action-btn <?php echo $post['user_liked'] ? 'active' : ''; ?>" 
                                        data-post-id="<?php echo $post['id']; ?>" 
                                        onclick="likePost(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-thumbs-up"></i>
                                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                                </button>
                                <button class="post-action-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo $post['comment_count']; ?></span>
                                </button>
                                <button class="post-action-btn">
                                    <i class="fas fa-share"></i>
                                    <span>Share</span>
                                </button>
                            </div>
                            
                            <!-- Comments Section -->
                            <div id="comments-<?php echo $post['id']; ?>" style="display: none;" class="mt-3">
                                <?php
                                // Get comments for this post
                                $stmt = $db->prepare("SELECT c.*, u.name, u.profile_image 
                                                     FROM comments c 
                                                     JOIN users u ON c.user_id = u.id 
                                                     WHERE c.post_id = ? 
                                                     ORDER BY c.created_at ASC");
                                $stmt->bind_param("i", $post['id']);
                                $stmt->execute();
                                $comments = $stmt->get_result();
                                
                                if ($comments->num_rows > 0):
                                    while ($comment = $comments->fetch_assoc()):
                                ?>
                                    <div class="comment-item">
                                        <span class="comment-author"><?php echo htmlspecialchars($comment['name']); ?>:</span>
                                        <?php echo htmlspecialchars($comment['comment_text']); ?>
                                        <div class="small text-muted"><?php echo timeAgo($comment['created_at']); ?></div>
                                    </div>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                                
                                <!-- Add Comment Form -->
                                <form method="POST" action="<?php echo SITE_URL; ?>/api/add_comment.php" class="mt-3">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="comment_text" 
                                               placeholder="Write a comment..." required>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <div class="empty-state-title">No posts yet</div>
                        <div class="empty-state-text">Be the first to share something!</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Load More -->
            <div class="text-center mt-4">
                <button id="loadMoreBtn" class="btn btn-outline-primary" onclick="loadMorePosts()">
                    <i class="fas fa-sync-alt me-2"></i>Load More
                </button>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sidebar">

                
                <!-- Suggested Groups -->
                <div class="sidebar-widget">
                    <div class="sidebar-widget-title">
                        <i class="fas fa-users me-2"></i>Your Groups
                    </div>
                    <a href="<?php echo SITE_URL; ?>/user/groups.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-plus me-2"></i>Explore Groups
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleComments(postId) {
    const commentsDiv = document.getElementById('comments-' + postId);
    if (commentsDiv.style.display === 'none') {
        commentsDiv.style.display = 'block';
    } else {
        commentsDiv.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
