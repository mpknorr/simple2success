<?php
require_once __DIR__ . '/BrevoMailer.php';
require_once __DIR__ . '/emailFooter.php';
require_once __DIR__ . '/helpers.php';

function getSmtpSettingFU($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key='$k'"));
    return $r ? $r['setting_value'] : '';
}

/**
 * Ensure all required tables exist and seed trigger email templates if missing.
 */
function ensureFollowupTables($link) {
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_sequences (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        target       ENUM('lead','member') NOT NULL DEFAULT 'lead',
        day_offset   INT NOT NULL DEFAULT 1,
        subject      VARCHAR(255) NOT NULL,
        subject_b    VARCHAR(255) NOT NULL DEFAULT '',
        body         LONGTEXT NOT NULL,
        is_active    TINYINT(1) NOT NULL DEFAULT 1,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $col = mysqli_fetch_assoc(mysqli_query($link, "SHOW COLUMNS FROM followup_sequences LIKE 'subject_b'"));
    if (!$col) {
        mysqli_query($link, "ALTER TABLE followup_sequences ADD COLUMN subject_b VARCHAR(255) NOT NULL DEFAULT '' AFTER subject");
    }

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_log (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        user_id          INT NOT NULL,
        sequence_id      INT NOT NULL,
        sent_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        brevo_message_id VARCHAR(255) NULL,
        status           VARCHAR(20) NOT NULL DEFAULT 'sent',
        delivered_at     TIMESTAMP NULL,
        opened_at        TIMESTAMP NULL,
        bounced_at       TIMESTAMP NULL,
        failed_at        TIMESTAMP NULL,
        UNIQUE KEY uniq_user_seq (user_id, sequence_id),
        INDEX idx_brevo_msg_id (brevo_message_id)
    )");

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_clicks (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        user_id      INT NOT NULL,
        sequence_id  INT NOT NULL DEFAULT 0,
        clicked_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_seq  (sequence_id)
    )");

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_trigger_log (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        user_id          INT NOT NULL,
        trigger_type     VARCHAR(64) NOT NULL,
        sent_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        brevo_message_id VARCHAR(255) NULL,
        status           VARCHAR(20) NOT NULL DEFAULT 'sent',
        UNIQUE KEY uniq_user_trigger (user_id, trigger_type)
    )");

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_ab_assignments (
        user_id      INT NOT NULL PRIMARY KEY,
        variant      CHAR(1) NOT NULL DEFAULT 'A'
    )");

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS email_templates (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        name         VARCHAR(100) NOT NULL DEFAULT '',
        template_key VARCHAR(100) NOT NULL UNIQUE,
        subject      VARCHAR(255) NOT NULL DEFAULT '',
        body         LONGTEXT NOT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Seed behavioral trigger templates (INSERT IGNORE — never overwrites admin edits)
    $banner = 'https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';

    $t1_subj = mysqli_real_escape_string($link, "You were this close, {{name}} — here's your direct link");
    $t1_body = mysqli_real_escape_string($link, '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
        . '<tr><td><img src="' . $banner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
        . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">'
        . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
        . '<p>You opened our email and clicked the link — but Step 2 is still not complete. That tells us you\'re interested. Here\'s your direct link to pick up exactly where you left off.</p>'
        . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;"><strong>Your system is waiting. One click away.</strong></p>'
        . '<div style="text-align:center;margin:28px 0;"><a href="{{cta_url}}" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Complete Step 2 Now &rarr;</a></div>'
        . '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>'
        . '</td></tr>'
        . '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; 2025 <a href="https://www.simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.</td></tr>'
        . '</table></td></tr></table></body></html>');
    mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
        VALUES ('Trigger: Clicked Not Converted', 'trigger_clicked_not_converted', '$t1_subj', '$t1_body')");

    $t2_subj = mysqli_real_escape_string($link, "{{name}}, Step 2 is done — here's what's missing");
    $t2_body = mysqli_real_escape_string($link, '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
        . '<tr><td><img src="' . $banner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
        . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">'
        . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
        . '<p>Congratulations — Step 2 is done. Your system is active. But there\'s one step that separates an active account from a <strong>genuinely earning system</strong>: Step 4.</p>'
        . '<p>Step 4 activates the full income structure. Without it, the system runs — but not at full potential. With it, every activity in your team directly benefits you.</p>'
        . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;"><strong>You\'ve already done the hardest part. Step 4 is the next logical move.</strong></p>'
        . '<div style="text-align:center;margin:28px 0;"><a href="{{cta_url}}" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Activate Step 4 Now &rarr;</a></div>'
        . '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>'
        . '</td></tr>'
        . '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; 2025 <a href="https://www.simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.</td></tr>'
        . '</table></td></tr></table></body></html>');
    mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
        VALUES ('Trigger: Step 2 Done No Step 4', 'trigger_step2_done_no_step4', '$t2_subj', '$t2_body')");
}

/**
 * Assign or retrieve A/B variant for a user (50/50 by user_id modulo).
 */
function getAbVariant($link, $user_id) {
    $uid = (int)$user_id;
    $row = mysqli_fetch_assoc(mysqli_query($link, "SELECT variant FROM followup_ab_assignments WHERE user_id=$uid"));
    if ($row) return $row['variant'];
    $variant = ($uid % 2 === 0) ? 'A' : 'B';
    mysqli_query($link, "INSERT IGNORE INTO followup_ab_assignments (user_id, variant) VALUES ($uid, '$variant')");
    return $variant;
}

function applyAbVariant($subject_a, $subject_b, $variant) {
    return ($variant === 'B' && !empty($subject_b)) ? $subject_b : $subject_a;
}

/**
 * Inject click tracking into all href links in an email body.
 */
function injectClickTracking($body, $base_url, $user_id, $sequence_id) {
    return preg_replace_callback(
        '/href="(https?:\/\/[^"]+)"/i',
        function($matches) use ($base_url, $user_id, $sequence_id) {
            $url = $matches[1];
            if (stripos($url, 'unsubscribe') !== false) return $matches[0];
            if (stripos($url, 'email-click.php') !== false) return $matches[0];
            $tracked = rtrim($base_url, '/') . '/includes/email-click.php'
                . '?uid=' . (int)$user_id
                . '&sid=' . (int)$sequence_id
                . '&url=' . urlencode($url);
            return 'href="' . $tracked . '"';
        },
        $body
    );
}

/**
 * BEHAVIORAL TRIGGER 1: "Clicked link but didn't complete Step 2"
 */
function sendClickedButNotConvertedEmails($link, $base_url) {
    $sent = 0; $errors = [];

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'trigger_clicked_not_converted' LIMIT 1"));
    if (!$tpl || empty($tpl['body'])) return ['sent' => 0, 'errors' => ['trigger_clicked_not_converted template missing']];

    $ctaUrl = rtrim($base_url, '/') . '/backoffice/start.php';

    $sql = "SELECT DISTINCT u.leadid, u.name, u.email
            FROM users u
            INNER JOIN followup_clicks fc ON fc.user_id = u.leadid
            WHERE (u.username IS NULL OR u.username = '')
            AND fc.clicked_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            AND fc.clicked_at <= DATE_SUB(NOW(), INTERVAL 2 HOUR)
            AND u.leadid NOT IN (
                SELECT user_id FROM followup_trigger_log WHERE trigger_type = 'clicked_no_step2'
            )";

    $recipients = mysqli_query($link, $sql);
    if (!$recipients) return ['sent' => 0, 'errors' => []];

    try {
        $mailer = new BrevoMailer($link);
    } catch (\Exception $e) {
        return ['sent' => 0, 'errors' => ['BrevoMailer init: ' . $e->getMessage()]];
    }

    while ($rec = mysqli_fetch_assoc($recipients)) {
        $uid     = (int)$rec['leadid'];
        $toEmail = $rec['email'];
        $toName  = $rec['name'] ?: $toEmail;

        if (emailFooter_shouldSkip($link, $uid, 'trigger_clicked_not_converted')) continue;

        $magicLink = generateMagicLink($link, $uid, 'trigger_clicked', 48);
        $body    = str_replace(['{{name}}', '{{email}}', '{{cta_url}}', '{{magic_link}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail), $ctaUrl, $magicLink],
                               $tpl['body']);
        $subject = str_replace(['{{name}}', '{{email}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail)],
                               $tpl['subject']);
        $body    = injectClickTracking($body, $base_url, $uid, 0);
        $body   .= renderEmailFooter($link, 'trigger_clicked_not_converted', $uid);
        $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $messageId = $mailer->sendEmail(
                $toEmail, $toName, $subject, $body,
                ['followup', 'trigger', 'trigger_clicked_not_converted'],
                ['user_id' => $uid, 'trigger_type' => 'clicked_no_step2', 'email_type' => 'trigger']
            );
            $mid = mysqli_real_escape_string($link, $messageId);
            mysqli_query($link, "INSERT IGNORE INTO followup_trigger_log
                (user_id, trigger_type, brevo_message_id, status)
                VALUES ($uid, 'clicked_no_step2', '$mid', 'sent')");
            $evMeta = mysqli_real_escape_string($link, 'Trigger: ' . strip_tags($subject));
            mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, meta, brevo_message_id)
                VALUES ($uid, 'email_sent', '$evMeta', '$mid')");
            $sent++;
        } catch (\Exception $e) {
            error_log("sendClickedButNotConverted [$toEmail]: " . $e->getMessage());
            $errors[] = "$toEmail: trigger 1 failed";
        }
    }
    return ['sent' => $sent, 'errors' => $errors];
}

/**
 * BEHAVIORAL TRIGGER 2: "Step 2 done, Step 4 not started after 48h"
 */
function sendStep2DoneNoStep4Emails($link, $base_url) {
    $sent = 0; $errors = [];

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'trigger_step2_done_no_step4' LIMIT 1"));
    if (!$tpl || empty($tpl['body'])) return ['sent' => 0, 'errors' => ['trigger_step2_done_no_step4 template missing']];

    $ctaUrl = rtrim($base_url, '/') . '/backoffice/start.php';

    $sql = "SELECT u.leadid, u.name, u.email
            FROM users u
            WHERE u.username IS NOT NULL AND u.username != ''
            AND u.step2_at IS NOT NULL
            AND TIMESTAMPDIFF(HOUR, u.step2_at, NOW()) BETWEEN 48 AND 96
            AND u.leadid NOT IN (
                SELECT user_id FROM followup_trigger_log WHERE trigger_type = 'step2_done_no_step4'
            )";

    $recipients = mysqli_query($link, $sql);
    if (!$recipients) return ['sent' => 0, 'errors' => []];

    try {
        $mailer = new BrevoMailer($link);
    } catch (\Exception $e) {
        return ['sent' => 0, 'errors' => ['BrevoMailer init: ' . $e->getMessage()]];
    }

    while ($rec = mysqli_fetch_assoc($recipients)) {
        $uid     = (int)$rec['leadid'];
        $toEmail = $rec['email'];
        $toName  = $rec['name'] ?: $toEmail;

        if (emailFooter_shouldSkip($link, $uid, 'trigger_step2_done_no_step4')) continue;

        $magicLink = generateMagicLink($link, $uid, 'trigger_step2_no_step4', 48);
        $body    = str_replace(['{{name}}', '{{email}}', '{{cta_url}}', '{{magic_link}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail), $ctaUrl, $magicLink],
                               $tpl['body']);
        $subject = str_replace(['{{name}}', '{{email}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail)],
                               $tpl['subject']);
        $body    = injectClickTracking($body, $base_url, $uid, 0);
        $body   .= renderEmailFooter($link, 'trigger_step2_done_no_step4', $uid);
        $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $messageId = $mailer->sendEmail(
                $toEmail, $toName, $subject, $body,
                ['followup', 'trigger', 'trigger_step2_done_no_step4'],
                ['user_id' => $uid, 'trigger_type' => 'step2_done_no_step4', 'email_type' => 'trigger']
            );
            $mid = mysqli_real_escape_string($link, $messageId);
            mysqli_query($link, "INSERT IGNORE INTO followup_trigger_log
                (user_id, trigger_type, brevo_message_id, status)
                VALUES ($uid, 'step2_done_no_step4', '$mid', 'sent')");
            $evMeta = mysqli_real_escape_string($link, 'Trigger: ' . strip_tags($subject));
            mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, meta, brevo_message_id)
                VALUES ($uid, 'email_sent', '$evMeta', '$mid')");
            $sent++;
        } catch (\Exception $e) {
            error_log("sendStep2DoneNoStep4 [$toEmail]: " . $e->getMessage());
            $errors[] = "$toEmail: trigger 2 failed";
        }
    }
    return ['sent' => $sent, 'errors' => $errors];
}

/**
 * BEHAVIORAL TRIGGER 3: "Logged in but never watched the video after 24h"
 */
function sendNoVideoWatchedEmails($link, $base_url) {
    $sent = 0; $errors = [];

    $tSubj = mysqli_real_escape_string($link, '{{name}}, you haven\'t watched the overview yet');
    $banner = 'https://www.simple2success.com/assets/img/email-banner.jpg';
    $tBody = mysqli_real_escape_string($link,
        '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;">'
        . '<tr><td><img src="' . $banner . '" width="600" alt="Simple2Success" style="display:block;width:100%;"></td></tr>'
        . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">'
        . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
        . '<p>You created your free account — but you haven\'t watched the business presentation yet.</p>'
        . '<p>This overview shows you exactly what you\'re joining, how the income system works, and why people in 40+ countries use it to build additional income online.</p>'
        . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">'
        . '<strong>You do not need to watch all of it right now. But the more you understand, the faster you move.</strong></p>'
        . '<div style="text-align:center;margin:28px 0;">'
        . '<a href="{{cta_url}}" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Watch the Presentation Now &rarr;</a>'
        . '</div>'
        . '<p style="color:#888;font-size:13px;">Most people who watch it complete Step 1 the same day.</p>'
        . '</td></tr></table></td></tr></table></body></html>'
    );

    mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
        VALUES ('Trigger: No Video Watched', 'trigger_no_video_watched', '$tSubj', '$tBody')");

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'trigger_no_video_watched' LIMIT 1"));
    if (!$tpl || empty($tpl['body'])) return ['sent' => 0, 'errors' => ['trigger_no_video_watched template missing']];

    $ctaUrl = rtrim($base_url, '/') . '/backoffice/start.php';

    $sql = "SELECT u.leadid, u.name, u.email
            FROM users u
            WHERE u.last_login IS NOT NULL
              AND u.step2_at IS NULL
              AND u.last_login < NOW() - INTERVAL 24 HOUR
              AND u.leadid NOT IN (
                  SELECT DISTINCT lead_id FROM lead_events WHERE event_type = 'video_play'
              )
              AND u.leadid NOT IN (
                  SELECT user_id FROM followup_trigger_log WHERE trigger_type = 'no_video_24h'
              )
            LIMIT 50";

    $recipients = mysqli_query($link, $sql);
    if (!$recipients) return ['sent' => 0, 'errors' => []];

    try {
        $mailer = new BrevoMailer($link);
    } catch (\Exception $e) {
        return ['sent' => 0, 'errors' => ['BrevoMailer init: ' . $e->getMessage()]];
    }

    while ($rec = mysqli_fetch_assoc($recipients)) {
        $uid     = (int)$rec['leadid'];
        $toEmail = $rec['email'];
        $toName  = $rec['name'] ?: $toEmail;

        if (emailFooter_shouldSkip($link, $uid, 'trigger_no_video_watched')) continue;

        $body    = str_replace(['{{name}}', '{{cta_url}}'],
                               [htmlspecialchars($toName), $ctaUrl],
                               $tpl['body']);
        $subject = str_replace('{{name}}', htmlspecialchars($toName), $tpl['subject']);
        $body   .= renderEmailFooter($link, 'trigger_no_video_watched', $uid);
        $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $messageId = $mailer->sendEmail(
                $toEmail, $toName, $subject, $body,
                ['followup', 'trigger', 'trigger_no_video_watched'],
                ['user_id' => $uid, 'trigger_type' => 'no_video_24h', 'email_type' => 'trigger']
            );
            $mid = mysqli_real_escape_string($link, $messageId);
            mysqli_query($link, "INSERT IGNORE INTO followup_trigger_log
                (user_id, trigger_type, brevo_message_id, status)
                VALUES ($uid, 'no_video_24h', '$mid', 'sent')");
            $evMeta = mysqli_real_escape_string($link, 'Trigger: ' . strip_tags($subject));
            mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, meta, brevo_message_id)
                VALUES ($uid, 'email_sent', '$evMeta', '$mid')");
            $sent++;
        } catch (\Exception $e) {
            error_log("sendNoVideoWatched [$toEmail]: " . $e->getMessage());
            $errors[] = "$toEmail: trigger 3 failed";
        }
    }
    return ['sent' => $sent, 'errors' => $errors];
}

/**
 * Main function: Send all due follow-up emails.
 */
function sendFollowupEmails($link) {
    ensureFollowupTables($link);

    $base_url = getSmtpSettingFU($link, 'site_url') ?: 'https://www.simple2success.com';

    $sent = 0; $errors = [];

    try {
        $mailer = new BrevoMailer($link);
    } catch (\Exception $e) {
        return ['sent' => 0, 'errors' => ['BrevoMailer init: ' . $e->getMessage()]];
    }

    // ── 1. Regular day-offset sequences (A/B via subject_b column) ───────────
    $seqs = mysqli_query($link, "SELECT * FROM followup_sequences WHERE is_active = 1 ORDER BY target, day_offset ASC");
    if ($seqs && mysqli_num_rows($seqs) > 0) {
        while ($seq = mysqli_fetch_assoc($seqs)) {
            $seq_id     = (int)$seq['id'];
            $day_offset = (int)$seq['day_offset'];
            $target     = $seq['target'];
            $body       = $seq['body'];

            $filter = ($target === 'lead')
                ? "(username IS NULL OR username = '')"
                : "(username IS NOT NULL AND username != '')";

            $sql = "SELECT leadid, name, email FROM users
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

                $fuKey = 'followup_seq_' . $seq_id;
                if (emailFooter_shouldSkip($link, $uid, $fuKey)) continue;

                $variant         = getAbVariant($link, $uid);
                $finalSubject    = applyAbVariant($seq['subject'], $seq['subject_b'] ?? '', $variant);
                $magicLink       = generateMagicLink($link, $uid, $fuKey, 72);

                $personalSubject = str_replace(
                    ['{{name}}', '{{email}}', '{{magic_link}}'],
                    [htmlspecialchars($toName), htmlspecialchars($toEmail), $magicLink],
                    $finalSubject
                );
                $personalBody = str_replace(
                    ['{{name}}', '{{email}}', '{{magic_link}}'],
                    [htmlspecialchars($toName), htmlspecialchars($toEmail), $magicLink],
                    $body
                );
                $personalBody  = injectClickTracking($personalBody, $base_url, $uid, $seq_id);
                $personalBody .= renderEmailFooter($link, $fuKey, $uid);
                $personalSubject = html_entity_decode($personalSubject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $seqType = ($target === 'lead') ? 'lead_sequence' : 'member_sequence';

                try {
                    $messageId = $mailer->sendEmail(
                        $toEmail, $toName, $personalSubject, $personalBody,
                        ['followup', $seqType],
                        ['user_id' => $uid, 'sequence_id' => $seq_id, 'sequence_type' => $target,
                         'day_offset' => $day_offset, 'email_type' => 'followup_sequence']
                    );
                    $mid = mysqli_real_escape_string($link, $messageId);
                    mysqli_query($link, "INSERT IGNORE INTO followup_log
                        (user_id, sequence_id, brevo_message_id, status)
                        VALUES ($uid, $seq_id, '$mid', 'sent')");
                    $evMeta = mysqli_real_escape_string($link, "seq $seq_id ($target day $day_offset): " . strip_tags($personalSubject));
                    mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, meta, brevo_message_id)
                        VALUES ($uid, 'email_sent', '$evMeta', '$mid')");
                    $sent++;
                } catch (\Exception $e) {
                    error_log("sendFollowupEmails seq $seq_id [$toEmail]: " . $e->getMessage());
                    $errors[] = "$toEmail (seq $seq_id): send failed";
                }
            }
        }
    }

    // ── 2. Behavioral trigger: clicked but didn't complete Step 2 ────────────
    $t1 = sendClickedButNotConvertedEmails($link, $base_url);
    $sent += $t1['sent']; $errors = array_merge($errors, $t1['errors']);

    // ── 3. Behavioral trigger: Step 2 done, Step 4 not started after 48h ─────
    $t2 = sendStep2DoneNoStep4Emails($link, $base_url);
    $sent += $t2['sent']; $errors = array_merge($errors, $t2['errors']);

    // ── 4. Behavioral trigger: logged in but no video played after 24h ────────
    $t3 = sendNoVideoWatchedEmails($link, $base_url);
    $sent += $t3['sent']; $errors = array_merge($errors, $t3['errors']);

    // ── 5. Cleanup expired magic link tokens ──────────────────────────────────
    mysqli_query($link, "DELETE FROM login_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");

    return ['sent' => $sent, 'errors' => $errors];
}
