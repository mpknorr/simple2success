<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/emailFooter.php';

function getSmtpSettingWelcome($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key = '$k'"));
    return $r ? $r['setting_value'] : '';
}

function sendWelcomeMail($link, $toEmail, $toName, $plainPassword, $loginUrl) {
    // Template aus DB laden
    $tpl = mysqli_fetch_assoc(mysqli_query($link, "SELECT subject, body FROM email_templates WHERE template_key = 'welcome_user' LIMIT 1"));
    if (!$tpl) {
        return 'Welcome template not found in DB';
    }

    $displayName = $toName ?: $toEmail;
    $subject = str_replace(
        ['{{name}}', '{{email}}', '{{password}}', '{{login_url}}'],
        [htmlspecialchars($displayName), htmlspecialchars($toEmail), htmlspecialchars($plainPassword), $loginUrl],
        $tpl['subject']
    );
    $body = str_replace(
        ['{{name}}', '{{email}}', '{{password}}', '{{login_url}}'],
        [htmlspecialchars($displayName), htmlspecialchars($toEmail), htmlspecialchars($plainPassword), $loginUrl],
        $tpl['body']
    );

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->CharSet  = 'UTF-8';
        $mail->Host     = getSmtpSettingWelcome($link, 'smtp_host');
        $mail->SMTPAuth = true;
        $mail->Username = getSmtpSettingWelcome($link, 'smtp_user');
        $mail->Password = getSmtpSettingWelcome($link, 'smtp_password');
        $mail->Port     = (int) getSmtpSettingWelcome($link, 'smtp_port');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->isHTML(true);

        $fromEmail = getSmtpSettingWelcome($link, 'smtp_from_email');
        $fromName  = getSmtpSettingWelcome($link, 'smtp_from_name');
        $mail->setFrom($fromEmail ?: 'info@simple2success.com', $fromName ?: 'Simple2Success');
        $mail->addAddress($toEmail, $displayName);
        $mail->Subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $mail->Body    = $body . renderEmailFooter($link, 'welcome_user', 0);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Welcome mail error: {$mail->ErrorInfo}";
    }
}
