<?php
$pageTitle = 'Post Moderation';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_post'])) {
        $postId = (int)$_POST['post_id'];
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param("i", $postId);
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/admin/posts.php', 'Post deleted successfully!', 'success');
        }
    } elseif (isset($_POST['pin_post'])) {
        $postId = (int)$_POST['post_id'];
        $stmt = $db->prepare("UPDATE posts SET is_pinned = NOT is_pinned WHERE id = ?");
        $stmt->bind_param("i", $postId);
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/admin/posts.php', 'Post updated successfully!', 'success');
        }
    }
}

// Get posts
$query = "SELECT p.*, u.name, u.profile_image, u.batch,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
          (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.is_pinned DESC, p.created_at DESC";
$posts = $db->query($query);
?>

<div class="container-fluid">
    <h4 class="mb-4">
        <i class="fas fa-newspaper me-2 text-primary"></i>Post Moderation
    </h4>
    
    <div class="row">
        <?php if ($posts->num_rows > 0): ?>
            <?php while ($post = $posts->fetch_assoc()): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo SITE_URL . '/uploads/' . $post['profile_image']; ?>" 
                                         class="rounded-circle me-2" 
                                         style="width: 40px; height: 40px; object-fit: cover;"
                                         alt="<?php echo htmlspecialchars($post['name']); ?>">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($post['name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($post['batch']); ?> | 
                                            <?php echo timeAgo($post['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <?php if ($post['is_pinned']): ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-thumbtack me-1"></i>Pinned
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <?php if ($post['image']): ?>
                                <img src="<?php echo SITE_URL . '/uploads/' . $post['image']; ?>" 
                                     class="img-fluid rounded mb-3" alt="Post image">
                            <?php endif; ?>
                            
                            <div class="d-flex gap-3 mb-3 text-muted small">
                                <span>
                                    <i class="fas fa-thumbs-up me-1"></i><?php echo $post['like_count']; ?> Likes
                                </span>
                                <span>
                                    <i class="fas fa-comment me-1"></i><?php echo $post['comment_count']; ?> Comments
                                </span>
                                <span>
                                    <i class="fas fa-eye me-1"></i><?php echo ucfirst($post['visibility']); ?>
                                </span>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="pin_post" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-thumbtack me-1"></i>
                                        <?php echo $post['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="delete_post" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('Delete this post?')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="empty-state-title">No posts found</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
