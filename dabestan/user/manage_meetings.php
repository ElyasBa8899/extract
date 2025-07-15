<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
// We will add role-based access control later. For now, any logged-in user can see this.

$err = $success_msg = "";

// Handle Add Meeting POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_meeting'])) {
    $title = trim($_POST['title']);
    $meeting_date = trim($_POST['meeting_date']);
    $speaker = trim($_POST['speaker']);
    $location = trim($_POST['location']);

    if (empty($title) || empty($meeting_date)) {
        $err = "عنوان و تاریخ جلسه الزامی است.";
    } else {
        $sql = "INSERT INTO service_meetings (title, meeting_date, speaker, location, created_by) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $meeting_date, $speaker, $location, $_SESSION['id']);
            if (mysqli_stmt_execute($stmt)) {
                $new_meeting_id = mysqli_insert_id($link); // Corrected: use $link instead of $stmt
                // Here we can auto-populate the checklist for the new meeting
                // For now, we just show a success message.
                $success_msg = "جلسه جدید با موفقیت ثبت شد.";
            } else {
                $err = "خطا در ثبت جلسه.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all existing meetings
$meetings = [];
$sql = "SELECT id, title, meeting_date, speaker, location FROM service_meetings ORDER BY meeting_date DESC";
if($result = mysqli_query($link, $sql)){
    $meetings = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت جلسات ضمن خدمت</h2>
    <p>در این بخش جلسات هفتگی مدرسین را تعریف و مدیریت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Meeting Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ثبت جلسه جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="title">عنوان جلسه <span style="color: red;">*</span></label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="meeting_date">تاریخ جلسه <span style="color: red;">*</span></label>
                <input type="text" name="meeting_date" id="meeting_date_picker" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="speaker">نام استاد/سخنران</label>
                <input type="text" name="speaker" id="speaker" class="form-control">
            </div>
            <div class="form-group">
                <label for="location">مکان جلسه</label>
                <input type="text" name="location" id="location" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_meeting" class="btn btn-primary" value="ثبت جلسه">
            </div>
        </form>
    </div>

    <!-- List of Existing Meetings -->
    <div class="table-container">
        <h3>تقویم اجرایی (جلسات ثبت شده)</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                    <th>عنوان جلسه</th>
                    <th>تاریخ و زمان</th>
                    <th>سخنران</th>
                    <th>مکان</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($meetings)): ?>
                    <tr><td colspan="5" style="text-align: center;">هیچ جلسه‌ای ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($meetings as $meeting): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($meeting['title']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['meeting_date']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['speaker']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['location']); ?></td>
                            <td>
                                <a href="meeting_checklist.php?meeting_id=<?php echo $meeting['id']; ?>" class="btn btn-secondary btn-sm">چک‌لیست</a>
                                <a href="meeting_attendance.php?meeting_id=<?php echo $meeting['id']; ?>" class="btn btn-info btn-sm">حضور و غیاب</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
<script type="text/javascript">
    $(document).ready(function() {
        $("#meeting_date_picker").pDatepicker({
            format: 'YYYY-MM-DD',
            autoClose: true
        });
    });
</script>
