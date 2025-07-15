<?php
// This file is included in other files, so session_start() and other requires should be in the parent.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /dabestan/index.php");
    exit;
}
// Ensure access control functions are available
if (!function_exists('has_permission')) {
    require_once __DIR__ . "/access_control.php";
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه دبستان</title>
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <link rel="stylesheet" href="/dabestan/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>دبستان</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dabestan/user/index.php"><i data-feather="home"></i><span>داشبورد</span></a></li>

            <?php if(has_permission('manage_users')): ?>
                <li class="has-submenu">
                    <a href="#"><i data-feather="settings"></i><span>مدیریت سیستم</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                        <li><a href="/dabestan/admin/manage_users.php"><span>کاربران</span></a></li>
                        <li><a href="/dabestan/admin/manage_roles.php"><span>نقش‌ها</span></a></li>
                        <li><a href="/dabestan/admin/manage_departments.php"><span>بخش‌ها</span></a></li>
                        <li><a href="/dabestan/admin/manage_classes.php"><span>کلاس‌ها</span></a></li>
                        <li><a href="/dabestan/admin/manage_forms.php"><span>فرم‌ها</span></a></li>
                        <li><a href="/dabestan/admin/manage_tasks.php"><span>وظایف</span></a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if(has_permission('view_department_menu')): // A new permission to see these menus ?>
                <!-- New Structure based on Departments -->
                <li class="has-submenu">
                    <a href="#"><i data-feather="eye"></i><span>نظارت</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                        <li><a href="/dabestan/admin/view_all_assessments.php"><span>مشاهده خوداظهاری‌ها</span></a></li>
                        <!-- Add link to visitor form -->
                        <!-- Add link to analysis page -->
                    </ul>
                </li>
                <li class="has-submenu">
                    <a href="#"><i data-feather="gift"></i><span>پرورشی</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                        <li><a href="/dabestan/user/class_event_reports.php"><span>گزارش خدمت‌گزاری‌ها</span></a></li>
                        <li><a href="/dabestan/admin/manage_general_events.php"><span>پروژه‌های عمومی</span></a></li>
                        <li><a href="/dabestan/user/rental_items.php"><span>کرایه‌چی</span></a></li>
                    </ul>
                </li>
                 <li class="has-submenu">
                    <a href="#"><i data-feather="users"></i><span>اولیا</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                         <li><a href="/dabestan/user/manage_parent_meetings.php"><span>جلسات اولیا</span></a></li>
                    </ul>
                </li>
                <li class="has-submenu">
                    <a href="#"><i data-feather-cpu></i><span>ضمن خدمت</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                         <li><a href="/dabestan/user/manage_meetings.php"><span>جلسات ضمن خدمت</span></a></li>
                    </ul>
                </li>
                 <li class="has-submenu">
                    <a href="#"><i data-feather="crosshair"></i><span>جذب و راه‌اندازی</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                         <li><a href="/dabestan/admin/manage_regions.php"><span>مدیریت مناطق</span></a></li>
                         <!-- Add link to recruited students list -->
                    </ul>
                </li>
                <li class="has-submenu">
                    <a href="#"><i data-feather="dollar-sign"></i><span>مالی و پشتیبانی</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                        <?php if(has_permission('manage_inventory')): ?>
                            <li><a href="/dabestan/admin/manage_categories.php"><span>دسته‌بندی انبار</span></a></li>
                            <li><a href="/dabestan/admin/manage_inventory.php"><span>اقلام انبار</span></a></li>
                        <?php endif; ?>
                        <?php if(has_permission('manage_financials')): ?>
                            <li><a href="/dabestan/admin/manage_booklets.php"><span>مدیریت جزوات</span></a></li>
                            <li><a href="/dabestan/user/financial_transactions.php"><span>ثبت تراکنش مالی</span></a></li>
                        <?php endif; ?>
                        <li><a href="/dabestan/user/my_financial_status.php"><span>وضعیت حساب من</span></a></li>
                    </ul>
                </li>
            <?php endif; ?>



            <li class="has-submenu">
                <a href="#"><i data-feather="message-square"></i><span>ارتباطات</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <?php if(has_permission('submit_ticket')): ?>
                        <li><a href="/dabestan/user/new_ticket.php"><span>ایجاد تیکت جدید</span></a></li>
                    <?php endif; ?>
                    <li><a href="/dabestan/user/my_tickets.php"><span>تیکت‌های من</span></a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#"><i data-feather="briefcase"></i><span>وظایف</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <?php if(has_permission('manage_tasks')): ?>
                        <li><a href="/dabestan/admin/manage_tasks.php"><span>ایجاد وظیفه جدید</span></a></li>
                    <?php endif; ?>
                    <li><a href="/dabestan/user/my_tasks.php"><span>وظایف من</span></a></li>
                </ul>
            </li>

            <li class="nav-section-title"><span>ابزارها</span></li>
            <li><a href="/dabestan/user/my_classes.php"><i data-feather="book-open"></i><span>مدیریت کلاس‌های من</span></a></li>
            <li><a href="/dabestan/user/self_assessment_form.php"><i data-feather="edit-3"></i><span>فرم خوداظهاری</span></a></li>

            <li class="nav-section-title"><span>پروفایل</span></li>
            <li><a href="/dabestan/user/my_settings.php"><i data-feather="tool"></i><span>تنظیمات</span></a></li>
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
                            <a href="/dabestan/user/view_all_notifications.php">مشاهده همه</a>
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
