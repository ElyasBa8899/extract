<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch user's transactions
$sql = "SELECT
            bt.transaction_date,
            bt.transaction_type,
            bt.amount,
            bt.notes,
            b.name as booklet_name
        FROM booklet_transactions bt
        LEFT JOIN booklets b ON bt.booklet_id = b.id
        WHERE bt.user_id = ?
        ORDER BY bt.transaction_date DESC";
$transactions = [];
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="my_financial_status.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> بازگشت به وضعیت مالی</a>
    <h2><i class="fas fa-history"></i> ریز تراکنش‌های مالی من</h2>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>نوع تراکنش</th>
                        <th>مبلغ (تومان)</th>
                        <th>بابت</th>
                        <th>یادداشت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" class="text-center">هیچ تراکنشی برای شما ثبت نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo to_persian_date($t['transaction_date']); ?></td>
                                <td>
                                    <?php if ($t['transaction_type'] == 'credit'): ?>
                                        <span class="badge bg-success">واریز</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">برداشت</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($t['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($t['booklet_name'] ?? 'متفرقه'); ?></td>
                                <td><?php echo htmlspecialchars($t['notes']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
