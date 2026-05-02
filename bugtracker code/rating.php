<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

$selected_bug = "";
if (isset($_GET['bug_id'])) {
    $selected_bug = $_GET['bug_id'];
}

if (isset($_POST['submit_rating'])) {

    $bug_id = $_POST['bug_id'];
    $rating = $_POST['rating'];

    if ($rating < 1 || $rating > 5) {
        $message = "<p style='color:red;'>Invalid rating.</p>";
    } else {

        $checkBug = $conn->prepare("SELECT * FROM bugs WHERE bug_id = ? AND user_id = ? AND status = 'resolved'");
        $checkBug->bind_param("ii", $bug_id, $user_id);
        $checkBug->execute();
        $validBug = $checkBug->get_result();

        if ($validBug->num_rows == 0) {
            $message = "<p style='color:red;'>Invalid bug selection.</p>";
        } else {

            $check = $conn->prepare("SELECT * FROM ratings WHERE bug_id = ? AND user_id = ?");
            $check->bind_param("ii", $bug_id, $user_id);
            $check->execute();
            $check_result = $check->get_result();

            if ($check_result->num_rows > 0) {

                $stmt = $conn->prepare("UPDATE ratings SET rating = ? WHERE bug_id = ? AND user_id = ?");
                $stmt->bind_param("iii", $rating, $bug_id, $user_id);

                if ($stmt->execute()) {
                    $message = "<p style='color:green;'>Rating updated successfully!</p>";
                }

            } else {

                $stmt = $conn->prepare("INSERT INTO ratings (bug_id, user_id, rating) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $bug_id, $user_id, $rating);

                if ($stmt->execute()) {
                    $message = "<p style='color:green;'>Rating submitted successfully!</p>";
                } else {
                    $message = "<p style='color:red;'>Error submitting rating.</p>";
                }
            }

            $stmt->close();
            $check->close();
        }

        $checkBug->close();
    }
}

$bugs = $conn->prepare("
    SELECT b.* 
    FROM bugs b
    LEFT JOIN ratings r ON b.bug_id = r.bug_id AND r.user_id = ?
    WHERE b.user_id = ? AND b.status = 'resolved' AND r.bug_id IS NULL
    ORDER BY b.bug_id DESC
");
$bugs->bind_param("ii", $user_id, $user_id);
$bugs->execute();
$bug_result = $bugs->get_result();

$ratings = $conn->prepare("
    SELECT r.*, b.bug_name, b.ticket_number 
    FROM ratings r 
    JOIN bugs b ON r.bug_id = b.bug_id 
    WHERE r.user_id = ? 
    ORDER BY r.rating_id DESC
");
$ratings->bind_param("i", $user_id);
$ratings->execute();
$rating_result = $ratings->get_result();

include 'header.php';
?>

<div class="menu-bar">
    <span>Main Menu</span>
    <a href="user_home.php?view=my">My Bugs</a>
    <a href="user_home.php?view=submit">Submit Bug</a>
    <a href="user_home.php?view=search">Search All Bugs</a>
</div>

<div class="section-card">
    <h3>Submit a Rating</h3>
    <?php echo $message; ?>

    <?php if ($bug_result->num_rows > 0): ?>

        <form method="POST">

            <label>Select Resolved Bug:</label><br>
            <select name="bug_id" style="width:100%; margin:10px 0;">
                <?php while ($bug = $bug_result->fetch_assoc()): ?>
                    <option value="<?php echo $bug['bug_id']; ?>" <?php if ($selected_bug == $bug['bug_id']) echo "selected"; ?>>
                        <?php echo $bug['ticket_number'] . ' - ' . $bug['bug_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Rating:</label><br>
            <select name="rating" style="width:100%; margin:10px 0;">
                <option value="5">⭐⭐⭐⭐⭐</option>
                <option value="4">⭐⭐⭐⭐</option>
                <option value="3">⭐⭐⭐</option>
                <option value="2">⭐⭐</option>
                <option value="1">⭐</option>
            </select>

            <button type="submit" name="submit_rating" class="btn-white">
                Submit Rating
            </button>

        </form>

    <?php else: ?>
        <p>No unresolved ratings.</p>
    <?php endif; ?>

</div>

<div class="section-card">
    <h3>My Ratings</h3>

    <?php if ($rating_result->num_rows > 0): ?>

        <?php while ($r = $rating_result->fetch_assoc()): ?>

            <div style="background:white; padding:15px; margin-bottom:15px; border-radius:10px;">

                <strong><?php echo $r['bug_name']; ?></strong><br>
                Ticket #: <?php echo $r['ticket_number']; ?><br>
                Rating: <?php echo str_repeat('⭐', $r['rating']); ?> (<?php echo $r['rating']; ?>/5)

            </div>

        <?php endwhile; ?>

    <?php else: ?>
        <p>No ratings yet.</p>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>