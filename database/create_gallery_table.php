<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$db = getDB();

echo "Creating gallery table...\n";

$sql = "CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    caption TEXT,
    tags VARCHAR(255),
    batch VARCHAR(50),
    department VARCHAR(100),
    event_type ENUM('reunion', 'campus', 'event', 'achievement', 'other') DEFAULT 'other',
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_batch (batch),
    INDEX idx_department (department),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($sql)) {
    echo "Gallery table created successfully.\n";
} else {
    echo "Error: " . $db->error . "\n";
}

// Create gallery likes table
$sql2 = "CREATE TABLE IF NOT EXISTS gallery_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gallery_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gallery_id) REFERENCES gallery(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (gallery_id, user_id),
    INDEX idx_gallery_id (gallery_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($sql2)) {
    echo "Gallery likes table created successfully.\n";
} else {
    echo "Error: " . $db->error . "\n";
}
?>
