<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// Fetch regions for the filter dropdown
$regions = [];
$sql_regions = "SELECT id, name FROM regions ORDER BY name ASC";
if($result_regions = mysqli_query($link, $sql_regions)){
    $regions = mysqli_fetch_all($result_regions, MYSQLI_ASSOC);
}

// Base SQL query
$sql = "SELECT s.id, s.student_name, s.parent_name, s.phone_number, s.recruiter_name, s.event_name, s.recruited_at, r.name as region_name
        FROM recruited_students s
        JOIN regions r ON s.region_id = r.id";

// Filter by region
$selected_region = '';
if(isset($_GET['region_id']) && !empty($_GET['region_id'])){
    $selected_region = $_GET['region_id'];
    $sql .= " WHERE s.region_id = ?";
}

$sql .= " ORDER BY s.recruited_at DESC";

// Prepare and execute the statement
$students = [];
if($stmt = mysqli_prepare($link, $sql)){
    if(!empty($selected_region)){
        mysqli_stmt_bind_param($stmt, "i", $selected_region);
    }
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        echo "Error executing statement.";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement.";
}

mysqli_close($link);
require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>لیست دانش‌آموزان جذب شده</h2>
    <p>در این بخش لیست تمام دانش‌آموزان ثبت شده را مشاهده می‌کنید. می‌توانید بر اساس منطقه فیلتر کنید.</p>

    <!-- Filter Form -->
    <div class="form-container" style="margin-bottom: 20px;">
        <form action="list_students.php" method="get">
            <div class="form-group">
                <label for="region_id">فیلتر بر اساس منطقه:</label>
                <select name="region_id" id="region_id" class="form-control" onchange="this.form.submit()">
                    <option value="">همه مناطق</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?php echo $region['id']; ?>" <?php if($selected_region == $region['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($region['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="table-container">
        <h3>تعداد کل دانش‌آموزان یافت شده: <?php echo count($students); ?></h3>
        <table class="table">
            <thead>
                <tr>
                    <th>نام دانش‌آموز</th>
                    <th>منطقه</th>
                    <th>نام والدین</th>
                    <th>شماره تماس</th>
                    <th>معرف</th>
                    <th>مراسم جذب</th>
                    <th>تاریخ جذب</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">هیچ دانش‌آموزی با این مشخصات یافت نشد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['region_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['recruiter_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['recruited_at']); ?></td>
                            <td>
                                <!-- Add edit/delete buttons later -->
                                <a href="#" class="btn btn-secondary btn-sm">ویرایش</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
