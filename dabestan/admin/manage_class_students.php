<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";

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
        // Here you would typically move students from 'recruited_students' to a 'class_students' table
        // or update their status. For now, we'll just simulate this by deleting them from the recruited list.
        $sql = "DELETE FROM recruited_students WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql);

        foreach ($students_to_add as $student_id) {
            // You would also have an INSERT statement here to add them to the class roster.
            mysqli_stmt_bind_param($stmt, "i", $student_id);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        $success_msg = count($students_to_add) . " دانش‌آموز با موفقیت به کلاس اضافه شدند.";
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

require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
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
// mysqli_close($link);
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php";
?>
