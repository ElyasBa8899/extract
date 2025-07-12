<?php
require_once "../includes/db.php";

echo "<h1>Seeding Self-Assessment Form...</h1>";

// --- Form Definition ---
$form_name = "فرم خوداظهاری هفتگی مدرس";
$form_description = "این فرم به صورت هفتگی توسط مدرسین برای گزارش وضعیت کلاس تکمیل می‌شود.";
$admin_user_id = 1; // Assuming admin user has ID 1

// --- Questions Definition ---
$sections = [
    "اطلاعات پایه" => [
        ['label' => 'نوع کلاس برگزار شده', 'type' => 'select', 'options' => 'عادی,فوق برنامه,تشکیل نشده'],
        ['label' => 'تاریخ روز جلسه', 'type' => 'select', 'options' => implode(',', range(1, 31))],
        ['label' => 'تاریخ ماه جلسه', 'type' => 'select', 'options' => implode(',', range(1, 12))],
        ['label' => 'تاریخ سال جلسه', 'type' => 'select', 'options' => implode(',', range(1403, 1503))],
    ],
    "حضور و غیاب" => [
        ['label' => 'مدرسین قبل از جلسه هماهنگی داشته اند؟', 'type' => 'radio', 'options' => 'بله,خیر'],
        ['label' => 'زمان هماهنگی قبل از جلسه', 'type' => 'select', 'options' => 'کمتر از نیم ساعت,بین نیم تا دو ساعت,بیش از دو ساعت,نداشتیم'],
        ['label' => 'مدرسین قبل از جلسه توسل داشته اند', 'type' => 'radio', 'options' => 'بله,خیر'],
        ['label' => 'وضعیت حضور مدرس اول', 'type' => 'select', 'options' => 'راس ساعت,با تاخیر تا ده دقیقه,تاخیر بیش از ده دقیقه,غیبت'],
        ['label' => 'وضعیت حضور مدرس دوم', 'type' => 'select', 'options' => 'راس ساعت,با تاخیر تا ده دقیقه,تاخیر بیش از ده دقیقه,غیبت'],
        ['label' => 'وضعیت حضور مدرس سوم', 'type' => 'select', 'options' => 'راس ساعت,با تاخیر تا ده دقیقه,تاخیر بیش از ده دقیقه,غیبت', 'required' => false],
        ['label' => 'تعداد غائبین این جلسه', 'type' => 'number'],
        ['label' => 'اسامی غایبین این جلسه', 'type' => 'textarea', 'required' => false],
        ['label' => 'با غائبین بدون اطلاع تماس گرفته شده', 'type' => 'select', 'options' => 'بله,خیر,غایب بدون اطلاع نداشتیم'],
    ],
    "جزوه و داستان" => [
        ['label' => 'جزوه و داستان', 'type' => 'select', 'options' => 'آخرین بازمانده,ماهنامه,داستان با هماهنگی,داستان بدون هماهنگی,عدم اجرا'],
        ['label' => 'زمان جزوه', 'type' => 'select', 'options' => 'کمتر از 15 دقیقه,بین 15 تا 30 دقیقه,بیش از 30 دقیقه,عدم اجرا'],
        ['label' => 'اجرای جزوه', 'type' => 'select', 'options' => 'مدرس اول,مدرس دوم,مدرس سوم,به صورت مشترک,عدم اجرا'],
    ],
    "بخش تخصصی جزوه" => [ // Conditional section
        ['label' => 'کدام درس از جزوه اخرین بازمانده رو تدریس کردید', 'type' => 'select', 'options' => 'درس اول,درس دوم,درس سوم,درس چهارم,درس پنجم,درس ششم,درس هفتم,درس هشتم,درس نهم,درس دهم,درس یازدهم,درس دوازدهم,درس سیزدهم,درس چهاردهم'],
        ['label' => 'کدام جلد از جزوه ماهنامه را تدریس کردید', 'type' => 'select', 'options' => 'محرم,صفر,ربیع الاول,ربیع الثانی,جمادی الاول,جمادی الثانی,رجب,شعبان,رمضان,شوال,ذی القعده,ذی الحجه'],
        ['label' => 'درس چندم جزوه ماهنامه را تدریس کردید', 'type' => 'select', 'options' => 'درس اول,درس دوم,درس سوم,درس چهارم'],
        ['label' => 'عنوان داستان گفته شده', 'type' => 'text'],
    ],
    "محتوا" => [
        ['label' => 'نوع یادحضرت', 'type' => 'select', 'options' => 'طبق چارت,با هماهنگی,بدون هماهنگی,عدم اجرا'],
        ['label' => 'زمان یادحضرت', 'type' => 'select', 'options' => 'کمتر از 15 دقیقه,بین 15 تا 30 دقیقه,بیش از 30 دقیقه,عدم اجرا'],
        ['label' => 'عنوان یاد حضرت', 'type' => 'text'],
        ['label' => 'نوع بازی', 'type' => 'select', 'options' => 'کانال بازی,بازی جدید,عدم اجرا'],
        ['label' => 'زمان بازی', 'type' => 'select', 'options' => 'کمتر از 30 دقیقه,بین 30 تا 45 دقیقه,بیش از 45 دقیقه,عدم اجرا'],
        ['label' => 'اجرا بازی', 'type' => 'select', 'options' => 'مدرس اول,مدرس دوم,مدرس سوم,به صورت مشترک,عدم اجرا'],
        ['label' => 'محتوای دیگر ارائه شده', 'type' => 'select', 'options' => 'احکام,قرآن,مناسبت,نداشتیم'],
        ['label' => 'در ارائه محتوا خلاقیت داشتید؟', 'type' => 'radio', 'options' => 'بله (لطفا در بخش توضیحات شرح دهید),خیر'],
    ],
    "توضیحات" => [
         ['label' => 'توضیحات', 'type' => 'textarea', 'required' => false],
    ]
];

mysqli_begin_transaction($link);
try {
    // 1. Create the form
    $sql_form = "INSERT INTO forms (form_name, form_description, created_by) VALUES (?, ?, ?)";
    $stmt_form = mysqli_prepare($link, $sql_form);
    mysqli_stmt_bind_param($stmt_form, "ssi", $form_name, $form_description, $admin_user_id);
    mysqli_stmt_execute($stmt_form);
    $form_id = mysqli_insert_id($link);
    echo "Form '{$form_name}' created with ID: {$form_id}<br>";

    // 2. Insert fields
    $sql_field = "INSERT INTO form_fields (form_id, field_label, field_type, field_options, is_required, field_order) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_field = mysqli_prepare($link, $sql_field);
    $order = 0;

    foreach($sections as $section_name => $fields){
        // You could add a field for section name if you modify the table
        foreach($fields as $field){
            $label = $field['label'];
            $type = $field['type'];
            $options = $field['options'] ?? '';
            $required = $field['required'] ?? true; // Default to required
            $is_req_val = $required ? 1 : 0;

            mysqli_stmt_bind_param($stmt_field, "isssii", $form_id, $label, $type, $options, $is_req_val, $order);
            mysqli_stmt_execute($stmt_field);
            $order++;
        }
    }
    mysqli_stmt_close($stmt_field);

    mysqli_commit($link);
    echo "<h2>All fields seeded successfully!</h2>";
    echo "<p style='color:red;'>Please delete this file now.</p>";

} catch (Exception $e) {
    mysqli_rollback($link);
    echo "An error occurred: " . $e->getMessage();
}

mysqli_close($link);
?>
