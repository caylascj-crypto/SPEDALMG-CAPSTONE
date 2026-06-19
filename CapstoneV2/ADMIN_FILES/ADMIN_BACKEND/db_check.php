<?php
require_once __DIR__ . '/db.php';
$conn = getDatabaseConnection();
if (!$conn) {
    echo "<h1>Database connection failed</h1>";
    exit;
}

$result = $conn->query("SELECT id, admin_email, admin_password, first_name, last_name, role, condition_info, status, created_at FROM admin_accounts ORDER BY id ASC");
$statsResult = $conn->query("SELECT COUNT(*) AS total_users, SUM(role = 'teacher') AS teachers, SUM(role = 'student') AS students, SUM(status = 'active') AS active_accounts FROM admin_accounts");
$stats = $statsResult ? $statsResult->fetch_assoc() : ['total_users' => 0, 'teachers' => 0, 'students' => 0, 'active_accounts' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Check</title>
  <style>body{font-family:Arial,sans-serif;padding:24px;background:#f5f7fb;color:#111}table{border-collapse:collapse;width:100%;margin-top:16px}th,td{border:1px solid #d4d9e3;padding:8px;text-align:left}th{background:#eef2ff}h1{margin:0 0 16px}pre{background:#fff;border:1px solid #d4d9e3;padding:16px;}</style>
</head>
<body>
  <h1>Database Check</h1>
  <div>
    <strong>Total Users:</strong> <?= (int)$stats['total_users'] ?><br>
    <strong>Teachers:</strong> <?= (int)$stats['teachers'] ?><br>
    <strong>Students:</strong> <?= (int)$stats['students'] ?><br>
    <strong>Active Accounts:</strong> <?= (int)$stats['active_accounts'] ?><br>
  </div>
  <h2>Accounts</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Password</th>
        <th>Name</th>
        <th>Role</th>
        <th>Condition</th>
        <th>Status</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['admin_email']) ?></td>
            <td><?= htmlspecialchars($row['admin_password']) ?></td>
            <td><?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td><?= htmlspecialchars($row['condition_info']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8">No accounts found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
<?php $conn->close(); ?>
