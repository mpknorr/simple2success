<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}
require_once '../includes/conn.php';
require_once '../includes/onboarding.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !s2sVerifyCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: start.php?err=csrf#step1');
    exit();
}

$userid  = (int)$_SESSION['userid'];
$step1ip = mysqli_real_escape_string($link, function_exists('getClientIp') ? getClientIp() : ($_SERVER['REMOTE_ADDR'] ?? ''));
$now     = date('Y-m-d H:i:s');

// Get the referring sponsor's PM number for the registration sponsorId parameter.
$row = mysqli_fetch_assoc(mysqli_query($link, "SELECT referer FROM users WHERE leadid = $userid"));
$referer_username = '';
if (!empty($row['referer']) && is_numeric($row['referer'])) {
    $ref = mysqli_fetch_assoc(mysqli_query($link, "SELECT username FROM users WHERE leadid = " . (int)$row['referer']));
    $referer_username = $ref['username'] ?? '';
}

// Never send a lead into registration without a verified numeric sponsor ID.
// A missing sponsor creates a wrong team connection that is difficult to undo.
if (!preg_match('/^\d+$/', trim((string)$referer_username))) {
    s2sLogLeadEvent($link, $userid, 'step1_sponsor_missing', 'backoffice/start.php');
    header('Location: start.php?err=sponsor#step1');
    exit();
}

// Record Step 1 only after the sponsor relationship has been verified.
mysqli_query($link, "UPDATE users SET
    signuproot = IF(signuproot IS NULL OR signuproot = '', '$now', signuproot),
    step1_at   = IF(step1_at IS NULL, NOW(), step1_at),
    step1_ip   = IF(step1_ip IS NULL OR step1_ip = '', '$step1ip', step1_ip)
    WHERE leadid = $userid");
s2sLogLeadEvent($link, $userid, 'step1_button_click', 'backoffice/start.php', 'verified_sponsor');

$url = 'https://registration.pm-international.com/?sponsorId=' . rawurlencode(trim((string)$referer_username));

header('Location: ' . $url);
exit();
