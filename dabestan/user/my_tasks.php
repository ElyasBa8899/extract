<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Get user's department IDs
$department_ids_query = mysqli_query($link, "SELECT department_id FROM user_departments WHERE user_id = $user_id");
$department_ids = mysqli_fetch_all($department_ids_query, MYSQLI_ASSOC);
$dept_ids_list = !empty($department_ids) ? implode(',', array_column($department_ids, 'department_id')) : '0';

// Fetch tasks assigned to the user directly OR to their department
$sql = "
    SELECT DISTINCT
        t.id, t.title, t.status, t.priority, t.deadline, u.username as creator
    FROM tasks t
    JOIN users u ON t.created_by = u.id
    JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.assigned_to_user_id = ?
    OR (ta.assigned_to_department_id IN ($dept_ids_list))
    ORDER BY t.deadline ASC, t.priority DESC, t.created_at DESC
";

$tasks = [];
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2><i class="fas fa-tasks"></i> وظایف من</h2>
    <p>در این بخش لیست وظایفی که به شما یا بخش شما محول شده است را مشاهده می‌کنید.</p>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>عنوان وظیفه</th>
                        <th>ایجاد کننده</th>
                        <th>اولویت</th>
                        <th>وضعیت</th>
                        <th>مهلت انجام</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="6" class="text-center">هیچ وظیفه‌ای برای شما ثبت نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr class="task-row-<?php echo htmlspecialchars($task['priority']); ?>">
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['creator']); ?></td>
                                <td><?php echo get_task_priority_badge($task['priority']); ?></td>
                                <td><?php echo get_task_status_badge($task['status']); ?></td>
                                <td>
                                    <?php if ($task['deadline']): ?>
                                        <span class="<?php echo (new DateTime() > new DateTime($task['deadline'])) ? 'text-danger' : ''; ?>">
                                            <?php echo to_persian_date($task['deadline']); ?>
                                        </span>
                                    <?php else: ?>
                                        ---
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-primary btn-sm">مشاهده و پیگیری</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.task-row-urgent { border-left: 5px solid #dc3545; }
.task-row-high { border-left: 5px solid #ffc107; }
.task-row-normal { border-left: 5px solid #17a2b8; }
</style>

<?php
require_once "../includes/footer.php";
?>
