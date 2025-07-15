<?php
require_once '../includes/db.php';

echo "<h1>Seeding Permissions and Role-Permission Mappings</h1>";

// --- Define Permissions ---
// Format: [permission_name, description]
$permissions = [
    // Basic User Permissions
    ['submit_ticket', 'اجازه ثبت تیکت جدید'],
    ['view_own_financials', 'مشاهده وضعیت مالی خود'],
    ['fill_self_assessment', 'پر کردن فرم خوداظهاری'],
    ['edit_own_class_info', 'ویرایش اطلاعات کلاس‌های خود'],

    // Department & Admin Permissions
    ['view_department_menu', 'مشاهده منوهای بخش‌های سازمانی'],
    ['manage_users', 'مدیریت کامل کاربران (ایجاد، ویرایش، حذف)'],
    ['manage_roles', 'مدیریت نقش‌ها و مجوزها'],
    ['manage_classes', 'مدیریت تمام کلاس‌ها'],
    ['manage_forms', 'ایجاد و مدیریت فرم‌های پویا'],
    ['manage_inventory', 'مدیریت انبار و اموال'],
    ['manage_financials', 'مدیریت امور مالی کلی (جزوات، تراکنش‌ها)'],
    ['manage_donations', 'مدیریت کمک‌های مالی (صله)'],
    ['manage_recruitment', 'مدیریت بخش جذب و راه‌اندازی'],
    ['view_all_submissions', 'مشاهده تمام فرم‌های ثبت شده توسط دیگران'],
    ['view_analytics', 'مشاهده تحلیل‌ها و گزارشات'],
    ['manage_meetings', 'مدیریت جلسات (اولیا، ضمن خدمت و...)'],
];

// --- Define Role-Permission Mappings ---
// Format: [role_name, [permission_name, permission_name, ...]]
$role_permissions = [
    'مدرس' => [
        'submit_ticket',
        'view_own_financials',
        'fill_self_assessment',
        'edit_own_class_info'
    ],
    'عضو بخش' => [
        'submit_ticket',
        'view_own_financials',
        'fill_self_assessment',
        'edit_own_class_info',
        'view_department_menu' // So they can see the menu
    ],
    'مدیر بخش' => [
        'submit_ticket',
        'view_own_financials',
        'fill_self_assessment',
        'edit_own_class_info',
        'view_department_menu',
        'manage_meetings' // Example permission for a department head
    ],
    'معاون دبستان' => [
        'submit_ticket',
        'view_own_financials',
        'fill_self_assessment',
        'edit_own_class_info',
        'view_department_menu',
        'manage_classes',
        'view_all_submissions',
        'manage_meetings'
    ],
    'مدیر دبستان' => [ // This role gets almost all permissions
        'submit_ticket',
        'view_own_financials',
        'fill_self_assessment',
        'edit_own_class_info',
        'view_department_menu',
        'manage_users',
        'manage_roles',
        'manage_classes',
        'manage_forms',
        'manage_inventory',
        'manage_financials',
        'manage_donations',
        'manage_recruitment',
        'view_all_submissions',
        'view_analytics',
        'manage_meetings'
    ]
];

// --- Seeding Logic ---
mysqli_begin_transaction($link);
try {
    // 1. Insert Permissions
    echo "<h3>Processing Permissions...</h3>";
    $sql_perm = "INSERT INTO permissions (permission_name, permission_description) VALUES (?, ?) ON DUPLICATE KEY UPDATE permission_description=VALUES(permission_description)";
    $stmt_perm = mysqli_prepare($link, $sql_perm);
    foreach ($permissions as $p) {
        mysqli_stmt_bind_param($stmt_perm, "ss", $p[0], $p[1]);
        mysqli_stmt_execute($stmt_perm);
        echo "Permission '{$p[0]}' processed.<br>";
    }
    mysqli_stmt_close($stmt_perm);
    echo "<p style='color:green;'>Permissions seeded successfully.</p>";

    // 2. Map Permissions to Roles
    echo "<h3>Processing Role-Permission Mappings...</h3>";
    // Clear old mappings to ensure a clean slate
    mysqli_query($link, "DELETE FROM role_permissions");
    echo "Cleared old role-permission mappings.<br>";

    $sql_map = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
    $stmt_map = mysqli_prepare($link, $sql_map);

    foreach ($role_permissions as $role_name => $perms) {
        // Get role ID
        $role_res = mysqli_query($link, "SELECT id FROM roles WHERE role_name = '{$role_name}'");
        if (mysqli_num_rows($role_res) > 0) {
            $role_id = mysqli_fetch_assoc($role_res)['id'];
            echo "<b>Mapping for role '{$role_name}' (ID: {$role_id}):</b><br>";

            foreach ($perms as $perm_name) {
                // Get permission ID
                $perm_res = mysqli_query($link, "SELECT id FROM permissions WHERE permission_name = '{$perm_name}'");
                if (mysqli_num_rows($perm_res) > 0) {
                    $perm_id = mysqli_fetch_assoc($perm_res)['id'];
                    mysqli_stmt_bind_param($stmt_map, "ii", $role_id, $perm_id);
                    mysqli_stmt_execute($stmt_map);
                    echo "- Mapped permission '{$perm_name}' (ID: {$perm_id}).<br>";
                } else {
                    echo "<span style='color:orange;'>Warning: Permission '{$perm_name}' not found.</span><br>";
                }
            }
        } else {
            echo "<span style='color:orange;'>Warning: Role '{$role_name}' not found.</span><br>";
        }
    }
    mysqli_stmt_close($stmt_map);
    echo "<p style='color:green;'>Role-Permission mappings seeded successfully.</p>";

    mysqli_commit($link);
    echo "<h2>All operations completed successfully!</h2>";

} catch (Exception $e) {
    mysqli_rollback($link);
    echo "<h2>An error occurred!</h2>";
    echo "<p style='color:red;'>Transaction rolled back. Error: " . $e->getMessage() . "</p>";
}

mysqli_close($link);
?>
