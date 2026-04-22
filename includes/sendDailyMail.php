<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function getSmtpSettingDL($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key='$k'"));
    return $r ? $r['setting_value'] : '';
}

/**
 * Send daily lead notification emails to all members who received new leads today.
 * Call this from a cron job (e.g. once per day at 20:00).
 *
 * @param  mysqli $link
 * @return array  ['sent' => int, 'errors' => array]
 */
function sendDailyLeadsNotifications($link) {
    // Load template from DB
    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'daily_leads' LIMIT 1"
    ));
    if (!$tpl) {
        return ['sent' => 0, 'errors' => ['daily_leads template not found in email_templates table.']];
    }

    $tplSubject = $tpl['subject'];
    $tplBody    = $tpl['body'];

    // SMTP config
    $smtpHost  = getSmtpSettingDL($link, 'smtp_host');
    $smtpUser  = getSmtpSettingDL($link, 'smtp_user');
    $smtpPass  = getSmtpSettingDL($link, 'smtp_password');
    $smtpPort  = (int) getSmtpSettingDL($link, 'smtp_port');
    $fromEmail = getSmtpSettingDL($link, 'smtp_from_email') ?: 'info@simple2success.com';
    $fromName  = getSmtpSettingDL($link, 'smtp_from_name')  ?: 'Simple2Success';

    $sent   = 0;
    $errors = [];

    // Find all members who got at least one new lead today
    $members = mysqli_query($link,
        "SELECT leadid, name, email FROM users
         WHERE leadid IN (
             SELECT DISTINCT referer FROM users
             WHERE referer IS NOT NULL AND referer != 0 AND DATE(timestamp) = CURDATE()
         )
         AND username IS NOT NULL AND username != ''"
    );
    if (!$members) {
        return ['sent' => 0, 'errors' => ['DB query failed: ' . mysqli_error($link)]];
    }

    while ($member = mysqli_fetch_assoc($members)) {
        $memberId    = (int)$member['leadid'];
        $memberEmail = $member['email'];
        $memberName  = $member['name'] ?: $memberEmail;

        // Collect today's leads for this member
        $leadsRes = mysqli_query($link,
            "SELECT email FROM users
             WHERE referer = $memberId AND DATE(timestamp) = CURDATE()
             ORDER BY timestamp ASC"
        );
        if (!$leadsRes || mysqli_num_rows($leadsRes) === 0) continue;

        $leadEmails = [];
        while ($lr = mysqli_fetch_assoc($leadsRes)) {
            $leadEmails[] = htmlspecialchars($lr['email']);
        }

        // Build lead list HTML
        if (count($leadEmails) === 1) {
            $leadsHtml = $leadEmails[0];
        } else {
            $leadsHtml = '<ul style="padding-left:20px;margin:8px 0;">';
            foreach ($leadEmails as $le) {
                $leadsHtml .= '<li>' . $le . '</li>';
            }
            $leadsHtml .= '</ul>';
        }

        // Personalise subject and body
        $personalSubject = str_replace(
            ['{{name}}', '{{email}}', '{{leads}}'],
            [htmlspecialchars($memberName), htmlspecialchars($memberEmail), $leadsHtml],
            $tplSubject
        );
        $personalBody = str_replace(
            ['{{name}}', '{{email}}', '{{leads}}'],
            [htmlspecialchars($memberName), htmlspecialchars($memberEmail), $leadsHtml],
            $tplBody
        );

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
            $mail->addAddress($memberEmail, $memberName);
            $mail->Subject = html_entity_decode($personalSubject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $mail->Body    = $personalBody;
            $mail->send();
            $sent++;
        } catch (Exception $e) {
            $errors[] = "$memberEmail: {$mail->ErrorInfo}";
        }
    }

    return ['sent' => $sent, 'errors' => $errors];
}
