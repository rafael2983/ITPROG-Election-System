<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$election_id = 1;

$status_stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$status_stmt->bind_param("i", $user_id);
$status_stmt->execute();
$user_data = $status_stmt->get_result()->fetch_assoc();

if ($user_data['status'] === 'inactive') {
    die("<div class='container' style='text-align:center;'>
            <h2>Account Deactivated</h2>
            <p>Your account is inactive. You cannot vote.</p>
            <a href='dashboard.php'><button>Back to Dashboard</button></a>
          </div>");
}

$check_sql = "SELECT id FROM voter_logs WHERE user_id = ? AND election_id = ? AND action = 'Voted'";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $election_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("<div class='container'><h2>Already Voted</h2><p>You have already cast your vote.</p><a href='dashboard.php'>Back</a></div>");
}

$sql = "SELECT candidates.*, positions.position_name, positions.id as pos_id
        FROM candidates
        LEFT JOIN positions ON candidates.position = positions.position_name
        WHERE candidates.status='approved'
        ORDER BY positions.id ASC";
$result = $conn->query($sql);
$current_position = "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cast Your Vote</title>
    <link rel="stylesheet" href="assets/style.css">
    <script>
        function confirmSubmission() {
            return confirm("Are you sure you want to submit? This cannot be undone.");
        }
    </script>
</head>
<body>
<div class="container" style="width:750px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back</a>
    <h2>Cast Your Vote</h2>
    <form method="POST" action="submit_vote.php" onsubmit="return confirmSubmission();">
        <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php if ($current_position != $row['position_name']): ?>
                <h3 style="background:#f4f4f4; padding:10px; margin-top:30px; border-left:5px solid #3498db;">
                    <?php echo htmlspecialchars($row['position_name']); ?>
                </h3>
                <?php $current_position = $row['position_name']; ?>
            <?php endif; ?>
            <label style="display:flex; align-items:center; padding:15px; border:1px solid #ddd; border-radius:8px; margin-bottom:10px; cursor:pointer;">
                <input type="radio" name="vote[<?php echo $row['pos_id']; ?>]" value="<?php echo $row['id']; ?>" required style="margin-right:20px;">
                <img src="uploads/<?php echo $row['photo']; ?>" width="60" height="60" style="border-radius:50%; margin-right:15px; object-fit:cover;">
                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
            </label>
        <?php endwhile; ?>
        <button type="submit" style="width:100%; padding:15px; background:#27ae60; color:white; margin-top:20px;">Submit Ballot</button>
    </form>
</div>
</body>
</html>