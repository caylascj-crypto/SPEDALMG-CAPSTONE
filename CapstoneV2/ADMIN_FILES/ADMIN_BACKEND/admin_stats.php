<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['total_users' => 0, 'teachers' => 0, 'students' => 0, 'active_accounts' => 0]);
    exit;
}

$result = $conn->query("SELECT
    COUNT(*) AS total_users,
    SUM(role = 'teacher') AS teachers,
    SUM(role = 'student') AS students,
    SUM(status = 'active') AS active_accounts
FROM admin_accounts");

if (!$result) {
    echo json_encode(['total_users' => 0, 'teachers' => 0, 'students' => 0, 'active_accounts' => 0]);
    $conn->close();
    exit;
}

$stats = $result->fetch_assoc();
$result->close();
$conn->close();

echo json_encode($stats);