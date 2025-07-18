<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: manage_class_events
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// This page would be more complex in a real scenario, dealing with event types, budgets, etc.
// For now, we'll create a simple "service" or "event" record for a class.

// Fetching data for form dropdowns
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll();

// Handle Add/Edit Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_title'])) {
    $class_id = $_POST['class_id'];
    $event_title = trim($_POST['event_title']);
    $event_date = trim($_POST['event_date']);
    $description = trim($_POST['description']);
    // In a real app, you'd have a dedicated table for these events.
    // We'll add a simple task for the teacher for now.

    if (empty($event_title) || empty($class_id) || empty($event_date)) {
        $error = "عنوان، کلاس و تاریخ الزامی است.";
    } else {
        try {
            // We'll create a task for the class teacher as a placeholder for this functionality
            $stmt_teacher = $pdo->prepare("SELECT teacher_user_id FROM classes WHERE id = ?");
            $stmt_teacher->execute([$class_id]);
            $teacher_id = $stmt_teacher->fetchColumn();

            if ($teacher_id) {
                $task_title = "خدمت‌رسانی پرورشی: " . $event_title;
                $task_desc = "برای کلاس شما یک رویداد/خدمت‌رسانی پرورشی با عنوان '{$event_title}' در تاریخ " . to_persian_date($event_date, "Y/m/d") . " ثبت شده است.\n توضیحات: " . $description;

                $pdo->beginTransaction();

                $stmt_task = $pdo->prepare("INSERT INTO tasks (title, description, deadline, created_by, priority) VALUES (?, ?, ?, ?, 'medium')");
                $stmt_task->execute([$task_title, $task_desc, $event_date, $_SESSION['id']]);
                $task_id = $pdo->lastInsertId();

                $stmt_assign = $pdo->prepare("INSERT INTO task_assignments (task_id, assigned_to_user_id) VALUES (?, ?)");
                $stmt_assign->execute([$task_id, $teacher_id]);

                $pdo->commit();
                $message = "رویداد/خدمت‌رسانی با موفقیت به عنوان یک وظیفه برای مدرس کلاس ثبت شد.";
            } else {
                $error = "این کلاس مدرسی برای تخصیص وظیفه ندارد.";
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "خطا در ثبت اطلاعات: " . $e->getMessage();
        }
    }
}

// Fetch recently created class events (as tasks)
$recent_events = $pdo->query("
    SELECT t.title, t.description, t.deadline, u.full_name as teacher_name
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    JOIN users u ON ta.assigned_to_user_id = u.id
    WHERE t.title LIKE 'خدمت‌رسانی پرورشی:%'
    ORDER BY t.created_at DESC
    LIMIT 20
")->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت خدمت‌رسانی به کلاس‌ها</h2>
        <p>ثبت رویدادها و خدمات پرورشی برای کلاس‌های مختلف.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header"><h5>ثبت خدمت‌رسانی جدید</h5></div>
            <div class="card-body">
                <form action="manage_class_events.php" method="post">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="class_id">کلاس</label>
                            <select name="class_id" class="form-control" required>
                                <option value="">-- انتخاب کنید --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="event_title">عنوان رویداد/خدمت</label>
                            <input type="text" name="event_title" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="event_date">تاریخ اجرا</label>
                            <input type="date" name="event_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="description">توضیحات</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">ثبت خدمت</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5>خدمت‌رسانی‌های اخیر</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>مدرس مسئول</th>
                                <th>تاریخ اجرا</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(str_replace('خدمت‌رسانی پرورشی: ', '', $event['title'])); ?></td>
                                <td><?php echo htmlspecialchars($event['teacher_name']); ?></td>
                                <td><?php echo to_persian_date($event['deadline'], 'Y/m/d'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
