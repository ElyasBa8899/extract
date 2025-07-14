<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php"; // Include functions file
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('manage_users'); // Assuming only users who can manage users can manage classes

$err = $success_msg = "";

// Handle Add Class POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);

    if (empty($class_name)) {
        $err = "نام کلاس نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO classes (class_name, description) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $class_name, $description);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "کلاس جدید با موفقیت ایجاد شد.";
            } else {
                $err = "خطا در ایجاد کلاس.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all existing classes with their teachers
$classes = [];
$sql_classes = "
    SELECT
        c.id,
        c.class_name,
        c.description,
        c.status,
        GROUP_CONCAT(DISTINCT u.first_name, ' ', u.last_name SEPARATOR ', ') as teachers
    FROM classes c
    LEFT JOIN class_teachers ct ON c.id = ct.class_id
    LEFT JOIN users u ON ct.teacher_id = u.id
    GROUP BY c.id
    ORDER BY c.class_name ASC
";
if($result = mysqli_query($link, $sql_classes)){
    $classes = mysqli_fetch_all($result, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت کلاس‌ها</h2>
    <p>در این بخش، کلاس‌های درسی را تعریف و مدیریت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ایجاد کلاس جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="class_name">نام کلاس</label>
                <input type="text" name="class_name" id="class_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">توضیحات</label>
                <input type="text" name="description" id="description" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_class" class="btn btn-primary" value="ایجاد کلاس">
            </div>
        </form>
    </div>

    <div class="table-container">
        <h3>لیست کلاس‌ها</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام کلاس</th>
                        <th>توضیحات</th>
                            <th>مدرس(ها)</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($classes)): ?>
                        <tr><td colspan="5">هیچ کلاسی یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['description']); ?></td>
                                <td><?php echo htmlspecialchars($class['teachers'] ?? '---'); ?></td>
                                <td><?php echo translate_class_status($class['status']); ?></td>
                                <td>
                                    <a href="edit_class.php?class_id=<?php echo $class['id']; ?>" class="btn btn-warning btn-sm">ویرایش</a>
                                    <a href="manage_class_students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-info btn-sm">دانش‌آموزان</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// mysqli_close($link);
require_once "../includes/footer.php";
?>
