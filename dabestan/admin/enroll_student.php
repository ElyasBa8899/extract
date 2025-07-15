<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];
    $region_id = $_POST['region_id']; // To redirect back to the correct page

    if (empty($student_id) || empty($class_id)) {
        // Handle error
        header("location: view_region_students.php?region_id={$region_id}&error=missing_data");
        exit;
    }

    // This is a multi-step process, so we use a transaction
    mysqli_begin_transaction($link);

    try {
        // 1. Get student info from recruited_students
        $student_info_q = mysqli_query($link, "SELECT * FROM recruited_students WHERE id = $student_id");
        if (mysqli_num_rows($student_info_q) == 0) {
            throw new Exception("Student not found in recruitment list.");
        }
        $student_info = mysqli_fetch_assoc($student_info_q);

        // 2. Instead of deleting, we update the student's record to link them to the class.
        // This preserves their recruitment history.
        $sql_enroll = "UPDATE recruited_students SET class_id = ? WHERE id = ?";
        $stmt_enroll = mysqli_prepare($link, $sql_enroll);
        mysqli_stmt_bind_param($stmt_enroll, "ii", $class_id, $student_id);
        mysqli_stmt_execute($stmt_enroll);

        // If all queries were successful, commit the transaction
        mysqli_commit($link);

        // Redirect back with a success message
        header("location: view_region_students.php?region_id={$region_id}&success=enrolled");
        exit;

    } catch (Exception $e) {
        // If any query fails, roll back the transaction
        mysqli_rollback($link);
        // Redirect back with an error message
        header("location: view_region_students.php?region_id={$region_id}&error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Redirect if accessed directly
    header("location: manage_regions.php");
    exit;
}
?>
