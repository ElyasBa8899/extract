<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/access_control.php";

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

// --- Security Check: Ensure the user is assigned to this task ---
$is_assigned = is_user_assigned_to_task($link, $user_id, $task_id);
if (!$is_assigned && !has_permission('manage_tasks')) {
    echo "دسترسی غیرمجاز. شما به این وظیفه تخصیص داده نشده‌اید.";
    exit;
}
// --- End Security Check ---


// Handle POST Actions (Add Comment, Update Status)
$err = $success_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Action: Add Comment
    if (isset($_POST['add_comment']) && !empty(trim($_POST['comment']))) {
        $comment = trim($_POST['comment']);
        $sql = "INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iis", $task_id, $user_id, $comment);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            record_task_history($link, $task_id, $user_id, "افزودن نظر", $comment);
            $success_msg = "نظر شما ثبت شد.";
        } else {
            $err = "خطا در ثبت نظر.";
        }
    }

    // Action: Update Status
    if (isset($_POST['update_status']) && !empty($_POST['new_status'])) {
        $new_status = $_POST['new_status'];
        $sql = "UPDATE tasks SET status = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $new_status, $task_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            record_task_history($link, $task_id, $user_id, "تغییر وضعیت به " . get_task_status_name($new_status));
            $success_msg = "وضعیت وظیفه به‌روزرسانی شد.";
        } else {
            $err = "خطا در به‌روزرسانی وضعیت.";
        }
    }
}


// Fetch task details
$task_sql = "
    SELECT t.*, u.username as creator_name
    FROM tasks t
    JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
";
$stmt = mysqli_prepare($link, $task_sql);
mysqli_stmt_bind_param($stmt, "i", $task_id);
mysqli_stmt_execute($stmt);
$task_result = mysqli_stmt_get_result($stmt);
$task = mysqli_fetch_assoc($task_result);
mysqli_stmt_close($stmt);

if (!$task) {
    echo "وظیفه یافت نشد.";
    exit;
}

// Fetch assignees
$assignees_sql = "
    SELECT u.username, d.department_name
    FROM task_assignments ta
    LEFT JOIN users u ON ta.assigned_to_user_id = u.id
    LEFT JOIN departments d ON ta.assigned_to_department_id = d.id
    WHERE ta.task_id = ?
";
$stmt_a = mysqli_prepare($link, $assignees_sql);
mysqli_stmt_bind_param($stmt_a, "i", $task_id);
mysqli_stmt_execute($stmt_a);
$assignees_result = mysqli_stmt_get_result($stmt_a);
$assignees = mysqli_fetch_all($assignees_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_a);


// Fetch comments
$comments_sql = "SELECT tc.*, u.username FROM task_comments tc JOIN users u ON tc.user_id = u.id WHERE tc.task_id = ? ORDER BY tc.created_at ASC";
$stmt_c = mysqli_prepare($link, $comments_sql);
mysqli_stmt_bind_param($stmt_c, "i", $task_id);
mysqli_stmt_execute($stmt_c);
$comments_result = mysqli_stmt_get_result($stmt_c);
$comments = mysqli_fetch_all($comments_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_c);

// Fetch history
$history_sql = "SELECT th.*, u.username FROM task_history th JOIN users u ON th.user_id = u.id WHERE th.task_id = ? ORDER BY th.created_at DESC";
$stmt_h = mysqli_prepare($link, $history_sql);
mysqli_stmt_bind_param($stmt_h, "i", $task_id);
mysqli_stmt_execute($stmt_h);
$history_result = mysqli_stmt_get_result($stmt_h);
$history = mysqli_fetch_all($history_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_h);


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="my_tasks.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> بازگشت به لیست وظایف</a>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="row">
        <!-- Main Task Info -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                </div>
                <div class="card-body">
                    <p><strong>توضیحات:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    <hr>
                    <!-- Comments Section -->
                    <h5><i class="fas fa-comments"></i> نظرات</h5>
                    <div class="comments-section mb-4">
                        <?php foreach($comments as $comment): ?>
                        <div class="comment">
                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                            <span class="text-muted text-sm"><?php echo to_persian_date($comment['created_at']); ?></span>
                            <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Add Comment Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $task_id; ?>" method="post">
                        <div class="form-group">
                            <label for="comment">افزودن نظر</label>
                            <textarea name="comment" id="comment" rows="3" class="form-control"></textarea>
                        </div>
                        <button type="submit" name="add_comment" class="btn btn-primary">ثبت نظر</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">جزئیات وظیفه</h5>
                    <p><strong>ایجاد کننده:</strong> <?php echo htmlspecialchars($task['creator_name']); ?></p>
                    <p><strong>تاریخ ایجاد:</strong> <?php echo to_persian_date($task['created_at']); ?></p>
                    <p><strong>مهلت انجام:</strong> <?php echo $task['deadline'] ? to_persian_date($task['deadline']) : 'ندارد'; ?></p>
                    <p><strong>اولویت:</strong> <?php echo get_task_priority_badge($task['priority']); ?></p>
                    <p><strong>وضعیت فعلی:</strong> <?php echo get_task_status_badge($task['status']); ?></p>

                    <!-- Update Status Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $task_id; ?>" method="post" class="mt-3">
                        <div class="form-group">
                            <label for="new_status">تغییر وضعیت</label>
                            <select name="new_status" id="new_status" class="form-control">
                                <option value="pending" <?php if($task['status'] == 'pending') echo 'selected'; ?>>در انتظار</option>
                                <option value="in_progress" <?php if($task['status'] == 'in_progress') echo 'selected'; ?>>در حال انجام</option>
                                <option value="completed" <?php if($task['status'] == 'completed') echo 'selected'; ?>>تکمیل شده</option>
                                <option value="cancelled" <?php if($task['status'] == 'cancelled') echo 'selected'; ?>>لغو شده</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-success">بروزرسانی وضعیت</button>
                    </form>
                    <hr>
                    <p><strong>تخصیص یافته به:</strong></p>
                    <ul>
                        <?php foreach($assignees as $assignee): ?>
                            <li>
                                <?php
                                echo !empty($assignee['username']) ? 'کاربر: ' . htmlspecialchars($assignee['username']) : '';
                                echo !empty($assignee['department_name']) ? 'بخش: ' . htmlspecialchars($assignee['department_name']) : '';
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-history"></i> تاریخچه تغییرات</h5>
                    <ul class="history-list">
                        <?php foreach($history as $item): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($item['username']); ?></strong>
                            <?php echo htmlspecialchars($item['action']); ?>
                            <span class="text-muted text-sm d-block"><?php echo to_persian_date($item['created_at']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.comment { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
.comment:last-child { border-bottom: none; }
.history-list { list-style: none; padding: 0; max-height: 300px; overflow-y: auto; }
.history-list li { background-color: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 5px; }
</style>

<?php
require_once "../includes/footer.php";
?>
