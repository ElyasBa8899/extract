<?php
require_once 'includes/db.php';

$pdo = get_db_connection();

$sql = file_get_contents('database.sql');

try {
    $pdo->exec($sql);
    echo "Database tables created successfully.";
} catch (PDOException $e) {
    die("DB ERROR: ". $e->getMessage());
}
