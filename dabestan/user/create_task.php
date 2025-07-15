<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
if (!has_permission('create_task')) {
    echo "شما اجازه ایجاد وظیفه جدید را ندارید.";
    exit;
}

$err = $success_msg = "";

// Fetch departments and users for assignment
$departments = mysqli_query($link, "SELECT id, department_name FROM departments ORDER BY department_name ASC");
$users = mysqli_query($link, "SELECT id, username FROM users ORDER BY username ASC");


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = !empty(trim($_POST['deadline'])) ? trim($_POST['deadline']) : null;
    $priority = trim($_POST['priority']);
    $assign_type = $_POST['assign_type'];
    $assigned_to_dept_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
    $assigned_to_user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;

    if (empty($title)) {
        $err = "عنوان وظیفه الزامی است.";
    } elseif ($assign_type === 'department' && empty($assigned_to_dept_id)) {
        $err = "لطفاً بخش مورد نظر را برای ارجاع انتخاب کنید.";
    } elseif ($assign_type === 'user' && empty($assigned_to_user_id)) {
        $err = "لطفاً کاربر مورد نظر را برای ارجاع انتخاب کنید.";
    } else {
        mysqli_begin_transaction($link);
        try {
            // 1. Insert the task
            $sql_task = "INSERT INTO tasks (title, description, priority, deadline, created_by) VALUES (?, ?, ?, ?, ?)";
            $stmt_task = mysqli_prepare($link, $sql_task);
            mysqli_stmt_bind_param($stmt_task, "ssssi", $title, $description, $priority, $deadline, $_SESSION['id']);
            mysqli_stmt_execute($stmt_task);
            $task_id = mysqli_insert_id($link);

            // 2. Insert assignment
            $sql_assign = "INSERT INTO task_assignments (task_id, assigned_to_user_id, assigned_to_department_id) VALUES (?, ?, ?)";
            $stmt_assign = mysqli_prepare($link, $sql_assign);
            mysqli_stmt_bind_param($stmt_assign, "iii", $task_id, $assigned_to_user_id, $assigned_to_dept_id);
            mysqli_stmt_execute($stmt_assign);

            // 3. Create notifications
            $notification_message = "وظیفه جدیدی با عنوان '" . htmlspecialchars($title) . "' برای شما ثبت شد.";
            $notification_link = "/user/view_task.php?id=" . $task_id; // This page needs to be created
            $target_user_ids = [];
            if ($assigned_to_user_id) {
                $target_user_ids[] = $assigned_to_user_id;
            } elseif ($assigned_to_dept_id) {
                $sql_users = "SELECT user_id FROM user_departments WHERE department_id = ?";
                $stmt_users = mysqli_prepare($link, $sql_users);
                mysqli_stmt_bind_param($stmt_users, "i", $assigned_to_dept_id);
                mysqli_stmt_execute($stmt_users);
                $result_users = mysqli_stmt_get_result($stmt_users);
                while ($row = mysqli_fetch_assoc($result_users)) $target_user_ids[] = $row['user_id'];
            }
            if (!empty($target_user_ids)) {
                $sql_notify = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                $stmt_notify = mysqli_prepare($link, $sql_notify);
                foreach ($target_user_ids as $target_id) {
                    mysqli_stmt_bind_param($stmt_notify, "iss", $target_id, $notification_message, $notification_link);
                    mysqli_stmt_execute($stmt_notify);
                }
            }

            mysqli_commit($link);
            $success_msg = "وظیفه با موفقیت ایجاد و ارجاع داده شد.";

        } catch (Exception $e) {
            mysqli_rollback($link);
            $err = "خطا در ایجاد وظیفه: " . $e->getMessage();
        }
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>

<div class="page-content">
    <a href="manage_tasks.php" class="btn btn-secondary mb-3">&larr; بازگشت به لیست وظایف</a>
    <h2>ایجاد وظیفه جدید</h2>
    <p>یک وظیفه جدید تعریف کرده و آن را به یک بخش یا کاربر خاص ارجاع دهید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="title">عنوان وظیفه <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <textarea name="description" id="description" class="form-control" rows="5"></textarea>
            </div>
             <div class="form-group">
                <label for="deadline">ددلاین (اختیاری)</label>
                <input type="text" name="deadline" id="deadline" class="form-control persian-datepicker">
            </div>
            <div class="form-group">
                 <label for="priority">اولویت</label>
                <select name="priority" id="priority" class="form-control">
                    <option value="normal">عادی</option>
                    <option value="high">بالا</option>
                    <option value="urgent">فوری</option>
                </select>
            </div>

            <hr>
            <h4>ارجاع وظیفه</h4>
             <div class="form-group">
                <label>نوع ارجاع</label>
                 <div class="radio-group">
                    <input type="radio" name="assign_type" value="department" id="assign_type_dept" checked onchange="toggleAssignFields()"> <label for="assign_type_dept">ارجاع به بخش</label>
                </div>
                <div class="radio-group">
                    <input type="radio" name="assign_type" value="user" id="assign_type_user" onchange="toggleAssignFields()"> <label for="assign_type_user">ارجاع به کاربر</label>
                </div>
            </div>

            <div id="department_field" class="form-group">
                <label for="department_id">انتخاب بخش</label>
                <select name="department_id" id="department_id" class="form-control">
                     <option value="">-- انتخاب کنید --</option>
                    <?php mysqli_data_seek($departments, 0); while($dept = mysqli_fetch_assoc($departments)): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="user_field" class="form-group" style="display: none;">
                <label for="user_id">انتخاب کاربر</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">-- انتخاب کنید --</option>
                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <input type="submit" name="create_task" class="btn btn-primary" value="ایجاد و ارجاع وظیفه">
            </div>
        </form>
    </div>
</div>

<script>
function toggleAssignFields() {
    const assignType = document.querySelector('input[name="assign_type"]:checked').value;
    const deptField = document.getElementById('department_field');
    const userField = document.getElementById('user_field');

    if (assignType === 'department') {
        deptField.style.display = 'block';
        userField.style.display = 'none';
    } else {
        deptField.style.display = 'none';
        userField.style.display = 'block';
    }
}
</script>


<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php"; ?>
