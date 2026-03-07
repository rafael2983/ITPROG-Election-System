<?php
include("config/db.php");

/* Check if election has ended */
$checkElection = $conn->query("SELECT * FROM elections WHERE id=1");
$election = $checkElection->fetch_assoc();

if (strtotime($election['end_date']) > time()) {
    echo "Results will be available after the election ends.";
    exit();
}

$sql = "SELECT candidates.name, positions.position_name, COUNT(votes.id) as total_votes
        FROM candidates
        LEFT JOIN votes ON candidates.id = votes.candidate_id
        JOIN positions ON candidates.position = positions.id
        GROUP BY candidates.id
        ORDER BY positions.position_name, total_votes DESC";

$result = $conn->query($sql);

$current_position = "";
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container" style="width:700px;">

    <h2>Election Results</h2>

    <?php while ($row = $result->fetch_assoc()): ?>

        <?php if ($current_position != $row['position_name']): ?>
            <h3>
                <?php echo $row['position_name']; ?>
            </h3>
            <?php $current_position = $row['position_name']; ?>
        <?php endif; ?>

        <p>
            <strong>
                <?php echo $row['name']; ?>
            </strong>
            — Votes:
            <?php echo $row['total_votes']; ?>
        </p>

    <?php endwhile; ?>

</div>