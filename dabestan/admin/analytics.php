<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: view_analytics
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();

// Example: Get average score for a specific numeric field in a form
$form_id_for_analysis = 1; // Example form ID
$field_id_for_analysis = 1; // Example numeric field ID

$avg_score = null;
try {
    $stmt = $pdo->prepare("
        SELECT AVG(CAST(field_value AS REAL)) as average_score
        FROM form_submission_data
        WHERE field_id = ?
    ");
    $stmt->execute([$field_id_for_analysis]);
    $result = $stmt->fetch();
    if ($result) {
        $avg_score = $result['average_score'];
    }
} catch (PDOException $e) {
    // Handle error
}

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>تحلیل و آنالیز داده‌ها</h2>
        <p>گزارشات آماری و نموداری از داده‌های ثبت شده در فرم‌ها.</p>

        <div class="alert alert-info">
            <strong>توجه:</strong> این بخش در حال توسعه است. در آینده نمودارهای تحلیلی و قابلیت‌های مقایسه‌ای پیشرفته به این صفحه اضافه خواهد شد.
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="widget">
                    <div class="widget-header">
                        <h5>مثال: میانگین نمرات یک فیلد خاص</h5>
                    </div>
                    <div class="widget-body">
                        <?php if ($avg_score !== null): ?>
                            <p>میانگین امتیازات ثبت شده برای فیلد نمونه (ID: <?php echo $field_id_for_analysis; ?>) در فرم نمونه (ID: <?php echo $form_id_for_analysis; ?>) برابر است با:</p>
                            <div class="balance" style="font-size: 2rem;"><?php echo round($avg_score, 2); ?></div>
                        <?php else: ?>
                            <p>داده‌ای برای تحلیل یافت نشد.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
