<?php
session_start();
include("config/db.php");

// Security: Only managers or admins access this page
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'manager' && $role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle Status Updates (Approve/Reject/Pending) 
if (isset($_POST['action']) && isset($_POST['candidate_id'])) {
    $new_status = $_POST['action']; 
    $candidate_id = $_POST['candidate_id'];

    $stmt = $conn->prepare("UPDATE candidates SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $candidate_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Candidate status updated to $new_status!');</script>";
    }
}

// Fetch all candidates for review 
$sql = "SELECT candidates.*, positions.position_name 
        FROM candidates 
        LEFT JOIN positions ON candidates.position = positions.position_name
        ORDER BY candidates.created_at ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Candidates</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container" style="width:950px;">
    <a href="dashboard.php" style="text-decoration:none; color:#3498db;">← Back to Dashboard</a>
    <table border="1" style="width:100%; border-collapse: collapse; margin-top:20px;">
        <thead>
            <tr style="background:#f4f4f4; text-align:left;">
                <th style="padding:10px;">Photo</th>
                <th style="padding:10px;">Name / ID</th>
                <th style="padding:10px;">Position</th>
                <th style="padding:10px;">Status</th>
                <th style="padding:10px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="padding:10px; text-align:center;">
                        <img src="uploads/<?php echo $row['photo']; ?>" width="50" height="50" style="border-radius:50%; object-fit:cover;">
                    </td>
                    <td style="padding:10px;">
                        <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                        <small><?php echo htmlspecialchars($row['student_id']); ?></small>
                    </td>
                    <td style="padding:10px;"><?php echo htmlspecialchars($row['position_name'] ?? $row['position']); ?></td>
                    <td style="padding:10px; text-align:center;">
                        <span style="padding:4px 8px; border-radius:4px; font-size:0.8em; color:white; background: 
                            <?php echo ($row['status'] == 'approved') ? '#27ae60' : (($row['status'] == 'rejected') ? '#e74c3c' : '#f39c12'); ?>;">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td style="padding:10px;">
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="candidate_id" value="<?php echo $row['id']; ?>">
                            
                            <?php if ($row['status'] != 'approved'): ?>
                                <button type="submit" name="action" value="approved" style="background:#27ae60; color:white; border:none; padding:5px 10px; cursor:pointer; font-size:0.8em;">Approve</button>
                            <?php endif; ?>

                            <?php if ($row['status'] != 'rejected'): ?>
                                <button type="submit" name="action" value="rejected" style="background:#e74c3c; color:white; border:none; padding:5px 10px; cursor:pointer; font-size:0.8em;">Reject</button>
                            <?php endif; ?>

                            <?php if ($row['status'] != 'pending'): ?>
                                <button type="submit" name="action" value="pending" style="background:#f39c12; color:white; border:none; padding:5px 10px; cursor:pointer; font-size:0.8em;">Reset to Pending</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="padding:20px; text-align:center;">No candidates registered.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>