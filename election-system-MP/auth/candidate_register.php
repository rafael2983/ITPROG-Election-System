<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the user has already applied as a candidate
$check_stmt = $conn->prepare("SELECT id FROM candidates WHERE user_id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    die("<div class='container' style='text-align:center; margin-top:50px;'>
            <h2>Application Already Submitted</h2>
            <p>You have already submitted a candidate application for this election. You cannot apply more than once.</p>
            <a href='../dashboard.php'><button style='padding:10px 20px; cursor:pointer;'>Back to Dashboard</button></a>
          </div>");
}

// Fetch the official student ID from the users table for validation
$user_stmt = $conn->prepare("SELECT student_id FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$actual_student_id = $user_data['student_id'];

$positions_result = $conn->query("SELECT * FROM positions ORDER BY id ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submitted_id = $_POST['student_id'];
    
    if ($submitted_id !== $actual_student_id) {
        echo "<script>alert('Error: The Student ID entered does not match your account.');</script>";
    } else {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $position = $_POST['position']; 
        $synopsis = $_POST['synopsis'];
        
        $photo = $_FILES['photo']['name'];
        $target = "../uploads/" . basename($photo);
        move_uploaded_file($_FILES['photo']['tmp_name'], $target);

        $sql = "INSERT INTO candidates (user_id, name, student_id, email, position, photo, synopsis, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $user_id, $name, $submitted_id, $email, $position, $photo, $synopsis);

        if ($stmt->execute()) {
            echo "<script>alert('Application submitted successfully!'); window.location='../dashboard.php';</script>";
        }
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
    <a href="../dashboard.php" style="text-decoration:none; color:#3498db;">← Back to Dashboard</a>
    <h2>Candidate Application</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Full Name:</label>
        <input type="text" name="name" required style="width:100%; padding:8px; margin-bottom:15px;">

        <label>Student ID:</label>
        <input type="text" name="student_id" value="<?php echo htmlspecialchars($actual_student_id); ?>" readonly style="width:100%; padding:8px; margin-bottom:15px; background:#f4f4f4; color:#555;">

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
        <textarea name="synopsis" required style="width:100%; height:120px; margin-bottom:20px; padding:8px;"></textarea>

        <button type="submit" style="width:100%; background:#3498db; color:white; padding:12px; cursor:pointer; border:none; border-radius:4px;">Submit Application</button>
    </form>
</div>
</body>
</html>