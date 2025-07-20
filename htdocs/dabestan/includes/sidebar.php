<aside id="sidebar">
    <nav class="main-nav">
        <ul>
            <!-- This will be populated dynamically based on user roles and permissions -->
            <li><a href="<?php echo BASE_URL; ?>/user/dashboard.php">داشبورد</a></li>

            <?php if (is_logged_in()): ?>
                <li><a href="<?php echo BASE_URL; ?>/user/profile.php">پروفایل من</a></li>

                <!-- Example of a conditional menu item -->
                <?php if (has_permission('fill_self_assessment')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/user/self_assessment.php">فرم خوداظهاری</a></li>
                <?php endif; ?>

                <li><a href="<?php echo BASE_URL; ?>/logout.php">خروج</a></li>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>/login.php">ورود</a></li>
            <?php endif; ?>

            <!-- Admin Menu -->
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <li class="menu-separator"></li>
                <li class="menu-title">پنل مدیریت</li>
                <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">داشبورد ادمین</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/manage_users.php">مدیریت کاربران</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/manage_forms.php">مدیریت فرم‌ها</a></li>
                <!-- Add more admin links here -->
            <?php endif; ?>
        </ul>
    </nav>
</aside>
