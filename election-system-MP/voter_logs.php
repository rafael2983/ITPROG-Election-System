<?php
include("config/db.php");

// Security: Prevent students from accessing logs
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Election Manager' && $_SESSION['role'] !== 'Election Committee')) {
    header("Location: dashboard.php");
    exit();
}

$sql = "SELECT voter_logs.*, users.full_name, users.student_id, elections.title
        FROM voter_logs
        JOIN users ON voter_logs.user_id = users.id
        JOIN elections ON voter_logs.election_id = elections.id
        ORDER BY voter_logs.timestamp DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Voter Logs</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="logs-container">

<a href="dashboard.php" class="back-btn">← Back</a>

<h2>Voter Logs / Audit Trail</h2>

<table class="logs-table">
    <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Election</th>
        <th>Action</th>
        <th>Date & Time</th>
    </tr>

    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['student_id']; ?></td>
        <td><?= $row['full_name']; ?></td>
        <td><?= $row['title']; ?></td>
        <td>
            <span class="badge badge-voted">
                <?= $row['action']; ?>
            </span>
        </td>
        <td><?= $row['timestamp']; ?></td>
    </tr>
    <?php endwhile; ?>

</table>

</div>

</body>
</html>