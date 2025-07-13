<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/telegram_bot.php"; // For notifications

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$err = $success_msg = "";

// Handle Password Change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch current password from DB
    $sql_pass = "SELECT password, telegram_chat_id FROM users WHERE id = ?";
    if($stmt_pass = mysqli_prepare($link, $sql_pass)){
        mysqli_stmt_bind_param($stmt_pass, "i", $user_id);
        mysqli_stmt_execute($stmt_pass);
        $result = mysqli_stmt_get_result($stmt_pass);
        $user = mysqli_fetch_assoc($result);
        $hashed_password = $user['password'];
        $chat_id = $user['telegram_chat_id'];

        // Verify current password
        if(password_verify($current_password, $hashed_password)){
            // Check if new passwords match
            if($new_password === $confirm_password){
                // Check password strength (simple version)
                if(strlen($new_password) >= 8){
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE users SET password = ? WHERE id = ?";
                    if($stmt_update = mysqli_prepare($link, $sql_update)){
                        mysqli_stmt_bind_param($stmt_update, "si", $new_hashed_password, $user_id);
                        if(mysqli_stmt_execute($stmt_update)){
                            $success_msg = "رمز عبور شما با موفقیت تغییر کرد.";
                            // Send Telegram notification
                            sendTelegramMessage($chat_id, "✅ رمز عبور شما در سامانه دبستان با موفقیت تغییر کرد.");
                        } else {
                            $err = "خطایی در به‌روزرسانی رمز عبور رخ داد.";
                        }
                        mysqli_stmt_close($stmt_update);
                    }
                } else {
                    $err = "رمز عبور جدید باید حداقل ۸ کاراکتر باشد.";
                }
            } else {
                $err = "رمز عبور جدید و تکرار آن یکسان نیستند.";
            }
        } else {
            $err = "رمز عبور فعلی شما صحیح نیست.";
        }
        mysqli_stmt_close($stmt_pass);
    }
}

// Handle Telegram Chat ID update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_telegram'])) {
    $telegram_chat_id = trim($_POST['telegram_chat_id']);

    $sql_update = "UPDATE users SET telegram_chat_id = ? WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql_update)){
        mysqli_stmt_bind_param($stmt, "si", $telegram_chat_id, $user_id);
        if(mysqli_stmt_execute($stmt)){
            $success_msg = "شناسه چت تلگرام شما با موفقیت ذخیره شد.";
             sendTelegramMessage($telegram_chat_id, "✅ حساب کاربری شما در سامانه دبستان با موفقیت به این چت متصل شد.");
        } else {
            $err = "خطا در ذخیره شناسه چت تلگرام.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch current user data for display
$user_data_query = mysqli_query($link, "SELECT telegram_chat_id FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_data_query);


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>تنظیمات من</h2>
    <p>در این بخش می‌توانید تنظیمات حساب کاربری خود را مدیریت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container" style="margin-bottom: 30px;">
        <h4>تغییر رمز عبور</h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="setting-form">
            <div class="form-group">
                <label for="current_password">رمز عبور فعلی</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">رمز عبور جدید</label>
                <input type="password" id="password" name="new_password" class="form-control" required>
                 <div id="password-strength-bar"><div></div></div>
                <small id="password-strength-text"></small>
            </div>
            <div class="form-group">
                <label for="confirm_password">تکرار رمز عبور جدید</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" name="change_password" class="btn btn-primary" value="تغییر رمز عبور">
            </div>
        </form>
    </div>

    <div class="form-container">
        <h4>اتصال به تلگرام</h4>
        <p>برای دریافت نوتیفیکیشن‌ها، ابتدا ربات ما را در تلگرام استارت کنید و سپس شناسه عددی چت خود را در کادر زیر وارد نمایید.</p>
        <p>
            <strong>آیدی ربات: <a href="https://t.me/Dabestan_Site_Bot" target="_blank">@Dabestan_Site_Bot</a></strong>
        </p>
        <p class="text-muted" style="font-size: 0.9em;">برای دریافت شناسه، می‌توانید به ربات <a href="https://t.me/userinfobot" target="_blank">@userinfobot</a> پیام دهید و ID خود را کپی کنید.</p>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="telegram-form">
            <div class="form-group">
                <label for="telegram_chat_id">شناسه چت تلگرام</label>
                <div class="input-group">
                    <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control" value="<?php echo htmlspecialchars($user_data['telegram_chat_id'] ?? ''); ?>" placeholder="شناسه شما..." readonly>
                    <button type="button" id="edit-chat-id" class="btn btn-secondary">ویرایش</button>
                </div>
            </div>
             <div class="form-group" id="telegram-submit-area" style="display: none;">
                <input type="submit" name="update_telegram" class="btn btn-primary" value="ذخیره">
                <button type="button" id="send-test-message" class="btn btn-success">ارسال پیام تست</button>
            </div>
        </form>
    </div>

</div>
<script src="../assets/js/password-strength.js"></script>
<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
