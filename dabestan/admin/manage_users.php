<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

// Fetch all users
$users = [];
$sql = "SELECT id, username, first_name, last_name, is_admin FROM users ORDER BY username ASC";
if($result = mysqli_query($link, $sql)){
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
// // mysqli_close($link); // Removed from here, will be closed in footer

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت کاربران</h2>
    <p>در این بخش لیست کاربران سیستم را مشاهده و مدیریت کنید.</p>
    <a href="create_user.php" class="btn btn-primary" style="margin-bottom: 20px;">+ ایجاد کاربر جدید</a>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>نام کاربری</th>
                    <th>نام و نام خانوادگی</th>
                    <th>ادمین اصلی؟</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo $user['is_admin'] ? 'بله' : 'خیر'; ?></td>
                        <td>
                            <a href="edit_user.php?user_id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm">ویرایش و تخصیص نقش</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
