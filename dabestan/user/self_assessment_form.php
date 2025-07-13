<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// We assume the form ID is 1 for the self-assessment form.
const SELF_ASSESSMENT_FORM_ID = 1;

// Fetch form fields and map them for easier access by label
$fields_query = mysqli_query($link, "SELECT id, field_label, field_type, field_options, is_required FROM form_fields WHERE form_id = " . SELF_ASSESSMENT_FORM_ID . " ORDER BY field_order ASC");
$all_fields = [];
if($fields_query) {
    while ($row = mysqli_fetch_assoc($fields_query)) {
        $all_fields[$row['field_label']] = $row;
    }
}

// Helper function to render a field by its label
function render_field_by_label($label, $all_fields_map) {
    if (!isset($all_fields_map[$label])) return "<div>Field '{$label}' not found!</div>";

    $field = $all_fields_map[$label];
    $field_name = 'field_' . $field['id'];
    $required_attr = $field['is_required'] ? 'required' : '';
    $html = '<div class="form-group" data-field-label="' . htmlspecialchars($label) . '">';
    $html .= '<label for="' . $field_name . '">' . htmlspecialchars($field['field_label']) . ($field['is_required'] ? ' <span class="required-star">*</span>' : '') . '</label>';

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
        default:
            $html .= "<input type='{$field['field_type']}' name='{$field_name}' id='{$field_name}' class='form-control' {$required_attr}>";
            break;
    }
    $html .= '</div>';
    return $html;
}

// ... (Handle Form Submission - to be added)


require_once "../includes/header.php";
?>
<style>
    .form-stepper { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; padding: 10px; background-color: #fff; border-radius: var(--radius-lg); position: sticky; top: 70px; /* Height of header */ z-index: 998; box-shadow: var(--shadow-md); }
    .step-btn { background: var(--background-color); border: 1px solid var(--border-color); padding: 8px 16px; font-weight: 600; color: var(--text-muted); cursor: pointer; border-radius: var(--radius-md); transition: all 0.2s; }
    .step-btn.active { color: #fff; background-color: var(--primary-color); border-color: var(--primary-color); }
    .form-section { display: none; animation: fadeIn 0.5s; }
    .form-section.active { display: block; }
    .required-star { color: var(--danger-color); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="page-content">
    <h2>فرم خوداظهاری هفتگی</h2>

    <div class="form-stepper">
        <button class="step-btn active" data-target="section-1">اطلاعات پایه</button>
        <button class="step-btn" data-target="section-2">حضور و غیاب</button>
        <button class="step-btn" data-target="section-3">جزوه و داستان</button>
        <button class="step-btn" data-target="section-4" style="display:none;">بخش تخصصی جزوه</button>
        <button class="step-btn" data-target="section-5">محتوا</button>
        <button class="step-btn" data-target="section-6">توضیحات</button>
    </div>

    <form id="self-assessment-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-container" novalidate>

        <div id="section-1" class="form-section active">
            <h3>اطلاعات پایه</h3>
            <?php echo render_field_by_label('نوع کلاس برگزار شده', $all_fields); ?>
            <?php echo render_field_by_label('تاریخ روز جلسه', $all_fields); ?>
            <?php echo render_field_by_label('تاریخ ماه جلسه', $all_fields); ?>
            <?php echo render_field_by_label('تاریخ سال جلسه', $all_fields); ?>
        </div>

        <div id="section-2" class="form-section">
            <h3>حضور و غیاب</h3>
            <?php echo render_field_by_label('مدرسین قبل از جلسه هماهنگی داشته اند؟', $all_fields); ?>
            <?php echo render_field_by_label('زمان هماهنگی قبل از جلسه', $all_fields); ?>
            <?php echo render_field_by_label('مدرسین قبل از جلسه توسل داشته اند', $all_fields); ?>
            <?php echo render_field_by_label('وضعیت حضور مدرس اول', $all_fields); ?>
            <?php echo render_field_by_label('وضعیت حضور مدرس دوم', $all_fields); ?>
            <?php echo render_field_by_label('وضعیت حضور مدرس سوم', $all_fields); ?>
            <?php echo render_field_by_label('تعداد غائبین این جلسه', $all_fields); ?>
            <?php echo render_field_by_label('اسامی غایبین این جلسه', $all_fields); ?>
            <?php echo render_field_by_label('با غائبین بدون اطلاع تماس گرفته شده', $all_fields); ?>
        </div>

        <div id="section-3" class="form-section">
            <h3>جزوه و داستان</h3>
            <?php echo render_field_by_label('جزوه و داستان', $all_fields); ?>
            <?php echo render_field_by_label('زمان جزوه', $all_fields); ?>
            <?php echo render_field_by_label('اجرای جزوه', $all_fields); ?>
        </div>

        <div id="section-4" class="form-section">
             <h3>بخش تخصصی جزوه</h3>
            <?php echo render_field_by_label('کدام درس از جزوه اخرین بازمانده رو تدریس کردید', $all_fields); ?>
            <?php echo render_field_by_label('کدام جلد از جزوه ماهنامه را تدریس کردید', $all_fields); ?>
            <?php echo render_field_by_label('درس چندم جزوه ماهنامه را تدریس کردید', $all_fields); ?>
            <?php echo render_field_by_label('عنوان داستان گفته شده', $all_fields); ?>
        </div>

        <div id="section-5" class="form-section">
             <h3>محتوا</h3>
            <?php echo render_field_by_label('نوع یادحضرت', $all_fields); ?>
            <?php echo render_field_by_label('زمان یادحضرت', $all_fields); ?>
            <?php echo render_field_by_label('عنوان یاد حضرت', $all_fields); ?>
            <?php echo render_field_by_label('نوع بازی', $all_fields); ?>
            <?php echo render_field_by_label('زمان بازی', $all_fields); ?>
            <?php echo render_field_by_label('اجرا بازی', $all_fields); ?>
            <?php echo render_field_by_label('محتوای دیگر ارائه شده', $all_fields); ?>
            <?php echo render_field_by_label('در ارائه محتوا خلاقیت داشتید؟', $all_fields); ?>
        </div>

        <div id="section-6" class="form-section">
            <h3>توضیحات</h3>
            <?php echo render_field_by_label('توضیحات', $all_fields); ?>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <input type="submit" name="submit_form" class="btn btn-primary" value="ثبت نهایی فرم">
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainForm = document.getElementById('self-assessment-form');
    if (!mainForm) return;

    const stepButtons = document.querySelectorAll('.step-btn');
    const formSections = document.querySelectorAll('.form-section');

    // --- Navigation ---
    stepButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');

            stepButtons.forEach(b => b.classList.remove('active'));
            formSections.forEach(s => s.classList.remove('active'));

            this.classList.add('active');
            const targetSection = document.getElementById(targetId);
            targetSection.classList.add('active');

            // Scroll to the top of the section
            targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // --- Conditional Logic ---
    const classTypeSelect = mainForm.querySelector('[data-field-label="نوع کلاس برگزار شده"] select');
    const jozveSelect = mainForm.querySelector('[data-field-label="جزوه و داستان"] select');

    function toggleVisibility() {
        const classType = classTypeSelect ? classTypeSelect.value : '';
        const jozveType = jozveSelect ? jozveSelect.value : '';

        // Toggle main sections based on class type
        const isNormalClass = classType === 'عادی';
        ['section-2', 'section-3', 'section-4', 'section-5'].forEach(id => {
            const sectionTab = document.querySelector(`[data-target="${id}"]`);
            if (sectionTab) sectionTab.style.display = isNormalClass ? 'inline-flex' : 'none';
        });

        // Toggle specialized jozve section
        const specializedTab = document.querySelector('[data-target="section-4"]');
        const showSpecialized = isNormalClass && (jozveType === 'آخرین بازمانده' || jozveType === 'ماهنامه' || jozveType.includes('داستان'));
        if (specializedTab) specializedTab.style.display = showSpecialized ? 'inline-flex' : 'none';

        // Toggle individual fields within specialized section
        const bazmandehField = mainForm.querySelector('[data-field-label="کدام درس از جزوه اخرین بازمانده رو تدریس کردید"]');
        const mahnamehFields = [mainForm.querySelector('[data-field-label="کدام جلد از جزوه ماهنامه را تدریس کردید"]'), mainForm.querySelector('[data-field-label="درس چندم جزوه ماهنامه را تدریس کردید"]')];
        const dastanField = mainForm.querySelector('[data-field-label="عنوان داستان گفته شده"]');

        if(bazmandehField) bazmandehField.style.display = (showSpecialized && jozveType === 'آخرین بازمانده') ? 'block' : 'none';
        mahnamehFields.forEach(f => { if(f) f.style.display = (showSpecialized && jozveType === 'ماهنامه') ? 'block' : 'none'; });
        if(dastanField) dastanField.style.display = (showSpecialized && jozveType.includes('داستان')) ? 'block' : 'none';
    }

    if (classTypeSelect) classTypeSelect.addEventListener('change', toggleVisibility);
    if (jozveSelect) jozveSelect.addEventListener('change', toggleVisibility);

    // --- Validation on Submit ---
    mainForm.addEventListener('submit', function(e) {
        let firstErrorField = null;

        for (const section of formSections) {
            if (section.offsetParent === null) continue; // Skip hidden sections

            for (const field of section.querySelectorAll('[required]')) {
                if ((field.type === 'radio' && !mainForm.querySelector(`[name="${field.name}"]:checked`)) || (field.value.trim() === '')) {
                    firstErrorField = field;
                    break;
                }
            }
            if (firstErrorField) break;
        }

        if (firstErrorField) {
            e.preventDefault();
            const errorSection = firstErrorField.closest('.form-section');
            const errorSectionId = errorSection.id;

            // Switch to the tab with the error
            document.querySelector(`.step-btn[data-target="${errorSectionId}"]`).click();

            // Focus on the field
            firstErrorField.focus();
            firstErrorField.style.borderColor = 'var(--danger-color)';
            alert('لطفاً تمام فیلدهای ستاره‌دار را تکمیل کنید.');
        }
    });

    // Initial state setup
    toggleVisibility();
});
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
