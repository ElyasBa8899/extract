<?php

// Migration: 006
// Description: Adds reminder tracking column to tasks table.
// Date: 2024-07-16

function run_migration_006_update_tasks_for_reminders($link) {
    $queries = [];
    $errors = [];
    $success_count = 0;

    // --- 1. Add last_reminder_sent_at column ---
    $check_column_q = mysqli_query($link, "SHOW COLUMNS FROM `tasks` LIKE 'last_reminder_sent_at'");
    if (mysqli_num_rows($check_column_q) == 0) {
        $queries[] = "ALTER TABLE `tasks`
                      ADD COLUMN `last_reminder_sent_at` DATETIME NULL DEFAULT NULL AFTER `completed_at`;";
    }

    echo "<h4>اجرای به‌روزرسانی 006: افزودن ستون یادآوری به وظایف</h4>";

    if (empty($queries)) {
        echo "<p class='alert alert-info'>پایگاه داده شما برای این نسخه از قبل به‌روز است.</p>";
        return true;
    }

    foreach ($queries as $query) {
        if (mysqli_query($link, $query)) {
            $success_count++;
            echo "<p style='color: green;'>&#10004; دستور با موفقیت اجرا شد.</p>";
        } else {
            $errors[] = "خطا در اجرای دستور: " . mysqli_error($link);
        }
    }

    if (empty($errors)) {
        echo "<p class='alert alert-success'>تمام " . $success_count . " دستور این نسخه با موفقیت اجرا شد.</p>";
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
