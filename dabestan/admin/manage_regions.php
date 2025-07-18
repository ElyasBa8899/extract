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

// Handle form submission for adding/editing a region
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['region_name'])) {
    $region_name = trim($_POST['region_name']);
    $description = trim($_POST['description']);
    $region_id = isset($_POST['region_id']) ? $_POST['region_id'] : null;

    if (empty($region_name)) {
        $error = "نام منطقه نمی‌تواند خالی باشد.";
    } else {
        try {
            if ($region_id) {
                $stmt = $pdo->prepare("UPDATE regions SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$region_name, $description, $region_id]);
                $message = "منطقه با موفقیت ویرایش شد.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO regions (name, description) VALUES (?, ?)");
                $stmt->execute([$region_name, $description]);
                $message = "منطقه جدید با موفقیت اضافه شد.";
            }
        } catch (PDOException $e) {
            $error = "خطا در ثبت اطلاعات: " . $e->getMessage();
        }
    }
}

// Handle region deletion
if (isset($_GET['delete'])) {
    $region_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM regions WHERE id = ?");
        $stmt->execute([$region_id]);
        $message = "منطقه با موفقیت حذف شد.";
    } catch (PDOException $e) {
        $error = "خطا در حذف منطقه: " . $e->getMessage();
    }
}

// Fetch all regions to display
try {
    $regions_stmt = $pdo->query("SELECT * FROM regions ORDER BY name");
    $regions = $regions_stmt->fetchAll();
} catch (PDOException $e) {
    $error = "خطا در دریافت لیست مناطق: " . $e->getMessage();
    $regions = [];
}

// Fetch a single region for editing
$edit_region = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM regions WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_region = $stmt->fetch();
}

?>

<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت مناطق</h2>
        <p>در این بخش می‌توانید مناطق جغرافیایی را برای دسته‌بندی دانش‌آموزان جذب شده، تعریف کنید.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5><?php echo $edit_region ? 'ویرایش منطقه' : 'افزودن منطقه جدید'; ?></h5>
            </div>
            <div class="card-body">
                <form action="manage_regions.php" method="post">
                    <?php if ($edit_region): ?>
                        <input type="hidden" name="region_id" value="<?php echo htmlspecialchars($edit_region['id']); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="region_name">نام منطقه</label>
                        <input type="text" id="region_name" name="region_name" class="form-control" value="<?php echo htmlspecialchars($edit_region['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">توضیحات</label>
                        <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($edit_region['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_region ? 'ذخیره تغییرات' : 'افزودن منطقه'; ?></button>
                        <?php if ($edit_region): ?>
                            <a href="manage_regions.php" class="btn btn-secondary">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <hr>

        <div class="card mt-4">
            <div class="card-header">
                <h5>لیست مناطق موجود</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>نام منطقه</th>
                                <th>توضیحات</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($regions)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">هیچ منطقه‌ای تعریف نشده است.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($regions as $region): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($region['name']); ?></td>
                                        <td><?php echo htmlspecialchars($region['description']); ?></td>
                                        <td>
                                            <a href="manage_regions.php?edit=<?php echo $region['id']; ?>" class="btn btn-sm btn-info">ویرایش</a>
                                            <a href="manage_regions.php?delete=<?php echo $region['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این منطقه مطمئن هستید؟');">حذف</a>
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
