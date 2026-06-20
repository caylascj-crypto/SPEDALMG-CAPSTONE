<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

session_start();
$teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id'])
            : (isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1);
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

$progress = [
    'success' => true,
    'student_name' => '',
    'overall_progress' => 0,
    'average_score' => 0,
    'activities_completed' => 0,
    'total_activities' => 0,
    'activity_scores' => [0, 0, 0, 0, 0, 0],
    'activities' => [],
    'skills' => [
        'Cognitive' => 0,
        'Communication' => 0,
        'Fine Motor' => 0,
        'Social Skills' => 0,
        'Self Help' => 0
    ],
    'notes' => [],
    'iep_goals' => []
];

// Get student info
$result = $conn->query("SELECT student_name FROM students WHERE id = $student_id AND teacher_id = $teacher_id");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $progress['student_name'] = $row['student_name'];
} else {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Get completed activities and scores
$result = $conn->query("SELECT lp.score, COUNT(*) as count FROM learner_progress lp 
                        WHERE lp.student_id = $student_id 
                        GROUP BY lp.score");
$scores = [];
$totalScore = 0;
$scoreCount = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $score = $row['score'];
        $count = $row['count'];
        $scores[$score] = $count;
        $totalScore += ($score * $count);
        $scoreCount += $count;
    }
}

$progress['activities_completed'] = $scoreCount;
$progress['average_score'] = $scoreCount > 0 ? round($totalScore / $scoreCount) : 0;
$progress['overall_progress'] = $progress['average_score'];

// Get total activities for this teacher
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_activities WHERE teacher_id = $teacher_id");
if ($result) {
    $row = $result->fetch_assoc();
    $progress['total_activities'] = $row['count'];
}

// Get activity scores for last 6 weeks
$result = $conn->query("SELECT WEEK(lp.assessment_date) as week, AVG(lp.score) as avg_score 
                        FROM learner_progress lp 
                        WHERE lp.student_id = $student_id 
                        AND lp.assessment_date >= DATE_SUB(NOW(), INTERVAL 6 WEEK) 
                        GROUP BY WEEK(lp.assessment_date) 
                        ORDER BY week DESC 
                        LIMIT 6");
$weekScores = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $weekScores[] = round($row['avg_score']);
    }
}
// Reverse to get chronological order (oldest to newest)
$weekScores = array_reverse($weekScores);
// Pad with zeros if needed
while (count($weekScores) < 6) {
    array_unshift($weekScores, 0);
}
$progress['activity_scores'] = array_slice($weekScores, -6);

// Get recent activities with status
$result = $conn->query("SELECT ta.id, ta.activity_title, lp.score, lp.assessment_date, 
                        CASE 
                            WHEN lp.assessment_date IS NOT NULL THEN 'completed'
                            ELSE 'not-started'
                        END as status
                        FROM teacher_activities ta
                        LEFT JOIN learner_progress lp ON ta.id = lp.activity_id AND lp.student_id = $student_id
                        WHERE ta.teacher_id = $teacher_id
                        ORDER BY ta.created_at DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $scoreColor = '#CBD5E1';
        $score = $row['score'];
        if ($score >= 80) $scoreColor = '#10b981';
        elseif ($score >= 60) $scoreColor = '#f59e0b';
        elseif ($score > 0) $scoreColor = '#ef4444';
        
        $progress['activities'][] = [
            'id' => $row['id'],
            'name' => $row['activity_title'],
            'date' => $row['assessment_date'] ?: date('Y-m-d'),
            'score' => $score ?: '—',
            'score_color' => $scoreColor,
            'status' => $row['status']
        ];
    }
}

// Calculate skills (based on activity categories)
$result = $conn->query("SELECT ta.subject, AVG(lp.score) as avg_score
                        FROM learner_progress lp
                        LEFT JOIN teacher_activities ta ON lp.activity_id = ta.id
                        WHERE lp.student_id = $student_id
                        GROUP BY ta.subject");
$skillMap = [
    'cognitive' => 'Cognitive',
    'communication' => 'Communication',
    'motor' => 'Fine Motor',
    'social' => 'Social Skills',
    'self-help' => 'Self Help'
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subject = strtolower($row['subject']);
        foreach ($skillMap as $key => $skillName) {
            if (strpos($subject, $key) !== false) {
                $progress['skills'][$skillName] = round($row['avg_score']);
                break;
            }
        }
    }
}

// Get IEP goals
$result = $conn->query("SELECT iep_goal FROM iep_materials WHERE student_id = $student_id AND teacher_id = $teacher_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $progress['iep_goals'][] = $row['iep_goal'];
    }
}

// Load notes from student_notes table
$notes_stmt = $conn->prepare("SELECT id, note, created_at FROM student_notes WHERE teacher_id=? AND student_id=? ORDER BY created_at DESC LIMIT 20");
$notes_stmt->bind_param("ii", $teacher_id, $student_id);
$notes_stmt->execute();
$notes_res = $notes_stmt->get_result();
$progress['notes'] = [];
while ($n = $notes_res->fetch_assoc()) {
    $progress['notes'][] = $n;
}
$notes_stmt->close();

echo json_encode($progress);
$conn->close();
?>
