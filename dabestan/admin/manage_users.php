<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) {
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// Handle form submission for adding/editing a user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
    $assigned_roles = isset($_POST['roles']) ? $_POST['roles'] : [];

    if (empty($username)) {
        $error = "نام کاربری نمی‌تواند خالی باشد.";
    } elseif (empty($password) && !$user_id) {
        $error = "رمز عبور برای کاربر جدید الزامی است.";
    } else {
        try {
            $pdo->beginTransaction();

            if ($user_id) {
                // Update existing user
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, password = ?, is_admin = ? WHERE id = ?");
                    $stmt->execute([$username, $full_name, $hashed_password, $is_admin, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, is_admin = ? WHERE id = ?");
                    $stmt->execute([$username, $full_name, $is_admin, $user_id]);
                }
                $message = "کاربر با موفقیت ویرایش شد.";
            } else {
                // Add new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password, is_admin) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $full_name, $hashed_password, $is_admin]);
                $user_id = $pdo->lastInsertId();
                $message = "کاربر جدید با موفقیت اضافه شد.";
            }

            // Update user roles
            $stmt_delete_roles = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmt_delete_roles->execute([$user_id]);

            $stmt_insert_roles = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            foreach ($assigned_roles as $role_id) {
                $stmt_insert_roles->execute([$user_id, $role_id]);
            }

            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                 $error = "خطا: نام کاربری باید منحصر به فرد باشد. این نام قبلاً استفاده شده است.";
            } else {
                $error = "خطا در ثبت اطلاعات: " . $e->getMessage();
            }
        }
    }
}


// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $message = "کاربر با موفقیت حذف شد.";
    } catch (PDOException $e) {
        $error = "خطا در حذف کاربر: " . $e->getMessage();
    }
}

// Fetch all users and their roles
try {
    $users_stmt = $pdo->query("
        SELECT u.*, GROUP_CONCAT(r.role_name) as roles
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        GROUP BY u.id
        ORDER BY u.username
    ");
    $users = $users_stmt->fetchAll();
} catch (PDOException $e) {
    $error = "خطا در دریافت لیست کاربران: " . $e->getMessage();
    $users = [];
}

// Fetch all roles for the form
try {
    $roles_stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $all_roles = $roles_stmt->fetchAll();
} catch (PDOException $e) {
    $error .= "<br>خطا در دریافت لیست نقش‌ها: " . $e->getMessage();
    $all_roles = [];
}


// Fetch a single user for editing
$edit_user = null;
$edit_user_roles = [];
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();

    if ($edit_user) {
        $stmt_roles = $pdo->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
        $stmt_roles->execute([$edit_id]);
        $edit_user_roles = $stmt_roles->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

?>

<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت کاربران</h2>
        <p>در این بخش می‌توانید کاربران سیستم را اضافه، ویرایش یا حذف کنید.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5><?php echo $edit_user ? 'ویرایش کاربر' : 'افزودن کاربر جدید'; ?></h5>
            </div>
            <div class="card-body">
                <form action="manage_users.php" method="post">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user['id']); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="username">نام کاربری</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required>
                    </div>
                     <div class="form-group">
                        <label for="full_name">نام کامل</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">رمز عبور</label>
                        <input type="password" id="password" name="password" class="form-control" <?php echo !$edit_user ? 'required' : ''; ?>>
                        <?php if ($edit_user): ?>
                            <small class="form-text text-muted">برای تغییر ندادن رمز عبور، این فیلد را خالی بگذارید.</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin" value="1" <?php echo (isset($edit_user['is_admin']) && $edit_user['is_admin']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_admin">
                                کاربر ادمین کل است
                            </label>
                             <small class="form-text text-muted">این کاربر به تمامی بخش‌های مدیریتی دسترسی خواهد داشت.</small>
                        </div>
                    </div>

                    <hr>
                    <h5>تخصیص نقش‌ها</h5>
                    <div class="permission-grid">
                        <?php foreach ($all_roles as $role): ?>
                             <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" id="role_<?php echo $role['id']; ?>" <?php echo in_array($role['id'], $edit_user_roles) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="role_<?php echo $role['id']; ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>


                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_user ? 'ذخیره تغییرات' : 'افزودن کاربر'; ?></button>
                        <?php if ($edit_user): ?>
                            <a href="manage_users.php" class="btn btn-secondary">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <hr>

        <div class="card mt-4">
            <div class="card-header">
                <h5>لیست کاربران</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>نام کاربری</th>
                                <th>نام کامل</th>
                                <th>نقش‌ها</th>
                                <th>ادمین کل</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">هیچ کاربری یافت نشد.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['roles'] ? str_replace(',', '، ', $user['roles']) : '---'); ?></td>
                                        <td><?php echo $user['is_admin'] ? '<span class="badge badge-success">بله</span>' : 'خیر'; ?></td>
                                        <td>
                                            <a href="manage_users.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">ویرایش</a>
                                            <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این کاربر مطمئن هستید؟');">حذف</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
</style>

<?php require_once "../includes/footer.php"; ?>
