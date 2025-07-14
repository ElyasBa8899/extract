<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch classes taught by the current user
$my_classes = [];
$sql = "SELECT c.id, c.class_name, c.description, c.status, r.name as region_name
        FROM classes c
        JOIN class_teachers ct ON c.id = ct.class_id
        LEFT JOIN regions r ON c.region_id = r.id
        WHERE ct.teacher_id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $my_classes = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت کلاس‌های من</h2>
    <p>در این بخش می‌توانید کلاس‌هایی که به شما تخصیص داده شده است را مشاهده و مدیریت کنید.</p>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>نام کلاس</th>
                    <th>منطقه</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($my_classes)): ?>
                    <tr><td colspan="4" style="text-align: center;">هنوز کلاسی به شما تخصیص داده نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($my_classes as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['region_name'] ?? '---'); ?></td>
                            <td><?php echo translate_class_status($class['status']); ?></td>
                            <td>
                                <a href="edit_my_class.php?class_id=<?php echo $class['id']; ?>" class="btn btn-warning btn-sm">ویرایش اطلاعات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// mysqli_close($link);
require_once "../includes/footer.php";
?>
