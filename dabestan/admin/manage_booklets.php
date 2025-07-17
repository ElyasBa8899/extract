<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

require_permission('manage_financials');

$err = $success_msg = "";

// Handle Add/Edit Booklet
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_booklet'])) {
    $booklet_id = $_POST['booklet_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];

    if (empty($name) || $price < 0) {
        $err = "نام جزوه و قیمت (عدد مثبت) الزامی است.";
    } else {
        if (empty($booklet_id)) { // Add new
            $sql = "INSERT INTO booklets (name, description, price) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssd", $name, $description, $price);
            $success_msg = "جزوه جدید با موفقیت افزوده شد.";
        } else { // Update existing
            $sql = "UPDATE booklets SET name = ?, description = ?, price = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssdi", $name, $description, $price, $booklet_id);
            $success_msg = "جزوه با موفقیت ویرایش شد.";
        }

        if (mysqli_stmt_execute($stmt)) {
            // Success
        } else {
            $err = "خطا در ذخیره‌سازی جزوه.";
            $success_msg = "";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Delete Booklet
if (isset($_GET['delete_id'])) {
    $booklet_id_to_delete = $_GET['delete_id'];
    // Optional: Check for dependencies before deleting
    $sql = "DELETE FROM booklets WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $booklet_id_to_delete);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "جزوه با موفقیت حذف شد.";
    } else {
        $err = "خطا در حذف جزوه.";
    }
    mysqli_stmt_close($stmt);
}


// Fetch booklets
$booklets_sql = "SELECT * FROM booklets ORDER BY name ASC";
$booklets_result = mysqli_query($link, $booklets_sql);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2><i class="fas fa-book-open"></i> مدیریت جزوات</h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Add/Edit Booklet Form -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن/ویرایش جزوه</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="booklet_id" id="booklet_id" value="">
            <div class="form-group">
                <label for="name">نام جزوه</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="price">قیمت (تومان)</label>
                <input type="number" name="price" id="price" class="form-control" required min="0" step="0.01">
            </div>
            <div class="form-group">
                <button type="submit" name="save_booklet" class="btn btn-primary">ذخیره</button>
            </div>
        </form>
    </div>

    <!-- List of Booklets -->
    <div class="table-container">
        <h3>لیست جزوات</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>نام جزوه</th>
                        <th>قیمت</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($booklet = mysqli_fetch_assoc($booklets_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booklet['name']); ?></td>
                        <td><?php echo number_format($booklet['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($booklet['description']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editBooklet(<?php echo htmlspecialchars(json_encode($booklet)); ?>)">ویرایش</button>
                            <a href="manage_booklets.php?delete_id=<?php echo $booklet['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editBooklet(booklet) {
    document.getElementById('booklet_id').value = booklet.id;
    document.getElementById('name').value = booklet.name;
    document.getElementById('description').value = booklet.description;
    document.getElementById('price').value = booklet.price;
    window.scrollTo(0, 0); // Scroll to top to see the form
}
</script>

<?php
require_once "../includes/footer.php";
?>
