<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// Fetch all available forms
$forms = [];
$sql = "SELECT id, form_name, form_description FROM forms ORDER BY created_at DESC";
if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        $forms = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    }
}
mysqli_close($link);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>لیست فرم‌ها</h2>
    <p>در این بخش می‌توانید فرم‌های تعریف شده توسط مدیریت را مشاهده و تکمیل نمایید.</p>

    <div class="table-container">
        <h3>فرم‌های موجود</h3>
        <?php if (empty($forms)): ?>
            <p>هیچ فرمی برای تکمیل وجود ندارد.</p>
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
                                <a href="fill_form.php?form_id=<?php echo $form['id']; ?>" class="btn btn-primary">تکمیل فرم</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
