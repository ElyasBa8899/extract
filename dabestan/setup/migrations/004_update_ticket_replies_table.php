<?php

// Migration: 004
// Description: Adds is_log column to ticket_replies table.
// Date: 2024-07-16

function run_migration_004_update_ticket_replies_table($link) {
    $queries = [];
    $errors = [];
    $success_count = 0;

    // --- 1. Add is_log column ---
    $check_column_q = mysqli_query($link, "SHOW COLUMNS FROM `ticket_replies` LIKE 'is_log'");
    if (mysqli_num_rows($check_column_q) == 0) {
        $queries[] = "ALTER TABLE `ticket_replies`
                      ADD COLUMN `is_log` BOOLEAN NOT NULL DEFAULT FALSE AFTER `reply_message`;";
    }

    echo "<h4>اجرای به‌روزرسانی 004: افزودن ستون لاگ به پاسخ‌های تیکت</h4>";

    if (empty($queries)) {
        echo "<p class='alert alert-info'>پایگاه داده شما برای این نسخه از قبل به‌روز است. هیچ تغییری لازم نبود.</p>";
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
