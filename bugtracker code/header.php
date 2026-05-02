<?php
// header.php
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Bug Tracker</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="main-header">

  <div class="header-left">
      <img src="header_wide.jpeg" alt="Bugbusters Logo">
  </div>

  <div class="header-nav"></div>

  <div class="header-right">
      <img src="header_square.jpeg" alt="Square Logo">
  </div>

</header>

<?php if (isset($_SESSION['role'])): ?>
    <div class="sub-header">
        <span>
            <strong><?php echo ucfirst($_SESSION['role']); ?> Dashboard</strong> — Welcome <?php echo $_SESSION['name']; ?>
        </span>
        <a href="logout.php">Logout</a>
    </div>
<?php endif; ?>