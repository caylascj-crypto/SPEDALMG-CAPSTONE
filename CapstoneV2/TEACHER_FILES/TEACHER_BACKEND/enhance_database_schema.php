<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$queries = [];
$results = [];

// Table: activity_assignments - track which activities are assigned to which students
$queries[] = "CREATE TABLE IF NOT EXISTS activity_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    activity_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE,
    status VARCHAR(50) DEFAULT 'assigned',
    FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id),
    FOREIGN KEY (activity_id) REFERENCES teacher_activities(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY unique_assignment (activity_id, student_id, teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Table: teacher_settings - store individual teacher preferences
$queries[] = "CREATE TABLE IF NOT EXISTS teacher_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL UNIQUE,
    settings_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Table: teacher_reports - save generated reports
$queries[] = "CREATE TABLE IF NOT EXISTS teacher_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    report_title VARCHAR(255) NOT NULL,
    report_content LONGTEXT,
    report_date DATE,
    report_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Table: lesson_plans - detailed lesson planning
$queries[] = "CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    activity_id INT,
    student_id INT,
    plan_title VARCHAR(255),
    plan_content LONGTEXT,
    duration INT,
    resources TEXT,
    objectives TEXT,
    status VARCHAR(50) DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id),
    FOREIGN KEY (activity_id) REFERENCES teacher_activities(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Add missing columns to students table
$queries[] = "ALTER TABLE students ADD COLUMN IF NOT EXISTS disability_category VARCHAR(100)";
$queries[] = "ALTER TABLE students ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

// Add missing columns to iep_materials
$queries[] = "ALTER TABLE iep_materials ADD COLUMN IF NOT EXISTS last_reviewed DATE";
$queries[] = "ALTER TABLE iep_materials ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

// Add missing columns to learner_progress
$queries[] = "ALTER TABLE learner_progress ADD COLUMN IF NOT EXISTS feedback TEXT";
$queries[] = "ALTER TABLE learner_progress ADD COLUMN IF NOT EXISTS assessment_date DATE";

// Add missing columns to teacher_activities if not present
$queries[] = "ALTER TABLE teacher_activities ADD COLUMN IF NOT EXISTS learning_materials LONGTEXT";
$queries[] = "ALTER TABLE teacher_activities ADD COLUMN IF NOT EXISTS instructions LONGTEXT";
$queries[] = "ALTER TABLE teacher_activities ADD COLUMN IF NOT EXISTS duration INT DEFAULT 30";
$queries[] = "ALTER TABLE teacher_activities ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

// Execute all queries
foreach ($queries as $query) {
    if (empty(trim($query))) continue;
    
    if ($conn->query($query) === TRUE) {
        $results[] = ['query' => substr($query, 0, 50) . '...', 'status' => 'success'];
    } else {
        $results[] = ['query' => substr($query, 0, 50) . '...', 'status' => 'error', 'error' => $conn->error];
    }
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Database schema enhanced successfully',
    'results' => $results
]);
?>
