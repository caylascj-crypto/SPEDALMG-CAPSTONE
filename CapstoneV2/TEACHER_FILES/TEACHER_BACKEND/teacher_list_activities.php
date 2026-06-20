<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode([]);
    exit;
}

session_start();
$teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id'])
            : (isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1);

$single_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($single_id) {
    $sql = "SELECT id, activity_title, activity_description, subject, grade_level, difficulty,
                   learning_materials, instructions, status, created_at
            FROM teacher_activities
            WHERE teacher_id = $teacher_id AND id = $single_id
            LIMIT 1";
    $result = $conn->query($sql);
    $activity = null;
    if ($result && $row = $result->fetch_assoc()) {
        $activity = [
            'id'                 => $row['id'],
            'title'              => $row['activity_title'],
            'description'        => $row['activity_description'],
            'subject'            => $row['subject'],
            'grade_level'        => $row['grade_level'],
            'difficulty'         => $row['difficulty'],
            'learning_materials' => $row['learning_materials'],
            'instructions'       => $row['instructions'],
            'status'             => $row['status'],
            'created_at'         => $row['created_at']
        ];
    }
    echo json_encode($activity);
} else {
    $sql = "SELECT id, activity_title, activity_description, subject, grade_level, difficulty, status, created_at
            FROM teacher_activities
            WHERE teacher_id = $teacher_id
            ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $activities = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'id'          => $row['id'],
                'title'       => $row['activity_title'],
                'description' => $row['activity_description'],
                'subject'     => $row['subject'],
                'grade_level' => $row['grade_level'],
                'difficulty'  => $row['difficulty'],
                'status'      => $row['status'],
                'created_at'  => $row['created_at']
            ];
        }
    }
    echo json_encode($activities);
}
$conn->close();
?>
