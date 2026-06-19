<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Delete all accounts with old email domain
$conn->query("DELETE FROM admin_accounts WHERE admin_email LIKE '%@spedalm.edu.ph'");

// Add new correct accounts
$accounts = [
    [
        'email' => 'admin@mamatid.edu.ph',
        'password' => 'Admin@123',
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'admin'
    ],
    [
        'email' => 'caylas@mamatid.edu.ph',
        'password' => 'Teacher@123',
        'first_name' => 'Caylas',
        'last_name' => 'Santos',
        'role' => 'teacher'
    ],
    [
        'email' => 'jay@mamatid.edu.ph',
        'password' => 'Teacher@123',
        'first_name' => 'Jay',
        'last_name' => 'Caylas',
        'role' => 'teacher'
    ],
    [
        'email' => 'hydee@mamatid.edu.ph',
        'password' => 'Teacher@123',
        'first_name' => 'Hydee',
        'last_name' => 'Santos',
        'role' => 'teacher'
    ]
];

$inserted = [];
foreach ($accounts as $account) {
    $stmt = $conn->prepare("INSERT IGNORE INTO admin_accounts (admin_email, admin_password, first_name, last_name, role, school_name, status) VALUES (?, ?, ?, ?, ?, 'Mamatid Elementary School', 'active')");
    $stmt->bind_param("sssss", $account['email'], $account['password'], $account['first_name'], $account['last_name'], $account['role']);
    
    if ($stmt->execute()) {
        $inserted[] = $account['email'];
    }
    $stmt->close();
}

// Get all accounts
$result = $conn->query("SELECT id, admin_email, first_name, last_name, role, status FROM admin_accounts ORDER BY role, id");
$all_accounts = [];
while ($row = $result->fetch_assoc()) {
    $all_accounts[] = $row;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Database reset successfully',
    'accounts_inserted' => $inserted,
    'total_accounts' => count($all_accounts),
    'all_accounts' => $all_accounts
]);

$conn->close();
?>
