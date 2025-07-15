<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// --- Data Fetching for Widgets ---

// 1. Fetch last 5 tickets for the user
$tickets = [];
$sql_tickets = "SELECT id, title, status FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
if($stmt_tickets = mysqli_prepare($link, $sql_tickets)){
    mysqli_stmt_bind_param($stmt_tickets, "i", $user_id);
    mysqli_stmt_execute($stmt_tickets);
    $result_tickets = mysqli_stmt_get_result($stmt_tickets);
    $tickets = mysqli_fetch_all($result_tickets, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_tickets);
}

// 2. Fetch financial status for the user
$balance = 0;
$sql_finance = "SELECT
                    (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'credit') -
                    (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'debit')
                AS balance";
if($stmt_finance = mysqli_prepare($link, $sql_finance)){
    mysqli_stmt_bind_param($stmt_finance, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt_finance);
    $result_finance = mysqli_stmt_get_result($stmt_finance);
    $balance_row = mysqli_fetch_assoc($result_finance);
    $balance = $balance_row['balance'];
    mysqli_stmt_close($stmt_finance);
}

// Helper function for ticket status badge
function get_status_badge_dash($status) {
    $badges = [
        'open' => '<span class="badge badge-primary">باز</span>',
        'in_progress' => '<span class="badge badge-warning">در حال بررسی</span>',
        'closed' => '<span class="badge badge-secondary">بسته</span>',
        'urgent' => '<span class="badge badge-danger">فوری</span>'
    ];
    return $badges[$status] ?? '';
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>

<div class="page-content">
    <h2>داشبورد</h2>
    <p>سلام <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>، به سامانه خوش آمدید. در اینجا خلاصه‌ای از فعالیت‌های خود را مشاهده می‌کنید.</p>

    <div class="dashboard-grid" style="margin-top: 20px;">

        <!-- Tickets Widget -->
        <div class="widget">
            <div class="widget-header">
                <h3>آخرین تیکت‌های شما</h3>
                <a href="my_tickets.php">مشاهده همه</a>
            </div>
            <div class="widget-body">
                <ul>
                    <?php if(empty($tickets)): ?>
                        <li>شما تاکنون تیکتی ثبت نکرده‌اید.</li>
                    <?php else: ?>
                        <?php foreach($tickets as $ticket): ?>
                            <li>
                                <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>"><?php echo htmlspecialchars($ticket['title']); ?></a>
                                <?php echo get_status_badge_dash($ticket['status']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Financial Status Widget -->
        <div class="widget">
            <div class="widget-header">
                <h3>وضعیت حساب جزوات</h3>
                <a href="my_financial_status.php">مشاهده جزئیات</a>
            </div>
            <div class="widget-body financial-widget-body">
                <div class="balance <?php echo $balance >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo number_format(abs($balance)); ?>
                    <small>تومان</small>
                </div>
                <span>(<?php echo $balance >= 0 ? 'بستانکار' : 'بدهکار'; ?>)</span>
            </div>
        </div>

        <!-- More widgets can be added here -->

        <!-- Self-Assessment Analysis Widget -->
        <div class="widget">
            <div class="widget-header">
                <h3>فرم‌های خوداظهاری</h3>
                <a href="my_self_assessments.php">مشاهده کامل</a>
            </div>
            <div class="widget-body">
                <p>برای ارزیابی و بهبود عملکرد، فرم‌های خوداظهاری خود را به صورت هفتگی ثبت کنید.</p>
                <a href="self_assessment_form.php" class="btn btn-primary" style="margin-top: 10px;">پر کردن فرم جدید</a>
            </div>
        </div>

    </div>
</div>

<?php
// mysqli_close($link);
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php";
?>
