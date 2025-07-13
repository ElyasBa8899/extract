<?php
session_start();
require_once "../includes/db.php";

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]){
    header("location: ../index.php");
    exit;
}

$username = $password = $first_name = $last_name = "";
$is_admin = 0;
$err = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"])) || empty(trim($_POST["first_name"]))) {
        $err = "لطفا فیلدهای ستاره‌دار را پر کنید.";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
        $first_name = trim($_POST["first_name"]);
        $last_name = trim($_POST["last_name"]); // Last name is optional
        $is_admin = 0; // is_admin is always 0 for new users
    }

    // Check if username is already taken
    if(empty($err)){
        $sql = "SELECT id FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $err = "این نام کاربری قبلا انتخاب شده است.";
                }
            } else {
                $err = "خطایی رخ داد. لطفا دوباره تلاش کنید.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // If no errors, insert into database
    if (empty($err)) {
        $sql = "INSERT INTO users (first_name, last_name, username, password, is_admin) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssi", $param_first_name, $param_last_name, $param_username, $param_password, $param_is_admin);

            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
            $param_is_admin = $is_admin;

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "کاربر جدید با موفقیت ایجاد شد.";
            } else {
                $err = "خطا در ایجاد کاربر جدید.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ایجاد کاربر جدید</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-wrapper" style="margin-top: 50px;">
        <h2>ایجاد کاربر جدید</h2>
        <p>اطلاعات کاربر جدید را وارد کنید.</p>
        <?php
        if(!empty($err)){
            echo '<div class="alert alert-danger">' . $err . '</div>';
        }
        if(!empty($success_msg)){
            echo '<div class="alert alert-success">' . $success_msg . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>نام</label>
                <input type="text" name="first_name" class="form-control">
            </div>
            <div class="form-group">
                <label>نام خانوادگی</label>
                <input type="text" name="last_name" class="form-control">
            </div>
            <div class="form-group">
                <label>نام کاربری</label>
                <input type="text" name="username" class="form-control">
            </div>
            <div class="form-group">
                <label>رمز عبور</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="form-group">
                <input type="checkbox" name="is_admin" id="is_admin">
                <label for="is_admin"> این کاربر ادمین است</label>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="ایجاد کاربر">
                <a href="index.php" class="btn btn-secondary">بازگشت به پنل</a>
            </div>
        </form>
    </div>
</body>
</html>
