<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: manage_tasks.php");
    exit;
}

$task_id = $_GET['id'];
$user_id = $_SESSION['id'];

// TODO: Add proper permission check to see if user can view this task

// Fetch task details
$sql_task = "SELECT t.*, u.username as creator_username
             FROM tasks t
             JOIN users u ON t.created_by = u.id
             WHERE t.id = ?";
$stmt_task = mysqli_prepare($link, $sql_task);
mysqli_stmt_bind_param($stmt_task, "i", $task_id);
mysqli_stmt_execute($stmt_task);
$result = mysqli_stmt_get_result($stmt_task);
$task = mysqli_fetch_assoc($result);

if (!$task) {
    die("وظیفه یافت نشد.");
}

// Handle Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    // Add more validation for allowed statuses

    $sql_update = "UPDATE tasks SET status = ? WHERE id = ?";
    // Also set completed_at if status is 'completed'
    if ($new_status === 'completed') {
        $sql_update = "UPDATE tasks SET status = ?, completed_at = NOW() WHERE id = ?";
    }

    $stmt_update = mysqli_prepare($link, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "si", $new_status, $task_id);
    if(mysqli_stmt_execute($stmt_update)) {
        // Refresh task data
        $task['status'] = $new_status;
        echo "<div class='alert alert-success'>وضعیت وظیفه با موفقیت به‌روزرسانی شد.</div>";
    } else {
        echo "<div class='alert alert-danger'>خطا در به‌روزرسانی وضعیت.</div>";
    }
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>

<div class="page-content">
    <a href="manage_tasks.php" class="btn btn-secondary mb-3">&larr; بازگشت به لیست وظایف</a>

    <div class="widget">
        <div class="widget-header d-flex justify-content-between align-items-center">
            <h2>جزئیات وظیفه: <?php echo htmlspecialchars($task['title']); ?></h2>
            <div>
                <span class="badge badge-info"><?php echo htmlspecialchars($task['status']); ?></span>
                 <span class="badge badge-danger"><?php echo htmlspecialchars($task['priority']); ?></span>
            </div>
        </div>
        <div class="widget-body">
            <p><strong>توضیحات:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
            <hr>
            <div class="task-meta">
                <span><strong>ایجاد شده توسط:</strong> <?php echo htmlspecialchars($task['creator_username']); ?></span>
                <span><strong>تاریخ ایجاد:</strong> <?php echo to_persian_date($task['created_at']); ?></span>
                <?php if ($task['deadline']): ?>
                <span><strong>ددلاین:</strong> <?php echo to_persian_date($task['deadline']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="widget mt-4">
        <div class="widget-header">
            <h4>به‌روزرسانی وضعیت</h4>
        </div>
        <div class="widget-body">
            <form action="" method="post">
                <div class="form-group">
                    <select name="status" class="form-control">
                        <option value="pending" <?php if($task['status'] == 'pending') echo 'selected'; ?>>در انتظار</option>
                        <option value="in_progress" <?php if($task['status'] == 'in_progress') echo 'selected'; ?>>در حال انجام</option>
                        <option value="completed" <?php if($task['status'] == 'completed') echo 'selected'; ?>>انجام شده</option>
                        <option value="cancelled" <?php if($task['status'] == 'cancelled') echo 'selected'; ?>>لغو شده</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn btn-primary">به‌روزرسانی</button>
            </form>
        </div>
    </div>

     <!-- Comments/Discussion section can be added here later -->
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php"; ?>
