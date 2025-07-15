<?php
// Define a constant for the project root directory to be used everywhere.
// This makes file includes and links much more reliable.
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__FILE__));
}

// Define a constant for the web root URL.
// This helps in creating absolute URLs for links and assets.
// It automatically determines if the server is using HTTPS and gets the host name.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/dabestan'; // Assuming the project is in the 'dabestan' subfolder

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
?>
