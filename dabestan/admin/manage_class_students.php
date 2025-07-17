<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header("location: manage_classes.php");
    exit;
}

$class_id = $_GET['class_id'];
$err = $success_msg = "";

// Fetch class details
$class_query = mysqli_query($link, "SELECT class_name, region_id FROM classes WHERE id = $class_id");
if(mysqli_num_rows($class_query) == 0){
    header("location: manage_classes.php");
    exit;
}
$class = mysqli_fetch_assoc($class_query);
$region_id = $class['region_id'];

// Handle Add Students to Class POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_students'])) {
    $students_to_add = $_POST['student_ids'] ?? [];

    if (!empty($students_to_add)) {
        mysqli_begin_transaction($link);
        try {
            $sql_get_student = "SELECT * FROM recruited_students WHERE id = ?";
            $stmt_get_student = mysqli_prepare($link, $sql_get_student);

            $sql_add_to_class = "INSERT INTO class_students (class_id, student_name, added_by_user_id) VALUES (?, ?, ?)";
            $stmt_add_to_class = mysqli_prepare($link, $sql_add_to_class);

            $sql_update_recruited = "UPDATE recruited_students SET class_id = ? WHERE id = ?";
            $stmt_update_recruited = mysqli_prepare($link, $sql_update_recruited);

            $admin_id = $_SESSION['id'];

            foreach ($students_to_add as $student_id) {
                // Get student name
                mysqli_stmt_bind_param($stmt_get_student, "i", $student_id);
                mysqli_stmt_execute($stmt_get_student);
                $result = mysqli_stmt_get_result($stmt_get_student);
                $student = mysqli_fetch_assoc($result);
                $student_name = $student['student_name'];

                // Add to class_students
                mysqli_stmt_bind_param($stmt_add_to_class, "isi", $class_id, $student_name, $admin_id);
                mysqli_stmt_execute($stmt_add_to_class);

                // Update recruited_students to link them to the class
                mysqli_stmt_bind_param($stmt_update_recruited, "ii", $class_id, $student_id);
                mysqli_stmt_execute($stmt_update_recruited);
            }

            mysqli_stmt_close($stmt_get_student);
            mysqli_stmt_close($stmt_add_to_class);
            mysqli_stmt_close($stmt_update_recruited);

            mysqli_commit($link);
            $success_msg = count($students_to_add) . " دانش‌آموز با موفقیت به کلاس اضافه شدند و از لیست جذب به‌روزرسانی شدند.";
        } catch (Exception $e) {
            mysqli_rollback($link);
            $err = "خطا در اضافه کردن دانش‌آموزان: " . $e->getMessage();
        }
    }
}


// Fetch available students from the class's region
$available_students = [];
if ($region_id) {
    $sql_students = "SELECT id, student_name FROM recruited_students WHERE region_id = ?";
    if($stmt = mysqli_prepare($link, $sql_students)){
        mysqli_stmt_bind_param($stmt, "i", $region_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $available_students = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="edit_class.php?class_id=<?php echo $class_id; ?>" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به ویرایش کلاس</a>
    <h2>مدیریت دانش‌آموزان کلاس: <?php echo htmlspecialchars($class['class_name']); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <h3>افزودن دانش‌آموز از لیست جذب منطقه</h3>
        <?php if (!$region_id): ?>
            <div class="alert alert-warning">برای افزودن دانش‌آموز، ابتدا باید یک منطقه برای این کلاس در صفحه <a href="edit_class.php?class_id=<?php echo $class_id; ?>">ویرایش کلاس</a> مشخص کنید.</div>
        <?php elseif (empty($available_students)): ?>
            <div class="alert alert-info">هیچ دانش‌آموز جذب شده‌ای در منطقه مربوط به این کلاس برای افزودن وجود ندارد.</div>
        <?php else: ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?class_id=<?php echo $class_id; ?>" method="post">
                <div class="checkbox-grid">
                    <?php foreach($available_students as $student): ?>
                        <div class="checkbox-group">
                            <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" id="student_<?php echo $student['id']; ?>">
                            <label for="student_<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['student_name']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group" style="margin-top: 20px;">
                    <input type="submit" name="add_students" class="btn btn-primary" value="افزودن انتخاب شده‌ها به کلاس">
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Here you would typically list the students already in the class -->

</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
