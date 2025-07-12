<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Handle Add Region POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_region'])) {
    $region_name = trim($_POST['region_name']);

    if (empty($region_name)) {
        $err = "نام منطقه نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO regions (name, created_by) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $region_name, $_SESSION['id']);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "منطقه جدید با موفقیت اضافه شد.";
            } else {
                $err = "خطا در افزودن منطقه.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle Delete Region Request
if (isset($_GET['delete_region'])) {
    $region_to_delete = $_GET['delete_region'];
    // We should add a check here to ensure no students are assigned to this region before deleting.
    // For now, we'll just delete it.
    $sql = "DELETE FROM regions WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $region_to_delete);
        if(mysqli_stmt_execute($stmt)){
            $success_msg = "منطقه با موفقیت حذف شد.";
        } else {
            $err = "خطا در حذف منطقه. ممکن است دانش‌آموزانی به این منطقه تخصیص داده شده باشند.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all existing regions
$regions = [];
$sql = "SELECT id, name FROM regions ORDER BY name ASC";
if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        $regions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    mysqli_free_result($result);
}
mysqli_close($link);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت مناطق</h2>
    <p>در این بخش می‌توانید مناطق جغرافیایی را برای دسته‌بندی دانش‌آموزان جذب شده تعریف کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Region Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن منطقه جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="region_name">نام منطقه</label>
                <input type="text" name="region_name" id="region_name" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" name="add_region" class="btn btn-primary" value="افزودن منطقه">
            </div>
        </form>
    </div>

    <!-- List of Existing Regions -->
    <div class="table-container">
        <h3>مناطق موجود</h3>
        <?php if (empty($regions)): ?>
            <p>هیچ منطقه‌ای تاکنون تعریف نشده است.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>نام منطقه</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regions as $region): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($region['name']); ?></td>
                            <td>
                                <a href="manage_regions.php?delete_region=<?php echo $region['id']; ?>" class="btn btn-danger" onclick="return confirm('آیا از حذف این منطقه مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
