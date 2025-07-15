<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
// In the future, we would add role-based access control here
// to ensure only users from the "Parvareshi" department can see this.

// We are assuming the ID of the "گزارش برنامه مناسبتی" form is 3.
// This should be made dynamic in a real-world scenario.
const EVENT_REPORT_FORM_ID = 3;

$err = "";
$submissions = [];

// Fetch all submissions for the specific form ID
$sql = "SELECT s.id, s.submitted_at, u.username as submitter_username, c.class_name
        FROM form_submissions s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.form_id = ?
        ORDER BY s.submitted_at DESC";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $form_id);
    $form_id = EVENT_REPORT_FORM_ID;

    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $submissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $err = "خطا در واکشی گزارش‌ها.";
    }
    mysqli_stmt_close($stmt);
} else {
    $err = "خطا در آماده‌سازی کوئری.";
}

mysqli_close($link);
require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>گزارش‌های خدمت‌گزاری کلاس‌ها در مناسبت‌ها</h2>
    <p>در این بخش گزارش‌های ثبت شده توسط مدرسین برای مناسبت‌های مختلف را مشاهده و پیگیری کنید.</p>

    <?php if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; } ?>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ثبت شده توسط</th>
                    <th>برای کلاس</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">
                            هیچ گزارشی برای فرم "گزارش برنامه مناسبتی" (با ID=<?php echo EVENT_REPORT_FORM_ID; ?>) ثبت نشده است.
                            <br>
                            <small>مطمئن شوید که فرم مورد نظر در بخش مدیریت فرم‌ها با همین ID ایجاد شده باشد.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['submitter_username']); ?></td>
                            <td><?php echo htmlspecialchars($submission['class_name'] ?? '<i>نامشخص</i>'); ?></td>
                            <td><?php echo htmlspecialchars($submission['submitted_at']); ?></td>
                            <td>
                                <a href="../admin/view_submission_details.php?submission_id=<?php echo $submission['id']; ?>" class="btn btn-primary btn-sm">مشاهده جزئیات گزارش</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
