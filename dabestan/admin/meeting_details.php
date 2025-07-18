<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!isset($_SESSION['loggedin'])) {
    header("Location: ../index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

$meeting_id = $_GET['id'] ?? null;

if (!$meeting_id) {
    die("خطا: شناسه جلسه مشخص نشده است.");
}

// --- Handle Checklist Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_checklist_item'])) {
    $item_description = trim($_POST['item_description']);
    if (!empty($item_description)) {
        $stmt = $pdo->prepare("INSERT INTO meeting_checklist_items (meeting_id, item_description) VALUES (?, ?)");
        $stmt->execute([$meeting_id, $item_description]);
        $message = "آیتم چک‌لیست اضافه شد.";
    }
}
if (isset($_GET['toggle_checklist'])) {
    $item_id = $_GET['toggle_checklist'];
    $stmt = $pdo->prepare("UPDATE meeting_checklist_items SET is_completed = 1 - is_completed WHERE id = ?");
    $stmt->execute([$item_id]);
    header("Location: meeting_details.php?id=" . $meeting_id);
    exit();
}

// --- Handle Attendance ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $present_users = $_POST['present_users'] ?? [];
    $absent_users_text = trim($_POST['absent_users_text'] ?? '');

    try {
        $pdo->beginTransaction();
        // Clear previous attendance records for this meeting
        $pdo->prepare("DELETE FROM meeting_attendance WHERE meeting_id = ?")->execute([$meeting_id]);

        // Save present registered users
        $stmt_present = $pdo->prepare("INSERT INTO meeting_attendance (meeting_id, user_id, is_present) VALUES (?, ?, 1)");
        foreach ($present_users as $user_id) {
            $stmt_present->execute([$meeting_id, $user_id]);
        }

        // Save non-user attendees (present or absent)
        $non_user_attendees = explode("\n", $absent_users_text);
        $stmt_non_user = $pdo->prepare("INSERT INTO meeting_attendance (meeting_id, attendee_name, is_present) VALUES (?, ?, 0)");
        foreach ($non_user_attendees as $attendee_name) {
            $name = trim($attendee_name);
            if (!empty($name)) {
                $stmt_non_user->execute([$meeting_id, $name]);
            }
        }
        $pdo->commit();
        $message = "حضور و غیاب با موفقیت ثبت شد.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "خطا در ثبت حضور و غیاب: " . $e->getMessage();
    }
}


// --- Fetch Data ---
$meeting = $pdo->prepare("SELECT m.*, c.class_name FROM meetings m LEFT JOIN classes c ON m.related_class_id = c.id WHERE m.id = ?")->fetch();
$meeting->execute([$meeting_id]);
$meeting = $meeting->fetch();

if (!$meeting) die("جلسه‌ای یافت نشد.");

$checklist_items = $pdo->prepare("SELECT * FROM meeting_checklist_items WHERE meeting_id = ? ORDER BY id")->execute([$meeting_id])->fetchAll();

// Fetch attendance data
$users = $pdo->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name")->fetchAll();
$present_user_ids = $pdo->prepare("SELECT user_id FROM meeting_attendance WHERE meeting_id = ? AND is_present = 1 AND user_id IS NOT NULL")->execute([$meeting_id])->fetchAll(PDO::FETCH_COLUMN, 0);
$non_user_attendees = $pdo->prepare("SELECT attendee_name FROM meeting_attendance WHERE meeting_id = ? AND is_present = 0")->execute([$meeting_id])->fetchAll(PDO::FETCH_COLUMN, 0);

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>جزئیات جلسه: <?php echo htmlspecialchars($meeting['title']); ?></h2>
        <p><strong>نوع:</strong> <?php echo htmlspecialchars($meeting['meeting_type']); ?> | <strong>تاریخ:</strong> <?php echo to_persian_date($meeting['meeting_date']); ?> | <strong>وضعیت:</strong> <?php echo get_status_badge_view($meeting['status']); ?></p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <form action="meeting_details.php?id=<?php echo $meeting_id; ?>" method="post">
            <div class="row mt-4">
                <!-- Checklist Card -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5>چک‌لیست جلسه</h5></div>
                        <div class="card-body">
                            <div class="input-group mb-3">
                                <input type="text" name="item_description" class="form-control" placeholder="آیتم جدید...">
                                <div class="input-group-append">
                                    <button type="submit" name="add_checklist_item" class="btn btn-primary">افزودن</button>
                                </div>
                            </div>
                            <ul class="list-group">
                                <?php foreach ($checklist_items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $item['is_completed'] ? 'completed' : ''; ?>">
                                        <?php echo htmlspecialchars($item['item_description']); ?>
                                        <a href="?id=<?php echo $meeting_id; ?>&toggle_checklist=<?php echo $item['id']; ?>" class="btn btn-sm <?php echo $item['is_completed'] ? 'btn-success' : 'btn-outline-secondary'; ?>">
                                            <i data-feather="<?php echo $item['is_completed'] ? 'check-square' : 'square'; ?>"></i>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Attendance Card -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5>حضور و غیاب</h5></div>
                        <div class="card-body">
                            <h6>اعضای ثبت شده (مدرسین)</h6>
                            <div class="attendance-grid mb-3">
                                <?php foreach ($users as $user): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="present_users[]" value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $present_user_ids) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="user_<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
                            <h6>سایر شرکت‌کنندگان/غایبین (هر نام در یک خط)</h6>
                            <textarea name="absent_users_text" class="form-control" rows="4"><?php echo htmlspecialchars(implode("\n", $non_user_attendees)); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="save_attendance" class="btn btn-success">ذخیره کلیه تغییرات</button>
                <a href="javascript:history.back()" class="btn btn-secondary">بازگشت</a>
            </div>
        </form>
    </div>
</div>
<style>
    .list-group-item.completed { text-decoration: line-through; background-color: #f0f0f0; }
    .attendance-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.5rem; }
</style>
<?php require_once "../includes/footer.php"; ?>
