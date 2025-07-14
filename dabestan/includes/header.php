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
<style>
    /* Basic styling for notification dropdown */
    .header-notifications {
        position: relative;
        display: inline-block;
    }

    .notification-icon {
        cursor: pointer;
        position: relative;
    }

    .notification-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 10px;
        font-weight: bold;
        display: none; /* Hidden by default */
    }

    .notification-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 5px;
        width: 300px;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        z-index: 1000;
    }
    .notification-dropdown.show {
        display: block;
    }

    .notification-header, .notification-footer {
        padding: 10px;
        font-weight: bold;
        text-align: center;
        background-color: #f7f7f7;
    }

    #notification-list {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    #notification-list .notification-item {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
        color: #333;
        text-decoration: none;
        display: block;
        transition: background-color 0.2s;
    }
    #notification-list .notification-item:hover {
        background-color: #f2f2f2;
    }
     #notification-list .notification-item small {
        display: block;
        color: #999;
        font-size: 11px;
        margin-top: 4px;
    }
     #notification-list .no-notification {
        padding: 20px;
        text-align: center;
        color: #888;
     }

    .notification-footer a {
        color: #007bff;
        text-decoration: none;
    }
</style>

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
                    </ul>
                </li>
            <?php endif; ?>

            <?php if(has_permission('view_department_menu')): // A new permission to see these menus ?>
                <!-- New Structure based on Departments -->
                <li class="has-submenu">
                    <a href="#"><i data-feather="eye"></i><span>نظارت</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                    <ul class="submenu">
                        <li><a href="/dabestan/user/self_assessment_form.php"><span>فرم خوداظهاری</span></a></li>
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
                <a href="#"><i data-feather="check-square"></i><span>مدیریت وظایف</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/user/manage_tasks.php"><span>لیست وظایف</span></a></li>
                    <li><a href="/dabestan/user/create_task.php"><span>ایجاد وظیفه جدید</span></a></li>
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
<script>
// Plain JavaScript - No jQuery needed
document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.getElementById('notification-icon');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationCount = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');
    const rootPath = '/dabestan';

    function fetchNotifications(showDropdown = false) {
        fetch(rootPath + '/includes/fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }

                // Update badge
                if (data.unread_count > 0) {
                    notificationCount.textContent = data.unread_count;
                    notificationCount.style.display = 'block';
                } else {
                    notificationCount.style.display = 'none';
                }

                // Populate list
                notificationList.innerHTML = ''; // Clear previous list
                if (data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        const item = document.createElement('a');
                        item.className = 'notification-item';
                        item.href = notif.link ? rootPath + notif.link : '#';

                        const time = new Date(notif.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
                        const date = new Date(notif.created_at).toLocaleDateString('fa-IR');

                        item.innerHTML = `${notif.message}<small>${date} - ${time}</small>`;
                        notificationList.appendChild(item);
                    });
                } else {
                    notificationList.innerHTML = '<div class="no-notification">هیچ اعلان جدیدی وجود ندارد.</div>';
                }

                if (showDropdown) {
                    notificationDropdown.classList.add('show');
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                if (showDropdown) {
                    notificationList.innerHTML = '<div class="no-notification">خطا در بارگذاری اعلان‌ها.</div>';
                    notificationDropdown.classList.add('show');
                }
            });
    }

    // Toggle dropdown on icon click
    notificationIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        if (notificationDropdown.classList.contains('show')) {
            notificationDropdown.classList.remove('show');
        } else {
            fetchNotifications(true); // Fetch and then show
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationIcon.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('show');
        }
    });

    // Initial fetch and periodic refresh
    fetchNotifications();
    setInterval(fetchNotifications, 60000); // Refresh every 60 seconds
});
</script>
