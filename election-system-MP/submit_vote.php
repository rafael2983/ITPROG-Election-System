<?php
session_start();
include("config/db.php");

$user_id = $_SESSION['user_id'];

$election_id = 1; // for now assume 1 election

foreach ($_POST['vote'] as $position_id => $candidate_id) {

    $sql = "INSERT INTO votes (user_id,candidate_id,election_id,position_id)
        VALUES ('$user_id','$candidate_id','$election_id','$position_id')";

    $conn->query($sql);

}

// Log the voting action
$log_sql = "INSERT INTO voter_logs (user_id, election_id, action)
            VALUES ('$user_id', '$election_id', 'Voted')";
$conn->query($log_sql);

echo "<script>alert('Vote submitted successfully!'); window.location='results.php';</script>";
?>