<?php
require_once __DIR__ . '/BrevoMailer.php';
require_once __DIR__ . '/emailFooter.php';

function getSmtpSettingNL($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key='$k'"));
    return $r ? $r['setting_value'] : '';
}

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

    if ($target === 'members') {
        $sql = "SELECT leadid, name, email FROM users WHERE username IS NOT NULL AND username != '' ORDER BY leadid ASC";
    } elseif ($target === 'leads') {
        $sql = "SELECT leadid, name, email FROM users WHERE (username IS NULL OR username = '') ORDER BY leadid ASC";
    } else {
        $sql = "SELECT leadid, name, email FROM users ORDER BY leadid ASC";
    }
    $recipients = mysqli_query($link, $sql);
    if (!$recipients) return ['sent' => 0, 'errors' => ['DB query failed: ' . mysqli_error($link)]];

    $sent = 0; $errors = [];

    try {
        $mailer = new BrevoMailer($link);
    } catch (\Exception $e) {
        return ['sent' => 0, 'errors' => ['BrevoMailer init: ' . $e->getMessage()]];
    }

    while ($rec = mysqli_fetch_assoc($recipients)) {
        $uid     = (int)$rec['leadid'];
        $toEmail = $rec['email'];
        $toName  = $rec['name'] ?: $toEmail;

        $personalBody    = str_replace(['{{name}}', '{{email}}'], [htmlspecialchars($toName), htmlspecialchars($toEmail)], $body)
                         . renderEmailFooter($link, 'newsletter', $uid);
        $personalSubject = html_entity_decode(
            str_replace(['{{name}}', '{{email}}'], [htmlspecialchars($toName), htmlspecialchars($toEmail)], $subject),
            ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $mailer->sendEmail($toEmail, $toName, $personalSubject, $personalBody,
                ['newsletter', $target],
                ['user_id' => $uid, 'email_type' => 'newsletter', 'target' => $target]);
            $sent++;
        } catch (\Exception $e) {
            error_log("sendNewsletter [$toEmail]: " . $e->getMessage());
            $errors[] = "$toEmail: " . $e->getMessage();
        }
    }

    $esc_subject = mysqli_real_escape_string($link, $subject);
    $esc_body    = mysqli_real_escape_string($link, $body);
    $esc_target  = mysqli_real_escape_string($link, $target);
    mysqli_query($link, "INSERT INTO newsletters (subject, body, target, total_sent, sent_at)
        VALUES ('$esc_subject', '$esc_body', '$esc_target', $sent, NOW())");

    return ['sent' => $sent, 'errors' => $errors];
}
