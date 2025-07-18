<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_singleton.php';
require_once __DIR__ . '/access_control.php';
require_once __DIR__ . '/jdf.php';


/**
 * Converts a Gregorian date string to a Persian date string.
 *
 * @param string $gregorian_date The date string in a format recognizable by strtotime().
 * @param string $format The desired output format for jdate().
 * @return string The formatted Persian date.
 */
function to_persian_date($gregorian_date, $format = 'Y/m/d H:i') {
    if (empty($gregorian_date) || $gregorian_date == '0000-00-00 00:00:00') {
        return '---';
    }
    return jdate($format, strtotime($gregorian_date));
}


/**
 * Returns an HTML badge for a given status string.
 *
 * @param string $status The status string (e.g., 'pending', 'completed', 'open').
 * @return string The HTML for the badge.
 */
function get_status_badge_view($status) {
    $status_map = [
        'pending' => ['label' => 'در انتظار', 'class' => 'badge-warning'],
        'in_progress' => ['label' => 'در حال انجام', 'class' => 'badge-info'],
        'completed' => ['label' => 'تکمیل شده', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'لغو شده', 'class' => 'badge-secondary'],
        'open' => ['label' => 'باز', 'class' => 'badge-primary'],
        'closed' => ['label' => 'بسته', 'class' => 'badge-secondary'],
        'urgent' => ['label' => 'فوری', 'class' => 'badge-danger'],
        'planned' => ['label' => 'برنامه‌ریزی شده', 'class' => 'badge-info'],
    ];

    $label = $status_map[$status]['label'] ?? 'ناشناخته';
    $class = $status_map[$status]['class'] ?? 'badge-secondary';

    return "<span class='badge {$class}'>" . htmlspecialchars($label) . "</span>";
}

/**
 * Returns an HTML badge for a given priority string.
 *
 * @param string $priority The priority string (e.g., 'low', 'medium', 'high').
 * @return string The HTML for the badge.
 */
function get_priority_badge_view($priority) {
    $priority_map = [
        'low' => ['label' => 'پایین', 'class' => 'badge-success'],
        'medium' => ['label' => 'متوسط', 'class' => 'badge-warning'],
        'high' => ['label' => 'بالا', 'class' => 'badge-danger'],
        'urgent' => ['label' => 'بسیار فوری', 'class' => 'badge-danger'],
    ];

    $label = $priority_map[$priority]['label'] ?? 'ناشناخته';
    $class = $priority_map[$priority]['class'] ?? 'badge-secondary';

    return "<span class='badge {$class}'>" . htmlspecialchars($label) . "</span>";
}

/**
 * Fetches the name of a class by its ID.
 *
 * @param int $class_id The ID of the class.
 * @return string|null The name of the class or null if not found.
 */
function get_class_name_by_id($class_id) {
    $pdo = get_db_connection();
    try {
        $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching class name: " . $e->getMessage());
        return null;
    }
}
?>
