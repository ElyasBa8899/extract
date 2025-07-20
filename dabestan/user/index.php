<?php
session_start();
require_once __DIR__ . '/../core/functions.php';
$link = get_db_connection();
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch data for dashboard widgets
// 1. Open Tickets Count
$open_tickets_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM tickets WHERE user_id = $user_id AND status != 'closed'"))['count'];

// 2. Pending Tasks Count
$pending_tasks_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.assigned_to_user_id = $user_id AND t.status = 'pending'"))['count'];

// 3. Financial Balance
$balance = 0;
$sql_finance = "SELECT (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'credit') - (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'debit') AS balance";
if($stmt_finance = mysqli_prepare($link, $sql_finance)){
    mysqli_stmt_bind_param($stmt_finance, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt_finance);
    $result_finance = mysqli_stmt_get_result($stmt_finance);
    $balance_row = mysqli_fetch_assoc($result_finance);
    $balance = $balance_row['balance'];
    mysqli_stmt_close($stmt_finance);
}

// 4. Quick Links
$quick_links = [
    ['url' => 'new_ticket.php', 'icon' => 'plus-circle', 'label' => 'تیکت جدید'],
    ['url' => 'my_tasks.php', 'icon' => 'briefcase', 'label' => 'وظایف من'],
    ['url' => 'self_assessment_form.php', 'icon' => 'edit-3', 'label' => 'فرم خوداظهاری'],
    ['url' => 'my_financial_status.php', 'icon' => 'dollar-sign', 'label' => 'وضعیت مالی']
];

?>

<div class="page-content">
    <div class="container-fluid">
        <div class="dashboard-header">
            <h1>سلام، <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
            <p>به پنل کاربری خود در سامانه دبستان خوش آمدید.</p>
        </div>

        <div class="dashboard-stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #eef2ff;">
                    <i data-feather="message-square" style="color: #6366f1;"></i>
                </div>
                <div class="stat-info">
                    <h4>تیکت‌های باز</h4>
                    <span><?php echo $open_tickets_count; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #f0fdf4;">
                    <i data-feather="check-circle" style="color: #22c55e;"></i>
                </div>
                <div class="stat-info">
                    <h4>وظایف در انتظار</h4>
                    <span><?php echo $pending_tasks_count; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #fffbeb;">
                    <i data-feather="dollar-sign" style="color: #f59e0b;"></i>
                </div>
                <div class="stat-info">
                    <h4>وضعیت حساب</h4>
                    <span class="<?php echo $balance >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo number_format($balance); ?> تومان</span>
                </div>
            </div>
        </div>

        <div class="dashboard-section">
            <h2>دسترسی سریع</h2>
            <div class="quick-links-grid">
                <?php foreach ($quick_links as $link_item): ?>
                    <a href="<?php echo $link_item['url']; ?>" class="quick-link-card">
                        <i data-feather="<?php echo $link_item['icon']; ?>"></i>
                        <span><?php echo $link_item['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="widget">
                    <div class="widget-header">
                        <h3>آخرین وظایف شما</h3>
                        <a href="my_tasks.php">مشاهده همه</a>
                    </div>
                    <div class="widget-body">
                        <!-- Task list content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-header { margin-bottom: 32px; }
.dashboard-header h1 { font-size: 2em; font-weight: 700; }
.dashboard-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px; }
.stat-card { background: #fff; border-radius: 12px; padding: 24px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); }
.stat-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.stat-info h4 { margin: 0 0 4px; font-size: 0.9rem; color: var(--text-muted); }
.stat-info span { font-size: 1.5rem; font-weight: 700; }
.dashboard-section { margin-bottom: 32px; }
.quick-links-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; }
.quick-link-card { background: #fff; border-radius: 12px; padding: 24px; text-align: center; text-decoration: none; color: var(--text-color); box-shadow: var(--shadow-sm); transition: all 0.2s ease; }
.quick-link-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); color: var(--primary-color); }
.quick-link-card i { width: 32px; height: 32px; margin-bottom: 12px; }
.quick-link-card span { font-weight: 600; }
</style>
