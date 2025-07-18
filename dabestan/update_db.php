<?php
require_once __DIR__ . '/includes/db_singleton.php';

// Path to the SQLite database file
$db_path = DB_PATH;

// If the database file already exists, delete it to start fresh
if (file_exists($db_path)) {
    unlink($db_path);
    echo "Existing database file deleted.<br>";
}

// Get the database connection (this will create the file)
$pdo = get_db_connection();

// Read the SQL file using an absolute path
$sql_file = __DIR__ . '/final_database.sql';
$sql = file_get_contents($sql_file);

if ($sql === false) {
    die("Error: Unable to read the SQL file at '{$sql_file}'.");
}

// Simplified cleanup for SQLite compatibility
$sql = preg_replace('/--.*$/m', '', $sql); // Remove SQL comments
$sql = preg_replace('/SET .*;/m', '', $sql);
$sql = preg_replace('/START TRANSACTION;/m', '', $sql);
$sql = preg_replace('/COMMIT;/m', '', $sql);
$sql = preg_replace('/ENGINE\s*=\s*\w+/i', '', $sql);
$sql = preg_replace('/COLLATE\s*=\s*\w+/i', '', $sql);
$sql = preg_replace('/`/', '', $sql);
$sql = preg_replace('/unsigned /i', ' ', $sql);
$sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);

// Convert AUTO_INCREMENT to SQLite's format
// This is still a tricky part, as SQLite's AUTOINCREMENT is a bit different.
// It must be on an "INTEGER PRIMARY KEY" column.
$sql = preg_replace('/int\(\d+\)\s+NOT NULL\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);


// Split into individual statements
$statements = explode(';', $sql);

// Execute each statement
foreach ($statements as $statement) {
    if (trim($statement) == '') {
        continue;
    }
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        echo "Error executing statement: " . $e->getMessage() . "<br>";
        echo "Statement: <pre>" . htmlspecialchars($statement) . "</pre><br>";
    }
}

echo "Database structure updated successfully from '{$sql_file}'.";

?>
