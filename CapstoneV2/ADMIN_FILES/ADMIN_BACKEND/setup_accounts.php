<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Define accounts to ensure exist
$accounts = [
    [
        'email' => 'admin@spedalm.edu.ph',
        'password' => 'Admin@123',
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'admin',
        'school_name' => 'Mamatid Elementary School'
    ],
    [
        'email' => 'caylas@spedalm.edu.ph',
        'password' => 'Teacher@123',
        'first_name' => 'Caylas',
        'last_name' => 'Santos',
        'role' => 'teacher',
        'school_name' => 'Mamatid Elementary School'
    ],
    [
        'email' => 'jay@spedalm.edu.ph',
        'password' => 'Teacher@123',
        'first_name' => 'Jay',
        'last_name' => 'Caylas',
        'role' => 'teacher',
        'school_name' => 'Mamatid Elementary School'
    ],
    [
        'email' => 'hydee@spedalm.edu.ph',
        'password' => 'Teacher@123',
        'first_name' => 'Hydee',
        'last_name' => 'Santos',
        'role' => 'teacher',
        'school_name' => 'Mamatid Elementary School'
    ]
];

$added = [];
$already_exist = [];

foreach ($accounts as $account) {
    // Check if account exists
    $check_stmt = $conn->prepare("SELECT id FROM admin_accounts WHERE admin_email = ?");
    $check_stmt->bind_param("s", $account['email']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        // Add new account
        $insert_stmt = $conn->prepare("INSERT INTO admin_accounts (admin_email, admin_password, first_name, last_name, role, school_name, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $insert_stmt->bind_param("ssssss", $account['email'], $account['password'], $account['first_name'], $account['last_name'], $account['role'], $account['school_name']);
        
        if ($insert_stmt->execute()) {
            $added[] = $account['email'];
        }
        $insert_stmt->close();
    } else {
        $already_exist[] = $account['email'];
    }
    
    $check_stmt->close();
}

// Get all accounts
$result = $conn->query("SELECT id, admin_email, first_name, last_name, role, status FROM admin_accounts ORDER BY role, id");
$all_accounts = [];
while ($row = $result->fetch_assoc()) {
    $all_accounts[] = $row;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Accounts synchronized',
    'added' => $added,
    'already_exist' => $already_exist,
    'total_accounts' => count($all_accounts),
    'all_accounts' => $all_accounts
]);

$conn->close();