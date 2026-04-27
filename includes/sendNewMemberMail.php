<?php
require_once __DIR__ . '/BrevoMailer.php';
require_once __DIR__ . '/emailFooter.php';

function sendNewMemberMail($link, $root) {
    global $baseurl;
    $safeRoot = mysqli_real_escape_string($link, $root);
    $loginUrl = rtrim($baseurl ?: 'https://www.simple2success.com', '/') . '/backoffice/login.php';

    $row = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT u.email AS member_email, r.leadid AS sponsor_id, r.email AS sponsor_email, r.name AS sponsor_name
         FROM users u
         LEFT JOIN users r ON u.referer = r.leadid
         WHERE u.username = '$safeRoot' LIMIT 1"));

    if (!$row || empty($row['sponsor_email'])) {
        return 'sendNewMemberMail: sponsor not found for username ' . $root;
    }

    $memberEmail  = $row['member_email'];
    $sponsorId    = (int)($row['sponsor_id'] ?? 0);
    $sponsorEmail = $row['sponsor_email'];
    $sponsorName  = $row['sponsor_name'] ?: $sponsorEmail;

    $tpl = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT subject, body FROM email_templates WHERE template_key = 'new_member' LIMIT 1"));
    if (!$tpl) {
        return 'sendNewMemberMail: new_member template not found in email_templates';
    }

    $subject = str_replace(
        ['{{name}}', '{{member_email}}', '{{login_url}}'],
        [htmlspecialchars($sponsorName), htmlspecialchars($memberEmail), $loginUrl],
        $tpl['subject']);
    $body = str_replace(
        ['{{name}}', '{{member_email}}', '{{login_url}}'],
        [htmlspecialchars($sponsorName), htmlspecialchars($memberEmail), $loginUrl],
        $tpl['body'])
        . renderEmailFooter($link, 'new_member', $sponsorId);
    $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    try {
        $mailer = new BrevoMailer($link);
        $mailer->sendEmail($sponsorEmail, $sponsorName, $subject, $body,
            ['admin-notification', 'new-member'],
            ['user_id' => $sponsorId, 'email_type' => 'new_member']);
        return true;
    } catch (\Exception $e) {
        error_log("sendNewMemberMail [$sponsorEmail]: " . $e->getMessage());
        return 'sendNewMemberMail error: ' . $e->getMessage();
    }
}
