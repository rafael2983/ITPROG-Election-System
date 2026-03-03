<?php
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (student_id, full_name, email, password)
            VALUES ('$student_id', '$full_name', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Registration successful!'); window.location='login.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<link rel="stylesheet" href="../assets/style.css">

<div class="container">
    <form method="POST">
        <h2>Create Account</h2>
        <input type="text" name="student_id" placeholder="Student ID" required>
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
</div>