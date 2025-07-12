<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['id'];

// Fetch unread count
$count_result = mysqli_query($link, "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = $user_id AND is_read = 0");
$unread_count = mysqli_fetch_assoc($count_result)['unread_count'];

// Fetch last 5 notifications
$notifications = [];
$sql = "SELECT message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

header('Content-Type: application/json');
echo json_encode([
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);

mysqli_close($link);
?>
