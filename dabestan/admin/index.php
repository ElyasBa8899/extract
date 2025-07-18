<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

if (!is_admin()) {
    header("location: ../user/index.php");
    exit;
}

$pdo = get_db_connection();

// --- Data Fetching for Admin Widgets ---
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$open_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open', 'in_progress', 'urgent')")->fetchColumn();
$recent_submissions = $pdo->query("SELECT COUNT(*) FROM form_submissions WHERE submitted_at >= datetime('now', '-1 day')")->fetchColumn();
$rented_items = $pdo->query("SELECT COUNT(*) FROM item_rentals WHERE return_date IS NULL")->fetchColumn();

require_once "../includes/header.php";
?>

<div class="page-content">
    <div class="container-fluid">
        <h2>داشبورد مدیریت کل</h2>
        <p>سلام <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>، در اینجا یک نمای کلی از وضعیت سیستم را مشاهده می‌کنید.</p>

        <div class="dashboard-grid">
            <div class="widget">
                <div class="widget-header"><h3>تعداد کل کاربران</h3></div>
                <div class="widget-body financial-widget-body">
                    <div class="balance"><?php echo $total_users; ?></div>
                    <span>کاربر فعال</span>
                </div>
            </div>
            <div class="widget">
                <div class="widget-header"><h3>تیکت‌های باز</h3></div>
                <div class="widget-body financial-widget-body">
                    <div class="balance"><?php echo $open_tickets; ?></div>
                    <a href="manage_tickets.php">مدیریت تیکت‌ها</a>
                </div>
            </div>
            <div class="widget">
                <div class="widget-header"><h3>پاسخ فرم در ۲۴ ساعت اخیر</h3></div>
                <div class="widget-body financial-widget-body">
                    <div class="balance"><?php echo $recent_submissions; ?></div>
                    <a href="manage_forms.php">مدیریت فرم‌ها</a>
                </div>
            </div>
            <div class="widget">
                <div class="widget-header"><h3>اقلام کرایه داده شده</h3></div>
                <div class="widget-body financial-widget-body">
                    <div class="balance"><?php echo $rented_items; ?></div>
                    <span>(در انتظار بازگشت)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
