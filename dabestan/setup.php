<?php
// This is a one-time setup file. Run it once, then delete it.
require_once "includes/db.php";

echo "<h1>Setup Script</h1>";

// 1. Seed initial permissions
echo "<h2>Seeding Permissions...</h2>";
$initial_permissions = [
    ['manage_users', 'توانایی ایجاد، ویرایش و حذف کاربران'],
    ['manage_roles', 'توانایی مدیریت نقش‌ها و دسترسی‌ها'],
    ['manage_forms', 'توانایی ایجاد و طراحی فرم‌ها'],
    ['view_all_submissions', 'توانایی مشاهده تمام پاسخ‌های فرم‌ها'],
    ['manage_inventory', 'توانایی مدیریت انبار و اقلام'],
    ['manage_financials', 'توانایی ثبت تراکنش‌های مالی و مدیریت جزوات'],
    ['view_all_financials', 'توانایی مشاهده تمام گزارش‌های مالی'],
    ['manage_meetings', 'توانایی مدیریت جلسات (ضمن خدمت، اولیا و...)'],
    ['manage_events', 'توانایی مدیریت رویدادهای عمومی'],
    ['submit_ticket', 'توانایی ارسال تیکت جدید'],
    ['view_all_tickets', 'توانایی مشاهده تمام تیکت‌های سیستم']
];
$sql_seed = "INSERT IGNORE INTO `permissions` (`permission_name`, `permission_description`) VALUES (?, ?)";
$stmt_seed = mysqli_prepare($link, $sql_seed);
foreach($initial_permissions as $p){
    mysqli_stmt_bind_param($stmt_seed, "ss", $p[0], $p[1]);
    if(mysqli_stmt_execute($stmt_seed)){
        echo "Permission '{$p[0]}' seeded successfully.<br>";
    }
}
mysqli_stmt_close($stmt_seed);
echo "<p>Permissions seeding complete.</p>";


// 2. Seed the main admin user
echo "<h2>Creating Super Admin User...</h2>";
$admin_user = 'admin';
$admin_pass = 'Admin_dabestan_site_110_59';
$hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);

$sql_admin = "INSERT IGNORE INTO `users` (`first_name`, `last_name`, `username`, `password`, `is_admin`) VALUES (?, ?, ?, ?, ?)";
if($stmt_admin = mysqli_prepare($link, $sql_admin)){
    $first = 'ادمین';
    $last = 'اصلی';
    $is_admin_val = 1; // The fix is here: using a variable instead of a literal.
    mysqli_stmt_bind_param($stmt_admin, "ssssi", $first, $last, $admin_user, $hashed_pass, $is_admin_val);
    if(mysqli_stmt_execute($stmt_admin)){
        if(mysqli_stmt_affected_rows($stmt_admin) > 0){
            echo "<p>Admin user '<b>admin</b>' created successfully.</p>";
            echo "<p>Password: <b>Admin_dabestan_site_110_59</b></p>";
        } else {
            echo "<p>Admin user '<b>admin</b>' already exists. No changes made.</p>";
        }
    } else {
        echo "<p style='color:red;'>Error creating admin user.</p>";
    }
    mysqli_stmt_close($stmt_admin);
}
echo "<p>Admin user setup complete.</p>";


echo "<hr><h1>Setup Complete!</h1>";
echo "<p style='color:red; font-weight:bold;'>Please delete this file (setup.php) immediately for security reasons.</p>";

mysqli_close($link);
?>
