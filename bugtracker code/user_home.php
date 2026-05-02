<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'my';
$message = "";

if (isset($_POST['submit'])) {

    $bug_name = trim($_POST['bug_name']);
    $severity = trim($_POST['severity']);
    $impact = trim($_POST['impact']);
    $source_application = trim($_POST['source_application']);

    if ($bug_name == "" || $severity == "" || $impact == "" || $source_application == "") {
        $message = "<p style='color:red;'>This is a required field.</p>";
    } else {

        $result = $conn->query("SELECT ticket_number FROM bugs ORDER BY bug_id DESC LIMIT 1");

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last = intval(substr($row['ticket_number'], 3));
            $new = $last + 1;
        } else {
            $new = 1001;
        }

        $ticket_number = "TK-" . $new;

        $stmt = $conn->prepare("
            INSERT INTO bugs (bug_name, ticket_number, severity, impact, status, user_id, source_application)
            VALUES (?, ?, ?, ?, 'new', ?, ?)
        ");

        $stmt->bind_param(
            "ssssis",
            $bug_name,
            $ticket_number,
            $severity,
            $impact,
            $user_id,
            $source_application
        );

        $stmt->execute();
        $stmt->close();

        header("Location: user_home.php?view=my&submitted=1");
        exit();
    }
}

include 'header.php';
?>

<div class="menu-bar">
    <span>Main Menu</span>

    <a href="user_home.php?view=my">My Bugs</a>
    <a href="user_home.php?view=submit">Submit Bug</a>
    <a href="user_home.php?view=search">Search All Bugs</a>
</div>

<?php if ($view == 'my'): ?>

<?php
$stmt = $conn->prepare("SELECT * FROM bugs WHERE user_id = ? ORDER BY bug_id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section-card">
    
<?php if (isset($_GET['submitted'])): ?>
    <p style="color:green;">Bug submitted successfully.</p>
<?php endif; ?>

    <h3>My Bugs</h3>

    <?php while ($bug = $result->fetch_assoc()): ?>

        <?php
            $bg = "white";

            if ($bug['status'] == "resolved") {
                $bg = "#D3D3D3";
            } elseif ($bug['status'] == "new") {
                $bg = "#EAF6FF";
            }

            $check = $conn->prepare("SELECT * FROM ratings WHERE bug_id = ? AND user_id = ?");
            $check->bind_param("ii", $bug['bug_id'], $user_id);
            $check->execute();
            $rated = $check->get_result()->num_rows > 0;
            $check->close();
        ?>

        <div style="background:<?php echo $bg; ?>; padding:15px; margin-bottom:15px; border-radius:10px; color:#2F2F2F; position:relative;">

            <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%); display:flex; gap:6px;">

                <a href="comment_bug.php?bug_id=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                    Comment
                </a>

                <?php if ($bug['status'] == 'resolved'): ?>
                    <?php if ($rated): ?>

                        <?php
                        $getRating = $conn->prepare("SELECT rating FROM ratings WHERE bug_id = ? AND user_id = ?");
                        $getRating->bind_param("ii", $bug['bug_id'], $user_id);
                        $getRating->execute();
                        $ratingRow = $getRating->get_result()->fetch_assoc();
                        $userRating = $ratingRow['rating'];
                        $getRating->close();
                        ?>

                        <span style="background:#C0C0C0; padding:6px 12px; border-radius:6px; color:#2F2F2F;">
                            <?php echo str_repeat('⭐', $userRating) . " ($userRating/5)"; ?>
                        </span>
                        
                    <?php else: ?>
                        <a href="rating.php?bug_id=<?php echo $bug['bug_id']; ?>" style="background:#E0E0E0; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                            Rate
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

            </div>

            <strong><?php echo $bug['bug_name']; ?></strong><br><br>

            Ticket #: <?php echo $bug['ticket_number']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>
            Status: <?php echo $bug['status']; ?><br>
            Source: <?php echo $bug['source_application']; ?>

        </div>

    <?php endwhile; ?>

</div>

<?php elseif ($view == 'submit'): ?>

<div class="container center-page">
    <div class="form-card">

        <h3>Submit Bug</h3>

        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <form method="POST">

            <label>Bug Name</label><br>
            <input type="text" name="bug_name" required><br><br>

            <label>Source Application</label><br>
            <input type="text" name="source_application" required><br><br>

            <label>Severity</label><br>
            <select name="severity" required>
                <option value="">Select Severity</option>
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
            </select><br><br>

            <label>Impact</label><br>
            <input type="text" name="impact" required><br><br>

            <button type="submit" name="submit">Submit</button>

        </form>

    </div>
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
            $search_result = $stmt->get_result();

        } else {

            $keyword = "%" . $_POST['keyword'] . "%";

            $stmt = $conn->prepare("
                SELECT * FROM bugs
                WHERE bug_name LIKE ?
                OR source_application LIKE ?
                OR severity LIKE ?
                OR impact LIKE ?
                OR status LIKE ?
                OR solution LIKE ?
            ");
            $stmt->bind_param("ssssss", $keyword, $keyword, $keyword, $keyword, $keyword, $keyword);
            $stmt->execute();
            $search_result = $stmt->get_result();
        }

        if ($search_result->num_rows == 0) {
            echo "<p>No bugs found.</p>";
        } else {
            while ($bug = $search_result->fetch_assoc()):

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

            Source: <?php echo $bug['source_application']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>
            Status: <?php echo $bug['status']; ?><br>
            Solution: <?php echo $bug['solution']; ?><br>

            <?php if ($ratingText != ""): ?>
                Rating: <?php echo $ratingText; ?><br>
            <?php endif; ?>

        </div>

    <?php
            endwhile;
        }
    }
    ?>
</div>

<?php endif; ?>

<?php include 'footer.php'; ?>