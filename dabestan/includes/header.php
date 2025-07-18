<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$is_admin_user = $is_logged_in && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Determine the base path to correctly link assets
$base_path = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/user/') !== false ? '../' : './';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه دبستان</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://unpkg.com/jmoment/jmoment.min.js"></script>

</head>
<body>

<?php if ($is_logged_in): ?>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>سامانه دبستان</h3>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <?php if ($is_admin_user): ?>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>admin/index.php"><i data-feather="home"></i> داشبورد</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>admin/manage_users.php"><i data-feather="users"></i> مدیریت کاربران</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_roles.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>admin/manage_roles.php"><i data-feather="shield"></i> مدیریت نقش‌ها</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_permissions.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>admin/manage_permissions.php"><i data-feather="key"></i> مدیریت مجوزها</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_forms.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>admin/manage_forms.php"><i data-feather="file-text"></i> مدیریت فرم‌ها</a></li>
                    <li><hr style="border-color: #555;"></li>
                <?php else: ?>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>user/index.php"><i data-feather="home"></i> داشبورد</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_tasks.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>user/my_tasks.php"><i data-feather="briefcase"></i> وظایف من</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_tickets.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>user/my_tickets.php"><i data-feather="message-square"></i> تیکت‌های من</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'self_assessment_form.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>user/self_assessment_form.php"><i data-feather="edit-3"></i> فرم خوداظهاری</a></li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_financial_status.php' ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>user/my_financial_status.php"><i data-feather="dollar-sign"></i> وضعیت مالی</a></li>
                <?php endif; ?>
                <li><a href="<?php echo $base_path; ?>logout.php"><i data-feather="log-out"></i> خروج</a></li>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        <header class="page-header">
            <div class="header-left">
                <button class="hamburger-menu">
                    <i data-feather="menu"></i>
                </button>
                <div class="datetime-container">
                    <span id="persian-date"></span> | <span id="persian-time"></span>
                </div>
            </div>
            <div class="header-right">
                <div class="notifications-icon">
                    <i data-feather="bell"></i>
                    <span class="notification-count">3</span>
                </div>
                <div class="user-profile">
                    <a href="#"><?php echo htmlspecialchars($_SESSION["username"]); ?></a>
                </div>
            </div>
        </header>
<?php endif; ?>

<script src="<?php echo $base_path; ?>assets/js/script.js?v=<?php echo time(); ?>"></script>
<script>
    feather.replace();
</script>
</body>
</html>
