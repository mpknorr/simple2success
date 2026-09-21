<?php
/**
 * welcome.php — Step 2 POST handler (no HTML)
 * Processes the PM Partner ID form, then redirects back to start.php.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}
require_once "../includes/conn.php";
require_once "../includes/sendNewMemberMail.php";
require_once "../includes/onboarding.php";

// Ensure notifications table exists
mysqli_query($link, "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    lead_id INT NOT NULL,
    lead_name VARCHAR(255),
    lead_profile_pic VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["root"])) {
    if (!s2sVerifyCsrf($_POST['csrf_token'] ?? null)) {
        header("Location: start.php?err=csrf#step2");
        exit();
    }

    $root     = trim($_POST["root"]);
    // The account being updated always comes from the authenticated session.
    // Never trust a posted user ID here.
    $userid   = (int)$_SESSION['userid'];
    $is_admin = !empty($_SESSION['is_admin']);

    // ── Lock check: if already saved a numeric PM number, block changes ──────
    if (!$is_admin) {
        $existing = mysqli_fetch_assoc(mysqli_query($link, "SELECT username FROM users WHERE leadid = $userid"));
        if ($existing && !empty($existing['username']) && preg_match('/^\d+$/', trim($existing['username']))) {
            header("Location: start.php?err=locked");
            exit();
        }
    }

    // ── Numeric format validation ─────────────────────────────────────────────
    if (!preg_match('/^\d{4,12}$/', $root)) {
        header("Location: start.php?err=invalidpm#step2");
        exit();
    }

    // A Partner ID belongs to one account. Prevent accidental cross-account links.
    $rootCheck = mysqli_real_escape_string($link, $root);
    $duplicate = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT leadid FROM users WHERE username='$rootCheck' AND leadid<>$userid LIMIT 1"));
    if ($duplicate) {
        header("Location: start.php?err=duplicatepm#step2");
        exit();
    }

    $root    = mysqli_real_escape_string($link, $root);
    $step2ip = mysqli_real_escape_string($link, getClientIp());
    $result  = mysqli_query($link, "UPDATE users SET username='$root', step2_at=NOW(), step2_ip='$step2ip' WHERE leadid=$userid");

    if (!$result) {
        header("Location: start.php?err=invalidpm");
        exit();
    }

    // ── Log step2 completion event ───────────────────────────────────────────
    s2sLogLeadEvent($link, $userid, 'step2_completed', 'backoffice/start.php', 'partner_id_saved');

    // ── Send notification email to referer (sponsor) ─────────────────────────
    $mailResult = sendNewMemberMail($link, $root);
    if ($mailResult !== true) {
        error_log('[welcome.php] sendNewMemberMail failed: ' . $mailResult);
    }

    // ── Insert notification for referer ──────────────────────────────────────
    $leadData = mysqli_fetch_assoc(mysqli_query($link, "SELECT name, email, profile_pic, referer FROM users WHERE leadid = $userid"));
    if ($leadData && !empty($leadData['referer'])) {
        $recipientId = (int)$leadData['referer'];
        $leadName    = mysqli_real_escape_string($link, $leadData['name'] ?: ($leadData['email'] ?: 'Unknown'));
        $leadPic     = mysqli_real_escape_string($link, $leadData['profile_pic'] ?: 'user_default.png');
        mysqli_query($link, "INSERT INTO notifications (recipient_id, lead_id, lead_name, lead_profile_pic)
            VALUES ($recipientId, $userid, '$leadName', '$leadPic')");
    }

    // ── Success: return to start.php with success state ──────────────────────
    unset($_SESSION['s2s_csrf_token']);
    header("Location: start.php?step2=done#after-activation");
    exit();
}

// If reached without POST (direct access), redirect to start.php
header("Location: start.php");
exit();
