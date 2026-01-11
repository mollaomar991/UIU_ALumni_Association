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

// Check if user owns the post
$stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit;
}

$post = $result->fetch_assoc();

if ($post['user_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Delete post (cascade will handle likes and comments)
$stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
$stmt->bind_param("i", $postId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
}
?>
