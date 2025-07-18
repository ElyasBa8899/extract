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
$selected_role_id = isset($_GET['role_id']) ? $_GET['role_id'] : null;

// Handle permission assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['role_id'])) {
    $role_id = $_POST['role_id'];
    $assigned_permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Delete old permissions for this role
        $stmt_delete = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt_delete->execute([$role_id]);

        // Insert new permissions
        $stmt_insert = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($assigned_permissions as $permission_id) {
            $stmt_insert->execute([$role_id, $permission_id]);
        }

        // Commit transaction
        $pdo->commit();
        $message = "مجوزهای نقش با موفقیت به‌روزرسانی شد.";
        $selected_role_id = $role_id; // Keep the role selected after update

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "خطا در ذخیره مجوزها: " . $e->getMessage();
    }
}


// Fetch all roles
try {
    $roles_stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $roles = $roles_stmt->fetchAll();
} catch (PDOException $e) {
    $error = "خطا در دریافت لیست نقش‌ها: " . $e->getMessage();
    $roles = [];
}

// Fetch all permissions
try {
    $permissions_stmt = $pdo->query("SELECT * FROM permissions ORDER BY permission_name");
    $permissions = $permissions_stmt->fetchAll();
} catch (PDOException $e) {
    $error .= "<br>خطا در دریافت لیست مجوزها: " . $e->getMessage();
    $permissions = [];
}

// Fetch permissions for the selected role
$role_permissions = [];
if ($selected_role_id) {
    $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$selected_role_id]);
    $role_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

?>

<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت مجوزها</h2>
        <p>در این بخش می‌توانید مجوزهای هر نقش را مدیریت کنید.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>انتخاب نقش برای تخصیص مجوز</h5>
            </div>
            <div class="card-body">
                <form action="manage_permissions.php" method="get" class="form-inline">
                    <div class="form-group">
                        <select name="role_id" class="form-control" onchange="this.form.submit()">
                            <option value="">--- یک نقش را انتخاب کنید ---</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo ($selected_role_id == $role['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_role_id): ?>
        <hr>
        <div class="card mt-4">
            <div class="card-header">
                <h5>مجوزهای نقش: <?php echo htmlspecialchars(array_values(array_filter($roles, fn($r) => $r['id'] == $selected_role_id))[0]['role_name'] ?? ''); ?></h5>
            </div>
            <div class="card-body">
                <form action="manage_permissions.php?role_id=<?php echo $selected_role_id; ?>" method="post">
                    <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
                    <div class="permission-grid">
                        <?php foreach ($permissions as $permission): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" id="perm_<?php echo $permission['id']; ?>" <?php echo in_array($permission['id'], $role_permissions) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="perm_<?php echo $permission['id']; ?>">
                                    <strong><?php echo htmlspecialchars($permission['permission_name']); ?></strong>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($permission['permission_description']); ?></small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">ذخیره مجوزها</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
<style>
.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}
.form-check {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}
</style>

<?php require_once "../includes/footer.php"; ?>
