<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];

            header("Location: ../dashboard.php");
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No account found.";
    }
}
?>

<link rel="stylesheet" href="../assets/style.css">

<div class="container">
    <form method="POST">
        <h2>Login</h2>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <p>No account? <a href="register.php">Register</a></p>
    </form>
</div>