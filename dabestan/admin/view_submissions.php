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
$form_id = $_GET['form_id'] ?? null;

// Fetch all forms for the dropdown
$forms = $pdo->query("SELECT id, title FROM forms ORDER BY title")->fetchAll();

// Fetch submissions if a form is selected
$submissions = [];
if ($form_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.submitted_at, u.full_name as user_name, c.class_name
        FROM form_submissions s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.related_class_id = c.id
        WHERE s.form_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$form_id]);
    $submissions = $stmt->fetchAll();
}

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مشاهده پاسخ فرم‌ها</h2>
        <p>پاسخ‌های ثبت شده توسط کاربران برای فرم‌های مختلف را مشاهده کنید.</p>

        <div class="card">
            <div class="card-header">
                <h5>انتخاب فرم</h5>
            </div>
            <div class="card-body">
                <form action="view_submissions.php" method="get" class="form-inline">
                    <div class="form-group">
                        <select name="form_id" class="form-control" onchange="this.form.submit()">
                            <option value="">--- یک فرم را انتخاب کنید ---</option>
                            <?php foreach ($forms as $form): ?>
                                <option value="<?php echo $form['id']; ?>" <?php echo ($form_id == $form['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($form['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($form_id): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5>پاسخ‌های ثبت شده</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>کاربر</th>
                                <th>کلاس مربوطه</th>
                                <th>تاریخ ثبت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($submissions)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">هیچ پاسخی برای این فرم ثبت نشده است.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($submission['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($submission['class_name'] ?? '---'); ?></td>
                                        <td><?php echo to_persian_date($submission['submitted_at']); ?></td>
                                        <td>
                                            <a href="view_submission_details.php?id=<?php echo $submission['id']; ?>" class="btn btn-sm btn-info">مشاهده جزئیات</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
