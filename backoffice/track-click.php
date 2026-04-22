<?php
session_start();

if (empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/conn.php';

$allowed = [
    'start_step' => 'start.php',
];

$action   = $_GET['action'] ?? '';
$redirect = $allowed[$action] ?? null;

if (!$redirect) {
    header('Location: index.php');
    exit();
}

$leadId = (int)$_SESSION['userid'];
$ip     = mysqli_real_escape_string($link, $_SERVER['REMOTE_ADDR'] ?? '');
$page   = mysqli_real_escape_string($link, 'backoffice/index.php');
$event  = mysqli_real_escape_string($link, 'start_step_click');

mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, page, ip)
    VALUES ($leadId, '$event', '$page', '$ip')");

header('Location: ' . $redirect);
exit();
