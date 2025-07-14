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

// Security Check: User must be an admin or a teacher of this specific class
$is_admin = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true;
$is_teacher_q = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id AND teacher_id = $user_id");
$is_teacher = mysqli_num_rows($is_teacher_q) > 0;

if (!$is_admin && !$is_teacher) {
    echo "دسترسی غیرمجاز.";
    exit;
}

// Fetch class details
$class_query = mysqli_query($link, "SELECT * FROM classes WHERE id = $class_id");
$class = mysqli_fetch_assoc($class_query);

// Handle POST requests for adding a student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_name = trim($_POST['student_name']);
    if (empty($student_name)) {
        $err = "نام متربی نمی‌تواند خالی باشد.";
    } else {
        // Add student to the class
        $sql_add = "INSERT INTO class_students (class_id, student_name, added_by) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql_add)) {
            mysqli_stmt_bind_param($stmt, "isi", $class_id, $student_name, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "متربی با موفقیت اضافه شد.";
            } else {
                $err = "خطا در اضافه کردن متربی.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>مدیریت متربیان کلاس: <?php echo htmlspecialchars($class['class_name']); ?></h2>
        <a href="my_classes.php" class="btn btn-secondary">&larr; بازگشت به لیست کلاس‌ها</a>
    </div>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container mb-4">
        <h3>افزودن متربی جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?class_id=<?php echo $class_id; ?>" method="post">
            <div class="form-group">
                <label for="student_name">نام و نام خانوادگی متربی:</label>
                <input type="text" name="student_name" id="student_name" class="form-control" required>
            </div>
            <button type="submit" name="add_student" class="btn btn-success">افزودن متربی</button>
        </form>
    </div>

    <div class="table-container">
        <h3>لیست متربیان</h3>
        <p>در این لیست، متربیان اضافه شده توسط شما و همچنین متربیانی که توسط ادمین به این کلاس منتقل شده‌اند، نمایش داده می‌شوند.</p>
        <table class="table">
            <thead>
                <tr>
                    <th>نام متربی</th>
                    <th>اضافه شده توسط</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all students: both from class_students (added by teacher) and recruited_students (added by admin)
                $students = [];

                // 1. Get students added by teachers
                $sql_teacher_added = "SELECT cs.id, cs.student_name, u.full_name as added_by_name, 'teacher' as source
                                      FROM class_students cs
                                      JOIN users u ON cs.added_by = u.id
                                      WHERE cs.class_id = ?";
                if($stmt_teacher = mysqli_prepare($link, $sql_teacher_added)) {
                    mysqli_stmt_bind_param($stmt_teacher, "i", $class_id);
                    mysqli_stmt_execute($stmt_teacher);
                    $result = mysqli_stmt_get_result($stmt_teacher);
                    while($row = mysqli_fetch_assoc($result)) {
                        $students[] = $row;
                    }
                    mysqli_stmt_close($stmt_teacher);
                }

                // 2. Get students added by admin (from recruited_students)
                $sql_admin_added = "SELECT rs.id, rs.student_name, 'ادمین سیستم' as added_by_name, 'admin' as source
                                    FROM recruited_students rs
                                    WHERE rs.class_id = ?";
                if($stmt_admin = mysqli_prepare($link, $sql_admin_added)) {
                    mysqli_stmt_bind_param($stmt_admin, "i", $class_id);
                    mysqli_stmt_execute($stmt_admin);
                    $result = mysqli_stmt_get_result($stmt_admin);
                    while($row = mysqli_fetch_assoc($result)) {
                        $students[] = $row;
                    }
                    mysqli_stmt_close($stmt_admin);
                }

                if (empty($students)): ?>
                    <tr><td colspan="3">هنوز متربی‌ای در این کلاس ثبت نشده است.</td></tr>
                <?php else:
                    // Sort students by name
                    usort($students, function($a, $b) {
                        return strcmp($a['student_name'], $b['student_name']);
                    });

                    foreach ($students as $student):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['added_by_name']); ?></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="openDeleteModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['student_name'], ENT_QUOTES); ?>', '<?php echo $student['source']; ?>')">
                            درخواست حذف
                        </button>
                    </td>
                </tr>
                <?php
                    endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Student Modal -->
<div id="deleteModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h3>درخواست حذف متربی</h3>
        <form id="deleteForm" action="request_student_deletion.php" method="post">
            <input type="hidden" name="student_id" id="modalStudentId">
            <input type="hidden" name="student_source" id="modalStudentSource">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <p>شما در حال ثبت درخواست برای حذف متربی <strong id="modalStudentName"></strong> از این کلاس هستید.</p>
            <div class="form-group">
                <label for="deletion_reason">دلیل حذف (ضروری):</label>
                <textarea name="deletion_reason" id="deletion_reason" class="form-control" rows="4" required></textarea>
            </div>
            <p class="small">پس از ثبت، درخواست شما برای ادمین ارسال می‌شود و در صورت تایید، متربی از کلاس حذف خواهد شد.</p>
            <div class="form-group">
                <button type="submit" class="btn btn-danger">ثبت درخواست حذف</button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">لغو</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeleteModal(studentId, studentName, studentSource) {
    document.getElementById('modalStudentId').value = studentId;
    document.getElementById('modalStudentName').textContent = studentName;
    document.getElementById('modalStudentSource').value = studentSource;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
