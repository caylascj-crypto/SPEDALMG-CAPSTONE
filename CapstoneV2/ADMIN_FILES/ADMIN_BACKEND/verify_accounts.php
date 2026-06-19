<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Check current accounts
$result = $conn->query("SELECT id, admin_email, first_name, last_name, role, status FROM admin_accounts ORDER BY id");

if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}

// Ensure correct roles
$updateAdminSql = "UPDATE admin_accounts SET role = 'admin' WHERE admin_email = 'admin@spedalm.edu.ph'";
$conn->query($updateAdminSql);

$updateTeacherSql = "UPDATE admin_accounts SET role = 'teacher' WHERE admin_email = 'caylas@spedalm.edu.ph'";
$conn->query($updateTeacherSql);

echo json_encode([
    'message' => 'Accounts verified and updated',
    'accounts_before' => $accounts
]);

$conn->close();