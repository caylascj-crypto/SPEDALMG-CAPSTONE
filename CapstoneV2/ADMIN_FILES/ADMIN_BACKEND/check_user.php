<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = 98;

// Check if user exists
$result = $conn->query("SELECT * FROM admin_accounts WHERE id = $user_id");

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => "User ID $user_id NOT FOUND in database"]);
    exit;
}

$user = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'user_id' => $user['id'],
    'full_name' => $user['first_name'] . ' ' . $user['last_name'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['admin_email'],
    'phone' => $user['phone_number'],
    'role' => $user['role'],
    'status' => $user['status'],
    'condition' => $user['condition_info']
]);

$conn->close();
