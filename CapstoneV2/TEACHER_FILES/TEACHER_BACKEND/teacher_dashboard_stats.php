<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id']) : 1;

$stats = [
    'assigned_learners'   => 0,
    'active_learners'     => 0,
    'total_activities'    => 0,
    'draft_activities'    => 0,
    'published_activities'=> 0,
    'recent_activities'   => [],
    'today_tasks'         => [],
    'learning_plan'       => []
];

// All count queries share the same $teacher_id — use one prepared helper
$countQueries = [
    ['assigned_learners',    "SELECT COUNT(*) as c FROM students WHERE teacher_id=?",                                                     'i'],
    ['active_learners',      "SELECT COUNT(*) as c FROM students WHERE teacher_id=? AND status='active'",                                 'i'],
    ['total_activities',     "SELECT COUNT(*) as c FROM teacher_activities WHERE teacher_id=?",                                           'i'],
    ['draft_activities',     "SELECT COUNT(*) as c FROM teacher_activities WHERE teacher_id=? AND status='draft'",                        'i'],
    ['published_activities', "SELECT COUNT(*) as c FROM teacher_activities WHERE teacher_id=? AND status='published'",                    'i'],
];

foreach ($countQueries as [$key, $sql, $types]) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, $teacher_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stats[$key] = (int)($row['c'] ?? 0);
    $stmt->close();
}

// Recent activities (last 5)
$stmt = $conn->prepare("SELECT id, activity_title, created_at FROM teacher_activities WHERE teacher_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $stats['recent_activities'][] = ['id' => $row['id'], 'title' => $row['activity_title'], 'date' => $row['created_at']];
}
$stmt->close();

$stats['learning_plan'] = [
    'first_grading'  => ['Cognitive Development', 'Communication Skills', 'Fine Motor Skills', 'Attention & Routine'],
    'second_grading' => ['Social Skills', 'Emotional Regulation', 'Gross Motor Skills', 'Behavior Support'],
    'third_grading'  => ['Life Skills', 'Self-Help Skills', 'Functional Communication', 'Evaluation & Progress Monitoring']
];

$stats['today_tasks'] = [];

echo json_encode($stats);
$conn->close();
