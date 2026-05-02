<?php
$host = "127.0.0.1";
$db = "caicedr1_bug_tracker";
$user = "caicedr1_bugtracker_user";
$pass = "Lovesuandoi415!";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed");
}
?>