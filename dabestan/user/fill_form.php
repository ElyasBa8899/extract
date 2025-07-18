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
$form_id = $_GET['id'] ?? null;

if (!$form_id) {
    // If no form ID is specified, show a list of available forms to the user
    try {
        // This logic can be improved to show only relevant forms to the user
        $forms = $pdo->query("SELECT id, title, description FROM forms")->fetchAll();
    } catch (PDOException $e) {
        $error = "خطا در دریافت لیست فرم‌ها: " . $e->getMessage();
        $forms = [];
    }
} else {
    // Fetch form details and fields
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([$form_id]);
    $form = $stmt->fetch();

    if (!$form) {
        die("فرم مورد نظر یافت نشد.");
    }

    $fields_stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id");
    $fields_stmt->execute([$form_id]);
    $fields = $fields_stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_form'])) {
    $form_id = $_POST['form_id'];
    $user_id = $_SESSION['id'];

    try {
        $pdo->beginTransaction();

        $stmt_sub = $pdo->prepare("INSERT INTO form_submissions (form_id, user_id) VALUES (?, ?)");
        $stmt_sub->execute([$form_id, $user_id]);
        $submission_id = $pdo->lastInsertId();

        $stmt_data = $pdo->prepare("INSERT INTO form_submission_data (submission_id, field_id, field_value) VALUES (?, ?, ?)");

        foreach ($_POST['fields'] as $field_id => $value) {
            $field_value = is_array($value) ? json_encode($value) : trim($value);
            $stmt_data->execute([$submission_id, $field_id, $field_value]);
        }

        $pdo->commit();
        $message = "فرم شما با موفقیت ثبت شد.";
        // Clear form data to prevent re-display
        $form = null;
        $fields = null;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "خطا در ثبت فرم: " . $e->getMessage();
    }
}


?>
<div class="page-content">
    <div class="container-fluid">

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <a href="index.php" class="btn btn-primary">بازگشت به داشبورد</a>
        <?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <?php if (isset($form) && $form): ?>
            <h2><?php echo htmlspecialchars($form['title']); ?></h2>
            <p><?php echo htmlspecialchars($form['description']); ?></p>
            <hr>
            <div class="card">
                <div class="card-body">
                    <form action="fill_form.php" method="post">
                        <input type="hidden" name="form_id" value="<?php echo $form['id']; ?>">

                        <?php foreach ($fields as $field): ?>
                            <div class="form-group">
                                <label for="field_<?php echo $field['id']; ?>"><?php echo htmlspecialchars($field['label']); ?><?php if($field['is_required']) echo ' <span class="text-danger">*</span>'; ?></label>
                                <?php
                                    $options = json_decode($field['options'] ?? '[]', true);
                                    switch ($field['field_type']) {
                                        case 'textarea':
                                            echo "<textarea name='fields[{$field['id']}]' id='field_{$field['id']}' class='form-control' " . ($field['is_required'] ? 'required' : '') . "></textarea>";
                                            break;
                                        case 'select':
                                            echo "<select name='fields[{$field['id']}]' id='field_{$field['id']}' class='form-control' " . ($field['is_required'] ? 'required' : '') . ">";
                                            echo "<option value=''>-- انتخاب کنید --</option>";
                                            foreach ($options as $option) {
                                                echo "<option value='" . htmlspecialchars($option) . "'>" . htmlspecialchars($option) . "</option>";
                                            }
                                            echo "</select>";
                                            break;
                                        case 'radio':
                                            foreach ($options as $option) {
                                                echo "<div class='form-check'><input type='radio' name='fields[{$field['id']}]' value='" . htmlspecialchars($option) . "' class='form-check-input'><label class='form-check-label'>" . htmlspecialchars($option) . "</label></div>";
                                            }
                                            break;
                                        case 'checkbox':
                                            foreach ($options as $option) {
                                                echo "<div class='form-check'><input type='checkbox' name='fields[{$field['id']}][]' value='" . htmlspecialchars($option) . "' class='form-check-input'><label class='form-check-label'>" . htmlspecialchars($option) . "</label></div>";
                                            }
                                            break;
                                        case 'number':
                                            echo "<input type='number' name='fields[{$field['id']}]' id='field_{$field['id']}' class='form-control' " . ($field['is_required'] ? 'required' : '') . ">";
                                            break;
                                        case 'text':
                                        default:
                                            echo "<input type='text' name='fields[{$field['id']}]' id='field_{$field['id']}' class='form-control' " . ($field['is_required'] ? 'required' : '') . ">";
                                            break;
                                    }
                                ?>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" name="submit_form" class="btn btn-primary">ثبت فرم</button>
                    </form>
                </div>
            </div>
        <?php elseif (!isset($form_id)): ?>
            <h2>لیست فرم‌های قابل تکمیل</h2>
            <div class="list-group">
                <?php foreach($forms as $f): ?>
                    <a href="fill_form.php?id=<?php echo $f['id']; ?>" class="list-group-item list-group-item-action">
                        <h5 class="mb-1"><?php echo htmlspecialchars($f['title']); ?></h5>
                        <p class="mb-1"><?php echo htmlspecialchars($f['description']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
