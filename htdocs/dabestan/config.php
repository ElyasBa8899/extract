<?php
// ################# DATABASE CONFIGURATION #################
// Database host (usually "localhost")
define('DB_HOST', '127.0.0.1');

// Database username
define('DB_USER', 'root');

// Database password
define('DB_PASS', '');

// Database name
define('DB_NAME', 'dabestan_db');


// ################# SITE CONFIGURATION #################
// Base URL of the site (e.g., http://localhost/dabestan)
// It's recommended to set this dynamically, but for now, a static value is fine.
define('BASE_URL', 'http://localhost/dabestan');

// Site name
define('SITE_NAME', 'دبستان');


// ################# OTHER SETTINGS #################
// Set the default timezone
date_default_timezone_set('Asia/Tehran');

// Enable/disable error reporting for development/production
// For development, it's good to see all errors.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// For production, you should turn this off.
// error_reporting(0);
// ini_set('display_errors', 0);

// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
