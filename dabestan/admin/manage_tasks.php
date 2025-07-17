<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

// Permissions check
require_permission('manage_tasks');

$err = $success_msg = "";

// Handle Create Task POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_task'])) {
    // Sanitize and validate inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $assign_type = $_POST['assign_type'];
    $assigned_ids = $_POST['assigned_ids'] ?? [];

    if (empty($title) || empty($assign_type) || empty($assigned_ids)) {
        $err = "عنوان، نوع تخصیص و حداقل یک گیرنده برای وظیفه الزامی است.";
    } else {
        mysqli_begin_transaction($link);
        try {
            // 1. Insert into tasks table
            $sql_task = "INSERT INTO tasks (title, description, priority, deadline, created_by) VALUES (?, ?, ?, ?, ?)";
            $stmt_task = mysqli_prepare($link, $sql_task);
            mysqli_stmt_bind_param($stmt_task, "ssssi", $title, $description, $priority, $deadline, $_SESSION['id']);
            mysqli_stmt_execute($stmt_task);
            $task_id = mysqli_insert_id($stmt_task);
            mysqli_stmt_close($stmt_task);

            // 2. Insert into task_assignments table
            $sql_assign = "INSERT INTO task_assignments (task_id, assigned_to_user_id, assigned_to_department_id) VALUES (?, ?, ?)";
            $stmt_assign = mysqli_prepare($link, $sql_assign);

            foreach ($assigned_ids as $id) {
                $user_id = ($assign_type == 'user') ? $id : null;
                $dept_id = ($assign_type == 'department') ? $id : null;
                mysqli_stmt_bind_param($stmt_assign, "iii", $task_id, $user_id, $dept_id);
                mysqli_stmt_execute($stmt_assign);
            }
            mysqli_stmt_close($stmt_assign);

            mysqli_commit($link);
            $success_msg = "وظیفه جدید با موفقیت ایجاد و تخصیص داده شد.";

        } catch (Exception $e) {
            mysqli_rollback($link);
            $err = "خطا در ایجاد وظیفه: " . $e->getMessage();
        }
    }
}

// Fetch data for form dropdowns
$users = mysqli_query($link, "SELECT id, first_name, last_name FROM users ORDER BY last_name ASC");
$departments = mysqli_query($link, "SELECT id, department_name FROM departments ORDER BY department_name ASC");

// Fetch existing tasks
$tasks_sql = "
    SELECT
        t.id, t.title, t.status, t.priority, t.deadline, u_creator.username as creator,
        GROUP_CONCAT(DISTINCT u_assignee.username SEPARATOR ', ') as assigned_users,
        GROUP_CONCAT(DISTINCT d_assignee.department_name SEPARATOR ', ') as assigned_departments
    FROM tasks t
    JOIN users u_creator ON t.created_by = u_creator.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN users u_assignee ON ta.assigned_to_user_id = u_assignee.id
    LEFT JOIN departments d_assignee ON ta.assigned_to_department_id = d_assignee.id
    GROUP BY t.id
    ORDER BY t.created_at DESC
";
$tasks_result = mysqli_query($link, $tasks_sql);


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت وظایف</h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Task Form -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3><i class="fas fa-plus"></i> ایجاد وظیفه جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="title">عنوان وظیفه</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="priority">اولویت</label>
                        <select name="priority" id="priority" class="form-control">
                            <option value="normal">عادی</option>
                            <option value="high">بالا</option>
                            <option value="urgent">فوری</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="deadline">مهلت انجام (اختیاری)</label>
                        <input type="datetime-local" name="deadline" id="deadline" class="form-control">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>تخصیص به:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="assign_type" id="assign_user" value="user" checked onchange="toggleAssigneeList()">
                    <label class="form-check-label" for="assign_user">کاربر(ان)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="assign_type" id="assign_department" value="department" onchange="toggleAssigneeList()">
                    <label class="form-check-label" for="assign_department">بخش(ها)</label>
                </div>
            </div>
            <div class="form-group" id="user-list">
                <label for="assigned-users">انتخاب کاربر(ان)</label>
                <select name="assigned_ids[]" id="assigned-users" class="form-control" multiple required>
                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" id="department-list" style="display: none;">
                <label for="assigned-departments">انتخاب بخش(ها)</label>
                <select name="assigned_ids[]" id="assigned-departments" class="form-control" multiple>
                    <?php mysqli_data_seek($departments, 0); // Reset pointer ?>
                    <?php while($dept = mysqli_fetch_assoc($departments)): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" name="create_task" class="btn btn-primary">ایجاد وظیفه</button>
            </div>
        </form>
    </div>

    <!-- List of Existing Tasks -->
    <div class="table-container">
        <h3><i class="fas fa-list-check"></i> لیست وظایف</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>ایجاد کننده</th>
                        <th>تخصیص یافته به</th>
                        <th>وضعیت</th>
                        <th>اولویت</th>
                        <th>مهلت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($task = mysqli_fetch_assoc($tasks_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                        <td><?php echo htmlspecialchars($task['creator']); ?></td>
                        <td>
                            <?php
                            echo !empty($task['assigned_users']) ? 'کاربران: ' . htmlspecialchars($task['assigned_users']) : '';
                            echo !empty($task['assigned_departments']) ? 'بخش‌ها: ' . htmlspecialchars($task['assigned_departments']) : '';
                            ?>
                        </td>
                        <td><?php echo get_task_status_badge($task['status']); ?></td>
                        <td><?php echo get_task_priority_badge($task['priority']); ?></td>
                        <td><?php echo $task['deadline'] ? to_persian_date($task['deadline']) : '---'; ?></td>
                        <td>
                            <a href="../user/view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">مشاهده</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleAssigneeList() {
    const assignType = document.querySelector('input[name="assign_type"]:checked').value;
    const userList = document.getElementById('user-list');
    const deptList = document.getElementById('department-list');
    const userSelect = document.getElementById('assigned-users');
    const deptSelect = document.getElementById('assigned-departments');

    if (assignType === 'user') {
        userList.style.display = 'block';
        deptList.style.display = 'none';
        userSelect.setAttribute('required', 'required');
        deptSelect.removeAttribute('required');
        deptSelect.name = ''; // Disable department select name
        userSelect.name = 'assigned_ids[]';
    } else {
        userList.style.display = 'none';
        deptList.style.display = 'block';
        deptSelect.setAttribute('required', 'required');
        userSelect.removeAttribute('required');
        userSelect.name = ''; // Disable user select name
        deptSelect.name = 'assigned_ids[]';
    }
}
// Initial call to set the correct state on page load
toggleAssigneeList();
</script>

<?php
require_once "../includes/footer.php";
?>
