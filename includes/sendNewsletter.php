<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function getSmtpSettingNL($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key='$k'"));
    return $r ? $r['setting_value'] : '';
}

/**
 * Send newsletter to target audience.
 *
 * @param mysqli $link       DB connection
 * @param string $subject    Email subject
 * @param string $body       HTML body (may contain {{name}}, {{email}})
 * @param string $target     'all' | 'members' | 'leads'
 * @return array ['sent' => int, 'errors' => array]
 */
function sendNewsletter($link, $subject, $body, $target) {

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS newsletters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(255) NOT NULL,
        body LONGTEXT NOT NULL,
        target ENUM('all','members','leads') NOT NULL,
        total_sent INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL
    )");

    // Build recipient query
    if ($target === 'members') {
        $sql = "SELECT name, email FROM users WHERE username IS NOT NULL AND username != '' ORDER BY leadid ASC";
    } elseif ($target === 'leads') {
        $sql = "SELECT name, email FROM users WHERE (username IS NULL OR username = '') ORDER BY leadid ASC";
    } else {
        $sql = "SELECT name, email FROM users ORDER BY leadid ASC";
    }
    $recipients = mysqli_query($link, $sql);
    if (!$recipients) return ['sent' => 0, 'errors' => ['DB query failed: ' . mysqli_error($link)]];

    // SMTP config
    $smtpHost  = getSmtpSettingNL($link, 'smtp_host');
    $smtpUser  = getSmtpSettingNL($link, 'smtp_user');
    $smtpPass  = getSmtpSettingNL($link, 'smtp_password');
    $smtpPort  = (int) getSmtpSettingNL($link, 'smtp_port');
    $fromEmail = getSmtpSettingNL($link, 'smtp_from_email') ?: 'info@simple2success.com';
    $fromName  = getSmtpSettingNL($link, 'smtp_from_name')  ?: 'Simple2Success';

    $sent   = 0;
    $errors = [];

    while ($rec = mysqli_fetch_assoc($recipients)) {
        $toEmail = $rec['email'];
        $toName  = $rec['name'] ?: $toEmail;

        $personalBody    = str_replace(['{{name}}', '{{email}}'], [htmlspecialchars($toName), htmlspecialchars($toEmail)], $body);
        $personalSubject = str_replace(['{{name}}', '{{email}}'], [htmlspecialchars($toName), htmlspecialchars($toEmail)], $subject);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet    = 'UTF-8';
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->Port       = $smtpPort;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->isHTML(true);
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = html_entity_decode($personalSubject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $mail->Body    = $personalBody;
            $mail->send();
            $sent++;
        } catch (Exception $e) {
            $errors[] = "$toEmail: {$mail->ErrorInfo}";
        }
    }

    // Log to newsletters table
    $esc_subject = mysqli_real_escape_string($link, $subject);
    $esc_body    = mysqli_real_escape_string($link, $body);
    $esc_target  = mysqli_real_escape_string($link, $target);
    mysqli_query($link, "INSERT INTO newsletters (subject, body, target, total_sent, sent_at)
        VALUES ('$esc_subject', '$esc_body', '$esc_target', $sent, NOW())");

    return ['sent' => $sent, 'errors' => $errors];
}
