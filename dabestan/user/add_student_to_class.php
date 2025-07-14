<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class_id'];
    $student_name = trim($_POST['student_name']);

    $phone_number = trim($_POST['phone_number']);

    $user_id = $_SESSION['id'];

    // Security Check: Ensure the user is a teacher of this class
    $is_teacher_q = mysqli_query($link, "SELECT * FROM class_teachers WHERE class_id = $class_id AND teacher_id = $user_id");
    if(mysqli_num_rows($is_teacher_q) == 0) {
        die("دسترسی غیرمجاز.");
    }

    if (empty($student_name)) {
        header("location: edit_my_class.php?class_id={$class_id}&error=empty_name");
        exit;
    }

    // --- Main Logic ---
    // 1. Add the student to the class_students table

    $sql_add = "INSERT INTO class_students (class_id, student_name, phone_number) VALUES (?, ?, ?)";
    $stmt_add = mysqli_prepare($link, $sql_add);
    mysqli_stmt_bind_param($stmt_add, "iss", $class_id, $student_name, $phone_number);

    mysqli_stmt_execute($stmt_add);
    mysqli_stmt_close($stmt_add);

    // 2. Check if this student exists in the recruited_students table
    $recruited_q = mysqli_query($link, "SELECT * FROM recruited_students WHERE student_name = '{$student_name}' AND class_id IS NULL");

    // 3. If they existed in the recruited list, notify the admin/recruitment head
    if (mysqli_num_rows($recruited_q) > 0) {
        $recruited_student = mysqli_fetch_assoc($recruited_q);

        // Let's create a notification for the admin
        $admin_id = 1; // Assuming admin user has ID 1
        $notif_message = "متربی '{$student_name}' که در لیست جذب بود، توسط مدرس به کلاس اضافه شد. لطفاً وضعیت او را در لیست جذب بررسی کنید.";
        $notif_link = "admin/view_region_students.php?region_id=" . $recruited_student['region_id'];

        $sql_notif = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
        if($stmt_notif = mysqli_prepare($link, $sql_notif)){
            mysqli_stmt_bind_param($stmt_notif, "iss", $admin_id, $notif_message, $notif_link);
            mysqli_stmt_execute($stmt_notif);
            mysqli_stmt_close($stmt_notif);
        }
    }

    // Redirect back with success message
    header("location: edit_my_class.php?class_id={$class_id}&success=student_added");
    exit;
}
?>
