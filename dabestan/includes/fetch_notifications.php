<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['id'];

// Fetch unread notifications
$sql = "SELECT id, message, link, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get the count of unread notifications
$sql_count = "SELECT COUNT(id) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt_count = mysqli_prepare($link, $sql_count);
mysqli_stmt_bind_param($stmt_count, "i", $user_id);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$count_data = mysqli_fetch_assoc($result_count);
$unread_count = $count_data['unread_count'];

mysqli_close($link);

echo json_encode(['notifications' => $notifications, 'unread_count' => $unread_count]);
?>
