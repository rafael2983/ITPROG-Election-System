<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];
$election_id = $_POST['election_id'] ?? 1;

// 1. Time-Gate Security: Verify the voting period is currently active
$election_stmt = $conn->prepare("SELECT start_date, end_date FROM elections WHERE id = ?");
$election_stmt->bind_param("i", $election_id);
$election_stmt->execute();
$election = $election_stmt->get_result()->fetch_assoc();

if (!$election) {
    die("Error: Election record not found.");
}

$start_time = strtotime($election['start_date']);
$end_time = strtotime($election['end_date']);
$current_time = time();

if ($current_time < $start_time) {
    echo "<script>alert('Error: The voting period has not started yet.'); window.location='dashboard.php';</script>";
    exit();
}

if ($current_time > $end_time) {
    echo "<script>alert('Error: The voting period has officially closed.'); window.location='dashboard.php';</script>";
    exit();
}

// 2. Integrity Check: Ensure the user has not already voted
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
    
    $conn->begin_transaction();

    try {
        foreach ($_POST['vote'] as $position_id => $candidate_id) {
            $vote_sql = "INSERT INTO votes (user_id, candidate_id, election_id, position_id) VALUES (?, ?, ?, ?)";
            $v_stmt = $conn->prepare($vote_sql);
            $v_stmt->bind_param("iiii", $user_id, $candidate_id, $election_id, $position_id);
            $v_stmt->execute();
        }

        // 4. Audit Trail: Document participation without revealing specific choices
        $log_sql = "INSERT INTO voter_logs (user_id, election_id, action, timestamp) VALUES (?, ?, 'Voted', NOW())";
        $l_stmt = $conn->prepare($log_sql);
        $l_stmt->bind_param("ii", $user_id, $election_id);
        $l_stmt->execute();

        $conn->commit();
        echo "<script>alert('Vote submitted successfully!'); window.location='results.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error submitting vote: " . htmlspecialchars($e->getMessage());
    }

} else {
    echo "Invalid ballot submission. No candidates were selected.";
}
?>