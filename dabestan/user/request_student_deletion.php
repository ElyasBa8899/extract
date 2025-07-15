<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $student_source = $_POST['student_source']; // 'teacher' or 'admin'
    $class_id = $_POST['class_id'];
    $reason = trim($_POST['deletion_reason']);
    $requester_id = $_SESSION['id'];

    if (empty($reason)) {
        header("location: manage_class_students.php?class_id={$class_id}&error=reason_required");
        exit;
    }

    // --- Security Check: Requester must be a teacher of the class ---
    $is_teacher_q = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id AND teacher_id = $requester_id");
    if (mysqli_num_rows($is_teacher_q) == 0) {
        die("دسترسی غیرمجاز.");
    }
    // ---------------------------------------------------------------

    // --- Get Student Name ---
    $student_name = "";
    if ($student_source === 'teacher') {
        $q = mysqli_query($link, "SELECT student_name FROM class_students WHERE id = $student_id");
        if ($res = mysqli_fetch_assoc($q)) $student_name = $res['student_name'];
    } else { // admin
        $q = mysqli_query($link, "SELECT student_name FROM recruited_students WHERE id = $student_id");
        if ($res = mysqli_fetch_assoc($q)) $student_name = $res['student_name'];
    }
    if (empty($student_name)) {
         header("location: manage_class_students.php?class_id={$class_id}&error=student_not_found");
         exit;
    }
    // -------------------------

    // --- Create Deletion Request ---
    $sql = "INSERT INTO student_deletion_requests (student_id, student_name, student_source, class_id, reason, requested_by) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "issisi", $student_id, $student_name, $student_source, $class_id, $reason, $requester_id);
        if (mysqli_stmt_execute($stmt)) {
            // --- Notify Admins ---
            $class_info_q = mysqli_query($link, "SELECT class_name FROM classes WHERE id = $class_id");
            $class_name = mysqli_fetch_assoc($class_info_q)['class_name'];

            $notif_message = "درخواست حذف متربی '{$student_name}' از کلاس '{$class_name}' ثبت شد.";
            $notif_link = "/admin/manage_deletion_requests.php"; // This page needs to be created

            // Send notification to all admins
            $admins_q = mysqli_query($link, "SELECT id FROM users WHERE is_admin = 1");
            while ($admin = mysqli_fetch_assoc($admins_q)) {
                $sql_notif = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                if($stmt_notif = mysqli_prepare($link, $sql_notif)){
                    mysqli_stmt_bind_param($stmt_notif, "iss", $admin['id'], $notif_message, $notif_link);
                    mysqli_stmt_execute($stmt_notif);
                }
            }
            // ---------------------

            header("location: manage_class_students.php?class_id={$class_id}&success=request_sent");
        } else {
            header("location: manage_class_students.php?class_id={$class_id}&error=request_failed");
        }
        mysqli_stmt_close($stmt);
    }
    // -----------------------------
    exit;
}
?>
