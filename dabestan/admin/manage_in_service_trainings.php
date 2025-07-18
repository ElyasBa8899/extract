<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // Later, use has_permission('manage_meetings')
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// Handle Add/Edit Meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meeting_title'])) {
    $meeting_id = $_POST['meeting_id'] ?? null;
    $title = trim($_POST['meeting_title']);
    $meeting_date = trim($_POST['meeting_date']);
    $location = trim($_POST['location']);
    $status = $_POST['status'];

    if (empty($title) || empty($meeting_date)) {
        $error = "عنوان و تاریخ جلسه الزامی است.";
    } else {
        try {
            if ($meeting_id) {
                $stmt = $pdo->prepare("UPDATE meetings SET title=?, meeting_date=?, location=?, status=? WHERE id=? AND meeting_type='in_service_training'");
                $stmt->execute([$title, $meeting_date, $location, $status, $meeting_id]);
                $message = "جلسه با موفقیت ویرایش شد.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO meetings (title, meeting_date, location, status, meeting_type, created_by) VALUES (?, ?, ?, ?, 'in_service_training', ?)");
                $stmt->execute([$title, $meeting_date, $location, $status, $_SESSION['id']]);
                $message = "جلسه جدید با موفقیت ثبت شد.";
            }
        } catch (PDOException $e) {
            $error = "خطا در ثبت اطلاعات: " . $e->getMessage();
        }
    }
}


// Handle Deletion
if (isset($_GET['delete'])) {
    $meeting_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM meetings WHERE id = ? AND meeting_type='in_service_training'");
        $stmt->execute([$meeting_id]);
        $message = "جلسه با موفقیت حذف شد.";
    } catch (PDOException $e) {
        $error = "خطا در حذف جلسه: " . $e->getMessage();
    }
}


// Fetch all in-service training meetings
$meetings = $pdo->query("
    SELECT m.*, u.full_name as creator_name
    FROM meetings m
    JOIN users u ON m.created_by = u.id
    WHERE m.meeting_type = 'in_service_training'
    ORDER BY m.meeting_date DESC
")->fetchAll();

// Fetch a single meeting for editing
$edit_meeting = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ? AND meeting_type='in_service_training'");
    $stmt->execute([$edit_id]);
    $edit_meeting = $stmt->fetch();
}
?>

<div class="page-content">
    <div class="container-fluid">
        <h2>تقویم اجرایی ضمن خدمت</h2>
        <p>جلسات آموزشی و معرفتی مدرسین را در این بخش مدیریت کنید.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card">
            <div class="card-header">
                <h5><?php echo $edit_meeting ? 'ویرایش جلسه' : 'افزودن جلسه جدید'; ?></h5>
            </div>
            <div class="card-body">
                <form action="manage_in_service_trainings.php" method="post">
                    <?php if ($edit_meeting): ?>
                        <input type="hidden" name="meeting_id" value="<?php echo $edit_meeting['id']; ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="meeting_title">عنوان جلسه</label>
                            <input type="text" name="meeting_title" class="form-control" value="<?php echo htmlspecialchars($edit_meeting['title'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="meeting_date">تاریخ و ساعت</label>
                            <input type="datetime-local" name="meeting_date" class="form-control" value="<?php echo htmlspecialchars($edit_meeting['meeting_date'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="location">مکان</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($edit_meeting['location'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="status">وضعیت</label>
                            <select name="status" class="form-control">
                                <option value="planned" <?php echo (isset($edit_meeting['status']) && $edit_meeting['status'] == 'planned') ? 'selected' : ''; ?>>برنامه‌ریزی شده</option>
                                <option value="completed" <?php echo (isset($edit_meeting['status']) && $edit_meeting['status'] == 'completed') ? 'selected' : ''; ?>>تکمیل شده</option>
                                <option value="cancelled" <?php echo (isset($edit_meeting['status']) && $edit_meeting['status'] == 'cancelled') ? 'selected' : ''; ?>>لغو شده</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_meeting ? 'ذخیره تغییرات' : 'افزودن جلسه'; ?></button>
                        <?php if ($edit_meeting): ?>
                            <a href="manage_in_service_trainings.php" class="btn btn-secondary">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Meetings List -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>لیست جلسات</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>تاریخ</th>
                                <th>مکان</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meetings as $meeting): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($meeting['title']); ?></td>
                                    <td><?php echo to_persian_date($meeting['meeting_date']); ?></td>
                                    <td><?php echo htmlspecialchars($meeting['location']); ?></td>
                                    <td><?php echo get_status_badge_view($meeting['status']); ?></td>
                                    <td>
                                        <a href="meeting_details.php?id=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-info">جزئیات</a>
                                        <a href="manage_in_service_trainings.php?edit=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-warning">ویرایش</a>
                                        <a href="manage_in_service_trainings.php?delete=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
                                    </td>
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
