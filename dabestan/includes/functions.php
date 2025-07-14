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

/**
 * Translates class status from English to Persian.
 *
 * @param string $status The status in English (e.g., "active").
 * @return string The translated status in Persian.
 */
function translate_class_status($status) {
    $translation = [
        'active'    => 'فعال',
        'inactive'  => 'غیرفعال',
        'archived'  => 'آرشیو شده',
        'disbanded' => 'منحل شده',
        'setup'     => 'تحویل مقدمات'
    ];
    return $translation[$status] ?? $status;
}

/**
 * Returns an HTML badge for a given ticket status.
 *
 * @param string $status The status of the ticket.
 * @return string The HTML badge.
 */
function get_status_badge($status) {
    switch ($status) {
        case 'open':
            return '<span class="badge badge-primary">باز</span>';
        case 'in_progress':
            return '<span class="badge badge-warning">در حال بررسی</span>';
        case 'closed':
            return '<span class="badge badge-secondary">بسته شده</span>';
        default:
            return '<span class="badge badge-light">نامشخص</span>';
    }
}

/**
 * Returns an HTML badge for urgent priority tickets.
 *
 * @param string $priority The priority of the ticket.
 * @return string The HTML badge or an empty string.
 */
function get_priority_badge($priority) {
    if ($priority === 'urgent') {
        return '<span class="badge badge-danger">فوری</span>';
    }
    return '';
}
?>
