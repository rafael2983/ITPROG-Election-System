<?php
session_start();
include("config/db.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['committee', 'manager', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: unauthorized.php");
    exit();
}

// Delete Voters
if (isset($_GET['delete_id'])) {
    if (in_array($_SESSION['role'], ['manager', 'admin'])) {
        $id = $_GET['delete_id'];
        if ($conn->query("DELETE FROM users WHERE id='$id' AND role='student'") === TRUE) {
            echo "<script>alert('Voter permanently deleted.');</script>";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('Access denied. Only managers and admins can permanently delete voters.');</script>";
    }
}

// Fetch Voters
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";
$voters = $conn->query("
    SELECT  u.id, u.full_name, u.student_id, u.email, u.role, u.created_at,
            COUNT(DISTINCT CONCAT(v.election_id, '-', v.position_id)) AS total_votes
    FROM    users u
    LEFT JOIN votes v ON v.user_id = u.id
    WHERE   u.role = 'student'
      AND  (u.full_name LIKE '$search' OR u.email LIKE '$search' OR u.student_id LIKE '$search')
    GROUP BY u.id
    ORDER BY u.full_name
");

// Search for Single Voter
$view_voter = null;
if (isset($_GET['view_id'])) {
    $view_voter = $conn->query("
        SELECT u.id, u.full_name, u.student_id, u.email, u.role, u.created_at,
               COUNT(DISTINCT CONCAT(v.election_id, '-', v.position_id)) AS total_votes
        FROM   users u
        LEFT JOIN votes v ON v.user_id = u.id
        WHERE  u.id='" . $_GET['view_id'] . "' AND u.role='student'
        GROUP BY u.id
    ")->fetch_assoc();
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container" style="width:900px;">

    <h2>Voter Management</h2>
    <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></strong>
       &nbsp;|&nbsp; Role: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong></p>
    <hr style="margin:10px 0 16px;">

    <!-- Search -->
    <form method="GET" style="margin-bottom:16px;">
        <input type="text" name="search" placeholder="Search by name, email, or student ID"
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                style="padding:6px 10px; width:300px;">
        <button type="submit" >Search</button>
        <a href="manage_voters.php"><button type="button">Clear</button></a>
        <a href="dashboard.php"><button type="button">Dashboard</button></a>
    </form>

    <!-- View Voter Details -->
    <?php if ($view_voter): ?>
    <div style="border:1px solid #ccc; border-radius:8px; padding:20px; margin-bottom:20px; background:#f9f9f9;">
        <h3 style="margin-bottom:14px;">Voter Details</h3>

        <div style="display:flex; align-items:center; gap:16px; margin-bottom:14px;">
            <div style="width:70px; height:70px; border-radius:50%; background:#ddd; display:flex; align-items:center; justify-content:center; font-size:26px; color:#888;">
                &#128100;
            </div>
            <div>
                <strong style="font-size:16px;"><?= htmlspecialchars($view_voter['full_name']) ?></strong><br>
                <span style="font-size:13px; color:#555;"><?= htmlspecialchars($view_voter['student_id']) ?></span><br>
                <span style="font-size:13px; color:#555;"><?= htmlspecialchars($view_voter['email']) ?></span><br>
                <span style="font-size:12px; color:#888;">Registered: <?= htmlspecialchars($view_voter['created_at']) ?></span>
            </div>
        </div>

        <p style="margin-top:8px;"><strong>Votes Cast:</strong> <?= $view_voter['total_votes'] ?></p>

        <div style="margin-top:14px;">
            <a href="manage_voters.php<?= isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '' ?>">
                <button type="button">Close</button>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Voter List -->
    <?php if ($voters->num_rows === 0): ?>
        <p>No voters found.</p>
    <?php else: while ($row = $voters->fetch_assoc()): ?>

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
                <a href="?view_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">View</a>

                <?php if (in_array($_SESSION['role'], ['manager', 'admin'])): ?>
                    &nbsp;|&nbsp;
                    <a href="?delete_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       style="color:red;"
                       onclick="return confirm('Permanently delete this voter and all their votes? This cannot be undone.')">
                        Delete
                    </a>
                <?php endif; ?>
            </div>

        </div>

    <?php endwhile; endif; ?>

</div>
