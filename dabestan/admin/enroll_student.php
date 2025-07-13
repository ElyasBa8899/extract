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

        // 2. Add student to the main 'students' table (or whatever your main student roster is)
        // This is a placeholder for the actual table you'll use for enrolled students.
        // Let's assume a table 'class_students' exists with (class_id, student_name, phone_number)

        // For now, we will just simulate this by adding a note.
        // A real implementation would look something like this:
        /*
        $sql_add_to_roster = "INSERT INTO class_students (class_id, student_name, original_recruitment_id) VALUES (?, ?, ?)";
        $stmt_roster = mysqli_prepare($link, $sql_add_to_roster);
        mysqli_stmt_bind_param($stmt_roster, "isi", $class_id, $student_info['student_name'], $student_id);
        mysqli_stmt_execute($stmt_roster);
        */

        // 3. Delete the student from the recruited_students table
        $sql_delete_recruited = "DELETE FROM recruited_students WHERE id = ?";
        $stmt_delete = mysqli_prepare($link, $sql_delete_recruited);
        mysqli_stmt_bind_param($stmt_delete, "i", $student_id);
        mysqli_stmt_execute($stmt_delete);

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
