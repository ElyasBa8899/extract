<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit;
}

$user_id = $_SESSION['id'];

// Mark all unread notifications for the user as read
$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>
