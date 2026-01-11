<?php
$pageTitle = 'Events';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Handle RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp_action'])) {
    $eventId = (int)$_POST['event_id'];
    $status = $_POST['status']; // 'going' or 'interested'
    
    if (in_array($status, ['going', 'interested'])) {
        // Check if already RSVPed
        $checkStmt = $db->prepare("SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $eventId, $user['id']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing
            $updateStmt = $db->prepare("UPDATE event_participants SET status = ? WHERE event_id = ? AND user_id = ?");
            $updateStmt->bind_param("sii", $status, $eventId, $user['id']);
            $updateStmt->execute();
        } else {
            // Insert new
            $insertStmt = $db->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, ?)");
            $insertStmt->bind_param("iis", $eventId, $user['id'], $status);
            $insertStmt->execute();
        }
        
        // Redirect to avoid resubmission
        redirect(SITE_URL . '/user/events.php');
    } elseif ($status === 'remove') {
        // Remove RSVP
        $deleteStmt = $db->prepare("DELETE FROM event_participants WHERE event_id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $eventId, $user['id']);
        $deleteStmt->execute();
        redirect(SITE_URL . '/user/events.php');
    }
}

// Fetch Events with RSVP status
$query = "SELECT e.*, 
          (SELECT status FROM event_participants WHERE event_id = e.id AND user_id = ?) as my_status,
          (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND status = 'going') as going_count,
          (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND status = 'interested') as interested_count
          FROM events e 
          WHERE e.event_date >= CURDATE() 
          ORDER BY e.event_date ASC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$events = $stmt->get_result();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-calendar-alt me-2 text-primary"></i>Upcoming Events
        </h4>
    </div>
    
    <?php if ($events->num_rows > 0): ?>
        <div class="row">
            <?php while ($event = $events->fetch_assoc()): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 shadow-sm hover-shadow transition-all <?php echo $event['is_featured'] ? 'border-primary' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <?php if ($event['is_featured']): ?>
                                        <span class="badge bg-primary mb-2">Featured</span>
                                    <?php endif; ?>
                                    <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($event['title']); ?></h5>
                                </div>
                                <div class="text-center bg-light rounded p-2 border">
                                    <div class="text-uppercase small fw-bold text-muted"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                    <div class="h4 mb-0 fw-bold"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                </div>
                            </div>
                            
                            <p class="card-text text-muted mb-3">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </p>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2 text-muted">
                                    <i class="far fa-clock me-2 w-20 text-center"></i>
                                    <?php echo date('g:i A', strtotime($event['event_date'])); ?>
                                </div>
                                <?php if ($event['venue']): ?>
                                    <div class="d-flex align-items-center text-muted">
                                        <i class="fas fa-map-marker-alt me-2 w-20 text-center"></i>
                                        <?php echo htmlspecialchars($event['venue']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <div class="small text-muted">
                                    <span class="me-3">
                                        <i class="fas fa-check-circle me-1 text-success"></i>
                                        <?php echo $event['going_count']; ?> Going
                                    </span>
                                    <span>
                                        <i class="fas fa-star me-1 text-warning"></i>
                                        <?php echo $event['interested_count']; ?> Interested
                                    </span>
                                </div>
                                
                                <div class="dropdown">
                                    <?php 
                                    $btnClass = 'btn-outline-primary';
                                    $btnText = 'RSVP';
                                    $btnIcon = 'far fa-calendar-check';
                                    
                                    if ($event['my_status'] === 'going') {
                                        $btnClass = 'btn-success';
                                        $btnText = 'Going';
                                        $btnIcon = 'fas fa-check';
                                    } elseif ($event['my_status'] === 'interested') {
                                        $btnClass = 'btn-warning';
                                        $btnText = 'Interested';
                                        $btnIcon = 'fas fa-star';
                                    }
                                    ?>
                                    
                                    <button class="btn <?php echo $btnClass; ?> btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="<?php echo $btnIcon; ?> me-1"></i><?php echo $btnText; ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form method="POST">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <input type="hidden" name="rsvp_action" value="1">
                                                <button type="submit" name="status" value="going" class="dropdown-item <?php echo $event['my_status'] === 'going' ? 'active' : ''; ?>">
                                                    <i class="fas fa-check me-2 text-success"></i>Going
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <input type="hidden" name="rsvp_action" value="1">
                                                <button type="submit" name="status" value="interested" class="dropdown-item <?php echo $event['my_status'] === 'interested' ? 'active' : ''; ?>">
                                                    <i class="fas fa-star me-2 text-warning"></i>Interested
                                                </button>
                                            </form>
                                        </li>
                                        <?php if ($event['my_status']): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <input type="hidden" name="rsvp_action" value="1">
                                                    <button type="submit" name="status" value="remove" class="dropdown-item text-danger">
                                                        <i class="fas fa-times me-2"></i>Remove RSVP
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
            </div>
            <h5 class="text-muted">No upcoming events</h5>
            <p class="text-muted">Check back later for new events.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.w-20 { width: 20px; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
