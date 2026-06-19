<?php
require_once 'ADMIN_FILES/ADMIN_BACKEND/db.php';

$conn = getDatabaseConnection();
if (!$conn) {
    die("Database connection failed");
}

// Delete test activities (Create Activity and Complete Activity entries)
$sql = "DELETE FROM admin_activities WHERE activity_type IN ('Create Activity', 'Complete Activity')";

if ($conn->query($sql)) {
    echo "Test activities removed successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
