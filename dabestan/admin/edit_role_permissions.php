<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['role_id']) || empty($_GET['role_id'])) {
    header("location: manage_roles.php");
    exit;
}

$role_id = $_GET['role_id'];
$err = $success_msg = "";

// Fetch role details
$role = mysqli_fetch_assoc(mysqli_query($link, "SELECT role_name FROM roles WHERE id = $role_id"));
if(!$role){ echo "نقش یافت نشد."; exit; }

// Seed and Fetch all available permissions
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
    mysqli_stmt_execute($stmt_seed);
}
mysqli_stmt_close($stmt_seed);

$all_permissions = mysqli_fetch_all(mysqli_query($link, "SELECT id, permission_name, permission_description FROM permissions"), MYSQLI_ASSOC);

// Fetch permissions currently assigned to this role
$current_permissions_result = mysqli_query($link, "SELECT permission_id FROM role_permissions WHERE role_id = $role_id");
$current_permissions = array_column(mysqli_fetch_all($current_permissions_result, MYSQLI_ASSOC), 'permission_id');


// Handle Permissions Update POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_permissions'])) {
    $new_permissions = $_POST['permissions'] ?? [];

    mysqli_begin_transaction($link);
    try {
        // 1. Delete old permissions for this role
        mysqli_query($link, "DELETE FROM role_permissions WHERE role_id = $role_id");

        // 2. Insert new ones
        $sql_insert = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
        $stmt_insert = mysqli_prepare($link, $sql_insert);
        foreach($new_permissions as $perm_id){
            mysqli_stmt_bind_param($stmt_insert, "ii", $role_id, $perm_id);
            mysqli_stmt_execute($stmt_insert);
        }
        mysqli_stmt_close($stmt_insert);

        mysqli_commit($link);
        $success_msg = "دسترسی‌های نقش با موفقیت به‌روزرسانی شد.";
        // Refresh current permissions for display
        $current_permissions_result = mysqli_query($link, "SELECT permission_id FROM role_permissions WHERE role_id = $role_id");
        $current_permissions = array_column(mysqli_fetch_all($current_permissions_result, MYSQLI_ASSOC), 'permission_id');

    } catch (Exception $e) {
        mysqli_rollback($link);
        $err = "خطا در به‌روزرسانی دسترسی‌ها.";
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_roles.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت نقش‌ها</a>
    <h2>مدیریت دسترسی‌های نقش: <?php echo htmlspecialchars($role['role_name']); ?></h2>
    <p>دسترسی‌های مورد نظر برای این نقش را انتخاب کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="edit_role_permissions.php?role_id=<?php echo $role_id; ?>" method="post">
            <h3>لیست دسترسی‌ها</h3>
            <?php foreach($all_permissions as $permission): ?>
                <div class="checkbox-group">
                    <input type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" id="perm_<?php echo $permission['id']; ?>"
                        <?php if(in_array($permission['id'], $current_permissions)) echo 'checked'; ?>>
                    <label for="perm_<?php echo $permission['id']; ?>">
                        <strong><?php echo htmlspecialchars($permission['permission_name']); ?></strong> -
                        <small><?php echo htmlspecialchars($permission['permission_description']); ?></small>
                    </label>
                </div>
            <?php endforeach; ?>

            <div class="form-group" style="margin-top: 20px;">
                <input type="submit" name="update_permissions" class="btn btn-primary" value="ذخیره تغییرات">
            </div>
        </form>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
