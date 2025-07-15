<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('manage_events');

$err = $success_msg = "";

// Handle Add Event POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    $event_name = trim($_POST['event_name']);
    $event_year = trim($_POST['event_year']);
    $description = trim($_POST['description']);
    $proposal = trim($_POST['proposal']);
    $required_workforce = trim($_POST['required_workforce']);
    $required_budget = trim($_POST['required_budget']);

    if (empty($event_name)) {
        $err = "نام رویداد الزامی است.";
    } else {
        $sql = "INSERT INTO general_events (event_name, event_year, description, proposal, required_workforce, required_budget, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sisssdi", $event_name, $event_year, $description, $proposal, $required_workforce, $required_budget, $_SESSION['id']);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "رویداد عمومی جدید با موفقیت ثبت شد.";
            } else {
                $err = "خطا در ثبت رویداد.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all existing events
$events = [];
$sql_events = "SELECT id, event_name, event_year, status FROM general_events ORDER BY event_year DESC, event_name ASC";
$result_events = mysqli_query($link, $sql_events);
if($result_events){
    $events = mysqli_fetch_all($result_events, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت مناسبت‌ها و پروژه‌های عمومی</h2>
    <p>در این بخش پروژه‌های بزرگ و عمومی (مانند جشن نیمه شعبان، غدیر و...) را ثبت و آرشیو کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Event Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ثبت پروژه/رویداد جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="event_name">نام رویداد <span style="color: red;">*</span></label>
                <input type="text" name="event_name" id="event_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="event_year">سال برگزاری</label>
                <input type="number" name="event_year" id="event_year" class="form-control" placeholder="<?php echo date('Y') + 621; // Jalali year approx ?>">
            </div>
            <div class="form-group">
                <label for="description">توضیحات پروژه</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="proposal">پروپوزال</label>
                <textarea name="proposal" id="proposal" class="form-control" rows="5"></textarea>
            </div>
            <div class="form-group">
                <label for="required_workforce">نیروی انسانی مورد نیاز</label>
                <textarea name="required_workforce" id="required_workforce" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="required_budget">مبلغ مورد نیاز (تومان)</label>
                <input type="number" step="0.01" name="required_budget" id="required_budget" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_event" class="btn btn-primary" value="ثبت رویداد">
            </div>
        </form>
    </div>

    <!-- List of Existing Events -->
    <div class="table-container">
        <h3>آرشیو رویدادها</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام رویداد</th>
                        <th>سال</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="4" style="text-align: center;">هیچ رویدادی ثبت نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_year']); ?></td>
                                <td><?php echo htmlspecialchars($event['status'] ?? '<i>نامشخص</i>'); ?></td>
                                <td>
                                    <a href="#" class="btn btn-primary btn-sm">مشاهده جزئیات</a>
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
mysqli_close($link);
require_once "../includes/footer.php";
?>
