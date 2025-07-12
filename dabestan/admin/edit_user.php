<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header("location: manage_users.php");
    exit;
}

$user_id_to_edit = $_GET['user_id'];
$err = $success_msg = "";

// Fetch user details
$user_query = mysqli_query($link, "SELECT * FROM users WHERE id = $user_id_to_edit");
$user = mysqli_fetch_assoc($user_query);
if(!$user){ echo "کاربر یافت نشد."; exit; }

// Fetch all available roles
$all_roles_query = mysqli_query($link, "SELECT id, role_name FROM roles ORDER BY role_name ASC");
$all_roles = mysqli_fetch_all($all_roles_query, MYSQLI_ASSOC);

// Fetch roles currently assigned to this user
$current_roles_query = mysqli_query($link, "SELECT role_id FROM user_roles WHERE user_id = $user_id_to_edit");
$current_roles = array_column(mysqli_fetch_all($current_roles_query, MYSQLI_ASSOC), 'role_id');

// Handle User Update POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $new_roles = $_POST['roles'] ?? [];

    mysqli_begin_transaction($link);
    try {
        // 1. Update basic info
        $sql_update_user = "UPDATE users SET first_name = ?, last_name = ? WHERE id = ?";
        $stmt_update_user = mysqli_prepare($link, $sql_update_user);
        mysqli_stmt_bind_param($stmt_update_user, "ssi", $first_name, $last_name, $user_id_to_edit);
        mysqli_stmt_execute($stmt_update_user);

        // 2. Delete old roles for this user
        mysqli_query($link, "DELETE FROM user_roles WHERE user_id = $user_id_to_edit");

        // 3. Insert new ones
        if(!empty($new_roles)){
            $sql_insert_roles = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
            $stmt_insert_roles = mysqli_prepare($link, $sql_insert_roles);
            foreach($new_roles as $role_id){
                mysqli_stmt_bind_param($stmt_insert_roles, "ii", $user_id_to_edit, $role_id);
                mysqli_stmt_execute($stmt_insert_roles);
            }
            mysqli_stmt_close($stmt_insert_roles);
        }

        mysqli_commit($link);
        $success_msg = "اطلاعات کاربر با موفقیت به‌روزرسانی شد.";
        // Refresh current roles for display
        $current_roles_query = mysqli_query($link, "SELECT role_id FROM user_roles WHERE user_id = $user_id_to_edit");
        $current_roles = array_column(mysqli_fetch_all($current_roles_query, MYSQLI_ASSOC), 'role_id');

    } catch (Exception $e) {
        mysqli_rollback($link);
        $err = "خطا در به‌روزرسانی اطلاعات.";
    }
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_users.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت کاربران</a>
    <h2>ویرایش کاربر: <?php echo htmlspecialchars($user['username']); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form action="edit_user.php?user_id=<?php echo $user_id_to_edit; ?>" method="post">
        <div class="form-container" style="margin-bottom: 30px;">
            <h4>اطلاعات پایه</h4>
            <div class="form-group">
                <label>نام</label>
                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>">
            </div>
            <div class="form-group">
                <label>نام خانوادگی</label>
                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>">
            </div>
        </div>

        <div class="form-container">
            <h4>تخصیص نقش‌ها</h4>
            <?php foreach($all_roles as $role): ?>
                <div class="checkbox-group">
                    <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" id="role_<?php echo $role['id']; ?>"
                        <?php if(in_array($role['id'], $current_roles)) echo 'checked'; ?>>
                    <label for="role_<?php echo $role['id']; ?>">
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <input type="submit" name="update_user" class="btn btn-primary" value="ذخیره تغییرات">
        </div>
    </form>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
