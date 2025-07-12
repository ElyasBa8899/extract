<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Fetch departments for the dropdown
$departments = [];
// First, add a general option
$departments[] = ['id' => null, 'department_name' => 'عمومی (بدون ارجاع)'];
// Then fetch from DB
$sql_depts = "SELECT id, department_name FROM departments ORDER BY department_name ASC";
if($result_depts = mysqli_query($link, $sql_depts)){
    while($row = mysqli_fetch_assoc($result_depts)){
        $departments[] = $row;
    }
}

// Handle New Ticket POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_ticket'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
    $status = trim($_POST['priority']); // urgent or open

    if (empty($title) || empty($message)) {
        $err = "عنوان و متن پیام الزامی است.";
    } else {
        $sql = "INSERT INTO tickets (title, message, user_id, assigned_to_department_id, status) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssiis", $title, $message, $_SESSION['id'], $department_id, $status);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "تیکت شما با موفقیت ثبت شد. به زودی پاسخ داده خواهد شد.";
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
                <label for="department_id">ارجاع به بخش</label>
                <select name="department_id" id="department_id" class="form-control">
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endforeach; ?>
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

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
