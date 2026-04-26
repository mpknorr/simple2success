<?php
session_start();
if (empty($_SESSION["userid"]) || empty($_SESSION["is_admin"])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
require_once '../includes/conn.php';

header('Content-Type: application/json; charset=utf-8');

$dim_type  = $_GET['dim_type']  ?? 'page';           // page | source | country_detected | lang | funnel
$dim_value = $_GET['dim_value'] ?? '';
$metric    = $_GET['metric']    ?? 'signups';
$from      = $_GET['from']      ?? date('Y-m-d');
$to        = $_GET['to']        ?? date('Y-m-d');

$esc_dim   = mysqli_real_escape_string($link, $dim_value);
$esc_from  = mysqli_real_escape_string($link, $from);
$esc_to    = mysqli_real_escape_string($link, $to);

// ── Re-Signups: different table ───────────────────────────────────────────────
if ($metric === 'resignups') {
    $dimFilter = ($dim_type === 'page' && $dim_value !== '')
        ? "AND le.page = '$esc_dim'"
        : '';
    $sql = "SELECT le.created_at AS signup_at,
                   u.leadid, u.name, u.email, u.country_detected, u.lang, u.source, u.paidstatus,
                   u.step1_at, u.username
            FROM lead_events le
            LEFT JOIN users u ON u.leadid = le.lead_id
            WHERE le.event_type = 'signup_attempt'
              $dimFilter
              AND le.created_at BETWEEN '$esc_from 00:00:00' AND '$esc_to 23:59:59'
            ORDER BY le.created_at DESC
            LIMIT 200";
    $res  = mysqli_query($link, $sql);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    echo json_encode(['rows' => $rows, 'metric' => $metric, 'total' => count($rows)]);
    exit();
}

// ── Users table queries ───────────────────────────────────────────────────────
$whereBase = "WHERE timestamp BETWEEN '$esc_from 00:00:00' AND '$esc_to 23:59:59'";

// Dimension filter (skip for 'funnel' = global)
$allowedDims = ['page', 'source', 'country_detected', 'lang'];
if (in_array($dim_type, $allowedDims, true) && $dim_value !== '') {
    $whereBase .= " AND `$dim_type` = '$esc_dim'";
}

// Metric filter
if ($metric === 'step1')  $whereBase .= ' AND step1_at IS NOT NULL';
if ($metric === 'step2')  $whereBase .= " AND username IS NOT NULL AND username != ''";
if ($metric === 'paid')   $whereBase .= " AND paidstatus = 'Paid'";

$sql  = "SELECT timestamp AS signup_at, leadid, name, email,
                country_detected, lang, source, page, paidstatus, step1_at, username
         FROM users $whereBase
         ORDER BY timestamp DESC
         LIMIT 200";
$res  = mysqli_query($link, $sql);
$rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

echo json_encode(['rows' => $rows, 'metric' => $metric, 'total' => count($rows)]);
