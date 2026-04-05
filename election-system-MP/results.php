<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_role = strtolower($_SESSION['role'] ?? '');

$election_query = $conn->query("SELECT * FROM elections WHERE id = 1");
$election = $election_query->fetch_assoc();
$end_timestamp = strtotime($election['end_date']);
$now = time();

$is_manager = ($user_role === 'manager' || $user_role === 'admin');
$election_ended = ($now > $end_timestamp);

if (!$election_ended && !$is_manager) {
    echo "<div class='container' style='text-align:center;'>
            <h2>Election Results</h2>
            <p>Results will be revealed after <strong>" . $election['end_date'] . "</strong>.</p>
            <a href='dashboard.php'><button>Back</button></a>
          </div>";
    exit();
}

$sql = "SELECT positions.position_name, candidates.name, COUNT(votes.id) as vote_count
        FROM positions
        LEFT JOIN candidates ON positions.position_name = candidates.position
        LEFT JOIN votes ON candidates.id = votes.candidate_id
        GROUP BY positions.id, candidates.id
        ORDER BY FIELD(positions.position_name, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Public Relations Officer'), vote_count DESC";

$result = $conn->query($sql);

$rows = [];
$max_votes_per_pos = [];

while($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $pos = $row['position_name'];
    $votes = (int)$row['vote_count'];
    
    if (!isset($max_votes_per_pos[$pos]) || $votes > $max_votes_per_pos[$pos]) {
        $max_votes_per_pos[$pos] = $votes;
    }
}

$current_pos = "";
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
    <h2 style="text-align:center;">Election Results</h2>
    <p style="text-align:center;"><strong>Election:</strong> <?php echo htmlspecialchars($election['title']); ?></p>

    <?php if ($is_manager && !$election_ended): ?>
        <p style="text-align:center; color:#e67e22; font-size:0.85em; font-style:italic;">Note: You are viewing early results as an Administrator.</p>
    <?php endif; ?>

    <table border="1" style="width:100%; text-align:left; border-collapse:collapse; margin-top:20px;">
        <thead>
            <tr style="background:#f4f4f4;">
                <th style="padding:12px; width:30%;">Position</th>
                <th style="padding:12px;">Candidate</th>
                <th style="padding:12px; width:20%;">Verified Votes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $row): ?>
                <?php 
                $pos = $row['position_name'];
                $is_winner = ($row['vote_count'] > 0 && $row['vote_count'] == $max_votes_per_pos[$pos]);
                ?>
                <tr style="<?php echo $is_winner ? 'background:#e8f5e9;' : ''; ?>">
                    <td style="padding:12px; font-weight:bold;">
                        <?php 
                        if ($current_pos != $pos) {
                            echo htmlspecialchars($pos);
                            $current_pos = $pos;
                        }
                        ?>
                    </td>
                    <td style="padding:12px;"><?php echo htmlspecialchars($row['name'] ?? "No Candidates Registered"); ?></td>
                    <td style="padding:12px;"><strong><?php echo $row['vote_count']; ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>