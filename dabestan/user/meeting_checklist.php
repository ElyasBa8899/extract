<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

if (!isset($_GET['meeting_id']) || empty($_GET['meeting_id'])) {
    header("location: manage_meetings.php");
    exit;
}

$meeting_id = $_GET['meeting_id'];
$err = $success_msg = "";

// Fetch meeting details
$meeting = null;
$sql_meeting = "SELECT title FROM service_meetings WHERE id = ?";
if($stmt_meeting = mysqli_prepare($link, $sql_meeting)){
    mysqli_stmt_bind_param($stmt_meeting, "i", $meeting_id);
    mysqli_stmt_execute($stmt_meeting);
    $result_meeting = mysqli_stmt_get_result($stmt_meeting);
    $meeting = mysqli_fetch_assoc($result_meeting);
    mysqli_stmt_close($stmt_meeting);
}

if(!$meeting){
    echo "جلسه یافت نشد.";
    exit;
}

// Default checklist items
$default_checklist = ["هماهنگی مکان", "هماهنگی استاد", "دعوت تلگرامی", "دعوت تلفنی"];

// Check if checklist exists, if not, create it
$sql_check = "SELECT COUNT(*) as count FROM meeting_checklist_items WHERE meeting_id = ?";
if($stmt_check = mysqli_prepare($link, $sql_check)){
    mysqli_stmt_bind_param($stmt_check, "i", $meeting_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $row = mysqli_fetch_assoc($result_check);
    if($row['count'] == 0){
        // Create default checklist
        $sql_insert = "INSERT INTO meeting_checklist_items (meeting_id, item_name) VALUES (?, ?)";
        if($stmt_insert = mysqli_prepare($link, $sql_insert)){
            foreach($default_checklist as $item){
                mysqli_stmt_bind_param($stmt_insert, "is", $meeting_id, $item);
                mysqli_stmt_execute($stmt_insert);
            }
            mysqli_stmt_close($stmt_insert);
        }
    }
    mysqli_stmt_close($stmt_check);
}

// Handle Checklist Update POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_checklist'])) {
    $checklist_items = $_POST['checklist_items'] ?? [];

    // First, set all items for this meeting to not completed
    $sql_reset = "UPDATE meeting_checklist_items SET is_completed = 0 WHERE meeting_id = ?";
    if($stmt_reset = mysqli_prepare($link, $sql_reset)){
        mysqli_stmt_bind_param($stmt_reset, "i", $meeting_id);
        mysqli_stmt_execute($stmt_reset);
        mysqli_stmt_close($stmt_reset);
    }

    // Then, update the checked items to completed
    if(!empty($checklist_items)){
        $sql_update = "UPDATE meeting_checklist_items SET is_completed = 1, completed_by = ?, completed_at = NOW() WHERE meeting_id = ? AND id = ?";
        if($stmt_update = mysqli_prepare($link, $sql_update)){
            foreach($checklist_items as $item_id){
                mysqli_stmt_bind_param($stmt_update, "iii", $_SESSION['id'], $meeting_id, $item_id);
                mysqli_stmt_execute($stmt_update);
            }
            mysqli_stmt_close($stmt_update);
        }
    }
    $success_msg = "چک‌لیست با موفقیت به‌روزرسانی شد.";
}

// Fetch checklist items for display
$checklist = [];
$sql_fetch_list = "SELECT id, item_name, is_completed FROM meeting_checklist_items WHERE meeting_id = ?";
if($stmt_fetch = mysqli_prepare($link, $sql_fetch_list)){
    mysqli_stmt_bind_param($stmt_fetch, "i", $meeting_id);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    $checklist = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_fetch);
}


require_once "../includes/header.php";
?>

<div class="page-content">
    <a href="manage_meetings.php" class="btn btn-secondary" style="margin-bottom: 20px;">&larr; بازگشت به مدیریت جلسات</a>
    <h2>چک‌لیست جلسه: <?php echo htmlspecialchars($meeting['title']); ?></h2>
    <p>کارهای انجام شده قبل از شروع جلسه را مشخص کنید.</p>

    <?php
    if(!empty($err)){ echo '<div class="alert alert-danger">' . $err . '</div>'; }
    if(!empty($success_msg)){ echo '<div class="alert alert-success">' . $success_msg . '</div>'; }
    ?>

    <div class="form-container">
        <form action="meeting_checklist.php?meeting_id=<?php echo $meeting_id; ?>" method="post">
            <h3>لیست کارها</h3>
            <?php if(empty($checklist)): ?>
                <p>آیتمی برای این چک‌لیست یافت نشد.</p>
            <?php else: ?>
                <?php foreach($checklist as $item): ?>
                    <div class="checkbox-group">
                        <input type="checkbox" name="checklist_items[]" value="<?php echo $item['id']; ?>" id="item_<?php echo $item['id']; ?>" <?php if($item['is_completed']) echo 'checked'; ?>>
                        <label for="item_<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></label>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="form-group" style="margin-top: 20px;">
                <input type="submit" name="update_checklist" class="btn btn-primary" value="ذخیره تغییرات">
            </div>
        </form>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
