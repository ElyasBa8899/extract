<?php
// Telegram Bot Token
define('TELEGRAM_BOT_TOKEN', '7726563483:AAF8TeGuly0SgloqO6CGVfUj5cBNyMXC8sk');

// -- Database Configuration --
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'dabestan_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Task Notification Settings ---
// How many days before a deadline to send a reminder to the assigned user.
define('TASK_REMINDER_DAYS_BEFORE', 1);

// The name of the role to notify when a task becomes overdue.
define('OVERDUE_TASK_NOTIFY_ROLE', 'Admin');

// --- Global PDO object ---
// Use the get_db_connection() function from db_singleton.php to access this.
$pdo = null;
?>
