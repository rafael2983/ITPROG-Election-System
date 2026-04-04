<?php
session_start();
include("config/db.php");

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'manager' && $role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$election_id = 1;
$stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->bind_param("i", $election_id);
$stmt->execute();
$election = $stmt->get_result()->fetch_assoc();

// Requirement: Cannot change dates once the period has started
$has_started = (strtotime($election['start_date']) <= time());

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$has_started) {
    $title = $_POST['title'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];

    $update = $conn->prepare("UPDATE elections SET title = ?, start_date = ?, end_date = ? WHERE id = ?");
    $update->bind_param("sssi", $title, $start, $end, $election_id);
    
    if ($update->execute()) {
        echo "<script>alert('Election dates updated!'); window.location='setup_election.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Setup</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="width:500px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back</a>
    <h2>Election & Timing Setup</h2>

    <?php if ($has_started): ?>
        <div style="background:#fee; color:#c0392b; padding:15px; border-radius:5px; margin-bottom:20px;">
            <strong>Lock Active:</strong> The voting period has already started. You cannot modify election details to prevent bias.
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Election Title:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($election['title']); ?>" <?php echo $has_started ? 'disabled' : 'required'; ?> style="width:100%; padding:8px; margin-bottom:15px;">

        <label>Start Date & Time:</label>
        <input type="datetime-local" name="start_date" value="<?php echo date('Y-m-d\TH:i', strtotime($election['start_date'])); ?>" <?php echo $has_started ? 'disabled' : 'required'; ?> style="width:100%; padding:8px; margin-bottom:15px;">

        <label>End Date & Time:</label>
        <input type="datetime-local" name="end_date" value="<?php echo date('Y-m-d\TH:i', strtotime($election['end_date'])); ?>" <?php echo $has_started ? 'disabled' : 'required'; ?> style="width:100%; padding:8px; margin-bottom:15px;">

        <?php if (!$has_started): ?>
            <button type="submit" style="width:100%; background:#2c3e50; color:white;">Save Election Setup</button>
        <?php endif; ?>
    </form>
</div>
</body>
</html>