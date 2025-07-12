<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// We assume the form ID is 4, created by the seeder script.
// You might want to fetch this ID dynamically in a real app.
const SELF_ASSESSMENT_FORM_ID = 4;

// Fetch form fields and map them for easier access
$fields_query = mysqli_query($link, "SELECT id, field_label, field_type, field_options, is_required FROM form_fields WHERE form_id = " . SELF_ASSESSMENT_FORM_ID . " ORDER BY field_order ASC");
$all_fields = mysqli_fetch_all($fields_query, MYSQLI_ASSOC);

// Helper function to render a field
function render_field($field) {
    $field_name = 'field_' . $field['id'];
    $required_attr = $field['is_required'] ? 'required' : '';
    $html = '<div class="form-group" data-field-id="' . $field['id'] . '">';
    $html .= '<label for="' . $field_name . '">' . htmlspecialchars($field['field_label']) . ($field['is_required'] ? ' <span style="color:red;">*</span>' : '') . '</label>';

    switch ($field['field_type']) {
        case 'textarea':
            $html .= "<textarea name='{$field_name}' id='{$field_name}' class='form-control' {$required_attr}></textarea>";
            break;
        case 'select':
            $options = explode(',', $field['field_options']);
            $html .= "<select name='{$field_name}' id='{$field_name}' class='form-control' {$required_attr}>";
            $html .= "<option value=''>انتخاب کنید...</option>";
            foreach ($options as $option) {
                $option = trim($option);
                $html .= "<option value='{$option}'>" . htmlspecialchars($option) . "</option>";
            }
            $html .= "</select>";
            break;
        case 'radio':
             $options = explode(',', $field['field_options']);
             foreach ($options as $index => $option) {
                 $option = trim($option);
                 $radio_id = "{$field_name}_{$index}";
                 $html .= "<div class='radio-group'><input type='radio' name='{$field_name}' id='{$radio_id}' value='{$option}' {$required_attr}> <label for='{$radio_id}'>" . htmlspecialchars($option) . "</label></div>";
             }
            break;
        default: // text, number, date
            $html .= "<input type='{$field['field_type']}' name='{$field_name}' id='{$field_name}' class='form-control' {$required_attr}>";
            break;
    }
    $html .= '</div>';
    return $html;
}


// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_form'])) {
    mysqli_begin_transaction($link);
    try {
        $sql_sub = "INSERT INTO form_submissions (form_id, user_id) VALUES (?, ?)";
        $stmt_sub = mysqli_prepare($link, $sql_sub);
        mysqli_stmt_bind_param($stmt_sub, "ii", $form_id, $user_id);
        $form_id = SELF_ASSESSMENT_FORM_ID;
        $user_id = $_SESSION['id'];
        mysqli_stmt_execute($stmt_sub);
        $submission_id = mysqli_insert_id($link);
        mysqli_stmt_close($stmt_sub);

        $sql_data = "INSERT INTO form_submission_data (submission_id, field_id, field_value) VALUES (?, ?, ?)";
        $stmt_data = mysqli_prepare($link, $sql_data);

        foreach ($all_fields as $field) {
            $field_id = $field['id'];
            $post_key = 'field_' . $field_id;
            if (isset($_POST[$post_key])) {
                $field_value = is_array($_POST[$post_key]) ? implode(', ', $_POST[$post_key]) : $_POST[$post_key];
                mysqli_stmt_bind_param($stmt_data, "iis", $submission_id, $field_id, $field_value);
                mysqli_stmt_execute($stmt_data);
            }
        }
        mysqli_stmt_close($stmt_data);

        mysqli_commit($link);
        $success_msg = "فرم خوداظهاری شما با موفقیت ثبت شد.";
    } catch (Exception $e) {
        mysqli_rollback($link);
        $err = "خطا در ثبت فرم: " . $e->getMessage();
    }
}


require_once "../includes/header.php";
?>
<style>
    .form-stepper { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; padding: 10px; background-color: #fff; border-radius: var(--radius-lg); position: sticky; top: 70px; z-index: 998; box-shadow: var(--shadow-sm); }
    .step-btn { background: var(--background-color); border: 1px solid var(--border-color); padding: 8px 16px; font-weight: 600; color: var(--text-muted); cursor: pointer; border-radius: var(--radius-md); transition: all 0.2s; }
    .step-btn.active { color: #fff; background-color: var(--primary-color); border-color: var(--primary-color); }
    .form-section { display: none; animation: fadeIn 0.5s; }
    .form-section.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<div class="page-content">
    <h2>فرم خوداظهاری هفتگی</h2>

    <?php if(isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php else: ?>
        <div class="form-stepper">
            <button class="step-btn active" data-target="section-1">اطلاعات پایه</button>
            <button class="step-btn" data-target="section-2">حضور و غیاب</button>
            <button class="step-btn" data-target="section-3">جزوه و داستان</button>
            <button class="step-btn" data-target="section-4" style="display:none;">بخش تخصصی جزوه</button>
            <button class="step-btn" data-target="section-5">محتوا</button>
            <button class="step-btn" data-target="section-6">توضیحات</button>
        </div>

        <?php if(isset($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; } ?>
        <form id="self-assessment-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-container">

            <div id="section-1" class="form-section active"><?php foreach(array_slice($all_fields, 0, 4) as $field) echo render_field($field); ?></div>
            <div id="section-2" class="form-section"><?php foreach(array_slice($all_fields, 4, 9) as $field) echo render_field($field); ?></div>
            <div id="section-3" class="form-section"><?php foreach(array_slice($all_fields, 13, 3) as $field) echo render_field($field); ?></div>
            <div id="section-4" class="form-section"><?php foreach(array_slice($all_fields, 16, 4) as $field) echo render_field($field); ?></div>
            <div id="section-5" class="form-section"><?php foreach(array_slice($all_fields, 20, 8) as $field) echo render_field($field); ?></div>
            <div id="section-6" class="form-section"><?php foreach(array_slice($all_fields, 28, 1) as $field) echo render_field($field); ?></div>

            <div class="form-group" style="margin-top: 20px;">
                <input type="submit" name="submit_form" class="btn btn-primary" value="ثبت نهایی فرم">
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainForm = document.getElementById('self-assessment-form');
    if(!mainForm) return;

    const stepButtons = document.querySelectorAll('.step-btn');
    const formSections = document.querySelectorAll('.form-section');
    const classTypeSelect = mainForm.querySelector('[name="field_<?php echo $all_fields[0]['id']; ?>"]');
    const jozveSelect = mainForm.querySelector('[name="field_<?php echo $all_fields[13]['id']; ?>"]');
    const specializedSectionTab = document.querySelector('[data-target="section-4"]');

    function switchTab(targetId) {
        stepButtons.forEach(b => b.classList.remove('active'));
        formSections.forEach(s => s.classList.remove('active'));
        const activeBtn = document.querySelector(`[data-target="${targetId}"]`);
        if(activeBtn) activeBtn.classList.add('active');
        const activeSection = document.getElementById(targetId);
        if(activeSection) activeSection.classList.add('active');
    }

    stepButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            switchTab(this.getAttribute('data-target'));
        });
    });

    function handleClassTypeChange() {
        const selectedType = classTypeSelect.value;
        const sectionsToToggle = ['section-2', 'section-3', 'section-4', 'section-5'];
        let show = selectedType === 'عادی';

        sectionsToToggle.forEach(id => {
            const section = document.getElementById(id);
            if (section) section.style.display = show ? 'block' : 'none';
        });

        if (selectedType === 'فوق برنامه' || selectedType === 'تشکیل نشده') {
            // Hide all tabs except the first and last
            stepButtons.forEach(btn => {
                const target = btn.getAttribute('data-target');
                if (target !== 'section-1' && target !== 'section-6') {
                    btn.style.display = 'none';
                }
            });
        } else {
             stepButtons.forEach(btn => btn.style.display = 'inline-block');
             handleJozveChange(); // Re-evaluate jozve section visibility
        }
    }

    function handleJozveChange() {
        const selectedJozve = jozveSelect.value;
        const bazmandehField = mainForm.querySelector('[data-field-id="<?php echo $all_fields[16]['id']; ?>"]');
        const mahnamehFields = [mainForm.querySelector('[data-field-id="<?php echo $all_fields[17]['id']; ?>"]'), mainForm.querySelector('[data-field-id="<?php echo $all_fields[18]['id']; ?>"]')];
        const dastanField = mainForm.querySelector('[data-field-id="<?php echo $all_fields[19]['id']; ?>"]');

        // Hide all specialized fields first
        specializedSectionTab.style.display = 'none';
        if(bazmandehField) bazmandehField.style.display = 'none';
        if(dastanField) dastanField.style.display = 'none';
        mahnamehFields.forEach(f => { if(f) f.style.display = 'none'; });

        if(selectedJozve === 'آخرین بازمانده') {
            specializedSectionTab.style.display = 'inline-block';
            if(bazmandehField) bazmandehField.style.display = 'block';
        } else if (selectedJozve === 'ماهنامه') {
            specializedSectionTab.style.display = 'inline-block';
            mahnamehFields.forEach(f => { if(f) f.style.display = 'block'; });
        } else if (selectedJozve.includes('داستان')) {
            specializedSectionTab.style.display = 'inline-block';
            if(dastanField) dastanField.style.display = 'block';
        }
    }

    if(classTypeSelect) classTypeSelect.addEventListener('change', handleClassTypeChange);
    if(jozveSelect) jozveSelect.addEventListener('change', handleJozveChange);

    // Initial state
    handleClassTypeChange();
});
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
