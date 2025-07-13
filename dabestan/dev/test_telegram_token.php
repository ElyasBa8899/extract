<?php
// A simple script to check if the Telegram Bot Token is valid.
// Place this in a temporary 'dev' folder and run it directly from your browser.

require_once '../config.php';

echo "<h1>Telegram Token Validator</h1>";

if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN') {
    echo "<p style='color: red;'><strong>Error:</strong> Telegram token is not defined in config.php. Please set it.</p>";
    exit;
}

$token = TELEGRAM_BOT_TOKEN;
$url = "https://api.telegram.org/bot{$token}/getMe";

echo "<p>Checking token: " . htmlspecialchars(substr($token, 0, 10)) . "...</p>";

// Use cURL to send the request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Add timeout to prevent long waits
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> " . htmlspecialchars($curl_error) . "</p>";
    echo "<p>This might indicate a network issue between your server (XAMPP) and Telegram's servers. Check your firewall or network settings.</p>";

} elseif ($http_code !== 200) {
    echo "<p style='color: red;'><strong>API Request Failed!</strong></p>";
    echo "<p>HTTP Status Code: " . $http_code . "</p>";
    echo "<p>Response from Telegram:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    echo "<p>This usually means the token is incorrect or has been revoked.</p>";

} else {
    $data = json_decode($response, true);
    if ($data && $data['ok'] === true) {
        echo "<p style='color: green;'><strong>Success! Token is valid.</strong></p>";
        echo "<p>Bot Name: " . htmlspecialchars($data['result']['first_name']) . "</p>";
        echo "<p>Bot Username: @" . htmlspecialchars($data['result']['username']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>Error:</strong> Telegram API returned an error.</p>";
        echo "<p>Response:</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}
?>
