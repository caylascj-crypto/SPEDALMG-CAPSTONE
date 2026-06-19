<?php
require_once __DIR__ . '/db.php';

$conn = getDatabaseConnection();
if (!$conn) {
    echo "Database connection failed";
    exit;
}

// Check if phone_number column exists
$result = $conn->query("SHOW COLUMNS FROM admin_accounts LIKE 'phone_number'");
if ($result->num_rows == 0) {
    echo "Adding phone_number column...";
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN phone_number VARCHAR(20) AFTER school_name");
    echo " Done!<br>";
}

// Check if created_at column exists
$result = $conn->query("SHOW COLUMNS FROM admin_accounts LIKE 'created_at'");
if ($result->num_rows == 0) {
    echo "Adding created_at column...";
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo " Done!<br>";
}

echo "Database setup complete!";
$conn->close();
?>
