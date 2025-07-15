<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";

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

// Fetch unregistered students (still in recruited_students table)
$unregistered_students = [];
$sql_unregistered = "SELECT id, student_name, phone_number FROM recruited_students WHERE region_id = ? AND class_id IS NULL ORDER BY student_name ASC";
if($stmt_unregistered = mysqli_prepare($link, $sql_unregistered)){
    mysqli_stmt_bind_param($stmt_unregistered, "i", $region_id);
    mysqli_stmt_execute($stmt_unregistered);
    $result_unregistered = mysqli_stmt_get_result($stmt_unregistered);
    $unregistered_students = mysqli_fetch_all($result_unregistered, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_unregistered);
}

// Fetch registered students (moved to main students table or linked to a class)
$registered_students = [];
$sql_registered = "SELECT rs.student_name, c.class_name
                   FROM recruited_students rs
                   JOIN classes c ON rs.class_id = c.id
                   WHERE rs.region_id = ? AND rs.class_id IS NOT NULL";
if($stmt_registered = mysqli_prepare($link, $sql_registered)){
    mysqli_stmt_bind_param($stmt_registered, "i", $region_id);
    mysqli_stmt_execute($stmt_registered);
    $result_registered = mysqli_stmt_get_result($stmt_registered);
    $registered_students = mysqli_fetch_all($result_registered, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_registered);
}

// Fetch active classes in the region that have at least one student
$active_classes_with_students = [];
$sql_classes = "SELECT DISTINCT c.id, c.class_name, u.full_name as teacher_name
                FROM classes c
                JOIN users u ON c.teacher_id = u.id
                JOIN recruited_students rs ON c.id = rs.class_id
                WHERE c.region_id = ? AND c.status = 'active'";

if ($stmt_classes = mysqli_prepare($link, $sql_classes)) {
    mysqli_stmt_bind_param($stmt_classes, "i", $region_id);
    mysqli_stmt_execute($stmt_classes);
    $result_classes = mysqli_stmt_get_result($stmt_classes);
    $active_classes_with_students = mysqli_fetch_all($result_classes, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_classes);
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
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

    <div class="table-container" style="margin-bottom: 30px;">
        <h3>دانش‌آموزان در صف انتظار</h3>
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
                    <?php if (empty($unregistered_students)): ?>
                        <tr><td colspan="3">هیچ دانش‌آموزی در صف انتظار این منطقه نیست.</td></tr>
                    <?php else: ?>
                        <?php foreach ($unregistered_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" onclick="openEnrollModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['student_name'], ENT_QUOTES); ?>')">
                                        ثبت‌نام در کلاس
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container">
        <h3>کلاس‌های فعال در این منطقه</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام کلاس</th>
                        <th>مدرس</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_classes_with_students)): ?>
                        <tr><td colspan="3">هیچ کلاس فعالی با متربی ثبت‌نام شده در این منطقه وجود ندارد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($active_classes_with_students as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                                <td>
                                    <a href="manage_class_students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">مدیریت متربیان</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container" style="margin-top: 40px;">
        <h3>دانش‌آموزان ثبت‌نام شده (نمایش کلی)</h3>
         <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام دانش‌آموز</th>
                        <th>کلاس</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registered_students)): ?>
                        <tr><td colspan="2">هیچ دانش‌آموز ثبت‌نام شده‌ای در این منطقه وجود ندارد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registered_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Enroll Student Modal -->
<div id="enrollModal" class="modal" style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color:#fefefe; margin:15% auto; padding:20px; border:1px solid #888; width:80%; max-width:500px; border-radius:8px;">
        <span class="close" onclick="closeEnrollModal()" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
        <h3>ثبت‌نام دانش‌آموز</h3>
        <p>دانش‌آموز <strong id="modalStudentName"></strong> را در کدام کلاس ثبت‌نام می‌کنید؟</p>
        <form action="enroll_student.php" method="post">
            <input type="hidden" name="student_id" id="modalStudentId">
            <input type="hidden" name="region_id" value="<?php echo $region_id; ?>">
            <div class="form-group">
                <label for="class_id">انتخاب کلاس:</label>
                <select name="class_id" id="class_id" class="form-control" required>
                    <option value="">-- انتخاب کنید --</option>
                    <?php
                    // Fetch active classes in this region
                    $classes_in_region_q = mysqli_query($link, "SELECT id, class_name FROM classes WHERE region_id = $region_id AND status = 'active'");
                    while($class_item = mysqli_fetch_assoc($classes_in_region_q)) {
                        echo "<option value='{$class_item['id']}'>" . htmlspecialchars($class_item['class_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="ثبت‌نام نهایی">
            </div>
        </form>
    </div>
</div>

<script>
function openEnrollModal(studentId, studentName) {
    document.getElementById('modalStudentId').value = studentId;
    document.getElementById('modalStudentName').textContent = studentName;
    document.getElementById('enrollModal').style.display = 'block';
}

function closeEnrollModal() {
    document.getElementById('enrollModal').style.display = 'none';
}

// Close modal if user clicks outside of it
window.onclick = function(event) {
    const modal = document.getElementById('enrollModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php
// mysqli_close($link);
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php";
?>
