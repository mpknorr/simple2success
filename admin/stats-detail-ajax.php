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

// ── Follow-up drill-down ──────────────────────────────────────────────────────
if ($dim_type === 'followup') {
    $seq_id = (int)$dim_value;

    if ($metric === 'fup_clicked') {
        $fcExists = mysqli_num_rows(mysqli_query($link, "SHOW TABLES LIKE 'followup_clicks'")) > 0;
        if ($fcExists) {
            $sql = "SELECT fck.clicked_at AS signup_at,
                           u.leadid, u.name, u.email, u.country_detected, u.lang,
                           u.source, u.page, u.paidstatus, u.step1_at, u.username
                    FROM followup_clicks fck
                    LEFT JOIN users u ON u.leadid = fck.user_id
                    WHERE fck.sequence_id = $seq_id
                      AND fck.clicked_at BETWEEN '$esc_from 00:00:00' AND '$esc_to 23:59:59'
                    ORDER BY fck.clicked_at DESC LIMIT 200";
        } else {
            $sql = "SELECT fl.sent_at AS signup_at,
                           u.leadid, u.name, u.email, u.country_detected, u.lang,
                           u.source, u.page, u.paidstatus, u.step1_at, u.username
                    FROM followup_log fl
                    LEFT JOIN users u ON u.leadid = fl.user_id
                    WHERE fl.sequence_id = $seq_id
                      AND fl.status = 'clicked'
                      AND fl.sent_at BETWEEN '$esc_from 00:00:00' AND '$esc_to 23:59:59'
                    ORDER BY fl.sent_at DESC LIMIT 200";
        }
    } else {
        $statusFilter = [
            'fup_delivered' => "AND fl.status IN ('delivered','opened','clicked')",
            'fup_opened'    => "AND fl.status IN ('opened','clicked')",
            'fup_bounced'   => "AND fl.status = 'bounced'",
            'fup_spam'      => "AND fl.status = 'spam'",
            'fup_failed'    => "AND fl.status = 'failed'",
        ][$metric] ?? '';

        $sql = "SELECT fl.sent_at AS signup_at,
                       u.leadid, u.name, u.email, u.country_detected, u.lang,
                       u.source, u.page, u.paidstatus, u.step1_at, u.username
                FROM followup_log fl
                LEFT JOIN users u ON u.leadid = fl.user_id
                WHERE fl.sequence_id = $seq_id
                  AND fl.sent_at BETWEEN '$esc_from 00:00:00' AND '$esc_to 23:59:59'
                  $statusFilter
                ORDER BY fl.sent_at DESC LIMIT 200";
    }

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
