<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$status_stmt->bind_param("i", $user_id);
$status_stmt->execute();
$user_data = $status_stmt->get_result()->fetch_assoc();

if (isset($user_data['status']) && $user_data['status'] === 'inactive') {
    die("<div class='container' style='text-align:center;'>
            <h2>Account Deactivated</h2>
            <p>Your account is inactive. You cannot view the candidate list.</p>
            <a href='dashboard.php'><button>Back to Dashboard</button></a>
          </div>");
}


$sql = "SELECT candidates.*, positions.position_name 
        FROM candidates 
        LEFT JOIN positions ON candidates.position = positions.position_name
        WHERE candidates.status = 'approved' 
        ORDER BY FIELD(positions.position_name, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Public Relations Officer'), candidates.name ASC";

$result = $conn->query($sql);
$current_position = "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>eVote - Election Candidates</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="width:750px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back to Dashboard</a>
    <h2 style="margin-top:20px;">Election Candidates</h2>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
                $display_position = !empty($row['position_name']) ? $row['position_name'] : $row['position']; 
            ?>
            
            <?php if ($current_position != $display_position): ?>
                <h3 style="border-bottom: 2px solid #eee; padding-bottom: 5px; margin-top: 25px; color: #2c3e50;">
                    <?php echo htmlspecialchars($display_position); ?>
                </h3>
                <?php $current_position = $display_position; ?>
            <?php endif; ?>

            <div style="display:flex; align-items:center; margin-bottom:15px; background:#fff; padding:15px; border-radius:8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <img src="uploads/<?php echo $row['photo']; ?>" width="80" height="80" 
                     style="border-radius:50%; margin-right:20px; object-fit:cover; border:2px solid #ddd;" 
                     onerror="this.src='assets/default-avatar.png';">
                <div>
                    <strong style="font-size:1.2em; color: #34495e;"><?php echo htmlspecialchars($row['name']); ?></strong><br>
                    <p style="margin:5px 0; color:#7f8c8d; line-height: 1.4;">
                        <?php echo htmlspecialchars($row['synopsis']); ?>
                    </p>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="padding:40px; text-align:center; color:#888;">
            <p>No approved candidates are currently listed for this election.</p>
        </div>
    <?php endif; ?>

    <div style="margin-top:30px; text-align:center;">
        <a href="vote.php"><button style="padding:12px 50px; cursor:pointer;">Proceed to Voting</button></a>
    </div>
</div>
</body>
</html>