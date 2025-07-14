<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch all notifications for the user
$notifications_query = mysqli_query($link, "SELECT * FROM notifications WHERE user_id = {$user_id} ORDER BY created_at DESC");
$notifications = mysqli_fetch_all($notifications_query, MYSQLI_ASSOC);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>تمام اعلان‌ها</h2>
    <p>در اینجا می‌توانید تاریخچه تمام اعلان‌های خود را مشاهده کنید.</p>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>پیام</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="3">هیچ اعلانی برای نمایش وجود ندارد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <tr class="<?php echo $notif['is_read'] ? 'notification-read' : 'notification-unread'; ?>">
                            <td>
                                <?php if (!empty($notif['link'])): ?>
                                    <a href="/dabestan/<?php echo htmlspecialchars($notif['link']); ?>">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(time_ago($notif['created_at'])); ?></td>
                            <td><?php echo $notif['is_read'] ? 'خوانده شده' : 'جدید'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .notification-unread { font-weight: bold; }
    .notification-read { color: var(--text-muted); }
</style>

<?php
// mysqli_close($link);
require_once "../includes/footer.php";
?>
