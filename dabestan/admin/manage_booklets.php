<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Handle Add Booklet POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_booklet'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);

    if (empty($name) || !is_numeric($price)) {
        $err = "نام و قیمت (عددی) جزوه الزامی است.";
    } else {
        $sql = "INSERT INTO booklets (name, price, description) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sds", $name, $price, $description);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "جزوه جدید با موفقیت اضافه شد.";
            } else {
                $err = "خطا در افزودن جزوه.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all existing booklets
$booklets = [];
$sql = "SELECT id, name, price, description FROM booklets ORDER BY name ASC";
if($result = mysqli_query($link, $sql)){
    $booklets = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
mysqli_close($link);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت جزوات</h2>
    <p>در این بخش جزوات قابل ارائه به مدرسین و قیمت آن‌ها را تعریف کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Booklet Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن جزوه جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="name">نام جزوه <span style="color: red;">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="price">قیمت (به تومان) <span style="color: red;">*</span></label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <input type="text" name="description" id="description" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_booklet" class="btn btn-primary" value="افزودن جزوه">
            </div>
        </form>
    </div>

    <!-- List of Existing Booklets -->
    <div class="table-container">
        <h3>لیست جزوات تعریف شده</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>نام جزوه</th>
                    <th>قیمت (تومان)</th>
                    <th>توضیحات</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($booklets)): ?>
                    <tr><td colspan="4" style="text-align: center;">هیچ جزوه‌ای تعریف نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($booklets as $booklet): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booklet['name']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($booklet['price'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($booklet['description']); ?></td>
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
