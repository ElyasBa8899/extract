<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: manage_financials
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// --- Booklet Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booklet'])) {
    $title = trim($_POST['booklet_title']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    if ($title && $price) {
        $stmt = $pdo->prepare("INSERT INTO booklets (title, price) VALUES (?, ?)");
        $stmt->execute([$title, $price]);
        $message = "جزوه جدید اضافه شد.";
    }
}

// --- Transaction Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $user_id = $_POST['user_id'];
    $type = $_POST['transaction_type'];
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $notes = trim($_POST['notes']);

    if ($user_id && $type && $amount) {
        $stmt = $pdo->prepare("INSERT INTO booklet_transactions (user_id, transaction_type, amount, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $amount, $notes]);
        $message = "تراکنش جدید با موفقیت ثبت شد.";
    } else {
        $error = "اطلاعات تراکنش نامعتبر است.";
    }
}


// --- Fetch Data ---
$booklets = $pdo->query("SELECT * FROM booklets ORDER BY title")->fetchAll();
$users = $pdo->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name")->fetchAll();
$transactions = $pdo->query("
    SELECT t.*, u.full_name as user_name
    FROM booklet_transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.transaction_date DESC
")->fetchAll();

// Calculate balances
$balances = $pdo->query("
    SELECT
        u.id,
        u.full_name,
        (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = u.id AND transaction_type = 'debit') -
        (SELECT COALESCE(SUM(amount), 0) FROM booklet_transactions WHERE user_id = u.id AND transaction_type = 'credit') as balance
    FROM users u
    WHERE u.is_admin = 0
    GROUP BY u.id
    HAVING balance != 0
    ORDER BY balance DESC
")->fetchAll();


?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت مالی جزوات</h2>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5>افزودن جزوه جدید</h5></div>
                    <div class="card-body">
                        <form action="manage_booklets.php" method="post">
                            <div class="form-group"><input type="text" name="booklet_title" class="form-control" placeholder="عنوان جزوه" required></div>
                            <div class="form-group"><input type="number" name="price" class="form-control" placeholder="قیمت (تومان)" required></div>
                            <button type="submit" name="add_booklet" class="btn btn-secondary">افزودن جزوه</button>
                        </form>
                    </div>
                </div>
                 <div class="card mt-3">
                    <div class="card-header"><h5>وضعیت حساب مدرسین</h5></div>
                    <ul class="list-group list-group-flush">
                        <?php foreach($balances as $user_balance): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($user_balance['full_name']); ?>
                            <span class="badge <?php echo $user_balance['balance'] > 0 ? 'badge-danger' : 'badge-success'; ?>">
                                <?php echo number_format(abs($user_balance['balance'])); ?>
                                <?php echo $user_balance['balance'] > 0 ? 'بدهکار' : 'بستانکار'; ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-8">
                 <div class="card">
                    <div class="card-header"><h5>ثبت تراکنش جدید</h5></div>
                    <div class="card-body">
                        <form action="manage_booklets.php" method="post">
                             <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>مدرس</label>
                                    <select name="user_id" class="form-control" required>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>نوع تراکنش</label>
                                    <select name="transaction_type" class="form-control" required>
                                        <option value="debit">بدهکار (خرید جزوه)</option>
                                        <option value="credit">بستانکار (پرداخت وجه)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>مبلغ (تومان)</label>
                                    <input type="number" name="amount" class="form-control" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>یادداشت</label>
                                    <input type="text" name="notes" class="form-control">
                                </div>
                            </div>
                            <button type="submit" name="add_transaction" class="btn btn-primary">ثبت تراکنش</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5>لیست کل تراکنش‌ها</h5></div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>مدرس</th><th>نوع</th><th>مبلغ</th><th>یادداشت</th><th>تاریخ</th></tr></thead>
                    <tbody>
                        <?php foreach($transactions as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['user_name']); ?></td>
                            <td><?php echo $t['transaction_type'] == 'debit' ? 'بدهکار' : 'بستانکار'; ?></td>
                            <td class="<?php echo $t['transaction_type'] == 'debit' ? 'text-danger' : 'text-success'; ?>"><?php echo number_format($t['amount']); ?></td>
                            <td><?php echo htmlspecialchars($t['notes']); ?></td>
                            <td><?php echo to_persian_date($t['transaction_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
