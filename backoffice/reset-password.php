<?php
require_once '../includes/conn.php';

// Tabelle sicherstellen
mysqli_query($link, "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(250) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_token (token)
)");

$token    = trim($_GET['token'] ?? '');
$tokenEsc = mysqli_real_escape_string($link, $token);
$error    = '';
$success  = false;
$resetRow = null;

if ($token) {
    $resetRow = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT * FROM password_resets WHERE token = '$tokenEsc' AND expires_at > NOW() LIMIT 1"
    ));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $resetRow) {
    $newPassword     = $_POST["password"] ?? '';
    $confirmPassword = $_POST["password_confirm"] ?? '';

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hash  = mysqli_real_escape_string($link, password_hash($newPassword, PASSWORD_DEFAULT));
        $email = mysqli_real_escape_string($link, $resetRow["email"]);

        mysqli_query($link, "UPDATE users SET password = '$hash' WHERE email = '$email'");
        mysqli_query($link, "DELETE FROM password_resets WHERE token = '$tokenEsc'");

        header("Location: login.php?reset=success");
        exit();
    }
}
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Reset Password — Simple2Success</title>
    <link rel="shortcut icon" type="image/x-icon" href="app-assets/img/ico/favicon.ico">
    <link href="app-assets/css/fonts/font-style.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="app-assets/fonts/feather/style.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/fonts/simple-line-icons/style.css">
    <link rel="stylesheet" type="text/css" href="app-assets/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/perfect-scrollbar.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/switchery.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/themes/layout-dark.css">
    <link rel="stylesheet" href="app-assets/css/plugins/switchery.css">
    <link rel="stylesheet" href="app-assets/css/pages/authentication.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body class="vertical-layout vertical-menu 1-column auth-page navbar-static layout-dark blank-page" data-menu="vertical-menu" data-col="1-column">
    <div class="wrapper">
        <div class="main-panel">
            <div class="main-content">
                <div class="content-overlay"></div>
                <div class="content-wrapper">
                    <section id="reset-password" class="auth-height">
                        <div class="row full-height-vh m-0 d-flex align-items-center justify-content-center">
                            <div class="col-md-7 col-12">
                                <div class="card overflow-hidden">
                                    <div class="card-content">
                                        <div class="card-body auth-img">
                                            <div class="row m-0">
                                                <div class="col-lg-6 d-none d-lg-flex justify-content-center align-items-center text-center auth-img-bg py-2">
                                                    <img src="app-assets/img/gallery/forgot.png" alt="" class="img-fluid" width="260" height="230">
                                                </div>
                                                <div class="col-lg-6 col-md-12 px-4 py-3">
                                                    <h4 class="mb-2 card-title">Set New Password</h4>

                                                    <?php if (!$token || !$resetRow): ?>
                                                        <div class="alert alert-danger">
                                                            This reset link is invalid or has expired.<br>
                                                            <a href="forgot-password.php">Request a new link</a>
                                                        </div>
                                                    <?php else: ?>
                                                        <?php if ($error): ?>
                                                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                                        <?php endif; ?>

                                                        <p class="card-text mb-3">Choose a new password for <strong><?= htmlspecialchars($resetRow['email']) ?></strong>.</p>

                                                        <form method="POST" action="reset-password.php?token=<?= urlencode($token) ?>">
                                                            <input type="password" name="password" class="form-control mb-2" placeholder="New password (min. 6 characters)" required minlength="6" autofocus>
                                                            <input type="password" name="password_confirm" class="form-control mb-3" placeholder="Confirm new password" required>
                                                            <div class="d-flex flex-sm-row flex-column justify-content-between">
                                                                <a href="login.php" class="btn bg-light-primary mb-2 mb-sm-0">Back To Login</a>
                                                                <button type="submit" class="btn btn-primary ml-sm-1">Set Password</button>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <script src="app-assets/vendors/js/vendors.min.js"></script>
    <script src="app-assets/js/core/app-menu.js"></script>
    <script src="app-assets/js/core/app.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
