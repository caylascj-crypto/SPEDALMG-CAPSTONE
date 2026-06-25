<?php
error_reporting(0);
ini_set('display_errors', '0');

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
    exit;
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Fatal error',
            'details' => $err['message'],
            'file' => $err['file'],
            'line' => $err['line']
        ]);
    }
});


// Function to sync teacher account to teacher_accounts table
function syncTeacherAccount($admin_id, $email, $first_name, $last_name) {
    require_once __DIR__ . '/../../TEACHER_FILES/TEACHER_BACKEND/db.php';
    $teacher_conn = getTeacherDatabaseConnection();
    
    if (!$teacher_conn) {
        return false;
    }
    
    // Check if teacher exists in teacher_accounts
    $check_stmt = $teacher_conn->prepare("SELECT id FROM teacher_accounts WHERE teacher_email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        // Create new teacher_accounts entry with the correct name from admin_accounts
        $insert_stmt = $teacher_conn->prepare("INSERT INTO teacher_accounts (teacher_email, teacher_password, first_name, last_name, school_name, status) VALUES (?, ?, ?, ?, 'Mamatid Elementary School', 'active')");
        $password = 'Teacher@123'; // Default password for teachers
        $insert_stmt->bind_param("ssss", $email, $password, $first_name, $last_name);
        $insert_stmt->execute();
        $insert_stmt->close();
    } else {
        // Update existing teacher_accounts entry with the latest name from admin_accounts
        $update_stmt = $teacher_conn->prepare("UPDATE teacher_accounts SET first_name = ?, last_name = ? WHERE teacher_email = ?");
        $update_stmt->bind_param("sss", $first_name, $last_name, $email);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    $check_stmt->close();
    $teacher_conn->close();
    return true;
}

// Function to get student record from teacher DB
function getStudentRecord($admin_account_id) {
    require_once __DIR__ . '/../../TEACHER_FILES/TEACHER_BACKEND/db.php';
    $conn = getTeacherDatabaseConnection();
    if (!$conn) return null;

    $stmt = $conn->prepare("SELECT s.id AS student_record_id, s.teacher_id, s.disability_type, s.grade_level, s.student_name FROM students s WHERE s.admin_account_id = ? AND s.status = 'active' LIMIT 1");
    if (!$stmt) { $conn->close(); return null; }
    $stmt->bind_param("i", $admin_account_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row;
}

// Function to get teacher_id from teacher_accounts
function getTeacherIdByEmail($email) {
    require_once __DIR__ . '/../../TEACHER_FILES/TEACHER_BACKEND/db.php';
    $teacher_conn = getTeacherDatabaseConnection();
    
    if (!$teacher_conn) {
        return null;
    }
    
    $stmt = $teacher_conn->prepare("SELECT id FROM teacher_accounts WHERE teacher_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teacher_id = null;
    if ($row = $result->fetch_assoc()) {
        $teacher_id = $row['id'];
    }
    
    $stmt->close();
    $teacher_conn->close();
    return $teacher_id;
}

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Handle POST login request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
    $password = isset($_POST['admin_password']) ? trim($_POST['admin_password']) : '';

    if ($email === '' || $password === '') {
        echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("SELECT id, admin_password, first_name, last_name, role FROM admin_accounts WHERE admin_email = ? AND status = 'active'");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        $conn->close();
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();


    if ($result && $result->num_rows > 0) {

        $row = $result->fetch_assoc();

        // Debug: tell frontend what it received (without exposing password)
        // Note: admin_password is only compared server-side.
        $expected_pw = $row['admin_password'];

        if ($password === $expected_pw) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
            $_SESSION['admin_role'] = $row['role'];
            $_SESSION['login_time'] = date('Y-m-d H:i:s');

            // Update last_login — marks teacher/admin as active
            $upd = $conn->prepare("UPDATE admin_accounts SET last_login = NOW() WHERE id = ?");
            if ($upd) { $upd->bind_param("i", $row['id']); $upd->execute(); $upd->close(); }
            
            $teacher_id = null;
            $student_record = null;

            if ($row['role'] === 'teacher') {
                syncTeacherAccount($row['id'], $email, $row['first_name'], $row['last_name']);
                $teacher_id = getTeacherIdByEmail($email);
                $_SESSION['teacher_id'] = $teacher_id;
            } elseif ($row['role'] === 'student') {
                $student_record = getStudentRecord($row['id']);
            }

            $response = [
                'status' => 'success',
                'message' => 'Login successful',
                'role' => $row['role'],
                'admin_name' => $_SESSION['admin_name'],
                'admin_id' => $row['id']
            ];

            if ($teacher_id) {
                $response['teacher_id'] = $teacher_id;
            }

            if ($student_record) {
                $response['student_record_id'] = $student_record['student_record_id'];
                $response['teacher_id'] = $student_record['teacher_id'];
                $response['student_condition'] = $student_record['disability_type'];
                $response['student_grade'] = $student_record['grade_level'];
            }
            
            echo json_encode($response);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

    $stmt->close();
}

$conn->close();
