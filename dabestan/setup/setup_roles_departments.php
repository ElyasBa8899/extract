<?php
require_once '../includes/db.php';

echo "Starting setup...\n";

// --- Define Roles and Departments ---
$roles = [
    ['role_name' => 'مدیر دبستان', 'role_description' => 'دسترسی کامل به تمام بخش‌های مدیریتی.'],
    ['role_name' => 'معاون دبستان', 'role_description' => 'دسترسی به بخش‌های مدیریتی مشخص شده.'],
    ['role_name' => 'مدیر بخش', 'role_description' => 'مدیریت یک بخش خاص (مانند نظارت، پرورشی).'],
    ['role_name' => 'معاون بخش', 'role_description' => 'کمک به مدیر بخش در مدیریت امور.'],
    ['role_name' => 'عضو بخش', 'role_description' => 'عضو یک یا چند بخش سازمانی.'],
    ['role_name' => 'مدرس', 'role_description' => 'دسترسی‌های استاندارد برای مدرسان.']
];

$departments = [
    ['department_name' => 'نظارت', 'department_description' => 'بخش نظارت بر عملکرد کلاس‌ها'],
    ['department_name' => 'پرورشی', 'department_description' => 'بخش امور پرورشی و فرهنگی'],
    ['department_name' => 'ضمن خدمت', 'department_description' => 'بخش آموزش‌های ضمن خدمت مدرسان'],
    ['department_name' => 'اولیا', 'department_description' => 'بخش ارتباط با اولیای دانش‌آموزان'],
    ['department_name' => 'امید تدریس', 'department_description' => 'بخش آموزش مدرسان جدید'],
    ['department_name' => 'منابع انسانی', 'department_description' => 'بخش مدیریت امور پرسنل'],
    ['department_name' => 'مالی', 'department_description' => 'بخش امور مالی و پشتیبانی'],
    ['department_name' => 'جذب و راه اندازی', 'department_description' => 'بخش جذب دانش‌آموزان جدید']
];


// --- Insert Roles ---
$sql_role = "INSERT INTO roles (role_name, role_description) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_description=VALUES(role_description)";
$stmt_role = mysqli_prepare($link, $sql_role);

if ($stmt_role) {
    foreach ($roles as $role) {
        mysqli_stmt_bind_param($stmt_role, "ss", $role['role_name'], $role['role_description']);
        if (mysqli_stmt_execute($stmt_role)) {
            echo "Role '{$role['role_name']}' processed successfully.\n";
        } else {
            echo "Error inserting role '{$role['role_name']}': " . mysqli_stmt_error($stmt_role) . "\n";
        }
    }
    mysqli_stmt_close($stmt_role);
} else {
    echo "Error preparing role statement: " . mysqli_error($link) . "\n";
}

echo "\nRoles setup finished.\n";

// --- Insert Departments ---
$sql_dept = "INSERT INTO departments (department_name, department_description) VALUES (?, ?) ON DUPLICATE KEY UPDATE department_description=VALUES(department_description)";
$stmt_dept = mysqli_prepare($link, $sql_dept);

if ($stmt_dept) {
    foreach ($departments as $dept) {
        mysqli_stmt_bind_param($stmt_dept, "ss", $dept['department_name'], $dept['department_description']);
        if (mysqli_stmt_execute($stmt_dept)) {
            echo "Department '{$dept['department_name']}' processed successfully.\n";
        } else {
            echo "Error inserting department '{$dept['department_name']}': " . mysqli_stmt_error($stmt_dept) . "\n";
        }
    }
    mysqli_stmt_close($stmt_dept);
} else {
    echo "Error preparing department statement: " . mysqli_error($link) . "\n";
}

echo "\nDepartments setup finished.\n";


// --- Finalize ---
mysqli_close($link);
echo "\nSetup complete!\n";
?>
