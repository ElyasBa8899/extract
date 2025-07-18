<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: manage_inventory
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// --- Category Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        $stmt = $pdo->prepare("INSERT INTO inventory_categories (name) VALUES (?)");
        $stmt->execute([$category_name]);
        $message = "دسته‌بندی جدید اضافه شد.";
    }
}

// --- Item Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $category_id = $_POST['category_id'] ?: null;
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);

    if (!empty($item_name) && $quantity !== false) {
        $stmt = $pdo->prepare("INSERT INTO inventory_items (name, category_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$item_name, $category_id, $quantity]);
        $message = "آیتم جدید به انبار اضافه شد.";
    } else {
        $error = "نام کالا و تعداد صحیح الزامی است.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $item_id = $_POST['item_id'];
    $new_quantity = filter_var($_POST['new_quantity'], FILTER_VALIDATE_INT);
    if ($new_quantity !== false) {
        $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $item_id]);
        $message = "موجودی کالا به‌روزرسانی شد.";
    }
}


// --- Fetch Data ---
$categories = $pdo->query("SELECT * FROM inventory_categories ORDER BY name")->fetchAll();
$items = $pdo->query("
    SELECT i.*, c.name as category_name
    FROM inventory_items i
    LEFT JOIN inventory_categories c ON i.category_id = c.id
    ORDER BY c.name, i.name
")->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت انبار</h2>
        <p>موجودی اقلام و دسته‌بندی‌های انبار را مدیریت کنید.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="row">
            <!-- Categories Management -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5>دسته‌بندی‌ها</h5></div>
                    <div class="card-body">
                        <form action="manage_inventory.php" method="post" class="input-group mb-3">
                            <input type="text" name="category_name" class="form-control" placeholder="نام دسته‌بندی جدید" required>
                            <div class="input-group-append">
                                <button type="submit" name="add_category" class="btn btn-secondary">افزودن</button>
                            </div>
                        </form>
                        <ul class="list-group">
                            <?php foreach($categories as $category): ?>
                                <li class="list-group-item"><?php echo htmlspecialchars($category['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Items Management -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h5>افزودن کالای جدید</h5></div>
                    <div class="card-body">
                        <form action="manage_inventory.php" method="post">
                            <div class="row">
                                <div class="col-md-5 form-group"><input type="text" name="item_name" class="form-control" placeholder="نام کالا" required></div>
                                <div class="col-md-4 form-group">
                                    <select name="category_id" class="form-control">
                                        <option value="">بدون دسته‌بندی</option>
                                        <?php foreach($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group"><input type="number" name="quantity" class="form-control" placeholder="تعداد اولیه" required></div>
                            </div>
                            <button type="submit" name="add_item" class="btn btn-primary">افزودن به انبار</button>
                        </form>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><h5>لیست موجودی انبار</h5></div>
                    <div class="card-body">
                         <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>کالا</th><th>دسته‌بندی</th><th>موجودی فعلی</th><th>به‌روزرسانی موجودی</th></tr></thead>
                                <tbody>
                                    <?php foreach($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category_name'] ?? '---'); ?></td>
                                        <td><strong><?php echo $item['quantity']; ?></strong></td>
                                        <td>
                                            <form action="manage_inventory.php" method="post" class="form-inline">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="new_quantity" class="form-control form-control-sm" style="width: 80px;" value="<?php echo $item['quantity']; ?>">
                                                <button type="submit" name="update_quantity" class="btn btn-sm btn-info">ثبت</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
