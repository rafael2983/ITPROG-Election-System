<?php
session_start();
include("config/db.php");

// Security: Only manager, committee, or admin can access logs
$allowed_roles = ['manager', 'committee', 'admin'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $allowed_roles)) {
    header("Location: dashboard.php");
    exit();
}

// 2. Fetch Audit Trail: Shows who voted and when
$sql = "SELECT voter_logs.*, users.full_name, users.student_id, elections.title 
        FROM voter_logs 
        JOIN users ON voter_logs.user_id = users.id 
        JOIN elections ON voter_logs.election_id = elections.id 
        ORDER BY voter_logs.timestamp DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail - Voter Logs</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="width:900px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back</a>
    <h2>Voter Logs & Audit Trail</h2>
    <p>This list documents student participation. Choices remain confidential.</p>

    <table border="1" style="width:100%; border-collapse: collapse; margin-top:20px;">
        <thead>
            <tr style="background-color:#f2f2f2; text-align:left;">
                <th style="padding:12px;">Student ID</th>
                <th style="padding:12px;">Full Name</th>
                <th style="padding:12px;">Election</th>
                <th style="padding:12px;">Action</th>
                <th style="padding:12px;">Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="padding:12px;"><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td style="padding:12px;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td style="padding:12px;"><?php echo htmlspecialchars($row['title']); ?></td>
                    <td style="padding:12px;">
                        <span style="background:#27ae60; color:white; padding:4px 8px; border-radius:4px; font-size:0.85em;">
                            <?php echo htmlspecialchars($row['action']); ?>
                        </span>
                    </td>
                    <td style="padding:12px;"><?php echo $row['timestamp']; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding:20px; text-align:center; color:#888;">No voting logs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>