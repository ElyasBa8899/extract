<?php
session_start();

// This line must be before the header include
// to check session before any output.
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["is_admin"]){
    header("location: ../index.php");
    exit;
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>پنل کاربری</h2>
    <p>سلام، <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. به پنل کاربری خود خوش آمدید.</p>
    <p>اینجا محتوای اصلی پنل کاربری شما قرار خواهد گرفت.</p>
</div>

<?php
require_once "../includes/footer.php";
?>
