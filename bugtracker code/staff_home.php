<?php
session_start();

include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "staff") {
    header("Location: index.php");
    exit();
}

$staff_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'home';

if (isset($_GET['accept'])) {

    $bug_id = $_GET['accept'];

    $stmt = $conn->prepare("UPDATE bugs SET status = 'in_progress' WHERE bug_id = ? AND staff_id = ?");
    $stmt->bind_param("ii", $bug_id, $staff_id);
    $stmt->execute();
    $stmt->close();

    header("Location: staff_home.php");
    exit();
}

include 'header.php';
?>

<div class="menu-bar">
    <span>Main Menu</span>

    <a href="staff_home.php">My Bugs</a>
    <a href="staff_home.php?view=search">Search Bugs</a>
</div>

<?php if ($view == 'home'): ?>

<?php
$stmt = $conn->prepare("SELECT * FROM bugs WHERE staff_id = ? AND status = 'assigned' ORDER BY bug_id DESC");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$assigned = $stmt->get_result();
?>

<div class="section-card">
    <h3>New Assignments</h3>

    <?php if ($assigned->num_rows == 0): ?>
        <p>No new assignments.</p>
    <?php else: ?>
    <?php while ($bug = $assigned->fetch_assoc()): ?>

        <div style="background:#FFF4CC; padding:15px; margin-bottom:15px; border-radius:10px; position:relative;">

            <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%);">
                <a href="staff_home.php?accept=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px;">
                    Accept
                </a>
            </div>

            <strong><?php echo $bug['bug_name']; ?></strong><br><br>

            Ticket #: <?php echo $bug['ticket_number']; ?><br>
            Source: <?php echo $bug['source_application']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>

        </div>

    <?php endwhile; ?>
    <?php endif; ?>

</div>

<?php
$stmt = $conn->prepare("SELECT * FROM bugs WHERE staff_id = ? AND status = 'in_progress' ORDER BY bug_id DESC");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$inprogress = $stmt->get_result();
?>

<div class="section-card">
    <h3>In Progress</h3>

    <?php while ($bug = $inprogress->fetch_assoc()): ?>

        <div style="background:white; padding:15px; margin-bottom:15px; border-radius:10px; position:relative;">

            <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%);">
                <a href="edit_bug.php?bug_id=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                    Resolve
                </a>
            </div>

            <strong><?php echo $bug['bug_name']; ?></strong><br><br>

            Ticket #: <?php echo $bug['ticket_number']; ?><br>
            Source: <?php echo $bug['source_application']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>

        </div>

    <?php endwhile; ?>

</div>

<?php
$stmt = $conn->prepare("SELECT * FROM bugs WHERE staff_id = ? AND status = 'resolved' ORDER BY bug_id DESC");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$resolved = $stmt->get_result();
?>

<div class="section-card">
    <h3>Resolved Bugs</h3>

    <?php if ($resolved->num_rows == 0): ?>
        <p>No resolved bugs.</p>
    <?php else: ?>

        <?php while ($bug = $resolved->fetch_assoc()): ?>

            <?php
            $getRating = $conn->prepare("SELECT rating FROM ratings WHERE bug_id = ?");
            $getRating->bind_param("i", $bug['bug_id']);
            $getRating->execute();
            $ratingResult = $getRating->get_result();

            $ratingText = "";
            if ($ratingResult->num_rows > 0) {
                $row = $ratingResult->fetch_assoc();
                $ratingText = str_repeat('⭐', $row['rating']) . " (" . $row['rating'] . "/5)";
            }
            $getRating->close();
            ?>

            <div style="background:#D3D3D3; padding:15px; margin-bottom:15px; border-radius:10px; position:relative;">

                <strong><?php echo $bug['bug_name']; ?></strong><br><br>

                Ticket #: <?php echo $bug['ticket_number']; ?><br>
                Source: <?php echo $bug['source_application']; ?><br>
                Severity: <?php echo $bug['severity']; ?><br>
                Impact: <?php echo $bug['impact']; ?><br>
                Solution: <?php echo $bug['solution']; ?><br>

                <?php if ($ratingText != ""): ?>
                    Rating: <?php echo $ratingText; ?><br>
                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

</div>

<?php elseif ($view == 'search'): ?>

<div class="section-card">
    <h3>Search Bugs</h3>

    <form method="POST">
        <input type="text" name="keyword" placeholder="Search bugs">
        <button type="submit">Search</button>
        <button type="submit" name="view_all">View All</button>
    </form>

    <br>

    <?php
    if (
        (isset($_POST['keyword']) && $_POST['keyword'] != "") ||
        isset($_POST['view_all'])
    ) {

        if (isset($_POST['view_all'])) {

            $stmt = $conn->prepare("SELECT * FROM bugs");
            $stmt->execute();
            $result = $stmt->get_result();

        } else {

            $keyword = "%" . $_POST['keyword'] . "%";

            $stmt = $conn->prepare("
                SELECT * FROM bugs
                WHERE bug_name LIKE ?
                OR ticket_number LIKE ?
                OR impact LIKE ?
                OR status LIKE ?
                OR severity LIKE ?
            ");
            $stmt->bind_param("sssss", $keyword, $keyword, $keyword, $keyword, $keyword);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        while ($bug = $result->fetch_assoc()):

            $getRating = $conn->prepare("SELECT rating FROM ratings WHERE bug_id = ?");
            $getRating->bind_param("i", $bug['bug_id']);
            $getRating->execute();
            $ratingResult = $getRating->get_result();

            $ratingText = "";
            if ($ratingResult->num_rows > 0) {
                $row = $ratingResult->fetch_assoc();
                $ratingText = str_repeat('⭐', $row['rating']) . " (" . $row['rating'] . "/5)";
            }
            $getRating->close();
    ?>

        <div style="background:white; padding:15px; margin-bottom:15px; border-radius:10px; position:relative;">

            <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%);">
                <a href="comment_bug.php?bug_id=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                    Comment
                </a>
            </div>

            <strong><?php echo $bug['bug_name']; ?></strong><br><br>

            Ticket #: <?php echo $bug['ticket_number']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>
            Status: <?php echo $bug['status']; ?><br>
            Source: <?php echo $bug['source_application']; ?><br>
            User ID: <?php echo $bug['user_id']; ?><br>
            Staff ID: <?php echo $bug['staff_id']; ?><br>
            Solution: <?php echo $bug['solution']; ?><br>

            <?php if ($ratingText != ""): ?>
                Rating: <?php echo $ratingText; ?><br>
            <?php endif; ?>

        </div>

    <?php endwhile; } ?>

</div>

<?php endif; ?>

<?php include 'footer.php'; ?>