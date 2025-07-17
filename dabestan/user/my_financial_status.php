<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Calculate user's balance
$balance = 0;
$sql = "SELECT
            (SELECT SUM(amount) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'credit') as total_credit,
            (SELECT SUM(amount) FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'debit') as total_debit";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_credit = $row['total_credit'] ?? 0;
    $total_debit = $row['total_debit'] ?? 0;
    $balance = $total_credit - $total_debit;
    mysqli_stmt_close($stmt);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2><i class="fas fa-file-invoice-dollar"></i> وضعیت مالی من</h2>
    <p>در این بخش می‌توانید وضعیت حساب خود (مربوط به جزوات و...) را مشاهده کنید.</p>

    <div class="card text-white <?php echo ($balance >= 0) ? 'bg-success' : 'bg-danger'; ?> mb-3" style="max-width: 18rem;">
        <div class="card-header">موجودی حساب شما</div>
        <div class="card-body">
            <h5 class="card-title"><?php echo number_format($balance, 2); ?> تومان</h5>
            <p class="card-text">
                <?php
                if ($balance > 0) echo "شما بستانکار هستید.";
                elseif ($balance < 0) echo "شما بدهکار هستید.";
                else echo "حساب شما تسویه است.";
                ?>
            </p>
        </div>
    </div>

    <a href="financial_transactions.php" class="btn btn-info">مشاهده ریز تراکنش‌ها</a>

</div>

<?php
require_once "../includes/footer.php";
?>
