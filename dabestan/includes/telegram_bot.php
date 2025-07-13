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

    // Using file_get_contents as an alternative to cURL
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($post_fields),
            'timeout' => 10, // 10 seconds timeout
        ],
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        return json_encode(['ok' => false, 'description' => 'file_get_contents error: ' . ($error['message'] ?? 'Unknown error')]);
    }

    // Return Telegram's direct response (which is already JSON)
    return $response;
}

// Example of how to use it:
// 1. Get the chat_id for a user from the database
// 2. Call the function: sendTelegramMessage($user_chat_id, "Your message here.");

?>
