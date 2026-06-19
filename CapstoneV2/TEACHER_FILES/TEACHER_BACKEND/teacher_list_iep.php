<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode([]);
    exit;
}

session_start();
$teacher_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1;

$sql = "SELECT id, student_id, iep_goal, learning_objective, strategies, status, created_at 
        FROM iep_materials 
        WHERE teacher_id = $teacher_id 
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$ieps = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get student name
        $student_result = $conn->query("SELECT student_name FROM students WHERE id = " . $row['student_id']);
        $student_name = '';
        if ($student_result && $student_result->num_rows > 0) {
            $student_row = $student_result->fetch_assoc();
            $student_name = $student_row['student_name'];
        }

        $ieps[] = [
            'id' => $row['id'],
            'student_id' => $row['student_id'],
            'student_name' => $student_name,
            'goal' => $row['iep_goal'],
            'objective' => $row['learning_objective'],
            'strategies' => $row['strategies'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode($ieps);
$conn->close();
?>
