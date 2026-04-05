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
$election_id = 1;

// 1. Fetch Election Timing
$election_stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$election_stmt->bind_param("i", $election_id);
$election_stmt->execute();
$election = $election_stmt->get_result()->fetch_assoc();

// 2. Handle missing setup or unconfigured dates
if (!$election || empty($election['start_date']) || empty($election['end_date'])) {
    die("<div class='container' style='text-align:center;'>
            <a href='dashboard.php' style='text-decoration:none; color:#3498db;'>← Back</a>
            <h2 style='margin-top:30px;'>Voting Schedule: TBA</h2>
            <div style='background:#f8f9fa; padding:20px; border-radius:8px; border:1px solid #ddd; margin-top:20px;'>
                <p>The official voting period has not been finalized yet.</p>
                <p style='color:#7f8c8d; font-style:italic;'>Please wait for the formal announcement.</p>
            </div>
            <br>
            <a href='dashboard.php'><button style='padding:10px 30px; cursor:pointer;'>Return to Dashboard</button></a>
          </div>");
}

$start_time = strtotime($election['start_date']);
$end_time = strtotime($election['end_date']);
$current_time = time();

/**
 * 3. Voting Period Validation
 * Change: Now displays "TBA" if the current time is before the start time.
 */
if ($current_time < $start_time) {
    die("<div class='container' style='text-align:center;'>
            <a href='dashboard.php' style='text-decoration:none; color:#3498db;'>← Back</a>
            <h2 style='margin-top:30px;'>Voting Schedule: TBA</h2>
            <div style='background:#f8f9fa; padding:20px; border-radius:8px; border:1px solid #ddd; margin-top:20px;'>
                <p>Voting for <strong>" . htmlspecialchars($election['title']) . "</strong> has not started yet.</p>
                <p style='color:#7f8c8d; font-style:italic;'>The exact schedule is currently To Be Announced (TBA).</p>
            </div>
            <br>
            <a href='dashboard.php'><button style='padding:10px 30px; cursor:pointer;'>Return to Dashboard</button></a>
          </div>");
}

if ($current_time > $end_time) {
    die("<div class='container' style='text-align:center;'>
            <h2>Election Ended</h2>
            <p>The voting period has officially closed.</p>
            <a href='dashboard.php'><button>Back to Dashboard</button></a>
          </div>");
}

// 4. One-time voting check
$check_sql = "SELECT id FROM voter_logs WHERE user_id = ? AND election_id = ? AND action = 'Voted'";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $election_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("<div class='container' style='text-align:center;'>
            <h2>Already Voted</h2>
            <p>You have already submitted your ballot for this election.</p>
            <a href='dashboard.php'><button>Back to Dashboard</button></a>
          </div>");
}

// 5. Fetch approved candidates
$sql = "SELECT candidates.*, positions.position_name, positions.id as pos_id
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
    <title>eVote - Cast Your Vote</title>
    <link rel="stylesheet" href="assets/style.css">
    <script>
        function confirmSubmission() {
            return confirm("Submit your final ballot? This action cannot be undone.");
        }
    </script>
</head>
<body>
<div class="container" style="width:750px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back</a>
    <h2 style="text-align:center; margin-bottom:30px;">Cast Your Vote</h2>
    <p style="text-align:center; color:#7f8c8d;">Election: <?php echo htmlspecialchars($election['title']); ?></p>

    <form method="POST" action="submit_vote.php" onsubmit="return confirmSubmission();">
        <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php if ($current_position != $row['position_name']): ?>
                    <h3 style="background:#f8f9fa; padding:12px; margin-top:40px; border-left:5px solid #3498db; color:#2c3e50; text-align:center;">
                        <?php echo htmlspecialchars($row['position_name']); ?>
                    </h3>
                    <?php $current_position = $row['position_name']; ?>
                <?php endif; ?>
                <label style="display:flex; align-items:center; padding:15px; border:1px solid #ddd; border-radius:10px; margin-bottom:12px; cursor:pointer; background:#fff;">
                    <div style="margin: 0 20px;">
                        <input type="radio" name="vote[<?php echo $row['pos_id']; ?>]" value="<?php echo $row['id']; ?>" required style="transform: scale(1.4);">
                    </div>
                    <img src="uploads/<?php echo $row['photo']; ?>" width="65" height="65" style="border-radius:50%; object-fit:cover; border:2px solid #eee; margin-right:20px;" onerror="this.src='assets/default-avatar.png';">
                    <div style="flex-grow: 1;">
                        <strong style="font-size:1.15em; color:#34495e;"><?php echo htmlspecialchars($row['name']); ?></strong>
                    </div>
                </label>
            <?php endwhile; ?>
            <div style="margin-top:40px; text-align:center;">
                <button type="submit" style="width:100%; padding:18px; font-size:1.1em; background-color: #27ae60; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">
                    Submit Final Ballot
                </button>
            </div>
        <?php else: ?>
            <p style="text-align:center; padding:50px; color:#888;">No approved candidates are available to vote for yet.</p>
        <?php endif; ?>
    </form>
</div>
</body>
</html>