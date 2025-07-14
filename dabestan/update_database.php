<?php
// This script applies database migrations.
// It's safe to run this multiple times. Already applied migrations will be skipped.

session_start();
require_once "includes/db.php"; // Connect to the database

// --- Basic Security: Only allow admins to run this ---
// This check is temporarily removed as requested by the user for local testing.
// It should be re-enabled for production.
/*
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || empty($_SESSION["is_admin"])) {
    header("HTTP/1.1 403 Forbidden");
    echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page. Please ensure you are logged in as an administrator.</p>";
    exit;
}
*/
// ----------------------------------------------------

// Table to track which migrations have been run
$migrations_table = 'schema_migrations';

// Create migrations table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS `$migrations_table` (
  `version` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (!mysqli_query($link, $create_table_sql)) {
    die("Fatal Error: Could not create migrations tracking table. " . mysqli_error($link));
}

// Get all migrations that have already been run
$applied_migrations_q = mysqli_query($link, "SELECT version FROM `$migrations_table`");
$applied_migrations = [];
while ($row = mysqli_fetch_assoc($applied_migrations_q)) {
    $applied_migrations[] = $row['version'];
}

// Find all available migration files
$migration_files_path = __DIR__ . '/setup/migrations/';
$all_migration_files = glob($migration_files_path . '*.php');
sort($all_migration_files); // Sort files to ensure they run in order

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>به‌روزرسانی پایگاه داده</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Vazirmatn', sans-serif; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        .migration-log { margin-top: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>روند به‌روزرسانی پایگاه داده</h1>
        <div class="migration-log">
            <?php
            $updates_applied_in_this_run = 0;

            foreach ($all_migration_files as $file) {
                $version = basename($file, '.php');

                if (in_array($version, $applied_migrations)) {
                    // Skip already applied migrations
                    continue;
                }

                echo "<h3>اجرای نسخه: $version</h3>";
                require_once $file;

                $function_name = 'run_migration_' . preg_replace('/_/', '', str_replace('-', '_', $version), 1);

                if (function_exists($function_name)) {
                    mysqli_begin_transaction($link);
                    try {
                        $result = $function_name($link);
                        if ($result === true) {
                            // Mark this migration as applied
                            $stmt = mysqli_prepare($link, "INSERT INTO `$migrations_table` (version) VALUES (?)");
                            mysqli_stmt_bind_param($stmt, "s", $version);
                            mysqli_stmt_execute($stmt);
                            mysqli_commit($link);
                            echo "<p class='alert alert-success'>نسخه <strong>$version</strong> با موفقیت اعمال و در سیستم ثبت شد.</p><hr>";
                            $updates_applied_in_this_run++;
                        } else {
                            throw new Exception("تابع به‌روزرسانی برای نسخه $version مقدار true برنگرداند.");
                        }
                    } catch (Exception $e) {
                        mysqli_rollback($link);
                        echo "<p class='alert alert-danger'><strong>خطا!</strong> عملیات برای نسخه <strong>$version</strong> لغو شد. پیام خطا: " . $e->getMessage() . "</p><hr>";
                    }
                } else {
                    echo "<p class='alert alert-danger'>خطا: تابع <code>$function_name</code> در فایل <code>$file</code> یافت نشد.</p><hr>";
                }
            }

            if ($updates_applied_in_this_run == 0) {
                echo "<h3 style='text-align: center;' class='alert alert-info'>پایگاه داده شما به‌روز است. هیچ به‌روزرسانی جدیدی برای اجرا یافت نشد.</h3>";
            } else {
                 echo "<h3 style='text-align: center;' class='alert alert-success'>عملیات با موفقیت انجام شد. مجموعاً $updates_applied_in_this_run به‌روزرسانی جدید اعمال گردید.</h3>";
            }
            ?>
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="btn btn-primary">بازگشت به صفحه اصلی</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
if (isset($link) && $link instanceof mysqli && $link->thread_id) {
    $link->close();
}
?>
