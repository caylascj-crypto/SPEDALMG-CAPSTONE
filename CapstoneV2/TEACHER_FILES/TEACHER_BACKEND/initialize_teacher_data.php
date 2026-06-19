<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// This script initializes test data for teacher portal
// It will be run once to populate the database with sample data

$teacher_id = 2; // Teacher ID (assuming ID 1 is admin)

// Insert test students
$students = [
    ['De Leon, Nina', 'De Leon, Maria', 'maria@example.com', '09123456789', 'ASD', 8],
    ['Santos, Juan', 'Santos, Rosa', 'rosa@example.com', '09234567890', 'ADHD', 9],
    ['Reyes, Maria', 'Reyes, Juan', 'juan.reyes@example.com', '09345678901', 'Down Syndrome', 7],
    ['Dela Cruz, Carlos', 'Dela Cruz, Ana', 'ana.delacruz@example.com', '09456789012', '', 10],
    ['Lopez, Sofia', 'Lopez, Miguel', 'miguel.lopez@example.com', '09567890123', 'ID', 6],
    ['Garcia, Antonio', 'Garcia, Mercedes', 'mercedes.garcia@example.com', '09678901234', 'ASD', 9],
    ['Perez, Lucia', 'Perez, Diego', 'diego.perez@example.com', '09789012345', 'ADHD', 8]
];

foreach ($students as $student) {
    $stmt = $conn->prepare("INSERT INTO students (teacher_id, student_name, parent_name, parent_email, parent_phone, disability_type, age, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    if (!$stmt) {
        continue;
    }
    $stmt->bind_param("issssssi", $teacher_id, $student[0], $student[1], $student[2], $student[3], $student[4], $student[5]);
    $stmt->execute();
    $stmt->close();
}

// Insert test activities
$activities = [
    ['Reading Comprehension Basics', 'Introduction to reading comprehension for special learners', 'English', 'Elementary', 'Easy'],
    ['Math Counting Skills', 'Basic counting and number recognition', 'Mathematics', 'Elementary', 'Beginner'],
    ['Social Skills Workshop', 'Learning to interact in social situations', 'Social Studies', 'Elementary', 'Intermediate'],
    ['Fine Motor Development', 'Activities to improve fine motor skills', 'Physical Education', 'Elementary', 'Easy'],
    ['Communication Practice', 'Speech and language development exercises', 'Language Arts', 'Elementary', 'Intermediate']
];

foreach ($activities as $activity) {
    $stmt = $conn->prepare("INSERT INTO teacher_activities (teacher_id, activity_title, activity_description, subject, grade_level, difficulty, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'published')");
    if (!$stmt) {
        continue;
    }
    $stmt->bind_param("issssss", $teacher_id, $activity[0], $activity[1], $activity[2], $activity[3], $activity[4]);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'message' => 'Test data initialized successfully']);
$conn->close();
?>
