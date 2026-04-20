<?php
/**
 * welcome.php — Step 2 POST handler (no HTML)
 * Processes the PM Partner ID form, then redirects back to start.php.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "../includes/conn.php";
require_once "../includes/sendNewMemberMail.php";

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

if (isset($_POST["root"])) {
    $root     = trim($_POST["root"]);
    $userid   = (int)$_POST["userid"];
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
    if (!preg_match('/^\d+$/', $root)) {
        header("Location: start.php?err=invalidpm");
        exit();
    }

    $root    = mysqli_real_escape_string($link, $root);
    $step2ip = mysqli_real_escape_string($link, getClientIp());
    $result  = mysqli_query($link, "UPDATE users SET username='$root', step2_at=NOW(), step2_ip='$step2ip' WHERE leadid=$userid");

    if (!$result) {
        header("Location: start.php?err=invalidpm");
        exit();
    }

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
    header("Location: start.php?step2=done");
    exit();
}

// If reached without POST (direct access), redirect to start.php
header("Location: start.php");
exit();
