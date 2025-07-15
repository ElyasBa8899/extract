<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/functions.php"; // Include our new functions file

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch tickets for the current user.
// An admin sees all tickets. Other users see tickets they created OR tickets assigned to them/their department.
$tickets = [];
$is_admin = !empty($_SESSION['is_admin']);

$sql = "
    SELECT t.id, t.title, t.status, t.priority, t.created_at,
           d.department_name,
           u_assigned.username as assigned_username,
           u_creator.username as creator_username
    FROM tickets t
    LEFT JOIN departments d ON t.assigned_to_department_id = d.id
    LEFT JOIN users u_assigned ON t.assigned_to_user_id = u_assigned.id
    JOIN users u_creator ON t.user_id = u_creator.id
";

if (!$is_admin) {
    // Get departments the user belongs to
    $user_depts_q = mysqli_query($link, "SELECT department_id FROM user_departments WHERE user_id = $user_id");
    $user_depts = mysqli_fetch_all($user_depts_q, MYSQLI_ASSOC);
    $dept_ids = !empty($user_depts) ? implode(',', array_column($user_depts, 'department_id')) : '0';

    $sql .= " WHERE t.user_id = ?
              OR t.assigned_to_user_id = ?
              OR t.assigned_to_department_id IN ($dept_ids)";
}

$sql .= " ORDER BY t.priority = 'urgent' DESC, t.created_at DESC";

if($stmt = mysqli_prepare($link, $sql)){
    if (!$is_admin) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tickets = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// // mysqli_close($link); // Removed from here

// The badge functions are now in functions.php, so they are removed from here.

require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>

<style>
.badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; }
.badge-primary { color: #fff; background-color: #007bff; }
.badge-secondary { color: #fff; background-color: #6c757d; }
.badge-danger { color: #fff; background-color: #dc3545; }
.badge-warning { color: #000; background-color: #ffc107; }
.badge-light { color: #000; background-color: #f8f9fa; }

.ticket-row.priority-urgent {
    background-color: #fff3f3; /* Light red background for urgent tickets */
    font-weight: bold;
}
.ticket-row.priority-urgent:hover {
    background-color: #ffe8e8;
}
</style>

<div class="page-content">
    <h2><?php echo $is_admin ? 'مدیریت همه تیکت‌ها' : 'تیکت‌های من'; ?></h2>
    <p><?php echo $is_admin ? 'در این بخش تمام تیکت‌های سیستم را مشاهده و مدیریت کنید.' : 'در این بخش لیست تیکت‌هایی که ارسال کرده‌اید یا به شما ارجاع داده شده را مشاهده می‌کنید.'; ?></p>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>عنوان تیکت</th>
                        <th>ارجاع به</th>
                    <th>وضعیت</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="5" style="text-align: center;">شما تاکنون هیچ تیکتی ارسال نکرده‌اید.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr class="ticket-row priority-<?php echo htmlspecialchars($ticket['priority']); ?>">
                            <td>
                                <?php echo htmlspecialchars($ticket['title']); ?>
                                <?php echo get_priority_badge($ticket['priority']); ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($ticket['assigned_username'])) {
                                    echo 'کاربر: ' . htmlspecialchars($ticket['assigned_username']);
                                } elseif (!empty($ticket['department_name'])) {
                                    echo 'بخش: ' . htmlspecialchars($ticket['department_name']);
                                } else {
                                    echo 'عمومی';
                                }
                                ?>
                            </td>
                            <td><?php echo get_status_badge($ticket['status']); ?></td>
                            <td><?php echo to_persian_date($ticket['created_at']); ?></td>
                            <td>
                                <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-primary btn-sm">مشاهده و پاسخ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php"; ?>
