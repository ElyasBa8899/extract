<?php
session_start();
require_once __DIR__ . '/../core/functions.php';
$link = get_db_connection(); // Get connection
require_once "../includes/access_control.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

// Only super admin can see this specific dashboard
if (!$_SESSION['is_admin']) {
    // Redirect non-admin users to the regular user dashboard
    header("location: ../user/index.php");
    exit;
}

// --- Data Fetching for Admin Widgets ---

// 1. Total Users
$total_users = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM users"))['count'];

// 2. Open Tickets
$open_tickets = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM tickets WHERE status = 'open' OR status = 'in_progress' OR status = 'urgent'"))['count'];

// 3. Recent Submissions (last 24 hours)
$recent_submissions = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM form_submissions WHERE submitted_at >= NOW() - INTERVAL 1 DAY"))['count'];

// 4. Rented out items
$rented_items = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM item_rentals WHERE return_date IS NULL"))['count'];


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>داشبورد مدیریت کل</h2>
    <p>سلام <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>، در اینجا یک نمای کلی از وضعیت سیستم را مشاهده می‌کنید.</p>

    <div class="dashboard-grid">

        <!-- Total Users Widget -->
        <div class="widget">
            <div class="widget-header"><h3>تعداد کل کاربران</h3></div>
            <div class="widget-body financial-widget-body">
                <div class="balance"><?php echo $total_users; ?></div>
                <span>کاربر فعال در سیستم</span>
            </div>
        </div>

        <!-- Open Tickets Widget -->
        <div class="widget">
            <div class="widget-header"><h3>تیکت‌های باز</h3></div>
            <div class="widget-body financial-widget-body">
                <div class="balance"><?php echo $open_tickets; ?></div>
                <a href="../user/my_tickets.php">مدیریت تیکت‌ها</a>
            </div>
        </div>

        <!-- Recent Submissions Widget -->
        <div class="widget">
            <div class="widget-header"><h3>پاسخ فرم در ۲۴ ساعت اخیر</h3></div>
            <div class="widget-body financial-widget-body">
                <div class="balance"><?php echo $recent_submissions; ?></div>
                <a href="manage_forms.php">مدیریت فرم‌ها</a>
            </div>
        </div>

        <!-- Rented Items Widget -->
        <div class="widget">
            <div class="widget-header"><h3>اقلام کرایه داده شده</h3></div>
            <div class="widget-body financial-widget-body">
                <div class="balance"><?php echo $rented_items; ?></div>
                <span>(در انتظار بازگشت)</span>
            </div>
        </div>

    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
