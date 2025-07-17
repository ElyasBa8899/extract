<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/functions.php";
require_once "../includes/access_control.php";

if (!has_permission('manage_tasks')) {
    header("location: ../index.php");
    exit;
}

// Fetch all pending reassignment requests
$sql = "SELECT trr.*, t.title as task_title, u_requester.username as requester_name, u_new.username as new_user_name
        FROM task_reassignment_requests trr
        JOIN tasks t ON trr.task_id = t.id
        JOIN users u_requester ON trr.requested_by_id = u_requester.id
        JOIN users u_new ON trr.new_user_id = u_new.id
        WHERE trr.status = 'pending' AND trr.requested_to_id = ?
        ORDER BY trr.created_at DESC";

$requests = [];
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت درخواست‌های محول کردن وظیفه</h2>
        <p>در این بخش می‌توانید درخواست‌های ارسال شده برای محول کردن وظایف را تایید یا رد کنید.</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>وظیفه</th>
                        <th>درخواست‌دهنده</th>
                        <th>کاربر پیشنهادی</th>
                        <th>دلیل</th>
                        <th>تاریخ درخواست</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center">در حال حاضر هیچ درخواست جدیدی وجود ندارد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><a href="../user/view_task.php?id=<?php echo $req['task_id']; ?>"><?php echo htmlspecialchars($req['task_title']); ?></a></td>
                                <td><?php echo htmlspecialchars($req['requester_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['new_user_name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($req['comment'])); ?></td>
                                <td><?php echo to_persian_date($req['created_at']); ?></td>
                                <td>
                                    <a href="../user/view_task.php?id=<?php echo $req['task_id']; ?>&reassign_action=approve&req_id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('آیا از تایید این درخواست اطمینان دارید؟');">تایید</a>
                                    <a href="../user/view_task.php?id=<?php echo $req['task_id']; ?>&reassign_action=reject&req_id=<?php echo $req['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا از رد این درخواست اطمینان دارید؟');">رد</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
