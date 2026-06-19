<?php
require_once __DIR__ . '/../../ADMIN_FILES/ADMIN_BACKEND/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
$activity_title = isset($_POST['activity_title']) ? trim($_POST['activity_title']) : '';
$activity_description = isset($_POST['activity_description']) ? trim($_POST['activity_description']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$grade_level = isset($_POST['grade_level']) ? trim($_POST['grade_level']) : '';
$difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'draft';

if (!$teacher_id || !$activity_title) {
    echo json_encode(['success' => false, 'message' => 'Teacher ID and activity title are required']);
    exit;
}

// Connect to teacher database
$teacher_conn = getTeacherDatabaseConnection();
if (!$teacher_conn) {
    echo json_encode(['success' => false, 'message' => 'Teacher database connection failed']);
    exit;
}

// Insert activity into teacher database
$sql = "INSERT INTO teacher_activities (teacher_id, activity_title, activity_description, subject, grade_level, difficulty, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $teacher_conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit;
}

$stmt->bind_param("issssss", $teacher_id, $activity_title, $activity_description, $subject, $grade_level, $difficulty, $status);

if ($stmt->execute()) {
    $activity_id = $stmt->insert_id;
    
    // Get teacher name from admin database
    $teacher_query = $conn->query("SELECT first_name, last_name FROM admin_accounts WHERE id = $teacher_id");
    $teacher_name = 'Unknown Teacher';
    if ($teacher_query && $row = $teacher_query->fetch_assoc()) {
        $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
    }
    
    // Log activity to admin_activities table
    $logSql = "INSERT INTO admin_activities (activity_type, user_type, user_name, user_email, action_detail) 
               VALUES ('Create Activity', 'teacher', ?, ?, ?)";
    $logStmt = $conn->prepare($logSql);
    if ($logStmt) {
        $actionDetail = "Activity: " . substr($activity_title, 0, 50);
        $teacher_email = ''; // We don't have email from this context, but it's optional
        $logStmt->bind_param("sss", $teacher_name, $teacher_email, $actionDetail);
        $logStmt->execute();
        $logStmt->close();
    }
    
    echo json_encode(['success' => true, 'activity_id' => $activity_id, 'message' => 'Activity created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create activity']);
}

$stmt->close();
$teacher_conn->close();
$conn->close();
?>
