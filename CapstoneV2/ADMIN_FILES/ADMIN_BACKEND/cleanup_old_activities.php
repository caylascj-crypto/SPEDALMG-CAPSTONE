<?php
require_once __DIR__ . '/../../ADMIN_FILES/ADMIN_BACKEND/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Delete old default activities from admin_activities table
// These are the ones you want to remove
$deletions = [
    "DELETE FROM admin_activities WHERE user_name = 'Ma. Teresa Cruz'",
    "DELETE FROM admin_activities WHERE user_name = 'Nina De Leon'",
    "DELETE FROM admin_activities WHERE user_name = 'hydee' AND activity_type = 'Create Activity' AND DATE(created_at) = '2026-06-16'",
    "DELETE FROM admin_activities WHERE user_name = 'asd' AND activity_type = 'Complete Activity'",
];

$deleted_count = 0;
foreach ($deletions as $sql) {
    $result = $conn->query($sql);
    if ($result) {
        $deleted_count += $conn->affected_rows;
    }
}

echo json_encode(['success' => true, 'message' => "Deleted $deleted_count old activities"]);

$conn->close();
?>
