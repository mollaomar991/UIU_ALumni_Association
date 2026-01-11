<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

if (!isLoggedIn()) {
    redirectWithMessage(SITE_URL . '/auth/login.php', 'Please login to comment', 'error');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/user/feed.php');
}

$postId = (int)($_POST['post_id'] ?? 0);
$commentText = sanitizeInput($_POST['comment_text'] ?? '');
$userId = $_SESSION['user_id'];

if ($postId > 0 && !empty($commentText)) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $postId, $userId, $commentText);
    
    if ($stmt->execute()) {
        // Create notification for post owner
        $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();
        
        if ($post && $post['user_id'] != $userId) {
            $currentUser = getCurrentUser();
            createNotification($post['user_id'], 'comment', "{$currentUser['name']} commented on your post", $postId);
        }
    }
}

redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/user/feed.php');
?>
