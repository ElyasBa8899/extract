<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION['is_admin']) {
    header("location: ../index.php");
    exit;
}

// Fetch all classes
$classes = [];
$sql_classes = "SELECT id, class_name FROM classes ORDER BY class_name ASC";
if($result = mysqli_query($link, $sql_classes)){
    $classes = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Fetch submissions if a class is selected
$submissions = [];
$selected_class_id = null;
if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
    $selected_class_id = $_GET['class_id'];
    $sql_submissions = "SELECT s.id, s.submitted_at, u.username
                        FROM form_submissions s
                        JOIN users u ON s.user_id = u.id
                        WHERE s.form_id = 1 AND s.class_id = ?
                        ORDER BY s.submitted_at DESC";
    if($stmt = mysqli_prepare($link, $sql_submissions)){
        mysqli_stmt_bind_param($stmt, "i", $selected_class_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $submissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>

<div class="page-content">
    <h2>مشاهده و تحلیل فرم‌های خوداظهاری</h2>
    <p>برای مشاهده فرم‌های ثبت شده، ابتدا یک کلاس را انتخاب کنید.</p>

    <div class="form-container" style="margin-bottom: 30px;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
            <div class="form-group">
                <label for="class_id">انتخاب کلاس:</label>
                <select name="class_id" id="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- یک کلاس را انتخاب کنید --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php if($selected_class_id == $class['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selected_class_id): ?>
    <div class="table-container">
        <h3>فرم‌های ثبت شده برای کلاس انتخاب شده</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ثبت شده توسط</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="3" style="text-align: center;">هیچ فرمی برای این کلاس ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['username']); ?></td>
                            <td><?php echo to_persian_date($submission['submitted_at']); ?></td>
                            <td>
                                <a href="view_submission_details.php?id=<?php echo $submission['id']; ?>" class="btn btn-info btn-sm">مشاهده جزئیات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php
// mysqli_close($link);
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php";
?>
