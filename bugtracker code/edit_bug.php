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

$stmt = $conn->prepare("SELECT * FROM bugs WHERE bug_id = ?");
$stmt->bind_param("i", $bug_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    die("Bug not found");
}

$bug = $result->fetch_assoc();
$stmt->close();

$staff_result = $conn->query("SELECT * FROM staff");

if (isset($_POST['update'])) {

    if ($_SESSION['role'] == "admin") {

        $bug_name = $_POST['bug_name'];
        $ticket_number = $_POST['ticket_number'];
        $severity = $_POST['severity'];
        $impact = $_POST['impact'];
        $status = $_POST['status'];
        $staff_id = $_POST['staff_id'];
        $source_application = $_POST['source_application'];
        $solution = $_POST['solution'];

        $update = $conn->prepare("
            UPDATE bugs 
            SET bug_name = ?, 
                ticket_number = ?, 
                severity = ?, 
                impact = ?, 
                status = ?, 
                staff_id = ?, 
                source_application = ?, 
                solution = ?
            WHERE bug_id = ?
        ");

        $update->bind_param(
            "ssssssssi",
            $bug_name,
            $ticket_number,
            $severity,
            $impact,
            $status,
            $staff_id,
            $source_application,
            $solution,
            $bug_id
        );

        $update->execute();
        $update->close();

        header("Location: admin_home.php?view=search&updated=1");
        exit();
    }

    if ($_SESSION['role'] == "staff") {

        $severity = $_POST['severity'];
        $solution = $_POST['solution'];

        $update = $conn->prepare("
            UPDATE bugs 
            SET severity = ?, 
                solution = ?
            WHERE bug_id = ?
        ");

        $update->bind_param(
            "ssi",
            $severity,
            $solution,
            $bug_id
        );

        $update->execute();
        $update->close();

        header("Location: staff_home.php");
        exit();
    }
}

if (isset($_POST['approve'])) {

    if ($_SESSION['role'] == "staff") {

        $severity = $_POST['severity'];
        $solution = $_POST['solution'];

        $update = $conn->prepare("
            UPDATE bugs 
            SET severity = ?, 
                solution = ?, 
                status = 'pending_approval'
            WHERE bug_id = ?
        ");

        $update->bind_param(
            "ssi",
            $severity,
            $solution,
            $bug_id
        );

        $update->execute();
        $update->close();

        header("Location: staff_home.php");
        exit();
    }
}

if (isset($_POST['delete'])) {

    if ($_SESSION['role'] == "admin") {

        $stmt = $conn->prepare("DELETE FROM bugs WHERE bug_id = ?");
        $stmt->bind_param("i", $bug_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_home.php?view=search");
        exit();
    }
}

include 'header.php';

if ($_SESSION['role'] == "admin") {
    include 'admin_menu.php';
}

elseif ($_SESSION['role'] == "staff") {
?>
<div class="menu-bar">
    <span>Main Menu</span>
    <a href="staff_home.php">My Bugs</a>
    <a href="staff_home.php?view=search">Search Bugs</a>
</div>
<?php
}

?>

<div class="section-card assign-card">
    <h3>Edit Bug</h3>

    <form method="POST" style="display:grid; grid-template-columns: 150px 1fr; gap:10px; align-items:center;">

        <label>Ticket Number:</label>
        <input type="text" name="ticket_number" value="<?php echo $bug['ticket_number']; ?>" <?php if ($_SESSION['role'] == "staff") echo "disabled"; ?>>

        <label>Bug Name:</label>
        <input type="text" name="bug_name" value="<?php echo $bug['bug_name']; ?>" <?php if ($_SESSION['role'] == "staff") echo "disabled"; ?>>

        <label>Severity:</label>
        <select name="severity">
            <option value="low" <?php if ($bug['severity'] == "low") echo "selected"; ?>>Low</option>
            <option value="medium" <?php if ($bug['severity'] == "medium") echo "selected"; ?>>Medium</option>
            <option value="high" <?php if ($bug['severity'] == "high") echo "selected"; ?>>High</option>
        </select>

        <label>Impact:</label>
        <input type="text" name="impact" value="<?php echo $bug['impact']; ?>" <?php if ($_SESSION['role'] == "staff") echo "disabled"; ?>>

        <label>Status:</label>
        <select name="status" <?php if ($_SESSION['role'] == "staff") echo "disabled"; ?>>
            <option value="new" <?php if ($bug['status'] == "new") echo "selected"; ?>>New</option>
            <option value="assigned" <?php if ($bug['status'] == "assigned") echo "selected"; ?>>Assigned</option>
            <option value="in_progress" <?php if ($bug['status'] == "in_progress") echo "selected"; ?>>In Progress</option>
            <option value="pending_approval" <?php if ($bug['status'] == "pending_approval") echo "selected"; ?>>Pending Approval</option>
            <option value="resolved" <?php if ($bug['status'] == "resolved") echo "selected"; ?>>Resolved</option>
        </select>

        <label>Source Application:</label>
        <input type="text" name="source_application" value="<?php echo $bug['source_application']; ?>" <?php if ($_SESSION['role'] == "staff") echo "disabled"; ?>>

        <label>Assign to Staff:</label>
        <select name="staff_id" <?php if ($_SESSION['role'] == "staff") echo "disabled"; ?>>
            <?php while ($staff = $staff_result->fetch_assoc()): ?>
                <option 
                    value="<?php echo $staff['staff_id']; ?>"
                    <?php if ($bug['staff_id'] == $staff['staff_id']) echo "selected"; ?>
                >
                    <?php echo $staff['name']; ?> (ID: <?php echo $staff['staff_id']; ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label>Solution:</label>
        <textarea name="solution" style="width:100%; height:100px;"><?php echo $bug['solution']; ?></textarea>

        <div></div>
        <button type="submit" name="update" class="btn-white">Save Changes</button>
        
        <?php if ($_SESSION['role'] == "admin"): ?>
        <div></div>
        <button type="submit" name="delete" class="btn-white" style="background:#FADADD;">Delete Bug 
            </button>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == "staff"): ?>
        <div></div>
        <button type="submit" name="approve" class="btn-white">Send for Approval</button>
        <?php endif; ?>

    </form>

    <br><br>

    <a href="comment_bug.php?bug_id=<?php echo $bug['bug_id']; ?>" 
       style="background:#E0E0E0; padding:8px 14px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
        Comment
    </a>

</div>

<?php include 'footer.php'; ?>