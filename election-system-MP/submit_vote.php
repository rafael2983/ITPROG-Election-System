<?php
session_start();
include("config/db.php");

// Security: Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];
$election_id = $_POST['election_id'] ?? 1;



// 2. Requirement: Double-check for existing votes to prevent bypass
$check_sql = "SELECT id FROM voter_logs WHERE user_id = ? AND election_id = ? AND action = 'Voted'";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $election_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo "<script>alert('Error: You have already voted.'); window.location='dashboard.php';</script>";
    exit();
}

// 3. Process the Ballot
if (isset($_POST['vote']) && is_array($_POST['vote'])) {
    
    // We use a transaction to ensure all votes are saved or none at all
    $conn->begin_transaction();

    try {
        foreach ($_POST['vote'] as $position_id => $candidate_id) {
            // Insert each choice into the 'votes' table
            $vote_sql = "INSERT INTO votes (user_id, candidate_id, election_id, position_id) VALUES (?, ?, ?, ?)";
            $v_stmt = $conn->prepare($vote_sql);
            $v_stmt->bind_param("iiii", $user_id, $candidate_id, $election_id, $position_id);
            $v_stmt->execute();
        }

        // 4. Requirement: Create the Audit Trail entry 
        // This documents exactly which users have submitted their ballots without revealing their choices.
        $log_sql = "INSERT INTO voter_logs (user_id, election_id, action) VALUES (?, ?, 'Voted')";
        $l_stmt = $conn->prepare($log_sql);
        $l_stmt->bind_param("ii", $user_id, $election_id);
        $l_stmt->execute();

        // Commit the transaction
        $conn->commit();

        echo "<script>alert('Vote submitted successfully!'); window.location='results.php';</script>";

    } catch (Exception $e) {
        // If anything fails, undo all changes
        $conn->rollback();
        echo "Error submitting vote: " . $e->getMessage();
    }

} else {
    echo "Invalid ballot submission.";
}
?>