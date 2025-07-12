<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$err = $success_msg = "";

// --- Schema Migration: Add telegram_chat_id column if it doesn't exist ---
$check_column_query = mysqli_query($link, "SHOW COLUMNS FROM `users` LIKE 'telegram_chat_id'");
if (mysqli_num_rows($check_column_query) == 0) {
    mysqli_query($link, "ALTER TABLE `users` ADD `telegram_chat_id` VARCHAR(50) NULL DEFAULT NULL AFTER `is_admin`");
}
// --- End Migration ---

// Fetch current user data
$user_query = mysqli_query($link, "SELECT telegram_chat_id FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Handle Update Settings POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    $telegram_chat_id = trim($_POST['telegram_chat_id']);

    $sql = "UPDATE users SET telegram_chat_id = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $telegram_chat_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "تنظیمات شما با موفقیت ذخیره شد.";
            $user['telegram_chat_id'] = $telegram_chat_id; // Update for display
        } else {
            $err = "خطا در ذخیره تنظیمات.";
        }
        mysqli_stmt_close($stmt);
    }
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>تنظیمات کاربری</h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <h3>تنظیمات اعلان تلگرام</h3>
        <p>برای دریافت اعلان‌ها در تلگرام، ابتدا به ربات ما پیام دهید تا چت آیدی خود را دریافت کنید و سپس آن را در کادر زیر وارد نمایید.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="telegram_chat_id">چت آیدی تلگرام</label>
                <input type="text" name="telegram_chat_id" id="telegram_chat_id" class="form-control" value="<?php echo htmlspecialchars($user['telegram_chat_id'] ?? ''); ?>" style="direction: ltr; text-align: left;">
            </div>
            <div class="form-group">
                <input type="submit" name="update_settings" class="btn btn-primary" value="ذخیره تنظیمات">
            </div>
        </form>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
