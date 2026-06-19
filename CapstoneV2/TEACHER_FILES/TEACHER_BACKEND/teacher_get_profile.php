<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../../ADMIN_FILES/ADMIN_BACKEND/db.php';

header('Content-Type: application/json');

// Get teacher_id from POST or SESSION
$teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 
              (isset($_SESSION['teacher_id']) ? intval($_SESSION['teacher_id']) : 1);

$conn = getTeacherDatabaseConnection();
$admin_conn = getDatabaseConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get teacher profile
$sql = "SELECT id, teacher_email, first_name, last_name, school_name, phone_number, specialization, class_section, status 
        FROM teacher_accounts WHERE id=?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query prepare failed: ' . $conn->error]);
    $conn->close();
    if ($admin_conn) $admin_conn->close();
    exit;
}

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $teacher_email = $row['teacher_email'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    
    // Try to get the teacher's name from admin_accounts for consistency
    // This ensures the name shown matches what was set when the account was created
    if ($admin_conn) {
        $admin_sql = "SELECT first_name, last_name FROM admin_accounts WHERE admin_email = ? AND role = 'teacher'";
        $admin_stmt = $admin_conn->prepare($admin_sql);
        if ($admin_stmt) {
            $admin_stmt->bind_param("s", $teacher_email);
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            if ($admin_row = $admin_result->fetch_assoc()) {
                // Use the name from admin_accounts (the official name)
                $first_name = $admin_row['first_name'];
                $last_name = $admin_row['last_name'];
            }
            $admin_stmt->close();
        }
    }
    
    // Build display name from available fields
    $display_name = trim($first_name . ' ' . $last_name) ?: $teacher_email;
    
    // Get first letter for avatar
    $avatar_letter = strtoupper(substr($display_name, 0, 1));
    
    echo json_encode([
        'success' => true,
        'teacher' => [
            'id' => $row['id'],
            'email' => $row['teacher_email'],
            'name' => $display_name,
            'firstName' => $first_name,
            'lastName' => $last_name,
            'schoolName' => $row['school_name'],
            'phone' => $row['phone_number'],
            'specialization' => $row['specialization'],
            'classSection' => $row['class_section'],
            'status' => $row['status'],
            'avatarLetter' => $avatar_letter
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
}

$stmt->close();
$conn->close();
if ($admin_conn) {
    $admin_conn->close();
}
