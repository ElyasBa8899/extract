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

$user_id = $_SESSION['id'];

// Fetch user's department
$user_dept_id = null;
$sql_dept = "SELECT department_id FROM user_departments WHERE user_id = ?";
if($stmt_dept = mysqli_prepare($link, $sql_dept)){
    mysqli_stmt_bind_param($stmt_dept, "i", $user_id);
    mysqli_stmt_execute($stmt_dept);
    $result_dept = mysqli_stmt_get_result($stmt_dept);
    if($dept = mysqli_fetch_assoc($result_dept)){
        $user_dept_id = $dept['department_id'];
    }
    mysqli_stmt_close($stmt_dept);
}


// Fetch tasks assigned to the user or their department
$tasks_query = "SELECT t.*, u_creator.username as creator
                FROM tasks t
                JOIN users u_creator ON t.created_by = u_creator.id
                JOIN task_assignments ta ON t.id = ta.task_id
                WHERE ta.assigned_to_user_id = ?
                OR (ta.assigned_to_department_id IS NOT NULL AND ta.assigned_to_department_id = ?)
                ORDER BY t.deadline ASC, t.priority DESC";

if($stmt_tasks = mysqli_prepare($link, $tasks_query)){
    mysqli_stmt_bind_param($stmt_tasks, "ii", $user_id, $user_dept_id);
    mysqli_stmt_execute($stmt_tasks);
    $result_tasks = mysqli_stmt_get_result($stmt_tasks);
    $tasks = mysqli_fetch_all($result_tasks, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_tasks);
} else {
    $tasks = [];
}

function get_status_badge_task($status) {
    switch ($status) {
        case 'pending': return '<span class="badge badge-warning">در انتظار</span>';
        case 'in_progress': return '<span class="badge badge-info">در حال انجام</span>';
        case 'completed': return '<span class="badge badge-success">تکمیل شده</span>';
        case 'cancelled': return '<span class="badge badge-secondary">لغو شده</span>';
        default: return '';
    }
}

function get_priority_badge_task($priority) {
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
        <h2>وظایف من</h2>
        <p>در این بخش می‌توانید لیست وظایفی که به شما یا بخش شما محول شده است را مشاهده کنید.</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>اولویت</th>
                        <th>وضعیت</th>
                        <th>مهلت</th>
                        <th>ایجاد کننده</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="6" class="text-center">در حال حاضر وظیفه‌ای برای شما ثبت نشده است.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo get_priority_badge_task($task['priority']); ?></td>
                                <td><?php echo get_status_badge_task($task['status']); ?></td>
                                <td><?php echo (!empty($task['deadline']) && $task['deadline'] != '0000-00-00 00:00:00') ? to_persian_date($task['deadline'], 'Y/m/d H:i') : 'ندارد'; ?></td>
                                <td><?php echo htmlspecialchars($task['creator']); ?></td>
                                <td>
                                    <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">مشاهده جزئیات</a>
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
