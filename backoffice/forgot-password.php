<?php
require_once '../includes/conn.php';
require_once '../includes/BrevoMailer.php';
require_once '../includes/emailFooter.php';

// Tabelle für Reset-Tokens anlegen (falls nicht vorhanden)
mysqli_query($link, "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(250) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_token (token)
)");

// Password-Reset-Template seeden falls nicht vorhanden
$_pwrBanner = 'https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';
$_pwrSubj   = mysqli_real_escape_string($link, 'Reset Your Simple2Success Password');
$_pwrBody   = mysqli_real_escape_string($link,
    '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
    . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
    . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
    . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
    . '<tr><td><img src="' . $_pwrBanner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
    . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">'
    . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
    . '<p>You requested a password reset for your Simple2Success account.</p>'
    . '<p>Click the button below to set a new password. This link is valid for <strong>1 hour</strong>.</p>'
    . '<div style="text-align:center;margin:28px 0;">'
    . '<a href="{{reset_link}}" style="background:#cb2ebc;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;">Reset My Password &rarr;</a>'
    . '</div>'
    . '<p style="color:#888;font-size:13px;">If you did not request a password reset, you can safely ignore this email — your password will not change.</p>'
    . '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>'
    . '</td></tr>'
    . '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; ' . date('Y') . ' <a href="https://simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.</td></tr>'
    . '</table></td></tr></table></body></html>'
);
mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
    VALUES ('Password Reset', 'password_reset', '$_pwrSubj', '$_pwrBody')");

$sent  = isset($_GET['sent']);
$error = $_GET['error'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"])) {
    $email = mysqli_real_escape_string($link, trim($_POST["email"]));

    $user = mysqli_fetch_assoc(mysqli_query($link, "SELECT leadid, name FROM users WHERE email = '$email' LIMIT 1"));

    if ($user) {
        // Alte Tokens für diese E-Mail löschen
        mysqli_query($link, "DELETE FROM password_resets WHERE email = '$email'");

        // Neuen Token generieren
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date("Y-m-d H:i:s", time() + 3600); // 1 Stunde gültig
        $tokenEsc  = mysqli_real_escape_string($link, $token);
        mysqli_query($link, "INSERT INTO password_resets (email, token, expires_at) VALUES ('$email', '$tokenEsc', '$expiresAt')");

        $resetLink = $baseurl . '/backoffice/reset-password.php?token=' . urlencode($token);
        $displayName = $user['name'] ?: $email;

        $tpl = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT subject, body FROM email_templates WHERE template_key = 'password_reset' LIMIT 1"));
        $fpSubject = $tpl['subject'] ?? 'Reset Your Simple2Success Password';
        $fpBody    = $tpl['body'] ?? '';
        $fpBody = str_replace(
            ['{{name}}', '{{reset_link}}'],
            [htmlspecialchars($displayName), $resetLink],
            $fpBody);
        if (!empty(trim(strip_tags($fpBody)))) {
            try {
                $mailer = new BrevoMailer($link);
                $mailer->sendEmail($email, $displayName,
                    html_entity_decode($fpSubject, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    $fpBody . renderEmailFooter($link, 'password_reset', (int)$user['leadid']),
                    ['transactional', 'password-reset'],
                    ['user_id' => (int)$user['leadid'], 'email_type' => 'password_reset']);
            } catch (\Exception $e) {
                error_log("forgot-password [$email]: " . $e->getMessage());
            }
        }

        header("Location: forgot-password.php?sent=1");
        exit();
    } else {
        header("Location: forgot-password.php?error=not_found");
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
    <title>Forgot Password — Simple2Success</title>
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
                    <section id="forgot-password" class="auth-height">
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
                                                    <h4 class="mb-2 card-title">Recover Password</h4>

                                                    <?php if ($sent): ?>
                                                        <div class="alert alert-success">
                                                            <strong>Check your inbox!</strong><br>
                                                            If this email is registered, we've sent you a password reset link. It's valid for 1 hour.
                                                        </div>
                                                        <a href="login.php" class="btn btn-primary btn-block mt-2">Back to Login</a>
                                                    <?php else: ?>
                                                        <p class="card-text mb-3">Enter your email address and we'll send you a link to reset your password.</p>

                                                        <?php if ($error === 'not_found'): ?>
                                                            <div class="alert alert-danger">No account found with that email address.</div>
                                                        <?php endif; ?>

                                                        <form method="POST" action="forgot-password.php">
                                                            <input type="email" name="email" class="form-control mb-3" placeholder="Your email address" required autofocus>
                                                            <div class="d-flex flex-sm-row flex-column justify-content-between">
                                                                <a href="login.php" class="btn bg-light-primary mb-2 mb-sm-0">Back To Login</a>
                                                                <button type="submit" class="btn btn-primary ml-sm-1">Send Reset Link</button>
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
