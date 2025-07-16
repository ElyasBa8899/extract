<?php
// This script will be run by a cron job to send task reminders and notifications.
// Example cron job command:
// /usr/bin/php /path/to/your/project/dabestan/includes/cron_notifications.php

// Set a long execution time
set_time_limit(0);

require_once 'db_singleton.php';
require_once 'functions.php';
require_once '../config.php';

$link = get_db_connection();

// --- 1. Send Reminders for Upcoming Deadlines ---

$reminder_days_before = defined('TASK_REMINDER_DAYS_BEFORE') ? TASK_REMINDER_DAYS_BEFORE : 1;
$reminder_date = new DateTime();
$reminder_date->modify("+" . $reminder_days_before . " day");
$reminder_date_str = $reminder_date->format('Y-m-d H:i:s');

$sql_upcoming = "SELECT t.id, t.title, ta.assigned_to_user_id
                 FROM tasks t
                 JOIN task_assignments ta ON t.id = ta.task_id
                 WHERE t.status IN ('pending', 'in_progress')
                   AND t.deadline IS NOT NULL
                   AND t.deadline <= ?
                   AND NOT EXISTS (
                       SELECT 1 FROM task_reminders tr
                       WHERE tr.task_id = t.id AND tr.reminder_type = 'upcoming'
                   )";

if ($stmt_upcoming = mysqli_prepare($link, $sql_upcoming)) {
    mysqli_stmt_bind_param($stmt_upcoming, "s", $reminder_date_str);
    mysqli_stmt_execute($stmt_upcoming);
    $result_upcoming = mysqli_stmt_get_result($stmt_upcoming);

    while ($task = mysqli_fetch_assoc($result_upcoming)) {
        if ($task['assigned_to_user_id']) {
            $message = "یادآوری: مهلت انجام وظیفه '{$task['title']}' نزدیک است.";
            send_notification($task['assigned_to_user_id'], 'task_reminder', $task['id'], $message);

            // Log that a reminder was sent
            $sql_log = "INSERT INTO task_reminders (task_id, user_id, reminder_type, sent_at) VALUES (?, ?, 'upcoming', NOW())";
            if($stmt_log = mysqli_prepare($link, $sql_log)){
                mysqli_stmt_bind_param($stmt_log, "ii", $task['id'], $task['assigned_to_user_id']);
                mysqli_stmt_execute($stmt_log);
                mysqli_stmt_close($stmt_log);
            }
        }
    }
    mysqli_stmt_close($stmt_upcoming);
}


// --- 2. Send Notifications for Overdue Tasks ---

$now = new DateTime();
$now_str = $now->format('Y-m-d H:i:s');

$sql_overdue = "SELECT t.id, t.title, ta.assigned_to_user_id, t.created_by,
                       (SELECT department_id FROM user_departments ud WHERE ud.user_id = t.created_by LIMIT 1) as creator_dept_id
                FROM tasks t
                JOIN task_assignments ta ON t.id = ta.task_id
                WHERE t.status IN ('pending', 'in_progress')
                  AND t.deadline IS NOT NULL
                  AND t.deadline < ?
                  AND NOT EXISTS (
                      SELECT 1 FROM task_reminders tr
                      WHERE tr.task_id = t.id AND tr.reminder_type = 'overdue_manager'
                  )";

if ($stmt_overdue = mysqli_prepare($link, $sql_overdue)) {
    mysqli_stmt_bind_param($stmt_overdue, "s", $now_str);
    mysqli_stmt_execute($stmt_overdue);
    $result_overdue = mysqli_stmt_get_result($stmt_overdue);

    while ($task = mysqli_fetch_assoc($result_overdue)) {
        $manager_role_name = defined('OVERDUE_TASK_NOTIFY_ROLE') ? OVERDUE_TASK_NOTIFY_ROLE : 'Admin';
        $managers_to_notify = get_users_by_role($manager_role_name);

        $user_info = get_user_info($task['assigned_to_user_id']);
        $assigned_user_name = $user_info ? $user_info['username'] : 'کاربر';

        foreach ($managers_to_notify as $manager) {
            $message = "توجه: مهلت انجام وظیفه '{$task['title']}' که به '{$assigned_user_name}' محول شده بود، به پایان رسیده است.";
            send_notification($manager['id'], 'task_overdue', $task['id'], $message);
        }

        // Log that a notification was sent to managers
        $sql_log_manager = "INSERT INTO task_reminders (task_id, reminder_type, sent_at) VALUES (?, 'overdue_manager', NOW())";
        if($stmt_log_m = mysqli_prepare($link, $sql_log_manager)){
            mysqli_stmt_bind_param($stmt_log_m, "i", $task['id']);
            mysqli_stmt_execute($stmt_log_m);
            mysqli_stmt_close($stmt_log_m);
        }
    }
    mysqli_stmt_close($stmt_overdue);
}


mysqli_close($link);
echo "Cron job finished.\n";
?>
