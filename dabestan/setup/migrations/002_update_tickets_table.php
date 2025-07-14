<?php

// Migration: 002
// Description: Alters tickets table to add priority and specific user assignment.
// Date: 2024-07-15

function run_migration_002_update_tickets_table($link) {
    $queries = [];
    $errors = [];
    $success_count = 0;

    // --- 1. Add assigned_to_user_id column ---
    $check_column_q1 = mysqli_query($link, "SHOW COLUMNS FROM `tickets` LIKE 'assigned_to_user_id'");
    if (mysqli_num_rows($check_column_q1) == 0) {
        $queries[] = "ALTER TABLE `tickets`
                      ADD COLUMN `assigned_to_user_id` INT(11) NULL DEFAULT NULL AFTER `assigned_to_department_id`,
                      ADD KEY `assigned_to_user_id` (`assigned_to_user_id`),
                      ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;";
    }

    // --- 2. Add priority column and modify status enum ---
    $check_column_q2 = mysqli_query($link, "SHOW COLUMNS FROM `tickets` LIKE 'priority'");
    if (mysqli_num_rows($check_column_q2) == 0) {
        // Change status column to remove 'urgent' and add 'priority' column
        $queries[] = "ALTER TABLE `tickets`
                      CHANGE COLUMN `status` `status` ENUM('open', 'in_progress', 'closed') NOT NULL DEFAULT 'open',
                      ADD COLUMN `priority` ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal' AFTER `status`;";

        // Migrate old 'urgent' status to the new 'priority' column
        $queries[] = "UPDATE `tickets` SET `priority` = 'urgent' WHERE `status` = 'urgent';";
        // Reset old 'urgent' statuses to 'open'
        $queries[] = "UPDATE `tickets` SET `status` = 'open' WHERE `status` = 'urgent';";

    }

    echo "<h4>اجرای به‌روزرسانی 002:</h4>";

    if (empty($queries)) {
        echo "<p class='alert alert-info'>پایگاه داده شما برای این نسخه از قبل به‌روز است. هیچ تغییری لازم نبود.</p>";
        return true;
    }

    foreach ($queries as $query) {
        if (mysqli_query($link, $query)) {
            $success_count++;
            echo "<p style='color: green;'>&#10004; دستور با موفقیت اجرا شد: <code>" . htmlspecialchars(substr($query, 0, 80)) . "...</code></p>";
        } else {
            $errors[] = "خطا در اجرای دستور: " . mysqli_error($link) . "<br><code>" . htmlspecialchars($query) . "</code>";
            echo "<p style='color: red;'>&#10006; " . end($errors) . "</p>";
        }
    }

    if (empty($errors)) {
        echo "<p class='alert alert-success'>تمام " . $success_count . " دستور این نسخه با موفقیت اجرا شد.</p>";
        return true;
    } else {
         echo "<p class='alert alert-danger'>برخی از دستورات با خطا مواجه شدند.</p>";
        return false;
    }
}
?>
