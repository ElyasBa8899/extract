<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!isset($_SESSION['loggedin'])) {
    header("Location: ../index.php");
    exit();
}

$pdo = get_db_connection();
$ticket_id = $_GET['id'] ?? null;
$user_id = $_SESSION['id'];

if (!$ticket_id) {
    die("شناسه تیکت مشخص نشده است.");
}

// Fetch ticket info
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

// Security check: ensure the user owns the ticket or is an admin
if (!$ticket || ($ticket['user_id'] != $user_id && !is_admin())) {
    die("شما مجوز دسترسی به این تیکت را ندارید.");
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $reply_text = trim($_POST['reply_message']);
    if (!empty($reply_text)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$ticket_id, $user_id, $reply_text]);

            // Update ticket status to 'in_progress' if it was 'open'
            if ($ticket['status'] == 'open') {
                $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?")->execute([$ticket_id]);
            }
            header("Location: view_ticket.php?id=" . $ticket_id);
            exit();
        } catch (PDOException $e) {
            $error = "خطا در ثبت پاسخ: " . $e->getMessage();
        }
    }
}


// Fetch all replies for this ticket
$replies_stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.is_admin
    FROM ticket_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
");
$replies_stmt->execute([$ticket_id]);
$replies = $replies_stmt->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>موضوع: <?php echo htmlspecialchars($ticket['title']); ?></h2>
        <p>
            <strong>وضعیت:</strong> <?php echo get_status_badge_view($ticket['status']); ?> |
            <strong>اولویت:</strong> <?php echo get_priority_badge_view($ticket['priority']); ?> |
            <strong>تاریخ ایجاد:</strong> <?php echo to_persian_date($ticket['created_at']); ?>
        </p>
        <hr>

        <div class="chat-container">
            <!-- Initial Ticket Message -->
            <div class="chat-message">
                <div class="message-bubble user-bubble">
                    <p><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                    <span class="message-time"><?php echo to_persian_date($ticket['created_at']); ?> - (تیکت اصلی)</span>
                </div>
            </div>

            <!-- Replies -->
            <?php foreach ($replies as $reply): ?>
                <div class="chat-message">
                    <div class="message-bubble <?php echo $reply['user_id'] == $ticket['user_id'] ? 'user-bubble' : 'admin-bubble'; ?>">
                        <p class="font-weight-bold"><?php echo htmlspecialchars($reply['full_name']); ?>:</p>
                        <p><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                        <span class="message-time"><?php echo to_persian_date($reply['created_at']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr>

        <?php if ($ticket['status'] != 'closed'): ?>
        <div class="card">
            <div class="card-header"><h5>ارسال پاسخ جدید</h5></div>
            <div class="card-body">
                <form action="view_ticket.php?id=<?php echo $ticket_id; ?>" method="post">
                    <div class="form-group">
                        <textarea name="reply_message" class="form-control" rows="5" placeholder="پاسخ خود را اینجا بنویسید..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">ارسال پاسخ</button>
                    <?php if (is_admin()): ?>
                        <a href="manage_tickets.php?close=<?php echo $ticket_id; ?>" class="btn btn-danger">بستن تیکت</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-secondary">این تیکت بسته شده است و امکان ارسال پاسخ وجود ندارد.</div>
        <?php endif; ?>

    </div>
</div>
<style>
.chat-container { display: flex; flex-direction: column; gap: 1rem; }
.chat-message { display: flex; }
.message-bubble { padding: 1rem; border-radius: 1rem; max-width: 70%; }
.user-bubble { background-color: #e9ecef; margin-right: auto; border-bottom-right-radius: 0; }
.admin-bubble { background-color: #d1ecf1; margin-left: auto; border-bottom-left-radius: 0; }
.message-time { font-size: 0.8rem; color: #6c757d; display: block; margin-top: 0.5rem; text-align: left; }
</style>
<?php require_once "../includes/footer.php"; ?>
