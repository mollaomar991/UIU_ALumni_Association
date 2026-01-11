<?php
$pageTitle = 'Event Management';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$adminId = $_SESSION['admin_id'];

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $eventDate = sanitizeInput($_POST['event_date'] ?? '');
    $venue = sanitizeInput($_POST['venue'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (!empty($title) && !empty($description) && !empty($eventDate)) {
        $stmt = $db->prepare("INSERT INTO events (title, description, event_date, venue, created_by, is_featured) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $title, $description, $eventDate, $venue, $adminId, $isFeatured);
        
        if ($stmt->execute()) {
            redirectWithMessage(SITE_URL . '/admin/events.php', 'Event created successfully!', 'success');
        }
    }
}

// Handle event deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $eventId = (int)$_POST['event_id'];
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    if ($stmt->execute()) {
        redirectWithMessage(SITE_URL . '/admin/events.php', 'Event deleted successfully!', 'success');
    }
}

// Get events
$events = $db->query("SELECT e.*, a.username as created_by_name 
                      FROM events e 
                      JOIN admin a ON e.created_by = a.id 
                      ORDER BY e.event_date DESC");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-calendar-alt me-2 text-primary"></i>Event Management
        </h4>
        <button type="button" class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#createEventModal">
            <i class="fas fa-plus me-2"></i>Create Event
        </button>
    </div>
    
    <?php if ($events->num_rows > 0): ?>
        <div class="row">
            <?php while ($event = $events->fetch_assoc()): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <?php if ($event['is_featured']): ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            
                            <div class="mb-2">
                                <i class="fas fa-calendar me-2 text-primary"></i>
                                <?php echo date('F j, Y g:i A', strtotime($event['event_date'])); ?>
                            </div>
                            
                            <?php if ($event['venue']): ?>
                                <div class="mb-3">
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($event['venue']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-muted small mb-3">
                                Created by <?php echo htmlspecialchars($event['created_by_name']); ?>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" name="delete_event" class="btn btn-danger btn-sm" 
                                        onclick="return confirm('Delete this event?')">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="empty-state-title">No events found</div>
            <div class="empty-state-text">Create your first event</div>
        </div>
    <?php endif; ?>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2 text-primary"></i>Create Event
                </h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="event_date" class="form-label">Event Date & Time</label>
                            <input type="datetime-local" class="form-control" id="event_date" name="event_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="venue" class="form-label">Venue</label>
                            <input type="text" class="form-control" id="venue" name="venue">
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                        <label class="form-check-label" for="is_featured">
                            Mark as Featured Event
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_event" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
