<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: view_all_submissions
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$submission_id = $_GET['id'] ?? null;

if (!$submission_id) {
    die("شناسه پاسخ مشخص نشده است.");
}

// Fetch submission details
$stmt = $pdo->prepare("
    SELECT s.submitted_at, f.title as form_title, u.full_name as user_name
    FROM form_submissions s
    JOIN forms f ON s.form_id = f.id
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$submission_id]);
$submission_info = $stmt->fetch();

if (!$submission_info) {
    die("پاسخی با این شناسه یافت نشد.");
}

// Fetch the actual submitted data
$data_stmt = $pdo->prepare("
    SELECT sd.field_value, ff.label, ff.field_type
    FROM form_submission_data sd
    JOIN form_fields ff ON sd.field_id = ff.id
    WHERE sd.submission_id = ?
    ORDER BY ff.id
");
$data_stmt->execute([$submission_id]);
$submitted_data = $data_stmt->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>جزئیات پاسخ فرم: <?php echo htmlspecialchars($submission_info['form_title']); ?></h2>
        <p>
            <strong>ثبت شده توسط:</strong> <?php echo htmlspecialchars($submission_info['user_name']); ?> |
            <strong>در تاریخ:</strong> <?php echo to_persian_date($submission_info['submitted_at']); ?>
        </p>

        <div class="card">
            <div class="card-header"><h5>پاسخ‌ها</h5></div>
            <div class="card-body">
                <dl class="row">
                    <?php foreach ($submitted_data as $data): ?>
                        <dt class="col-sm-3 border-bottom pb-2 mb-2"><?php echo htmlspecialchars($data['label']); ?></dt>
                        <dd class="col-sm-9 border-bottom pb-2 mb-2">
                            <?php
                                // Check if the value is a JSON array (for checkboxes)
                                $decoded_value = json_decode($data['field_value'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_value)) {
                                    echo htmlspecialchars(implode('، ', $decoded_value));
                                } else {
                                    echo nl2br(htmlspecialchars($data['field_value']));
                                }
                            ?>
                        </dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>

        <a href="javascript:history.back()" class="btn btn-secondary mt-3">بازگشت</a>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
