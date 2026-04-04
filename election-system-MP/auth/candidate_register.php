<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Eligibility Check: Block deactivated accounts
$status_stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$status_stmt->bind_param("i", $user_id);
$status_stmt->execute();
$user_data = $status_stmt->get_result()->fetch_assoc();

if ($user_data['status'] === 'inactive') {
    die("<div class='container'><h2>Access Denied</h2><p>Your account is deactivated. You cannot apply.</p><a href='../dashboard.php'>Back</a></div>");
}

// Fetch the positions we just added via SQL
$positions_result = $conn->query("SELECT * FROM positions ORDER BY id ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $position = $_POST['position']; 
    $synopsis = $_POST['synopsis'];
    
    $photo = $_FILES['photo']['name'];
    $target = "../uploads/" . basename($photo);
    move_uploaded_file($_FILES['photo']['tmp_name'], $target);

    $sql = "INSERT INTO candidates (user_id, name, student_id, email, position, photo, synopsis, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $user_id, $name, $student_id, $email, $position, $photo, $synopsis);

    if ($stmt->execute()) {
        echo "<script>alert('Application submitted for review!'); window.location='../dashboard.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate Registration</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="width:500px;">
    <a href="../dashboard.php">← Back to Dashboard</a>
    <h2>Candidate Application</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Full Name:</label>
        <input type="text" name="name" required style="width:100%; padding:8px; margin-bottom:15px;">

        <label>Student ID:</label>
        <input type="text" name="student_id" required style="width:100%; padding:8px; margin-bottom:15px;">

        <label>Email Address:</label>
        <input type="email" name="email" required style="width:100%; padding:8px; margin-bottom:15px;">

        <label>Position Running For:</label>
        <select name="position" required style="width:100%; padding:10px; margin-bottom:15px;">
            <option value="">-- Choose Position --</option>
            <?php while($row = $positions_result->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($row['position_name']); ?>">
                    <?php echo htmlspecialchars($row['position_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Profile Photo:</label>
        <input type="file" name="photo" accept="image/*" required style="width:100%; margin-bottom:15px;">

        <label>Synopsis of Goals:</label>
        <textarea name="synopsis" placeholder="Describe your plans for the organization..." required style="width:100%; height:120px; margin-bottom:20px; padding:8px;"></textarea>

        <button type="submit" style="width:100%; background:#3498db; color:white; padding:12px; cursor:pointer;">Submit Application</button>
    </form>
</div>
</body>
</html>