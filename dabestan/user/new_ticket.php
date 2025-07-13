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
    $status = trim($_POST['priority']); // urgent or open
    $assign_type = $_POST['assign_type'];

    $department_id = null;
    $assigned_user_id = null;

    if($assign_type == 'department'){
        $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
    } else {
        $assigned_user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
    }

    if (empty($title) || empty($message)) {
        $err = "عنوان و متن پیام الزامی است.";
    } else {
        $sql = "INSERT INTO tickets (title, message, user_id, assigned_to_department_id, assigned_to_user_id, status) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssiiis", $title, $message, $_SESSION['id'], $department_id, $assigned_user_id, $status);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "تیکت شما با موفقیت ثبت شد.";

                // Send Telegram Notification
                require_once '../includes/telegram_bot.php';
                $message = "✅ تیکت جدید با عنوان \"<b>" . htmlspecialchars($title) . "</b>\" توسط شما ثبت شد.";
                // We need to get the user's chat_id
                $user_info_query = mysqli_query($link, "SELECT telegram_chat_id FROM users WHERE id = {$_SESSION['id']}");
                if($user_info = mysqli_fetch_assoc($user_info_query)){
                    sendTelegramMessage($user_info['telegram_chat_id'], $message);
                }

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
                <label for="department_id">ارسال به</label>
                <select name="department_id" id="department_id" class="form-control">
                    <option value="0">ادمین کل</option>
                    <?php while($dept = mysqli_fetch_assoc($departments)): ?>
                        <option value="<?php echo $dept['id']; ?>">بخش <?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>اولویت <span style="color: red;">*</span></label>
                <div class="radio-group">
                    <input type="radio" name="priority" value="open" id="priority_normal" checked> <label for="priority_normal">عادی</label>
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
mysqli_close($link);
require_once "../includes/footer.php";
?>
