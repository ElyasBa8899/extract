<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_id = $_POST['ticket_id'];
    $assign_type = $_POST['assign_type'];
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
    $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
    $current_user_id = $_SESSION['id'];

    // TODO: Add robust permission check here. For now, only admin can reassign.
    if (empty($_SESSION['is_admin'])) {
        header("Location: view_ticket.php?id=$ticket_id&error=auth_failed");
        exit;
    }

    $assigned_to_user_id = null;
    $assigned_to_department_id = null;

    if ($assign_type === 'department') {
        $assigned_to_department_id = $department_id;
    } else {
        $assigned_to_user_id = $user_id;
    }

    $sql = "UPDATE tickets SET assigned_to_user_id = ?, assigned_to_department_id = ?, status = 'in_progress' WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iii", $assigned_to_user_id, $assigned_to_department_id, $ticket_id);
        if (mysqli_stmt_execute($stmt)) {
            // Add a reply to the ticket to log the reassignment
            $reassign_message = "تیکت توسط " . $_SESSION['username'] . " ارجاع داده شد.";
            $sql_log = "INSERT INTO ticket_replies (ticket_id, user_id, reply_message, is_log) VALUES (?, ?, ?, 1)";
            $stmt_log = mysqli_prepare($link, $sql_log);
            mysqli_stmt_bind_param($stmt_log, "iis", $ticket_id, $current_user_id, $reassign_message);
            mysqli_stmt_execute($stmt_log);

            // Notify the new assignee(s)
            $ticket_info_q = mysqli_query($link, "SELECT title FROM tickets WHERE id = $ticket_id");
            $ticket_title = mysqli_fetch_assoc($ticket_info_q)['title'];
            $notification_message = "تیکت '" . htmlspecialchars($ticket_title) . "' به شما ارجاع داده شد.";
            $notification_link = "/user/view_ticket.php?id=" . $ticket_id;

            $target_user_ids = [];
            if ($assigned_to_user_id) {
                $target_user_ids[] = $assigned_to_user_id;
            } elseif ($assigned_to_department_id) {
                $sql_users = "SELECT user_id FROM user_departments WHERE department_id = ?";
                if ($stmt_users = mysqli_prepare($link, $sql_users)) {
                    mysqli_stmt_bind_param($stmt_users, "i", $assigned_to_department_id);
                    mysqli_stmt_execute($stmt_users);
                    $result_users = mysqli_stmt_get_result($stmt_users);
                    while ($row = mysqli_fetch_assoc($result_users)) {
                        $target_user_ids[] = $row['user_id'];
                    }
                }
            }

            if (!empty($target_user_ids)) {
                $sql_notify = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                if ($stmt_notify = mysqli_prepare($link, $sql_notify)) {
                    foreach ($target_user_ids as $target_id) {
                        mysqli_stmt_bind_param($stmt_notify, "iss", $target_id, $notification_message, $notification_link);
                        mysqli_stmt_execute($stmt_notify);
                    }
                }
            }

            header("Location: view_ticket.php?id=$ticket_id&success=reassigned");
        } else {
            header("Location: view_ticket.php?id=$ticket_id&error=reassign_failed");
        }
        mysqli_stmt_close($stmt);
    }
    exit;
}
?>
