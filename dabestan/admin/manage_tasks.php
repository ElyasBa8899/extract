<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

if (!has_permission('manage_tasks')) {
    header("location: ../index.php");
    exit;
}

// Handle form submissions for adding/editing tasks
$task_id = $title = $description = $status = $priority = $deadline = "";
$assigned_to_user_id = $assigned_to_department_id = null;
$form_err = "";
$update_mode = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add or Update Task
    if (isset($_POST['save_task'])) {
        $task_id = $_POST['task_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $status = $_POST['status'];
        $priority = $_POST['priority'];
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        $assign_to_all = isset($_POST['assign_to_all']);
        $assigned_to_user_id = !$assign_to_all && !empty($_POST['assigned_to_user_id']) ? $_POST['assigned_to_user_id'] : null;
        $assigned_to_department_id = !$assign_to_all && !empty($_POST['assigned_to_department_id']) ? $_POST['assigned_to_department_id'] : null;

        if (empty($title)) {
            $form_err = "عنوان وظیفه نمی‌تواند خالی باشد.";
        }

        if (empty($form_err)) {
            if (empty($task_id)) { // Add new task
                $sql = "INSERT INTO tasks (title, description, status, priority, deadline, created_by) VALUES (?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $status, $priority, $deadline, $_SESSION['id']);
                    mysqli_stmt_execute($stmt);
                    $new_task_id = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);

                    // Assign task
                    if ($assign_to_all) {
                        $all_users_query = mysqli_query($link, "SELECT id FROM users");
                        while ($user = mysqli_fetch_assoc($all_users_query)) {
                            $sql_assign = "INSERT INTO task_assignments (task_id, assigned_to_user_id) VALUES (?, ?)";
                            if ($stmt_assign = mysqli_prepare($link, $sql_assign)) {
                                mysqli_stmt_bind_param($stmt_assign, "ii", $new_task_id, $user['id']);
                                mysqli_stmt_execute($stmt_assign);
                                mysqli_stmt_close($stmt_assign);

                                // Send notification
                                $message = "وظیفه جدیدی با عنوان '" . htmlspecialchars($title) . "' برای شما ثبت شد.";
                                $link_notif = "user/view_task.php?id=" . $new_task_id;
                                $sql_notif = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                                if($stmt_notif = mysqli_prepare($link, $sql_notif)){
                                    mysqli_stmt_bind_param($stmt_notif, "iss", $user['id'], $message, $link_notif);
                                    mysqli_stmt_execute($stmt_notif);
                                    mysqli_stmt_close($stmt_notif);
                                }
                            }
                        }
                    } else {
                        $sql_assign = "INSERT INTO task_assignments (task_id, assigned_to_user_id, assigned_to_department_id) VALUES (?, ?, ?)";
                        if ($stmt_assign = mysqli_prepare($link, $sql_assign)) {
                            mysqli_stmt_bind_param($stmt_assign, "iii", $new_task_id, $assigned_to_user_id, $assigned_to_department_id);
                            mysqli_stmt_execute($stmt_assign);
                            mysqli_stmt_close($stmt_assign);

                            // Send notification
                            if ($assigned_to_user_id) {
                                $message = "وظیفه جدیدی با عنوان '" . htmlspecialchars($title) . "' برای شما ثبت شد.";
                                $link_notif = "user/view_task.php?id=" . $new_task_id;
                                $sql_notif = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                                if($stmt_notif = mysqli_prepare($link, $sql_notif)){
                                    mysqli_stmt_bind_param($stmt_notif, "iss", $assigned_to_user_id, $message, $link_notif);
                                    mysqli_stmt_execute($stmt_notif);
                                    mysqli_stmt_close($stmt_notif);
                                }
                            } elseif ($assigned_to_department_id) {
                                $message = "وظیفه جدیدی با عنوان '" . htmlspecialchars($title) . "' برای بخش شما ثبت شد.";
                                $link_notif = "user/view_task.php?id=" . $new_task_id;
                                $sql_users_in_dept = "SELECT user_id FROM user_departments WHERE department_id = ?";
                                if($stmt_users = mysqli_prepare($link, $sql_users_in_dept)){
                                    mysqli_stmt_bind_param($stmt_users, "i", $assigned_to_department_id);
                                    mysqli_stmt_execute($stmt_users);
                                    $result_users = mysqli_stmt_get_result($stmt_users);
                                    while($user_row = mysqli_fetch_assoc($result_users)){
                                        $sql_notif = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                                        if($stmt_notif = mysqli_prepare($link, $sql_notif)){
                                            mysqli_stmt_bind_param($stmt_notif, "iss", $user_row['user_id'], $message, $link_notif);
                                            mysqli_stmt_execute($stmt_notif);
                                            mysqli_stmt_close($stmt_notif);
                                        }
                                    }
                                    mysqli_stmt_close($stmt_users);
                                }
                            }
                        }
                    }
                    $_SESSION['success_message'] = "وظیفه با موفقیت اضافه شد.";
                }
            } else { // Update existing task
                $sql = "UPDATE tasks SET title = ?, description = ?, status = ?, priority = ?, deadline = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $status, $priority, $deadline, $task_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Update assignment
                    $sql_update_assign = "UPDATE task_assignments SET assigned_to_user_id = ?, assigned_to_department_id = ? WHERE task_id = ?";
                    if ($stmt_update_assign = mysqli_prepare($link, $sql_update_assign)) {
                        mysqli_stmt_bind_param($stmt_update_assign, "iii", $assigned_to_user_id, $assigned_to_department_id, $task_id);
                        mysqli_stmt_execute($stmt_update_assign);
                        mysqli_stmt_close($stmt_update_assign);
                    }
                    $_SESSION['success_message'] = "وظیفه با موفقیت ویرایش شد.";
                }
            }
            header("location: manage_tasks.php");
            exit;
        }
    }

    // Delete Task
    if (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];
        $sql = "DELETE FROM tasks WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $task_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['success_message'] = "وظیفه با موفقیت حذف شد.";
        }
        header("location: manage_tasks.php");
        exit;
    }
}

// Fetch task data for editing
if (isset($_GET['edit'])) {
    $task_id = $_GET['edit'];
    $sql = "SELECT t.*, ta.assigned_to_user_id, ta.assigned_to_department_id FROM tasks t LEFT JOIN task_assignments ta ON t.id = ta.task_id WHERE t.id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $task_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($task = mysqli_fetch_assoc($result)) {
            $update_mode = true;
            $title = $task['title'];
            $description = $task['description'];
            $status = $task['status'];
            $priority = $task['priority'];
            $deadline = $task['deadline'];
            $assigned_to_user_id = $task['assigned_to_user_id'];
            $assigned_to_department_id = $task['assigned_to_department_id'];
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all tasks to display
$tasks_query = "SELECT t.*, u_creator.username as creator, u_assignee.username as assignee_user, d.department_name as assignee_dept
                FROM tasks t
                JOIN users u_creator ON t.created_by = u_creator.id
                LEFT JOIN task_assignments ta ON t.id = ta.task_id
                LEFT JOIN users u_assignee ON ta.assigned_to_user_id = u_assignee.id
                LEFT JOIN departments d ON ta.assigned_to_department_id = d.id
                ORDER BY t.created_at DESC";
$tasks = mysqli_query($link, $tasks_query);

// Fetch users and departments for assignment dropdowns
$users = mysqli_query($link, "SELECT id, username FROM users ORDER BY username");
$departments = mysqli_query($link, "SELECT id, department_name FROM departments ORDER BY department_name");

require_once "../includes/header.php";
?>

<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت وظایف</h2>
        <p>در این بخش می‌توانید وظایف را ایجاد، ویرایش و به کاربران یا بخش‌ها محول کنید.</p>

        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        if (!empty($form_err)) {
            echo '<div class="alert alert-danger">' . $form_err . '</div>';
        }
        ?>

        <!-- Add/Edit Task Form -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo $update_mode ? 'ویرایش وظیفه' : 'افزودن وظیفه جدید'; ?></h3>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                    <div class="form-group">
                        <label for="title">عنوان وظیفه</label>
                        <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">توضیحات</label>
                        <textarea name="description" id="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="assign_to_all" id="assign_to_all" class="form-check-input">
                        <label for="assign_to_all" class="form-check-label">ارسال برای همه کاربران</label>
                    </div>
                    <div class="row" id="assignment_selectors">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="assigned_to_user_id">محول شده به کاربر</label>
                                <select name="assigned_to_user_id" id="assigned_to_user_id" class="form-control">
                                    <option value="">-- انتخاب کنید --</option>
                                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo $assigned_to_user_id == $user['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['username']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="assigned_to_department_id">محول شده به بخش</label>
                                <select name="assigned_to_department_id" id="assigned_to_department_id" class="form-control">
                                    <option value="">-- انتخاب کنید --</option>
                                    <?php while ($dept = mysqli_fetch_assoc($departments)): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $assigned_to_department_id == $dept['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">وضعیت</label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                                    <option value="in_progress" <?php echo $status == 'in_progress' ? 'selected' : ''; ?>>در حال انجام</option>
                                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>لغو شده</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="priority">اولویت</label>
                                <select name="priority" id="priority" class="form-control" required>
                                    <option value="low" <?php echo $priority == 'low' ? 'selected' : ''; ?>>کم</option>
                                    <option value="medium" <?php echo $priority == 'medium' ? 'selected' : ''; ?>>متوسط</option>
                                    <option value="high" <?php echo $priority == 'high' ? 'selected' : ''; ?>>زیاد</option>
                                    <option value="urgent" <?php echo $priority == 'urgent' ? 'selected' : ''; ?>>فوری</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="deadline">مهلت انجام</label>
                                <input type="text" name="deadline" id="deadline" class="form-control persian-datepicker" value="<?php echo $deadline; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="save_task" class="btn btn-primary"><?php echo $update_mode ? 'ذخیره تغییرات' : 'افزودن وظیفه'; ?></button>
                        <?php if ($update_mode): ?>
                            <a href="manage_tasks.php" class="btn btn-secondary">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3>لیست وظایف</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>محول شده به</th>
                                <th>وضعیت</th>
                                <th>اولویت</th>
                                <th>مهلت</th>
                                <th>ایجاد کننده</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($tasks)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['assignee_user'] ? $row['assignee_user'] : $row['assignee_dept']); ?></td>
                                    <td><?php echo $row['status']; ?></td>
                                    <td><?php echo $row['priority']; ?></td>
                                    <td><?php echo (!empty($row['deadline']) && $row['deadline'] != '0000-00-00 00:00:00') ? to_persian_date($row['deadline'], 'Y/m/d H:i') : 'ندارد'; ?></td>
                                    <td><?php echo htmlspecialchars($row['creator']); ?></td>
                                    <td>
                                        <a href="manage_tasks.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">ویرایش</a>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline-block;">
                                            <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_task" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این وظیفه اطمینان دارید؟');">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
$(document).ready(function() {
    $(".persian-datepicker").pDatepicker({
        format: 'YYYY/MM/DD HH:mm:ss',
        timePicker: {
            enabled: true
        }
    });
});

document.getElementById('assign_to_all').addEventListener('change', function() {
    var selectors = document.getElementById('assignment_selectors');
    if (this.checked) {
        selectors.style.display = 'none';
    } else {
        selectors.style.display = 'flex'; // or 'block' depending on your layout
    }
});
</script>
<?php
require_once "../includes/footer.php";
?>
