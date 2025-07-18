<?php
/**
 * Access Control Functions
 *
 * This file contains functions for checking user permissions and roles.
 * It should be included in pages that require access control.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_singleton.php';

/**
 * Checks if a user has a specific permission.
 *
 * This function checks all roles assigned to the current user
 * to see if any of them grant the specified permission.
 * Super admins (is_admin = 1) always have all permissions.
 *
 * @param string $permission_name The name of the permission to check.
 * @return bool True if the user has the permission, false otherwise.
 */
function has_permission($permission_name) {
    // If the user is not logged in, they have no permissions.
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        return false;
    }

    // Super admin always has all permissions.
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        return true;
    }

    // Get the database connection.
    $pdo = get_db_connection();
    $user_id = $_SESSION['id'];

    try {
        // Prepare a statement to find if any of the user's roles have the permission.
        $sql = "
            SELECT COUNT(*)
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = :user_id AND p.permission_name = :permission_name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':permission_name' => $permission_name
        ]);

        // If the count is greater than 0, the user has the permission.
        return $stmt->fetchColumn() > 0;

    } catch (PDOException $e) {
        // Log the error and deny permission by default in case of failure.
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Checks if the current user is a super admin.
 *
 * @return bool True if the user is a super admin, false otherwise.
 */
function is_admin() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

/**
 * A simple gatekeeper function to be called at the top of permission-restricted pages.
 * If the user does not have the required permission, it redirects them.
 *
 * @param string $permission_name The permission required to access the page.
 * @param string $redirect_path The path to redirect to if permission is denied. Defaults to the user dashboard.
 */
function require_permission($permission_name, $redirect_path = '../user/index.php') {
    if (!has_permission($permission_name)) {
        // Optional: Set a flash message to inform the user why they were redirected.
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'شما مجوز دسترسی به این صفحه را ندارید.'
        ];
        header("Location: " . $redirect_path);
        exit();
    }
}

?>
