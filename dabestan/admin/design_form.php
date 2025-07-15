<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['form_id']) || empty($_GET['form_id'])) {
    header("location: manage_forms.php");
    exit;
}

$form_id = $_GET['form_id'];
$err = $success_msg = "";

// Fetch form details
$form = null;
$sql = "SELECT form_name, form_description FROM forms WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $form_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $form = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if(!$form){
    echo "فرم یافت نشد.";
    exit;
}

// Handle Add Field POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_field'])) {
    $field_label = trim($_POST['field_label']);
    $field_type = trim($_POST['field_type']);
    $field_options = trim($_POST['field_options']);
    $is_required = isset($_POST['is_required']) ? 1 : 0;

    if(empty($field_label) || empty($field_type)){
        $err = "برچسب و نوع فیلد الزامی است.";
    } else {
        $sql = "INSERT INTO form_fields (form_id, field_label, field_type, field_options, is_required) VALUES (?, ?, ?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "isssi", $form_id, $field_label, $field_type, $field_options, $is_required);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "فیلد جدید با موفقیت اضافه شد.";
            } else {
                $err = "خطا در افزودن فیلد.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle Delete Field Request
if (isset($_GET['delete_field'])) {
    $field_to_delete = $_GET['delete_field'];
    $sql = "DELETE FROM form_fields WHERE id = ? AND form_id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $field_to_delete, $form_id);
        if(mysqli_stmt_execute($stmt)){
            $success_msg = "فیلد با موفقیت حذف شد.";
        } else {
            $err = "خطا در حذف فیلد.";
        }
        mysqli_stmt_close($stmt);
    }
}


// Fetch existing fields for this form
$fields = [];
$sql = "SELECT id, field_label, field_type, field_options, is_required FROM form_fields WHERE form_id = ? ORDER BY field_order ASC";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $form_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $fields = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>

<div class="page-content">
    <a href="manage_forms.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت فرم‌ها</a>
    <h2>طراحی فرم: <?php echo htmlspecialchars($form['form_name']); ?></h2>
    <p><?php echo htmlspecialchars($form['form_description']); ?></p>

    <!-- Add New Field Form -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>افزودن فیلد جدید</h3>
        <?php
        if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
        if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
        ?>
        <form action="design_form.php?form_id=<?php echo $form_id; ?>" method="post">
            <div class="form-group">
                <label for="field_label">برچسب فیلد (سوال)</label>
                <input type="text" name="field_label" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="field_type">نوع فیلد</label>
                <select name="field_type" id="field_type_select" class="form-control" onchange="toggleOptionsField()">
                    <option value="text">متن کوتاه (Text)</option>
                    <option value="textarea">متن بلند (Textarea)</option>
                    <option value="number">عدد (Number)</option>
                    <option value="date">تاریخ (Date)</option>
                    <option value="select">لیست کشویی (Select)</option>
                    <option value="checkbox">چک‌باکس (Checkbox)</option>
                    <option value="radio">دکمه رادیویی (Radio)</option>
                </select>
            </div>
            <div class="form-group" id="options_field_group" style="display: none;">
                <label for="field_options">گزینه‌ها (با کاما جدا کنید)</label>
                <input type="text" name="field_options" class="form-control">
            </div>
            <div class="form-group">
                <input type="checkbox" name="is_required" id="is_required">
                <label for="is_required">این فیلد الزامی است</label>
            </div>
            <div class="form-group">
                <input type="submit" name="add_field" class="btn btn-primary" value="افزودن فیلد">
            </div>
        </form>
    </div>

    <!-- List of Existing Fields -->
    <div class="table-container">
        <h3>فیلدهای موجود</h3>
        <?php if (empty($fields)): ?>
            <p>هیچ فیلدی برای این فرم تعریف نشده است.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>برچسب</th>
                        <th>نوع</th>
                        <th>گزینه‌ها</th>
                        <th>الزامی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $field): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($field['field_label']); ?></td>
                            <td><?php echo htmlspecialchars($field['field_type']); ?></td>
                            <td><?php echo htmlspecialchars($field['field_options']); ?></td>
                            <td><?php echo $field['is_required'] ? 'بله' : 'خیر'; ?></td>
                            <td>
                                <a href="design_form.php?form_id=<?php echo $form_id; ?>&delete_field=<?php echo $field['id']; ?>" class="btn btn-danger" onclick="return confirm('آیا از حذف این فیلد مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleOptionsField() {
    const fieldType = document.getElementById('field_type_select').value;
    const optionsGroup = document.getElementById('options_field_group');
    if (fieldType === 'select' || fieldType === 'checkbox' || fieldType === 'radio') {
        optionsGroup.style.display = 'block';
    } else {
        optionsGroup.style.display = 'none';
    }
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php"; ?>
