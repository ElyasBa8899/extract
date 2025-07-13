<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch all financial transactions for the user
$transactions = [];
$sql_trans = "SELECT bt.*, b.name as booklet_name
              FROM booklet_transactions bt
              LEFT JOIN booklets b ON bt.booklet_id = b.id
              WHERE bt.user_id = ?
              ORDER BY bt.transaction_date DESC";
if($stmt = mysqli_prepare($link, $sql_trans)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="my_financial_status.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به وضعیت مالی</a>
    <h2>تمام تراکنش‌های مالی</h2>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>نوع تراکنش</th>
                        <th>مبلغ (تومان)</th>
                        <th>جزئیات</th>
                        <th>یادداشت</th>
                        <th>تاریخ ثبت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($transactions)): ?>
                        <tr><td colspan="5" style="text-align: center;">هیچ تراکنشی برای شما ثبت نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach($transactions as $trans): ?>
                        <tr class="<?php echo $trans['transaction_type'] == 'debit' ? 'table-danger' : 'table-success'; ?>">
                            <td><?php echo $trans['transaction_type'] == 'debit' ? 'بدهی (تحویل جزوه)' : 'پرداخت'; ?></td>
                            <td><?php echo number_format($trans['amount']); ?></td>
                            <td>
                                <?php if($trans['transaction_type'] == 'debit'): ?>
                                    <?php echo htmlspecialchars($trans['quantity'] . ' عدد از ' . $trans['booklet_name']); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($trans['notes']); ?></td>
                            <td><?php echo to_persian_date($trans['transaction_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
