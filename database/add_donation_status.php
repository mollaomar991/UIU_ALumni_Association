<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$db = getDB();

echo "Adding status column to donations table...\n";

$sql = "ALTER TABLE donations ADD COLUMN IF NOT EXISTS status ENUM('Pending', 'Processing', 'Success', 'Failed', 'Cancelled') DEFAULT 'Pending' AFTER payment_method";

if ($db->query($sql)) {
    echo "Status column added successfully.\n";
} else {
    echo "Error: " . $db->error . "\n";
}
?>
