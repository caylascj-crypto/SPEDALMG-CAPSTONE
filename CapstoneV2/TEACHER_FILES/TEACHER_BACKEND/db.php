<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

function getTeacherDatabaseConnection() {
    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $database = "spedalm_db";

    $conn = new mysqli($servername, $db_username, $db_password);
    if ($conn->connect_error) {
        return null;
    }

    if (!$conn->select_db($database)) {
        $conn->close();
        return null;
    }

    // Create teacher_accounts table
    $createTeacherTableSql = "CREATE TABLE IF NOT EXISTS teacher_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_email VARCHAR(255) NOT NULL UNIQUE,
        teacher_password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        school_name VARCHAR(255),
        phone_number VARCHAR(20),
        specialization VARCHAR(100),
        bio TEXT, /* CHANGED: added bio column to store teacher's short biography */
        class_section VARCHAR(50),
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createTeacherTableSql);

    // Create students table
    $createStudentsTableSql = "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        admin_account_id INT NULL DEFAULT NULL,
        student_name VARCHAR(255) NOT NULL,
        parent_name VARCHAR(255),
        parent_email VARCHAR(255),
        parent_phone VARCHAR(20),
        disability_type VARCHAR(100),
        grade_level VARCHAR(20),
        status VARCHAR(20) DEFAULT 'active',
        age INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
        INDEX (teacher_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createStudentsTableSql);
    // Add admin_account_id column if missing (migration)
    $conn->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS admin_account_id INT NULL DEFAULT NULL");

    // Create activities table
    $createActivitiesTableSql = "CREATE TABLE IF NOT EXISTS teacher_activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        activity_title VARCHAR(255) NOT NULL,
        activity_description TEXT,
        subject VARCHAR(100),
        grade_level VARCHAR(20),
        difficulty VARCHAR(20),
        learning_materials TEXT,
        instructions TEXT,
        status VARCHAR(20) DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
        INDEX (teacher_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createActivitiesTableSql);

    // Create IEP materials table
    $createIEPTableSql = "CREATE TABLE IF NOT EXISTS iep_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        student_id INT NOT NULL,
        iep_goal TEXT,
        learning_objective TEXT,
        strategies TEXT,
        materials TEXT,
        assessment_method VARCHAR(200),
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX (teacher_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createIEPTableSql);

    // Create learner progress table
    $createProgressTableSql = "CREATE TABLE IF NOT EXISTS learner_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        student_id INT NOT NULL,
        activity_id INT,
        score INT,
        notes TEXT,
        assessment_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (activity_id) REFERENCES teacher_activities(id) ON DELETE SET NULL,
        INDEX (teacher_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createProgressTableSql);

    // Create teacher reports table
    $createReportsTableSql = "CREATE TABLE IF NOT EXISTS teacher_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        student_id INT NOT NULL,
        report_title VARCHAR(255),
        report_content TEXT,
        report_date DATE,
        report_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX (teacher_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createReportsTableSql);

    // Create notifications table
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        notification_type VARCHAR(50) DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
        INDEX (teacher_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create teacher_settings table
    $conn->query("CREATE TABLE IF NOT EXISTS teacher_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL UNIQUE,
        settings_data TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create student_notes table
    $conn->query("CREATE TABLE IF NOT EXISTS student_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        student_id INT NOT NULL,
        note TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teacher_accounts(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX (teacher_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return $conn;
}
?>
