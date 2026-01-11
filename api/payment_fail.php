<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db_connect.php';

startSession();

$db = getDB();

$tran_id = $_POST['tran_id'] ?? '';

if (!empty($tran_id)) {
    // Update donation status to Failed
    $stmt = $db->prepare("UPDATE donations SET status = 'Failed' WHERE transaction_id = ?");
    $stmt->bind_param("s", $tran_id);
    $stmt->execute();
}

redirectWithMessage(SITE_URL . '/user/donations.php', 'Payment failed. Please try again.', 'error');
?>
