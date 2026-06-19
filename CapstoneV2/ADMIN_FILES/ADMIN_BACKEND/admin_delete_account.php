<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
    echo json_encode(['success' => false]);
    $conn->close();
    exit;
}

$ids = array_filter($data['ids'], function ($id) {
    return is_numeric($id) && $id > 0;
});

if (empty($ids)) {
    echo json_encode(['success' => false]);
    $conn->close();
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "DELETE FROM admin_accounts WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false]);
    $conn->close();
    exit;
}

$types = str_repeat('i', count($ids));
$stmt->bind_param($types, ...array_values($ids));
$result = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => (bool)$result]);