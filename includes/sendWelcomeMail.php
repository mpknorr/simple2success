<?php
require_once __DIR__ . '/BrevoMailer.php';
require_once __DIR__ . '/emailFooter.php';
require_once __DIR__ . '/helpers.php';

function sendWelcomeMail($link, $toEmail, $toName, $plainPassword, $loginUrl, $userId = 0) {
    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'welcome_user' LIMIT 1"));
    if (!$tpl) {
        return 'Welcome template not found in DB';
    }

    $displayName = $toName ?: $toEmail;

    $magicLink = ($userId > 0)
        ? generateMagicLink($link, $userId, 'welcome', 24)
        : $loginUrl;

    $placeholders = ['{{name}}', '{{email}}', '{{password}}', '{{login_url}}', '{{magic_link}}'];
    $values       = [htmlspecialchars($displayName), htmlspecialchars($toEmail),
                     htmlspecialchars($plainPassword), $loginUrl, $magicLink];

    $subject  = str_replace($placeholders, $values, $tpl['subject']);
    $body     = str_replace($placeholders, $values, $tpl['body'])
              . renderEmailFooter($link, 'welcome_user', 0);
    $subject  = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    try {
        $mailer    = new BrevoMailer($link);
        $messageId = $mailer->sendEmail(
            $toEmail,
            $displayName,
            $subject,
            $body,
            ['welcome', 'transactional'],
            ['user_id' => $userId, 'email_type' => 'welcome']
        );

        // messageId in lead_events speichern
        if ($userId > 0 && $messageId) {
            $mid = mysqli_real_escape_string($link, $messageId);
            mysqli_query($link,
                "INSERT INTO lead_events (lead_id, event_type, page, brevo_message_id)
                 VALUES ($userId, 'email_sent', 'welcome', '$mid')");
        }

        return true;
    } catch (\Exception $e) {
        error_log('sendWelcomeMail error: ' . $e->getMessage());
        return 'Welcome mail error: ' . $e->getMessage();
    }
}
