<?php
require_once '../includes/conn.php';
require_once '../includes/helpers.php';
require_once '../includes/PHPMailer/src/Exception.php';
require_once '../includes/PHPMailer/src/PHPMailer.php';
require_once '../includes/PHPMailer/src/SMTP.php';
require_once '../includes/emailFooter.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// SMTP helper (local, avoids function redeclaration)
function getRpSmtp($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key='$k'"));
    return $r ? $r['setting_value'] : '';
}

// Seed password_changed confirmation template if missing
$_pcBanner = 'https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';
$_pcSubj   = mysqli_real_escape_string($link, 'Your Simple2Success password has been changed');
$_pcBody   = mysqli_real_escape_string($link,
    '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
    . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
    . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
    . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
    . '<tr><td><img src="' . $_pcBanner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
    . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">'
    . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
    . '<p>Your Simple2Success password has been successfully reset.</p>'
    . '<p>Here are your updated login details:</p>'
    . '<table style="background:#f9f0ff;border-left:4px solid #cb2ebc;border-radius:4px;padding:14px 20px;margin:16px 0;width:100%;" cellpadding="6" cellspacing="0">'
    . '<tr><td style="font-weight:700;width:130px;">Username:</td><td>{{email}}</td></tr>'
    . '<tr><td style="font-weight:700;">New Password:</td><td>{{password}}</td></tr>'
    . '</table>'
    . '<div style="text-align:center;margin:28px 0;">'
    . '<a href="{{magic_link}}" style="background:#cb2ebc;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;">Log In Now &rarr;</a>'
    . '</div>'
    . '<p style="color:#888;font-size:13px;">This login link is valid for <strong>1 hour</strong>. You can also log in at any time using your email and new password: <a href="{{login_url}}" style="color:#cb2ebc;">{{login_url}}</a></p>'
    . '<p style="color:#ea5455;font-size:13px;"><strong>If you did not request this password change, please contact our support immediately.</strong></p>'
    . '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>'
    . '</td></tr>'
    . '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; ' . date('Y') . ' <a href="https://simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.</td></tr>'
    . '</table></td></tr></table></body></html>'
);
mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
    VALUES ('Password Changed Confirmation', 'password_changed', '$_pcSubj', '$_pcBody')");

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

        // Send confirmation email with new credentials + Magic Link (1h)
        $user = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT leadid, name FROM users WHERE email = '$email' LIMIT 1"));
        if ($user) {
            $displayName = $user['name'] ?: $resetRow['email'];
            $userId      = (int)$user['leadid'];
            $magicLink   = generateMagicLink($link, $userId, 'password_changed', 1);
            $loginUrl    = $baseurl . '/backoffice/login.php';

            $tpl = mysqli_fetch_assoc(mysqli_query($link,
                "SELECT subject, body FROM email_templates WHERE template_key = 'password_changed' LIMIT 1"));
            if ($tpl && !empty(trim(strip_tags($tpl['body'])))) {
                $pcBody = str_replace(
                    ['{{name}}', '{{email}}', '{{password}}', '{{login_url}}', '{{magic_link}}'],
                    [htmlspecialchars($displayName), htmlspecialchars($resetRow['email']),
                     htmlspecialchars($newPassword), $loginUrl, $magicLink],
                    $tpl['body']
                );
                $pcSubject = html_entity_decode($tpl['subject'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->CharSet    = 'UTF-8';
                    $mail->Host       = getRpSmtp($link, 'smtp_host');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getRpSmtp($link, 'smtp_user');
                    $mail->Password   = getRpSmtp($link, 'smtp_password');
                    $mail->Port       = (int)getRpSmtp($link, 'smtp_port');
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->isHTML(true);
                    $fromEmail = getRpSmtp($link, 'smtp_from_email') ?: 'noreply@simple2success.com';
                    $fromName  = getRpSmtp($link, 'smtp_from_name')  ?: 'Simple2Success';
                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($resetRow['email'], $displayName);
                    $mail->Subject = $pcSubject;
                    $mail->Body    = $pcBody . renderEmailFooter($link, 'password_changed', $userId);
                    $mail->send();
                } catch (MailException $e) {
                    // Silent — redirect always happens
                }
            }
        }

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
