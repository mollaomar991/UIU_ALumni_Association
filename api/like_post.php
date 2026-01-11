<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
startSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

$db = getDB();

// Check if already liked
$stmt = $db->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $postId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Unlike
    $stmt = $db->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
} else {
    // Like
    $stmt = $db->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    
    // Create notification for post owner
    $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    
    if ($post && $post['user_id'] != $userId) {
        $currentUser = getCurrentUser();
        createNotification($post['user_id'], 'like', "{$currentUser['name']} liked your post", $postId);
    }
}

// Get total likes
$stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
$stmt->bind_param("i", $postId);
$stmt->execute();
$likes = $stmt->get_result()->fetch_assoc()['count'];

echo json_encode(['success' => true, 'likes' => $likes]);
?>
