<?php
session_start();
require_once "includes/db.php"; // Ensure you have your DB connection details here

// --- Security Check: Only Admins can run this ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: index.php");
    exit;
}

$page_title = "به‌روزرسانی پایگاه داده";
require_once "includes/header.php";

// Array of update files in the correct order
$update_files = [
    'database_update.sql',
    'database_update_v2.sql',
    'database_update_v3.sql',
    'database_update_v4.sql',
    'database_update_v5.sql'
    // Add new update files here in the future
];

$success_messages = [];
$error_messages = [];

// --- Check and create schema_migrations table if it doesn't exist ---
$check_table_sql = "SHOW TABLES LIKE 'schema_migrations'";
$table_exists_result = mysqli_query($link, $check_table_sql);
if (mysqli_num_rows($table_exists_result) == 0) {
    $create_table_sql = "
        CREATE TABLE `schema_migrations` (
          `version` varchar(255) NOT NULL,
          `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    if (mysqli_query($link, $create_table_sql)) {
        $success_messages[] = "جدول `schema_migrations` با موفقیت ایجاد شد.";
    } else {
        $error_messages[] = "خطا در ایجاد جدول `schema_migrations`: " . mysqli_error($link);
    }
}

// --- Process each update file ---
foreach ($update_files as $file) {
    // Check if this version has already been applied
    $version = mysqli_real_escape_string($link, $file);
    $check_version_sql = "SELECT version FROM schema_migrations WHERE version = '$version'";
    $version_result = mysqli_query($link, $check_version_sql);

    if (mysqli_num_rows($version_result) > 0) {
        $success_messages[] = "به‌روزرسانی '{$file}' قبلاً اعمال شده است. (نادیده گرفته شد)";
        continue; // Skip to the next file
    }

    if (file_exists($file)) {
        $sql_content = file_get_contents($file);
        // Split SQL file into individual queries
        $queries = preg_split('/;(\r\n|\n|\r)/', $sql_content, -1, PREG_SPLIT_NO_EMPTY);

        $all_queries_successful = true;
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (mysqli_query($link, $query)) {
                    // Query was successful
                } else {
                    // If one query fails, stop processing this file
                    $error_messages[] = "خطا در اجرای بخشی از '{$file}': " . mysqli_error($link) . "<br><pre>{$query}</pre>";
                    $all_queries_successful = false;
                    break;
                }
            }
        }

        // If all queries in the file were successful, record the version
        if ($all_queries_successful) {
            $success_messages[] = "فایل '{$file}' با موفقیت اجرا شد.";
            $record_version_sql = "INSERT INTO schema_migrations (version) VALUES ('$version')";
            mysqli_query($link, $record_version_sql);
        }

    } else {
        $error_messages[] = "فایل به‌روزرسانی '{$file}' یافت نشد.";
    }
}

?>

<div class="page-content">
    <h2><i class="fas fa-database"></i> نتیجه به‌روزرسانی پایگاه داده</h2>

    <?php if (!empty($success_messages)): ?>
        <div class="alert alert-success">
            <h4><i class="fas fa-check-circle"></i> موفقیت‌آمیز</h4>
            <ul>
                <?php foreach ($success_messages as $msg): ?>
                    <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_messages)): ?>
        <div class="alert alert-danger">
            <h4><i class="fas fa-times-circle"></i> خطاها</h4>
            <ul>
                <?php foreach ($error_messages as $msg): ?>
                    <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
         <div class="alert alert-info">
            <p>تمام به‌روزرسانی‌های لازم با موفقیت بررسی و اعمال شدند. پایگاه داده شما به‌روز است.</p>
        </div>
    <?php endif; ?>

    <a href="admin/index.php" class="btn btn-primary">بازگشت به پنل مدیریت</a>
</div>

<?php
require_once "includes/footer.php";
?>
