<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/access_control.php";
require_once "../includes/header.php";

if (!is_super_admin()) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['form_id']) || empty($_GET['form_id'])) {
    header("location: manage_forms.php");
    exit;
}

$form_id = $_GET['form_id'];
$form = mysqli_fetch_assoc(mysqli_query($link, "SELECT title FROM forms WHERE id = $form_id"));

// Handle form submissions for adding/editing fields
$field_id = $label = $field_type = $options = "";
$is_required = 0;
$field_order = 0;
$form_err = "";
$update_mode = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add or Update Field
    if (isset($_POST['save_field'])) {
        $field_id = $_POST['field_id'];
        $label = trim($_POST['label']);
        $field_type = $_POST['field_type'];
        $options = trim($_POST['options']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $field_order = (int)$_POST['field_order'];

        if (empty($label) || empty($field_type)) {
            $form_err = "برچسب و نوع فیلد الزامی هستند.";
        }

        if (empty($form_err)) {
            if (empty($field_id)) { // Add new field
                $sql = "INSERT INTO form_fields (form_id, label, field_type, options, is_required, field_order) VALUES (?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "isssii", $form_id, $label, $field_type, $options, $is_required, $field_order);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $_SESSION['success_message'] = "فیلد با موفقیت اضافه شد.";
                }
            } else { // Update existing field
                $sql = "UPDATE form_fields SET label = ?, field_type = ?, options = ?, is_required = ?, field_order = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sssiii", $label, $field_type, $options, $is_required, $field_order, $field_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $_SESSION['success_message'] = "فیلد با موفقیت ویرایش شد.";
                }
            }
            header("location: manage_form_fields.php?form_id=" . $form_id);
            exit;
        }
    }

    // Delete Field
    if (isset($_POST['delete_field'])) {
        $field_id = $_POST['field_id'];
        $sql = "DELETE FROM form_fields WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $field_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['success_message'] = "فیلد با موفقیت حذف شد.";
        }
        header("location: manage_form_fields.php?form_id=" . $form_id);
        exit;
    }
}

// Fetch field data for editing
if (isset($_GET['edit'])) {
    $field_id = $_GET['edit'];
    $sql = "SELECT id, label, field_type, options, is_required, field_order FROM form_fields WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $field_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($field = mysqli_fetch_assoc($result)) {
            $update_mode = true;
            $label = $field['label'];
            $field_type = $field['field_type'];
            $options = $field['options'];
            $is_required = $field['is_required'];
            $field_order = $field['field_order'];
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all fields for this form
$fields = mysqli_query($link, "SELECT * FROM form_fields WHERE form_id = $form_id ORDER BY field_order ASC");
?>

<div class="page-content">
    <div class="container-fluid">
        <a href="manage_forms.php" class="btn btn-secondary mb-3">< بازگشت به لیست فرم‌ها</a>
        <h2>مدیریت فیلدهای فرم: <?php echo htmlspecialchars($form['title']); ?></h2>

        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        if (!empty($form_err)) {
            echo '<div class="alert alert-danger">' . $form_err . '</div>';
        }
        ?>

        <!-- Add/Edit Field Form -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo $update_mode ? 'ویرایش فیلد' : 'افزودن فیلد جدید'; ?></h3>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?form_id=<?php echo $form_id; ?>" method="post">
                    <input type="hidden" name="field_id" value="<?php echo $field_id; ?>">
                    <div class="form-group">
                        <label for="label">برچسب (سوال)</label>
                        <input type="text" name="label" id="label" class="form-control" value="<?php echo htmlspecialchars($label); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="field_type">نوع فیلد</label>
                        <select name="field_type" id="field_type" class="form-control" required>
                            <option value="text" <?php echo $field_type == 'text' ? 'selected' : ''; ?>>متن کوتاه</option>
                            <option value="textarea" <?php echo $field_type == 'textarea' ? 'selected' : ''; ?>>متن بلند</option>
                            <option value="select" <?php echo $field_type == 'select' ? 'selected' : ''; ?>>لیست کشویی</option>
                            <option value="checkbox" <?php echo $field_type == 'checkbox' ? 'selected' : ''; ?>>چک‌باکس</option>
                            <option value="radio" <?php echo $field_type == 'radio' ? 'selected' : ''; ?>>دکمه رادیویی</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="options">گزینه‌ها (برای لیست کشویی، چک‌باکس و رادیویی)</label>
                        <textarea name="options" id="options" class="form-control" placeholder="هر گزینه در یک خط جدید"><?php echo htmlspecialchars($options); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="field_order">ترتیب نمایش</label>
                        <input type="number" name="field_order" id="field_order" class="form-control" value="<?php echo $field_order; ?>" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="is_required" id="is_required" class="form-check-input" value="1" <?php echo $is_required ? 'checked' : ''; ?>>
                        <label for="is_required" class="form-check-label">الزامی است؟</label>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="save_field" class="btn btn-primary"><?php echo $update_mode ? 'ذخیره تغییرات' : 'افزودن فیلد'; ?></button>
                        <?php if ($update_mode): ?>
                            <a href="manage_form_fields.php?form_id=<?php echo $form_id; ?>" class="btn btn-secondary">انصراف</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Fields List -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3>لیست فیلدها</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ترتیب</th>
                                <th>برچسب</th>
                                <th>نوع</th>
                                <th>الزامی</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($fields)): ?>
                                <tr>
                                    <td><?php echo $row['field_order']; ?></td>
                                    <td><?php echo htmlspecialchars($row['label']); ?></td>
                                    <td><?php echo $row['field_type']; ?></td>
                                    <td><?php echo $row['is_required'] ? 'بله' : 'خیر'; ?></td>
                                    <td>
                                        <a href="manage_form_fields.php?form_id=<?php echo $form_id; ?>&edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">ویرایش</a>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?form_id=<?php echo $form_id; ?>" method="post" style="display: inline-block;">
                                            <input type="hidden" name="field_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_field" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این فیلد اطمینان دارید؟');">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
