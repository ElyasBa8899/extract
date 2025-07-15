<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Handle Rent Request
if (isset($_GET['rent_item_id'])) {
    $item_to_rent = $_GET['rent_item_id'];

    mysqli_begin_transaction($link);
    try {
        // 1. Check if item is available
        $sql_check = "SELECT quantity FROM inventory_items WHERE id = ? FOR UPDATE";
        $stmt_check = mysqli_prepare($link, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "i", $item_to_rent);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $item = mysqli_fetch_assoc($result_check);

        if ($item && $item['quantity'] > 0) {
            // 2. Decrement quantity
            $sql_update = "UPDATE inventory_items SET quantity = quantity - 1 WHERE id = ?";
            $stmt_update = mysqli_prepare($link, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "i", $item_to_rent);
            mysqli_stmt_execute($stmt_update);

            // 3. Log the rental
            $sql_log = "INSERT INTO item_rentals (item_id, user_id, rent_date) VALUES (?, ?, NOW())";
            $stmt_log = mysqli_prepare($link, $sql_log);
            mysqli_stmt_bind_param($stmt_log, "ii", $item_to_rent, $_SESSION['id']);
            mysqli_stmt_execute($stmt_log);

            mysqli_commit($link);
            $success_msg = "درخواست شما با موفقیت ثبت شد. جهت تحویل کالا با مسئول مربوطه هماهنگ کنید.";
        } else {
            $err = "متاسفانه این قلم در حال حاضر موجود نیست.";
            mysqli_rollback($link);
        }
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($link);
        $err = "خطایی در ثبت درخواست رخ داد.";
    }
}


// Fetch categories for the filter dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM inventory_categories ORDER BY name ASC";
if($result_cat = mysqli_query($link, $sql_categories)){
    $categories = mysqli_fetch_all($result_cat, MYSQLI_ASSOC);
}


// Fetch all available inventory items
$base_sql_items = "SELECT i.id, i.name, i.description, i.quantity, c.name as category_name
                   FROM inventory_items i
                   LEFT JOIN inventory_categories c ON i.category_id = c.id
                   WHERE i.quantity > 0";

$selected_category = '';
if(isset($_GET['category_id']) && !empty($_GET['category_id'])){
    $selected_category = $_GET['category_id'];
    $base_sql_items .= " AND i.category_id = ?";
}
$base_sql_items .= " ORDER BY i.name ASC";

$items = [];
if($stmt_items = mysqli_prepare($link, $base_sql_items)){
    if(!empty($selected_category)){
        mysqli_stmt_bind_param($stmt_items, "i", $selected_category);
    }
    mysqli_stmt_execute($stmt_items);
    $result_items = mysqli_stmt_get_result($stmt_items);
    $items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_items);
}


mysqli_close($link);
require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>لیست اقلام کرایه‌چی</h2>
    <p>در این بخش لیست اقلام قابل کرایه را مشاهده و درخواست خود را ثبت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Filter Form -->
    <div class="form-container" style="margin-bottom: 20px;">
        <form action="rental_items.php" method="get">
            <div class="form-group">
                <label for="category_id">فیلتر بر اساس دسته‌بندی:</label>
                <select name="category_id" id="category_id" class="form-control" onchange="this.form.submit()">
                    <option value="">همه دسته‌بندی‌ها</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php if($selected_category == $category['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Rental Items Table -->
    <div class="table-container">
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
                    <tr><td colspan="5" style="text-align: center;">هیچ قلمی با این مشخصات برای کرایه موجود نیست.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td>
                                <a href="rental_items.php?rent_item_id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('آیا از درخواست کرایه این قلم مطمئن هستید؟')">درخواست کرایه</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
