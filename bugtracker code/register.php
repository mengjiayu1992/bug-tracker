<?php
include 'db.php';

$message = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // validate name (letters and spaces only)
    if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $message = "<p style='color:#990000;'>Name can only contain letters and spaces</p>";
    }

    // validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p style='color:#990000;'>Invalid email format</p>";
    }

    // validate password (at least 1 letter and 1 number)
    elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d).+$/", $password)) {
        $message = "<p style='color:#990000;'>Password must contain at least one letter and one number</p>";
    }

    else {

        // check if email already exists
        $check = $conn->prepare("SELECT * FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "<p style='color:#ffcccc;'>Email already exists</p>";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed);

            if ($stmt->execute()) {
                $message = "<p style='color:#a8ffb0;'>Account created successfully</p>";
            } else {
                $message = "<p style='color:#ffcccc;'>Error creating account</p>";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<?php include 'header.php'; ?>

<div class="container center-page">
    <div class="form-card">

        <h2>Create Account</h2>

        <?php echo $message; ?>

        <form method="POST">
            Name:<br>
            <input type="text" name="name" required>

            Email:<br>
            <input type="email" name="email" required>

            Password:<br>
            <input type="password" name="password" required>

            <button type="submit" name="register">Register</button>
        </form>

        <br>
        <a href="index.php" style="color:white;">Back to Login</a>

    </div>
</div>

<?php include 'footer.php'; ?>