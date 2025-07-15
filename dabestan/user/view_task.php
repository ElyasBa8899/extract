<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/access_control.php";
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

// Fetch task details
$sql = "SELECT t.*, u.username as creator_name FROM tasks t JOIN users u ON t.created_by = u.id WHERE t.id = ? AND EXISTS (SELECT 1 FROM task_assignments ta WHERE ta.task_id = t.id AND ta.assigned_to_user_id = ?)";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $task = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$task) {
    echo "<div class='alert alert-danger'>وظیفه مورد نظر یافت نشد یا شما به آن دسترسی ندارید.</div>";
    require_once "../includes/footer.php";
    exit;
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $sql_update = "UPDATE tasks SET status = ? WHERE id = ?";
    if ($stmt_update = mysqli_prepare($link, $sql_update)) {
        mysqli_stmt_bind_param($stmt_update, "si", $new_status, $task_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        // Refresh the page to show the new status
        header("location: view_task.php?id=" . $task_id);
        exit;
    }
}

function get_status_badge($status) {
    switch ($status) {
        case 'pending': return '<span class="badge badge-warning">در انتظار</span>';
        case 'in_progress': return '<span class="badge badge-info">در حال انجام</span>';
        case 'completed': return '<span class="badge badge-success">تکمیل شده</span>';
        case 'cancelled': return '<span class="badge badge-secondary">لغو شده</span>';
        default: return '';
    }
}

function get_priority_badge($priority) {
    switch ($priority) {
        case 'normal': return '<span class="badge badge-primary">عادی</span>';
        case 'high': return '<span class="badge badge-danger">بالا</span>';
        case 'urgent': return '<span class="badge badge-danger" style="background-color: #dc3545; color: white;">فوری</span>';
        default: return '';
    }
}

?>

<div class="page-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2>جزئیات وظیفه: <?php echo htmlspecialchars($task['title']); ?></h2>
            <a href="my_tasks.php" class="btn btn-secondary">بازگشت به لیست وظایف</a>
        </div>
        <hr>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>عنوان:</strong> <?php echo htmlspecialchars($task['title']); ?></p>
                        <p><strong>ایجاد کننده:</strong> <?php echo htmlspecialchars($task['creator_name']); ?></p>
                        <p><strong>تاریخ ایجاد:</strong> <?php echo to_persian_date($task['created_at']); ?></p>
                        <p><strong>مهلت انجام:</strong> <?php echo $task['deadline'] ? to_persian_date($task['deadline']) : 'ندارد'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>وضعیت:</strong> <?php echo get_status_badge($task['status']); ?></p>
                        <p><strong>اولویت:</strong> <?php echo get_priority_badge($task['priority']); ?></p>
                        <p><strong>تاریخ تکمیل:</strong> <?php echo $task['completed_at'] ? to_persian_date($task['completed_at']) : 'تکمیل نشده'; ?></p>
                    </div>
                </div>
                <hr>
                <div>
                    <strong>توضیحات:</strong>
                    <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                تغییر وضعیت وظیفه
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $task_id; ?>" method="post">
                    <div class="form-group">
                        <label for="status">تغییر وضعیت به:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                            <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                            <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                            <option value="cancelled" <?php echo $task['status'] == 'cancelled' ? 'selected' : ''; ?>>لغو شده</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary">بروزرسانی وضعیت</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
