<?php
$pageTitle = 'Messages';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = (int)$_POST['receiver_id'];
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (!empty($message) && $receiverId > 0) {
        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user['id'], $receiverId, $message);
        $stmt->execute();
        
        // Create notification
        createNotification($receiverId, 'message', "New message from {$user['name']}", $user['id']);
    }
}

// Get selected conversation
$selectedUserId = isset($_GET['user']) ? (int)$_GET['user'] : null;

// Get all conversations
$query = "SELECT DISTINCT 
          CASE 
              WHEN m.sender_id = ? THEN m.receiver_id 
              ELSE m.sender_id 
          END as user_id,
          u.name, u.profile_image,
          (SELECT message FROM messages 
           WHERE (sender_id = ? AND receiver_id = user_id) 
           OR (sender_id = user_id AND receiver_id = ?)
           ORDER BY created_at DESC LIMIT 1) as last_message,
          (SELECT created_at FROM messages 
           WHERE (sender_id = ? AND receiver_id = user_id) 
           OR (sender_id = user_id AND receiver_id = ?)
           ORDER BY created_at DESC LIMIT 1) as last_message_time,
          (SELECT COUNT(*) FROM messages 
           WHERE sender_id = user_id AND receiver_id = ? AND is_read = 0) as unread_count
          FROM messages m
          JOIN users u ON u.id = CASE 
              WHEN m.sender_id = ? THEN m.receiver_id 
              ELSE m.sender_id 
          END
          WHERE m.sender_id = ? OR m.receiver_id = ?
          ORDER BY last_message_time DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("iiiiiiiii", $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']);
$stmt->execute();
$conversations = $stmt->get_result();

// Get selected conversation messages
$conversationMessages = [];
$selectedUser = null;

if ($selectedUserId) {
    // Mark messages as read
    $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $selectedUserId, $user['id']);
    $stmt->execute();
    
    // Get user info
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $selectedUserId);
    $stmt->execute();
    $selectedUser = $stmt->get_result()->fetch_assoc();
    
    // Get messages
    $stmt = $db->prepare("SELECT m.*, u.name, u.profile_image 
                         FROM messages m 
                         JOIN users u ON u.id = m.sender_id 
                         WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                         OR (m.sender_id = ? AND m.receiver_id = ?)
                         ORDER BY m.created_at ASC");
    $stmt->bind_param("iiii", $user['id'], $selectedUserId, $selectedUserId, $user['id']);
    $stmt->execute();
    $conversationMessages = $stmt->get_result();
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Conversations List -->
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-body p-0">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope me-2 text-primary"></i>Messages
                        </h5>
                    </div>
                    
                    <div class="message-list">
                        <?php if ($conversations->num_rows > 0): ?>
                            <?php while ($conv = $conversations->fetch_assoc()): ?>
                                <a href="?user=<?php echo $conv['user_id']; ?>" 
                                   class="message-item <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?> <?php echo $selectedUserId == $conv['user_id'] ? 'bg-light' : ''; ?>">
                                    <img src="<?php echo SITE_URL . '/uploads/' . $conv['profile_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($conv['name']); ?>" 
                                         class="message-avatar">
                                    <div class="message-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="message-name"><?php echo htmlspecialchars($conv['name']); ?></div>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="badge bg-primary rounded-pill"><?php echo $conv['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(truncateText($conv['last_message'], 40)); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo timeAgo($conv['last_message_time']); ?>
                                        </small>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-envelope fa-3x mb-3 d-block"></i>
                                <p class="mb-0">No conversations yet</p>
                                <small>Start messaging alumni from the <a href="<?php echo SITE_URL; ?>/user/explore.php">Explore</a> page</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="col-md-8 col-lg-9">
            <div class="card">
                <?php if ($selectedUser): ?>
                    <!-- Chat Header -->
                    <div class="card-header bg-white">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo SITE_URL . '/uploads/' . $selectedUser['profile_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($selectedUser['name']); ?>" 
                                 class="rounded-circle me-3" 
                                 style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($selectedUser['name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($selectedUser['batch']); ?> | 
                                    <?php echo htmlspecialchars($selectedUser['department']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Messages -->
                    <div class="chat-box">
                        <div class="chat-messages" id="chatMessages">
                            <?php while ($msg = $conversationMessages->fetch_assoc()): ?>
                                <div class="chat-message <?php echo $msg['sender_id'] == $user['id'] ? 'sent' : 'received'; ?>">
                                    <div class="chat-bubble">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        <div class="small text-muted mt-1">
                                            <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Chat Input -->
                        <div class="chat-input">
                            <form method="POST" action="" class="d-flex gap-2">
                                <input type="hidden" name="receiver_id" value="<?php echo $selectedUserId; ?>">
                                <input type="text" class="form-control" name="message" 
                                       placeholder="Type a message..." required>
                                <button type="submit" name="send_message" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="empty-state-title">Select a conversation</div>
                            <div class="empty-state-text">Choose a conversation from the list to start messaging</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedUser): ?>
<script>
// Auto-scroll to bottom of chat
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Auto-refresh messages every 5 seconds
setInterval(() => {
    location.reload();
}, 5000);
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
