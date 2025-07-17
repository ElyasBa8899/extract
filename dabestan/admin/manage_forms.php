<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('manage_forms');

$err = $success_msg = "";

// Handle Add Form POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_form'])) {
    $form_name = trim($_POST['form_name']);
    $form_description = trim($_POST['form_description']);
    $created_by = $_SESSION['id'];

    if (empty($form_name)) {
        $err = "نام فرم نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO forms (form_name, form_description, created_by) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $form_name, $form_description, $created_by);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "فرم جدید با موفقیت ایجاد شد.";
            } else {
                $err = "خطا در ایجاد فرم.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all existing forms
$forms = [];
$sql_forms = "SELECT id, form_name, form_description, created_at FROM forms ORDER BY created_at DESC";
if($result = mysqli_query($link, $sql_forms)){
    $forms = mysqli_fetch_all($result, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت فرم‌ها</h2>
    <p>در این بخش، فرم‌های پویا برای جمع‌آوری اطلاعات را ایجاد و مدیریت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ایجاد فرم جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="form_name">نام فرم</label>
                <input type="text" name="form_name" id="form_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="form_description">توضیحات فرم</label>
                <input type="text" name="form_description" id="form_description" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_form" class="btn btn-primary" value="ایجاد فرم">
            </div>
        </form>
    </div>

    <div class="table-container">
        <h3>لیست فرم‌ها</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام فرم</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($forms)): ?>
                        <tr><td colspan="3">هیچ فرمی یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($form['form_name']); ?></td>
                                <td><?php echo htmlspecialchars($form['form_description']); ?></td>
                                <td>
                                    <a href="design_form.php?form_id=<?php echo $form['id']; ?>" class="btn btn-info btn-sm">طراحی فیلدها</a>
                                    <a href="view_submissions.php?form_id=<?php echo $form['id']; ?>" class="btn btn-success btn-sm">مشاهده پاسخ‌ها</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
