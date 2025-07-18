<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

// Redirect if user is not admin
if (!is_admin()) {
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// Handle form submission for adding/editing a role
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['role_name'])) {
    $role_name = trim($_POST['role_name']);
    $role_description = trim($_POST['role_description']);
    $role_id = isset($_POST['role_id']) ? $_POST['role_id'] : null;

    if (empty($role_name)) {
        $error = "نام نقش نمی‌تواند خالی باشد.";
    } else {
        try {
            if ($role_id) {
                // Update existing role
                $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, role_description = ? WHERE id = ?");
                $stmt->execute([$role_name, $role_description, $role_id]);
                $message = "نقش با موفقیت ویرایش شد.";
            } else {
                // Add new role
                $stmt = $pdo->prepare("INSERT INTO roles (role_name, role_description) VALUES (?, ?)");
                $stmt->execute([$role_name, $role_description]);
                $message = "نقش جدید با موفقیت اضافه شد.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || $e->getCode() == '23000' || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                 $error = "خطا: نام نقش باید منحصر به فرد باشد. این نام قبلاً استفاده شده است.";
            } else {
                $error = "خطا در ثبت اطلاعات: " . $e->getMessage();
            }
        }
    }
}

// Handle role deletion
if (isset($_GET['delete'])) {
    $role_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->execute([$role_id]);
        $message = "نقش با موفقیت حذف شد.";
    } catch (PDOException $e) {
        $error = "خطا در حذف نقش: " . $e->getMessage();
    }
}

// Fetch all roles to display
try {
    $roles_stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $roles = $roles_stmt->fetchAll();
} catch (PDOException $e) {
    $error = "خطا در دریافت لیست نقش‌ها: " . $e->getMessage();
    $roles = [];
}

// Fetch a single role for editing
$edit_role = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_role = $stmt->fetch();
}

?>

<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت نقش‌ها</h2>
        <p>در این بخش می‌توانید نقش‌های کاربری مختلف را در سیستم تعریف و مدیریت کنید.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5><?php echo $edit_role ? 'ویرایش نقش' : 'افزودن نقش جدید'; ?></h5>
            </div>
            <div class="card-body">
                <form action="manage_roles.php" method="post">
                    <?php if ($edit_role): ?>
                        <input type="hidden" name="role_id" value="<?php echo htmlspecialchars($edit_role['id']); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="role_name">نام نقش</label>
                        <input type="text" id="role_name" name="role_name" class="form-control" value="<?php echo htmlspecialchars($edit_role['role_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="role_description">توضیحات نقش</label>
                        <textarea id="role_description" name="role_description" class="form-control"><?php echo htmlspecialchars($edit_role['role_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_role ? 'ذخیره تغییرات' : 'افزودن نقش'; ?></button>
                        <?php if ($edit_role): ?>
                            <a href="manage_roles.php" class="btn btn-secondary">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <hr>

        <div class="card mt-4">
            <div class="card-header">
                <h5>لیست نقش‌های موجود</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>نام نقش</th>
                                <th>توضیحات</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roles)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">هیچ نقشی تعریف نشده است.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                        <td><?php echo htmlspecialchars($role['role_description']); ?></td>
                                        <td>
                                            <a href="manage_roles.php?edit=<?php echo $role['id']; ?>" class="btn btn-sm btn-info">ویرایش</a>
                                            <a href="manage_permissions.php?role_id=<?php echo $role['id']; ?>" class="btn btn-sm btn-warning">مجوزها</a>
                                            <a href="manage_roles.php?delete=<?php echo $role['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این نقش مطمئن هستید؟');">حذف</a>
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

<?php require_once "../includes/footer.php"; ?>
