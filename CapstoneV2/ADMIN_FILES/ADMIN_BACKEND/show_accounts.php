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

$result = $conn->query("SELECT id, admin_email, admin_password, first_name, last_name, role, status FROM admin_accounts WHERE status = 'active' ORDER BY role, id");

if (!$result) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}

echo json_encode([
    'total' => count($accounts),
    'accounts' => $accounts
]);

$conn->close();