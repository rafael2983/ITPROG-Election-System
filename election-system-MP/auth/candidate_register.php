<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $position = $_POST['position'];
    $synopsis = $_POST['synopsis'];

    // Photo Upload
    $targetDir = "../uploads/";
    $fileName = time() . "_" . basename($_FILES["photo"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath);

    $sql = "INSERT INTO candidates 
            (user_id, name, student_id, email, position, photo, synopsis)
            VALUES 
            ('$user_id', '$name', '$student_id', '$email', '$position', '$fileName', '$synopsis')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Candidate application submitted! Awaiting approval.'); window.location='../dashboard.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<link rel="stylesheet" href="../assets/style.css">

<div class="container" style="width:400px;">
    <form method="POST" enctype="multipart/form-data">
        <h2>Candidate Registration</h2>

        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="student_id" placeholder="Student ID" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="position" placeholder="Position Running For" required>

        <textarea name="synopsis" placeholder="Synopsis of Goals" required
            style="width:100%; padding:10px; margin:8px 0;"></textarea>

        <input type="file" name="photo" accept="image/*" required>

        <button type="submit">Submit Application</button>
    </form>
</div>