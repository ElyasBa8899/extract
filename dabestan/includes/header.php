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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>سامانه دبستان</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dabestan/user/index.php">داشبورد</a></li>

            <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <li class="nav-section-title"><span>مدیریت سیستم</span></li>
            <li><a href="/dabestan/admin/manage_users.php">مدیریت کاربران</a></li>
            <li><a href="/dabestan/admin/manage_roles.php">مدیریت نقش‌ها</a></li>
                <li><a href="/dabestan/admin/manage_forms.php">مدیریت فرم‌ها</a></li>
                <li><a href="/dabestan/admin/manage_regions.php">مدیریت مناطق</a></li>
            <?php endif; ?>

            <li class="nav-section-title"><span>انبار و مالی</span></li>
            <li><a href="/dabestan/admin/manage_categories.php">مدیریت دسته‌بندی‌ها</a></li>
            <li><a href="/dabestan/admin/manage_inventory.php">مدیریت اقلام انبار</a></li>
            <li><a href="/dabestan/admin/manage_booklets.php">مدیریت جزوات</a></li>
            <li><a href="/dabestan/user/financial_transactions.php">ثبت تراکنش مالی</a></li>

            <li class="nav-section-title"><span>جذب و راه‌اندازی</span></li>
            <li><a href="/dabestan/user/add_student.php">ثبت دانش‌آموز جدید</a></li>
            <li><a href="/dabestan/user/list_students.php">لیست دانش‌آموزان</a></li>

            <li class="nav-section-title"><span>فعالیت‌های من</span></li>
            <li><a href="/dabestan/user/list_forms.php">تکمیل فرم‌ها</a></li>
            <li><a href="/dabestan/user/my_financial_status.php">وضعیت حساب من</a></li>

            <li class="nav-section-title"><span>بخش‌های سازمانی</span></li>
            <li><a href="/dabestan/user/rental_items.php">کرایه‌چی (پرورشی)</a></li>
            <li><a href="#">بخش اولیا</a></li>
            <li><a href="/dabestan/user/class_event_reports.php">گزارش خدمت‌گزاری‌ها (پرورشی)</a></li>
            <li><a href="/dabestan/user/manage_general_events.php">پروژه‌های عمومی (پرورشی)</a></li>
            <li><a href="#">بخش نظارت</a></li>
            <li><a href="#">بخش مالی</a></li>

            <li class="nav-section-title"><span>ضمن خدمت</span></li>
            <li><a href="/dabestan/user/manage_meetings.php">مدیریت جلسات</a></li>

            <li class="nav-section-title"><span>ارتباطات</span></li>
            <li><a href="/dabestan/user/new_ticket.php">ایجاد تیکت جدید</a></li>
            <li><a href="/dabestan/user/my_tickets.php">تیکت‌های من</a></li>

            <li style="margin-top: auto;"><a href="/dabestan/logout.php" style="background: #c0392b;">خروج</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <div class="header-title">
                <button class="menu-toggle" id="menu-toggle">☰</button>
                <h2>داشبورد اصلی</h2>
            </div>
            <div class="header-info">
                <div id="datetime">
                    <span id="time"></span>
                    <span id="date"></span>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                </div>
            </div>
        </header>
        <main>
            <!-- Page content will be loaded here -->
