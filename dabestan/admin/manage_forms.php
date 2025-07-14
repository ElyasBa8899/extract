<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_form'])) {
    $form_name = trim($_POST['form_name']);
    $form_description = trim($_POST['form_description']);

    if (empty($form_name)) {
        $err = "نام فرم نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO forms (form_name, form_description, created_by) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $form_name, $form_description, $_SESSION['id']);
            if (mysqli_stmt_execute($stmt)) {
                $new_form_id = mysqli_insert_id($stmt);
                header("location: design_form.php?form_id=" . $new_form_id);
                exit;
            } else {
                $err = "خطا در ایجاد فرم.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all existing forms
$forms = [];
$sql = "SELECT id, form_name, form_description FROM forms ORDER BY created_at DESC";
if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        $forms = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    }
}
// mysqli_close($link);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت فرم‌ها</h2>
    <p>در این بخش می‌توانید فرم‌های جدیدی برای سامانه تعریف کنید (مانند فرم خوداظهاری، فرم بازدید و...).</p>

    <!-- Create New Form Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ایجاد فرم جدید</h3>
        <?php
        if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="form_name">نام فرم</label>
                <input type="text" name="form_name" id="form_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="form_description">توضیحات فرم</label>
                <textarea name="form_description" id="form_description" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" name="create_form" class="btn btn-primary" value="ایجاد و طراحی فرم">
            </div>
        </form>
    </div>

    <!-- List of Existing Forms -->
    <div class="table-container">
        <h3>فرم‌های موجود</h3>
        <?php if (empty($forms)): ?>
            <p>هیچ فرمی تاکنون ایجاد نشده است.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>نام فرم</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($form['form_name']); ?></td>
                            <td><?php echo htmlspecialchars($form['form_description']); ?></td>
                            <td>
                                <a href="design_form.php?form_id=<?php echo $form['id']; ?>" class="btn btn-secondary">طراحی</a>
                                <a href="view_submissions.php?form_id=<?php echo $form['id']; ?>" class="btn btn-info">مشاهده پاسخ‌ها</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
