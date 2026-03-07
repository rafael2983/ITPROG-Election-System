<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container">
    <h2>Welcome, <?php echo $_SESSION['name']; ?>!</h2>
    <p>Your role: <?php echo $_SESSION['role']; ?></p>
    <a href="auth/logout.php"><button>Logout</button></a>
</div>

<a href="auth/candidate_register.php">
    <button style="margin-top:10px;">Apply as Candidate</button>
</a>