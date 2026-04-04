<?php
session_start();
include("config/db.php");

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'manager' && $role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$voter_id = $_GET['id'] ?? null;
if (!$voter_id) die("No ID provided.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, student_id = ?, email = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $student_id, $email, $voter_id);
    if ($stmt->execute()) {
        echo "<script>alert('Updated!'); window.location='manage_voters.php';</script>";
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Voter</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="width:500px;">
    <a href="manage_voters.php">← Back</a>
    <h2>Edit Voter</h2>
    <form method="POST">
        <label>Full Name:</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($voter['full_name']); ?>" required style="width:100%; padding:8px; margin-bottom:15px;">
        <label>Student ID:</label>
        <input type="text" name="student_id" value="<?php echo htmlspecialchars($voter['student_id']); ?>" required style="width:100%; padding:8px; margin-bottom:15px;">
        <label>Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($voter['email']); ?>" required style="width:100%; padding:8px; margin-bottom:15px;">
        <button type="submit" style="width:100%; background:#27ae60; color:white; padding:10px;">Update</button>
    </form>
</div>
</body>
</html>