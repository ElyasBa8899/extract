<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
require_permission('manage_roles'); // Only users who can manage roles can manage departments

$err = $success_msg = "";

// Handle Add Department POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_department'])) {
    $department_name = trim($_POST['department_name']);
    $department_description = trim($_POST['department_description']);

    if (empty($department_name)) {
        $err = "نام بخش نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO departments (department_name, department_description) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $department_name, $department_description);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "بخش جدید با موفقیت ایجاد شد.";
            } else {
                $err = "خطا در ایجاد بخش.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle Delete Department Request
if (isset($_GET['delete_department'])) {
    $department_to_delete = $_GET['delete_department'];
    // We should add checks here (e.g., if users or tickets are assigned to it)
    $sql = "DELETE FROM departments WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $department_to_delete);
        if(mysqli_stmt_execute($stmt)){
            $success_msg = "بخش با موفقیت حذف شد.";
        } else {
            $err = "خطا در حذف بخش.";
        }
        mysqli_stmt_close($stmt);
    }
}


// Fetch all existing departments
$departments = [];
$sql_depts = "SELECT id, department_name, department_description FROM departments ORDER BY department_name ASC";
if($result = mysqli_query($link, $sql_depts)){
    $departments = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت بخش‌های سازمانی</h2>
    <p>در این بخش، بخش‌های مختلف سازمان (اولیا، پرورشی، نظارت و...) را تعریف کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ایجاد بخش جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="department_name">نام بخش</label>
                <input type="text" name="department_name" id="department_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="department_description">توضیحات بخش</label>
                <input type="text" name="department_description" id="department_description" class="form-control">
            </div>
            <div class="form-group">
                <input type="submit" name="add_department" class="btn btn-primary" value="ایجاد بخش">
            </div>
        </form>
    </div>

    <div class="table-container">
        <h3>بخش‌های موجود</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>نام بخش</th>
                    <th>توضیحات</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $department): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($department['department_description']); ?></td>
                        <td>
                            <a href="manage_departments.php?delete_department=<?php echo $department['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این بخش مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
