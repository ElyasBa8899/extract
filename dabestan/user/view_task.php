<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: my_tasks.php");
    exit;
}

$task_id = $_GET['id'];
$user_id = $_SESSION['id'];

// Handle Comment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $sql = "INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iis", $task_id, $user_id, $comment);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("location: view_task.php?id=" . $task_id);
            exit;
        }
    }
}

// Handle Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    // Add validation for status
    $sql = "UPDATE tasks SET status = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $new_status, $task_id);
        mysqli_stmt_execute($stmt);
        // Add to history
        $status_translation = ['pending' => 'در انتظار', 'in_progress' => 'در حال انجام', 'completed' => 'تکمیل شده', 'cancelled' => 'لغو شده'];
        $action = "وضعیت وظیفه را به '" . ($status_translation[$new_status] ?? htmlspecialchars($new_status)) . "' تغییر داد.";
        $history_sql = "INSERT INTO task_history (task_id, user_id, action) VALUES (?, ?, ?)";
        $stmt_history = mysqli_prepare($link, $history_sql);
        mysqli_stmt_bind_param($stmt_history, "iis", $task_id, $user_id, $action);
        mysqli_stmt_execute($stmt_history);
        mysqli_stmt_close($stmt_history);
        header("location: view_task.php?id=" . $task_id);
        exit;
    }
}

require_once "../includes/header.php";

// Fetch task details first
$sql_task = "SELECT t.*, u.username as creator_name FROM tasks t JOIN users u ON t.created_by = u.id WHERE t.id = ?";
$stmt_task = mysqli_prepare($link, $sql_task);
mysqli_stmt_bind_param($stmt_task, "i", $task_id);
mysqli_stmt_execute($stmt_task);
$result_task = mysqli_stmt_get_result($stmt_task);
$task = mysqli_fetch_assoc($result_task);
mysqli_stmt_close($stmt_task);

if (!$task) {
    header("location: my_tasks.php"); // Or show an error message
    exit;
}

// Handle Reassignment Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reassignment'])) {
    $reassign_type = $_POST['reassign_type'];
    $new_user_id = ($reassign_type == 'user') ? $_POST['reassign_user_id'] : null;
    $new_department_id = ($reassign_type == 'department') ? $_POST['reassign_department_id'] : null;
    $reassign_comment = trim($_POST['reassign_comment']);
    $creator_id = $task['created_by'];

    $sql = "INSERT INTO task_reassignment_requests (task_id, requested_by_id, requested_to_id, new_user_id, new_department_id, comment) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iiisss", $task_id, $user_id, $creator_id, $new_user_id, $new_department_id, $reassign_comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Notify the creator
        $message = "کاربر " . htmlspecialchars($_SESSION['username']) . " درخواست محول کردن وظیفه '" . htmlspecialchars($task['title']) . "' را دارد.";
        send_notification($creator_id, 'reassignment_request', $task_id, $message, "user/view_task.php?id=" . $task_id);

        header("location: view_task.php?id=" . $task_id . "&reassign_req=sent");
        exit;
    }
}

// Handle Reassignment Approval/Rejection
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['reassign_action']) && isset($_GET['req_id'])) {
    $request_id = $_GET['req_id'];
    $action = $_GET['reassign_action']; // 'approve' or 'reject'

    // Fetch request details
    $sql_req = "SELECT * FROM task_reassignment_requests WHERE id = ? AND requested_to_id = ?";
    $stmt_req = mysqli_prepare($link, $sql_req);
    mysqli_stmt_bind_param($stmt_req, "ii", $request_id, $user_id);
    mysqli_stmt_execute($stmt_req);
    $request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_req));
    mysqli_stmt_close($stmt_req);

    if ($request && $request['status'] == 'pending') {
        if ($action == 'approve') {
            // 1. Update task assignment
            $sql_update = "UPDATE task_assignments SET assigned_to_user_id = ? WHERE task_id = ?";
            $stmt_update = mysqli_prepare($link, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ii", $request['new_user_id'], $request['task_id']);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            // 2. Update request status
            $sql_status = "UPDATE task_reassignment_requests SET status = 'approved' WHERE id = ?";
            $stmt_status = mysqli_prepare($link, $sql_status);
            mysqli_stmt_bind_param($stmt_status, "i", $request_id);
            mysqli_stmt_execute($stmt_status);
            mysqli_stmt_close($stmt_status);

            // 3. Add to history
            $new_user_info = get_user_info($request['new_user_id']);
            $history_action = "وظیفه را به " . htmlspecialchars($new_user_info['username']) . " محول کرد.";
            $history_sql = "INSERT INTO task_history (task_id, user_id, action) VALUES (?, ?, ?)";
            $stmt_history = mysqli_prepare($link, $history_sql);
            mysqli_stmt_bind_param($stmt_history, "iis", $request['task_id'], $user_id, $history_action);
            mysqli_stmt_execute($stmt_history);

            // 4. Notify original requester
            $message_requester = "درخواست شما برای محول کردن وظیفه '" . htmlspecialchars($task['title']) . "' تایید شد.";
            send_notification($request['requested_by_id'], 'reassignment_approved', $request['task_id'], $message_requester);

            // 5. Notify new user
            $message_new_user = "وظیفه جدیدی با عنوان '" . htmlspecialchars($task['title']) . "' به شما محول شد.";
            send_notification($request['new_user_id'], 'new_task_assigned', $request['task_id'], $message_new_user);

        } else { // Reject
            // 1. Update request status
            $sql_status = "UPDATE task_reassignment_requests SET status = 'rejected' WHERE id = ?";
            $stmt_status = mysqli_prepare($link, $sql_status);
            mysqli_stmt_bind_param($stmt_status, "i", $request_id);
            mysqli_stmt_execute($stmt_status);
            mysqli_stmt_close($stmt_status);

            // 2. Notify original requester
            $message = "درخواست شما برای محول کردن وظیفه '" . htmlspecialchars($task['title']) . "' رد شد.";
            send_notification($request['requested_by_id'], 'reassignment_rejected', $request['task_id'], $message);
        }
        header("location: view_task.php?id=" . $task_id);
        exit;
    }
}



// Fetch comments
$sql_comments = "SELECT tc.*, u.username FROM task_comments tc JOIN users u ON tc.user_id = u.id WHERE tc.task_id = ? ORDER BY tc.created_at ASC";
$stmt_comments = mysqli_prepare($link, $sql_comments);
mysqli_stmt_bind_param($stmt_comments, "i", $task_id);
mysqli_stmt_execute($stmt_comments);
$result_comments = mysqli_stmt_get_result($stmt_comments);
$comments = mysqli_fetch_all($result_comments, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_comments);

// Fetch history
$sql_history = "SELECT th.*, u.username FROM task_history th JOIN users u ON th.user_id = u.id WHERE th.task_id = ? ORDER BY th.created_at ASC";
$stmt_history = mysqli_prepare($link, $sql_history);
mysqli_stmt_bind_param($stmt_history, "i", $task_id);
mysqli_stmt_execute($stmt_history);
$result_history = mysqli_stmt_get_result($stmt_history);
$history = mysqli_fetch_all($result_history, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_history);



?>

<div class="page-content">
    <div class="container-fluid">

        <?php
        if (isset($_GET['reassign_req']) && $_GET['reassign_req'] == 'sent') {
            set_alert('success', 'درخواست شما برای محول کردن وظیفه با موفقیت ارسال شد.');
            header("Location: view_task.php?id=" . $task_id);
            exit;
        }
        ?>

        <div class="task-view-header">
            <div class="task-title">
                <h2><?php echo htmlspecialchars($task['title']); ?></h2>
                <div class="task-meta">
                    ایجاد شده توسط <?php echo htmlspecialchars($task['creator_name']); ?> در <?php echo to_persian_date($task['created_at']); ?>
                </div>
            </div>
            <div class="task-actions">
                <a href="my_tasks.php" class="btn btn-secondary">بازگشت به لیست</a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i data-feather="file-text"></i>توضیحات وظیفه</h5>
                    </div>
                    <div class="widget-body">
                        <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    </div>
                </div>

                <div class="widget mt-4">
                    <div class="widget-header">
                        <h5><i data-feather="message-square"></i>نظرات</h5>
                    </div>
                    <div class="widget-body">
                        <div class="comments-section">
                            <?php if (empty($comments)): ?>
                                <p class="text-muted text-center">هنوز نظری ثبت نشده است.</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment">
                                        <div class="comment-avatar">
                                            <i data-feather="user"></i>
                                        </div>
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                                <span class="text-muted"><?php echo time_ago($comment['created_at']); ?></span>
                                            </div>
                                            <div class="comment-body">
                                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <form action="" method="post" class="comment-form">
                            <div class="form-group">
                                <textarea name="comment" id="comment" class="form-control" rows="3" placeholder="نظر خود را بنویسید..."></textarea>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary"><i data-feather="send"></i> ارسال نظر</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i data-feather="info"></i>جزئیات وظیفه</h5>
                    </div>
                    <div class="widget-body">
                        <ul class="task-details-list">
                            <li>
                                <span>وضعیت:</span>
                                <?php echo get_status_badge_view($task['status']); ?>
                            </li>
                            <li>
                                <span>اولویت:</span>
                                <?php echo get_priority_badge_view($task['priority']); ?>
                            </li>
                            <li>
                                <span>مهلت انجام:</span>
                                <strong><?php echo (!empty($task['deadline']) && $task['deadline'] != '0000-00-00 00:00:00') ? to_persian_date($task['deadline']) : 'ندارد'; ?></strong>
                            </li>
                        </ul>
                    </div>
                    <?php if (can_edit_task($task_id, $user_id)): ?>
                    <div class="widget-footer">
                        <form action="" method="post" id="update-status-form" class="d-inline">
                            <select name="new_status" class="form-control-sm">
                                <option value="in_progress" <?php if($task['status'] == 'in_progress') echo 'selected'; ?>>در حال انجام</option>
                                <option value="completed" <?php if($task['status'] == 'completed') echo 'selected'; ?>>تکمیل شده</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-success">تغییر وضعیت</button>
                        </form>
                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#reassignRequestModal">
                            درخواست محول کردن
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="widget mt-4">
                    <div class="widget-header">
                        <h5><i data-feather="activity"></i>تاریخچه</h5>
                    </div>
                    <div class="widget-body">
                        <ul class="history-list">
                             <?php if (empty($history)): ?>
                                <p class="text-muted text-center">تاریخچه‌ای برای نمایش وجود ندارد.</p>
                            <?php else: ?>
                                <?php foreach ($history as $item): ?>
                                    <li>
                                        <div class="history-icon"><i data-feather="git-commit"></i></div>
                                        <div class="history-content">
                                            <span><strong><?php echo htmlspecialchars($item['username']); ?></strong> <?php echo htmlspecialchars($item['action']); ?></span>
                                            <span class="text-muted"><?php echo time_ago($item['created_at']); ?></span>
                                             <?php if (!empty($item['details'])): ?>
                                                <div class="history-details"><?php echo htmlspecialchars($item['details']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.task-view-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.task-title h2 { margin-bottom: 0.25rem; }
.task-meta { color: var(--text-muted); font-size: 0.9em; }

.widget {
    background: var(--widget-bg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.5rem;
}
.widget-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
}
.widget-header h5 { margin: 0; font-size: 1.1em; }
.widget-body { padding: 1.5rem; }

.comments-section { max-height: 400px; overflow-y: auto; padding: 0.5rem; }
.comment { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--background-color);
    display: flex;
    align-items: center;
    justify-content: center;
}
.comment-content { flex: 1; }
.comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; }
.comment-header strong { font-weight: 600; }
.comment-header .text-muted { font-size: 0.8em; }
.comment-body {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    border-radius: var(--radius-md);
}
.comment-form .form-group { margin-bottom: 1rem; }
.comment-form button { display: flex; align-items: center; gap: 0.5rem; }

.task-details-list { list-style: none; padding: 0; margin: 0; }
.task-details-list li {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}
.task-details-list li:last-child { border-bottom: none; }
.task-details-list li span:first-child { color: var(--text-muted); }

.history-list { list-style: none; padding: 0; margin: 0; }
.history-list li {
    display: flex;
    gap: 1rem;
    position: relative;
    padding-bottom: 1.5rem;
}
.history-list li:last-child { padding-bottom: 0; }
.history-list li:not(:last-child)::before {
    content: '';
    position: absolute;
    top: 18px;
    right: 18px;
    width: 2px;
    height: calc(100% - 18px);
    background: var(--border-color);
}
.history-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--background-color);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}
.history-content { flex: 1; }
.history-content span { display: block; }
.history-content .text-muted { font-size: 0.8em; margin-top: 0.25rem; }
.history-details {
    font-size: 0.85em;
    color: #6c757d;
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: var(--radius-md);
    margin-top: 0.5rem;
}
.widget-footer {
    padding: 1rem 1.5rem;
    background-color: #f8f9fa;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
    align-items: center;
}
</style>

<!-- Reassign Request Modal -->
<div class="modal fade" id="reassignRequestModal" tabindex="-1" role="dialog" aria-labelledby="reassignRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="reassignRequestModalLabel">درخواست محول کردن وظیفه</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <p>شما در حال ارسال یک درخواست به سازنده وظیفه (<?php echo htmlspecialchars($task['creator_name']); ?>) برای محول کردن این وظیفه هستید.</p>
            <div class="form-group">
                <label for="reassign_type">محول به:</label>
                <select name="reassign_type" id="reassign_type" class="form-control">
                    <option value="user">کاربر</option>
                    <option value="department">بخش</option>
                </select>
            </div>
            <div class="form-group" id="user_reassign_group">
                <label for="reassign_user_id">کاربر پیشنهادی</label>
                <select name="reassign_user_id" id="reassign_user_id" class="form-control">
                    <?php
                    $users_sql = "SELECT id, username FROM users WHERE id != ?";
                    if($stmt_users = mysqli_prepare($link, $users_sql)){
                        mysqli_stmt_bind_param($stmt_users, "i", $user_id);
                        mysqli_stmt_execute($stmt_users);
                        $users_result = mysqli_stmt_get_result($stmt_users);
                        while($user = mysqli_fetch_assoc($users_result)){
                            echo "<option value='{$user['id']}'>".htmlspecialchars($user['username'])."</option>";
                        }
                        mysqli_stmt_close($stmt_users);
                    }
                    ?>
                </select>
            </div>
            <div class="form-group" id="department_reassign_group" style="display: none;">
                <label for="reassign_department_id">بخش پیشنهادی</label>
                <select name="reassign_department_id" id="reassign_department_id" class="form-control">
                    <?php
                    $depts_sql = "SELECT id, department_name FROM departments";
                    $depts_result = mysqli_query($link, $depts_sql);
                    while($dept = mysqli_fetch_assoc($depts_result)){
                        echo "<option value='{$dept['id']}'>".htmlspecialchars($dept['department_name'])."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reassign_comment">دلیل و توضیحات</label>
                <textarea name="reassign_comment" id="reassign_comment" class="form-control" rows="3" required></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
          <button type="submit" name="request_reassignment" class="btn btn-primary">ارسال درخواست</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once "../includes/footer.php";
?>
