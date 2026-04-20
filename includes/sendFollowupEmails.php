<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function getSmtpSettingFU($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key='$k'"));
    return $r ? $r['setting_value'] : '';
}

/**
 * Send any follow-up emails that are due right now.
 * Call this from a cron job (e.g. every 15 minutes or hourly).
 *
 * @param  mysqli $link
 * @return array  ['sent' => int, 'errors' => array]
 */
function sendFollowupEmails($link) {

    // Ensure tables exist
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_sequences (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        target       ENUM('lead','member') NOT NULL DEFAULT 'lead',
        day_offset   INT NOT NULL DEFAULT 1,
        subject      VARCHAR(255) NOT NULL,
        body         LONGTEXT NOT NULL,
        is_active    TINYINT(1) NOT NULL DEFAULT 1,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_log (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        user_id      INT NOT NULL,
        sequence_id  INT NOT NULL,
        sent_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_seq (user_id, sequence_id)
    )");

    // Fetch all active sequences
    $seqs = mysqli_query($link, "SELECT * FROM followup_sequences WHERE is_active = 1 ORDER BY target, day_offset ASC");
    if (!$seqs || mysqli_num_rows($seqs) === 0) {
        return ['sent' => 0, 'errors' => []];
    }

    // SMTP config
    $smtpHost  = getSmtpSettingFU($link, 'smtp_host');
    $smtpUser  = getSmtpSettingFU($link, 'smtp_user');
    $smtpPass  = getSmtpSettingFU($link, 'smtp_password');
    $smtpPort  = (int) getSmtpSettingFU($link, 'smtp_port');
    $fromEmail = getSmtpSettingFU($link, 'smtp_from_email') ?: 'info@simple2success.com';
    $fromName  = getSmtpSettingFU($link, 'smtp_from_name')  ?: 'Simple2Success';

    $sent   = 0;
    $errors = [];

    while ($seq = mysqli_fetch_assoc($seqs)) {
        $seq_id     = (int)$seq['id'];
        $day_offset = (int)$seq['day_offset'];
        $target     = $seq['target'];
        $subject    = $seq['subject'];
        $body       = $seq['body'];

        // Determine eligible users
        // lead   = username IS NULL OR username = '' (have NOT completed Step 2)
        // member = username IS NOT NULL AND username != ''  (completed Step 2)
        if ($target === 'lead') {
            $filter = "(username IS NULL OR username = '')";
        } else {
            $filter = "(username IS NOT NULL AND username != '')";
        }

        // Users whose account is old enough for this day_offset
        // AND who haven't received this sequence yet
        $sql = "SELECT leadid, name, email, timestamp FROM users
                WHERE $filter
                AND TIMESTAMPDIFF(DAY, timestamp, NOW()) >= $day_offset
                AND leadid NOT IN (
                    SELECT user_id FROM followup_log WHERE sequence_id = $seq_id
                )";
        $recipients = mysqli_query($link, $sql);
        if (!$recipients) {
            $errors[] = "DB error for seq $seq_id: " . mysqli_error($link);
            continue;
        }

        while ($rec = mysqli_fetch_assoc($recipients)) {
            $uid     = (int)$rec['leadid'];
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
                $mail->Subject = $personalSubject;
                $mail->Body    = $personalBody;
                $mail->send();
                // Mark as sent
                mysqli_query($link, "INSERT IGNORE INTO followup_log (user_id, sequence_id) VALUES ($uid, $seq_id)");
                $sent++;
            } catch (Exception $e) {
                $errors[] = "$toEmail (seq $seq_id): {$mail->ErrorInfo}";
            }
        }
    }

    return ['sent' => $sent, 'errors' => $errors];
}
