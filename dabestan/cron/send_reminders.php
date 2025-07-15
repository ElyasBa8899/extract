<?php
// This script is intended to be run by a cron job on the server (e.g., once every hour).
// It checks for tasks with upcoming deadlines and sends notifications.

// Set the correct context (assuming it's run from the project root)
require_once dirname(__DIR__) . '/includes/db.php';

echo "--- شروع اسکریپت یادآوری وظایف (" . date('Y-m-d H:i:s') . ") ---\n";

// --- Logic to find tasks that need a reminder ---
// 1. Tasks that are not completed or cancelled.
// 2. Deadline is in the future but less than 24 hours away.
// 3. A reminder has not been sent in the last 12 hours (to avoid spamming).

$sql = "
    SELECT t.id, t.title, t.deadline
    FROM tasks t
    WHERE
        t.status IN ('pending', 'in_progress')
        AND t.deadline IS NOT NULL
        AND t.deadline > NOW()
        AND t.deadline < NOW() + INTERVAL 24 HOUR
        AND (t.last_reminder_sent_at IS NULL OR t.last_reminder_sent_at < NOW() - INTERVAL 12 HOUR)
";

$tasks_to_remind = mysqli_query($link, $sql);

if (!$tasks_to_remind) {
    echo "خطا در اجرای کوئری: " . mysqli_error($link) . "\n";
    exit;
}

if (mysqli_num_rows($tasks_to_remind) == 0) {
    echo "هیچ وظیفه‌ای برای ارسال یادآوری یافت نشد.\n";
    exit;
}

echo "تعداد " . mysqli_num_rows($tasks_to_remind) . " وظیفه برای ارسال یادآوری یافت شد.\n";

// --- Loop through tasks and send notifications ---
while ($task = mysqli_fetch_assoc($tasks_to_remind)) {
    $task_id = $task['id'];
    $task_title = $task['title'];
    $deadline = new DateTime($task['deadline']);
    $now = new DateTime();
    $interval = $now->diff($deadline);
    $hours_left = $interval->h + ($interval->days * 24);

    $notification_message = "یادآوری: کمتر از " . ($hours_left + 1) . " ساعت تا پایان مهلت وظیفه '" . htmlspecialchars($task_title) . "' باقی مانده است.";
    $notification_link = "/user/view_task.php?id=" . $task_id;

    // Find assignees for the task
    $sql_assignees = "
        SELECT assigned_to_user_id, assigned_to_department_id
        FROM task_assignments
        WHERE task_id = ?";
    $stmt_assignees = mysqli_prepare($link, $sql_assignees);
    mysqli_stmt_bind_param($stmt_assignees, "i", $task_id);
    mysqli_stmt_execute($stmt_assignees);
    $result_assignees = mysqli_stmt_get_result($stmt_assignees);

    $target_user_ids = [];
    while($assignment = mysqli_fetch_assoc($result_assignees)) {
        if ($assignment['assigned_to_user_id']) {
            $target_user_ids[] = $assignment['assigned_to_user_id'];
        } elseif ($assignment['assigned_to_department_id']) {
            // Get all users from that department
             $sql_users = "SELECT user_id FROM user_departments WHERE department_id = " . $assignment['assigned_to_department_id'];
             $result_users = mysqli_query($link, $sql_users);
             while ($row = mysqli_fetch_assoc($result_users)) {
                 $target_user_ids[] = $row['user_id'];
             }
        }
    }

    $unique_target_ids = array_unique($target_user_ids);

    if (!empty($unique_target_ids)) {
        $sql_notify = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
        $stmt_notify = mysqli_prepare($link, $sql_notify);

        foreach ($unique_target_ids as $target_id) {
            mysqli_stmt_bind_param($stmt_notify, "iss", $target_id, $notification_message, $notification_link);
            mysqli_stmt_execute($stmt_notify);
            echo "یادآوری برای کاربر ID: $target_id برای وظیفه ID: $task_id ارسال شد.\n";
        }
    }

    // Update the last_reminder_sent_at timestamp for the task
    mysqli_query($link, "UPDATE tasks SET last_reminder_sent_at = NOW() WHERE id = $task_id");
}

echo "--- پایان اسکریپت ---\n";
mysqli_close($link);
?>
