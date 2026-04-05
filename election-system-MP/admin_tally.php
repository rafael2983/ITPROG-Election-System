<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("config/db.php");

// Security: Only Election Managers can view the tally
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'manager' && $role !== 'admin') {
    die("Access Denied: Admin-level privilege required.");
}

// 2. Fixed SQL: Join using position name and count verified votes
$sql = "SELECT positions.position_name, candidates.name, COUNT(votes.id) as vote_count
        FROM positions
        LEFT JOIN candidates ON positions.position_name = candidates.position
        LEFT JOIN votes ON candidates.id = votes.candidate_id
        GROUP BY positions.id, candidates.id
        ORDER BY FIELD(positions.position_name, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Public Relations Officer'), vote_count DESC";

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
<div class="container" style="width:800px;">
    <h2>Automated Vote Tally</h2>
    <p>This report is generated from the verified audit trail.</p>
    
    <table border="1" style="width:100%; text-align:left; border-collapse:collapse;">
        <tr style="background:#f4f4f4;">
            <th style="padding:10px;">Position</th>
            <th style="padding:10px;">Candidate</th>
            <th style="padding:10px;">Verified Votes</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="padding:10px;"><?= ($current_pos != $row['position_name']) ? $row['position_name'] : ""; ?></td>
                <td style="padding:10px;"><?= htmlspecialchars($row['name'] ?? "No Candidates"); ?></td>
                <td style="padding:10px;"><strong><?= $row['vote_count']; ?></strong></td>
            </tr>
            <?php $current_pos = $row['position_name']; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3" style="padding:20px; text-align:center;">No data found. Check your database connections.</td></tr>
        <?php endif; ?>
    </table>
    <br>
    <a href="dashboard.php"><button style="width:100%;">Return to Dashboard</button></a>
</div>
</body>
</html>