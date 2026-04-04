<?php
session_start();
include("config/db.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//Only Roles allowed to access this php: Committee, Manager, and Admin
$allowed_roles = ['committee', 'manager', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

//Approval of Candidates: Committee, Manager, and Admin
if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];
    if ($conn->query("UPDATE candidates SET status='approved' WHERE id='$id'") === TRUE) {
        echo "<script>alert('Candidate approved and added to the election.');</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

//Rejection of Candidates: Committee, Manager, and Admin
if (isset($_GET['reject_id'])) {
    $id = $_GET['reject_id'];
    if ($conn->query("UPDATE candidates SET status='rejected' WHERE id='$id'") === TRUE) {
        echo "<script>alert('Candidate has been removed from the election.');</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

//Removal of Candidates: Manager, and Admin
if (isset($_GET['delete_id'])) {
    if (in_array($_SESSION['role'], ['manager', 'admin'])) {
        $id = $_GET['delete_id'];
        if ($conn->query("DELETE FROM candidates WHERE id='$id'") === TRUE) {
            echo "<script>alert('Candidate permanently deleted.');</script>";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('Access denied. Only managers and admins can permanently delete candidates.');</script>";
    }
}


$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";
$candidates = $conn->query("
    SELECT  c.id, c.name, c.student_id, c.email, c.position,
            c.status, c.synopsis, c.photo, c.created_at
    FROM    candidates c
    JOIN    users u ON u.id = c.user_id
    WHERE   c.name LIKE '$search' OR c.position LIKE '$search' OR c.student_id LIKE '$search'
    ORDER BY c.position, c.name
");


$view_candidate = null;
if (isset($_GET['view_id'])) {
    $view_candidate = $conn->query("
        SELECT c.*
        FROM   candidates c
        JOIN   users u ON u.id = c.user_id
        WHERE  c.id='" . $_GET['view_id'] . "'
    ")->fetch_assoc();
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container" style="width:900px;">

    <h2>Candidate Management</h2>
    <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? '') ?></strong>
       &nbsp;|&nbsp; Role: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong></p>
    <hr style="margin:10px 0 16px;">

    <!-- Search for Candidate-->
    <form method="GET" style="margin-bottom:16px;">
        <input type="text" name="search" placeholder="Search by name, position, or student ID"
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
               style="padding:6px 10px; width:300px;">
        <button type="submit">Search</button>
        <a href="candidate_management.php"><button type="button">Clear</button></a>
        <a href="dashboard.php"><button type="button">Dashboard</button></a>
    </form>

    <!-- Candidate Viewing -->
    <?php if ($view_candidate): ?>
    <div style="border:1px solid #ccc; border-radius:8px; padding:20px; margin-bottom:20px; background:#f9f9f9;">
        <h3 style="margin-bottom:14px;">Candidate Details</h3>

        <div style="display:flex; align-items:flex-start; gap:20px;">

            <?php if ($view_candidate['photo']): ?>
            <img src="../uploads/<?= htmlspecialchars($view_candidate['photo']) ?>"
                 width="110" style="border-radius:8px; flex-shrink:0;" alt="Candidate Photo">
            <?php endif; ?>

            <div>
                <p><strong>Name:</strong> <?= htmlspecialchars($view_candidate['name']) ?></p>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($view_candidate['student_id']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($view_candidate['email']) ?></p>
                <p><strong>Position:</strong> <?= htmlspecialchars($view_candidate['position']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($view_candidate['status']) ?></p>
                <p><strong>Date Added:</strong> <?= htmlspecialchars($view_candidate['created_at']) ?></p>
            </div>

        </div>

        <div style="margin-top:14px;">
            <strong>Synopsis:</strong><br>
            <p style="margin-top:6px;"><?= nl2br(htmlspecialchars($view_candidate['synopsis'])) ?></p>
        </div>

        <div style="margin-top:14px;">
            <a href="candidate_management.php<?= isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '' ?>">
                <button type="button">Close</button>
            </a>
        </div>
        
    </div>
    <?php endif; ?>

    <!-- Candidate List -->
    <?php if ($candidates->num_rows === 0): ?>
        <p>No candidates found.</p>
    <?php else: $i = 1; while ($row = $candidates->fetch_assoc()): ?>

        <div style="display:flex; align-items:center; justify-content:space-between; border:1px solid #ddd; border-radius:8px; padding:14px 16px; margin-bottom:10px; background:#fff;">

            <div style="display:flex; align-items:center; gap:16px;">

                <?php if ($row['photo']): ?>
                    <img src="../uploads/<?= htmlspecialchars($row['photo']) ?>"
                         width="60" style="border-radius:6px; flex-shrink:0;" alt="photo">
                <?php else: ?>
                    <div style="width:60px; height:60px; background:#eee; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#999; font-size:12px;">
                        No Photo
                    </div>
                <?php endif; ?>

                <div>
                    <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                    <span style="font-size:13px; color:#555;">
                        <?= htmlspecialchars($row['position']) ?> &nbsp;&bull;&nbsp;
                        <?= htmlspecialchars($row['student_id']) ?> &nbsp;&bull;&nbsp;
                        <?= htmlspecialchars($row['email']) ?>
                    </span><br>
                    <span style="font-size:12px; color:#888;">Added: <?= htmlspecialchars($row['created_at']) ?></span>
                </div>

            </div>

            <div style="text-align:right; flex-shrink:0; margin-left:16px;">

                <!-- Status of Candidate: Approved or Rejected -->
                <?php
                    $badge_color = '#888';
                    if ($row['status'] === 'approved') $badge_color = 'green';
                    if ($row['status'] === 'rejected')  $badge_color = 'red';
                ?>
                <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; color:#fff; background:<?= $badge_color ?>; margin-bottom:8px;">
                    <?= ucfirst($row['status']) ?>
                </span>

                <br>

                <!-- Actions -->
                <a href="?view_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">View</a>

                <?php if ($row['status'] !== 'approved'): ?>
                    &nbsp;|&nbsp;
                    <a href="?approve_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       onclick="return confirm('Approve this candidate and add them to the election?')">
                        Approve
                    </a>
                <?php endif; ?>

                <?php if ($row['status'] !== 'rejected'): ?>
                    &nbsp;|&nbsp;
                    <a href="?reject_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       onclick="return confirm('Remove this candidate from the election?')">
                        Reject
                    </a>
                <?php endif; ?>

                <?php if (in_array($_SESSION['role'], ['manager', 'admin'])): ?>
                    &nbsp;|&nbsp;
                    <a href="?delete_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       style="color:red;"
                       onclick="return confirm('Permanently delete this candidate from the database? This cannot be undone.')">
                        Remove
                    </a>
                <?php endif; ?>

            </div>

        </div>

    <?php endwhile; endif; ?>
    
</div>
