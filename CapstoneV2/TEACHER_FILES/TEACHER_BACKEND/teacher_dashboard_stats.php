<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

session_start();
$teacher_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1;

$stats = [
    'assigned_learners' => 0,
    'active_learners' => 0,
    'total_activities' => 0,
    'draft_activities' => 0,
    'published_activities' => 0,
    'recent_activities' => [],
    'today_tasks' => [],
    'learning_plan' => []
];

// Assigned learners
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE teacher_id = $teacher_id");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['assigned_learners'] = $row['count'];
}

// Active learners
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE teacher_id = $teacher_id AND status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['active_learners'] = $row['count'];
}

// Total activities
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_activities WHERE teacher_id = $teacher_id");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_activities'] = $row['count'];
}

// Draft activities
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_activities WHERE teacher_id = $teacher_id AND status = 'draft'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['draft_activities'] = $row['count'];
}

// Published activities
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_activities WHERE teacher_id = $teacher_id AND status = 'published'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['published_activities'] = $row['count'];
}

// Recent activities (last 5)
$result = $conn->query("SELECT id, activity_title, created_at FROM teacher_activities WHERE teacher_id = $teacher_id ORDER BY created_at DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats['recent_activities'][] = [
            'id' => $row['id'],
            'title' => $row['activity_title'],
            'date' => $row['created_at']
        ];
    }
}

// Learning plan (sample)
$stats['learning_plan'] = [
    'first_grading' => ['Cognitive Development', 'Communication Skills', 'Fine Motor Skills', 'Attention & Routine'],
    'second_grading' => ['Social Skills', 'Emotional Regulation', 'Gross Motor Skills', 'Behavior Support'],
    'third_grading' => ['Life Skills', 'Self-Help Skills', 'Functional Communication', 'Evaluation & Progress Monitoring']
];

// Today's tasks (sample - should be from database if needed)
$stats['today_tasks'] = [];

echo json_encode($stats);
$conn->close();
?>
