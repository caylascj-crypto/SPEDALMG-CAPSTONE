<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if password_changed column exists, if not add it
$checkCol = $conn->query("SHOW COLUMNS FROM admin_accounts LIKE 'password_changed'");
if ($checkCol && $checkCol->num_rows == 0) {
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN password_changed BOOLEAN DEFAULT FALSE");
    file_put_contents(__DIR__ . '/migrate.log', date('Y-m-d H:i:s') . " - Added password_changed column\n", FILE_APPEND);
}

// Update all teacher accounts to Teacher@123 where password hasn't been changed
$teacherResult = $conn->query("UPDATE admin_accounts SET admin_password = 'Teacher@123', password_changed = FALSE WHERE role = 'teacher' AND password_changed = FALSE");
$teacherCount = $conn->affected_rows;
file_put_contents(__DIR__ . '/migrate.log', date('Y-m-d H:i:s') . " - Updated $teacherCount teacher accounts to Teacher@123\n", FILE_APPEND);

// Update all student accounts to Student@123 where password hasn't been changed
$studentResult = $conn->query("UPDATE admin_accounts SET admin_password = 'Student@123', password_changed = FALSE WHERE role = 'student' AND password_changed = FALSE");
$studentCount = $conn->affected_rows;
file_put_contents(__DIR__ . '/migrate.log', date('Y-m-d H:i:s') . " - Updated $studentCount student accounts to Student@123\n", FILE_APPEND);

// Update all admin accounts to Admin@123 where password hasn't been changed
$adminResult = $conn->query("UPDATE admin_accounts SET admin_password = 'Admin@123', password_changed = FALSE WHERE role = 'admin' AND password_changed = FALSE");
$adminCount = $conn->affected_rows;
file_put_contents(__DIR__ . '/migrate.log', date('Y-m-d H:i:s') . " - Updated $adminCount admin accounts to Admin@123\n", FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => 'Migration completed',
    'teachers_updated' => $teacherCount,
    'students_updated' => $studentCount,
    'admins_updated' => $adminCount
]);

$conn->close();
?>
