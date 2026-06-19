<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

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

        if ($password === $row['admin_password']) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
            $_SESSION['admin_role'] = $row['role'];
            $_SESSION['login_time'] = date('Y-m-d H:i:s');
            
            // If the user is a teacher, sync their account to teacher_accounts and get teacher_id
            $teacher_id = null;
            if ($row['role'] === 'teacher') {
                syncTeacherAccount($row['id'], $email, $row['first_name'], $row['last_name']);
                $teacher_id = getTeacherIdByEmail($email);
                $_SESSION['teacher_id'] = $teacher_id;
            }
            
            $response = [
                'status' => 'success',
                'message' => 'Login successful',
                'role' => $row['role'],
                'admin_name' => $_SESSION['admin_name'],
                'admin_id' => $row['id']
            ];
            
            // Add teacher_id for teacher roles
            if ($teacher_id) {
                $response['teacher_id'] = $teacher_id;
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
