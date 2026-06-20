<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

session_start();
$teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id'])
            : (isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1);

$stats = [
    'total_students' => 0,
    'active_students' => 0,
    'total_activities' => 0,
    'assigned_learners' => 0
];

// Get total students
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE teacher_id = $teacher_id");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_students'] = $row['count'];
}

// Get active students
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE teacher_id = $teacher_id AND status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['active_students'] = $row['count'];
}

// Get total activities
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_activities WHERE teacher_id = $teacher_id");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_activities'] = $row['count'];
}

// Assigned learners (same as total students for this context)
$stats['assigned_learners'] = $stats['total_students'];

echo json_encode($stats);
$conn->close();
?>
