<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$err = $success_msg = "";

// Fetch regions for the dropdown
$regions = [];
$sql_regions = "SELECT id, name FROM regions ORDER BY name ASC";
if($result = mysqli_query($link, $sql_regions)){
    if(mysqli_num_rows($result) > 0){
        $regions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// Handle Add Student POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_name = trim($_POST['student_name']);
    $parent_name = trim($_POST['parent_name']);
    $phone_number = trim($_POST['phone_number']);
    $region_id = trim($_POST['region_id']);
    $recruiter_name = trim($_POST['recruiter_name']);
    $event_name = trim($_POST['event_name']);
    $recruited_at = trim($_POST['recruited_at']);

    if (empty($student_name) || empty($region_id) || empty($recruited_at)) {
        $err = "نام دانش‌آموز، منطقه و تاریخ جذب الزامی است.";
    } else {
        $sql = "INSERT INTO recruited_students (student_name, parent_name, phone_number, region_id, recruiter_name, event_name, recruited_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssisss", $student_name, $parent_name, $phone_number, $region_id, $recruiter_name, $event_name, $recruited_at);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "دانش‌آموز جدید با موفقیت ثبت شد.";
            } else {
                $err = "خطا در ثبت اطلاعات دانش‌آموز.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>ثبت دانش‌آموز جدید</h2>
    <p>در این بخش اطلاعات دانش‌آموزانی که به تازگی جذب شده‌اند را وارد کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="student_name">نام دانش‌آموز <span style="color: red;">*</span></label>
                <input type="text" name="student_name" id="student_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="parent_name">نام والدین</label>
                <input type="text" name="parent_name" id="parent_name" class="form-control">
            </div>
            <div class="form-group">
                <label for="phone_number">شماره تماس</label>
                <input type="text" name="phone_number" id="phone_number" class="form-control">
            </div>
            <div class="form-group">
                <label for="region_id">منطقه <span style="color: red;">*</span></label>
                <select name="region_id" id="region_id" class="form-control" required>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?php echo $region['id']; ?>"><?php echo htmlspecialchars($region['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="recruiter_name">نام معرف</label>
                <input type="text" name="recruiter_name" id="recruiter_name" class="form-control">
            </div>
            <div class="form-group">
                <label for="event_name">جذب شده در مراسم</label>
                <input type="text" name="event_name" id="event_name" class="form-control" placeholder="مثلا: غدیر ۱۴۰۳">
            </div>
            <div class="form-group">
                <label for="recruited_at">تاریخ جذب <span style="color: red;">*</span></label>
                <input type="date" name="recruited_at" id="recruited_at" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" name="add_student" class="btn btn-primary" value="ثبت دانش‌آموز">
            </div>
        </form>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
