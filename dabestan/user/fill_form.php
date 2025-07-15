<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['form_id']) || empty($_GET['form_id'])) {
    header("location: list_forms.php");
    exit;
}

$form_id = $_GET['form_id'];
$err = $success_msg = "";

// Fetch form details
$form = null;
$sql_form = "SELECT form_name, form_description FROM forms WHERE id = ?";
if($stmt_form = mysqli_prepare($link, $sql_form)){
    mysqli_stmt_bind_param($stmt_form, "i", $form_id);
    mysqli_stmt_execute($stmt_form);
    $result_form = mysqli_stmt_get_result($stmt_form);
    $form = mysqli_fetch_assoc($result_form);
    mysqli_stmt_close($stmt_form);
}

if(!$form){
    echo "فرم یافت نشد.";
    exit;
}

// Fetch form fields
$fields = [];
$sql_fields = "SELECT id, field_label, field_type, field_options, is_required FROM form_fields WHERE form_id = ? ORDER BY field_order ASC";
if($stmt_fields = mysqli_prepare($link, $sql_fields)){
    mysqli_stmt_bind_param($stmt_fields, "i", $form_id);
    mysqli_stmt_execute($stmt_fields);
    $result_fields = mysqli_stmt_get_result($stmt_fields);
    $fields = mysqli_fetch_all($result_fields, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_fields);
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_form'])) {
    // Start transaction
    mysqli_begin_transaction($link);

    try {
        // 1. Create a new submission record
        $sql_insert_submission = "INSERT INTO form_submissions (form_id, user_id) VALUES (?, ?)";
        $stmt_insert_submission = mysqli_prepare($link, $sql_insert_submission);
        mysqli_stmt_bind_param($stmt_insert_submission, "ii", $form_id, $_SESSION['id']);
        mysqli_stmt_execute($stmt_insert_submission);
        $submission_id = mysqli_insert_id($stmt_insert_submission);
        mysqli_stmt_close($stmt_insert_submission);

        // 2. Insert each field's data
        $sql_insert_data = "INSERT INTO form_submission_data (submission_id, field_id, field_value) VALUES (?, ?, ?)";
        $stmt_insert_data = mysqli_prepare($link, $sql_insert_data);

        foreach ($fields as $field) {
            $field_id = $field['id'];
            $post_key = 'field_' . $field_id;
            $field_value = isset($_POST[$post_key]) ? $_POST[$post_key] : '';

            // For checkbox, value is an array
            if (is_array($field_value)) {
                $field_value = implode(', ', $field_value);
            }

            mysqli_stmt_bind_param($stmt_insert_data, "iis", $submission_id, $field_id, $field_value);
            mysqli_stmt_execute($stmt_insert_data);
        }
        mysqli_stmt_close($stmt_insert_data);

        // If all good, commit the transaction
        mysqli_commit($link);
        $success_msg = "فرم شما با موفقیت ثبت شد. از همکاری شما سپاسگزاریم.";

    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($link);
        $err = "خطایی در ثبت اطلاعات رخ داد. لطفا دوباره تلاش کنید.";
        // You can log the detailed error: $exception->getMessage();
    }
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="list_forms.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به لیست فرم‌ها</a>
    <h2>تکمیل فرم: <?php echo htmlspecialchars($form['form_name']); ?></h2>
    <p><?php echo htmlspecialchars($form['form_description']); ?></p>

    <?php if(!empty($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php else: ?>
        <div class="form-container">
            <?php if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; } ?>
            <form action="fill_form.php?form_id=<?php echo $form_id; ?>" method="post">
                <?php foreach ($fields as $field): ?>
                    <div class="form-group">
                        <label for="field_<?php echo $field['id']; ?>">
                            <?php echo htmlspecialchars($field['field_label']); ?>
                            <?php if ($field['is_required']): ?><span style="color: red;">*</span><?php endif; ?>
                        </label>

                        <?php
                        $field_name = 'field_' . $field['id'];
                        $required_attr = $field['is_required'] ? 'required' : '';

                        switch ($field['field_type']) {
                            case 'textarea':
                                echo "<textarea name='{$field_name}' id='{$field_name}' class='form-control' {$required_attr}></textarea>";
                                break;

                            case 'select':
                                $options = explode(',', $field['field_options']);
                                echo "<select name='{$field_name}' id='{$field_name}' class='form-control' {$required_attr}>";
                                echo "<option value=''>انتخاب کنید...</option>";
                                foreach ($options as $option) {
                                    $option = trim($option);
                                    echo "<option value='{$option}'>" . htmlspecialchars($option) . "</option>";
                                }
                                echo "</select>";
                                break;

                            case 'checkbox':
                                $options = explode(',', $field['field_options']);
                                foreach ($options as $index => $option) {
                                    $option = trim($option);
                                    $checkbox_id = "{$field_name}_{$index}";
                                    echo "<div class='checkbox-group'><input type='checkbox' name='{$field_name}[]' id='{$checkbox_id}' value='{$option}'> <label for='{$checkbox_id}'>" . htmlspecialchars($option) . "</label></div>";
                                }
                                break;

                            case 'radio':
                                $options = explode(',', $field['field_options']);
                                foreach ($options as $index => $option) {
                                    $option = trim($option);
                                    $radio_id = "{$field_name}_{$index}";
                                    echo "<div class='radio-group'><input type='radio' name='{$field_name}' id='{$radio_id}' value='{$option}' {$required_attr}> <label for='{$radio_id}'>" . htmlspecialchars($option) . "</label></div>";
                                }
                                break;

                            default: // text, number, date
                                echo "<input type='{$field['field_type']}' name='{$field_name}' id='{$field_name}' class='form-control' {$required_attr}>";
                                break;
                        }
                        ?>
                    </div>
                <?php endforeach; ?>

                <div class="form-group">
                    <input type="submit" name="submit_form" class="btn btn-primary" value="ثبت نهایی فرم">
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
