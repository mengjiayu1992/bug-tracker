<?php
session_start();

include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['bug_id'])) {
    die("No bug selected");
}

$bug_id = $_GET['bug_id'];

// get bug
$stmt = $conn->prepare("SELECT * FROM bugs WHERE bug_id = ?");
$stmt->bind_param("i", $bug_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    die("Bug not found");
}

$bug = $result->fetch_assoc();
$stmt->close();

// get staff list
$staff_result = $conn->query("SELECT * FROM staff");

// assign bug
if (isset($_POST['assign'])) {

    $bug_name = $_POST['bug_name'];
    $ticket_number = $_POST['ticket_number'];
    $severity = $_POST['severity'];
    $impact = $_POST['impact'];
    $staff_id = $_POST['staff_id'];
    $source_application = $_POST['source_application'];

    $update = $conn->prepare("
        UPDATE bugs 
        SET bug_name = ?, 
            ticket_number = ?, 
            severity = ?, 
            impact = ?, 
            status = 'assigned',
            staff_id = ?, 
            source_application = ?
        WHERE bug_id = ?
    ");

    $update->bind_param(
        "ssssssi",
        $bug_name,
        $ticket_number,
        $severity,
        $impact,
        $staff_id,
        $source_application,
        $bug_id
    );

    $update->execute();
    $update->close();

    header("Location: admin_home.php?view=new");
    exit();
}

include 'header.php';
include 'admin_menu.php';
?>

<div class="section-card assign-card">
    <h3>Assign Bug</h3>

    <form method="POST" style="display:grid; grid-template-columns: 150px 1fr; gap:10px; align-items:center;">

        <label>Ticket Number:</label>
        <input type="text" name="ticket_number" value="<?php echo $bug['ticket_number']; ?>">

        <label>Bug Name:</label>
        <input type="text" name="bug_name" value="<?php echo $bug['bug_name']; ?>">

        <label>Severity:</label>
        <select name="severity">
            <option value="low" <?php if ($bug['severity'] == "low") echo "selected"; ?>>Low</option>
            <option value="medium" <?php if ($bug['severity'] == "medium") echo "selected"; ?>>Medium</option>
            <option value="high" <?php if ($bug['severity'] == "high") echo "selected"; ?>>High</option>
        </select>

        <label>Impact:</label>
        <input type="text" name="impact" value="<?php echo $bug['impact']; ?>">

        <label>Source Application:</label>
        <input type="text" name="source_application" value="<?php echo $bug['source_application']; ?>">

        <label>Assign to Staff:</label>
        <select name="staff_id">
            <?php while ($staff = $staff_result->fetch_assoc()): ?>
                <option 
                    value="<?php echo $staff['staff_id']; ?>"
                    <?php if ($bug['staff_id'] == $staff['staff_id']) echo "selected"; ?>
                >
                    <?php echo $staff['name']; ?> (ID: <?php echo $staff['staff_id']; ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <div></div>
        <button type="submit" name="assign" class="btn-white">Assign</button>

    </form>
</div>

<?php include 'footer.php'; ?>