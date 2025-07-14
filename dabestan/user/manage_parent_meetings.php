<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
// We will add role-based access control later.

$err = $success_msg = "";

// Fetch classes for the dropdown
$classes_result = mysqli_query($link, "SELECT id, class_name FROM classes WHERE status = 'active' ORDER BY class_name ASC");

// Handle Add Meeting POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_meeting'])) {
    $class_id = trim($_POST['class_id']);
    $meeting_date = trim($_POST['meeting_date']);
    $location = trim($_POST['location']);
    $speaker = trim($_POST['speaker']);

    if (empty($class_id) || empty($meeting_date)) {
        $err = "انتخاب کلاس و تاریخ جلسه الزامی است.";
    } else {
        $sql = "INSERT INTO parent_meetings (class_id, meeting_date, location, speaker, created_by) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isssi", $class_id, $meeting_date, $location, $speaker, $_SESSION['id']);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "جلسه اولیا با موفقیت ثبت شد.";
            } else {
                $err = "خطا در ثبت جلسه.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all existing meetings
$meetings = [];
$sql_meetings = "SELECT pm.id, pm.meeting_date, pm.location, pm.speaker, pm.status, c.class_name
                 FROM parent_meetings pm
                 JOIN classes c ON pm.class_id = c.id
                 ORDER BY pm.meeting_date DESC";
$result_meetings = mysqli_query($link, $sql_meetings);
if($result_meetings){
    $meetings = mysqli_fetch_all($result_meetings, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت جلسات اولیا</h2>
    <p>در این بخش جلسات اولیا برای کلاس‌های مختلف را برنامه‌ریزی و مدیریت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Meeting Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>برنامه‌ریزی جلسه جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="class_id">برای کلاس <span style="color: red;">*</span></label>
                <select name="class_id" id="class_id" class="form-control" required>
                    <option value="">انتخاب کنید...</option>
                    <?php while($class = mysqli_fetch_assoc($classes_result)): ?>
                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="meeting_date">تاریخ و زمان جلسه <span style="color: red;">*</span></label>
                <input type="text" name="meeting_date" id="meeting_date_persian" class="form-control" required>
                <input type="hidden" name="meeting_date_gregorian" id="meeting_date_gregorian">
            </div>
            <div class="form-group">
                <label for="location">مکان جلسه</label>
                <input type="text" name="location" id="location" class="form-control">
            </div>
            <div class="form-group">
                <label for="speaker">نام سخنران</label>
                <input type="text" name="speaker" id="speaker" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_meeting" class="btn btn-primary" value="ثبت جلسه">
            </div>
        </form>
    </div>

    <!-- List of Existing Meetings -->
    <div class="table-container">
        <h3>جلسات ثبت شده</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>کلاس</th>
                    <th>تاریخ و زمان</th>
                    <th>وضعیت</th>
                    <th>سخنران</th>
                    <th>مکان</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($meetings)): ?>
                    <tr><td colspan="6" style="text-align: center;">هیچ جلسه‌ای ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($meetings as $meeting): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($meeting['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['meeting_date']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['status']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['speaker']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['location']); ?></td>
                            <td>
                                <!-- We'll add functionality to these buttons later -->
                                <a href="#" class="btn btn-info btn-sm">ثبت گزارش‌ها</a>
                                <a href="#" class="btn btn-secondary btn-sm">ویرایش</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#meeting_date_persian").pDatepicker({
        format: 'YYYY/MM/DD HH:mm',
        altField: '#meeting_date_gregorian',
        altFormat: 'YYYY-MM-DD HH:mm:ss', // Correct format for MySQL DATETIME
        timePicker: {
            enabled: true,
            meridiem: {
                enabled: false
            }
        },
        toolbox: {
            enabled: true,
            calendarSwitch: {
                enabled: false,
            }
        }
    });
});
</script>

<?php
require_once "../includes/footer.php";
?>
