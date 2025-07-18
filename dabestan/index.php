<?php
session_start();
require_once "includes/db_singleton.php";
require_once "includes/functions.php";

// if user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: " . ($_SESSION["is_admin"] ? "admin/index.php" : "user/index.php"));
    exit;
}

$username = $password = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
        $err = "لطفا نام کاربری و رمز عبور را وارد کنید.";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
    }

    if (empty($err)) {
        try {
            $pdo = get_db_connection();
            $sql = "SELECT id, username, password, is_admin FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                if (password_verify($password, $user['password'])) {
                    // Password is correct, so start a new session
                    session_regenerate_id();
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['id'];
                    $_SESSION["username"] = $user['username'];
                    $_SESSION["is_admin"] = $user['is_admin'];

                    header("location: " . ($user['is_admin'] ? "admin/index.php" : "user/index.php"));
                    exit;
                } else {
                    $err = "نام کاربری یا رمز عبور اشتباه است.";
                }
            } else {
                $err = "نام کاربری یا رمز عبور اشتباه است.";
            }
        } catch (PDOException $e) {
            $err = "خطایی رخ داد. لطفا بعدا تلاش کنید.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه دبستان</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <h2>ورود به سامانه</h2>
        <p>لطفا اطلاعات خود را برای ورود وارد کنید.</p>
        <?php if(!empty($err)): ?>
            <div class="alert alert-danger"><?php echo $err; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>نام کاربری</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>">
            </div>
            <div class="form-group">
                <label for="password">رمز عبور</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control">
                    <span class="toggle-password">
                        <i data-feather="eye"></i>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="ورود">
            </div>
        </form>
    </div>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
