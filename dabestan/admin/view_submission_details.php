<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['submission_id']) || empty($_GET['submission_id'])) {
    header("location: manage_forms.php");
    exit;
}

$submission_id = $_GET['submission_id'];

// Fetch submission details (who, when, which form)
$submission_info = null;
$sql_info = "SELECT s.id, s.submitted_at, u.username, f.id as form_id, f.form_name
             FROM form_submissions s
             JOIN users u ON s.user_id = u.id
             JOIN forms f ON s.form_id = f.id
             WHERE s.id = ?";
if($stmt_info = mysqli_prepare($link, $sql_info)){
    mysqli_stmt_bind_param($stmt_info, "i", $submission_id);
    mysqli_stmt_execute($stmt_info);
    $result_info = mysqli_stmt_get_result($stmt_info);
    $submission_info = mysqli_fetch_assoc($result_info);
    mysqli_stmt_close($stmt_info);
}

if(!$submission_info){
    echo "پاسخ مورد نظر یافت نشد.";
    exit;
}

// Fetch the actual data for this submission
$submission_data = [];
$sql_data = "SELECT d.field_value, f.field_label
             FROM form_submission_data d
             JOIN form_fields f ON d.field_id = f.id
             WHERE d.submission_id = ?
             ORDER BY f.field_order ASC";
if($stmt_data = mysqli_prepare($link, $sql_data)){
    mysqli_stmt_bind_param($stmt_data, "i", $submission_id);
    mysqli_stmt_execute($stmt_data);
    $result_data = mysqli_stmt_get_result($stmt_data);
    $submission_data = mysqli_fetch_all($result_data, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_data);
}

// mysqli_close($link);

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="view_submissions.php?form_id=<?php echo $submission_info['form_id']; ?>" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به لیست پاسخ‌ها</a>
    <h2>جزئیات پاسخ ثبت شده</h2>

    <div class="form-container">
        <p><strong>فرم:</strong> <?php echo htmlspecialchars($submission_info['form_name']); ?></p>
        <p><strong>کاربر:</strong> <?php echo htmlspecialchars($submission_info['username']); ?></p>
        <p><strong>تاریخ ثبت:</strong> <?php echo htmlspecialchars($submission_info['submitted_at']); ?></p>
    </div>

    <div class="table-container" style="margin-top: 20px;">
        <h3>سوالات و پاسخ‌ها</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>سوال (فیلد)</th>
                    <th>پاسخ ثبت شده</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submission_data)): ?>
                    <tr>
                        <td colspan="2">دیتایی برای این پاسخ ثبت نشده است.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($submission_data as $data): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($data['field_label']); ?></strong></td>
                            <td><?php echo nl2br(htmlspecialchars($data['field_value'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
