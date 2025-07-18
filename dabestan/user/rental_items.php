<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";

if (!isset($_SESSION['loggedin'])) {
    header("Location: ../index.php");
    exit();
}

$pdo = get_db_connection();
$user_id = $_SESSION['id'];

// Fetch items rented by the current user that have not been returned yet
$rentals = $pdo->prepare("
    SELECT r.quantity, r.rental_date, r.notes, i.name as item_name
    FROM item_rentals r
    JOIN inventory_items i ON r.item_id = i.id
    WHERE r.user_id = ? AND r.return_date IS NULL
    ORDER BY r.rental_date DESC
");
$rentals->execute([$user_id]);
$rented_items = $rentals->fetchAll();

?>
<div class="page-content">
    <div class="container-fluid">
        <h2>اقلام کرایه گرفته شده</h2>
        <p>لیست اقلامی که از انبار کرایه گرفته‌اید و هنوز به انبار تحویل نداده‌اید.</p>

        <div class="card">
            <div class="card-header">
                <h5>لیست اقلام امانتی</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>نام کالا</th>
                                <th>تعداد</th>
                                <th>تاریخ کرایه</th>
                                <th>یادداشت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rented_items)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">در حال حاضر هیچ کالایی به امانت نزد شما نیست.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($rented_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo to_persian_date($item['rental_date']); ?></td>
                                    <td><?php echo htmlspecialchars($item['notes'] ?? '---'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once "../includes/footer.php"; ?>
