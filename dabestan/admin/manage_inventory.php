<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// Now, instead of just checking for is_admin, we check for a specific permission.
require_permission('manage_inventory');

$err = $success_msg = "";

// Fetch categories for the dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM inventory_categories ORDER BY name ASC";
if($result = mysqli_query($link, $sql_categories)){
    $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Handle Add Item POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $quantity = trim($_POST['quantity']);
    $category_id = trim($_POST['category_id']);

    if (empty($item_name) || !isset($quantity)) {
        $err = "نام و تعداد قلم الزامی است.";
    } else {
        $sql = "INSERT INTO inventory_items (name, description, quantity, category_id) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $item_name, $description, $quantity, $category_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "قلم جدید با موفقیت به انبار اضافه شد.";
            } else {
                $err = "خطا در افزودن قلم.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all inventory items
$items = [];
$sql_items = "SELECT i.id, i.name, i.description, i.quantity, c.name as category_name
              FROM inventory_items i
              LEFT JOIN inventory_categories c ON i.category_id = c.id
              ORDER BY i.name ASC";
if($result_items = mysqli_query($link, $sql_items)){
    $items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت انبار (اقلام کرایه‌چی)</h2>
    <p>در این بخش اقلام موجود در انبار را اضافه یا مدیریت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Item Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن قلم جدید به انبار</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="item_name">نام قلم <span style="color: red;">*</span></label>
                <input type="text" name="item_name" id="item_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <input type="text" name="description" id="description" class="form-control">
            </div>
            <div class="form-group">
                <label for="quantity">تعداد موجود <span style="color: red;">*</span></label>
                <input type="number" name="quantity" id="quantity" class="form-control" required min="0" value="0">
            </div>
            <div class="form-group">
                <label for="category_id">دسته‌بندی</label>
                <select name="category_id" id="category_id" class="form-control">
                    <option value="">بدون دسته‌بندی</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" name="add_item" class="btn btn-primary" value="افزودن قلم">
            </div>
        </form>
    </div>

    <!-- List of Existing Items -->
    <div class="table-container">
        <h3>لیست اقلام موجود در انبار</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>نام قلم</th>
                    <th>دسته‌بندی</th>
                    <th>تعداد موجود</th>
                    <th>توضیحات</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" style="text-align: center;">هیچ قلمی در انبار ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td>
                                <a href="#" class="btn btn-secondary btn-sm">ویرایش</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
