<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) {
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// Fetching data for form dropdowns
$regions = $pdo->query("SELECT * FROM regions ORDER BY name")->fetchAll();
$events = $pdo->query("SELECT * FROM general_events ORDER BY event_name")->fetchAll();

// Handle Add/Edit Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name'])) {
    $student_id = $_POST['student_id'] ?? null;
    $name = trim($_POST['student_name']);
    $parent_info = trim($_POST['parent_info']);
    $contact_number = trim($_POST['contact_number']);
    $region_id = $_POST['region_id'] ?: null;
    $introducer = trim($_POST['introducer']);
    $event_id = $_POST['event_id'] ?: null;

    if (empty($name)) {
        $error = "نام دانش‌آموز الزامی است.";
    } else {
        try {
            if ($student_id) {
                $stmt = $pdo->prepare("UPDATE students SET name=?, parent_info=?, contact_number=?, region_id=?, introducer=?, recruited_from_event_id=? WHERE id=?");
                $stmt->execute([$name, $parent_info, $contact_number, $region_id, $introducer, $event_id, $student_id]);
                $message = "اطلاعات دانش‌آموز با موفقیت ویرایش شد.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO students (name, parent_info, contact_number, region_id, introducer, recruited_from_event_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $parent_info, $contact_number, $region_id, $introducer, $event_id]);
                $message = "دانش‌آموز جدید با موفقیت ثبت شد.";
            }
        } catch (PDOException $e) {
            $error = "خطا در ثبت اطلاعات: " . $e->getMessage();
        }
    }
}


// Handle Deletion
if (isset($_GET['delete'])) {
    $student_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $message = "دانش‌آموز با موفقیت حذف شد.";
    } catch (PDOException $e) {
        $error = "خطا در حذف دانش‌آموز: " . $e->getMessage();
    }
}


// Fetching data for display
$students = $pdo->query("
    SELECT s.*, r.name as region_name, ge.event_name
    FROM students s
    LEFT JOIN regions r ON s.region_id = r.id
    LEFT JOIN general_events ge ON s.recruited_from_event_id = ge.id
    ORDER BY s.created_at DESC
")->fetchAll();

$stats = $pdo->query("
    SELECT
        COUNT(*) as total_students,
        (SELECT r.name FROM regions r WHERE r.id = s.region_id) as region_name,
        COUNT(s.region_id) as count_in_region
    FROM students s
    GROUP BY s.region_id
")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);


// Fetch a single student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_student = $stmt->fetch();
}


?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت جذب و راه‌اندازی</h2>
        <p>ثبت و مدیریت دانش‌آموزان جذب شده در مناطق مختلف.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Stats Section -->
        <div class="row">
             <div class="col-md-4">
                <div class="widget">
                    <div class="widget-header"><h3>کل دانش‌آموزان جذب شده</h3></div>
                    <div class="widget-body financial-widget-body">
                        <div class="balance"><?php echo count($students); ?></div>
                    </div>
                </div>
            </div>
            <?php foreach ($stats as $region_name => $region_stat): ?>
                 <div class="col-md-4">
                    <div class="widget">
                        <div class="widget-header"><h3><?php echo htmlspecialchars($region_name ?: 'بدون منطقه'); ?></h3></div>
                        <div class="widget-body financial-widget-body">
                            <div class="balance"><?php echo $region_stat[0]['count_in_region']; ?></div>
                            <span>نفر</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Add/Edit Form -->
        <div class="card">
             <div class="card-header">
                <h5><?php echo $edit_student ? 'ویرایش اطلاعات دانش‌آموز' : 'افزودن دانش‌آموز جدید'; ?></h5>
            </div>
            <div class="card-body">
                <form action="manage_recruitment.php" method="post">
                    <?php if ($edit_student): ?>
                        <input type="hidden" name="student_id" value="<?php echo $edit_student['id']; ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="student_name">نام دانش‌آموز</label>
                            <input type="text" name="student_name" class="form-control" value="<?php echo htmlspecialchars($edit_student['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="parent_info">نام والدین</label>
                            <input type="text" name="parent_info" class="form-control" value="<?php echo htmlspecialchars($edit_student['parent_info'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="contact_number">شماره تماس</label>
                            <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($edit_student['contact_number'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="region_id">منطقه</label>
                            <select name="region_id" class="form-control">
                                <option value="">-- انتخاب کنید --</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo $region['id']; ?>" <?php echo (isset($edit_student['region_id']) && $edit_student['region_id'] == $region['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($region['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="event_id">جذب شده از طریق</label>
                             <select name="event_id" class="form-control">
                                <option value="">-- انتخاب کنید --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?php echo $event['id']; ?>" <?php echo (isset($edit_student['recruited_from_event_id']) && $edit_student['recruited_from_event_id'] == $event['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['event_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="introducer">معرف</label>
                            <input type="text" name="introducer" class="form-control" value="<?php echo htmlspecialchars($edit_student['introducer'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_student ? 'ذخیره تغییرات' : 'افزودن دانش‌آموز'; ?></button>
                         <?php if ($edit_student): ?>
                            <a href="manage_recruitment.php" class="btn btn-secondary">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students List -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>لیست دانش‌آموزان جذب شده</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                         <thead>
                            <tr>
                                <th>نام</th>
                                <th>والدین</th>
                                <th>تماس</th>
                                <th>منطقه</th>
                                <th>جذب از</th>
                                <th>معرف</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['parent_info']); ?></td>
                                    <td><?php echo htmlspecialchars($student['contact_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['region_name'] ?? '---'); ?></td>
                                    <td><?php echo htmlspecialchars($student['event_name'] ?? '---'); ?></td>
                                    <td><?php echo htmlspecialchars($student['introducer']); ?></td>
                                    <td>
                                        <a href="manage_recruitment.php?edit=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">ویرایش</a>
                                        <a href="manage_recruitment.php?delete=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
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
