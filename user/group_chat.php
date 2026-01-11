<?php
$pageTitle = 'Group Chat';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Validate inputs
$type = $_GET['type'] ?? '';
$value = $_GET['name'] ?? '';

// Security Check: Ensure user belongs to this group
if ($type === 'department') {
    if ($user['department'] !== $value) {
        redirectWithMessage(SITE_URL . '/user/groups.php', 'Access Denied', 'error');
    }
} elseif ($type === 'batch') {
    // Check if user is in this batch OR (optional: admins/mods can view)
    // For now, strict check: sender must be in batch, OR we check if batch belongs to user's department? 
    // Alumni restriction logic says they can only view batches in their department. 
    // Let's enforce: User must be in the same department as the batch.
    
    // First verify batch exists and get its department
    $stmt = $db->prepare("SELECT department FROM batches WHERE batch_name = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirectWithMessage(SITE_URL . '/user/groups.php', 'Group not found', 'error');
    }
    
    $batchData = $result->fetch_assoc();
    if ($user['department'] !== $batchData['department']) {
       redirectWithMessage(SITE_URL . '/user/groups.php', 'Access Denied', 'error');
    }
} else {
    redirect(SITE_URL . '/user/groups.php');
}

// Handle AJAX Request for Members (Autocomplete)
if (isset($_GET['action']) && $_GET['action'] === 'fetch_members') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    $members = [];
    if ($type === 'department') {
        $stmt = $db->prepare("SELECT id, name, profile_image FROM users WHERE department = ? AND status = 'active'");
        $stmt->bind_param("s", $value);
    } else { // batch
        $stmt = $db->prepare("SELECT id, name, profile_image FROM users WHERE batch = ? AND department = ? AND status = 'active'");
        $stmt->bind_param("ss", $value, $user['department']);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['profile_image_url'] = SITE_URL . '/uploads/' . ($row['profile_image'] ?: 'default-avatar.png');
        $members[] = $row;
    }
    
    echo json_encode(['members' => $members]);
    exit;
}

// Handle AJAX Request for Messages (Fetch)
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    // Clear output buffer to ensure clean JSON
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    $query = "SELECT m.*, u.name, u.profile_image, u.role 
              FROM group_messages m 
              JOIN users u ON m.user_id = u.id 
              WHERE m.group_type = ? AND m.group_value = ? AND m.id > ? 
              ORDER BY m.created_at ASC";
              
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssi", $type, $value, $lastId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_me'] = ($row['user_id'] == $user['id']);
        $row['time'] = date('g:i A', strtotime($row['created_at']));
        // Fix image path
        $row['profile_image_url'] = SITE_URL . '/uploads/' . ($row['profile_image'] ?: 'default-avatar.png');
        
        // Highlight mentions
        // Using a specialized class for styling
        $row['message'] = preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', htmlspecialchars($row['message']));
        
        $messages[] = $row;
    }
    
    echo json_encode(['messages' => $messages]);
    exit;
}

// Handle POST Request (Send)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    // Clear output buffer
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO group_messages (group_type, group_value, user_id, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $type, $value, $user['id'], $message);
        
        if ($stmt->execute()) {
            $messageId = $stmt->insert_id;
            
            // Handle Mentions Notification
            if (preg_match_all('/@(\w+)/', $message, $matches)) {
                $mentionedNames = array_unique($matches[1]);
                foreach ($mentionedNames as $name) {
                    // Find user ID by name (simple lookup)
                    // Note: This matches partial names or first matches. 
                    // ideally we should use unique username but we use name here as requested.
                    $uStmt = $db->prepare("SELECT id FROM users WHERE name LIKE ? LIMIT 1");
                    $likeName = "%$name%";
                    $uStmt->bind_param("s", $likeName);
                    $uStmt->execute();
                    $uResult = $uStmt->get_result();
                    
                    if ($uRow = $uResult->fetch_assoc()) {
                        if ($uRow['id'] != $user['id']) { // Don't notify self
                            createNotification($uRow['id'], 'mention', "You were mentioned in a chat by " . $user['name'], $messageId);
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
    }
    exit;
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-lg chat-card" style="height: 80vh;">
                <!-- Chat Header -->
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <a href="<?php echo SITE_URL; ?>/user/groups.php" class="text-white me-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0">
                                <?php if ($type === 'department'): ?>
                                    <i class="fas fa-building me-2"></i>
                                <?php else: ?>
                                    <i class="fas fa-graduation-cap me-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($value); ?>
                            </h5>
                            <small class="text-white-50">Group Chat</small>
                        </div>
                    </div>
                    <div>
                         <?php 
                            // Link to existing Explore page for member list
                            $exploreLink = SITE_URL . "/user/explore.php?department=" . urlencode($user['department']);
                            if ($type === 'batch') {
                                $exploreLink .= "&batch=" . urlencode($value);
                            }
                        ?>
                        <a href="<?php echo $exploreLink; ?>" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-users me-1"></i>Members
                        </a>
                    </div>
                </div>
                
                <!-- Chat Body -->
                <div class="card-body bg-light overflow-auto" id="chat-messages" style="flex: 1; display: flex; flex-direction: column;">
                    <!-- Messages will be loaded here -->
                    <div class="text-center text-muted my-auto" id="loading-msg">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Footer -->
                <div class="card-footer bg-white py-3 position-relative">
                    <div id="mention-list" class="list-group position-absolute shadow-lg" style="bottom: 100%; left: 0; display: none; max-height: 200px; overflow-y: auto; z-index: 1000; width: 250px;">
                        <!-- Suggestions go here -->
                    </div>
                    
                    <form id="chat-form" class="d-flex gap-2">
                        <input type="text" class="form-control form-control-lg" id="message-input" 
                               placeholder="Type a message... (@ to mention)" autocomplete="off">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-card {
    display: flex;
    flex-direction: column;
}
.message-bubble {
    max-width: 75%;
    padding: 10px 15px;
    border-radius: 15px;
    margin-bottom: 5px;
    position: relative;
    word-wrap: break-word;
}
.message-me {
    background-color: var(--primary-color);
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 2px;
}
.message-other {
    background-color: #e9ecef;
    color: #333;
    align-self: flex-start;
    border-bottom-left-radius: 2px;
}
.message-row {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
    width: 100%;
}
.message-meta {
    font-size: 0.75rem;
    margin-bottom: 2px;
}
.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 2px;
    text-align: right;
}
.sender-name {
    font-weight: bold;
    margin-right: 5px;
}
.mention {
    font-weight: 800; /* Extra bold */
    color: var(--primary-color);
}
.message-me .mention {
    color: #ffffff !important;
    text-decoration: none; /* Removed underline as per preference for clean look */
    background-color: rgba(255,255,255,0.2); /* Subtle highlight background */
    padding: 0 4px;
    border-radius: 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const loadingMsg = document.getElementById('loading-msg');
    
    let lastId = 0;
    let isFirstLoad = true;
    
    // Poll for new messages every 3 seconds
    setInterval(fetchMessages, 3000);
    fetchMessages(); // Initial load
    
    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    
    function fetchMessages() {
        const url = `?type=<?php echo urlencode($type); ?>&name=<?php echo urlencode($value); ?>&action=fetch&last_id=${lastId}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (isFirstLoad) {
                    loadingMsg.style.display = 'none';
                    isFirstLoad = false;
                }
                
                if (data.messages && data.messages.length > 0) {
                    let shouldScroll = (chatBox.scrollTop + chatBox.clientHeight === chatBox.scrollHeight);
                    
                    data.messages.forEach(msg => {
                        appendMessage(msg);
                        lastId = Math.max(lastId, msg.id);
                    });
                    
                    // Always scroll on first load or if user was already at bottom
                    if (shouldScroll || lastId === data.messages[data.messages.length-1].id) {
                        scrollToBottom();
                    }
                }
            })
            .catch(err => console.error('Error fetching messages:', err));
    }
    
    function appendMessage(msg) {
        const isMe = msg.is_me;
        const alignClass = isMe ? 'align-items-end' : 'align-items-start';
        const bubbleClass = isMe ? 'message-me' : 'message-other';
        
        const div = document.createElement('div');
        div.className = `message-row ${alignClass}`;
        
        let content = '';
        
        if (!isMe) {
            content += `
                <div class="message-meta text-muted ms-1">
                    <img src="${msg.profile_image_url}" class="rounded-circle me-1" width="20" height="20">
                    <span class="sender-name">${msg.name}</span>
                </div>
            `;
        }
        
        content += `
            <div class="message-bubble ${bubbleClass}">
                ${msg.message}
                <div class="message-time">${msg.time}</div>
            </div>
        `;
        
        div.innerHTML = content;
        chatBox.appendChild(div);
    }
    
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = messageInput.value.trim();
        if (!text) return;
        
        const formData = new FormData();
        formData.append('message', text);
        
        messageInput.value = ''; // Clear input immediately for UX
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetchMessages(); // Fetch immediately to show own message
            } else {
                alert('Failed to send message');
            }
        })
        .catch(err => console.error('Error sending message:', err));
    });
    
    function escapeHtml(text) {
        // Return raw html if it contains span, else escape.
        // Actually, for display we need to be careful. The backend is returning highlighted HTML spans.
        // So we should NOT escape everything if it already contains trusted HTML from backend.
        // But here we are using innerHTML assignment.
        // Let's rely on backend sanitization for message content, OR simple detection here.
        // The appendMessage function was doing `escapeHtml(msg.message)`.
        // We should change appendMessage to trust the backend's HTML transformation for mentions.
        return text; 
    }

    // Mention Logic
    const mentionList = document.getElementById('mention-list');
    let members = [];
    
    // Fetch members on load
    fetch(`?type=<?php echo urlencode($type); ?>&name=<?php echo urlencode($value); ?>&action=fetch_members`)
        .then(res => res.json())
        .then(data => {
            members = data.members || [];
        });

    messageInput.addEventListener('keyup', function(e) {
        const val = this.value;
        const cursorPosition = this.selectionStart;
        const textBeforeCursor = val.substring(0, cursorPosition);
        const lastAt = textBeforeCursor.lastIndexOf('@');
        
        if (lastAt !== -1) {
            const query = textBeforeCursor.substring(lastAt + 1);
            // Check if there's a space after @, if so, stop suggesting (unless we want multi-word names?)
            // We'll allow spaces for names like "Rakib Hasan"
            
            if (query.length >= 0) { // Show immediately
                const matches = members.filter(m => m.name.toLowerCase().startsWith(query.toLowerCase()));
                if (matches.length > 0) {
                    showMentions(matches, lastAt);
                } else {
                    mentionList.style.display = 'none';
                }
            } else {
                mentionList.style.display = 'none';
            }
        } else {
            mentionList.style.display = 'none';
        }
    });
    
    function showMentions(matches, atIndex) {
        mentionList.innerHTML = '';
        matches.forEach(m => {
            const item = document.createElement('a');
            item.className = 'list-group-item list-group-item-action cursor-pointer d-flex align-items-center';
            item.style.cursor = 'pointer';
            // content
            item.innerHTML = `
                <img src="${m.profile_image_url}" class="rounded-circle me-2" width="30" height="30" style="object-fit: cover;">
                <span class="fw-bold">${m.name}</span>
            `;
            item.onclick = function() {
                insertMention(m.name, atIndex);
            };
            mentionList.appendChild(item);
        });
        mentionList.style.display = 'block';
    }
    
    function insertMention(name, atIndex) {
        const val = messageInput.value;
        const textBeforeAt = val.substring(0, atIndex);
        const textAfterCursor = val.substring(messageInput.selectionStart);
        
        // Replace the query with the name + space
        messageInput.value = textBeforeAt + '@' + name + ' ' + textAfterCursor;
        mentionList.style.display = 'none';
        messageInput.focus();
    }
    
    // Close suggestion on click outside
    document.addEventListener('click', function(e) {
        if (e.target !== mentionList && e.target !== messageInput) {
            mentionList.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
