<?php
session_start();
include("config/db.php");
$check = $conn->query("SELECT * FROM votes 
WHERE user_id='$user_id' AND election_id=1");

if ($check->num_rows > 0) {
    echo "You have already voted in this election.";
    exit();
}

$user_id = $_SESSION['user_id'];

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
    <h2>Cast Your Vote</h2>

    <form method="POST" action="submit_vote.php" onsubmit="return confirmVote();">

        <?php while ($row = $result->fetch_assoc()): ?>

            <?php if ($current_position != $row['position_name']): ?>
                <h3>
                    <?php echo $row['position_name']; ?>
                </h3>
                <?php $current_position = $row['position_name']; ?>
            <?php endif; ?>

            <label style="display:block;margin:10px 0;">
                <input type="radio" name="vote[<?php echo $row['position']; ?>]" value="<?php echo $row['id']; ?>" required>

                <img src="uploads/<?php echo $row['photo']; ?>" width="60">
                <?php echo $row['name']; ?>
            </label>

        <?php endwhile; ?>

        <button type="submit">Submit Vote</button>

    </form>
</div>

<script>
    function confirmVote() {
        return confirm("Are you sure you want to submit your vote?");
    }
</script>