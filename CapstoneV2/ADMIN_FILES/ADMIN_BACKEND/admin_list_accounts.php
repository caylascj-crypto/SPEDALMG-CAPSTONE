<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode([]);
    exit;
}

// Check and add missing columns if they don't exist
$checkPhone = $conn->query("SHOW COLUMNS FROM admin_accounts LIKE 'phone_number'");
if ($checkPhone && $checkPhone->num_rows == 0) {
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN phone_number VARCHAR(20) AFTER school_name");
}

$checkCreated = $conn->query("SHOW COLUMNS FROM admin_accounts LIKE 'created_at'");
if ($checkCreated && $checkCreated->num_rows == 0) {
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER updated_at");
}

// Query with phone_number and created_at
$result = $conn->query("SELECT id, admin_email, first_name, last_name, COALESCE(phone_number, '') as phone_number, role, condition_info, status, COALESCE(created_at, NOW()) as created_at FROM admin_accounts ORDER BY id DESC");

if (!$result) {
    echo json_encode([]);
    $conn->close();
    exit;
}

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}

echo json_encode($accounts);
$conn->close();