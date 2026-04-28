<?php
session_start();
if (empty($_SESSION["userid"]) || empty($_SESSION["is_admin"])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
require_once '../includes/conn.php';

header('Content-Type: application/json; charset=utf-8');

// type: recipients | clicks | delivered | opened | bounced | spam | failed
$type   = $_GET['type']   ?? 'recipients';
$seq_id = (int)($_GET['seq_id'] ?? 0);

if ($seq_id <= 0) {
    echo json_encode(['rows' => [], 'total' => 0]);
    exit();
}

if ($type === 'clicks') {
    $sql = "SELECT fc.clicked_at AS signup_at,
                   u.leadid, u.name, u.email, u.country_detected, u.lang, u.source,
                   u.paidstatus, u.step1_at, u.username
            FROM followup_clicks fc
            JOIN users u ON u.leadid = fc.user_id
            WHERE fc.sequence_id = $seq_id
            ORDER BY fc.clicked_at DESC
            LIMIT 200";
} elseif (in_array($type, ['delivered','opened','bounced','spam','failed'], true)) {
    $statusMap = [
        'delivered' => "fl.status IN ('delivered','opened','clicked')",
        'opened'    => "fl.status IN ('opened','clicked')",
        'bounced'   => "fl.status = 'bounced'",
        'spam'      => "fl.status = 'spam'",
        'failed'    => "fl.status = 'failed'",
    ];
    $orderMap = [
        'delivered' => 'fl.delivered_at',
        'opened'    => 'fl.opened_at',
        'bounced'   => 'fl.bounced_at',
        'spam'      => 'fl.spam_at',
        'failed'    => 'fl.failed_at',
    ];
    $where   = $statusMap[$type];
    $orderBy = $orderMap[$type] . ' DESC, fl.sent_at DESC';
    $sql = "SELECT fl.sent_at AS signup_at,
                   fl.status, fl.delivered_at, fl.opened_at, fl.bounced_at,
                   fl.spam_at, fl.failed_at, fl.bounce_type, fl.brevo_message_id,
                   u.leadid, u.name, u.email, u.country_detected, u.lang, u.source,
                   u.paidstatus, u.step1_at, u.username
            FROM followup_log fl
            JOIN users u ON u.leadid = fl.user_id
            WHERE fl.sequence_id = $seq_id AND $where
            ORDER BY $orderBy
            LIMIT 200";
} else {
    $sql = "SELECT fl.sent_at AS signup_at,
                   u.leadid, u.name, u.email, u.country_detected, u.lang, u.source,
                   u.paidstatus, u.step1_at, u.username
            FROM followup_log fl
            JOIN users u ON u.leadid = fl.user_id
            WHERE fl.sequence_id = $seq_id
            ORDER BY fl.sent_at DESC
            LIMIT 200";
}

$res  = mysqli_query($link, $sql);
$rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

echo json_encode(['rows' => $rows, 'type' => $type, 'total' => count($rows)]);
