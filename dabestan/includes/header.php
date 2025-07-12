<?php
// session_start(); should be called in the parent file
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه دبستان</title>
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>دبستان</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dabestan/user/index.php"><i data-feather="home"></i><span>داشبورد</span></a></li>

            <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <li class="nav-section-title"><span>مدیریت سیستم</span></li>
                <li><a href="/dabestan/admin/manage_users.php"><i data-feather="users"></i><span>مدیریت کاربران</span></a></li>
                <li><a href="/dabestan/admin/manage_roles.php"><i data-feather="shield"></i><span>مدیریت نقش‌ها</span></a></li>
                <li><a href="/dabestan/admin/manage_forms.php"><i data-feather="file-text"></i><span>مدیریت فرم‌ها</span></a></li>
                <li><a href="/dabestan/admin/manage_regions.php"><i data-feather="map"></i><span>مدیریت مناطق</span></a></li>
            <?php endif; ?>

            <li class="nav-section-title"><span>انبار و مالی</span></li>
            <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <li><a href="/dabestan/admin/manage_categories.php"><i data-feather="grid"></i><span>دسته‌بندی انبار</span></a></li>
            <li><a href="/dabestan/admin/manage_inventory.php"><i data-feather="archive"></i><span>اقلام انبار</span></a></li>
            <li><a href="/dabestan/admin/manage_booklets.php"><i data-feather="book-open"></i><span>مدیریت جزوات</span></a></li>
            <?php endif; ?>
            <li><a href="/dabestan/user/financial_transactions.php"><i data-feather="dollar-sign"></i><span>ثبت تراکنش مالی</span></a></li>
            <li><a href="/dabestan/user/my_financial_status.php"><i data-feather="credit-card"></i><span>وضعیت حساب من</span></a></li>

            <li class="nav-section-title"><span>بخش‌های سازمانی</span></li>
            <li><a href="/dabestan/user/rental_items.php"><i data-feather="package"></i><span>کرایه‌چی (پرورشی)</span></a></li>
            <li><a href="/dabestan/user/manage_parent_meetings.php"><i data-feather="user-check"></i><span>جلسات اولیا</span></a></li>
            <li><a href="/dabestan/user/class_event_reports.php"><i data-feather="flag"></i><span>گزارش خدمت‌گزاری‌ها</span></a></li>
            <li><a href="/dabestan/user/manage_general_events.php"><i data-feather="calendar"></i><span>پروژه‌های عمومی</span></a></li>
            <li><a href="/dabestan/user/manage_meetings.php"><i data-feather="mic"></i><span>جلسات ضمن خدمت</span></a></li>

            <li class="nav-section-title"><span>ارتباطات</span></li>
            <li><a href="/dabestan/user/new_ticket.php"><i data-feather="send"></i><span>ایجاد تیکت جدید</span></a></li>
            <li><a href="/dabestan/user/my_tickets.php"><i data-feather="inbox"></i><span>تیکت‌های من</span></a></li>

            <li><a href="/dabestan/logout.php"><i data-feather="log-out"></i><span>خروج</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <div class="header-left">
                 <button class="menu-toggle" id="menu-toggle"><i data-feather="menu"></i></button>
                 <div id="datetime">
                    <span id="date"></span>
                    <span id="time"></span>
                </div>
            </div>
            <div class="header-right">
                <div class="header-notifications">
                    <div class="notification-icon" id="notification-icon">
                        <i data-feather="bell"></i>
                        <span class="notification-count" id="notification-count"></span>
                    </div>
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="notification-header">اعلان‌ها</div>
                        <div id="notification-list"></div>
                        <div class="notification-footer">
                            <a href="#">مشاهده همه</a>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                </div>
            </div>
        </header>
        <main>
            <!-- Page content will be loaded here -->
