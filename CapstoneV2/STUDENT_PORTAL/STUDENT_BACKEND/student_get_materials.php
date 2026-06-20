<?php
require_once __DIR__ . '/../../TEACHER_FILES/TEACHER_BACKEND/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

$student_record_id = isset($_GET['student_record_id']) ? intval($_GET['student_record_id']) : 0;
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;

if (!$student_record_id || !$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get all activities from teacher, with progress for this student
$stmt = $conn->prepare("
    SELECT a.id, a.activity_title, a.activity_description, a.subject,
           a.grade_level, a.difficulty, a.status AS activity_status, a.created_at,
           lp.score, lp.assessment_date,
           CASE
               WHEN lp.id IS NULL THEN 'new'
               WHEN lp.score >= 80 THEN 'completed'
               ELSE 'in_progress'
           END AS progress_status
    FROM teacher_activities a
    LEFT JOIN learner_progress lp ON lp.activity_id = a.id AND lp.student_id = ?
    WHERE a.teacher_id = ? AND a.status = 'published'
    ORDER BY a.created_at DESC
");
$stmt->bind_param("ii", $student_record_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

$materials = [];
$total = 0;
$completed = 0;
$in_progress = 0;
$new_count = 0;

while ($row = $result->fetch_assoc()) {
    $total++;
    if ($row['progress_status'] === 'completed') $completed++;
    elseif ($row['progress_status'] === 'in_progress') $in_progress++;
    else $new_count++;
    $materials[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'total' => $total,
    'completed' => $completed,
    'in_progress' => $in_progress,
    'new' => $new_count,
    'materials' => $materials
]);
?>
