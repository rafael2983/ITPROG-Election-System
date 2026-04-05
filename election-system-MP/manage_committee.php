<?php
session_start();
include("config/db.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Only admin and manager can access this page
if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: unauthorized.php");
    exit();
}

// Account Creation
if (isset($_POST['create_account'])) {
    $full_name  = $_POST['full_name'];
    $student_id = $_POST['student_id'];
    $email      = $_POST['email'];
    $role       = $_POST['role'];
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $allowed_roles = ['committee', 'manager'];
    if (!in_array($role, $allowed_roles)) {
        echo "<script>alert('Invalid role selected.');</script>";
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email='$email' OR student_id='$student_id'");
        if ($check->num_rows > 0) {
            echo "<script>alert('Email or Student ID already exists.');</script>";
        } else {
            $sql = "INSERT INTO users (full_name, student_id, email, password, role)
                    VALUES ('$full_name', '$student_id', '$email', '$password', '$role')";
            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Account created successfully.');</script>";
            } else {
                echo "Error: " . $conn->error;
            }
        }
    }
}

// Delete Account
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    // Prevent admin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete your own account.');</script>";
    } else {
        if ($conn->query("DELETE FROM users WHERE id='$id' AND role IN ('committee', 'manager')") === TRUE) {
            echo "<script>alert('Account permanently deleted.');</script>";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Fetch Accounts
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";
$accounts = $conn->query("
    SELECT  id, full_name, student_id, email, role, created_at
    FROM    users
    WHERE   role IN ('committee', 'manager')
      AND  (full_name LIKE '$search' OR email LIKE '$search' OR student_id LIKE '$search')
    ORDER BY role, full_name
");

// View Single Account
$view_account = null;
if (isset($_GET['view_id'])) {
    $view_account = $conn->query("
        SELECT id, full_name, student_id, email, role, created_at
        FROM   users
        WHERE  id='" . $_GET['view_id'] . "' AND role IN ('committee', 'manager')
    ")->fetch_assoc();
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container" style="width:900px;">

    <h2>Account Management</h2>
    <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></strong>
       &nbsp;|&nbsp; Role: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong></p>
    <hr style="margin:10px 0 16px;">

    <!-- Search & Create -->
    <form method="GET" style="margin-bottom:16px;" style="margin-bottom:16px;">
        <input type="text" name="search" placeholder="Search by name, email, or student ID"
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                style="padding:6px 10px; width:300px;">
        <button type="submit" >Search</button>
        <a href="manage_committee.php"><button type="button">Clear</button></a>
        <button type="button" onclick="document.getElementById('create-account-form').style.display='block'">Create Account</button>
        <a href="dashboard.php"><button type="button">Dashboard</button></a>
    </form>

    <!-- Create Account Form -->
    <div id="create-account-form" style="display:none; border:1px solid #ccc; border-radius:8px; padding:20px; margin-bottom:20px; background:#f9f9f9;">
        <h3 style="margin-bottom:14px;">Create New Account</h3>
        <form method="POST">
            <div style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
                <div>
                    <label>Full Name<br>
                        <input type="text" name="full_name" placeholder="Full Name" required style="padding:6px 10px; width:200px;">
                    </label>
                </div>
                <div>
                    <label>Student ID<br>
                        <input type="text" name="student_id" placeholder="Student ID" required style="padding:6px 10px; width:160px;">
                    </label>
                </div>
                <div>
                    <label>Email<br>
                        <input type="email" name="email" placeholder="Email Address" required style="padding:6px 10px; width:200px;">
                    </label>
                </div>
                <div>
                    <label>Password<br>
                        <input type="password" name="password" placeholder="Password" required style="padding:6px 10px; width:160px;">
                    </label>
                </div>
                <div>
                    <label>Role<br>
                        <select name="role" required style="padding:6px 10px; width:140px;">
                            <option value="">-- Select Role --</option>
                            <option value="committee">Committee</option>
                            <option value="manager">Manager</option>
                        </select>
                    </label>
                </div>
            </div>
            <button type="submit" name="create_account" style="padding:6px 14px; font-size:13px;">Create Account</button>
            <button type="button" style="padding:6px 14px; font-size:13px;"
                    onclick="document.getElementById('create-account-form').style.display='none'">Cancel</button>
        </form>
    </div>

    <!-- View Account Details -->
    <?php if ($view_account): ?>
    <div style="border:1px solid #ccc; border-radius:8px; padding:20px; margin-bottom:20px; background:#f9f9f9;">
        <h3 style="margin-bottom:14px;">Account Details</h3>

        <div style="display:flex; align-items:center; gap:16px; margin-bottom:14px;">
            <div style="width:70px; height:70px; border-radius:50%; background:#ddd; display:flex; align-items:center; justify-content:center; font-size:26px; color:#888;">
                &#128100;
            </div>
            <div>
                <strong style="font-size:16px;"><?= htmlspecialchars($view_account['full_name']) ?></strong><br>
                <span style="font-size:13px; color:#555;"><?= htmlspecialchars($view_account['student_id']) ?></span><br>
                <span style="font-size:13px; color:#555;"><?= htmlspecialchars($view_account['email']) ?></span><br>
                <span style="font-size:12px; color:#888;">Created: <?= htmlspecialchars($view_account['created_at']) ?></span>
            </div>
        </div>

        <p><strong>Role:</strong>
            <?php $rc = $view_account['role'] === 'manager' ? '#2563c4' : '#6d28d9'; ?>
            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:bold; color:#fff; background:<?= $rc ?>;">
                <?= ucfirst($view_account['role']) ?>
            </span>
        </p>

        <div style="margin-top:14px;">
            <a href="manage_committee.php<?= isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '' ?>">
                <button type="button" style="padding:6px 14px; font-size:13px;">Close</button>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Account List -->
    <?php if ($accounts->num_rows === 0): ?>
        <p>No accounts found.</p>
    <?php else: while ($row = $accounts->fetch_assoc()): ?>

        <div style="display:flex; align-items:center; justify-content:space-between; border:1px solid #ddd; border-radius:8px; padding:14px 16px; margin-bottom:10px; background:#fff;">

            <!-- Left: avatar + info -->
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="width:50px; height:50px; border-radius:50%; background:#eee; display:flex; align-items:center; justify-content:center; font-size:22px; color:#999; flex-shrink:0;">
                    &#128100;
                </div>
                <div>
                    <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                    <span style="font-size:13px; color:#555;">
                        <?= htmlspecialchars($row['student_id']) ?> &nbsp;&bull;&nbsp;
                        <?= htmlspecialchars($row['email']) ?>
                    </span><br>
                    <span style="font-size:12px; color:#888;">Created: <?= htmlspecialchars($row['created_at']) ?></span>
                </div>
            </div>

            <div style="text-align:right; flex-shrink:0; margin-left:16px;">

                <!-- Roles -->
                <?php $badge_color = $row['role'] === 'manager' ? '#2563c4' : '#6d28d9'; ?>
                <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; color:#fff; background:<?= $badge_color ?>; margin-bottom:8px;">
                    <?= ucfirst($row['role']) ?>
                </span>

                <br>

                <!--- Delete --->
                &nbsp;|&nbsp;
                <a href="?delete_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                   style="color:red;"
                   onclick="return confirm('Permanently delete this account? This cannot be undone.')">
                    Delete
                </a>

            </div>

        </div>

    <?php endwhile; endif; ?>

</div>
