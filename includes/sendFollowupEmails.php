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

    try {
        mysqli_query($link, "ALTER TABLE followup_sequences ADD COLUMN IF NOT EXISTS subject_b VARCHAR(255) NOT NULL DEFAULT '' AFTER subject");
    } catch (\Throwable $e) { /* ignore */ }

    // Ensure last_login column exists (only runs during cron, not on every page load)
    try {
        mysqli_query($link, "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL");
    } catch (\Throwable $e) { /* ignore */ }

    mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_log (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        user_id          INT NOT NULL,
        sequence_id      INT NOT NULL,
        variant          CHAR(1) NOT NULL DEFAULT 'A',
        sent_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        brevo_message_id VARCHAR(255) NULL,
        status           VARCHAR(20) NOT NULL DEFAULT 'sent',
        delivered_at     TIMESTAMP NULL,
        opened_at        TIMESTAMP NULL,
        bounced_at       TIMESTAMP NULL,
        failed_at        TIMESTAMP NULL,
        UNIQUE KEY uniq_user_seq (user_id, sequence_id),
        INDEX idx_brevo_msg_id (brevo_message_id),
        INDEX idx_variant (variant)
    )");

    try {
        mysqli_query($link, "ALTER TABLE followup_log ADD COLUMN IF NOT EXISTS variant CHAR(1) NOT NULL DEFAULT 'A' AFTER sequence_id");
        mysqli_query($link, "ALTER TABLE followup_log ADD INDEX IF NOT EXISTS idx_variant (variant)");
    } catch (\Throwable $e) { /* ignore — column may already exist */ }

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

    $t1_subj = mysqli_real_escape_string($link, "{{name}}, finish the setup you started");
    $t1_body = mysqli_real_escape_string($link, '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
        . '<tr><td><img src="' . $banner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
        . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">'
        . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
        . '<p>You opened the official partner registration, but your Partner ID is not connected to Simple2Success yet.</p>'
        . '<p>If your registration is confirmed, copy the numeric Partner ID from your confirmation and save it in Step 2. If you have not finished registering, the same page takes you back to Step 1.</p>'
        . '<div style="text-align:center;margin:28px 0;"><a href="{{cta_url}}" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Continue My Setup &rarr;</a></div>'
        . '<p style="color:#888;font-size:13px;">One clear next step. No pressure.<br>Marc-Philipp | Simple2Success</p>'
        . '</td></tr>'
        . '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; 2025 <a href="https://www.simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.</td></tr>'
        . '</table></td></tr></table></body></html>');
    mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
        VALUES ('Trigger: Clicked Not Converted', 'trigger_clicked_not_converted', '$t1_subj', '$t1_body')");

    $t0_subj = mysqli_real_escape_string($link, "{{name}}, your first step is ready");
    $t0_body = mysqli_real_escape_string($link, '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
        . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.75;">'
        . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
        . '<p>Your Simple2Success account is ready, but Step 1 has not been opened yet.</p>'
        . '<p>You do not need to understand the whole system today. Open Mission Control, review the first step and decide at your own pace.</p>'
        . '<div style="text-align:center;margin:28px 0;"><a href="{{cta_url}}" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Show My First Step &rarr;</a></div>'
        . '<p style="color:#888;font-size:13px;">Marc-Philipp | Simple2Success</p>'
        . '</td></tr></table></td></tr></table></body></html>');
    mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
        VALUES ('Trigger: Account Ready, Step 1 Not Started', 'trigger_account_no_start', '$t0_subj', '$t0_body')");

    $t2_subj = mysqli_real_escape_string($link, "{{name}}, your next steps are ready");
    $t2_body = mysqli_real_escape_string($link, '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
        . '<tr><td><img src="' . $banner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
        . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">'
        . '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
        . '<p>Step 2 is complete. Your account setup is ready for the next phase.</p>'
        . '<p>Continue in order: Step 3 — choose your traffic, Step 4 — review your product options, then Step 5 — follow up and repeat what works.</p>'
        . '<p>There is also an optional current product conversation tool: the FitLine AI Scanner. Official partner information describes an app-based skin scan that evaluates more than 100 skin points and features and shows products matched to the customer profile. Check the current starter-set access, price and terms, and use the same Team Partner account for the purchase and the FitLine App sign-in.</p>'
        . '<p><a href="https://www.pm-international.com/de/de-de/partner/news/every-skin-is-different" style="color:#a51bc2;font-weight:bold;">View the official launch information &rarr;</a></p>'
        . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;"><strong>Choose one next action, review the result and keep your decisions in your control.</strong></p>'
        . '<div style="text-align:center;margin:28px 0;"><a href="{{cta_url}}" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Open Steps 3–5 &rarr;</a></div>'
        . '<p style="color:#888;font-size:13px;">Customer outcomes and income are not guaranteed.<br>Your Simple2Success Team</p>'
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
 * Keep trust high and complaints low: never send two follow-ups to the same
 * person inside one rolling contact window.
 */
function followupRecentlyContacted($link, $userId, $hours = 20) {
    $uid = (int)$userId;
    $hours = max(1, min(168, (int)$hours));
    $result = mysqli_query($link,
        "SELECT (
            EXISTS(SELECT 1 FROM followup_log WHERE user_id=$uid AND sent_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR))
            OR EXISTS(SELECT 1 FROM followup_trigger_log WHERE user_id=$uid AND sent_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR))
        ) AS recently_contacted");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return !empty($row['recently_contacted']);
}

/**
 * Inject a hidden preheader (inbox preview text) right after <body>.
 * Derived from the first ~90 chars of the email's plain text.
 * After the sender name, the preheader is the second biggest open-rate factor.
 */
function injectPreheader($body) {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
    // Skip the greeting ("Hi {{name}}," / "Hi Name,") so the preview shows real content
    $text = preg_replace('/^Hi [^,]{1,40},\s*/i', '', $text);
    if ($text === '') return $body;
    $preheader = mb_substr($text, 0, 90);
    $div = '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'
         . htmlspecialchars($preheader) . '&#847;&zwnj;&nbsp;</div>';
    $injected = preg_replace('/(<body[^>]*>)/i', '$1' . $div, $body, 1, $count);
    return $count ? $injected : $div . $body;
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
 * BEHAVIORAL TRIGGER 0: account created, but Step 1 was never opened.
 */
function sendAccountNotStartedEmails($link, $base_url) {
    $sent = 0; $errors = [];

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'trigger_account_no_start' LIMIT 1"));
    if (!$tpl || empty($tpl['body'])) return ['sent' => 0, 'errors' => ['trigger_account_no_start template missing']];

    $sql = "SELECT u.leadid, u.name, u.email
            FROM users u
            WHERE u.step1_at IS NULL
              AND u.step2_at IS NULL
              AND (u.username IS NULL OR u.username NOT REGEXP '^[0-9]+$')
              AND u.timestamp <= DATE_SUB(NOW(), INTERVAL 4 HOUR)
              AND u.timestamp >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
              AND u.leadid NOT IN (
                  SELECT user_id FROM followup_trigger_log WHERE trigger_type = 'account_no_start'
              )
            ORDER BY u.timestamp ASC
            LIMIT 100";

    $recipients = mysqli_query($link, $sql);
    if (!$recipients) return ['sent' => 0, 'errors' => ['account_no_start query failed']];

    try {
        $mailer = new BrevoMailer($link);
    } catch (\Exception $e) {
        return ['sent' => 0, 'errors' => ['BrevoMailer init: ' . $e->getMessage()]];
    }

    while ($rec = mysqli_fetch_assoc($recipients)) {
        $uid = (int)$rec['leadid'];
        if (followupRecentlyContacted($link, $uid)) continue;
        if (emailFooter_shouldSkip($link, $uid, 'trigger_account_no_start')) continue;

        $toEmail = $rec['email'];
        $toName = $rec['name'] ?: $toEmail;
        $magicLink = generateMagicLink($link, $uid, 'trigger_account_no_start', 48, 'index.php');
        $body = str_replace(['{{name}}', '{{email}}', '{{cta_url}}', '{{magic_link}}'],
            [htmlspecialchars($toName), htmlspecialchars($toEmail), $magicLink, $magicLink], $tpl['body']);
        $subject = str_replace(['{{name}}', '{{email}}'],
            [htmlspecialchars($toName), htmlspecialchars($toEmail)], $tpl['subject']);
        $body = injectClickTracking($body, $base_url, $uid, 0);
        $body .= renderEmailFooter($link, 'trigger_account_no_start', $uid);
        $body = injectPreheader($body);
        $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $messageId = $mailer->sendEmail(
                $toEmail, $toName, $subject, $body,
                ['followup', 'trigger', 'account_no_start'],
                ['user_id' => $uid, 'trigger_type' => 'account_no_start', 'email_type' => 'trigger']
            );
            $mid = mysqli_real_escape_string($link, $messageId);
            mysqli_query($link, "INSERT IGNORE INTO followup_trigger_log
                (user_id, trigger_type, brevo_message_id, status)
                VALUES ($uid, 'account_no_start', '$mid', 'sent')");
            $evMeta = mysqli_real_escape_string($link, 'Trigger: ' . strip_tags($subject));
            mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, meta, brevo_message_id)
                VALUES ($uid, 'email_sent', '$evMeta', '$mid')");
            $sent++;
        } catch (\Exception $e) {
            error_log("sendAccountNotStarted [$toEmail]: " . $e->getMessage());
            $errors[] = "$toEmail: account-no-start trigger failed";
        }
    }

    return ['sent' => $sent, 'errors' => $errors];
}

/**
 * BEHAVIORAL TRIGGER 1: opened Step 1 but did not complete Step 2.
 */
function sendClickedButNotConvertedEmails($link, $base_url) {
    $sent = 0; $errors = [];

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'trigger_clicked_not_converted' LIMIT 1"));
    if (!$tpl || empty($tpl['body'])) return ['sent' => 0, 'errors' => ['trigger_clicked_not_converted template missing']];

    $sql = "SELECT u.leadid, u.name, u.email
            FROM users u
            WHERE u.step1_at IS NOT NULL
              AND u.step2_at IS NULL
              AND (u.username IS NULL OR u.username NOT REGEXP '^[0-9]+$')
              AND u.step1_at <= DATE_SUB(NOW(), INTERVAL 2 HOUR)
              AND u.step1_at >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
              AND u.leadid NOT IN (
                  SELECT user_id FROM followup_trigger_log WHERE trigger_type = 'step1_no_step2'
              )
            ORDER BY u.step1_at ASC
            LIMIT 100";

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

        if (followupRecentlyContacted($link, $uid)) continue;
        if (emailFooter_shouldSkip($link, $uid, 'trigger_clicked_not_converted')) continue;

        $magicLink = generateMagicLink($link, $uid, 'trigger_clicked', 48, 'start.php');
        $body    = str_replace(['{{name}}', '{{email}}', '{{cta_url}}', '{{magic_link}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail), $magicLink, $magicLink],
                               $tpl['body']);
        $subject = str_replace(['{{name}}', '{{email}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail)],
                               $tpl['subject']);
        $body    = injectClickTracking($body, $base_url, $uid, 0);
        $body   .= renderEmailFooter($link, 'trigger_clicked_not_converted', $uid);
        $body    = injectPreheader($body);
        $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $messageId = $mailer->sendEmail(
                $toEmail, $toName, $subject, $body,
                ['followup', 'trigger', 'trigger_clicked_not_converted'],
                ['user_id' => $uid, 'trigger_type' => 'step1_no_step2', 'email_type' => 'trigger']
            );
            $mid = mysqli_real_escape_string($link, $messageId);
            mysqli_query($link, "INSERT IGNORE INTO followup_trigger_log
                (user_id, trigger_type, brevo_message_id, status)
                VALUES ($uid, 'step1_no_step2', '$mid', 'sent')");
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
 * BEHAVIORAL TRIGGER 2: Step 2 done; next-phase reminder after 48h.
 * Product or subscription completion is not externally verified in this app.
 */
function sendStep2DoneNoStep4Emails($link, $base_url) {
    $sent = 0; $errors = [];

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'trigger_step2_done_no_step4' LIMIT 1"));
    if (!$tpl || empty($tpl['body'])) return ['sent' => 0, 'errors' => ['trigger_step2_done_no_step4 template missing']];

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

        $magicLink = generateMagicLink($link, $uid, 'trigger_step2_no_step4', 48, 'start.php');
        $body    = str_replace(['{{name}}', '{{email}}', '{{cta_url}}', '{{magic_link}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail), $magicLink, $magicLink],
                               $tpl['body']);
        $subject = str_replace(['{{name}}', '{{email}}'],
                               [htmlspecialchars($toName), htmlspecialchars($toEmail)],
                               $tpl['subject']);
        $body    = injectClickTracking($body, $base_url, $uid, 0);
        $body   .= renderEmailFooter($link, 'trigger_step2_done_no_step4', $uid);
        $body    = injectPreheader($body);
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

        $magicLink = generateMagicLink($link, $uid, 'trigger_no_video', 48, 'start.php');
        $body    = str_replace(['{{name}}', '{{cta_url}}', '{{magic_link}}'],
                               [htmlspecialchars($toName), $magicLink, $magicLink],
                               $tpl['body']);
        $subject = str_replace('{{name}}', htmlspecialchars($toName), $tpl['subject']);
        $body    = injectClickTracking($body, $base_url, $uid, 0);
        $body   .= renderEmailFooter($link, 'trigger_no_video_watched', $uid);
        $body    = injectPreheader($body);
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

    // Send only during the configured server daytime window. The cron runs
    // hourly, so delaying a message is better than landing in an overnight pile.
    $hour = (int)date('G');
    $inSendWindow = ($hour >= 8 && $hour < 20);
    if (!$inSendWindow) {
        mysqli_query($link, "DELETE FROM login_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        return ['sent' => 0, 'errors' => []];
    }

    // ── 1. Behavioral messages first: they match the person's real state. ─────
    $t0 = sendAccountNotStartedEmails($link, $base_url);
    $sent += $t0['sent']; $errors = array_merge($errors, $t0['errors']);

    $t1 = sendClickedButNotConvertedEmails($link, $base_url);
    $sent += $t1['sent']; $errors = array_merge($errors, $t1['errors']);

    // ── 2. Regular day-offset sequences (A/B via subject_b column) ───────────
    // A rolling contact cap below guarantees at most one follow-up per 20 hours.
    $seqs = mysqli_query($link, "SELECT * FROM followup_sequences WHERE is_active = 1 ORDER BY target, day_offset ASC");
    if ($seqs && mysqli_num_rows($seqs) > 0) {
        while ($seq = mysqli_fetch_assoc($seqs)) {
            $seq_id     = (int)$seq['id'];
            $day_offset = (int)$seq['day_offset'];
            $target     = $seq['target'];
            $body       = $seq['body'];

            $filter = ($target === 'lead')
                ? "step2_at IS NULL AND (username IS NULL OR username NOT REGEXP '^[0-9]+$')"
                : "(step2_at IS NOT NULL OR username REGEXP '^[0-9]+$')";

            $sql = "SELECT leadid, name, email FROM users
                    WHERE $filter
                    AND TIMESTAMPDIFF(DAY, timestamp, NOW()) >= $day_offset
                    AND leadid NOT IN (
                        SELECT user_id FROM followup_log WHERE sequence_id = $seq_id
                    )
                    ORDER BY timestamp ASC
                    LIMIT 250";

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
                if (followupRecentlyContacted($link, $uid)) continue;
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
                    ['{{name}}', '{{email}}', '{{magic_link}}', '{{cta_url}}'],
                    [htmlspecialchars($toName), htmlspecialchars($toEmail), $magicLink, $magicLink],
                    $body
                );
                $personalBody  = injectClickTracking($personalBody, $base_url, $uid, $seq_id);
                $personalBody .= renderEmailFooter($link, $fuKey, $uid);
                $personalBody  = injectPreheader($personalBody);
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
                        (user_id, sequence_id, variant, brevo_message_id, status)
                        VALUES ($uid, $seq_id, '$variant', '$mid', 'sent')");
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

    // Step-4 and video reminders are deliberately not sent here: the current
    // application cannot verify Step 4 completion, and the overview video is
    // optional. Sending reminders from inferred states would erode trust.

    // ── 3. Cleanup expired magic link tokens ──────────────────────────────────
    mysqli_query($link, "DELETE FROM login_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");

    return ['sent' => $sent, 'errors' => $errors];
}
