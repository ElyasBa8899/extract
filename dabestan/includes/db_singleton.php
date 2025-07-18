<?php
require_once __DIR__ . '/../config.php';

function get_db_connection() {
    global $pdo;

    if ($pdo === null) {
        try {
            if (DB_TYPE === 'sqlite') {
                if (!extension_loaded('pdo_sqlite')) {
                    throw new Exception("PDO SQLite extension is not loaded. Please enable it in your php.ini file.");
                }
                $pdo = new PDO('sqlite:' . DB_PATH);
                $pdo->exec('PRAGMA foreign_keys = ON;');
            } elseif (DB_TYPE === 'mysql') {
                if (!extension_loaded('pdo_mysql')) {
                    throw new Exception("PDO MySQL extension is not loaded. Please enable it in your php.ini file.");
                }
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } else {
                throw new Exception("Unsupported database type defined in config.php");
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
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
