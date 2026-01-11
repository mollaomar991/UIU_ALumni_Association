<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sslcommerz_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db_connect.php';

startSession();

$db = getDB();

// Get transaction details from SSLCommerz
$tran_id = $_POST['tran_id'] ?? '';
$val_id = $_POST['val_id'] ?? '';
$amount = $_POST['amount'] ?? 0;
$card_type = $_POST['card_type'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($tran_id) || empty($val_id)) {
    redirectWithMessage(SITE_URL . '/user/donations.php', 'Invalid payment response', 'error');
}

// Validate transaction with SSLCommerz
$validation_url = SSLCZ_VALIDATION_URL . '?val_id=' . $val_id . '&store_id=' . SSLCZ_STORE_ID . '&store_passwd=' . SSLCZ_STORE_PASSWORD . '&format=json';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $validation_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$validation_response = curl_exec($ch);
curl_close($ch);

$validation_data = json_decode($validation_response, true);

// Check if validation was successful
if (isset($validation_data['status']) && $validation_data['status'] === 'VALID') {
    // Get donation record
    $stmt = $db->prepare("SELECT * FROM donations WHERE transaction_id = ?");
    $stmt->bind_param("s", $tran_id);
    $stmt->execute();
    $donation = $stmt->get_result()->fetch_assoc();
    
    if (!$donation) {
        redirectWithMessage(SITE_URL . '/user/donations.php', 'Donation record not found', 'error');
    }
    
    // Check if already processed
    if ($donation['status'] === 'Success') {
        redirectWithMessage(SITE_URL . '/user/donations.php', 'This donation has already been processed', 'info');
    }
    
    // Verify amount matches
    if ((float)$validation_data['amount'] != (float)$donation['amount']) {
        redirectWithMessage(SITE_URL . '/user/donations.php', 'Amount mismatch detected', 'error');
    }
    
    // Update donation status
    $stmt = $db->prepare("UPDATE donations SET status = 'Success', payment_method = ? WHERE transaction_id = ?");
    $payment_method = 'SSLCommerz (' . $card_type . ')';
    $stmt->bind_param("ss", $payment_method, $tran_id);
    $stmt->execute();
    
    // Update fundraiser amount
    $stmt = $db->prepare("UPDATE fundraisers SET current_amount = current_amount + ? WHERE id = ?");
    $stmt->bind_param("di", $donation['amount'], $donation['fundraiser_id']);
    $stmt->execute();
    
    // Create notification for user
    createNotification($donation['user_id'], 'donation', 'Your donation of BDT ' . number_format($donation['amount']) . ' was successful!', $donation['fundraiser_id']);
    
    redirectWithMessage(SITE_URL . '/user/donations.php', 'Thank you! Your donation of BDT ' . number_format($donation['amount']) . ' has been received successfully.', 'success');
    
} else {
    // Validation failed
    $stmt = $db->prepare("UPDATE donations SET status = 'Failed' WHERE transaction_id = ?");
    $stmt->bind_param("s", $tran_id);
    $stmt->execute();
    
    redirectWithMessage(SITE_URL . '/user/donations.php', 'Payment validation failed. Please contact support.', 'error');
}
?>
