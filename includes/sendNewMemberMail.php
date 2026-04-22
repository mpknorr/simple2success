<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function getSmtpSettingMember($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key = '$k'"));
    return $r ? $r['setting_value'] : '';
}

/**
 * Send "new member" notification to the sponsor when a user completes Step 2.
 *
 * @param  mysqli $link
 * @param  string $root  The new member's username (as set in welcome.php)
 * @return true|string   true on success, error string on failure
 */
function sendNewMemberMail($link, $root) {
    $safeRoot = mysqli_real_escape_string($link, $root);

    // Get new member's email + sponsor's email/name
    $row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT u.email AS member_email, r.email AS sponsor_email, r.name AS sponsor_name
         FROM users u
         LEFT JOIN users r ON u.referer = r.leadid
         WHERE u.username = '$safeRoot'
         LIMIT 1"
    ));

    if (!$row || empty($row['sponsor_email'])) {
        return 'sendNewMemberMail: sponsor not found for username ' . $root;
    }

    $memberEmail  = $row['member_email'];
    $sponsorEmail = $row['sponsor_email'];
    $sponsorName  = $row['sponsor_name'] ?: $sponsorEmail;

    // Load template
    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'new_member' LIMIT 1"
    ));
    if (!$tpl) {
        return 'sendNewMemberMail: new_member template not found in email_templates';
    }

    $subject = str_replace(
        ['{{name}}', '{{member_email}}'],
        [htmlspecialchars($sponsorName), htmlspecialchars($memberEmail)],
        $tpl['subject']
    );
    $body = str_replace(
        ['{{name}}', '{{member_email}}'],
        [htmlspecialchars($sponsorName), htmlspecialchars($memberEmail)],
        $tpl['body']
    );

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->CharSet    = 'UTF-8';
        $mail->Host       = getSmtpSettingMember($link, 'smtp_host');
        $mail->SMTPAuth   = true;
        $mail->Username   = getSmtpSettingMember($link, 'smtp_user');
        $mail->Password   = getSmtpSettingMember($link, 'smtp_password');
        $mail->Port       = (int) getSmtpSettingMember($link, 'smtp_port');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->isHTML(true);
        $fromEmail = getSmtpSettingMember($link, 'smtp_from_email');
        $fromName  = getSmtpSettingMember($link, 'smtp_from_name');
        $mail->setFrom($fromEmail ?: 'info@simple2success.com', $fromName ?: 'Simple2Success');
        $mail->addAddress($sponsorEmail, $sponsorName);
        $mail->Subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "sendNewMemberMail error: {$mail->ErrorInfo}";
    }
}
