<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/access_control.php";
require_once "../includes/functions.php";

require_permission('manage_donations');

$err = $success_msg = "";

// Handle Add/Edit Donation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_donation'])) {
    $donation_id = $_POST['donation_id'];
    $donor_name = trim($_POST['donor_name']);
    $phone_number = trim($_POST['phone_number']);
    $amount = (float)$_POST['amount'];
    $donation_date = $_POST['donation_date'];
    $project = trim($_POST['project']);
    $notes = trim($_POST['notes']);
    $created_by = $_SESSION['id'];

    if (empty($donor_name) || $amount <= 0 || empty($donation_date)) {
        $err = "نام اهداکننده، مبلغ (بیشتر از صفر) و تاریخ اهدا الزامی است.";
    } else {
        if (empty($donation_id)) { // Add new
            $sql = "INSERT INTO donations (donor_name, phone_number, amount, donation_date, project, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssdsssi", $donor_name, $phone_number, $amount, $donation_date, $project, $notes, $created_by);
            $success_msg = "کمک مالی جدید با موفقیت ثبت شد.";
        } else { // Update existing
            $sql = "UPDATE donations SET donor_name = ?, phone_number = ?, amount = ?, donation_date = ?, project = ?, notes = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssdsssi", $donor_name, $phone_number, $amount, $donation_date, $project, $notes, $donation_id);
            $success_msg = "اطلاعات کمک مالی با موفقیت ویرایش شد.";
        }

        if (mysqli_stmt_execute($stmt)) {
            // Success
        } else {
            $err = "خطا در ذخیره‌سازی اطلاعات.";
            $success_msg = "";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Delete Donation
if (isset($_GET['delete_id'])) {
    $donation_id_to_delete = $_GET['delete_id'];
    $sql = "DELETE FROM donations WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $donation_id_to_delete);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "کمک مالی با موفقیت حذف شد.";
    } else {
        $err = "خطا در حذف.";
    }
    mysqli_stmt_close($stmt);
}


// Fetch donations
$donations_sql = "SELECT * FROM donations ORDER BY donation_date DESC";
$donations_result = mysqli_query($link, $donations_sql);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2><i class="fas fa-hand-holding-heart"></i> مدیریت کمک‌های مالی (صله)</h2>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <!-- Add/Edit Donation Form -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h3>ثبت/ویرایش کمک مالی</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="donation_id" id="donation_id" value="">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="donor_name">نام اهداکننده</label>
                        <input type="text" name="donor_name" id="donor_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="phone_number">شماره تماس</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                     <div class="form-group">
                        <label for="amount">مبلغ (تومان)</label>
                        <input type="number" name="amount" id="amount" class="form-control" required min="0.01" step="0.01">
                    </div>
                </div>
            </div>
            <div class="row">
                 <div class="col-md-6">
                    <div class="form-group">
                        <label for="donation_date">تاریخ اهدا</label>
                        <input type="date" name="donation_date" id="donation_date" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="project">پروژه/مناسبت</label>
                        <input type="text" name="project" id="project" class="form-control">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="notes">یادداشت</label>
                <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" name="save_donation" class="btn btn-primary">ذخیره</button>
            </div>
        </form>
    </div>

    <!-- List of Donations -->
    <div class="table-container">
        <h3>لیست کمک‌های مالی ثبت شده</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>اهداکننده</th>
                        <th>مبلغ</th>
                        <th>تاریخ</th>
                        <th>پروژه</th>
                        <th>یادداشت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($donation = mysqli_fetch_assoc($donations_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                        <td><?php echo number_format($donation['amount'], 2); ?></td>
                        <td><?php echo to_persian_date($donation['donation_date']); ?></td>
                        <td><?php echo htmlspecialchars($donation['project']); ?></td>
                        <td><?php echo htmlspecialchars($donation['notes']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editDonation(<?php echo htmlspecialchars(json_encode($donation)); ?>)">ویرایش</button>
                            <a href="manage_donations.php?delete_id=<?php echo $donation['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editDonation(donation) {
    document.getElementById('donation_id').value = donation.id;
    document.getElementById('donor_name').value = donation.donor_name;
    document.getElementById('phone_number').value = donation.phone_number;
    document.getElementById('amount').value = donation.amount;
    document.getElementById('donation_date').value = donation.donation_date;
    document.getElementById('project').value = donation.project;
    document.getElementById('notes').value = donation.notes;
    window.scrollTo(0, 0); // Scroll to top to see the form
}
</script>

<?php
require_once "../includes/footer.php";
?>
