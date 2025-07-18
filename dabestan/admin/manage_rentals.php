<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: manage_rentals
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// Fetch data for forms
$items = $pdo->query("SELECT id, name, quantity FROM inventory_items WHERE quantity > 0 ORDER BY name")->fetchAll();
$users = $pdo->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name")->fetchAll();

// Handle Renting an Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rent_item'])) {
    $item_id = $_POST['item_id'];
    $user_id = $_POST['user_id'];
    $quantity = $_POST['quantity'];
    $notes = trim($_POST['notes']);

    if (empty($item_id) || empty($user_id) || empty($quantity)) {
        $error = "انتخاب آیتم، کاربر و تعداد الزامی است.";
    } elseif (!filter_var($quantity, FILTER_VALIDATE_INT) || $quantity <= 0) {
        $error = "تعداد باید یک عدد صحیح مثبت باشد.";
    } else {
        try {
            $pdo->beginTransaction();

            // Check available quantity
            $stmt_check = $pdo->prepare("SELECT quantity FROM inventory_items WHERE id = ?");
            $stmt_check->execute([$item_id]);
            $available_quantity = $stmt_check->fetchColumn();

            if ($available_quantity < $quantity) {
                throw new Exception("موجودی این کالا کافی نیست. موجودی فعلی: " . $available_quantity);
            }

            // Add rental record
            $stmt_insert = $pdo->prepare("INSERT INTO item_rentals (item_id, user_id, quantity, notes) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$item_id, $user_id, $quantity, $notes]);

            // Decrease inventory quantity
            $stmt_update = $pdo->prepare("UPDATE inventory_items SET quantity = quantity - ? WHERE id = ?");
            $stmt_update->execute([$quantity, $item_id]);

            $pdo->commit();
            $message = "کالا با موفقیت کرایه داده شد.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "خطا: " . $e->getMessage();
        }
    }
}

// Handle Returning an Item
if (isset($_GET['return'])) {
    $rental_id = $_GET['return'];
    try {
        $pdo->beginTransaction();

        // Get rental info
        $stmt_rental = $pdo->prepare("SELECT item_id, quantity FROM item_rentals WHERE id = ? AND return_date IS NULL");
        $stmt_rental->execute([$rental_id]);
        $rental = $stmt_rental->fetch();

        if ($rental) {
            // Mark as returned
            $stmt_return = $pdo->prepare("UPDATE item_rentals SET return_date = datetime('now') WHERE id = ?");
            $stmt_return->execute([$rental_id]);

            // Increase inventory quantity
            $stmt_update = $pdo->prepare("UPDATE inventory_items SET quantity = quantity + ? WHERE id = ?");
            $stmt_update->execute([$rental['quantity'], $rental['item_id']]);

            $pdo->commit();
            $message = "کالا با موفقیت به انبار بازگردانده شد.";
        } else {
            throw new Exception("این آیتم قبلا بازگردانده شده یا یافت نشد.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "خطا در بازگرداندن کالا: " . $e->getMessage();
    }
}


// Fetch current rentals
$rentals = $pdo->query("
    SELECT r.id, r.quantity, r.rental_date, i.name as item_name, u.full_name as user_name
    FROM item_rentals r
    JOIN inventory_items i ON r.item_id = i.id
    JOIN users u ON r.user_id = u.id
    WHERE r.return_date IS NULL
    ORDER BY r.rental_date DESC
")->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت کرایه‌چی</h2>
        <p>ثبت کرایه و بازگشت اقلام انبار.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header"><h5>کرایه دادن کالا</h5></div>
            <div class="card-body">
                <form action="manage_rentals.php" method="post">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="item_id">کالا</label>
                            <select name="item_id" class="form-control" required>
                                <option value="">-- انتخاب کالا --</option>
                                <?php foreach($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']) . " (موجودی: " . $item['quantity'] . ")"; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                             <label for="user_id">کاربر (مدرس)</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">-- انتخاب کاربر --</option>
                                <?php foreach($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="quantity">تعداد</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                         <div class="col-md-3 form-group">
                            <label for="notes">یادداشت</label>
                            <input type="text" name="notes" class="form-control">
                        </div>
                    </div>
                    <button type="submit" name="rent_item" class="btn btn-primary">ثبت کرایه</button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5>اقلام کرایه داده شده (در انتظار بازگشت)</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>کالا</th>
                                <th>تعداد</th>
                                <th>کرایه گیرنده</th>
                                <th>تاریخ کرایه</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rentals as $rental): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rental['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($rental['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($rental['user_name']); ?></td>
                                <td><?php echo to_persian_date($rental['rental_date']); ?></td>
                                <td>
                                    <a href="manage_rentals.php?return=<?php echo $rental['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('آیا از ثبت بازگشت این کالا مطمئن هستید؟');">ثبت بازگشت</a>
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
<?php require_once "../includes/footer.php"; ?>
