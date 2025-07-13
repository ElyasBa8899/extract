<?php
require_once __DIR__ . '/../config.php';

function sendTelegramMessage($chat_id, $message) {
    if (empty($chat_id) || !defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN') {
        // Return a structured error if config is missing
        return json_encode(['ok' => false, 'description' => 'Bot token or Chat ID is not configured.']);
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        // Return cURL errors as a structured response
        return json_encode(['ok' => false, 'description' => 'cURL Error: ' . $curl_error]);
    }

    // Return Telegram's direct response (which is already JSON)
    return $response;
}

// Example of how to use it:
// 1. Get the chat_id for a user from the database
// 2. Call the function: sendTelegramMessage($user_chat_id, "Your message here.");

?>
