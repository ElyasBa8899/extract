<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/access_control.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

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

// Fetch task details
$sql_task = "SELECT t.*, u.username as creator_name FROM tasks t JOIN users u ON t.created_by = u.id WHERE t.id = ?";
$stmt_task = mysqli_prepare($link, $sql_task);
mysqli_stmt_bind_param($stmt_task, "i", $task_id);
mysqli_stmt_execute($stmt_task);
$result_task = mysqli_stmt_get_result($stmt_task);
$task = mysqli_fetch_assoc($result_task);
mysqli_stmt_close($stmt_task);

if (!$task) {
    echo "<div class='alert alert-danger'>وظیفه مورد نظر یافت نشد.</div>";
    require_once "../includes/footer.php";
    exit;
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


function get_status_badge_view($status) {
    switch ($status) {
        case 'pending': return '<span class="badge badge-warning">در انتظار</span>';
        case 'in_progress': return '<span class="badge badge-info">در حال انجام</span>';
        case 'completed': return '<span class="badge badge-success">تکمیل شده</span>';
        case 'cancelled': return '<span class="badge badge-secondary">لغو شده</span>';
        default: return '';
    }
}

function get_priority_badge_view($priority) {
    switch ($priority) {
        case 'low': return '<span class="badge badge-light">کم</span>';
        case 'medium': return '<span class="badge badge-primary">متوسط</span>';
        case 'high': return '<span class="badge badge-danger">زیاد</span>';
        case 'urgent': return '<span class="badge badge-danger" style="background-color: #dc3545; color: white;">فوری</span>';
        default: return '';
    }
}

?>

<div class="page-content">
    <div class="container-fluid">
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
                <div class="card">
                    <div class="card-header">
                        توضیحات وظیفه
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        نظرات
                    </div>
                    <div class="card-body">
                        <div class="comments-section">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <div class="comment-header">
                                        <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                        <span class="text-muted"><?php echo time_ago($comment['created_at']); ?></span>
                                    </div>
                                    <div class="comment-body">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr>
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="comment">افزودن نظر</label>
                                <textarea name="comment" id="comment" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary">ارسال نظر</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        جزئیات وظیفه
                    </div>
                    <div class="card-body">
                        <p><strong>وضعیت:</strong> <?php echo get_status_badge_view($task['status']); ?></p>
                        <p><strong>اولویت:</strong> <?php echo get_priority_badge_view($task['priority']); ?></p>
                        <p><strong>مهلت انجام:</strong> <?php echo (!empty($task['deadline']) && $task['deadline'] != '0000-00-00 00:00:00') ? to_persian_date($task['deadline']) : 'ندارد'; ?></p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        تاریخچه
                    </div>
                    <div class="card-body">
                        <ul class="history-list">
                            <?php foreach ($history as $item): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($item['username']); ?></strong>
                                    <?php echo htmlspecialchars($item['action']); ?>
                                    <span class="text-muted"><?php echo time_ago($item['created_at']); ?></span>
                                    <?php if (!empty($item['details'])): ?>
                                        <div class="history-details"><?php echo htmlspecialchars($item['details']); ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.task-view-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.comments-section .comment { margin-bottom: 15px; }
.comment-header { margin-bottom: 5px; }
.history-list { list-style-type: none; padding: 0; }
.history-list li { margin-bottom: 10px; }
.history-details { font-size: 0.9em; color: #6c757d; }
</style>

<?php
require_once "../includes/footer.php";
?>
