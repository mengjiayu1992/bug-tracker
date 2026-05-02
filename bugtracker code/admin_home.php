<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit();
}

$view = $_GET['view'] ?? 'new';

if (isset($_POST['create_staff'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO staff (name, email, password) VALUES (?, ?,?)");
    $stmt->bind_param("sss", $name, $email, $hashed);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_home.php?view=staff&added=1");
    exit();
}

if (isset($_GET['delete_staff'])) {

    $staff_id = $_GET['delete_staff'];

    $stmt = $conn->prepare("UPDATE bugs SET staff_id = NULL WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_home.php?view=staff");
    exit();
}

if (isset($_GET['approve'])) {
    $bug_id = $_GET['approve'];

    $stmt = $conn->prepare("UPDATE bugs SET status = 'resolved' WHERE bug_id = ?");
    $stmt->bind_param("i", $bug_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_home.php?view=approve");
    exit();
}

if (isset($_GET['reject'])) {
    $bug_id = $_GET['reject'];

    $stmt = $conn->prepare("UPDATE bugs SET status = 'assigned' WHERE bug_id = ?");
    $stmt->bind_param("i", $bug_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_home.php?view=approve");
    exit();
}

include 'header.php';
include 'admin_menu.php';
?>

<?php if ($view == 'new'): ?>

<?php
$stmt = $conn->prepare("SELECT * FROM bugs WHERE status = 'new'");
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section-card">
    <h3>New Bugs</h3>

    <?php while ($bug = $result->fetch_assoc()): ?>

        <div style="background:white; padding:15px; margin-bottom:15px; border-radius:10px; color:#2F2F2F; position:relative;">

            <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%);">
                <a href="assign_bug.php?bug_id=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                    Assign
                </a>
            </div>

            <strong><?php echo $bug['bug_name']; ?></strong><br><br>

            Ticket #: <?php echo $bug['ticket_number']; ?><br>
            Source: <?php echo $bug['source_application']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?>

        </div>

    <?php endwhile; ?>

</div>

<?php elseif ($view == 'search'): ?>

<div class="section-card">

<?php if (isset($_GET['updated'])): ?>
    <p style="color:green;">Bug updated successfully.</p>
<?php endif; ?>

    <h3>Search Bugs</h3>

    <form method="POST">
        <input type="text" name="ticket" placeholder="Enter ticket (TK-1001)">
        <input type="text" name="keyword" placeholder="Enter keyword">
        <button type="submit">Search</button>
        <button type="submit" name="view_all">View All</button>
    </form>

    <br>

    <?php
    if (
        (isset($_POST['ticket']) && $_POST['ticket'] != "") ||
        (isset($_POST['keyword']) && $_POST['keyword'] != "") ||
        isset($_POST['view_all'])
    ) {

        if (isset($_POST['view_all'])) {

            $stmt = $conn->prepare("SELECT * FROM bugs");
            $stmt->execute();
            $search_result = $stmt->get_result();

        } elseif (isset($_POST['ticket']) && $_POST['ticket'] != "") {

            $ticket = $_POST['ticket'];

            $stmt = $conn->prepare("SELECT * FROM bugs WHERE ticket_number = ?");
            $stmt->bind_param("s", $ticket);
            $stmt->execute();
            $search_result = $stmt->get_result();

        } else {

            $keyword = "%" . $_POST['keyword'] . "%";

            $stmt = $conn->prepare("
                SELECT * FROM bugs 
                WHERE bug_name LIKE ? 
                OR impact LIKE ? 
                OR source_application LIKE ? 
                OR user_id LIKE ? 
                OR staff_id LIKE ? 
                OR status LIKE ? 
                OR severity LIKE ?
            ");
            $stmt->bind_param("sssssss", $keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword);
            $stmt->execute();
            $search_result = $stmt->get_result();
        }
    }

    if (isset($search_result)):

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

        <div style="background:white; padding:15px; margin-bottom:15px; border-radius:10px; color:#2F2F2F; position:relative;">

            <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%);">
                <a href="edit_bug.php?bug_id=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                    Edit
                </a>
                <a href="comment_bug.php?bug_id=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F; margin-left:6px;">
                    Comment
                </a>
            </div>

            <strong><?php echo $bug['bug_name']; ?></strong><br><br>

            Ticket #: <?php echo $bug['ticket_number']; ?><br>
            User ID: <?php echo $bug['user_id']; ?><br>
            Staff ID: <?php echo $bug['staff_id']; ?><br>
            Source: <?php echo $bug['source_application']; ?><br>
            Severity: <?php echo $bug['severity']; ?><br>
            Impact: <?php echo $bug['impact']; ?><br>
            Status: <?php echo $bug['status']; ?><br>

            <?php if ($ratingText != ""): ?>
                Rating: <?php echo $ratingText; ?><br>
            <?php endif; ?>

        </div>

    <?php
            endwhile;
        }

    endif;
    ?>

</div>

<?php elseif ($view == 'approve'): ?>

<?php
$stmt = $conn->prepare("SELECT * FROM bugs WHERE status = 'pending_approval'");
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section-card">
    <h3>Approve Solutions</h3>

    <?php if ($result->num_rows == 0): ?>
        <p>No bugs pending approval.</p>
    <?php else: ?>

        <?php while ($bug = $result->fetch_assoc()): ?>

            <div style="background:white; padding:15px; margin-bottom:15px; border-radius:10px; color:#2F2F2F; position:relative;">

                <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%); display:flex; gap:8px;">
                    <a href="admin_home.php?view=approve&approve=<?php echo $bug['bug_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                        Approve
                    </a>
                    <a href="admin_home.php?view=approve&reject=<?php echo $bug['bug_id']; ?>" style="background:#FADADD; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;">
                        Reject
                    </a>
                </div>

                <strong><?php echo $bug['bug_name']; ?></strong><br><br>

                Ticket #: <?php echo $bug['ticket_number']; ?><br>
                Severity: <?php echo $bug['severity']; ?><br>
                Impact: <?php echo $bug['impact']; ?><br>
                Status: <?php echo $bug['status']; ?><br>
                Source: <?php echo $bug['source_application']; ?><br>
                Staff ID: <?php echo $bug['staff_id']; ?><br><br>

                <strong>Solution:</strong><br>
                <?php echo $bug['solution']; ?>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>

</div>

<?php elseif ($view == 'staff'): ?>

<div class="section-card">

<?php if (isset($_GET['updated'])): ?>
    <p style="color:green;">Staff updated successfully.</p>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
    <p style="color:green;">Staff added successfully.</p>
<?php endif; ?>

    <h3>Staff Management</h3>

    <form method="POST">
        <input type="text" name="keyword" placeholder="Search name, email, or ID">
        <button type="submit">Search</button>
        <button type="submit" name="view_all">View All</button>
        <button type="submit" name="add_staff">Add New Staff</button>
    </form>

    <br>
    
<?php if (isset($_POST['add_staff'])): ?>

<form method="POST">
    <input type="text" name="name" placeholder="Staff Name" required>
    <input type="email" name="email" placeholder="Staff Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="create_staff">Create Staff</button>
</form>

<br>

<?php endif; ?>

    <?php
    if (
        (isset($_POST['keyword']) && $_POST['keyword'] != "") ||
        isset($_POST['view_all'])
    ) {

        if (isset($_POST['view_all'])) {

            $stmt = $conn->prepare("SELECT * FROM staff");
            $stmt->execute();
            $result = $stmt->get_result();

        } else {

            $keyword = "%" . $_POST['keyword'] . "%";

            $stmt = $conn->prepare("
                SELECT * FROM staff
                WHERE name LIKE ?
                OR email LIKE ?
                OR staff_id LIKE ?
            ");
            $stmt->bind_param("sss", $keyword, $keyword, $keyword);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        if ($result->num_rows == 0) {
            echo "<p>No staff found.</p>";
        } else {
            while ($staff = $result->fetch_assoc()):
    ?>

        <div style="background:white; padding:15px; margin-bottom:15px; border-radius:10px; color:#2F2F2F; position:relative;">

            <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%); display:flex; gap:6px;">
        
                <a href="edit_staff.php?staff_id=<?php echo $staff['staff_id']; ?>" style="background:#D8D3FF; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;"> Edit
                </a>

                <a href="admin_home.php?view=staff&delete_staff=<?php echo $staff['staff_id']; ?>" style="background:#FADADD; padding:6px 12px; border-radius:6px; text-decoration:none; color:#2F2F2F;"> Delete
                </a>

            </div>
            
            <strong><?php echo $staff['name']; ?></strong><br><br>

            Staff ID: <?php echo $staff['staff_id']; ?><br>
            Email: <?php echo $staff['email']; ?><br>
            Assigned Tickets:
            <?php
            $stmt2 = $conn->prepare("SELECT ticket_number FROM bugs WHERE staff_id = ?");
            $stmt2->bind_param("i", $staff['staff_id']);
            $stmt2->execute();
            $bugs = $stmt2->get_result();

            if ($bugs->num_rows == 0) {
                echo "None";
            } else {
                while ($b = $bugs->fetch_assoc()) {
                    echo $b['ticket_number'] . "    ";
                }
            }
            ?>
        
        </div>

    <?php
            endwhile;
        }
    }
    ?>

</div>

<?php endif; ?>

<?php include 'footer.php'; ?>