<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/access_control.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!has_permission('manage_users')) {
    echo "<div class='alert alert-danger'>شما مجوز دسترسی به این صفحه را ندارید.</div>";
    require_once "../includes/footer.php";
    exit;
}

// Fetch all users with their roles
$sql = "SELECT u.id, u.username, u.first_name, u.last_name, GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        GROUP BY u.id
        ORDER BY u.username ASC";
$result = mysqli_query($link, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<div class="page-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>مدیریت کاربران</h2>
            <a href="create_user.php" class="btn btn-primary">ایجاد کاربر جدید</a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>نام کاربری</th>
                        <th>نام</th>
                        <th>نام خانوادگی</th>
                        <th>نقش‌ها</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['roles']); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">ویرایش</a>
                            <!-- Add delete functionality with confirmation -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
