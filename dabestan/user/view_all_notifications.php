<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$success_msg = $err_msg = "";

// Handle marking notification as read/unread or deleting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $notification_id = $_POST['notification_id'];
    $action = $_POST['action'];

    // Security check: Ensure the notification belongs to the current user
    $check_q = mysqli_prepare($link, "SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($check_q, "ii", $notification_id, $user_id);
    mysqli_stmt_execute($check_q);
    $result_check = mysqli_stmt_get_result($check_q);

    if (mysqli_num_rows($result_check) > 0) {
        if ($action === 'toggle_read') {
            $is_read = $_POST['is_read'] == '1' ? 0 : 1; // Toggle current status
            $sql = "UPDATE notifications SET is_read = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $is_read, $notification_id);
            mysqli_stmt_execute($stmt);
        } elseif ($action === 'delete') {
            $sql = "DELETE FROM notifications WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "i", $notification_id);
            mysqli_stmt_execute($stmt);
            $success_msg = "اعلان با موفقیت حذف شد.";
        }
    } else {
        $err_msg = "دسترسی غیرمجاز یا اعلان یافت نشد.";
    }
}


// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $success_msg = "تمام اعلان‌ها به عنوان خوانده شده علامت‌گذاری شدند.";
}


// Fetch all notifications for the user
$sql_all = "SELECT id, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt_all = mysqli_prepare($link, $sql_all);
mysqli_stmt_bind_param($stmt_all, "i", $user_id);
mysqli_stmt_execute($stmt_all);
$result_all = mysqli_stmt_get_result($stmt_all);
$notifications = mysqli_fetch_all($result_all, MYSQLI_ASSOC);

require_once "../includes/header.php";
?>
<style>
    .notification-list-item {
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.3s;
    }
    .notification-list-item.is-read {
        background-color: #f8f9fa;
        opacity: 0.7;
    }
    .notification-content a {
        color: #0056b3;
        text-decoration: none;
        font-weight: bold;
    }
    .notification-content small {
        display: block;
        color: #6c757d;
        margin-top: 5px;
    }
    .notification-actions form {
        display: inline-block;
    }
</style>

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>همه اعلان‌ها</h2>
        <a href="?mark_all_read=1" class="btn btn-secondary">علامت‌گذاری همه به عنوان خوانده شده</a>
    </div>

    <?php
    if(!empty($err_msg)){ echo '<div class="alert alert-danger">' . $err_msg . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="notifications-container">
        <?php if (empty($notifications)): ?>
            <p class="text-center">هیچ اعلانی برای نمایش وجود ندارد.</p>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-list-item <?php echo $notif['is_read'] ? 'is-read' : ''; ?>">
                    <div class="notification-content">
                        <a href="<?php echo $notif['link'] ? '/dabestan' . htmlspecialchars($notif['link']) : '#'; ?>">
                            <?php echo htmlspecialchars($notif['message']); ?>
                        </a>
                        <small><?php echo to_persian_date($notif['created_at']); ?></small>
                    </div>
                    <div class="notification-actions">
                        <form method="post">
                            <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                            <input type="hidden" name="is_read" value="<?php echo $notif['is_read']; ?>">
                            <button type="submit" name="action" value="toggle_read" class="btn btn-sm <?php echo $notif['is_read'] ? 'btn-info' : 'btn-success'; ?>">
                                <?php echo $notif['is_read'] ? 'خوانده نشده' : 'خوانده شده'; ?>
                            </button>
                        </form>
                        <form method="post" onsubmit="return confirm('آیا از حذف این اعلان مطمئن هستید؟');">
                            <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                            <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm">حذف</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
