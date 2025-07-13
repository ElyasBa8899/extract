<?php
// This file should be included after db.php and session_start()

function has_permission($permission_name) {
    global $link;
    if (!isset($_SESSION['id'])) return false;

    // Super admin (user_id = 1 or is_admin = 1) has all permissions
    if ((isset($_SESSION['is_admin']) && $_SESSION['is_admin']) || $_SESSION['id'] == 1) {
        return true;
    }

    // Check cache first
    if (isset($_SESSION['permissions_cache']) && array_key_exists($permission_name, $_SESSION['permissions_cache'])) {
        return $_SESSION['permissions_cache'][$permission_name];
    }

    // If cache is not set, build it
    if (!isset($_SESSION['permissions_cache'])) {
        $_SESSION['permissions_cache'] = [];
        $user_id = $_SESSION['id'];
        $sql = "SELECT DISTINCT p.permission_name
                FROM user_roles ur
                JOIN role_permissions rp ON ur.role_id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ?";

        if($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)) {
                $_SESSION['permissions_cache'][$row['permission_name']] = true;
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Check the permission from the now-populated cache
    return isset($_SESSION['permissions_cache'][$permission_name]);
}

// A simple function to protect a page
function require_permission($permission_name) {
    if (!has_permission($permission_name)) {
        // You can redirect to an "access denied" page or just die.
        die("خطای دسترسی: شما اجازه مشاهده این صفحه را ندارید.");
    }
}
?>
