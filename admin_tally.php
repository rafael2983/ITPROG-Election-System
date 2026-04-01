<?php
session_start();
include("config/db.php");

// Only Election Managers can view the tally
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Election Manager') {
    die("Access Denied: Admin-level privilege required.");
}

// Counts verified submissions only
$sql = "SELECT positions.position_name, candidates.name, COUNT(votes.id) as vote_count
        FROM positions
        LEFT JOIN candidates ON positions.id = candidates.position
        LEFT JOIN votes ON candidates.id = votes.candidate_id
        GROUP BY positions.id, candidates.id
        ORDER BY positions.position_name, vote_count DESC";

$result = $conn->query($sql);
$current_pos = "";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manager Tally View</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Automated Vote Tally</h2>
    <p>This report is generated from the verified audit trail.</p>
    
    <table border="1" style="width:100%; text-align:left; border-collapse:collapse;">
        <tr style="background:#f4f4f4;">
            <th>Position</th>
            <th>Candidate</th>
            <th>Verified Votes</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= ($current_pos != $row['position_name']) ? $row['position_name'] : ""; ?></td>
            <td><?= $row['name'] ?? "No Candidates"; ?></td>
            <td><strong><?= $row['vote_count']; ?></strong></td>
        </tr>
        <?php $current_pos = $row['position_name']; ?>
        <?php endwhile; ?>
    </table>
    <br>
    <a href="dashboard.php"><button>Return to Dashboard</button></a>
</div>
</body>
</html>