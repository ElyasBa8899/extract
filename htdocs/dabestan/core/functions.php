<?php
// Include essential files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';

/**
 * Redirects the user to a specified URL.
 *
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

/**
 * Checks if a user is logged in.
 *
 * @return bool True if the user is logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * A simple function to sanitize output and prevent XSS attacks.
 *
 * @param string $data The data to be sanitized.
 * @return string The sanitized data.
 */
function e($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Checks if the current logged-in user has a specific permission.
 * This is a placeholder and will be implemented in detail later.
 *
 * @param string $permission_name The name of the permission to check.
 * @return bool True if the user has the permission, false otherwise.
 */
function has_permission($permission_name) {
    // This is a crucial part of the application and will be implemented
    // with a proper Role-Based Access Control (RBAC) system.
    // For now, we can have a simple check.
    // The admin user (is_admin = 1) has all permissions.
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        return true;
    }

    // A more complex check will be added here, querying the database
    // for the user's roles and associated permissions.
    // e.g., check against the `user_permissions` and `role_permissions` tables.

    return false; // Default to no permission
}

/**
 * Converts a Gregorian date (Y-m-d) to a Jalali (Shamsi) date.
 * This will be implemented using a suitable library.
 * For now, it's a placeholder.
 *
 * @param string $gregorian_date The date in 'Y-m-d' format.
 * @return string The date in Jalali format.
 */
function to_jalali($gregorian_date) {
    // We will integrate a proper library like Verta for this.
    // Example placeholder:
    if (empty($gregorian_date)) {
        return '';
    }
    // This is not a real conversion, just a placeholder.
    return str_replace('-', '/', $gregorian_date);
}

/**
 * Displays a formatted dump of a variable for debugging purposes.
 *
 * @param mixed $variable The variable to dump.
 */
function debug($variable) {
    echo '<pre style="background: #f5f5f5; border: 1px solid #ccc; padding: 10px; border-radius: 5px; text-align: left; direction: ltr;">';
    var_dump($variable);
    echo '</pre>';
}

?>
