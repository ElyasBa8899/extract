<?php
// This file can be used for general purpose functions

require_once 'jdf.php';

/**
 * Converts a MySQL DATETIME string to a Persian date format.
 *
 * @param string $datetime_str The MySQL DATETIME string (e.g., "2024-07-13 10:00:00").
 * @param string $format The format for the output date (uses jdf formatting).
 * @return string The formatted Persian date.
 */
function to_persian_date($datetime_str, $format = 'Y/m/d H:i') {
    if (empty($datetime_str)) {
        return '';
    }
    $timestamp = strtotime($datetime_str);
    return jdf($format, $timestamp);
}

/**
 * Sends a message to a specific Telegram user.
 *
 * @param string $chat_id The recipient's Telegram Chat ID.
 * @param string $message The message text.
 * @return bool True on success, false on failure.
 */
function send_telegram_message($chat_id, $message) {
    // IMPORTANT: Replace with your actual bot token
    $bot_token = 'YOUR_BOT_TOKEN';

    if (empty($chat_id) || $bot_token == 'YOUR_BOT_TOKEN') {
        return false;
    }

    $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result !== false;
}
?>
