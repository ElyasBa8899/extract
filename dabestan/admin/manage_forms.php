<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: manage_forms
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// --- Form Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit Form
    if (isset($_POST['form_title'])) {
        $form_id = $_POST['form_id'] ?? null;
        $title = trim($_POST['form_title']);
        $description = trim($_POST['form_description']);
        $form_type = $_POST['form_type'];

        try {
            if ($form_id) {
                $stmt = $pdo->prepare("UPDATE forms SET title=?, description=?, form_type=? WHERE id=?");
                $stmt->execute([$title, $description, $form_type, $form_id]);
                $message = "فرم با موفقیت ویرایش شد.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO forms (title, description, form_type, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $description, $form_type, $_SESSION['id']]);
                $message = "فرم جدید با موفقیت ایجاد شد.";
            }
        } catch (PDOException $e) {
            $error = "خطا در ذخیره فرم: " . $e->getMessage();
        }
    }
    // Add Field to Form
    if (isset($_POST['add_field'])) {
        $form_id = $_POST['form_id'];
        $label = trim($_POST['label']);
        $field_type = $_POST['field_type'];
        $options = ($field_type === 'select' || $field_type === 'radio' || $field_type === 'checkbox') ? json_encode(explode("\n", trim($_POST['options']))) : null;
        $is_required = isset($_POST['is_required']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("INSERT INTO form_fields (form_id, label, field_type, options, is_required) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$form_id, $label, $field_type, $options, $is_required]);
            $message = "فیلد جدید به فرم اضافه شد.";
        } catch (PDOException $e) {
            $error = "خطا در افزودن فیلد: " . $e->getMessage();
        }
    }
}

// --- Deletion Actions ---
if (isset($_GET['delete_form'])) {
    $form_id = $_GET['delete_form'];
    $stmt = $pdo->prepare("DELETE FROM forms WHERE id = ?");
    $stmt->execute([$form_id]);
    header("Location: manage_forms.php");
    exit();
}
if (isset($_GET['delete_field'])) {
    $field_id = $_GET['delete_field'];
    $form_id = $_GET['form_id'];
    $stmt = $pdo->prepare("DELETE FROM form_fields WHERE id = ?");
    $stmt->execute([$field_id]);
    header("Location: manage_forms.php?view=" . $form_id);
    exit();
}

// --- Fetch Data ---
$forms = $pdo->query("SELECT * FROM forms ORDER BY title")->fetchAll();
$view_form = null;
$form_fields = [];
if (isset($_GET['view'])) {
    $form_id = $_GET['view'];
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([$form_id]);
    $view_form = $stmt->fetch();
    if ($view_form) {
        $fields_stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id");
        $fields_stmt->execute([$form_id]);
        $form_fields = $fields_stmt->fetchAll();
    }
}

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>طراحی و مدیریت فرم‌ها</h2>
        <p>فرم‌های پویا برای نظارت، خوداظهاری و... ایجاد کنید.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5>لیست فرم‌ها</h5></div>
                    <ul class="list-group list-group-flush">
                        <?php foreach($forms as $form): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="?view=<?php echo $form['id']; ?>"><?php echo htmlspecialchars($form['title']); ?></a>
                            <a href="?delete_form=<?php echo $form['id']; ?>" onclick="return confirm('آیا از حذف کامل این فرم و تمام فیلدهایش مطمئنید؟');" class="text-danger"><i data-feather="trash-2"></i></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card mt-3">
                     <div class="card-header"><h5>ایجاد فرم جدید</h5></div>
                     <div class="card-body">
                         <form action="manage_forms.php" method="post">
                            <div class="form-group"><input type="text" name="form_title" placeholder="عنوان فرم" class="form-control" required></div>
                            <div class="form-group"><textarea name="form_description" placeholder="توضیحات فرم" class="form-control"></textarea></div>
                            <div class="form-group">
                                <select name="form_type" class="form-control">
                                    <option value="self_assessment">خوداظهاری</option>
                                    <option value="class_observation">بازدید کلاسی</option>
                                    <option value="parent_meeting">جلسه اولیا</option>
                                    <option value="other">دیگر</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">ایجاد فرم</button>
                        </form>
                     </div>
                </div>
            </div>

            <div class="col-md-8">
                <?php if ($view_form): ?>
                <div class="card">
                    <div class="card-header">
                        <h5>فیلدهای فرم: <?php echo htmlspecialchars($view_form['title']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($form_fields as $field): ?>
                            <div class="p-2 border rounded mb-2">
                                <strong><?php echo htmlspecialchars($field['label']); ?></strong> (<?php echo $field['field_type']; ?>)
                                <?php if($field['is_required']) echo '<span class="badge badge-danger">الزامی</span>'; ?>
                                <a href="?delete_field=<?php echo $field['id']; ?>&form_id=<?php echo $view_form['id']; ?>" onclick="return confirm('آیا مطمئنید؟');" class="float-left text-danger"><i data-feather="x-circle"></i></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><h5>افزودن فیلد جدید</h5></div>
                    <div class="card-body">
                        <form action="manage_forms.php?view=<?php echo $view_form['id']; ?>" method="post">
                            <input type="hidden" name="form_id" value="<?php echo $view_form['id']; ?>">
                            <div class="form-group"><input type="text" name="label" placeholder="عنوان فیلد (سوال)" class="form-control" required></div>
                            <div class="form-group">
                                <select name="field_type" class="form-control">
                                    <option value="text">متن کوتاه</option>
                                    <option value="textarea">متن بلند</option>
                                    <option value="number">عددی</option>
                                    <option value="select">لیست کشویی</option>
                                    <option value="radio">چند گزینه‌ای (تک انتخاب)</option>
                                    <option value="checkbox">چک‌باکس (چند انتخاب)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <textarea name="options" class="form-control" placeholder="گزینه‌ها (هر گزینه در یک خط)"></textarea>
                                <small>برای لیست کشویی، رادیو و چک‌باکس</small>
                            </div>
                             <div class="form-group">
                                <label><input type="checkbox" name="is_required" value="1"> این فیلد الزامی است.</label>
                            </div>
                            <button type="submit" name="add_field" class="btn btn-success">افزودن فیلد</button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-info">برای مدیریت فیلدها، یک فرم را از لیست انتخاب کنید.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
