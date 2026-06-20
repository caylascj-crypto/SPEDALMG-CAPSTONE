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

$notifications = [];

// Teacher notes become "message from teacher" notifications
$stmt = $conn->prepare("
    SELECT n.note, n.created_at,
           CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
    FROM student_notes n
    LEFT JOIN teacher_accounts t ON t.id = n.teacher_id
    WHERE n.teacher_id = ? AND n.student_id = ?
    ORDER BY n.created_at DESC
    LIMIT 20
");
$stmt->bind_param("ii", $teacher_id, $student_record_id);
$stmt->execute();
$notes = $stmt->get_result();
while ($row = $notes->fetch_assoc()) {
    $notifications[] = [
        'type' => 'message',
        'icon_class' => 'student-notif-icon--purple',
        'icon' => '💬',
        'title' => 'Note from ' . htmlspecialchars($row['teacher_name']),
        'text' => htmlspecialchars($row['note']),
        'time' => $row['created_at'],
        'read' => false
    ];
}
$stmt->close();

// New activities from teacher = activity notifications
$stmt2 = $conn->prepare("
    SELECT a.activity_title, a.created_at
    FROM teacher_activities a
    LEFT JOIN learner_progress lp ON lp.activity_id = a.id AND lp.student_id = ?
    WHERE a.teacher_id = ? AND a.status = 'published' AND lp.id IS NULL
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt2->bind_param("ii", $student_record_id, $teacher_id);
$stmt2->execute();
$acts = $stmt2->get_result();
while ($row = $acts->fetch_assoc()) {
    $notifications[] = [
        'type' => 'activity',
        'icon_class' => 'student-notif-icon--blue',
        'icon' => '📚',
        'title' => 'New activity available',
        'text' => 'Your teacher added "' . htmlspecialchars($row['activity_title']) . '" to your materials.',
        'time' => $row['created_at'],
        'read' => false
    ];
}
$stmt2->close();
$conn->close();

// Sort by time descending
usort($notifications, fn($a, $b) => strcmp($b['time'], $a['time']));

$unread = count(array_filter($notifications, fn($n) => !$n['read']));

echo json_encode([
    'success' => true,
    'unread' => $unread,
    'notifications' => $notifications
]);
?>
