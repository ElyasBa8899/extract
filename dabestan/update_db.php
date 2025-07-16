<?php
require_once "includes/db_singleton.php";
$link = get_db_connection();

function columnExists($link, $tableName, $columnName) {
    $result = mysqli_query($link, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    $exists = (mysqli_num_rows($result)) ? TRUE : FALSE;
    return $exists;
}

function constraintExists($link, $tableName, $constraintName) {
    $dbName = 'dabestan_db';
    $query = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '$tableName' AND CONSTRAINT_NAME = '$constraintName'";
    $result = mysqli_query($link, $query);
    $exists = (mysqli_num_rows($result)) ? TRUE : FALSE;
    return $exists;
}


$queries = [
    "CREATE TABLE IF NOT EXISTS `task_comments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `task_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `comment` text NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `task_id` (`task_id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",

    "CREATE TABLE IF NOT EXISTS `task_history` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `task_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `action` varchar(255) NOT NULL,
      `details` text,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `task_id` (`task_id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;"
];

if (!columnExists($link, 'classes', 'region_id')) {
    $queries[] = "ALTER TABLE `classes` ADD `region_id` INT(11) NULL DEFAULT NULL AFTER `status`, ADD INDEX `region_id` (`region_id`);";
}

if (!constraintExists($link, 'task_comments', 'task_comments_ibfk_1')) {
    $queries[] = "ALTER TABLE `task_comments` ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;";
}
if (!constraintExists($link, 'task_comments', 'task_comments_ibfk_2')) {
    $queries[] = "ALTER TABLE `task_comments` ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;";
}
if (!constraintExists($link, 'task_history', 'task_history_ibfk_1')) {
    $queries[] = "ALTER TABLE `task_history` ADD CONSTRAINT `task_history_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;";
}
if (!constraintExists($link, 'task_history', 'task_history_ibfk_2')) {
    $queries[] = "ALTER TABLE `task_history` ADD CONSTRAINT `task_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;";
}

if (!columnExists($link, 'notifications', 'type')) {
    $queries[] = "ALTER TABLE `notifications` ADD `type` VARCHAR(50) NOT NULL AFTER `user_id`, ADD `related_id` INT(11) NULL DEFAULT NULL AFTER `type`;";
}

$queries[] = "
CREATE TABLE IF NOT EXISTS `task_reassignment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `requested_by_id` int(11) NOT NULL,
  `requested_to_id` int(11) NOT NULL,
  `new_user_id` int(11) NOT NULL,
  `comment` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `requested_by_id` (`requested_by_id`),
  KEY `new_user_id` (`new_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";


echo <<<HTML
<style>
    body { font-family: sans-serif; line-height: 1.6; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    pre { background-color: #f4f4f4; padding: 10px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; }
</style>
HTML;

foreach ($queries as $query) {
    if (mysqli_query($link, $query)) {
        echo "<p class='success'>Query executed successfully:</p>";
        echo "<pre>" . htmlspecialchars($query) . "</pre>";
    } else {
        echo "<p class='error'>Error executing query:</p>";
        echo "<pre>" . htmlspecialchars($query) . "</pre>";
        echo "<p class='error'><strong>MySQL Error:</strong> " . mysqli_error($link) . "</p>";
    }
    echo "<hr>";
}

?>
