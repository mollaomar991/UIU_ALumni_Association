<?php
$pageTitle = 'Donations';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Fetch Active Fundraisers
$query = "SELECT * FROM fundraisers WHERE status = 'active' ORDER BY created_at DESC";
$fundraisers = $db->query($query);
?>

<div class="container py-4">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary"><i class="fas fa-hand-holding-heart me-2"></i>Give Back to UIU</h2>
        <p class="text-muted">Support scholarships, campus development, and alumni welfare.</p>
    </div>
    
    <div class="row g-4">
        <?php if ($fundraisers->num_rows > 0): ?>
            <?php while ($f = $fundraisers->fetch_assoc()): ?>
                <?php 
                    $percent = ($f['current_amount'] / $f['goal_amount']) * 100;
                    $percent = min(100, $percent); // Cap at 100%
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow transition-all">
                        <img src="<?php echo SITE_URL; ?>/assets/images/donation-placeholder.jpg" 
                             class="card-img-top" alt="Fundraiser" 
                             style="height: 200px; object-fit: cover;"
                             onerror="this.src='https://placehold.co/600x400/FF6622/ffffff?text=Fundraiser'">
                        
                        <div class="card-body">
                            <span class="badge bg-success mb-2">Active</span>
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($f['title']); ?></h5>
                            <p class="card-text text-muted small mb-3">
                                <?php echo htmlspecialchars(truncateText($f['description'], 120)); ?>
                            </p>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small fw-bold mb-1">
                                    <span>Raised: ৳<?php echo number_format($f['current_amount']); ?></span>
                                    <span>Goal: ৳<?php echo number_format($f['goal_amount']); ?></span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $percent; ?>%;" 
                                         aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="text-end small text-muted mt-1"><?php echo number_format($percent, 1); ?>% funded</div>
                            </div>
                            
                            <div class="d-grid">
                                <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#donateModal-<?php echo $f['id']; ?>">
                                    <i class="fas fa-heart me-2"></i>Donate Now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Donation Modal -->
                <div class="modal fade" id="donateModal-<?php echo $f['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="<?php echo SITE_URL; ?>/api/payment_init.php">
                                <div class="modal-header">
                                    <h5 class="modal-title">Donate to <?php echo htmlspecialchars($f['title']); ?></h5>
                                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><?php echo htmlspecialchars($f['description']); ?></p>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Amount (BDT)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">৳</span>
                                            <input type="number" class="form-control" name="amount" min="10" placeholder="e.g. 500" required>
                                        </div>
                                        <small class="text-muted">Minimum donation: BDT 10</small>
                                    </div>
                                    
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        You will be redirected to SSLCommerz secure payment gateway
                                    </div>
                                    
                                    <input type="hidden" name="fundraiser_id" value="<?php echo $f['id']; ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-lock me-2"></i>Proceed to Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">No active fundraising campaigns at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- My Donations History -->
    <div class="mt-5">
        <h4 class="mb-3">My Donations</h4>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php
                $histQuery = "SELECT d.*, f.title FROM donations d JOIN fundraisers f ON d.fundraiser_id = f.id WHERE d.user_id = ? ORDER BY d.created_at DESC";
                $stmt = $db->prepare($histQuery);
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $history = $stmt->get_result();
                ?>
                
                <?php if ($history->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Campaign</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($h = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($h['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($h['title']); ?></td>
                                        <td class="fw-bold text-success">৳<?php echo number_format($h['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($h['payment_method']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = match($h['status']) {
                                                'Success' => 'success',
                                                'Pending' => 'warning',
                                                'Processing' => 'info',
                                                'Failed', 'Cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $h['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">You haven't made any donations yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
