<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!isset($_SESSION['loggedin'])) {
    header("Location: ../index.php");
    exit();
}

$pdo = get_db_connection();
$user_id = $_SESSION['id'];

// Fetch tickets for the current user
$stmt = $pdo->prepare("
    SELECT * FROM tickets
    WHERE user_id = ? OR assigned_to_user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$tickets = $stmt->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2>لیست تیکت‌های من</h2>
            <a href="new_ticket.php" class="btn btn-primary">ارسال تیکت جدید</a>
        </div>
        <p>در این بخش می‌توانید لیست تیکت‌های ارسالی خود و پاسخ‌های دریافتی را مشاهده کنید.</p>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>وضعیت</th>
                                <th>اولویت</th>
                                <th>تاریخ ایجاد</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">شما تاکنون هیچ تیکتی ثبت نکرده‌اید.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                        <td><?php echo get_status_badge_view($ticket['status']); ?></td>
                                        <td><?php echo get_priority_badge_view($ticket['priority']); ?></td>
                                        <td><?php echo to_persian_date($ticket['created_at']); ?></td>
                                        <td>
                                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info">مشاهده</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
