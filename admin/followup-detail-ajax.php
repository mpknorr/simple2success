<?php
session_start();
if (empty($_SESSION["userid"]) || empty($_SESSION["is_admin"])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
require_once '../includes/conn.php';

header('Content-Type: application/json; charset=utf-8');

$type   = $_GET['type']   ?? 'recipients';  // recipients | clicks
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
