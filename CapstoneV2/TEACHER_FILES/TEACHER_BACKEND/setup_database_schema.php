<?php
require_once __DIR__ . '/db.php';

$conn = getTeacherDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Create notifications table
$createNotificationsSQL = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    notification_type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
    INDEX (teacher_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createNotificationsSQL);

// Create lesson_plans table
$createLessonPlansSQL = "CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    activity_id INT,
    student_id INT,
    plan_title VARCHAR(255),
    plan_content TEXT,
    duration INT,
    resources TEXT,
    objectives TEXT,
    status VARCHAR(20) DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES teacher_activities(id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    INDEX (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createLessonPlansSQL);

// Create activity_templates table
$createTemplatesSQL = "CREATE TABLE IF NOT EXISTS activity_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255),
    template_category VARCHAR(100),
    template_description TEXT,
    template_content TEXT,
    difficulty VARCHAR(20),
    target_focus VARCHAR(100),
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (template_category, difficulty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createTemplatesSQL);

// Create assessment_records table
$createAssessmentSQL = "CREATE TABLE IF NOT EXISTS assessment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    activity_id INT,
    assessment_date DATE,
    score INT,
    feedback TEXT,
    strengths TEXT,
    areas_for_improvement TEXT,
    status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES teacher_activities(id) ON DELETE SET NULL,
    INDEX (teacher_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createAssessmentSQL);

// Add missing columns to existing tables if needed
$alterStudentsSQL = "ALTER TABLE students ADD COLUMN IF NOT EXISTS disability_category VARCHAR(100)";
$conn->query($alterStudentsSQL);

$alterIEPSQL = "ALTER TABLE iep_materials ADD COLUMN IF NOT EXISTS last_reviewed DATE";
$conn->query($alterIEPSQL);

$alterProgressSQL = "ALTER TABLE learner_progress ADD COLUMN IF NOT EXISTS feedback TEXT";
$conn->query($alterProgressSQL);

echo json_encode(['success' => true, 'message' => 'Database schema updated successfully']);
$conn->close();
?>
