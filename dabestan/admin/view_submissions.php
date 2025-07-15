<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['form_id']) || empty($_GET['form_id'])) {
    header("location: manage_forms.php");
    exit;
}

$form_id = $_GET['form_id'];

// Fetch form details
$form = null;
$sql_form = "SELECT form_name FROM forms WHERE id = ?";
if($stmt_form = mysqli_prepare($link, $sql_form)){
    mysqli_stmt_bind_param($stmt_form, "i", $form_id);
    mysqli_stmt_execute($stmt_form);
    $result_form = mysqli_stmt_get_result($stmt_form);
    $form = mysqli_fetch_assoc($result_form);
    mysqli_stmt_close($stmt_form);
}

if(!$form){
    echo "فرم یافت نشد.";
    exit;
}

// Fetch all submissions for this form
$submissions = [];
$sql = "SELECT s.id, s.submitted_at, u.username
        FROM form_submissions s
        JOIN users u ON s.user_id = u.id
        WHERE s.form_id = ?
        ORDER BY s.submitted_at DESC";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $form_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $submissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
mysqli_close($link);

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_forms.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت فرم‌ها</a>
    <h2>پاسخ‌های ثبت شده برای فرم: <?php echo htmlspecialchars($form['form_name']); ?></h2>

    <div class="table-container">
        <?php if (empty($submissions)): ?>
            <p>هیچ پاسخی برای این فرم تاکنون ثبت نشده است.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>کاربر ثبت کننده</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['username']); ?></td>
                            <td><?php echo htmlspecialchars($submission['submitted_at']); ?></td>
                            <td>
                                <a href="view_submission_details.php?submission_id=<?php echo $submission['id']; ?>" class="btn btn-primary">مشاهده جزئیات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
