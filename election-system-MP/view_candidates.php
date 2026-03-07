<?php
session_start();
include("config/db.php");

$sql = "SELECT candidates.*, positions.position_name
        FROM candidates
        JOIN positions ON candidates.position = positions.id
        WHERE candidates.status='approved'
        ORDER BY positions.position_name";

$result = $conn->query($sql);

$current_position = "";
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container" style="width:700px;">

    <h2>Election Candidates</h2>

    <?php while ($row = $result->fetch_assoc()): ?>

        <?php if ($current_position != $row['position_name']): ?>
            <h3>
                <?php echo $row['position_name']; ?>
            </h3>
            <?php $current_position = $row['position_name']; ?>
        <?php endif; ?>

        <div style="display:flex;align-items:center;margin-bottom:10px;">

            <img src="uploads/<?php echo $row['photo']; ?>" width="70" style="border-radius:6px;margin-right:15px;">

            <div>
                <strong>
                    <?php echo $row['name']; ?>
                </strong><br>
                <?php echo $row['synopsis']; ?>
            </div>

        </div>

    <?php endwhile; ?>

    <a href="vote.php"><button>Proceed to Voting</button></a>

</div>