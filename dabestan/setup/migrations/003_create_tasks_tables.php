<?php

// Migration: 003
// Description: Creates tables for the new task management system.
// Date: 2024-07-16

function run_migration_003_create_tasks_tables($link) {
    $queries = [];
    $errors = [];
    $success_count = 0;

    // --- 1. Create `tasks` table ---
    $queries[] = "
    CREATE TABLE IF NOT EXISTS `tasks` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `title` VARCHAR(255) NOT NULL,
      `description` TEXT NULL,
      `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
      `priority` ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
      `deadline` DATETIME NULL DEFAULT NULL,
      `created_by` INT(11) NOT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `completed_at` DATETIME NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `created_by` (`created_by`),
      CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

    // --- 2. Create `task_assignments` table ---
    $queries[] = "
    CREATE TABLE IF NOT EXISTS `task_assignments` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `task_id` INT(11) NOT NULL,
      `assigned_to_user_id` INT(11) NULL DEFAULT NULL,
      `assigned_to_department_id` INT(11) NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `task_id` (`task_id`),
      KEY `assigned_to_user_id` (`assigned_to_user_id`),
      KEY `assigned_to_department_id` (`assigned_to_department_id`),
      CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
      CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`assigned_to_department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

    echo "<h4>اجرای به‌روزرسانی 003: ایجاد جداول سیستم مدیریت وظایف</h4>";

    foreach ($queries as $query) {
        if (mysqli_query($link, $query)) {
            $success_count++;
        } else {
            $errors[] = "خطا در اجرای دستور: " . mysqli_error($link) . "<br><code>" . htmlspecialchars($query) . "</code>";
        }
    }

    if (empty($errors)) {
        echo "<p class='alert alert-success'>تمام " . $success_count . " جدول با موفقیت ایجاد یا از قبل موجود بودند.</p>";
        return true;
    } else {
        echo "<p class='alert alert-danger'>برخی از دستورات با خطا مواجه شدند:</p><ul>";
        foreach($errors as $err) {
            echo "<li>$err</li>";
        }
        echo "</ul>";
        return false;
    }
}
?>
