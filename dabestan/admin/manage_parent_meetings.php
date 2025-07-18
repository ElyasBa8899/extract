<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // For now, only admin can manage this. Later, we'll use has_permission()
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// Handle marking a meeting as planned
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plan_meeting'])) {
    $class_id = $_POST['class_id'];
    $meeting_title = "جلسه اولیا برای کلاس " . get_class_name($class_id); // Helper function needed
    $meeting_date = $_POST['meeting_date'];

    if (empty($meeting_date)) {
        $error = "لطفاً تاریخ جلسه را مشخص کنید.";
    } else {
        try {
            $pdo->beginTransaction();
            // Create the meeting
            $stmt = $pdo->prepare("INSERT INTO meetings (meeting_type, related_class_id, title, meeting_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['parent_meeting', $class_id, $meeting_title, $meeting_date, $_SESSION['id']]);

            // Update class status
            $stmt_update = $pdo->prepare("UPDATE classes SET parent_meeting_status = 'pending' WHERE id = ?");
            $stmt_update->execute([$class_id]);

            $pdo->commit();
            $message = "جلسه اولیا برای کلاس با موفقیت برنامه‌ریزی شد.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "خطا در برنامه‌ریزی جلسه: " . $e->getMessage();
        }
    }
}

// Fetch all classes with their teacher's name
try {
    $stmt = $pdo->query("
        SELECT c.id, c.class_name, c.parent_meeting_status, u.full_name as teacher_name
        FROM classes c
        LEFT JOIN users u ON c.teacher_user_id = u.id
        ORDER BY c.class_name
    ");
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "خطا در دریافت لیست کلاس‌ها: " . $e->getMessage();
    $classes = [];
}


// Helper function to get class name by ID (can be moved to functions.php later)
function get_class_name($class_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    return $stmt->fetchColumn();
}
?>

<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت جلسات اولیا</h2>
        <p>وضعیت برگزاری جلسات اولیا برای هر کلاس را مشاهده و مدیریت کنید.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>لیست کلاس‌ها و وضعیت جلسه اولیا</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>نام کلاس</th>
                                <th>مدرس</th>
                                <th>وضعیت جلسه</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">هیچ کلاسی یافت نشد.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $class):
                                    $status_label = '';
                                    $status_class = '';
                                    switch ($class['parent_meeting_status']) {
                                        case 'completed':
                                            $status_label = 'برگزار شده';
                                            $status_class = 'badge-success';
                                            break;
                                        case 'pending':
                                            $status_label = 'در دست اقدام';
                                            $status_class = 'badge-warning';
                                            break;
                                        case 'not_held':
                                        default:
                                            $status_label = 'برگزار نشده';
                                            $status_class = 'badge-danger';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($class['teacher_name'] ?? '---'); ?></td>
                                        <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                        <td>
                                            <?php if ($class['parent_meeting_status'] == 'not_held'): ?>
                                                <form action="manage_parent_meetings.php" method="post" class="form-inline d-inline">
                                                    <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                    <input type="datetime-local" name="meeting_date" class="form-control form-control-sm" required>
                                                    <button type="submit" name="plan_meeting" class="btn btn-sm btn-primary">برنامه‌ریزی جلسه</button>
                                                </form>
                                            <?php else: ?>
                                                <a href="meeting_details.php?class_id=<?php echo $class['id']; ?>&type=parent_meeting" class="btn btn-sm btn-info">مشاهده جزئیات</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
