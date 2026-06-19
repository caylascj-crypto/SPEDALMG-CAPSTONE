<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode([]);
    exit;
}

session_start();
$teacher_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1; // Default to 1 for testing

// Get all students for this teacher
$sql = "SELECT id, student_name, parent_name, parent_email, parent_phone, disability_type, status, age, created_at 
        FROM students 
        WHERE teacher_id = $teacher_id 
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$students = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => $row['id'],
            'name' => $row['student_name'],
            'parent_name' => $row['parent_name'],
            'parent_email' => $row['parent_email'],
            'parent_phone' => $row['parent_phone'],
            'disability' => $row['disability_type'],
            'status' => $row['status'],
            'age' => $row['age'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode($students);
$conn->close();
?>
