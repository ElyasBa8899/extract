<?php
require_once __DIR__ . '/../config.php';

function get_db_connection() {
    global $pdo;

    if ($pdo === null) {
        try {
            // Check if the pdo_sqlite extension is loaded
            if (!extension_loaded('pdo_sqlite')) {
                throw new Exception("PDO SQLite extension is not loaded. Please enable it in your php.ini file.");
            }

            $db_path = DB_PATH;
            $pdo = new PDO(DB_TYPE . ':' . $db_path);

            // Set error mode to exception
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Set default fetch mode to associative array
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign key constraints for SQLite
            $pdo->exec('PRAGMA foreign_keys = ON;');

        } catch (PDOException $e) {
            // It's better to log this error than to display it directly
            // For now, we'll die with a generic message
            error_log("Database Connection Error: " . $e->getMessage());
            die("ERROR: Could not connect to the database. Please check the server logs.");
        } catch (Exception $e) {
            error_log("Configuration Error: " . $e->getMessage());
            die("ERROR: A configuration error occurred. Please check the server logs.");
        }
    }

    return $pdo;
}

// No need for a close function with PDO, it's handled automatically when the script ends.
?>
