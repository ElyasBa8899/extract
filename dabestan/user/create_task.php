<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
// require_permission('create_task');

$err = $success_msg = "";

// Fetch departments and users for assignment
$departments = mysqli_query($link, "SELECT id, department_name FROM departments ORDER BY department_name ASC");
$users = mysqli_query($link, "SELECT id, username FROM users ORDER BY username ASC");


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_task'])) {
    // TODO: Implement task creation logic
    $title = trim($_POST['title']);
    // ... and so on

    $err = "قابلیت ایجاد وظیفه هنوز پیاده‌سازی نشده است.";
}

require_once "../includes/header.php";
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


<?php require_once "../includes/footer.php"; ?>
