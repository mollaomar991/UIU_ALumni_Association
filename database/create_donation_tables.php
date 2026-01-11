<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$db = getDB();

echo "Creating donation tables...\n";

// Fundraisers Table
$sql1 = "CREATE TABLE IF NOT EXISTS fundraisers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(10, 2) NOT NULL,
    current_amount DECIMAL(10, 2) DEFAULT 0.00,
    image_url VARCHAR(255) DEFAULT 'default-fundraiser.jpg',
    status ENUM('active', 'completed', 'closed') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NULL,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($sql1)) {
    echo "Fundraisers table created successfully.\n";
} else {
    echo "Error creating fundraisers table: " . $db->error . "\n";
}

// Donations Table
$sql2 = "CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fundraiser_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_id VARCHAR(100),
    payment_method VARCHAR(50) DEFAULT 'card',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fundraiser_id) REFERENCES fundraisers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_fundraiser (fundraiser_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($sql2)) {
    echo "Donations table created successfully.\n";
} else {
    echo "Error creating donations table: " . $db->error . "\n";
}

// Insert a sample fundraiser
$check = $db->query("SELECT COUNT(*) as count FROM fundraisers");
if ($check->fetch_assoc()['count'] == 0) {
    // Need an admin ID
    $admin = $db->query("SELECT id FROM admin LIMIT 1")->fetch_assoc();
    if ($admin) {
        $stmt = $db->prepare("INSERT INTO fundraisers (title, description, goal_amount, created_by, end_date) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
        $title = "Scholarship Fund 2024";
        $desc = "Touching lives, one scholarship at a time. Help us support meritorious but needy students of UIU.";
        $goal = 500000.00;
        $adminId = $admin['id'];
        
        $stmt->bind_param("ssdi", $title, $desc, $goal, $adminId);
        if ($stmt->execute()) {
             echo "Sample fundraiser created.\n";
        }
    }
}
?>
