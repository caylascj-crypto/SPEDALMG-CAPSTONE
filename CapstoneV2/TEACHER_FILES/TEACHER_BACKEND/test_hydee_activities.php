<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check teacher_id = 1 (Hydee)
$sql = "SELECT id, activity_title, activity_description, subject, grade_level, difficulty, status, created_at 
        FROM teacher_activities 
        WHERE teacher_id = 1
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

// Also check teacher account
$teacher_query = $conn->query("SELECT id, first_name, last_name FROM teacher_accounts WHERE id = 1");
$teacher_info = [];
if ($teacher_query && $row = $teacher_query->fetch_assoc()) {
    $teacher_info = [
        'id' => $row['id'],
        'name' => $row['first_name'] . ' ' . $row['last_name']
    ];
}

echo json_encode([
    'success' => true,
    'teacher' => $teacher_info,
    'activities' => $activities,
    'count' => count($activities)
]);

$conn->close();
?>
