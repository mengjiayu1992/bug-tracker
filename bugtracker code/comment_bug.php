<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['bug_id'])) {
    die("No bug selected");
}

$bug_id = $_GET['bug_id'];
$author_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (isset($_POST['submit'])) {

    $comment = $_POST['comment'];

    $stmt = $conn->prepare("INSERT INTO comments (bug_id, author_id, role, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $bug_id, $author_id, $role, $comment);
    $stmt->execute();
    $stmt->close();

    header("Location: comment_bug.php?bug_id=" . $bug_id);
    exit();
}

if (isset($_GET['delete_comment'])) {

    $comment_id = $_GET['delete_comment'];

    $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ? AND author_id = ?");
    $stmt->bind_param("ii", $comment_id, $author_id);
    $stmt->execute();
    $stmt->close();

    header("Location: comment_bug.php?bug_id=" . $bug_id);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM bugs WHERE bug_id = ?");
$stmt->bind_param("i", $bug_id);
$stmt->execute();
$bug = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM comments WHERE bug_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $bug_id);
$stmt->execute();
$comments = $stmt->get_result();

$getRating = $conn->prepare("SELECT rating FROM ratings WHERE bug_id = ?");
$getRating->bind_param("i", $bug_id);
$getRating->execute();
$ratingResult = $getRating->get_result();

$ratingText = "";
if ($ratingResult->num_rows > 0) {
    $row = $ratingResult->fetch_assoc();
    $ratingText = str_repeat('⭐', $row['rating']) . " (" . $row['rating'] . "/5)";
}
$getRating->close();

include 'header.php';
?>

<?php if ($_SESSION['role'] == 'admin'): ?>
    <?php include 'admin_menu.php'; ?>

<?php elseif ($_SESSION['role'] == 'staff'): ?>
    <div class="menu-bar">
        <span>Main Menu</span>
        <a href="staff_home.php">My Bugs</a>
        <a href="staff_home.php?view=search">Search Bugs</a>
    </div>

<?php elseif ($_SESSION['role'] == 'user'): ?>
<div class="menu-bar">
    <span>Main Menu</span>

    <a href="user_home.php?view=my">My Bugs</a>
    <a href="user_home.php?view=submit">Submit Bug</a>
    <a href="user_home.php?view=search">Search All Bugs</a>
</div>
<?php endif; ?>

<div class="section-card">

    <h3>Bug Details</h3>

    <div style="background:white; padding:15px; border-radius:10px; margin-bottom:20px;">

        <strong><?php echo $bug['bug_name']; ?></strong><br><br>

        <?php if ($_SESSION['role'] == 'user'): ?>

            Status: <?php echo $bug['status']; ?><br>
            Source: <?php echo $bug['source_application']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>
            Solution: <?php echo $bug['solution']; ?>

        <?php else: ?>

            Ticket #: <?php echo $bug['ticket_number']; ?><br>
            User ID: <?php echo $bug['user_id']; ?><br>
            Staff ID: <?php echo $bug['staff_id']; ?><br>
            Status: <?php echo $bug['status']; ?><br>
            Source: <?php echo $bug['source_application']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>
            Solution: <?php echo $bug['solution']; ?>
            
        <?php endif; ?>

    </div>

    <?php if ($ratingText != ""): ?>
        <div style="background:#F5F5F5; padding:10px; border-radius:8px; margin-bottom:20px;">
            <strong>Rating:</strong> <?php echo $ratingText; ?>
        </div>
    <?php endif; ?>

    <h3>Comments</h3>

    <?php if ($comments->num_rows == 0): ?>
        <p>No comments.</p>
    <?php else: ?>

        <?php while ($c = $comments->fetch_assoc()): ?>

            <div style="background:#F5F5F5; padding:10px; border-radius:8px; margin-bottom:10px;">

                <?php
                $name = "";

                if ($c['role'] == 'staff') {
                    $stmt2 = $conn->prepare("SELECT name FROM staff WHERE staff_id = ?");
                    $stmt2->bind_param("i", $c['author_id']);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result()->fetch_assoc();
                    $name = $res2['name'] ?? "";
                }
                elseif ($c['role'] == 'user') {
                    $stmt2 = $conn->prepare("SELECT name FROM user WHERE user_id = ?");
                    $stmt2->bind_param("i", $c['author_id']);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result()->fetch_assoc();
                    $name = $res2['name'] ?? "";
                }
                elseif ($c['role'] == 'admin') {
                    $stmt2 = $conn->prepare("SELECT name FROM admin WHERE admin_id = ?");
                    $stmt2->bind_param("i", $c['author_id']);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result()->fetch_assoc();
                    $name = $res2['name'] ?? "";
                }

                $first = explode(" ", $name)[0];
                ?>

                <?php if ($c['author_id'] == $_SESSION['user_id']): ?>
                    <strong>Me (<?php echo $c['role']; ?>)</strong>
                <?php else: ?>
                    <strong><?php echo $first . " (" . $c['role'] . ")"; ?></strong>
                <?php endif; ?>
                (<?php echo $c['created_at']; ?>)<br><br>

                <?php echo $c['comment']; ?>
                
                <?php if ($c['author_id'] == $_SESSION['user_id']): ?>
                    <div style="text-align:right; margin-top:8px;">
                        <a href="comment_bug.php?bug_id=<?php echo $bug_id;
                            ?>&delete_comment=<?php echo $c['comment_id']; ?>"
                            style="background:#FADADD; padding:4px 10px; border
                            -radius:6px; text-decoration:none; color:#2F2F2F;">
                            Delete
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

    <h3>Add Comment</h3>

    <form method="POST">

        <textarea name="comment" style="width:100%; height:120px;" required></textarea>

        <br><br>

        <button type="submit" name="submit">Submit</button>

    </form>

</div>

<?php include 'footer.php'; ?>