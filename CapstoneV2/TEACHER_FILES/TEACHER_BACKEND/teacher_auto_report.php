<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
if (!$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'teacher_id required']);
    $conn->close();
    exit;
}

// Per-student summary with activity breakdown
$stmt = $conn->prepare("
    SELECT
        s.id                             AS student_id,
        s.student_name,
        s.grade_level,
        s.disability_type,
        COUNT(lp.id)                     AS total_attempts,
        COALESCE(AVG(lp.score), 0)       AS avg_score,
        COALESCE(MAX(lp.score), 0)       AS highest_score,
        COALESCE(MIN(lp.score), 0)       AS lowest_score,
        SUM(CASE WHEN lp.score >= 80 THEN 1 ELSE 0 END) AS passed_count,
        MAX(lp.assessment_date)          AS last_activity_date
    FROM students s
    LEFT JOIN learner_progress lp
           ON lp.student_id = s.id AND lp.teacher_id = ?
    WHERE s.teacher_id = ? AND s.status = 'active'
    GROUP BY s.id, s.student_name, s.grade_level, s.disability_type
    ORDER BY avg_score DESC
");
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$rows = $stmt->get_result();

$students = [];
$total_scores = [];

while ($r = $rows->fetch_assoc()) {
    $avg = round((float)$r['avg_score'], 1);
    $status = $r['total_attempts'] == 0 ? 'No Activity'
            : ($avg >= 80 ? 'Passing' : ($avg >= 60 ? 'Needs Support' : 'At Risk'));
    $students[] = [
        'student_id'        => $r['student_id'],
        'student_name'      => $r['student_name'],
        'grade_level'       => $r['grade_level'],
        'disability_type'   => $r['disability_type'],
        'total_attempts'    => (int)$r['total_attempts'],
        'avg_score'         => $avg,
        'highest_score'     => (int)$r['highest_score'],
        'lowest_score'      => (int)$r['lowest_score'],
        'passed_count'      => (int)$r['passed_count'],
        'last_activity_date'=> $r['last_activity_date'],
        'status'            => $status,
    ];
    if ($r['total_attempts'] > 0) $total_scores[] = $avg;
}
$stmt->close();

// Activity completion rates
$stmt2 = $conn->prepare("
    SELECT
        a.id, a.activity_title, a.activity_type, a.created_at,
        COUNT(lp.id)                     AS submissions,
        COALESCE(AVG(lp.score), 0)       AS avg_score,
        SUM(CASE WHEN lp.score >= 80 THEN 1 ELSE 0 END) AS passed_count
    FROM teacher_activities a
    LEFT JOIN learner_progress lp ON lp.activity_id = a.id AND lp.teacher_id = ?
    WHERE a.teacher_id = ? AND a.status = 'published'
    GROUP BY a.id, a.activity_title, a.activity_type, a.created_at
    ORDER BY a.created_at DESC
");
$stmt2->bind_param("ii", $teacher_id, $teacher_id);
$stmt2->execute();
$act_rows = $stmt2->get_result();

$activities = [];
while ($ar = $act_rows->fetch_assoc()) {
    $activities[] = [
        'activity_id'    => (int)$ar['id'],
        'activity_title' => $ar['activity_title'],
        'activity_type'  => $ar['activity_type'],
        'submissions'    => (int)$ar['submissions'],
        'avg_score'      => round((float)$ar['avg_score'], 1),
        'passed_count'   => (int)$ar['passed_count'],
        'created_at'     => $ar['created_at'],
    ];
}
$stmt2->close();

// Class overview
$class_avg = count($total_scores) > 0 ? round(array_sum($total_scores) / count($total_scores), 1) : 0;
$passing   = count(array_filter($students, fn($s) => $s['status'] === 'Passing'));
$at_risk   = count(array_filter($students, fn($s) => $s['status'] === 'At Risk'));
$no_act    = count(array_filter($students, fn($s) => $s['status'] === 'No Activity'));

// Recent activity log (last 30 days)
$stmt3 = $conn->prepare("
    SELECT lp.score, lp.assessment_date, lp.created_at,
           s.student_name, a.activity_title, a.activity_type
    FROM learner_progress lp
    JOIN students s          ON s.id = lp.student_id
    JOIN teacher_activities a ON a.id = lp.activity_id
    WHERE lp.teacher_id = ? AND lp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY lp.created_at DESC
    LIMIT 20
");
$stmt3->bind_param("i", $teacher_id);
$stmt3->execute();
$log_rows = $stmt3->get_result();
$recent_log = [];
while ($lr = $log_rows->fetch_assoc()) {
    $recent_log[] = $lr;
}
$stmt3->close();
$conn->close();

echo json_encode([
    'success'      => true,
    'generated_at' => date('Y-m-d H:i:s'),
    'overview' => [
        'class_avg'       => $class_avg,
        'total_students'  => count($students),
        'passing'         => $passing,
        'at_risk'         => $at_risk,
        'no_activity'     => $no_act,
        'total_activities'=> count($activities),
    ],
    'students'   => $students,
    'activities' => $activities,
    'recent_log' => $recent_log,
]);
?>
