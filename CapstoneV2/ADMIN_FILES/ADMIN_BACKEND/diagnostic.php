<?php
require_once __DIR__ . '/db.php';

$conn = getDatabaseConnection();
if (!$conn) {
    echo "❌ Database connection failed";
    exit;
}

echo "<h2>Database Diagnostic</h2>";

// Check columns
echo "<h3>Checking columns in admin_accounts table...</h3>";
$columns = $conn->query("DESCRIBE admin_accounts");
$columnList = [];
echo "<ul>";
while ($col = $columns->fetch_assoc()) {
    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    $columnList[] = $col['Field'];
}
echo "</ul>";

echo "<h3>Status:</h3>";
echo "Total columns: " . count($columnList) . "<br>";
echo "Has phone_number: " . (in_array('phone_number', $columnList) ? "✅ YES" : "❌ NO") . "<br>";
echo "Has created_at: " . (in_array('created_at', $columnList) ? "✅ YES" : "❌ NO") . "<br>";

// Try to add phone_number
if (!in_array('phone_number', $columnList)) {
    echo "<h3>Adding phone_number column...</h3>";
    $result = $conn->query("ALTER TABLE admin_accounts ADD COLUMN phone_number VARCHAR(20) AFTER school_name");
    if ($result) {
        echo "✅ phone_number column added!<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Try to add created_at
if (!in_array('created_at', $columnList)) {
    echo "<h3>Adding created_at column...</h3>";
    $result = $conn->query("ALTER TABLE admin_accounts ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    if ($result) {
        echo "✅ created_at column added!<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Test query
echo "<h3>Test Query Result:</h3>";
$test = $conn->query("SELECT id, phone_number FROM admin_accounts LIMIT 1");
if ($test) {
    echo "✅ Query successful!<br>";
    $row = $test->fetch_assoc();
    if ($row) {
        echo "Sample: ID=" . $row['id'] . ", Phone=" . ($row['phone_number'] ?: "empty") . "<br>";
    }
} else {
    echo "❌ Query failed: " . $conn->error . "<br>";
}

$conn->close();
?>
