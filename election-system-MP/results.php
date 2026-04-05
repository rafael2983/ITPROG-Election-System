<?php
// 1. Initialize session and database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("config/db.php");

// 2. Security: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_role = strtolower($_SESSION['role'] ?? '');

// 3. Fetch Election metadata
$election_query = $conn->query("SELECT * FROM elections WHERE id = 1");
$election = $election_query->fetch_assoc();

if (!$election) {
    die("Error: Election record not found in the database.");
}

$end_date_raw = $election['end_date'];
$end_timestamp = strtotime($end_date_raw);
$current_time = time();
$is_manager = ($user_role === 'manager' || $user_role === 'admin');
$election_ended = ($current_time > $end_timestamp);
$show_results = $election_ended || $is_manager;

if (!$show_results) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Election Results</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="container" style="text-align:center;">
            <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back to Dashboard</a>
            <h2>Election Results</h2>
            <div style="background:#fff3cd; padding:25px; border:1px solid #ffeeba; border-radius:8px; margin-top:20px;">
                <p style="color:#856404; font-weight:bold;">Results are currently hidden.</p>
                <p>The tally will be revealed to students after the election ends on:</p>
                <p><strong><?php echo $end_date_raw; ?></strong></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// 4. Fetch Tally Data
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Results</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="width:800px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back to Dashboard</a>
    <h2>Election Results</h2>
    <p><strong>Election:</strong> <?php echo htmlspecialchars($election['title']); ?></p>
    
    <?php if ($is_manager && !$election_ended): ?>
        <p style="color:#e67e22; font-size:0.9em; font-style:italic;">Note: You are viewing early results as an Administrator.</p>
    <?php endif; ?>

    <table border="1" style="width:100%; text-align:left; border-collapse:collapse; margin-top:20px;">
        <tr style="background:#f4f4f4;">
            <th style="padding:12px;">Position</th>
            <th style="padding:12px;">Candidate</th>
            <th style="padding:12px;">Verified Votes</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $is_first_in_pos = true;
            while($row = $result->fetch_assoc()): 
            ?>
                <tr style="<?php echo ($is_first_in_pos && $row['vote_count'] > 0) ? 'background:#e8f5e9;' : ''; ?>">
                    <td style="padding:12px; font-weight:bold;">
                        <?php 
                        if ($current_pos != $row['position_name']) {
                            echo htmlspecialchars($row['position_name']);
                            $current_pos = $row['position_name'];
                            $is_first_in_pos = true;
                        } else {
                            $is_first_in_pos = false;
                        }
                        ?>
                    </td>
                    <td style="padding:12px;">
                        <?php echo htmlspecialchars($row['name'] ?? "No Candidates Registered"); ?>
                        <?php if ($is_first_in_pos && $row['vote_count'] > 0 && $election_ended): ?>
                             <span style="color:#27ae60; font-size:0.8em; margin-left:10px;">★ Winner</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;">
                        <strong><?php echo $row['vote_count']; ?></strong>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3" style="padding:20px; text-align:center;">No voting data available.</td></tr>
        <?php endif; ?>
    </table>
    

</div>
</body>
</html>