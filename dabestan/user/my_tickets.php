<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php"; // Include our new functions file

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch tickets created by the user.
// Fetch tickets created by the user OR assigned to the user.
$tickets = [];
$sql = "SELECT t.id, t.title, t.status, t.created_at, d.department_name
        FROM tickets t
        LEFT JOIN departments d ON t.assigned_to_department_id = d.id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tickets = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// mysqli_close($link); // Removed from here

function get_status_badge($status) {
    switch ($status) {
        case 'open':
            return '<span class="badge badge-primary">باز</span>';
        case 'in_progress':
            return '<span class="badge badge-warning">در حال بررسی</span>';
        case 'closed':
            return '<span class="badge badge-secondary">بسته شده</span>';
        case 'urgent':
            return '<span class="badge badge-danger">فوری</span>';
        default:
            return '<span class="badge badge-light">نامشخص</span>';
    }
}

require_once "../includes/header.php";
?>

<style>
.badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; }
.badge-primary { color: #fff; background-color: #007bff; }
.badge-secondary { color: #fff; background-color: #6c757d; }
.badge-danger { color: #fff; background-color: #dc3545; }
.badge-warning { color: #000; background-color: #ffc107; }
.badge-light { color: #000; background-color: #f8f9fa; }
</style>

<div class="page-content">
    <h2>تیکت‌های من</h2>
    <p>در این بخش لیست تیکت‌هایی که ارسال کرده‌اید را مشاهده می‌کنید.</p>

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
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
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

<?php require_once "../includes/footer.php"; ?>
