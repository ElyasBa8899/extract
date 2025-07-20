<?php
session_start();
require_once __DIR__ . '/../core/functions.php';
$link = get_db_connection();
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch all self-assessment submissions by the current user
$submissions = [];
$sql = "SELECT s.id, s.submitted_at, c.class_name
        FROM form_submissions s
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.form_id = 1 AND s.user_id = ?
        ORDER BY s.submitted_at DESC";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $submissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>تاریخچه فرم‌های خوداظهاری من</h2>
    <p>در این بخش می‌توانید لیست فرم‌های خوداظهاری که تاکنون پر کرده‌اید را مشاهده کنید.</p>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>کلاس</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="3" style="text-align: center;">شما تاکنون فرم خوداظهاری پر نکرده‌اید.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['class_name']); ?></td>
                            <td><?php echo to_persian_date($submission['submitted_at']); ?></td>
                            <td>
                                <a href="../admin/view_submission_details.php?id=<?php echo $submission['id']; ?>" class="btn btn-info btn-sm">مشاهده جزئیات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
