<?php
error_reporting(0);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_OFF);

function getDatabaseConnection() {
    $conn = new mysqli("localhost", "root", "", "");
    if ($conn->connect_error) { return null; }

    $conn->query("CREATE DATABASE IF NOT EXISTS `spedalm_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    if (!$conn->select_db("spedalm_db")) { $conn->close(); return null; }

    // admin_accounts table
    $conn->query("CREATE TABLE IF NOT EXISTS admin_accounts (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        admin_email   VARCHAR(255) NOT NULL UNIQUE,
        admin_password VARCHAR(255) NOT NULL,
        first_name    VARCHAR(100),
        last_name     VARCHAR(100),
        school_name   VARCHAR(255),
        phone_number  VARCHAR(20),
        role          VARCHAR(50)  DEFAULT 'admin',
        condition_info VARCHAR(255),
        status        VARCHAR(20)  DEFAULT 'active',
        last_login    TIMESTAMP    NULL DEFAULT NULL,
        created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Safe migrations for existing installs
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN IF NOT EXISTS condition_info VARCHAR(255) AFTER role");
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL AFTER status");
    $conn->query("ALTER TABLE admin_accounts DROP CONSTRAINT IF EXISTS chk_admin_email_domain");

    // admin_activities log table
    $conn->query("CREATE TABLE IF NOT EXISTS admin_activities (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        activity_type VARCHAR(100),
        user_type   VARCHAR(50),
        user_name   VARCHAR(255),
        user_email  VARCHAR(255),
        action_detail TEXT,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Default accounts — seeded on first run
    $conn->query("INSERT IGNORE INTO admin_accounts (admin_email, admin_password, first_name, last_name, school_name, role, status)
        VALUES ('admin@spedalm.edu.ph', 'Admin@123', 'Admin', 'User', 'Mamatid Elementary School', 'admin', 'active')");
    $conn->query("INSERT IGNORE INTO admin_accounts (admin_email, admin_password, first_name, last_name, school_name, role, status)
        VALUES ('teacher@spedalm.edu.ph', 'Teacher@123', 'Demo', 'Teacher', 'Mamatid Elementary School', 'teacher', 'active')");
    $conn->query("INSERT IGNORE INTO admin_accounts (admin_email, admin_password, first_name, last_name, school_name, role, status, condition_info)
        VALUES ('student@spedalm.edu.ph', 'Student@123', 'Demo', 'Student', 'Mamatid Elementary School', 'student', 'active', 'ADHD')");

    return $conn;
}