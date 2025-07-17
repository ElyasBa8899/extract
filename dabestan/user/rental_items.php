<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$err = $success_msg = "";

// Handle Rental Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_item'])) {
    $item_id = $_POST['item_id'];
    $notes = trim($_POST['notes']);

    // Check item availability
    $item_query = mysqli_query($link, "SELECT quantity FROM inventory_items WHERE id = $item_id");
    $item = mysqli_fetch_assoc($item_query);

    if ($item && $item['quantity'] > 0) {
        mysqli_begin_transaction($link);
        try {
            // 1. Decrease quantity
            mysqli_query($link, "UPDATE inventory_items SET quantity = quantity - 1 WHERE id = $item_id");

            // 2. Create rental record
            $sql = "INSERT INTO item_rentals (item_id, user_id, rent_date, notes) VALUES (?, ?, NOW(), ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "iis", $item_id, $user_id, $notes);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($link);
            $success_msg = "کالا با موفقیت برای شما ثبت شد. لطفا جهت تحویل با مسئول انبار هماهنگ کنید.";
        } catch (Exception $e) {
            mysqli_rollback($link);
            $err = "خطا در ثبت درخواست.";
        }
    } else {
        $err = "کالای مورد نظر موجود نیست یا تعداد آن صفر است.";
    }
}


// Fetch available items
$items_sql = "SELECT i.id, i.name, i.description, i.quantity, c.name as category_name
              FROM inventory_items i
              LEFT JOIN inventory_categories c ON i.category_id = c.id
              WHERE i.quantity > 0
              ORDER BY c.name, i.name ASC";
$items_result = mysqli_query($link, $items_sql);

// Fetch user's current rentals
$rentals_sql = "SELECT r.id, i.name as item_name, r.rent_date
                FROM item_rentals r
                JOIN inventory_items i ON r.item_id = i.id
                WHERE r.user_id = $user_id AND r.return_date IS NULL
                ORDER BY r.rent_date DESC";
$rentals_result = mysqli_query($link, $rentals_sql);


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2><i class="fas fa-hand-holding-box"></i> کرایه چی (امانت‌دهی)</h2>
    <p>در این بخش می‌توانید لیست کالاهای موجود در انبار را مشاهده کرده و برای امانت گرفتن، درخواست خود را ثبت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Current Rentals -->
    <div class="table-container" style="margin-bottom: 30px;">
        <h3>کالاهای امانت گرفته شده توسط شما</h3>
        <?php if(mysqli_num_rows($rentals_result) == 0): ?>
            <p>شما در حال حاضر هیچ کالایی را به امانت نگرفته‌اید.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>نام کالا</th>
                        <th>تاریخ امانت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($rental = mysqli_fetch_assoc($rentals_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rental['item_name']); ?></td>
                        <td><?php echo to_persian_date($rental['rent_date']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>


    <!-- Available Items -->
    <div class="table-container">
        <h3>لیست کالاهای قابل امانت</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>نام کالا</th>
                        <th>دسته‌بندی</th>
                        <th>تعداد موجود</th>
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
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display:inline;">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <!-- A notes field could be added here if needed -->
                                <input type="hidden" name="notes" value="">
                                <button type="submit" name="request_item" class="btn btn-sm btn-primary">درخواست امانت</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
