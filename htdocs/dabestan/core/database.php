<?php
// Make sure the config file is included
require_once __DIR__ . '/../config.php';

/**
 * Establishes a database connection using mysqli.
 *
 * @return mysqli|false The mysqli connection object on success, or false on failure.
 */
function get_db_connection() {
    // Create a new mysqli connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check for connection errors
    if ($conn->connect_error) {
        // Log the error to a file or display a generic error message
        // For security, don't display the detailed mysqli error on a production server
        error_log("Database Connection Failed: " . $conn->connect_error);
        return false;
    }

    // Set the character set to utf8mb4 for full Unicode support
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
    }

    return $conn;
}

/**
 * A global variable to hold the database connection object.
 * This can be used throughout the application to access the database.
 */
$db = get_db_connection();

// Check if the connection was successful
if ($db === false) {
    // You can handle the error more gracefully here, e.g., show a maintenance page.
    die("Error: Unable to connect to the database. Please check the configuration.");
}
?>
