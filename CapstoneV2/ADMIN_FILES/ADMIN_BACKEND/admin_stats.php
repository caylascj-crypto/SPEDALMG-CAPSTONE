<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['total_users' => 0, 'teachers' => 0, 'students' => 0, 'active_accounts' => 0]);
    exit;
}

// Total users and teacher counts from admin_accounts
$result = $conn->query("SELECT
    COUNT(*) AS total_users,
    SUM(role = 'teacher') AS teachers,
    SUM(role = 'teacher' AND last_login IS NOT NULL) AS active_teachers,
    SUM(role = 'student') AS student_accounts,
    SUM(last_login IS NOT NULL) AS active_accounts
FROM admin_accounts WHERE status = 'active'");

if (!$result) {
    echo json_encode(['total_users' => 0, 'teachers' => 0, 'active_teachers' => 0, 'students' => 0, 'active_students' => 0]);
    $conn->close();
    exit;
}

$stats = $result->fetch_assoc();
$result->close();

// Student counts from teacher_accounts database
require_once __DIR__ . '/../../TEACHER_FILES/TEACHER_BACKEND/db.php';
$tconn = getTeacherDatabaseConnection();
$stats['students'] = 0;
$stats['active_students'] = 0;
if ($tconn) {
    // Total students
    $sr = $tconn->query("SELECT COUNT(*) AS total FROM students");
    if ($sr) { $stats['students'] = $sr->fetch_assoc()['total']; $sr->close(); }

    // Active students = those with at least 1 entry in learner_progress
    $ar = $tconn->query("SELECT COUNT(DISTINCT student_id) AS active FROM learner_progress");
    if ($ar) { $stats['active_students'] = $ar->fetch_assoc()['active']; $ar->close(); }
    $tconn->close();
}

$conn->close();
echo json_encode($stats);