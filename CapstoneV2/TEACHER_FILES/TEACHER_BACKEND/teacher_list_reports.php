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

$sql = "SELECT id, student_id, report_title, report_type, report_date, created_at 
        FROM teacher_reports 
        WHERE teacher_id = $teacher_id 
        ORDER BY report_date DESC LIMIT 50";

$result = $conn->query($sql);
$reports = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get student name
        $student_result = $conn->query("SELECT student_name FROM students WHERE id = " . $row['student_id']);
        $student_name = '';
        if ($student_result && $student_result->num_rows > 0) {
            $student_row = $student_result->fetch_assoc();
            $student_name = $student_row['student_name'];
        }

        $reports[] = [
            'id' => $row['id'],
            'student_id' => $row['student_id'],
            'student_name' => $student_name,
            'title' => $row['report_title'],
            'type' => $row['report_type'],
            'date' => $row['report_date'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode($reports);
$conn->close();
?>
