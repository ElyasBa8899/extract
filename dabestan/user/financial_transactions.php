<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
// We will add role-based access control later.

$err = $success_msg = "";

// Fetch users (teachers) and booklets for dropdowns
$users = mysqli_query($link, "SELECT id, first_name, last_name FROM users WHERE is_admin = 0 ORDER BY last_name ASC");
$booklets = mysqli_query($link, "SELECT id, name, price FROM booklets ORDER BY name ASC");

// Handle Transaction POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_transaction'])) {
    $user_id = $_POST['user_id'];
    $transaction_type = $_POST['transaction_type'];
    $notes = $_POST['notes'];
    $created_by = $_SESSION['id'];

    if (empty($user_id) || empty($transaction_type)) {
        $err = "انتخاب مدرس و نوع تراکنش الزامی است.";
    } else {
        mysqli_begin_transaction($link);
        try {
            $sql = "INSERT INTO booklet_transactions (user_id, booklet_id, quantity, transaction_type, amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);

            if ($transaction_type == 'debit') { // Debit - Handing out booklets
                $booklet_id = $_POST['booklet_id'];
                $quantity = $_POST['quantity'];

                // Get booklet price
                $sql_price = "SELECT price FROM booklets WHERE id = ?";
                $stmt_price = mysqli_prepare($link, $sql_price);
                mysqli_stmt_bind_param($stmt_price, "i", $booklet_id);
                mysqli_stmt_execute($stmt_price);
                $result_price = mysqli_stmt_get_result($stmt_price);
                $booklet_price = mysqli_fetch_assoc($result_price)['price'];

                $amount = $booklet_price * $quantity;
                mysqli_stmt_bind_param($stmt, "iiisdsi", $user_id, $booklet_id, $quantity, $transaction_type, $amount, $notes, $created_by);

            } else { // Credit - Receiving payment
                $amount = $_POST['amount'];
                $booklet_id = null;
                $quantity = null;
                mysqli_stmt_bind_param($stmt, "iiisdsi", $user_id, $booklet_id, $quantity, $transaction_type, $amount, $notes, $created_by);
            }

            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($link);
                $success_msg = "تراکنش با موفقیت ثبت شد.";
            } else {
                throw new Exception("خطا در اجرای دستور SQL.");
            }
            mysqli_stmt_close($stmt);

        } catch (Exception $e) {
            mysqli_rollback($link);
            $err = "خطا در ثبت تراکنش: " . $e->getMessage();
        }
    }
}

// Fetch recent transactions for display
$recent_transactions = [];
$sql_trans = "SELECT bt.*, u.username as teacher_name, b.name as booklet_name
              FROM booklet_transactions bt
              JOIN users u ON bt.user_id = u.id
              LEFT JOIN booklets b ON bt.booklet_id = b.id
              ORDER BY bt.transaction_date DESC LIMIT 20";
$result_trans = mysqli_query($link, $sql_trans);
if($result_trans){
    $recent_transactions = mysqli_fetch_all($result_trans, MYSQLI_ASSOC);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>ثبت تراکنش‌های مالی (جزوات)</h2>
    <p>تحویل جزوه به مدرسین یا دریافت وجه از آن‌ها را در این بخش ثبت کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Create New Transaction Section -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ثبت تراکنش جدید</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>نوع تراکنش <span style="color: red;">*</span></label>
                <div class="radio-group">
                    <input type="radio" name="transaction_type" value="debit" id="type_debit" onclick="toggleTransactionFields()" required> <label for="type_debit">تحویل جزوه (بدهکار کردن)</label>
                </div>
                <div class="radio-group">
                    <input type="radio" name="transaction_type" value="credit" id="type_credit" onclick="toggleTransactionFields()" required> <label for="type_credit">پرداخت وجه (بستانکار کردن)</label>
                </div>
            </div>

            <div class="form-group">
                <label for="user_id">برای مدرس <span style="color: red;">*</span></label>
                <select name="user_id" id="user_id" class="form-control" required>
                    <option value="">انتخاب کنید...</option>
                    <?php while($user = mysqli_fetch_assoc($users)) {
                        echo "<option value='{$user['id']}'>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</option>";
                    } ?>
                </select>
            </div>

            <div id="debit_fields" style="display:none;">
                <div class="form-group">
                    <label for="booklet_id">جزوه تحویلی <span style="color: red;">*</span></label>
                    <select name="booklet_id" id="booklet_id" class="form-control">
                        <option value="">انتخاب کنید...</option>
                        <?php mysqli_data_seek($booklets, 0); while($booklet = mysqli_fetch_assoc($booklets)) {
                             echo "<option value='{$booklet['id']}'>" . htmlspecialchars($booklet['name'] . ' (' . number_format($booklet['price']) . ' تومان)') . "</option>";
                        }?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">تعداد <span style="color: red;">*</span></label>
                    <input type="number" name="quantity" id="quantity" class="form-control" min="1">
                </div>
            </div>

            <div id="credit_fields" style="display:none;">
                <div class="form-group">
                    <label for="amount">مبلغ پرداختی (تومان) <span style="color: red;">*</span></label>
                    <input type="number" step="0.01" name="amount" id="amount" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">یادداشت (اختیاری)</label>
                <textarea name="notes" id="notes" class="form-control"></textarea>
            </div>

            <div class="form-group">
                <input type="submit" name="add_transaction" class="btn btn-primary" value="ثبت تراکنش">
            </div>
        </form>
    </div>

    <!-- Recent Transactions -->
    <div class="table-container">
        <h3>۲۰ تراکنش اخیر</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>مدرس</th>
                    <th>نوع</th>
                    <th>مبلغ (تومان)</th>
                    <th>جزئیات</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_transactions as $trans): ?>
                <tr class="<?php echo $trans['transaction_type'] == 'debit' ? 'table-danger' : 'table-success'; ?>">
                    <td><?php echo htmlspecialchars($trans['teacher_name']); ?></td>
                    <td><?php echo $trans['transaction_type'] == 'debit' ? 'بدهی' : 'پرداخت'; ?></td>
                    <td><?php echo number_format($trans['amount']); ?></td>
                    <td>
                        <?php if($trans['transaction_type'] == 'debit'): ?>
                            <?php echo htmlspecialchars($trans['quantity'] . ' عدد از ' . $trans['booklet_name']); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($trans['notes']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $trans['transaction_date']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleTransactionFields() {
    const type = document.querySelector('input[name="transaction_type"]:checked').value;
    const debitFields = document.getElementById('debit_fields');
    const creditFields = document.getElementById('credit_fields');

    if (type === 'debit') {
        debitFields.style.display = 'block';
        creditFields.style.display = 'none';
        document.getElementById('booklet_id').required = true;
        document.getElementById('quantity').required = true;
        document.getElementById('amount').required = false;
    } else {
        debitFields.style.display = 'none';
        creditFields.style.display = 'block';
        document.getElementById('booklet_id').required = false;
        document.getElementById('quantity').required = false;
        document.getElementById('amount').required = true;
    }
}
</script>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
