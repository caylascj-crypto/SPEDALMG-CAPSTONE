<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Log what we received
$log = [
    'user_id' => $_POST['user_id'] ?? 'MISSING',
    'full_name' => $_POST['full_name'] ?? 'MISSING',
    'email_address' => $_POST['email_address'] ?? 'MISSING',
    'phone_number' => $_POST['phone_number'] ?? 'MISSING',
    'status' => $_POST['admin_status_input'] ?? 'MISSING',
    'condition' => $_POST['admin_edit_cond_select'] ?? 'MISSING',
    'all_post' => $_POST
];

file_put_contents(__DIR__ . '/save_debug.log', json_encode($log, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

echo json_encode(['success' => true, 'data_received' => $log]);
?>
