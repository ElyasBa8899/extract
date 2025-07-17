<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

require_permission('manage_inventory');

$err = $success_msg = "";

// Handle Add/Edit Category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_category'])) {
    $category_id = $_POST['category_id'];
    $name = trim($_POST['name']);

    if (empty($name)) {
        $err = "نام دسته‌بندی الزامی است.";
    } else {
        if (empty($category_id)) { // Add new
            $sql = "INSERT INTO inventory_categories (name) VALUES (?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "s", $name);
            $success_msg = "دسته‌بندی جدید با موفقیت افزوده شد.";
        } else { // Update existing
            $sql = "UPDATE inventory_categories SET name = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "si", $name, $category_id);
            $success_msg = "دسته‌بندی با موفقیت ویرایش شد.";
        }

        if (mysqli_stmt_execute($stmt)) {
            // Success
        } else {
            $err = "خطا در ذخیره‌سازی دسته‌بندی.";
            $success_msg = "";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Delete Category
if (isset($_GET['delete_id'])) {
    $category_id_to_delete = $_GET['delete_id'];
    // First, un-categorize items in this category
    mysqli_query($link, "UPDATE inventory_items SET category_id = NULL WHERE category_id = $category_id_to_delete");

    // Then, delete the category
    $sql = "DELETE FROM inventory_categories WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id_to_delete);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "دسته‌بندی با موفقیت حذف شد.";
    } else {
        $err = "خطا در حذف دسته‌بندی.";
    }
    mysqli_stmt_close($stmt);
}


// Fetch categories
$categories_sql = "SELECT * FROM inventory_categories ORDER BY name ASC";
$categories_result = mysqli_query($link, $categories_sql);

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_inventory.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت انبار</a>
    <h2><i class="fas fa-tags"></i> مدیریت دسته‌بندی‌های انبار</h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Add/Edit Category Form -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن/ویرایش دسته‌بندی</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="category_id" id="category_id" value="">
            <div class="form-group">
                <label for="name">نام دسته‌بندی</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group">
                <button type="submit" name="save_category" class="btn btn-primary">ذخیره</button>
            </div>
        </form>
    </div>

    <!-- List of Categories -->
    <div class="table-container">
        <h3>لیست دسته‌بندی‌ها</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>نام دسته‌بندی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">ویرایش</button>
                            <a href="manage_categories.php?delete_id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟ با حذف این دسته‌بندی، کالاهای مرتبط بی‌دسته خواهند شد.')">حذف</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editCategory(category) {
    document.getElementById('category_id').value = category.id;
    document.getElementById('name').value = category.name;
    window.scrollTo(0, 0);
}
</script>

<?php
require_once "../includes/footer.php";
?>
