<?php
$pageTitle = 'Feedback Management';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Get all feedback
$feedback = $db->query("SELECT f.*, u.name as user_name, u.email as user_email 
                        FROM feedback f 
                        LEFT JOIN users u ON f.user_id = u.id 
                        ORDER BY f.created_at DESC");
?>

<div class="container-fluid">
    <h4 class="mb-4">
        <i class="fas fa-comments me-2 text-primary"></i>Feedback & Support
    </h4>
    
    <?php if ($feedback->num_rows > 0): ?>
        <?php while ($item = $feedback->fetch_assoc()): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1">
                                <?php echo $item['subject'] ?? 'No Subject'; ?>
                            </h6>
                            <div class="text-muted small">
                                From: <strong><?php echo htmlspecialchars($item['user_name'] ?? $item['name']); ?></strong>
                                (<?php echo htmlspecialchars($item['user_email'] ?? $item['email']); ?>)
                                <br>
                                Date: <?php echo date('M d, Y g:i A', strtotime($item['created_at'])); ?>
                            </div>
                        </div>
                        <span class="badge bg-<?php 
                            echo $item['status'] === 'new' ? 'danger' : 
                                ($item['status'] === 'read' ? 'warning' : 'success'); 
                        ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                    </div>
                    
                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($item['message'])); ?></p>
                    
                    <?php if ($item['admin_reply']): ?>
                        <div class="alert alert-success">
                            <strong>Admin Reply:</strong><br>
                            <?php echo nl2br(htmlspecialchars($item['admin_reply'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="empty-state-title">No feedback found</div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
