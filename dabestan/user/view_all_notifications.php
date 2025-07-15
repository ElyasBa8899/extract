<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Handle Mark as Read
if (isset($_GET['mark_read'])) {
    $notif_id = $_GET['mark_read'];
    $sql_mark = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    if($stmt_mark = mysqli_prepare($link, $sql_mark)){
        mysqli_stmt_bind_param($stmt_mark, "ii", $notif_id, $user_id);
        mysqli_stmt_execute($stmt_mark);
        mysqli_stmt_close($stmt_mark);
        header("location: view_all_notifications.php");
        exit;
    }
}

// Handle Delete Notification
if (isset($_GET['delete_notif'])) {
    $notif_id = $_GET['delete_notif'];
    $sql_delete = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
     if($stmt_delete = mysqli_prepare($link, $sql_delete)){
        mysqli_stmt_bind_param($stmt_delete, "ii", $notif_id, $user_id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        header("location: view_all_notifications.php");
        exit;
    }
}


// Fetch all notifications for the user
$notifications_query = mysqli_query($link, "SELECT * FROM notifications WHERE user_id = {$user_id} ORDER BY is_read ASC, created_at DESC");
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
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">هیچ اعلانی برای نمایش وجود ندارد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <tr class="<?php echo $notif['is_read'] ? 'notification-read' : 'notification-unread'; ?>">
                            <td>
                                <?php if (!empty($notif['link'])): ?>
                                    <a href="/dabestan/<?php echo ltrim(htmlspecialchars($notif['link']), '/'); ?>">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(time_ago($notif['created_at'])); ?></td>
                            <td>
                                <?php if (!$notif['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notif['id']; ?>" class="btn btn-primary btn-sm">خوانده شد</a>
                                <?php else: ?>
                                    <span class="btn btn-success btn-sm disabled">خوانده شده</span>
                                <?php endif; ?>
                                <a href="?delete_notif=<?php echo $notif['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این اعلان مطمئن هستید؟')">حذف</a>
                            </td>
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
// mysqli_close($link); // Singleton handles connection closing
require_once "../includes/footer.php";
?>
