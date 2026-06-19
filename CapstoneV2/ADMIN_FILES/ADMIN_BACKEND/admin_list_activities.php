<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// We need to get teacher database connection directly
$servername = "localhost";
$db_username = "root";
$db_password = "";
$database = "spedalm_db";

$teacher_conn = new mysqli($servername, $db_username, $db_password);
if ($teacher_conn->connect_error) {
    echo json_encode([]);
    exit;
}

if (!$teacher_conn->select_db($database)) {
    $teacher_conn->close();
    echo json_encode([]);
    exit;
}

$activities = [];

// Get all activities from teacher_activities table with teacher info
$sql = "SELECT 
    ta.id,
    ta.activity_title as title,
    ta.activity_description as description,
    ta.subject,
    ta.grade_level,
    ta.difficulty,
    ta.status,
    ta.created_at,
    tc.first_name,
    tc.last_name
FROM teacher_activities ta
LEFT JOIN teacher_accounts tc ON ta.teacher_id = tc.id
ORDER BY ta.created_at DESC
LIMIT 100";

$result = $teacher_conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $created_by = ($row['first_name'] && $row['last_name']) 
            ? $row['first_name'] . ' ' . $row['last_name'] 
            : 'Unknown';
        
        $activities[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'category' => $row['subject'] ?: 'General',
            'type' => 'Generated',
            'focus' => $row['grade_level'] ?: 'General',
            'difficulty' => $row['difficulty'] ?: 'medium',
            'status' => ucfirst($row['status']) ?: 'Draft',
            'creator' => $created_by,
            'created_by' => $created_by,
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode($activities);
$teacher_conn->close();
?>
