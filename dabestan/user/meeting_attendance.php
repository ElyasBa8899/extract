<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['meeting_id']) || empty($_GET['meeting_id'])) {
    header("location: manage_meetings.php");
    exit;
}

$meeting_id = $_GET['meeting_id'];
$err = $success_msg = "";

// Fetch meeting details
$meeting = null;
$sql_meeting = "SELECT title FROM service_meetings WHERE id = ?";
if($stmt_meeting = mysqli_prepare($link, $sql_meeting)){
    mysqli_stmt_bind_param($stmt_meeting, "i", $meeting_id);
    mysqli_stmt_execute($stmt_meeting);
    $result_meeting = mysqli_stmt_get_result($stmt_meeting);
    $meeting = mysqli_fetch_assoc($result_meeting);
    mysqli_stmt_close($stmt_meeting);
}
if(!$meeting){ echo "جلسه یافت نشد."; exit; }

// Fetch all users to display for attendance
$users = [];
$sql_users = "SELECT id, first_name, last_name FROM users WHERE is_admin = 0 ORDER BY last_name ASC"; // Excluding main admin for now
if($result_users = mysqli_query($link, $sql_users)){
    $users = mysqli_fetch_all($result_users, MYSQLI_ASSOC);
}

// Fetch existing attendance data for this meeting
$attendance_data = [];
$sql_attendance = "SELECT user_id, status FROM meeting_attendance WHERE meeting_id = ?";
if($stmt_attendance = mysqli_prepare($link, $sql_attendance)){
    mysqli_stmt_bind_param($stmt_attendance, "i", $meeting_id);
    mysqli_stmt_execute($stmt_attendance);
    $result_attendance = mysqli_stmt_get_result($stmt_attendance);
    while($row = mysqli_fetch_assoc($result_attendance)){
        $attendance_data[$row['user_id']] = $row['status'];
    }
    mysqli_stmt_close($stmt_attendance);
}


// Handle Attendance Update POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_attendance'])) {
    $attendance_statuses = $_POST['attendance'] ?? [];

    $sql = "INSERT INTO meeting_attendance (meeting_id, user_id, status) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_begin_transaction($link);
        try {
            foreach($attendance_statuses as $user_id => $status){
                if(!empty($status)){
                    mysqli_stmt_bind_param($stmt, "iis", $meeting_id, $user_id, $status);
                    mysqli_stmt_execute($stmt);
                }
            }
            mysqli_commit($link);
            $success_msg = "لیست حضور و غیاب با موفقیت به‌روزرسانی شد.";
            // Refresh attendance data after update
            if($stmt_attendance = mysqli_prepare($link, "SELECT user_id, status FROM meeting_attendance WHERE meeting_id = ?")){
                mysqli_stmt_bind_param($stmt_attendance, "i", $meeting_id);
                mysqli_stmt_execute($stmt_attendance);
                $result_attendance = mysqli_stmt_get_result($stmt_attendance);
                $attendance_data = [];
                while($row = mysqli_fetch_assoc($result_attendance)){
                    $attendance_data[$row['user_id']] = $row['status'];
                }
                mysqli_stmt_close($stmt_attendance);
            }
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($link);
            $err = "خطا در به‌روزرسانی لیست.";
        }
        mysqli_stmt_close($stmt);
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_meetings.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت جلسات</a>
    <h2>حضور و غیاب جلسه: <?php echo htmlspecialchars($meeting['title']); ?></h2>
    <p>وضعیت حضور شرکت‌کنندگان در جلسه را مشخص کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="meeting_attendance.php?meeting_id=<?php echo $meeting_id; ?>" method="post">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام شرکت‌کننده</th>
                        <th>وضعیت حضور</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user):
                        $user_id = $user['id'];
                        $current_status = $attendance_data[$user_id] ?? '';
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td>
                                <select name="attendance[<?php echo $user_id; ?>]" class="form-control">
                                    <option value="" <?php if($current_status == '') echo 'selected'; ?>>نامشخص</option>
                                    <option value="present" <?php if($current_status == 'present') echo 'selected'; ?>>حاضر</option>
                                    <option value="absent" <?php if($current_status == 'absent') echo 'selected'; ?>>غایب</option>
                                    <option value="justified_absence" <?php if($current_status == 'justified_absence') echo 'selected'; ?>>غیبت موجه</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-group" style="margin-top: 20px;">
                <input type="submit" name="update_attendance" class="btn btn-primary" value="ذخیره حضور و غیاب">
            </div>
        </form>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
