<?php
session_start();
include("config/db.php");

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'manager' && $role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 'active') ? 'inactive' : 'active';
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();
}

if (isset($_POST['remove_id'])) {
    $remove_id = $_POST['remove_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param("i", $remove_id);
    $stmt->execute();
}

$sql = "SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Voters</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="width:1000px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back</a>
    <h2>Voter Management</h2>
    <table border="1" style="width:100%; border-collapse: collapse; margin-top:20px;">
        <thead>
            <tr style="background:#f4f4f4;">
                <th style="padding:10px;">ID</th>
                <th style="padding:10px;">Name</th>
                <th style="padding:10px;">Status</th>
                <th style="padding:10px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="padding:10px;"><?php echo htmlspecialchars($row['student_id']); ?></td>
                <td style="padding:10px;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td style="padding:10px;"><?php echo strtoupper($row['status'] ?? 'ACTIVE'); ?></td>
                <td style="padding:10px; display: flex; gap: 5px;">
                    <a href="edit_voter.php?id=<?php echo $row['id']; ?>"><button style="background:#3498db; color:white; padding:5px;">Edit</button></a>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="current_status" value="<?php echo $row['status'] ?? 'active'; ?>">
                        <button type="submit" name="toggle_status" style="background:#f39c12; color:white; padding:5px;">
                            <?php echo ($row['status'] == 'inactive') ? 'Activate' : 'Deactivate'; ?>
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Remove voter?');">
                        <input type="hidden" name="remove_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" style="background:#e74c3c; color:white; padding:5px;">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>