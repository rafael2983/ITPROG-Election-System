<?php
session_start();
include("config/db.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['manager', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: unauthorized.php");
    exit();
}

// Delete Users
if (isset($_GET['delete_id'])) {
    if (in_array($_SESSION['role'], $allowed_roles)) {
        $id = $_GET['delete_id'];
        if ($conn->query("DELETE FROM users WHERE id='$id' AND role IN ('student', 'candidate')") === TRUE) {
            echo "<script>alert('User permanently deleted.');</script>";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('Access denied. Only managers and admins can delete users.');</script>";
    }
}

// Search
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";
$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['student', 'candidate']) ? $_GET['filter'] : '';
$role_condition = $filter ? "u.role = '$filter'" : "u.role IN ('student', 'candidate')";

$users = $conn->query("
    SELECT  u.id, u.full_name, u.student_id, u.email, u.role, u.created_at,
            COUNT(DISTINCT CONCAT(v.election_id, '-', v.position_id)) AS total_votes
    FROM    users u
    LEFT JOIN votes v ON v.user_id = u.id
    WHERE   $role_condition
      AND  (u.full_name LIKE '$search' OR u.email LIKE '$search' OR u.student_id LIKE '$search')
    GROUP BY u.id
    ORDER BY u.role, u.full_name
");

// Single User View
$view_user = null;
if (isset($_GET['view_id'])) {
    $view_user = $conn->query("
        SELECT  u.id, u.full_name, u.student_id, u.email, u.role, u.created_at,
                COUNT(DISTINCT CONCAT(v.election_id, '-', v.position_id)) AS total_votes
        FROM    users u
        LEFT JOIN votes v ON v.user_id = u.id
        WHERE   u.id='" . $_GET['view_id'] . "' AND u.role IN ('student', 'candidate')
        GROUP BY u.id
    ")->fetch_assoc();
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container" style="width:900px;">

    <h2>User Management</h2>
    <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></strong>
       &nbsp;|&nbsp; Role: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong></p>
    <hr style="margin:10px 0 16px;">

    <!-- Search + Filter -->
    <form method="GET" action="manage_users.php" style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap;">
        <input type="text" name="search" placeholder="Search by name, email, or student ID"
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
               style="padding:8px 12px; width:800px;">
        <select name="filter" style="padding:8px 12px;">
            <option value="">All Users</option>
            <option value="student"   <?= ($_GET['filter'] ?? '') === 'student'   ? 'selected' : '' ?>>Students</option>
            <option value="candidate" <?= ($_GET['filter'] ?? '') === 'candidate' ? 'selected' : '' ?>>Candidates</option>
        </select>
        <button type="submit" style="width:auto;">Search</button>
        <a href="manage_users.php"><button type="button" style="width:auto;">Clear</button></a>
        <a href="dashboard.php"><button type="button" style="width:auto;">Dashboard</button></a>
    </form>

    <!-- View User Details -->
    <?php if ($view_user): ?>
    <div style="border:1px solid #ccc; border-radius:8px; padding:20px; margin-bottom:20px; background:#f9f9f9;">
        <h3 style="margin-bottom:14px;">User Details</h3>

        <div style="display:flex; align-items:center; gap:16px; margin-bottom:14px;">
            <div style="width:70px; height:70px; border-radius:50%; background:#ddd; display:flex; align-items:center; justify-content:center; font-size:26px; color:#888;">
                &#128100;
            </div>
            <div>
                <strong style="font-size:16px;"><?= htmlspecialchars($view_user['full_name']) ?></strong><br>
                <span style="font-size:13px; color:#555;"><?= htmlspecialchars($view_user['student_id']) ?></span><br>
                <span style="font-size:13px; color:#555;"><?= htmlspecialchars($view_user['email']) ?></span><br>
                <span style="font-size:12px; color:#888;">Registered: <?= htmlspecialchars($view_user['created_at']) ?></span>
            </div>
        </div>

        <p><strong>Role:</strong>
            <?php $rc = $view_user['role'] === 'candidate' ? '#e67e22' : '#27ae60'; ?>
            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:bold; color:#fff; background:<?= $rc ?>;">
                <?= ucfirst($view_user['role']) ?>
            </span>
        </p>
        <p style="margin-top:8px;"><strong>Votes Cast:</strong> <?= $view_user['total_votes'] ?></p>

        <div style="margin-top:14px;">
            <a href="manage_users.php<?= isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '' ?>">
                <button type="button" style="padding:6px 14px; font-size:13px;">Close</button>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- User List -->
    <?php if ($users->num_rows === 0): ?>
        <p>No users found.</p>
    <?php else: while ($row = $users->fetch_assoc()): ?>

        <div style="display:flex; align-items:center; justify-content:space-between; border:1px solid #ddd; border-radius:8px; padding:14px 16px; margin-bottom:10px; background:#fff;">

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
                    <span style="font-size:12px; color:#888;">
                        Votes cast: <?= $row['total_votes'] ?> &nbsp;&bull;&nbsp;
                        Registered: <?= htmlspecialchars($row['created_at']) ?>
                    </span>
                </div>
            </div>

            <div style="text-align:right; flex-shrink:0; margin-left:16px;">

                <!-- Role: Voter or Candidate -->
                <?php $badge_color = $row['role'] === 'candidate' ? '#e67e22' : '#27ae60'; ?>
                <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; color:#fff; background:<?= $badge_color ?>; margin-bottom:8px;">
                    <?= ucfirst($row['role']) ?>
                </span>

                <br>

                <!-- Actions -->
                <a href="manage_users.php?view_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= isset($_GET['filter']) ? '&filter=' . urlencode($_GET['filter']) : '' ?>">View</a>

                <?php if (in_array($_SESSION['role'], ['manager', 'admin'])): ?>
                    &nbsp;|&nbsp;
                    <a href="manage_users.php?delete_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= isset($_GET['filter']) ? '&filter=' . urlencode($_GET['filter']) : '' ?>"
                       style="color:red;"
                       onclick="return confirm('Permanently delete this user and all their votes? This cannot be undone.')">
                        Delete
                    </a>
                <?php endif; ?>

            </div>

        </div>

    <?php endwhile; endif; ?>

</div>
