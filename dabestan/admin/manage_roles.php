<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Handle Add Role POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_role'])) {
    $role_name = trim($_POST['role_name']);
    $role_description = trim($_POST['role_description']);

    if (empty($role_name)) {
        $err = "نام نقش نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO roles (role_name, role_description) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $role_name, $role_description);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "نقش جدید با موفقیت ایجاد شد.";
            } else {
                $err = "خطا در ایجاد نقش. شاید این نام قبلاً استفاده شده باشد.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle Delete Role Request
if (isset($_GET['delete_role'])) {
    $role_to_delete = $_GET['delete_role'];
    // First, check if any user has this role
    $sql_check = "SELECT COUNT(*) as count FROM user_roles WHERE role_id = ?";
    $stmt_check = mysqli_prepare($link, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $role_to_delete);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $count = mysqli_fetch_assoc($result_check)['count'];

    if($count > 0){
        $err = "این نقش به یک یا چند کاربر اختصاص داده شده و قابل حذف نیست.";
    } else {
        $sql = "DELETE FROM roles WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $role_to_delete);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "نقش با موفقیت حذف شد.";
            } else {
                $err = "خطا در حذف نقش.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}


// Fetch all existing roles
$roles = [];
$sql_roles = "SELECT id, role_name, role_description FROM roles ORDER BY role_name ASC";
if($result = mysqli_query($link, $sql_roles)){
    $roles = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت نقش‌ها</h2>
    <p>در این بخش نقش‌های مختلف کاربران در سیستم را تعریف کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Role Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ایجاد نقش جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="role_name">نام نقش (مثال: مسئول پرورشی)</label>
                <input type="text" name="role_name" id="role_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="role_description">توضیحات نقش</label>
                <input type="text" name="role_description" id="role_description" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_role" class="btn btn-primary" value="ایجاد نقش">
            </div>
        </form>
    </div>

    <!-- List of Existing Roles -->
    <div class="table-container">
        <h3>نقش‌های موجود</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>نام نقش</th>
                    <th>توضیحات</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($roles)): ?>
                    <tr><td colspan="3" style="text-align: center;">هیچ نقشی تعریف نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                            <td><?php echo htmlspecialchars($role['role_description']); ?></td>
                            <td>
                                <a href="edit_role_permissions.php?role_id=<?php echo $role['id']; ?>" class="btn btn-primary btn-sm">مدیریت دسترسی‌ها</a>
                                <a href="manage_roles.php?delete_role=<?php echo $role['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این نقش مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
