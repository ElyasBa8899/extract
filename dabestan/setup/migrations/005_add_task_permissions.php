<?php

// Migration: 005
// Description: Adds new permissions for the task management system.
// Date: 2024-07-16

function run_migration_005_add_task_permissions($link) {
    $permissions = [
        ['manage_tasks', 'دسترسی به منوی مدیریت وظایف و مشاهده وظایف محول شده'],
        ['create_task', 'اجازه ایجاد وظیفه جدید برای دیگران'],
        ['view_all_tasks', 'اجازه مشاهده تمام وظایف در سیستم (مخصوص ادمین)']
    ];

    echo "<h4>اجرای به‌روزرسانی 005: افزودن مجوزهای سیستم وظایف</h4>";
    $errors = [];
    $success_count = 0;

    $stmt = mysqli_prepare($link, "INSERT INTO permissions (permission_name, permission_description) VALUES (?, ?)");

    foreach ($permissions as $permission) {
        // Check if permission already exists
        $check_q = mysqli_query($link, "SELECT id FROM permissions WHERE permission_name = '" . mysqli_real_escape_string($link, $permission[0]) . "'");
        if (mysqli_num_rows($check_q) == 0) {
            mysqli_stmt_bind_param($stmt, "ss", $permission[0], $permission[1]);
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            } else {
                $errors[] = "خطا در افزودن مجوز '" . $permission[0] . "': " . mysqli_error($link);
            }
        }
    }

    mysqli_stmt_close($stmt);

    if (empty($errors)) {
        if ($success_count > 0) {
            echo "<p class='alert alert-success'>تعداد " . $success_count . " مجوز جدید با موفقیت به سیستم اضافه شد.</p>";
        } else {
            echo "<p class='alert alert-info'>مجوزهای سیستم وظایف از قبل در سیستم موجود بودند.</p>";
        }
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
