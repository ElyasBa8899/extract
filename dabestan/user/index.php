<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
if ($_SESSION['is_admin']) {
    header("location: ../admin/index.php");
    exit;
}

$pdo = get_db_connection();
$user_id = $_SESSION['id'];

// Fetch data for dashboard widgets
$stmt_tickets = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE (user_id = ? OR assigned_to_user_id = ?) AND status != 'closed'");
$stmt_tickets->execute([$user_id, $user_id]);
$open_tickets_count = $stmt_tickets->fetchColumn();

$stmt_tasks = $pdo->prepare("SELECT COUNT(*) FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.assigned_to_user_id = ? AND t.status IN ('pending', 'in_progress')");
$stmt_tasks->execute([$user_id]);
$pending_tasks_count = $stmt_tasks->fetchColumn();

$stmt_finance = $pdo->prepare("
    SELECT
        (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = :user_id AND transaction_type = 'credit') -
        (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = :user_id AND transaction_type = 'debit')
    AS balance
");
$stmt_finance->execute([':user_id' => $user_id]);
$balance = $stmt_finance->fetchColumn();

$stmt_recent_tasks = $pdo->prepare("
    SELECT t.id, t.title, t.priority, t.status, t.deadline
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.assigned_to_user_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt_recent_tasks->execute([$user_id]);
$recent_tasks = $stmt_recent_tasks->fetchAll();

$quick_links = [
    ['url' => 'new_ticket.php', 'icon' => 'plus-circle', 'label' => 'تیکت جدید'],
    ['url' => 'my_tasks.php', 'icon' => 'briefcase', 'label' => 'وظایف من'],
    ['url' => 'fill_form.php', 'icon' => 'edit-3', 'label' => 'تکمیل فرم'],
    ['url' => 'my_financial_status.php', 'icon' => 'dollar-sign', 'label' => 'وضعیت مالی']
];

require_once "../includes/header.php";
?>
<div class="page-content">
    <div class="container-fluid">
        <div class="dashboard-header">
            <h1>سلام، <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
            <p>به پنل کاربری خود در سامانه دبستان خوش آمدید.</p>
        </div>

        <div class="row">
            <div class="col-md-4">
                 <div class="stat-card">
                    <div class="stat-icon" style="background-color: #eef2ff;"><i data-feather="message-square" style="color: #6366f1;"></i></div>
                    <div class="stat-info"><h4>تیکت‌های باز</h4><span><?php echo $open_tickets_count; ?></span></div>
                </div>
            </div>
             <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #f0fdf4;"><i data-feather="check-circle" style="color: #22c55e;"></i></div>
                    <div class="stat-info"><h4>وظایف در حال انجام</h4><span><?php echo $pending_tasks_count; ?></span></div>
                </div>
            </div>
             <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #fffbeb;"><i data-feather="dollar-sign" style="color: #f59e0b;"></i></div>
                    <div class="stat-info"><h4>وضعیت حساب</h4><span class="<?php echo $balance >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo number_format($balance); ?> تومان</span></div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="widget">
                    <div class="widget-header d-flex justify-content-between align-items-center">
                        <h5><i data-feather="briefcase"></i> آخرین وظایف شما</h5>
                        <a href="my_tasks.php" class="btn btn-sm btn-outline-secondary">مشاهده همه</a>
                    </div>
                    <div class="widget-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>عنوان</th><th>اولویت</th><th>وضعیت</th><th>مهلت</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_tasks)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">هیچ وظیفه‌ای برای نمایش وجود ندارد.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_tasks as $task): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                                <td><?php echo get_priority_badge_view($task['priority']); ?></td>
                                                <td><?php echo get_status_badge_view($task['status']); ?></td>
                                                <td><?php echo to_persian_date($task['deadline']); ?></td>
                                                <td><a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-light">مشاهده</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                 <div class="widget">
                    <div class="widget-header"><h5><i data-feather="link"></i> دسترسی سریع</h5></div>
                    <div class="widget-body">
                        <div class="quick-links-grid">
                             <?php foreach ($quick_links as $link_item): ?>
                                <a href="<?php echo $link_item['url']; ?>" class="quick-link-card">
                                    <i data-feather="<?php echo $link_item['icon']; ?>"></i>
                                    <span><?php echo $link_item['label']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.dashboard-header { margin-bottom: 2rem; }
.dashboard-header h1 { font-weight: 700; }
.stat-card { background: var(--widget-bg); border-radius: var(--radius-lg); padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow-sm); height: 100%; }
.stat-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-info h4 { margin: 0 0 4px; font-size: 0.9rem; color: var(--text-muted); }
.stat-info span { font-size: 1.5rem; font-weight: 700; }
.quick-links-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.quick-link-card { background: var(--background-color); border-radius: var(--radius-md); padding: 1rem; text-align: center; text-decoration: none; color: var(--text-color); transition: all 0.2s ease; }
.quick-link-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); color: var(--primary-color); }
.quick-link-card i { width: 24px; height: 24px; margin-bottom: 0.5rem; }
.quick-link-card span { font-weight: 500; font-size: 0.9em; }
</style>
<?php
require_once "../includes/footer.php";
?>
