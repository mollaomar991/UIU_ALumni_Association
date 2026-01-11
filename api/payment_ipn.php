<?php
// IPN (Instant Payment Notification) handler
// This is called by SSLCommerz server-to-server for payment confirmation

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/sslcommerz_config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$db = getDB();

$tran_id = $_POST['tran_id'] ?? '';
$val_id = $_POST['val_id'] ?? '';
$amount = $_POST['amount'] ?? 0;
$status = $_POST['status'] ?? '';

if (empty($tran_id) || empty($val_id)) {
    http_response_code(400);
    exit('Invalid IPN data');
}

// Validate with SSLCommerz
$validation_url = SSLCZ_VALIDATION_URL . '?val_id=' . $val_id . '&store_id=' . SSLCZ_STORE_ID . '&store_passwd=' . SSLCZ_STORE_PASSWORD . '&format=json';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $validation_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$validation_response = curl_exec($ch);
curl_close($ch);

$validation_data = json_decode($validation_response, true);

if (isset($validation_data['status']) && $validation_data['status'] === 'VALID') {
    // Get donation record
    $stmt = $db->prepare("SELECT * FROM donations WHERE transaction_id = ?");
    $stmt->bind_param("s", $tran_id);
    $stmt->execute();
    $donation = $stmt->get_result()->fetch_assoc();
    
    if ($donation && $donation['status'] !== 'Success') {
        // Update donation status
        $stmt = $db->prepare("UPDATE donations SET status = 'Success' WHERE transaction_id = ?");
        $stmt->bind_param("s", $tran_id);
        $stmt->execute();
        
        // Update fundraiser amount
        $stmt = $db->prepare("UPDATE fundraisers SET current_amount = current_amount + ? WHERE id = ?");
        $stmt->bind_param("di", $donation['amount'], $donation['fundraiser_id']);
        $stmt->execute();
        
        http_response_code(200);
        echo 'IPN processed successfully';
    } else {
        http_response_code(200);
        echo 'Already processed';
    }
} else {
    http_response_code(400);
    echo 'Validation failed';
}
?>
