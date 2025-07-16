<?php
// Telegram Bot Token
define('TELEGRAM_BOT_TOKEN', '7726563483:AAF8TeGuly0SgloqO6CGVfUj5cBNyMXC8sk');

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'dabestan_db');

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($link, "utf8mb4");

// --- Task Notification Settings ---

// How many days before a deadline to send a reminder to the assigned user.
define('TASK_REMINDER_DAYS_BEFORE', 1);

// The name of the role to notify when a task becomes overdue.
define('OVERDUE_TASK_NOTIFY_ROLE', 'Admin');
?>
