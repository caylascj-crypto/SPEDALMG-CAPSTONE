<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

function getDatabaseConnection() {
    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $database = "spedalm_db";

    $conn = new mysqli($servername, $db_username, $db_password);
    if ($conn->connect_error) {
        return null;
    }

    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        $conn->close();
        return null;
    }

    if (!$conn->select_db($database)) {
        $conn->close();
        return null;
    }

    $createTableSql = "CREATE TABLE IF NOT EXISTS admin_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_email VARCHAR(255) NOT NULL UNIQUE,
        admin_password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        school_name VARCHAR(255),
        phone_number VARCHAR(20),
        role VARCHAR(50) DEFAULT 'admin',
        condition_info VARCHAR(255),
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($createTableSql)) {
        $conn->close();
        return null;
    }

    // Ensure the table has the newest columns for the current version
    $conn->query("ALTER TABLE admin_accounts ADD COLUMN IF NOT EXISTS condition_info VARCHAR(255) AFTER role");
    // Remove old constraint if it exists to avoid conflicts
    $conn->query("ALTER TABLE admin_accounts DROP CONSTRAINT IF EXISTS chk_admin_email_domain");

    $insertAdminSql = "INSERT IGNORE INTO admin_accounts (admin_email, admin_password, first_name, last_name, school_name, role, status) VALUES ('admin@spedalm.edu.ph', 'Admin@123', 'Admin', 'User', 'Mamatid Elementary School', 'admin', 'active')";
    $conn->query($insertAdminSql);

    $insertTeacherSql = "INSERT IGNORE INTO admin_accounts (admin_email, admin_password, first_name, last_name, school_name, role, status) VALUES ('caylas@spedalm.edu.ph', 'caylas@123', 'Caylas', 'Santos', 'Mamatid Elementary School', 'teacher', 'active')";
    $conn->query($insertTeacherSql);

    return $conn;
}