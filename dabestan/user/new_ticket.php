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
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $title = trim($_POST['title']);
    $message_text = trim($_POST['message']);
    $priority = $_POST['priority'];
    $user_id = $_SESSION['id'];

    if (empty($title) || empty($message_text)) {
        $error = "عنوان و متن پیام الزامی است.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, title, message, priority) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message_text, $priority]);
            $message = "تیکت شما با موفقیت ثبت شد. به زودی پاسخ داده خواهد شد.";
        } catch (PDOException $e) {
            $error = "خطا در ثبت تیکت: " . $e->getMessage();
        }
    }
}

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>ارسال تیکت جدید</h2>
        <p>سوالات، مشکلات و درخواست‌های خود را از این طریق برای مدیران ارسال کنید.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <a href="my_tickets.php" class="btn btn-primary">مشاهده لیست تیکت‌ها</a>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="card">
                <div class="card-header"><h5>فرم ارسال تیکت</h5></div>
                <div class="card-body">
                    <form action="new_ticket.php" method="post">
                        <div class="form-group">
                            <label for="title">عنوان تیکت</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="message">متن پیام</label>
                            <textarea name="message" id="message" class="form-control" rows="6" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="priority">اولویت</label>
                            <select name="priority" id="priority" class="form-control">
                                <option value="low">کم</option>
                                <option value="medium" selected>متوسط</option>
                                <option value="high">زیاد</option>
                                <option value="urgent">فوری</option>
                            </select>
                        </div>
                        <button type="submit" name="submit_ticket" class="btn btn-primary">ارسال تیکت</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
