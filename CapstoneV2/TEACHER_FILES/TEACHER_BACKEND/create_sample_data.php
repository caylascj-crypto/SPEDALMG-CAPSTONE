<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../../ADMIN_FILES/ADMIN_BACKEND/db.php';

header('Content-Type: application/json');

// Get database connections
$admin_conn = getDatabaseConnection();
$teacher_conn = getTeacherDatabaseConnection();

if (!$admin_conn || !$teacher_conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Step 1: Check if teacher Hydee exists in teacher_accounts, if not create them
$teacher_email = 'hydee@spedalm.edu.ph';
$teacher_check = $teacher_conn->query("SELECT id, first_name, last_name FROM teacher_accounts WHERE teacher_email = '$teacher_email'");
$teacher_id = null;
$teacher_name = 'Hydee';

if ($teacher_check && $teacher_check->num_rows > 0) {
    $teacher_row = $teacher_check->fetch_assoc();
    $teacher_id = $teacher_row['id'];
    $teacher_name = $teacher_row['first_name'] . ' ' . $teacher_row['last_name'];
} else {
    // Create teacher account in teacher_accounts
    $insert_teacher = $teacher_conn->query("INSERT INTO teacher_accounts (teacher_email, teacher_password, first_name, last_name, school_name, status) 
        VALUES ('$teacher_email', 'Hydee@123', 'Hydee', 'Test', 'Mamatid Elementary School', 'active')");
    
    if ($insert_teacher) {
        $teacher_id = $teacher_conn->insert_id;
        $teacher_name = 'Hydee Test';
    }
}

if (!$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'Failed to get/create teacher']);
    exit;
}

// Step 2: Check if student asd exists, if not create them
$student_name = 'asd';
$student_check = $teacher_conn->query("SELECT id FROM students WHERE student_name = '$student_name' AND teacher_id = $teacher_id");
$student_id = null;

if ($student_check && $student_check->num_rows > 0) {
    $student_row = $student_check->fetch_assoc();
    $student_id = $student_row['id'];
} else {
    // Create student
    $insert_student = $teacher_conn->query("INSERT INTO students (teacher_id, student_name, grade_level, status) 
        VALUES ($teacher_id, '$student_name', '3', 'active')");
    
    if ($insert_student) {
        $student_id = $teacher_conn->insert_id;
    }
}

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Failed to get/create student']);
    exit;
}

// Step 3: Check if sample activity already exists
$activity_title = 'English Vocabulary Builder';
$activity_check = $teacher_conn->query("SELECT id FROM teacher_activities WHERE activity_title = '$activity_title' AND teacher_id = $teacher_id");
$activity_id = null;

if ($activity_check && $activity_check->num_rows > 0) {
    $activity_row = $activity_check->fetch_assoc();
    $activity_id = $activity_row['id'];
} else {
    // Create sample activity
    $activity_description = 'Learn common English vocabulary words with interactive exercises and quizzes.';
    $insert_activity = $teacher_conn->query("INSERT INTO teacher_activities (teacher_id, activity_title, activity_description, subject, grade_level, difficulty, status) 
        VALUES ($teacher_id, '$activity_title', '$activity_description', 'English', '3', 'medium', 'published')");
    
    if ($insert_activity) {
        $activity_id = $teacher_conn->insert_id;
    }
}

if (!$activity_id) {
    echo json_encode(['success' => false, 'message' => 'Failed to get/create activity']);
    exit;
}

// Step 4: Check if learner progress exists for this student and activity
$progress_check = $teacher_conn->query("SELECT id FROM learner_progress WHERE student_id = $student_id AND activity_id = $activity_id AND teacher_id = $teacher_id");

if (!$progress_check || $progress_check->num_rows == 0) {
    // Create progress record (student completed activity)
    $teacher_conn->query("INSERT INTO learner_progress (teacher_id, student_id, activity_id, score, notes, assessment_date) 
        VALUES ($teacher_id, $student_id, $activity_id, 85, 'Good progress on vocabulary', CURDATE())");
}

// Step 5: Log the activity to admin_activities table
// Get teacher name from admin_accounts for matching
$admin_teacher_check = $admin_conn->query("SELECT id FROM admin_accounts WHERE role = 'teacher' AND first_name = 'Hydee' LIMIT 1");

if ($admin_teacher_check && $admin_teacher_check->num_rows > 0) {
    $admin_log = $admin_conn->query("INSERT INTO admin_activities (activity_type, user_type, user_name, user_email, action_detail) 
        VALUES ('Create Activity', 'teacher', '$teacher_name', '$teacher_email', 'Activity: $activity_title')");
}

echo json_encode(['success' => true, 'message' => 'Sample data created successfully', 'teacher_id' => $teacher_id, 'student_id' => $student_id, 'activity_id' => $activity_id]);

$admin_conn->close();
$teacher_conn->close();
?>
