<?php
/**
 * Magic Link Auto-Login Handler
 *
 * Valid token   → session set, redirect to index.php
 * Expired token → new link sent by email, friendly expiry page shown
 * Invalid token → redirect to login.php with error flag
 */
require_once '../includes/conn.php';
require_once '../includes/helpers.php';
require_once '../includes/BrevoMailer.php';
use PHPMailer\PHPMailer\Exception as MailException;

// ── UI strings (English — multilingual structure ready for future extension) ──
$txt_expired_title = 'Your login link has expired';
$txt_expired_text  = 'For security reasons, login links expire after a limited time. We have automatically sent a new link to your email address.';
$txt_invalid_title = 'Invalid or already used link';
$txt_invalid_text  = 'This login link is invalid or has already been used. Please log in with your email and password.';
$txt_new_link_sent = 'A new login link has been sent to:';
$txt_check_inbox   = 'Please check your inbox (and your spam folder).';
$txt_back_to_login = 'Back to Login';

// ── Validate token format ─────────────────────────────────────────────────────
$rawToken = $_GET['token'] ?? '';
if (!preg_match('/^[0-9a-f]{64}$/', $rawToken)) {
    header('Location: login.php?err=invalid_link');
    exit();
}

$tokenEsc = mysqli_real_escape_string($link, $rawToken);

// ── Cleanup expired tokens older than 7 days ─────────────────────────────────
mysqli_query($link, "DELETE FROM login_tokens
    WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");

// ── Look up token ─────────────────────────────────────────────────────────────
$row = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT lt.*, u.name, u.email, u.is_admin
     FROM login_tokens lt
     JOIN users u ON u.leadid = lt.user_id
     WHERE lt.token = '$tokenEsc'
     LIMIT 1"));

if (!$row) {
    header('Location: login.php?err=invalid_link');
    exit();
}

// ── Already used ──────────────────────────────────────────────────────────────
if ($row['used_at'] !== null) {
    header('Location: login.php?err=link_used');
    exit();
}

// ── Expired — Graceful Expiry Flow ────────────────────────────────────────────
if (strtotime($row['expires_at']) < time()) {

    // Generate a fresh 2-hour token
    $newMagicLink = generateMagicLink($link, (int)$row['user_id'], 'expired_resend', 2);
    $displayName  = $row['name'] ?: $row['email'];

    $expiredBody =
        '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
        . '<body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;margin:0 auto;padding:30px 40px;">'
        . '<tr><td><h2 style="color:#cb2ebc;">Hi ' . htmlspecialchars($displayName) . ',</h2>'
        . '<p>You clicked a login link that has expired.</p>'
        . '<p>Here is your new secure login link — valid for <strong>2 hours</strong>:</p>'
        . '<div style="text-align:center;margin:24px 0;">'
        . '<a href="' . $newMagicLink . '" style="background:#cb2ebc;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;">Log In Now &rarr;</a>'
        . '</div>'
        . '<p style="color:#888;font-size:13px;">If you did not request this, you can ignore this email.</p>'
        . '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>'
        . '</td></tr></table></body></html>';

    try {
        $al_mailer = new BrevoMailer($link);
        $al_mailer->sendEmail($row['email'], $displayName,
            'Your new login link — Simple2Success', $expiredBody,
            ['transactional', 'magic-link'],
            ['user_id' => (int)$row['user_id'], 'email_type' => 'expired_magic_link']);
    } catch (\Exception $e) {
        error_log("autologin [{$row['email']}]: " . $e->getMessage());
        // Silent — user still sees the expiry page
    }

    // Show friendly expiry page
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($txt_expired_title) ?> — Simple2Success</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { min-height: 100vh; display: flex; align-items: center; justify-content: center;
         font-family: Arial, sans-serif; background: #0d0017; color: #e0e0e0; padding: 20px; }
  .card { background: rgba(18,0,42,.95); border: 1px solid rgba(203,46,188,.3);
          border-radius: 12px; padding: 40px 36px; max-width: 480px; width: 100%;
          text-align: center; box-shadow: 0 0 40px rgba(203,46,188,.12); }
  .icon { font-size: 2.5rem; margin-bottom: 16px; }
  h1 { font-size: 1.35rem; color: #fff; margin-bottom: 12px; }
  p  { font-size: .95rem; color: rgba(255,255,255,.6); line-height: 1.6; margin-bottom: 10px; }
  .email-highlight { color: #cb2ebc; font-weight: 700; }
  .btn { display: inline-block; margin-top: 24px; background: #cb2ebc; color: #fff;
         padding: 12px 28px; border-radius: 6px; text-decoration: none;
         font-weight: 700; font-size: .95rem; }
  .btn:hover { opacity: .88; }
</style>
</head>
<body>
  <div class="card">
    <div class="icon">⏱</div>
    <h1><?= htmlspecialchars($txt_expired_title) ?></h1>
    <p><?= htmlspecialchars($txt_expired_text) ?></p>
    <p><?= htmlspecialchars($txt_new_link_sent) ?><br>
       <span class="email-highlight"><?= htmlspecialchars($row['email']) ?></span></p>
    <p><?= htmlspecialchars($txt_check_inbox) ?></p>
    <a href="login.php" class="btn"><?= htmlspecialchars($txt_back_to_login) ?></a>
  </div>
</body>
</html>
    <?php
    exit();
}

// ── Valid token — log in ──────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
session_regenerate_id(true);

$_SESSION['userid']   = (int)$row['user_id'];
$_SESSION['is_admin'] = !empty($row['is_admin']);

// Invalidate token immediately
mysqli_query($link, "UPDATE login_tokens SET used_at = NOW()
    WHERE token = '$tokenEsc'");

header('Location: index.php');
exit();
