<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check and add phone_number column if it doesn't exist
$checkPhone = $conn->query("SHOW COLUMNS FROM admin_accounts LIKE 'phone_number'");
if ($checkPhone && $checkPhone->num_rows == 0) {
    $addResult = $conn->query("ALTER TABLE admin_accounts ADD COLUMN phone_number VARCHAR(20) AFTER school_name");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Log POST data for debugging
file_put_contents(__DIR__ . '/debug_post.log', date('Y-m-d H:i:s') . " - POST Data: " . json_encode($_POST) . "\n", FILE_APPEND);

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email_address = isset($_POST['email_address']) ? trim($_POST['email_address']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$status = isset($_POST['admin_status_input']) ? trim($_POST['admin_status_input']) : 'active';
$condition_info = isset($_POST['admin_edit_cond_select']) ? trim($_POST['admin_edit_cond_select']) : '';

// Parse full name into first and last name
$name_parts = explode(' ', $full_name, 2);
$first_name = $name_parts[0];
$last_name = isset($name_parts[1]) ? $name_parts[1] : '';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Get the user's role before updating
$role_check = $conn->prepare("SELECT role FROM admin_accounts WHERE id = ?");
$role_check->bind_param("i", $user_id);
$role_check->execute();
$role_result = $role_check->get_result();
$user_role = '';
if ($role_row = $role_result->fetch_assoc()) {
    $user_role = $role_row['role'];
}
$role_check->close();

// Update the user account - try different approaches
$sql = "UPDATE admin_accounts SET first_name = ?, last_name = ?, admin_email = ?, phone_number = ?, status = ?, condition_info = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // If phone_number column still doesn't exist, try without it
    $sql = "UPDATE admin_accounts SET first_name = ?, last_name = ?, admin_email = ?, status = ?, condition_info = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
        exit;
    }
    
    $stmt->bind_param("ssssi", $first_name, $last_name, $email_address, $status, $condition_info, $user_id);
} else {
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email_address, $phone_number, $status, $condition_info, $user_id);
}

if ($stmt->execute()) {
    file_put_contents(__DIR__ . '/debug_post.log', date('Y-m-d H:i:s') . " - UPDATE SUCCESS for user $user_id. Rows affected: " . $stmt->affected_rows . "\n", FILE_APPEND);
    
    // If the user is a teacher, sync to teacher_accounts
    if ($user_role === 'teacher') {
        syncTeacherAccount($email_address, $first_name, $last_name);
    }
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} else {
    file_put_contents(__DIR__ . '/debug_post.log', date('Y-m-d H:i:s') . " - UPDATE FAILED for user $user_id. Error: " . $stmt->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
$conn->close();

// Function to sync teacher to teacher_accounts table
function syncTeacherAccount($email, $firstName, $lastName) {
    require_once __DIR__ . '/../../TEACHER_FILES/TEACHER_BACKEND/db.php';
    $teacher_conn = getTeacherDatabaseConnection();
    
    if (!$teacher_conn) {
        return false;
    }
    
    // Check if teacher exists
    $check_stmt = $teacher_conn->prepare("SELECT id FROM teacher_accounts WHERE teacher_email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        // Create new teacher account
        $insert_stmt = $teacher_conn->prepare("INSERT INTO teacher_accounts (teacher_email, teacher_password, first_name, last_name, school_name, status) VALUES (?, ?, ?, ?, 'Mamatid Elementary School', 'active')");
        $password = 'Temp@1234';
        $insert_stmt->bind_param("ssss", $email, $password, $firstName, $lastName);
        $insert_stmt->execute();
        $insert_stmt->close();
    } else {
        // Update existing teacher account with latest name
        $update_stmt = $teacher_conn->prepare("UPDATE teacher_accounts SET first_name = ?, last_name = ? WHERE teacher_email = ?");
        $update_stmt->bind_param("sss", $firstName, $lastName, $email);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    $check_stmt->close();
    $teacher_conn->close();
    return true;
}
