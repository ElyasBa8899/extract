<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('manage_users');

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header("location: manage_classes.php");
    exit;
}

$class_id = $_GET['class_id'];
$err = $success_msg = "";

// Fetch class details
$class_query = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id");
$class = mysqli_fetch_assoc($class_query);
if(!$class){ echo "کلاس یافت نشد."; exit; }

// Fetch all available teachers (non-admin users)
$teachers_query = mysqli_query($link, "SELECT id, first_name, last_name FROM users WHERE is_admin = 0 ORDER BY last_name ASC");
$all_teachers = mysqli_fetch_all($teachers_query, MYSQLI_ASSOC);

// Fetch teachers currently assigned to this class
$current_teachers_query = mysqli_query($link, "SELECT teacher_id FROM class_teachers WHERE class_id = $class_id");
$current_teachers = array_column(mysqli_fetch_all($current_teachers_query, MYSQLI_ASSOC), 'teacher_id');

// Handle Update POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_class'])) {
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    $new_teachers = $_POST['teachers'] ?? [];

    mysqli_begin_transaction($link);
    try {
        // 1. Update class info
        $sql_update_class = "UPDATE classes SET class_name = ?, description = ?, status = ? WHERE id = ?";
        $stmt_update_class = mysqli_prepare($link, $sql_update_class);
        mysqli_stmt_bind_param($stmt_update_class, "sssi", $class_name, $description, $status, $class_id);
        mysqli_stmt_execute($stmt_update_class);

        // 2. Delete old teacher assignments
        mysqli_query($link, "DELETE FROM class_teachers WHERE class_id = $class_id");

        // 3. Insert new ones
        if(!empty($new_teachers)){
            $sql_insert_teachers = "INSERT INTO class_teachers (class_id, teacher_id) VALUES (?, ?)";
            $stmt_insert_teachers = mysqli_prepare($link, $sql_insert_teachers);
            foreach($new_teachers as $teacher_id){
                mysqli_stmt_bind_param($stmt_insert_teachers, "ii", $class_id, $teacher_id);
                mysqli_stmt_execute($stmt_insert_teachers);
            }
            mysqli_stmt_close($stmt_insert_teachers);
        }

        mysqli_commit($link);
        $success_msg = "اطلاعات کلاس با موفقیت به‌روزرسانی شد.";
        // Refresh data for display
        $current_teachers_query = mysqli_query($link, "SELECT teacher_id FROM class_teachers WHERE class_id = $class_id");
        $current_teachers = array_column(mysqli_fetch_all($current_teachers_query, MYSQLI_ASSOC), 'teacher_id');

    } catch (Exception $e) {
        mysqli_rollback($link);
        $err = "خطا در به‌روزرسانی اطلاعات.";
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_classes.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت کلاس‌ها</a>
    <h2>ویرایش کلاس: <?php echo htmlspecialchars($class['class_name']); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <form action="edit_class.php?class_id=<?php echo $class_id; ?>" method="post">
        <div class="form-container" style="margin-bottom: 30px;">
            <h4>اطلاعات پایه کلاس</h4>
            <div class="form-group">
                <label>نام کلاس</label>
                <input type="text" name="class_name" class="form-control" value="<?php echo htmlspecialchars($class['class_name']); ?>">
            </div>
            <div class="form-group">
                <label>توضیحات</label>
                <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($class['description']); ?>">
            </div>
            <div class="form-group">
                <label>وضعیت</label>
                <select name="status" class="form-control">
                    <option value="active" <?php if($class['status'] == 'active') echo 'selected'; ?>>فعال</option>
                    <option value="inactive" <?php if($class['status'] == 'inactive') echo 'selected'; ?>>غیرفعال</option>
                    <option value="archived" <?php if($class['status'] == 'archived') echo 'selected'; ?>>آرشیو شده</option>
                </select>
            </div>
        </div>

        <div class="form-container">
            <h4>تخصیص مدرسین به این کلاس</h4>
            <?php foreach($all_teachers as $teacher): ?>
                <div class="checkbox-group">
                    <input type="checkbox" name="teachers[]" value="<?php echo $teacher['id']; ?>" id="teacher_<?php echo $teacher['id']; ?>"
                        <?php if(in_array($teacher['id'], $current_teachers)) echo 'checked'; ?>>
                    <label for="teacher_<?php echo $teacher['id']; ?>">
                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <input type="submit" name="update_class" class="btn btn-primary" value="ذخیره تغییرات">
        </div>
    </form>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
