<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Handle Add Category POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);

    if (empty($category_name)) {
        $err = "نام دسته‌بندی نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO inventory_categories (name) VALUES (?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $category_name);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "دسته‌بندی جدید با موفقیت اضافه شد.";
            } else {
                $err = "خطا در افزودن دسته‌بندی.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle Delete Category Request
if (isset($_GET['delete_category'])) {
    $category_to_delete = $_GET['delete_category'];
    $sql = "DELETE FROM inventory_categories WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $category_to_delete);
        if(mysqli_stmt_execute($stmt)){
            $success_msg = "دسته‌بندی با موفقیت حذف شد.";
        } else {
            $err = "خطا در حذف دسته‌بندی. ممکن است اقلامی به این دسته‌بندی تخصیص داده شده باشند.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all existing categories
$categories = [];
$sql = "SELECT id, name FROM inventory_categories ORDER BY name ASC";
if($result = mysqli_query($link, $sql)){
    $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
mysqli_close($link);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت دسته‌بندی‌های انبار</h2>
    <p>در این بخش دسته‌بندی‌های اقلام موجود در انبار یا کرایه‌چی را تعریف کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Category Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن دسته‌بندی جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="category_name">نام دسته‌بندی</label>
                <input type="text" name="category_name" id="category_name" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" name="add_category" class="btn btn-primary" value="افزودن">
            </div>
        </form>
    </div>

    <!-- List of Existing Categories -->
    <div class="table-container">
        <h3>دسته‌بندی‌های موجود</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>نام دسته‌بندی</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="2" style="text-align: center;">هیچ دسته‌بندی‌ای تعریف نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td>
                                <a href="manage_categories.php?delete_category=<?php echo $category['id']; ?>" class="btn btn-danger" onclick="return confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
