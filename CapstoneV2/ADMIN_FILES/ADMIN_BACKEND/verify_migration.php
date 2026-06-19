<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if password_changed column exists
$checkCol = $conn->query("SHOW COLUMNS FROM admin_accounts LIKE 'password_changed'");

if ($checkCol && $checkCol->num_rows > 0) {
    // Column exists, show current data
    $result = $conn->query("SELECT id, first_name, last_name, role, admin_password, password_changed FROM admin_accounts ORDER BY id DESC LIMIT 5");
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'password_changed column EXISTS in database',
        'sample_accounts' => $accounts
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'password_changed column DOES NOT exist - running migration now...'
    ]);
    
    // Add the column
    if ($conn->query("ALTER TABLE admin_accounts ADD COLUMN password_changed BOOLEAN DEFAULT FALSE")) {
        // Set default passwords
        $conn->query("UPDATE admin_accounts SET admin_password = 'Teacher@123', password_changed = FALSE WHERE role = 'teacher'");
        $conn->query("UPDATE admin_accounts SET admin_password = 'Student@123', password_changed = FALSE WHERE role = 'student'");
        $conn->query("UPDATE admin_accounts SET admin_password = 'Admin@123', password_changed = FALSE WHERE role = 'admin'");
        
        echo json_encode([
            'success' => true,
            'message' => 'Column added and passwords updated successfully'
        ]);
    }
}

$conn->close();
?>
