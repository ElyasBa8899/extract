<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header("location: my_classes.php");
    exit;
}

$class_id = $_GET['class_id'];
$user_id = $_SESSION['id'];
$err = $success_msg = "";

// Security Check: Ensure the user is a teacher of this class
$is_teacher_q = mysqli_query($link, "SELECT * FROM class_teachers WHERE class_id = $class_id AND teacher_id = $user_id");
if(mysqli_num_rows($is_teacher_q) == 0) {
    echo "دسترسی غیرمجاز.";
    exit;
}

// Fetch class details
$class_query = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id");
$class = mysqli_fetch_assoc($class_query);

// Handle Update POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_class'])) {
    $description = trim($_POST['description']);
    // Add other fields a teacher is allowed to edit, e.g., class time, etc.

    // Confirmation Step
    if (isset($_POST['confirm_update'])) {
        $sql = "UPDATE classes SET description = ? WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $description, $class_id);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "اطلاعات کلاس با موفقیت به‌روزرسانی شد.";
                // Refresh data
                $class_query = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id");
                $class = mysqli_fetch_assoc($class_query);
            } else {
                $err = "خطا در به‌روزرسانی اطلاعات.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Show confirmation dialog
        $confirm_dialog = true;
        $changes_to_confirm = [
            'توضیحات' => $description
        ];
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="my_classes.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به لیست کلاس‌ها</a>
    <h2>ویرایش اطلاعات کلاس: <?php echo htmlspecialchars($class['class_name']); ?></h2>

    <?php if (isset($confirm_dialog) && $confirm_dialog === true): ?>
        <div class="alert alert-warning">
            <h4>تایید تغییرات</h4>
            <p>آیا از اعمال تغییرات زیر مطمئن هستید؟</p>
            <ul>
                <?php foreach($changes_to_confirm as $key => $value): ?>
                    <li><strong><?php echo $key; ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                <?php endforeach; ?>
            </ul>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?class_id=<?php echo $class_id; ?>" method="post">
                <input type="hidden" name="description" value="<?php echo htmlspecialchars($description); ?>">
                <input type="hidden" name="confirm_update" value="1">
                <input type="submit" name="update_class" class="btn btn-primary" value="بله، تایید و ذخیره">
                <a href="edit_my_class.php?class_id=<?php echo $class_id; ?>" class="btn btn-secondary">خیر، لغو</a>
            </form>
        </div>
    <?php else: ?>
        <?php
        if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
        if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
        ?>
        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?class_id=<?php echo $class_id; ?>" method="post">
                <div class="form-group">
                    <label for="description">توضیحات کلاس</label>
                    <textarea name="description" id="description" class="form-control" rows="4"><?php echo htmlspecialchars($class['description']); ?></textarea>
                </div>
                <!-- Add other editable fields here -->
                <div class="form-group">
                    <input type="submit" name="update_class" class="btn btn-primary" value="ذخیره تغییرات">
                </div>
            </form>
        </div>

        <hr style="margin: 40px 0;">

        <div class="table-container">
            <h3>مدیریت دانش‌آموزان کلاس</h3>
            <!-- Add student form -->
            <form action="add_student_to_class.php" method="post" style="margin-bottom: 20px;">
                 <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                 <div class="form-group">
                     <label for="student_name">نام و نام خانوادگی دانش‌آموز جدید:</label>
                     <input type="text" name="student_name" id="student_name" class="form-control" required>
                 </div>
                 <button type="submit" class="btn btn-success">افزودن دانش‌آموز</button>
            </form>

            <!-- List of current students -->
            <h4>لیست دانش‌آموزان فعلی</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>نام دانش‌آموز</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch students for this class
                    // This assumes a 'students' table linked to classes
                    // This is a placeholder for the actual implementation
                    ?>
                    <tr>
                        <td>دانش‌آموز نمونه ۱</td>
                        <td><a href="#" class="btn btn-danger btn-sm">حذف</a></td>
                    </tr>
                     <tr>
                        <td>دانش‌آموز نمونه ۲</td>
                        <td><a href="#" class="btn btn-danger btn-sm">حذف</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
