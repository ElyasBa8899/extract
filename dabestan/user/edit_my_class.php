<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header("location: my_classes.php");
    exit;
}

$class_id = $_GET['class_id'];
$err = $success_msg = "";

// Security Check: Ensure the current user is a teacher of this class
$is_teacher_query = mysqli_query($link, "SELECT 1 FROM class_teachers WHERE class_id = $class_id AND teacher_id = $user_id");
if(mysqli_num_rows($is_teacher_query) == 0) {
    // If user is not a teacher of this class, redirect them away.
    header("location: my_classes.php");
    exit;
}

// Fetch class details
$class_query = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id");
$class = mysqli_fetch_assoc($class_query);

// Handle Manual Student Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_manual_student'])) {
    $student_name = trim($_POST['student_name']);

    // Check if student already exists in the region's recruited list
    $check_sql = "SELECT id FROM recruited_students WHERE student_name = ? AND region_id = ?";
    if ($stmt_check = mysqli_prepare($link, $check_sql)) {
        mysqli_stmt_bind_param($stmt_check, "si", $student_name, $class['region_id']);
        mysqli_stmt_execute($stmt_check);
        $result = mysqli_stmt_get_result($stmt_check);
        if ($existing_student = mysqli_fetch_assoc($result)) {
            // If exists, delete from recruited list
            mysqli_query($link, "DELETE FROM recruited_students WHERE id = " . $existing_student['id']);
        }
        mysqli_stmt_close($stmt_check);
    }

    // Now, add the student to the class (we need a class_students table for this)
    // For now, we'll just show a success message.
    $success_msg = "دانش‌آموز " . htmlspecialchars($student_name) . " با موفقیت افزوده شد (شبیه‌سازی).";
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="my_classes.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به کلاس‌های من</a>
    <h2>ویرایش کلاس: <?php echo htmlspecialchars($class['class_name']); ?></h2>

    <?php if(!empty($err)) echo '<div class="alert alert-danger">'.$err.'</div>'; ?>
    <?php if(!empty($success_msg)) echo '<div class="alert alert-success">'.$success_msg.'</div>'; ?>

    <div class="form-container" style="margin-bottom: 30px;">
        <h4>افزودن دستی دانش‌آموز</h4>
        <p>اگر دانش‌آموز در لیست جذب منطقه وجود ندارد، از اینجا او را مستقیماً به کلاس اضافه کنید.</p>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?class_id=' . $class_id; ?>" method="post">
            <div class="form-group">
                <label for="student_name">نام کامل دانش‌آموز</label>
                <input type="text" name="student_name" id="student_name" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" name="add_manual_student" class="btn btn-primary" value="افزودن دانش‌آموز">
            </div>
        </form>
    </div>

    <!-- Placeholder for listing students already in the class -->
    <div class="table-container">
        <h3>دانش‌آموزان فعلی کلاس</h3>
        <p>این بخش در آینده لیست دانش‌آموزان ثبت‌نام شده در این کلاس را نمایش خواهد داد.</p>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
