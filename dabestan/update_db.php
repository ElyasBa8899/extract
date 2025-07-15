<?php
require_once "includes/db_singleton.php";
$link = get_db_connection();

function columnExists($link, $tableName, $columnName) {
    $result = mysqli_query($link, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return mysqli_num_rows($result) > 0;
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",

    "ALTER TABLE `task_comments`
      ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
      ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;",

    "ALTER TABLE `task_history`
      ADD CONSTRAINT `task_history_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
      ADD CONSTRAINT `task_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;"
];

if (!columnExists($link, 'classes', 'region_id')) {
    $queries[] = "ALTER TABLE `classes` ADD `region_id` INT(11) NULL DEFAULT NULL AFTER `status`, ADD INDEX `region_id` (`region_id`);";
}


foreach ($queries as $query) {
    if (mysqli_query($link, $query)) {
        echo "Query executed successfully: " . htmlspecialchars($query) . "<br>";
    } else {
        echo "Error executing query: " . mysqli_error($link) . "<br>";
    }
}

mysqli_close($link);
?>
