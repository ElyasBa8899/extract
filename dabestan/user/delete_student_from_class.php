<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['student_id']) || !isset($_GET['class_id'])) {
    header("location: my_classes.php");
    exit;
}

$student_id = $_GET['student_id'];
$class_id = $_GET['class_id'];
$user_id = $_SESSION['id'];

// Security Check: Ensure the user is a teacher of this class
$is_teacher_q = mysqli_query($link, "SELECT * FROM class_teachers WHERE class_id = $class_id AND teacher_id = $user_id");
if(mysqli_num_rows($is_teacher_q) == 0) {
    die("دسترسی غیرمجاز.");
}

// Delete the student from the class roster
$sql = "DELETE FROM class_students WHERE id = ? AND class_id = ?";
if($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $class_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Redirect back to the class editing page
header("location: edit_my_class.php?class_id={$class_id}&success=student_deleted");
exit;
?>
