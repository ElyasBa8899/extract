<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch user's financial transactions
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

// Calculate account balance
$total_debit = 0;
$total_credit = 0;
foreach($transactions as $trans){
    if($trans['transaction_type'] == 'debit'){
        $total_debit += $trans['amount'];
    } else {
        $total_credit += $trans['amount'];
    }
}
$balance = $total_credit - $total_debit;


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>وضعیت حساب مالی من (مربوط به جزوات)</h2>
    <p>در این بخش می‌توانید تاریخچه تراکنش‌ها و مانده حساب خود را مشاهده کنید.</p>

    <div class="financial-summary" style="display: flex; justify-content: space-around; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px;">
        <div>
            <h4>مجموع بدهی‌ها (تحویل جزوه)</h4>
            <p style="color: #dc3545; font-size: 1.5em; font-weight: bold;"><?php echo number_format($total_debit); ?> تومان</p>
        </div>
        <div>
            <h4>مجموع پرداخت‌ها</h4>
            <p style="color: #28a745; font-size: 1.5em; font-weight: bold;"><?php echo number_format($total_credit); ?> تومان</p>
        </div>
        <div>
            <h4>مانده حساب نهایی</h4>
            <p style="font-size: 1.5em; font-weight: bold; color: <?php echo $balance >= 0 ? '#28a745' : '#dc3545'; ?>">
                <?php echo number_format(abs($balance)); ?> تومان
                <span>(<?php echo $balance >= 0 ? 'بستانکار' : 'بدهکار'; ?>)</span>
            </p>
        </div>
    </div>


    <!-- Transactions History -->
    <div class="table-container">
        <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>تاریخچه تراکنش‌ها</h3>
            <a href="view_all_transactions.php" class="btn btn-secondary">مشاهده همه تراکنش‌ها</a>
        </div>
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
                    <?php foreach(array_slice($transactions, 0, 20) as $trans): // Show only last 20 ?>
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

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
