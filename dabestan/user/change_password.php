<?php
session_start();
require_once "../includes/db_singleton.php";
$link = get_db_connection();
require_once "../includes/access_control.php";
require_once "../includes/header.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php");
    exit;
}

$current_password = $new_password = $confirm_password = "";
$current_password_err = $new_password_err = $confirm_password_err = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate current password
    if (empty(trim($_POST["current_password"]))) {
        $current_password_err = "لطفا رمز عبور فعلی خود را وارد کنید.";
    } else {
        $current_password = trim($_POST["current_password"]);
    }

    // Validate new password
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "لطفا رمز عبور جدید را وارد کنید.";
    } elseif (strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "رمز عبور باید حداقل ۶ کاراکتر باشد.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "لطفا رمز عبور جدید را تکرار کنید.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "رمز عبور جدید و تکرار آن یکسان نیستند.";
        }
    }

    // Check input errors before updating the database
    if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Prepare a select statement
        $sql = "SELECT password FROM users WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($current_password, $hashed_password)) {
                            // Prepare an update statement
                            $sql = "UPDATE users SET password = ? WHERE id = ?";

                            if ($stmt_update = mysqli_prepare($link, $sql)) {
                                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                                mysqli_stmt_bind_param($stmt_update, "si", $param_password, $_SESSION["id"]);

                                if (mysqli_stmt_execute($stmt_update)) {
                                    $success_message = "رمز عبور شما با موفقیت تغییر کرد.";
                                } else {
                                    echo "مشکلی پیش آمد. لطفا دوباره تلاش کنید.";
                                }
                                mysqli_stmt_close($stmt_update);
                            }
                        } else {
                            $current_password_err = "رمز عبور فعلی شما صحیح نیست.";
                        }
                    }
                }
            } else {
                echo "مشکلی پیش آمد. لطفا دوباره تلاش کنید.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="page-content">
    <div class="container-fluid">
        <h2>تغییر رمز عبور</h2>
        <p>در این بخش می‌توانید رمز عبور خود را تغییر دهید.</p>

        <?php
        if (!empty($success_message)) {
            echo '<div class="alert alert-success">' . $success_message . '</div>';
        }
        ?>

        <div class="card">
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>رمز عبور فعلی</label>
                        <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>رمز عبور جدید</label>
                        <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>تکرار رمز عبور جدید</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="تغییر رمز عبور">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
