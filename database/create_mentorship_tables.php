<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$db = getDB();

// Add skills column to users table if it doesn't exist
$checkColumn = $db->query("SHOW COLUMNS FROM users LIKE 'skills'");
if ($checkColumn->num_rows === 0) {
    $alterQuery = "ALTER TABLE users ADD COLUMN skills TEXT NULL AFTER bio";
    if ($db->query($alterQuery)) {
        echo "Column 'skills' added to 'users' table.<br>";
    } else {
        echo "Error adding 'skills' column: " . $db->error . "<br>";
    }
} else {
    echo "Column 'skills' already exists in 'users' table.<br>";
}

// Create mentorship_requests table
$query = "CREATE TABLE IF NOT EXISTS mentorship_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_request (mentor_id, mentee_id)
)";

if ($db->query($query)) {
    echo "Table 'mentorship_requests' created successfully.<br>";
} else {
    echo "Error creating table 'mentorship_requests': " . $db->error . "<br>";
}
?>
