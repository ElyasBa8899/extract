<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/config_path.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/db.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}
if (!has_permission('manage_tasks')) {
    echo "شما اجازه دسترسی به این صفحه را ندارید.";
    exit;
}

$user_id = $_SESSION['id'];

// TODO: Implement logic to fetch tasks assigned to the user or their departments

require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت وظایف</h2>
    <p>در این بخش وظایف محول شده به شما یا بخش شما نمایش داده می‌شود.</p>

    <a href="create_task.php" class="btn btn-primary mb-3">ایجاد وظیفه جدید</a>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>عنوان وظیفه</th>
                    <th>اولویت</th>
                    <th>وضعیت</th>
                    <th>ددلاین</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="text-center">هنوز وظیفه‌ای برای نمایش وجود ندارد. (در حال ساخت)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dabestan/includes/footer.php"; ?>
