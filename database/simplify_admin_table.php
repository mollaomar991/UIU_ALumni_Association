<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$db = getDB();

echo "Simplifying admin table (removing role column)...\n";

// Check if role column exists
$result = $db->query("SHOW COLUMNS FROM admin LIKE 'role'");

if ($result->num_rows > 0) {
    // Drop the role column
    $sql = "ALTER TABLE admin DROP COLUMN role";
    
    if ($db->query($sql)) {
        echo "✓ Role column removed successfully.\n";
    } else {
        echo "✗ Error: " . $db->error . "\n";
    }
} else {
    echo "✓ Role column already removed.\n";
}

echo "\nAdmin table simplified. All admins now have equal access.\n";
?>
