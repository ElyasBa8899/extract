<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

require_permission('manage_inventory');

$err = $success_msg = "";

// Handle Add/Edit Item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_item'])) {
    $item_id = $_POST['item_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : null;

    if (empty($name) || $quantity < 0) {
        $err = "نام کالا و تعداد (عدد مثبت) الزامی است.";
    } else {
        if (empty($item_id)) { // Add new item
            $sql = "INSERT INTO inventory_items (name, description, quantity, category_id) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssii", $name, $description, $quantity, $category_id);
            $success_msg = "کالای جدید با موفقیت افزوده شد.";
        } else { // Update existing item
            $sql = "UPDATE inventory_items SET name = ?, description = ?, quantity = ?, category_id = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssiii", $name, $description, $quantity, $category_id, $item_id);
            $success_msg = "کالا با موفقیت ویرایش شد.";
        }

        if (mysqli_stmt_execute($stmt)) {
            // Success
        } else {
            $err = "خطا در ذخیره‌سازی کالا.";
            $success_msg = "";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Delete Item
if (isset($_GET['delete_id'])) {
    $item_id_to_delete = $_GET['delete_id'];
    // Optional: Check for dependencies (e.g., active rentals) before deleting
    $sql = "DELETE FROM inventory_items WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $item_id_to_delete);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "کالا با موفقیت حذف شد.";
    } else {
        $err = "خطا در حذف کالا.";
    }
    mysqli_stmt_close($stmt);
}


// Fetch items and categories
$items_sql = "SELECT i.*, c.name as category_name FROM inventory_items i LEFT JOIN inventory_categories c ON i.category_id = c.id ORDER BY i.name ASC";
$items_result = mysqli_query($link, $items_sql);

$categories_sql = "SELECT * FROM inventory_categories ORDER BY name ASC";
$categories_result = mysqli_query($link, $categories_sql);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2><i class="fas fa-warehouse"></i> مدیریت انبار</h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Add/Edit Item Form -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن/ویرایش کالا</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="item_id" id="item_id" value="">
            <div class="form-group">
                <label for="name">نام کالا</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="quantity">تعداد موجود</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" required min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category_id">دسته‌بندی</label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value="">بدون دسته‌بندی</option>
                            <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" name="save_item" class="btn btn-primary">ذخیره</button>
                <a href="manage_categories.php" class="btn btn-info">مدیریت دسته‌بندی‌ها</a>
            </div>
        </form>
    </div>

    <!-- List of Items -->
    <div class="table-container">
        <h3>لیست کالاهای موجود در انبار</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>نام کالا</th>
                        <th>دسته‌بندی</th>
                        <th>تعداد</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? '---'); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">ویرایش</button>
                            <a href="manage_inventory.php?delete_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editItem(item) {
    document.getElementById('item_id').value = item.id;
    document.getElementById('name').value = item.name;
    document.getElementById('description').value = item.description;
    document.getElementById('quantity').value = item.quantity;
    document.getElementById('category_id').value = item.category_id || '';
    window.scrollTo(0, 0); // Scroll to top to see the form
}
</script>

<?php
require_once "../includes/footer.php";
?>
