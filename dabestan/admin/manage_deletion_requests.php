<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Handle POST request to approve/reject a request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $admin_id = $_SESSION['id'];

    mysqli_begin_transaction($link);
    try {
        // 1. Get request details
        $req_q = mysqli_query($link, "SELECT * FROM student_deletion_requests WHERE id = $request_id AND status = 'pending'");
        if (mysqli_num_rows($req_q) == 0) {
            throw new Exception("درخواست یافت نشد یا قبلاً بررسی شده است.");
        }
        $request = mysqli_fetch_assoc($req_q);
        $student_id = $request['student_id'];
        $student_source = $request['student_source'];
        $class_id = $request['class_id'];

        if ($action === 'approve') {
            // 2.A. Delete the student from the corresponding table
            if ($student_source === 'teacher') {
                $sql_delete = "DELETE FROM class_students WHERE id = ?";
            } else { // 'admin'
                // Instead of deleting from recruited_students, we just remove the class link
                $sql_delete = "UPDATE recruited_students SET class_id = NULL, notes = CONCAT(IFNULL(notes,''), '\nحذف شده از کلاس ID: $class_id توسط ادمین در تاریخ " . date('Y-m-d') . " به دلیل: " . $request['reason'] . "') WHERE id = ?";
            }
            $stmt_delete = mysqli_prepare($link, $sql_delete);
            mysqli_stmt_bind_param($stmt_delete, "i", $student_id);
            if (!mysqli_stmt_execute($stmt_delete)) {
                 throw new Exception("خطا در حذف متربی.");
            }

            // 3.A. Update the request status to 'approved'
            $sql_update = "UPDATE student_deletion_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
            $success_msg = "درخواست با موفقیت تایید و متربی حذف شد.";

        } elseif ($action === 'reject') {
            // 3.B. Update the request status to 'rejected'
            $sql_update = "UPDATE student_deletion_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
            $success_msg = "درخواست با موفقیت رد شد.";
        } else {
             throw new Exception("عملیات نامعتبر.");
        }

        $stmt_update = mysqli_prepare($link, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ii", $admin_id, $request_id);
         if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("خطا در به‌روزرسانی وضعیت درخواست.");
        }

        // 4. Notify the teacher who made the request
        $teacher_id = $request['requested_by'];
        $status_text = ($action === 'approve') ? 'تایید' : 'رد';
        $notif_message = "درخواست شما برای حذف متربی '{$request['student_name']}' {$status_text} شد.";
        $notif_link = "/user/manage_class_students.php?class_id=" . $class_id;
        $sql_notify = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
        $stmt_notify = mysqli_prepare($link, $sql_notify);
        mysqli_stmt_bind_param($stmt_notify, "iss", $teacher_id, $notif_message, $notif_link);
        mysqli_stmt_execute($stmt_notify);


        mysqli_commit($link);

    } catch (Exception $e) {
        mysqli_rollback($link);
        $err = $e->getMessage();
    }
}


// Fetch pending requests
$pending_requests_q = mysqli_query($link, "
    SELECT sdr.*, c.class_name, u.full_name as requested_by_name
    FROM student_deletion_requests sdr
    JOIN classes c ON sdr.class_id = c.id
    JOIN users u ON sdr.requested_by = u.id
    WHERE sdr.status = 'pending'
    ORDER BY sdr.requested_at DESC
");
$pending_requests = mysqli_fetch_all($pending_requests_q, MYSQLI_ASSOC);


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت درخواست‌های حذف متربی</h2>
    <p>در این صفحه می‌توانید درخواست‌های ثبت شده توسط مدرسان برای حذف متربی از کلاس را مدیریت کنید.</p>

     <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="table-container">
        <h3>درخواست‌های در انتظار بررسی</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام متربی</th>
                        <th>کلاس</th>
                        <th>درخواست دهنده</th>
                        <th>دلیل حذف</th>
                        <th>تاریخ درخواست</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_requests)): ?>
                        <tr><td colspan="6">هیچ درخواست جدیدی برای بررسی وجود ندارد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['requested_by_name']); ?></td>
                                <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($request['reason']); ?></td>
                                <td><?php echo htmlspecialchars(jdf('Y/m/d H:i', strtotime($request['requested_at']))); ?></td>
                                <td>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" onclick="return confirm('آیا از تایید این درخواست و حذف متربی مطمئن هستید؟')">تایید</button>
                                    </form>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('آیا از رد این درخواست مطمئن هستید؟')">رد</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
     <!-- We can add other tables for Approved/Rejected requests history later if needed -->
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
