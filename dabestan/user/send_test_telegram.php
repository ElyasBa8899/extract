<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/telegram_bot.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

// Get data from POST request
$data = json_decode(file_get_contents('php://input'), true);
$chat_id = $data['chat_id'] ?? null;

if (empty($chat_id)) {
    echo json_encode(['success' => false, 'error' => 'Chat ID is missing.']);
    exit;
}

$username = $_SESSION['username'];
$message = "🤖 پیام تست از سامانه دبستان 🤖\n\n";
$message .= "سلام " . htmlspecialchars($username) . " عزیز،\n";
$message .= "اتصال حساب کاربری شما به ربات تلگرام با موفقیت برقرار است.";

// Use the updated sendTelegramMessage function which now uses cURL
$response = sendTelegramMessage($chat_id, $message);

// Decode Telegram's response to check if it was successful
$response_data = json_decode($response, true);

if ($response_data && isset($response_data['ok']) && $response_data['ok']) {
    echo json_encode(['success' => true]);
} else {
    // The description key should contain the error message from cURL or Telegram
    $error_message = $response_data['description'] ?? 'An unknown error occurred.';
    echo json_encode(['success' => false, 'error' => $error_message]);
}
?>
