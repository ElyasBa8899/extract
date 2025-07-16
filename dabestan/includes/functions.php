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

function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'سال',
        'm' => 'ماه',
        'w' => 'هفته',
        'd' => 'روز',
        'h' => 'ساعت',
        'i' => 'دقیقه',
        's' => 'ثانیه',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' پیش' : 'همین الان';
}

function get_user_info($user_id) {
    $link = get_db_connection();
    $sql = "SELECT username, first_name, last_name FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }
    return null;
}

function get_users_by_role($role_name) {
    $link = get_db_connection();
    $users = [];
    $sql = "SELECT u.id, u.username
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id
            WHERE r.role_name = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $role_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
    return $users;
}

function send_notification($user_id, $type, $related_id, $message) {
    $link = get_db_connection();
    $sql = "INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, ?, ?, ?)";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "isis", $user_id, $type, $related_id, $message);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    return false;
}
?>
