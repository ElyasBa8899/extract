<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['region_id']) || empty($_GET['region_id'])) {
    header("location: manage_regions.php");
    exit;
}

$region_id = $_GET['region_id'];
$err = $success_msg = "";

// Fetch region details
$region_query = mysqli_query($link, "SELECT name FROM regions WHERE id = $region_id");
if(mysqli_num_rows($region_query) == 0){
    header("location: manage_regions.php");
    exit;
}
$region = mysqli_fetch_assoc($region_query);


// Handle Add Student POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_name = trim($_POST['student_name']);
    $phone_number = trim($_POST['phone_number']);
    // Add other fields as necessary from your initial description

    if (empty($student_name)) {
        $err = "نام دانش‌آموز نمی‌تواند خالی باشد.";
    } else {
        $sql = "INSERT INTO recruited_students (student_name, phone_number, region_id) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $student_name, $phone_number, $region_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "دانش‌آموز جدید با موفقیت به این منطقه اضافه شد.";
            } else {
                $err = "خطا در افزودن دانش‌آموز.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all students for this region
$students = [];
$sql_students = "SELECT * FROM recruited_students WHERE region_id = ? ORDER BY student_name ASC";
if($stmt = mysqli_prepare($link, $sql_students)){
    mysqli_stmt_bind_param($stmt, "i", $region_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_regions.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به لیست مناطق</a>
    <h2>دانش‌آموزان جذب شده در منطقه: <?php echo htmlspecialchars($region['name']); ?></h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Form to add new student -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن دانش‌آموز جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?region_id=<?php echo $region_id; ?>" method="post">
            <div class="form-group">
                <label for="student_name">نام دانش‌آموز</label>
                <input type="text" name="student_name" id="student_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="phone_number">شماره تماس</label>
                <input type="text" name="phone_number" id="phone_number" class="form-control">
            </div>
            <!-- Add other fields for student info here -->
            <div class="form-group">
                <input type="submit" name="add_student" class="btn btn-primary" value="افزودن دانش‌آموز">
            </div>
        </form>
    </div>

    <div class="table-container">
        <h3>لیست دانش‌آموزان</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام دانش‌آموز</th>
                        <th>شماره تماس</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="3">هیچ دانش‌آموزی در این منطقه ثبت نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning">ویرایش</a>
                                    <a href="#" class="btn btn-sm btn-danger">حذف</a>
                                    <a href="#" class="btn btn-sm btn-success">ثبت‌نام در کلاس</a>
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
mysqli_close($link);
require_once "../includes/footer.php";
?>
