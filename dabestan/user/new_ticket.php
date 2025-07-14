<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Fetch departments and users for dropdowns
$departments = mysqli_query($link, "SELECT id, department_name FROM departments ORDER BY department_name ASC");
// Fetch only admin users for direct assignment
$admins = mysqli_query($link, "SELECT id, username FROM users WHERE is_admin = 1 ORDER BY username ASC");


// Handle New Ticket POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_ticket'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $priority = trim($_POST['priority']); // urgent or normal
    $assign_type = $_POST['assign_type'];

    $department_id = null;
    $assigned_user_id = null;

    if($assign_type == 'department'){
        $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        if(empty($department_id)){
            $err = "لطفاً یک بخش برای ارجاع انتخاب کنید.";
        }
    } else { // user
        $assigned_user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
         if(empty($assigned_user_id)){
            $err = "لطفاً یک کاربر برای ارجاع انتخاب کنید.";
        }
    }

    if (empty($title) || empty($message)) {
        $err = "عنوان و متن پیام الزامی است.";
    }

    if (empty($err)) {
        $sql = "INSERT INTO tickets (title, message, user_id, assigned_to_department_id, assigned_to_user_id, priority, status) VALUES (?, ?, ?, ?, ?, ?, 'open')";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssiiis", $title, $message, $_SESSION['id'], $department_id, $assigned_user_id, $priority);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "تیکت شما با موفقیت ثبت شد.";

                $ticket_id = mysqli_insert_id($link); // Corrected: Pass the connection link, not the statement

                // --- Create In-App Notification ---
                $notification_message = "تیکت جدیدی با عنوان '" . htmlspecialchars($title) . "' برای شما ثبت شد.";
                $notification_link = "/user/view_ticket.php?id=" . $ticket_id;

                $target_user_ids = [];
                if ($assigned_user_id) {
                    $target_user_ids[] = $assigned_user_id;
                } elseif ($department_id) {
                    // Find all users in the department
                    $sql_users = "SELECT user_id FROM user_departments WHERE department_id = ?";
                    if ($stmt_users = mysqli_prepare($link, $sql_users)) {
                        mysqli_stmt_bind_param($stmt_users, "i", $department_id);
                        mysqli_stmt_execute($stmt_users);
                        $result_users = mysqli_stmt_get_result($stmt_users);
                        while ($row = mysqli_fetch_assoc($result_users)) {
                            $target_user_ids[] = $row['user_id'];
                        }
                        mysqli_stmt_close($stmt_users);
                    }
                }

                // Insert notification for each target user
                if (!empty($target_user_ids)) {
                    $sql_notify = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                    if ($stmt_notify = mysqli_prepare($link, $sql_notify)) {
                        foreach ($target_user_ids as $target_id) {
                            mysqli_stmt_bind_param($stmt_notify, "iss", $target_id, $notification_message, $notification_link);
                            mysqli_stmt_execute($stmt_notify);
                        }
                        mysqli_stmt_close($stmt_notify);
                    }
                }
                // --- End In-App Notification ---

            } else {
                $err = "خطا در ثبت تیکت.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>ایجاد تیکت جدید</h2>
    <p>برای ارسال پیام، درخواست یا ارجاع کار، فرم زیر را تکمیل کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="title">عنوان <span style="color: red;">*</span></label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="message">متن پیام/درخواست <span style="color: red;">*</span></label>
                <textarea name="message" id="message" class="form-control" rows="6" required></textarea>
            </div>

            <div class="form-group">
                <label>نوع ارجاع</label>
                 <div class="radio-group">
                    <input type="radio" name="assign_type" value="department" id="assign_type_dept" checked onchange="toggleAssignFields()"> <label for="assign_type_dept">ارجاع به بخش</label>
                </div>
                <div class="radio-group">
                    <input type="radio" name="assign_type" value="user" id="assign_type_user" onchange="toggleAssignFields()"> <label for="assign_type_user">ارجاع به کاربر خاص (ادمین)</label>
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
                    <?php while($admin = mysqli_fetch_assoc($admins)): ?>
                        <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>


            <div class="form-group">
                <label>اولویت <span style="color: red;">*</span></label>
                <div class="radio-group">
                    <input type="radio" name="priority" value="normal" id="priority_normal" checked> <label for="priority_normal">عادی</label>
                </div>
                <div class="radio-group">
                    <input type="radio" name="priority" value="urgent" id="priority_urgent"> <label for="priority_urgent">فوری</label>
                </div>
            </div>
            <div class="form-group">
                <input type="submit" name="create_ticket" class="btn btn-primary" value="ارسال تیکت">
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

<?php
// mysqli_close($link);
require_once "../includes/footer.php";
?>
