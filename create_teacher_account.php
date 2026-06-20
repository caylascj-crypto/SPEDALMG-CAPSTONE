<?php
require_once __DIR__ . '/ADMIN_FILES/ADMIN_BACKEND/db.php';

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if teacher account exists
$result = $conn->query("SELECT id FROM admin_accounts WHERE admin_email = 'teacher@spedalm.edu.ph'");
if ($result && $result->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Teacher account already exists']);
    $conn->close();
    exit;
}

// Create teacher account
$stmt = $conn->prepare("INSERT INTO admin_accounts (admin_email, admin_password, first_name, last_name, role, status) 
                       VALUES (?, ?, ?, ?, ?, 'active')");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    $conn->close();
    exit;
}

$email = 'teacher@spedalm.edu.ph';
$password = 'Teacher@123';
$first_name = 'Ma. Teresa';
$last_name = 'Cruz';
$role = 'teacher';

$stmt->bind_param("sssss", $email, $password, $first_name, $last_name, $role);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Teacher account created successfully', 'account' => [
        'email' => $email,
        'password' => $password
    ]]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
