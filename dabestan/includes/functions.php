<?php
// This file can be used for general purpose functions

require_once 'jdf.php';

/**
 * Converts a MySQL DATETIME string to a Persian date format.
 *
 * @param string $datetime_str The MySQL DATETIME string (e.g., "2024-07-13 10:00:00").
 * @param string $format The format for the output date (uses jdf formatting).
 * @return string The formatted Persian date.
 */
function to_persian_date($datetime_str, $format = 'Y/m/d H:i') {
    if (empty($datetime_str)) {
        return '';
    }
    $timestamp = strtotime($datetime_str);
    return jdf($format, $timestamp);
}
?>
