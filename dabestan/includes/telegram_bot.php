<?php
require_once __DIR__ . '/../config.php';

function sendTelegramMessage($chat_id, $message) {
    if (empty($chat_id)) {
        return; // Do not send if chat_id is not set
    }

    $token = TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// Example of how to use it:
// 1. Get the chat_id for a user from the database
// 2. Call the function: sendTelegramMessage($user_chat_id, "Your message here.");

?>
