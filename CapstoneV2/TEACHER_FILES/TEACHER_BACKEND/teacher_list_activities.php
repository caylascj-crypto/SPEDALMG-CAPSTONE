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

$sql = "SELECT id, activity_title, activity_description, subject, grade_level, difficulty, status, created_at 
        FROM teacher_activities 
        WHERE teacher_id = $teacher_id 
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$activities = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'id' => $row['id'],
            'title' => $row['activity_title'],
            'description' => $row['activity_description'],
            'subject' => $row['subject'],
            'grade_level' => $row['grade_level'],
            'difficulty' => $row['difficulty'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode($activities);
$conn->close();
?>
