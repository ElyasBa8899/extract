<?php

// Migration: 001
// Description: Alters general_events and class_students tables to add new columns and constraints.
// Date: 2024-07-15

function run_migration_001_alter_tables($link) {
    $queries = [];
    $errors = [];
    $success_count = 0;

    // --- 1. Alter general_events table ---
    // Check if event_year column exists
    $check_column_q = mysqli_query($link, "SHOW COLUMNS FROM `general_events` LIKE 'event_year'");
    if (mysqli_num_rows($check_column_q) == 0) {
        $queries[] = "ALTER TABLE `general_events`
                      ADD COLUMN `event_year` INT(4) NULL AFTER `event_name`,
                      ADD COLUMN `proposal` TEXT COLLATE utf8mb4_persian_ci NULL AFTER `description`,
                      ADD COLUMN `required_workforce` TEXT COLLATE utf8mb4_persian_ci NULL AFTER `proposal`,
                      ADD COLUMN `required_budget` DECIMAL(15, 2) NULL AFTER `required_workforce`;";
    }

    // --- 2. Alter class_students table ---
    // Check if added_by_user_id column exists
    $check_column_q2 = mysqli_query($link, "SHOW COLUMNS FROM `class_students` LIKE 'added_by_user_id'");
    if (mysqli_num_rows($check_column_q2) == 0) {
        // We also check if the old 'phone_number' column exists to change it.
        $check_phone_col_q = mysqli_query($link, "SHOW COLUMNS FROM `class_students` LIKE 'phone_number'");
        if (mysqli_num_rows($check_phone_col_q) > 0) {
             $queries[] = "ALTER TABLE `class_students`
                           CHANGE COLUMN `phone_number` `added_by_user_id` INT(11) NULL DEFAULT NULL;";
             // Before adding the constraint, we need to make sure existing data doesn't violate it.
             // Let's set any existing non-null, non-matching values to NULL.
             // We assume that if a value is not a valid user ID, it should be nullified.
             // A simple way is to set all to NULL if they are not 0, as 0 is not a valid user ID.
             $queries[] = "UPDATE `class_students` SET `added_by_user_id` = NULL WHERE `added_by_user_id` NOT IN (SELECT id FROM users) AND `added_by_user_id` IS NOT NULL;";
             $queries[] = "ALTER TABLE `class_students`
                           ADD CONSTRAINT `class_students_ibfk_2` FOREIGN KEY (`added_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;";
        }
    }

    echo "<h4>اجرای به‌روزرسانی 001:</h4>";

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
