<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!is_admin()) { // permission: manage_donations
    header("Location: ../user/index.php");
    exit();
}

$pdo = get_db_connection();
$message = '';
$error = '';

// --- Donor Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_donor'])) {
    $name = trim($_POST['donor_name']);
    $contact = trim($_POST['contact_info']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO donors (name, contact_info) VALUES (?, ?)");
        $stmt->execute([$name, $contact]);
        $message = "خیر جدید با موفقیت اضافه شد.";
    }
}

// --- Donation Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_donation'])) {
    $donor_id = $_POST['donor_id'];
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $project_name = trim($_POST['project_name']);
    $donation_date = trim($_POST['donation_date']);

    if ($donor_id && $amount && $donation_date) {
        $stmt = $pdo->prepare("INSERT INTO donations (donor_id, amount, project_name, donation_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$donor_id, $amount, $project_name, $donation_date]);
        $message = "کمک مالی جدید با موفقیت ثبت شد.";
    } else {
        $error = "اطلاعات وارد شده نامعتبر است.";
    }
}

// --- Fetch Data ---
$donors = $pdo->query("SELECT * FROM donors ORDER BY name")->fetchAll();
$donations = $pdo->query("
    SELECT d.*, dn.name as donor_name
    FROM donations d
    JOIN donors dn ON d.donor_id = dn.id
    ORDER BY d.donation_date DESC
")->fetchAll();
$total_donations = $pdo->query("SELECT SUM(amount) FROM donations")->fetchColumn();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>مدیریت صله (کمک‌های مالی)</h2>
        <p>خیرین و کمک‌های مالی دریافت شده را مدیریت کنید.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="widget">
            <div class="widget-header"><h3>مجموع کل کمک‌های مالی</h3></div>
            <div class="widget-body financial-widget-body">
                <div class="balance"><?php echo number_format($total_donations ?? 0); ?> تومان</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5>افزودن خیر جدید</h5></div>
                    <div class="card-body">
                        <form action="manage_donations.php" method="post">
                            <div class="form-group"><input type="text" name="donor_name" class="form-control" placeholder="نام خیر" required></div>
                            <div class="form-group"><input type="text" name="contact_info" class="form-control" placeholder="اطلاعات تماس"></div>
                            <button type="submit" name="add_donor" class="btn btn-secondary">افزودن خیر</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h5>ثبت کمک مالی جدید</h5></div>
                    <div class="card-body">
                         <form action="manage_donations.php" method="post">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>خیر</label>
                                    <select name="donor_id" class="form-control" required>
                                        <?php foreach($donors as $donor): ?>
                                            <option value="<?php echo $donor['id']; ?>"><?php echo htmlspecialchars($donor['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>مبلغ (تومان)</label>
                                    <input type="number" name="amount" class="form-control" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>برای پروژه</label>
                                    <input type="text" name="project_name" class="form-control" placeholder="مثلا: جشن غدیر ۱۴۰۳">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>تاریخ</label>
                                    <input type="date" name="donation_date" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" name="add_donation" class="btn btn-primary">ثبت کمک مالی</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5>لیست کمک‌های مالی ثبت شده</h5></div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>خیر</th><th>مبلغ</th><th>پروژه</th><th>تاریخ</th></tr></thead>
                    <tbody>
                        <?php foreach($donations as $donation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                            <td><?php echo number_format($donation['amount']); ?> تومان</td>
                            <td><?php echo htmlspecialchars($donation['project_name']); ?></td>
                            <td><?php echo to_persian_date($donation['donation_date'], 'Y/m/d'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
