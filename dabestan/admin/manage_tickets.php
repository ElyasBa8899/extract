<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) {
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// Handle closing a ticket
if (isset($_GET['close'])) {
    $ticket_id = $_GET['close'];
    $stmt = $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $message = "تیکت با موفقیت بسته شد.";
}

// Handle assigning a ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $assign_to_user_id = $_POST['assign_to_user_id'];
    $stmt = $pdo->prepare("UPDATE tickets SET assigned_to_user_id = ? WHERE id = ?");
    $stmt->execute([$assign_to_user_id, $ticket_id]);
    $message = "تیکت به کاربر مورد نظر واگذار شد.";
}


// Fetch all tickets
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT t.*, u.full_name as user_name FROM tickets t JOIN users u ON t.user_id = u.id";
if ($filter == 'open') {
    $sql .= " WHERE t.status IN ('open', 'in_progress', 'urgent')";
}
$sql .= " ORDER BY t.created_at DESC";
$tickets = $pdo->query($sql)->fetchAll();

// Fetch all users for assignment dropdown
$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت تیکت‌ها</h2>
        <p>مشاهده، پاسخ‌دهی و مدیریت تمام تیکت‌های ثبت شده در سیستم.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

        <div class="btn-group mb-3">
            <a href="?filter=all" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">همه تیکت‌ها</a>
            <a href="?filter=open" class="btn <?php echo $filter == 'open' ? 'btn-primary' : 'btn-secondary'; ?>">تیکت‌های باز</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>فرستنده</th>
                                <th>عنوان</th>
                                <th>وضعیت</th>
                                <th>اولویت</th>
                                <th>تاریخ</th>
                                <th>تخصیص به</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                    <td><?php echo get_status_badge_view($ticket['status']); ?></td>
                                    <td><?php echo get_priority_badge_view($ticket['priority']); ?></td>
                                    <td><?php echo to_persian_date($ticket['created_at']); ?></td>
                                    <td>
                                        <form action="manage_tickets.php" method="post" class="form-inline">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                            <select name="assign_to_user_id" class="form-control form-control-sm">
                                                <option value="">--</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" <?php echo $ticket['assigned_to_user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_ticket" class="btn btn-sm btn-light">ثبت</button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="../user/view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info">مشاهده/پاسخ</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
