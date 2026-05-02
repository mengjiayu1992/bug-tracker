<?php
session_start();

$host = "localhost";
$db = "yum1_bug";
$user = "yum1_yum1";
$pass = "13512157138ymj";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $tables = ["admin", "staff", "user"];
    $foundUser = false;

    foreach ($tables as $table) {
        $sql = "SELECT * FROM `$table` WHERE email = ? AND password = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            $_SESSION['user_id'] = $row[$table . "_id"];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $table;

            if ($table === "admin") {
                header("Location: admin_home.php");
            } elseif ($table === "staff") {
                header("Location: staff_home.php");
            } else {
                header("Location: user_home.php");
            }
            exit();
        }

        $stmt->close();
    }

    $message = "<p style='color:red;'>Invalid email or password</p>";
}
?>

<?php include 'header.php'; ?>
<link rel="stylesheet" href="login.css"/>

<div class="form-card">
    <h2>Login</h2>

    <?php echo $message; ?>

    <form method="POST">
        Email:<br>
        <input type="email" name="email" required>

        Password:<br>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <br>
    <a href="register.php" style="color:white;">Create New Account</a>
</div>

<?php include 'footer.php'; ?>