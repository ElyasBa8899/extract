<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch classes taught by the current user
$classes = [];
$sql = "
    SELECT c.id, c.class_name, c.description, r.name as region_name
    FROM classes c
    JOIN class_teachers ct ON c.id = ct.class_id
    LEFT JOIN regions r ON c.region_id = r.id
    WHERE ct.teacher_id = ?
    ORDER BY c.class_name ASC
";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $classes = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>کلاس‌های من</h2>
    <p>در این بخش می‌توانید کلاس‌هایی که مدرس آن هستید را مشاهده و مدیریت کنید.</p>

    <div class="table-container">
        <h3>لیست کلاس‌ها</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام کلاس</th>
                        <th>منطقه</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr><td colspan="4">شما در حال حاضر مدرس هیچ کلاسی نیستید.</td></tr>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['region_name'] ?? '---'); ?></td>
                                <td><?php echo htmlspecialchars($class['description']); ?></td>
                                <td>
                                    <a href="edit_my_class.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-warning">ویرایش اطلاعات و دانش‌آموزان</a>
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
