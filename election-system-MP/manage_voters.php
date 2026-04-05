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

// Delete Votes
if (isset($_GET['delete_id'])) {
    if (in_array($_SESSION['role'], ['manager', 'admin'])) {
        $id = $_GET['delete_id'];
        if ($conn->query("DELETE FROM votes WHERE id='$id'") === TRUE) {
            echo "<script>alert('Vote record deleted.');</script>";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('Access denied. Only managers and admins can delete vote records.');</script>";
    }
}

// Search for Voter
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";
$votes = $conn->query("
    SELECT  v.id, v.created_at,
            u.full_name AS voter_name, u.student_id,
            c.name AS candidate_name,
            c.position AS position_name,
            e.title AS election_title
    FROM    votes v
    JOIN    users u ON u.id = v.user_id
    JOIN    candidates c ON c.id = v.candidate_id
    JOIN    elections e ON e.id = v.election_id
    WHERE   u.full_name LIKE '$search' OR u.student_id LIKE '$search' OR e.title LIKE '$search' OR c.position LIKE '$search'
    ORDER BY e.title, c.position, v.created_at DESC
");

// Singe Vote View
$view_vote = null;
if (isset($_GET['view_id'])) {
    $view_vote = $conn->query("
        SELECT  v.id, v.created_at,
                u.full_name AS voter_name, u.student_id, u.email,
                c.name AS candidate_name, c.position AS position_name,
                e.title AS election_title, e.start_date, e.end_date
        FROM    votes v
        JOIN    users u ON u.id = v.user_id
        JOIN    candidates c ON c.id = v.candidate_id
        JOIN    elections e ON e.id = v.election_id
        WHERE   v.id='" . $_GET['view_id'] . "'
    ")->fetch_assoc();
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container" style="width:900px;">

    <h2>Vote Management</h2>
    <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></strong>
       &nbsp;|&nbsp; Role: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong></p>
    <hr style="margin:10px 0 16px;">

    <!-- Search -->
    <form method="GET" action="manage_voters.php" style="margin-bottom:16px;">
        <input type="text" name="search" placeholder="Search by voter name, student ID, election or position"
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
               style="padding:6px 10px; width:300px;">
        <button type="submit">Search</button>
        <a href="manage_voters.php"><button type="button">Clear</button></a>
        <a href="dashboard.php"><button type="button">Dashboard</button></a>
    </form>

    <!-- View Vote Details -->
    <?php if ($view_vote): ?>
    <div style="border:1px solid #ccc; border-radius:8px; padding:20px; margin-bottom:20px; background:#f9f9f9;">
        <h3 style="margin-bottom:14px;">Vote Details</h3>
        <table cellpadding="8">
            <tr>
                <td><strong>Voter</strong></td>
                <td><?= htmlspecialchars($view_vote['voter_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Student ID</strong></td>
                <td><?= htmlspecialchars($view_vote['student_id']) ?></td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td><?= htmlspecialchars($view_vote['email']) ?></td>
            </tr>
            <tr>
                <td><strong>Voted For</strong></td>
                <td><?= htmlspecialchars($view_vote['candidate_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Position</strong></td>
                <td><?= htmlspecialchars($view_vote['position_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Election</strong></td>
                <td><?= htmlspecialchars($view_vote['election_title']) ?></td>
            </tr>
            <tr>
                <td><strong>Election Period</strong></td>
                <td><?= htmlspecialchars($view_vote['start_date']) ?> &mdash; <?= htmlspecialchars($view_vote['end_date']) ?></td>
            </tr>
            <tr>
                <td><strong>Voted At</strong></td>
                <td><?= htmlspecialchars($view_vote['created_at']) ?></td>
            </tr>
        </table>
        <br>
        <a href="manage_voters.php<?= isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '' ?>">
            <button type="button" style="padding:6px 14px; font-size:13px;">Close</button>
        </a>
    </div>
    <?php endif; ?>

    <!-- Vote List -->
    <?php if ($votes->num_rows === 0): ?>
        <p>No vote records found.</p>
    <?php else: while ($row = $votes->fetch_assoc()): ?>

        <div style="display:flex; align-items:center; justify-content:space-between; border:1px solid #ddd; border-radius:8px; padding:14px 16px; margin-bottom:10px; background:#fff;">

            <div>
                <strong><?= htmlspecialchars($row['voter_name']) ?></strong>
                <span style="font-size:13px; color:#555;"> &nbsp;&bull;&nbsp; <?= htmlspecialchars($row['student_id']) ?></span><br>
                <span style="font-size:13px; color:#555;">
                    Voted for <strong><?= htmlspecialchars($row['candidate_name']) ?></strong>
                    as <em><?= htmlspecialchars($row['position_name']) ?></em>
                </span><br>
                <span style="font-size:12px; color:#888;">
                    <?= htmlspecialchars($row['election_title']) ?> &nbsp;&bull;&nbsp;
                    <?= htmlspecialchars($row['created_at']) ?>
                </span>
            </div>

            <div style="text-align:right; flex-shrink:0; margin-left:16px;">
                <a href="manage_voters.php?view_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">View</a>

                <?php if (in_array($_SESSION['role'], ['manager', 'admin'])): ?>
                    &nbsp;|&nbsp;
                    <a href="manage_voters.php?delete_id=<?= $row['id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       style="color:red;"
                       onclick="return confirm('Delete this vote record? This cannot be undone.')">
                        Delete
                    </a>
                <?php endif; ?>
            </div>

        </div>

    <?php endwhile; endif; ?>

</div>