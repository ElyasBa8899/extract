<?php
session_start();

// This line must be before the header include
// to check session before any output.
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]){
    header("location: ../index.php");
    exit;
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>پنل مدیریت</h2>
    <p>سلام، <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. به پنل مدیریت خوش آمدید.</p>
    <p>از منوی سمت راست می‌توانید به بخش‌های مختلف دسترسی داشته باشید.</p>
</div>

<?php
require_once "../includes/footer.php";
?>
