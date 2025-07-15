<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

// For now, only admins can manage donations
// require_permission('manage_donations');

$err = $success_msg = "";

// Handle Add Donation POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_donation'])) {
    $donor_name = trim($_POST['donor_name']);
    $amount = trim($_POST['amount']);
    $event_name = trim($_POST['event_name']);
    $notes = trim($_POST['notes']);

    if (empty($donor_name) || !is_numeric($amount)) {
        $err = "نام اهداکننده و مبلغ صحیح را وارد کنید.";
    } else {
        // This is a simplified version. We need a 'donations' table.
        // For now, this just shows the logic.
        $success_msg = "کمک مالی با موفقیت ثبت شد (شبیه‌سازی شده).";
    }
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت کمک‌های مالی (صله)</h2>
    <p>در این بخش، کمک‌های مالی دریافت شده برای مراسمات و پروژه‌ها را ثبت و مدیریت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ثبت کمک مالی جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="donor_name">نام اهدا کننده</label>
                <input type="text" name="donor_name" id="donor_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="amount">مبلغ (تومان)</label>
                <input type="number" name="amount" id="amount" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="event_name">بابت (نام مراسم یا پروژه)</label>
                <input type="text" name="event_name" id="event_name" class="form-control" placeholder="مثلا: مراسم غدیر ۱۴۰۳">
            </div>
             <div class="form-group">
                <label for="notes">یادداشت</label>
                <textarea name="notes" id="notes" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" name="add_donation" class="btn btn-primary" value="ثبت کمک مالی">
            </div>
        </form>
    </div>

    <div class="table-container">
        <h3>تاریخچه کمک‌های مالی</h3>
        <p>این بخش پس از ایجاد جدول مربوط به کمک‌های مالی در پایگاه داده تکمیل خواهد شد.</p>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
