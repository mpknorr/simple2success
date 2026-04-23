<?php
/**
 * Email Click Tracking Endpoint
 * ─────────────────────────────
 * Called when a user clicks a tracked link in a follow-up email.
 * Logs the click to followup_clicks, then redirects to the real URL.
 *
 * URL format: /includes/email-click.php?uid=123&sid=5&url=https%3A%2F%2F...
 */

require_once __DIR__ . '/conn.php';

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

// Validate destination URL
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    header('Location: /backoffice/start.php');
    exit;
}

// Only allow http/https destinations (security: prevent javascript: etc.)
$scheme = (string)parse_url($url, PHP_URL_SCHEME);
if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
    header('Location: /backoffice/start.php');
    exit;
}

// Log the click — DB errors must never block the redirect
if ($uid > 0 && isset($link)) {
    try {
        @mysqli_query($link, "INSERT INTO followup_clicks (user_id, sequence_id) VALUES ($uid, $sid)");

        $metaStr = 'E-Mail Button geklickt';
        if ($sid > 0) {
            $seqRes = @mysqli_query($link, "SELECT name FROM followup_sequences WHERE id = $sid LIMIT 1");
            if ($seqRes && ($seqRow = mysqli_fetch_assoc($seqRes)) && !empty($seqRow['name'])) {
                $metaStr .= ' — ' . $seqRow['name'];
            }
        }
        $metaEsc = mysqli_real_escape_string($link, $metaStr);
        @mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, meta) VALUES ($uid, 'email_click', '$metaEsc')");
    } catch (Throwable $e) {
        // Logging failure must never block the redirect
    }
}

// Redirect to the real destination
header('Location: ' . $url);
exit;
