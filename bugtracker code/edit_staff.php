<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['staff_id'])) {
    header("Location: admin_home.php?view=staff");
    exit();
}

$staff_id = $_GET['staff_id'];

// get current staff info
$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();

// update staff
if (isset($_POST['update'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE staff SET name = ?, email = ? WHERE staff_id = ?");
    $stmt->bind_param("ssi", $name, $email, $staff_id);
    $stmt->execute();

    header("Location: admin_home.php?view=staff&updated=1");
    exit();
}

include 'header.php';
include 'admin_menu.php';
?>

<div class="section-card">
    <h3>Edit Staff</h3>

    <form method="POST">

        <label>Name</label><br>
        <input type="text" name="name" value="<?php echo $staff['name']; ?>" required><br><br>

        <label>Email</label><br>
        <input type="text" name="email" value="<?php echo $staff['email']; ?>" required><br><br>

        <button type="submit" name="update">Update</button>

    </form>
</div>

<?php include 'footer.php'; ?>