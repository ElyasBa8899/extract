<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: my_tickets.php");
    exit;
}

$ticket_id = $_GET['id'];
$user_id = $_SESSION['id'];

// Fetch ticket info
$ticket = null;
$sql_ticket = "SELECT t.*, u.username as creator_username
               FROM tickets t
               JOIN users u ON t.user_id = u.id
               WHERE t.id = ?";
if($stmt_ticket = mysqli_prepare($link, $sql_ticket)){
    mysqli_stmt_bind_param($stmt_ticket, "i", $ticket_id);
    mysqli_stmt_execute($stmt_ticket);
    $result = mysqli_stmt_get_result($stmt_ticket);
    $ticket = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_ticket);
}

// Security Check: User can view if they are the creator, an admin, or in the assigned department/user
$can_view = false;
if ($ticket) {
    if ($ticket['user_id'] == $user_id || !empty($_SESSION['is_admin'])) {
        $can_view = true;
    } elseif ($ticket['assigned_to_user_id'] == $user_id) {
        $can_view = true;
    } elseif ($ticket['assigned_to_department_id']) {
        $dept_id = $ticket['assigned_to_department_id'];
        $check_dept = mysqli_query($link, "SELECT 1 FROM user_departments WHERE user_id = $user_id AND department_id = $dept_id");
        if (mysqli_num_rows($check_dept) > 0) {
            $can_view = true;
        }
    }
}

if (!$can_view) {
    echo "دسترسی غیرمجاز یا تیکت یافت نشد.";
    exit;
}

// Handle New Reply POST
$err = $success_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_reply'])) {
    $reply_message = trim($_POST['reply_message']);
    if (empty($reply_message)) {
        $err = "متن پاسخ نمی‌تواند خالی باشد.";
    } else {
        $sql_reply = "INSERT INTO ticket_replies (ticket_id, user_id, reply_message) VALUES (?, ?, ?)";
        if ($stmt_reply = mysqli_prepare($link, $sql_reply)) {
            mysqli_stmt_bind_param($stmt_reply, "iis", $ticket_id, $user_id, $reply_message);
            if (mysqli_stmt_execute($stmt_reply)) {
                if($ticket['status'] == 'open'){
                    mysqli_query($link, "UPDATE tickets SET status = 'in_progress' WHERE id = $ticket_id");
                    $ticket['status'] = 'in_progress';
                }

                if ($ticket['user_id'] != $user_id) {
                    $notif_message = "پاسخ جدیدی برای تیکت شما با عنوان \"" . htmlspecialchars($ticket['title']) . "\" ثبت شد.";
                    $notif_link = "user/view_ticket.php?id=" . $ticket_id;

                    // Create web notification
                    $sql_notif = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                    $stmt_notif = mysqli_prepare($link, $sql_notif);
                    mysqli_stmt_bind_param($stmt_notif, "iss", $ticket['user_id'], $notif_message, $notif_link);
                    mysqli_stmt_execute($stmt_notif);
                    mysqli_stmt_close($stmt_notif);

                    // Send Telegram notification
                    $owner_id = $ticket['user_id'];
                    $owner_telegram_query = mysqli_query($link, "SELECT telegram_chat_id FROM users WHERE id = $owner_id");
                    if($owner_telegram_query && mysqli_num_rows($owner_telegram_query) > 0){
                        $owner_chat_id = mysqli_fetch_assoc($owner_telegram_query)['telegram_chat_id'];
                        if(!empty($owner_chat_id)){
                            send_telegram_message($owner_chat_id, $notif_message);
                        }
                    }
                }

                $success_msg = "پاسخ شما با موفقیت ثبت شد.";
            } else {
                $err = "خطا در ثبت پاسخ.";
            }
            mysqli_stmt_close($stmt_reply);
        }
    }
}

// Fetch all replies for this ticket
$replies = [];
$sql_replies = "SELECT r.*, u.username as replier_username
                FROM ticket_replies r
                JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC";
if($stmt_replies = mysqli_prepare($link, $sql_replies)){
    mysqli_stmt_bind_param($stmt_replies, "i", $ticket_id);
    mysqli_stmt_execute($stmt_replies);
    $result_replies = mysqli_stmt_get_result($stmt_replies);
    $replies = mysqli_fetch_all($result_replies, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_replies);
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>
<style>
.ticket-message, .ticket-reply { background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.ticket-header, .reply-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e9ecef; padding-bottom: 10px; margin-bottom: 15px; }
.ticket-header strong, .reply-header strong { font-size: 1.1em; }
.ticket-header span, .reply-header span { font-size: 0.9em; color: #6c757d; }
.ticket-body, .reply-body { line-height: 1.6; }
.is-creator { border-right: 4px solid #007bff; }
.is-responder { border-right: 4px solid #28a745; }
</style>

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="my_tickets.php" class="btn btn-secondary">&larr; بازگشت به لیست تیکت‌ها</a>
        <div>
            <span class="badge badge-info" style="font-size: 1em; padding: 0.5em 1em;"><?php echo get_status_badge($ticket['status']); ?></span>
            <?php if($ticket['priority'] == 'urgent'): ?>
                <span class="badge badge-danger" style="font-size: 1em; padding: 0.5em 1em;">فوری</span>
            <?php endif; ?>
        </div>
    </div>

    <h2>موضوع: <?php echo htmlspecialchars($ticket['title']); ?></h2>

    <!-- Original Ticket Message -->
    <div class="ticket-message is-creator">
        <div class="ticket-header">
            <strong><?php echo htmlspecialchars($ticket['creator_username']); ?></strong>
            <span><?php echo to_persian_date($ticket['created_at']); ?></span>
        </div>
        <div class="ticket-body">
            <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
        </div>
    </div>

    <!-- Replies -->
    <?php foreach($replies as $reply): ?>
    <div class="ticket-reply <?php echo ($reply['user_id'] == $ticket['user_id']) ? 'is-creator' : 'is-responder'; ?>">
        <div class="reply-header">
            <strong><?php echo htmlspecialchars($reply['replier_username']); ?></strong>
            <span><?php echo to_persian_date($reply['created_at']); ?></span>
        </div>
        <div class="reply-body">
            <?php echo nl2br(htmlspecialchars($reply['reply_message'])); ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Add Reply Form -->
    <div class="form-container">
        <h3>ارسال پاسخ جدید</h3>
        <?php
        if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
        if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
        ?>
        <form action="view_ticket.php?id=<?php echo $ticket_id; ?>" method="post">
            <div class="form-group">
                <label for="reply_message">پاسخ شما</label>
                <textarea name="reply_message" id="reply_message" rows="5" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <input type="submit" name="add_reply" class="btn btn-primary" value="ارسال پاسخ">
            </div>
        </form>
    </div>

    <?php if(!empty($_SESSION['is_admin'])): // Only show for admins for now ?>
    <hr>
    <div class="form-container">
        <h3>ارجاع تیکت</h3>
        <form action="reassign_ticket.php" method="post">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
            <div class="form-group">
                <label>نوع ارجاع</label>
                <div class="radio-group">
                    <input type="radio" name="assign_type" value="department" id="assign_type_dept_re" checked onchange="toggleReassignFields()"> <label for="assign_type_dept_re">ارجاع به بخش</label>
                </div>
                <div class="radio-group">
                    <input type="radio" name="assign_type" value="user" id="assign_type_user_re" onchange="toggleReassignFields()"> <label for="assign_type_user_re">ارجاع به کاربر</label>
                </div>
            </div>
            <div id="department_field_re" class="form-group">
                <label for="department_id_re">انتخاب بخش</label>
                <select name="department_id" id="department_id_re" class="form-control">
                    <?php
                    $departments = mysqli_query($link, "SELECT id, department_name FROM departments ORDER BY department_name ASC");
                    while($dept = mysqli_fetch_assoc($departments)): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="user_field_re" class="form-group" style="display: none;">
                <label for="user_id_re">انتخاب کاربر</label>
                <select name="user_id" id="user_id_re" class="form-control">
                    <?php
                     $users = mysqli_query($link, "SELECT id, username FROM users ORDER BY username ASC");
                    while($user = mysqli_fetch_assoc($users)): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-info">ارجاع</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleReassignFields() {
    const assignType = document.querySelector('input[name="assign_type"]:checked').value;
    const deptField = document.getElementById('department_field_re');
    const userField = document.getElementById('user_field_re');

    if (assignType === 'department') {
        deptField.style.display = 'block';
        userField.style.display = 'none';
    } else {
        deptField.style.display = 'none';
        userField.style.display = 'block';
    }
}
</script>

<?php
// mysqli_close($link);
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php";
?>
