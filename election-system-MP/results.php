<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$election_id = 1;
$stmt = $conn->prepare("SELECT title, end_date FROM elections WHERE id = ?");
$stmt->bind_param("i", $election_id);
$stmt->execute();
$election_result = $stmt->get_result();

// FIX: Check if the election actually exists in the database
if ($election_result->num_rows === 0) {
    die("<div class='container'><h2>Error</h2><p>No election found with ID 1. Please add an election to the 'elections' table in phpMyAdmin.</p><a href='dashboard.php'>Back</a></div>");
}

$election = $election_result->fetch_assoc();
$current_time = date("Y-m-d H:i:s");
$is_finished = (strtotime($current_time) > strtotime($election['end_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Results</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="width:850px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back to Dashboard</a>
    
    <h2 style="margin-top:20px;">Election Results</h2>
    <p><strong>Election:</strong> <?php echo htmlspecialchars($election['title']); ?></p>

    <?php if (!$is_finished): ?>
        <div style="padding:40px; text-align:center; background:#fff3cd; border:1px solid #ffeeba; border-radius:8px;">
            <p style="color:#856404; font-weight:bold;">Results are currently hidden.</p>
            <p>The tally will be revealed after the election ends on:<br>
            <strong><?php echo $election['end_date']; ?></strong></p>
        </div>
    <?php else: ?>
        <?php
        // Automated Tallying Query
        $sql = "SELECT c.name, p.position_name, COUNT(v.id) as total_votes
                FROM candidates c
                LEFT JOIN votes v ON c.id = v.candidate_id
                LEFT JOIN positions p ON c.position = p.position_name
                GROUP BY c.id
                ORDER BY p.id ASC, total_votes DESC";
        $result = $conn->query($sql);
        $current_pos = "";
        ?>

        <table border="1" style="width:100%; border-collapse: collapse; margin-top:20px;">
            <thead>
                <tr style="background:#f4f4f4;">
                    <th style="padding:12px; text-align:left;">Position</th>
                    <th style="padding:12px; text-align:left;">Candidate</th>
                    <th style="padding:12px; text-align:center;">Verified Votes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="padding:12px;">
                                <?php 
                                    if ($current_pos != $row['position_name']) {
                                        echo "<strong>" . htmlspecialchars($row['position_name'] ?? 'Uncategorized') . "</strong>";
                                        $current_pos = $row['position_name'];
                                    }
                                ?>
                            </td>
                            <td style="padding:12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td style="padding:12px; text-align:center;"><strong><?php echo $row['total_votes']; ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="padding:20px; text-align:center;">No data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>