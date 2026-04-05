<?php

$allowed_roles = ['committee', 'manager', 'admin'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
    <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p>
    
    <div class="button-group" style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
        
        <a href="view_candidates.php"><button style="width: 100%;">View Candidates & Positions</button></a>
        <a href="vote.php"><button style="width: 100%;">Cast Your Vote</button></a>
        <a href="auth/candidate_register.php"><button style="width: 100%;">Apply as Candidate</button></a>

        <?php if ($role === 'committee' || $role === 'manager' || $role === 'admin'): ?>
            <a href="voter_logs.php"><button style="width: 100%; background-color: #34495e; color: white;">View Voter Logs (Audit Trail)</button></a>
        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], $allowed_roles)): ?>
            <a href="setup_election.php"><button style="width: 100%; background-color: #34495e; color: white;">Election Timing Setup</button></a>
            <a href="manage_candidates.php"><button style="width: 100%; background-color: #34495e; color: white;">Manage Candidates</button></a>
            <a href="manage_voters.php"><button style="width: 100%; background-color: #34495e; color: white;">Manage Voters</button></a>
        <?php endif; ?>

        <?php if ($role == 'manager' || $role == 'admin'): ?>
            <a href="manage_committee.php"><button style="width: 100%; background-color: #34495e; color: white;">Manage Committee</button></a>
            <a href="manage_users.php"><button style="width: 100%; background-color: #34495e; color: white;">Manage Users</button></a>
        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], $allowed_roles)): ?>
            <a href="results.php"><button style="width: 100%; background-color: #2c3e50; color: white;">View Automated Vote Tally</button></a>
        <?php endif; ?>

        <hr style="width: 100%; border: 0; border-top: 1px solid #eee; margin: 10px 0;">
        <a href="auth/logout.php"><button style="width: 100%; background-color: #c0392b; color: white;">Logout</button></a>
    </div>
</div>
</body>
</html>
