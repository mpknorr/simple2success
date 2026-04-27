<?php
require_once __DIR__ . '/BrevoMailer.php';
require_once __DIR__ . '/emailFooter.php';

function sendDailyLeadsNotifications($link) {
    global $baseurl;

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS daily_leads_log (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        member_id  INT NOT NULL,
        sent_date  DATE NOT NULL,
        sent_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_member_date (member_id, sent_date)
    )");

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'daily_leads' LIMIT 1"));
    if (!$tpl) {
        return ['sent' => 0, 'errors' => ['daily_leads template not found in email_templates table.']];
    }

    $loginUrl = rtrim($baseurl ?: 'https://www.simple2success.com', '/') . '/backoffice/login.php';
    $sent = 0; $errors = [];

    $members = mysqli_query($link,
        "SELECT leadid, name, email FROM users
         WHERE leadid IN (
             SELECT DISTINCT referer FROM users
             WHERE referer IS NOT NULL AND referer != 0 AND DATE(timestamp) = CURDATE()
         )
         AND username IS NOT NULL AND username != ''");
    if (!$members) {
        return ['sent' => 0, 'errors' => ['DB query failed: ' . mysqli_error($link)]];
    }

    try {
        $mailer = new BrevoMailer($link);
    } catch (\Exception $e) {
        return ['sent' => 0, 'errors' => ['BrevoMailer init: ' . $e->getMessage()]];
    }

    while ($member = mysqli_fetch_assoc($members)) {
        $memberId    = (int)$member['leadid'];
        $memberEmail = $member['email'];
        $memberName  = $member['name'] ?: $memberEmail;

        $alreadySent = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT id FROM daily_leads_log WHERE member_id=$memberId AND sent_date=CURDATE() LIMIT 1"));
        if ($alreadySent) continue;

        if (emailFooter_shouldSkip($link, $memberId, 'daily_leads')) continue;

        $leadsRes = mysqli_query($link,
            "SELECT email FROM users WHERE referer=$memberId AND DATE(timestamp)=CURDATE() ORDER BY timestamp ASC");
        if (!$leadsRes || mysqli_num_rows($leadsRes) === 0) continue;

        $leadEmails = [];
        while ($lr = mysqli_fetch_assoc($leadsRes)) {
            $leadEmails[] = htmlspecialchars($lr['email']);
        }
        if (count($leadEmails) === 1) {
            $leadsHtml = $leadEmails[0];
        } else {
            $leadsHtml = '<ul style="padding-left:20px;margin:8px 0;">';
            foreach ($leadEmails as $le) $leadsHtml .= '<li>' . $le . '</li>';
            $leadsHtml .= '</ul>';
        }

        $subject = str_replace(['{{name}}', '{{email}}', '{{leads}}', '{{login_url}}'],
            [htmlspecialchars($memberName), htmlspecialchars($memberEmail), $leadsHtml, $loginUrl],
            $tpl['subject']);
        $body = str_replace(['{{name}}', '{{email}}', '{{leads}}', '{{login_url}}'],
            [htmlspecialchars($memberName), htmlspecialchars($memberEmail), $leadsHtml, $loginUrl],
            $tpl['body'])
            . renderEmailFooter($link, 'daily_leads', $memberId);
        $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $mailer->sendEmail($memberEmail, $memberName, $subject, $body,
                ['admin-notification', 'daily-leads'],
                ['user_id' => $memberId, 'email_type' => 'daily_leads']);
            mysqli_query($link, "INSERT IGNORE INTO daily_leads_log (member_id, sent_date) VALUES ($memberId, CURDATE())");
            $sent++;
        } catch (\Exception $e) {
            error_log("sendDailyMail [$memberEmail]: " . $e->getMessage());
            $errors[] = "$memberEmail: " . $e->getMessage();
        }
    }
    return ['sent' => $sent, 'errors' => $errors];
}
