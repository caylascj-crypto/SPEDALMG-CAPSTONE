<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../../ADMIN_FILES/ADMIN_BACKEND/db.php';

header('Content-Type: application/json');

session_start();
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id'])
            : (isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1);

$teacher_conn = getTeacherDatabaseConnection();
$admin_conn   = getDatabaseConnection();

if (!$teacher_conn || !$admin_conn) { echo json_encode([]); exit; }

// Names already enrolled by this teacher
$enrolled = [];
$res = $teacher_conn->query("SELECT LOWER(TRIM(student_name)) as sn FROM students WHERE teacher_id=$teacher_id");
if ($res) { while ($r = $res->fetch_assoc()) $enrolled[] = $r['sn']; }

// All active student accounts from admin
$result = $admin_conn->query("SELECT id, admin_email, first_name, last_name, condition_info
                               FROM admin_accounts
                               WHERE role='student' AND status='active'
                               ORDER BY first_name, last_name");

$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fn = trim($row['first_name'] ?: '');
        $ln = trim($row['last_name'] ?: '');
        $full = trim("$fn $ln");
        if (!$full) $full = explode('@', $row['admin_email'])[0];

        $students[] = [
            'admin_id'   => $row['id'],
            'email'      => $row['admin_email'],
            'first_name' => $fn,
            'last_name'  => $ln,
            'full_name'  => $full,
            'condition'  => $row['condition_info'] ?: '',
            'enrolled'   => in_array(strtolower($full), $enrolled)
        ];
    }
}

echo json_encode($students);
$teacher_conn->close();
$admin_conn->close();
?>
