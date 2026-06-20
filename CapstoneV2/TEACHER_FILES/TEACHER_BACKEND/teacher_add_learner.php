<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

session_start();
$teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id'])
            : (isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1);

$student_name    = isset($_POST['student_name'])    ? trim($_POST['student_name'])    : '';
$parent_name     = isset($_POST['parent_name'])     ? trim($_POST['parent_name'])     : '';
$parent_email    = isset($_POST['parent_email'])    ? trim($_POST['parent_email'])    : '';
$parent_phone    = isset($_POST['parent_phone'])    ? trim($_POST['parent_phone'])    : '';
$disability_type = isset($_POST['disability_type']) ? trim($_POST['disability_type']) : '';
$grade_level       = isset($_POST['grade_level'])       ? trim($_POST['grade_level'])       : '';
$admin_account_id  = isset($_POST['admin_account_id'])  ? intval($_POST['admin_account_id']) : 0;
$age               = isset($_POST['age'])               ? intval($_POST['age'])             : 0;
$status            = 'active';

if (!$student_name) {
    echo json_encode(['success' => false, 'message' => 'Student name is required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO students (teacher_id, admin_account_id, student_name, parent_name, parent_email, parent_phone, disability_type, grade_level, age, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed']);
    $conn->close();
    exit;
}

$stmt->bind_param("iisssssiis", $teacher_id, $admin_account_id, $student_name, $parent_name, $parent_email, $parent_phone, $disability_type, $grade_level, $age, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student added successfully', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add student: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
