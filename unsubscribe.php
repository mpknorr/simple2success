<?php
/**
 * Marketing Email Unsubscribe Endpoint
 * ────────────────────────────────────
 * One-click opt-out from marketing emails (followup sequences, behavioral
 * triggers, daily lead notifications). No login required (§ 7 UWG).
 * Transaction emails (password reset, welcome, support) are unaffected.
 *
 * URL: /unsubscribe.php?uid=<leadid>&token=<hex>
 */

require_once __DIR__ . '/includes/conn.php';

$uid   = isset($_GET['uid'])   ? (int)$_GET['uid']           : 0;
$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

$status = 'error'; // 'ok' | 'already' | 'error'

if ($uid > 0 && $token !== '' && preg_match('/^[a-f0-9]{16,128}$/i', $token)) {
    $row = @mysqli_fetch_assoc(@mysqli_query($link,
        "SELECT unsubscribe_token, marketing_optout FROM users WHERE leadid=$uid LIMIT 1"));
    if ($row && !empty($row['unsubscribe_token']) && hash_equals($row['unsubscribe_token'], $token)) {
        if ((int)$row['marketing_optout'] === 1) {
            $status = 'already';
        } else {
            @mysqli_query($link, "UPDATE users SET marketing_optout=1 WHERE leadid=$uid");
            $ip = mysqli_real_escape_string($link, $_SERVER['REMOTE_ADDR'] ?? '');
            @mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, ip) VALUES ($uid, 'unsubscribe', '$ip')");
            $status = 'ok';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Unsubscribe &mdash; Simple2Success</title>
    <link rel="shortcut icon" href="<?= htmlspecialchars($baseurl) ?>/backoffice/app-assets/img/ico/favicon.ico">
    <style>
        body { background:#1a1a2e; color:#ccc; font-family:Arial,sans-serif; padding:40px 20px; margin:0; }
        .wrap { max-width:560px; margin:60px auto; background:#232334; border-radius:8px; padding:40px; text-align:center; }
        .wrap h2 { color:#cb2ebc; margin-top:0; }
        .wrap p  { line-height:1.7; color:#ccc; }
        .wrap a  { color:#cb2ebc; }
        .icon { font-size:48px; margin-bottom:12px; }
        .icon.ok    { color:#2ecc71; }
        .icon.warn  { color:#e74c3c; }
    </style>
</head>
<body>
<div class="wrap">
<?php if ($status === 'ok'): ?>
    <div class="icon ok">&#10003;</div>
    <h2>You have been unsubscribed</h2>
    <p>You will no longer receive marketing emails from Simple2Success.</p>
    <p style="font-size:13px;color:#999;">
        You will still receive essential account emails such as password resets,
        support responses, and transactional notifications.
    </p>
<?php elseif ($status === 'already'): ?>
    <div class="icon ok">&#10003;</div>
    <h2>Already unsubscribed</h2>
    <p>Your email address is already unsubscribed from marketing emails.</p>
<?php else: ?>
    <div class="icon warn">&#9888;</div>
    <h2>Invalid unsubscribe link</h2>
    <p>The link you used is invalid or has expired. Please contact
        <a href="mailto:info@simple2success.com">info@simple2success.com</a> if you continue to receive unwanted emails.</p>
<?php endif; ?>
    <p style="margin-top:32px;font-size:12px;color:#666;">
        <a href="<?= htmlspecialchars($baseurl) ?>/impress.php">Legal Notice</a> &middot;
        <a href="<?= htmlspecialchars($baseurl) ?>/legal.php?doc=privacy-policy">Privacy</a>
    </p>
</div>
</body>
</html>
<?php
mysqli_close($link);
