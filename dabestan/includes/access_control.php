<?php
// This file should be included after db.php and session_start()

function has_permission($permission_name) {
    // Super-admin (is_admin = 1) has all permissions
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        return true;
    }

    if (!isset($_SESSION['id'])) {
        return false;
    }

    $user_id = $_SESSION['id'];
    $link = $GLOBALS['link']; // Access the global $link variable from db.php

    // This is a more complex query that checks if a user has a role that has a certain permission.
    $sql = "SELECT COUNT(*) as count
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.permission_name = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $user_id, $permission_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row['count'] > 0;
    }

    return false;
}

// A simple function to protect a page
function require_permission($permission_name) {
    if (!has_permission($permission_name)) {
        // You can redirect to an "access denied" page or just die.
        die("خطای دسترسی: شما اجازه مشاهده این صفحه را ندارید.");
    }
}
?>
