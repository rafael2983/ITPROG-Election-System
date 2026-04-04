<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
$role = strtolower($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>eVote - Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Welcome, <?php echo $_SESSION['name']; ?>!</h2>
    <p>Logged in as: <strong><?php echo $_SESSION['role']; ?></strong></p>
    <div class="button-group" style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
        
        <?php if ($role === 'student' || $role === 'candidate'): ?>
            <a href="view_candidates.php"><button style="width: 100%;">View Candidates</button></a>
            <a href="vote.php"><button style="width: 100%;">Cast Your Vote</button></a>
            <a href="auth/candidate_register.php"><button style="width: 100%;">Apply as Candidate</button></a>
        <?php endif; ?>

        <?php if ($role === 'manager' || $role === 'admin'): ?>
            <a href="setup_election.php"><button style="width: 100%; background-color: #34495e;">Election Timing Setup</button></a>
            <a href="manage_candidates.php"><button style="width: 100%; background-color: #34495e;">Manage Candidates</button></a>
            <a href="manage_voters.php"><button style="width: 100%; background-color: #34495e;">Manage Voters</button></a>
            <a href="voter_logs.php"><button style="width: 100%;">View Audit Trail</button></a>
            <a href="results.php"><button style="width: 100%; background-color: #2c3e50;">View Automated Tally</button></a>
        <?php endif; ?>

        <hr style="width: 100%; border: 0; border-top: 1px solid #eee; margin: 10px 0;">
        <a href="auth/logout.php"><button style="width: 100%; background-color: #c0392b;">Logout</button></a>
    </div>
</div>
</body>
</html>