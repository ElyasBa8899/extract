<?php
// This script is intended to be run by a cron job.
// e.g., 0 8 * * * /usr/bin/php /path/to/your/project/dabestan/includes/cron_notifications.php

// Set the correct working directory
chdir(dirname(__FILE__));

require_once 'db_singleton.php';
require_once 'functions.php';

echo "Cron job started at " . date('Y-m-d H:i:s') . "\n";

$pdo = get_db_connection();

// --- 1. Overdue Task Notifications ---
$overdue_tasks_stmt = $pdo->prepare("
    SELECT t.id as task_id, t.title, t.deadline, ta.assigned_to_user_id
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    WHERE t.status IN ('pending', 'in_progress')
      AND t.deadline < date('now')
      AND NOT EXISTS (
          SELECT 1 FROM notifications
          WHERE link LIKE ?
          AND message LIKE ?
          AND user_id = ta.assigned_to_user_id
      )
");
// We check link and message to avoid sending duplicate notifications for the same event
$overdue_tasks_stmt->execute(['../user/view_task.php?id=%', 'یادآوری: مهلت انجام وظیفه%']);
$overdue_tasks = $overdue_tasks_stmt->fetchAll();

foreach ($overdue_tasks as $task) {
    if ($task['assigned_to_user_id']) {
        $message = "یادآوری: مهلت انجام وظیفه شما \"{$task['title']}\" در تاریخ " . to_persian_date($task['deadline']) . " به پایان رسیده است.";
        $link = "../user/view_task.php?id=" . $task['task_id'];

        $insert_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $insert_stmt->execute([$task['assigned_to_user_id'], $message, $link]);
        echo "Sent overdue notification for task #{$task['task_id']} to user #{$task['assigned_to_user_id']}\n";
    }
}


// --- 2. Checklist Item Reminders ---
$upcoming_checklists_stmt = $pdo->prepare("
    SELECT m.id as meeting_id, m.title, ci.item_description, m.created_by, ci.responsible_user_id
    FROM meetings m
    JOIN meeting_checklist_items ci ON m.id = ci.meeting_id
    WHERE m.status = 'planned'
      AND ci.is_completed = 0
      AND m.meeting_date BETWEEN date('now') AND date('now', '+1 day')
      AND NOT EXISTS (
          SELECT 1 FROM notifications
          WHERE link LIKE ?
          AND message LIKE ?
          AND (user_id = m.created_by OR user_id = ci.responsible_user_id)
      )
");
$upcoming_checklists_stmt->execute(['../admin/meeting_details.php?id=%', 'یادآوری: جلسه%']);
$upcoming_checklists = $upcoming_checklists_stmt->fetchAll();

foreach ($upcoming_checklists as $item) {
    // Notify the responsible person if set, otherwise the creator of the meeting.
    $user_to_notify = $item['responsible_user_id'] ?? $item['created_by'];

    $message = "یادآوری: جلسه \"{$item['title']}\" فردا برگزار می‌شود. آیتم چک‌لیست \"{$item['item_description']}\" هنوز تکمیل نشده است.";
    $link = "../admin/meeting_details.php?id=" . $item['meeting_id'];

    $insert_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $insert_stmt->execute([$user_to_notify, $message, $link]);
    echo "Sent checklist reminder for meeting #{$item['meeting_id']} to user #{$user_to_notify}\n";
}


echo "Cron job finished at " . date('Y-m-d H:i:s') . "\n";
?>
