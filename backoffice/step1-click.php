<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}
require_once '../includes/conn.php';

$userid  = (int)$_SESSION['userid'];
$step1ip = mysqli_real_escape_string($link, $_SERVER['REMOTE_ADDR'] ?? '');
$now     = date('Y-m-d H:i:s');

// Record Step 1 click — only set if not already set
mysqli_query($link, "UPDATE users SET
    signuproot = IF(signuproot IS NULL OR signuproot = '', '$now', signuproot),
    step1_at   = IF(step1_at IS NULL, NOW(), step1_at),
    step1_ip   = IF(step1_ip IS NULL OR step1_ip = '', '$step1ip', step1_ip)
    WHERE leadid = $userid");

// Get sponsor's PM number for TP= parameter
$row = mysqli_fetch_assoc(mysqli_query($link, "SELECT referer FROM users WHERE leadid = $userid"));
$referer_username = '';
if (!empty($row['referer']) && is_numeric($row['referer'])) {
    $ref = mysqli_fetch_assoc(mysqli_query($link, "SELECT username FROM users WHERE leadid = " . (int)$row['referer']));
    $referer_username = $ref['username'] ?? '';
}

$tp  = !empty($referer_username) ? '?TP=' . urlencode($referer_username) : '';
$url = 'https://www.pmebusiness.com/registrationv2/' . $tp;

header('Location: ' . $url);
exit();
