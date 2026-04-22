<?php
/**
 * Daily lead notification cron script.
 * Sends each member an email listing the new leads they received today.
 * Schedule this to run once per day (e.g. 20:00) via cPanel / server cron:
 *
 *   /usr/bin/php /path/to/cron/daily_leads.php >> /path/to/cron/daily_leads.log 2>&1
 *
 * Or call it via HTTP with a secret token:
 *   https://yoursite.com/cron/daily_leads.php?token=7ed168b24e108db9a53682af3645256049c2a46b25a9ab43
 */

// Security: allow CLI or HTTP call with a token
$cli      = (php_sapi_name() === 'cli');
$token_ok = isset($_GET['token']) && $_GET['token'] === '7ed168b24e108db9a53682af3645256049c2a46b25a9ab43';

if (!$cli && !$token_ok) {
    http_response_code(403);
    die('Forbidden');
}

define('CRON_RUN', true);

// Bootstrap — conn.php sets $link and $baseurl
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/../includes/sendDailyMail.php';

$result = sendDailyLeadsNotifications($link);

$msg = date('Y-m-d H:i:s') . " | Sent: {$result['sent']} | Errors: " . count($result['errors']);
if (!empty($result['errors'])) {
    $msg .= "\n  " . implode("\n  ", $result['errors']);
}
echo $msg . "\n";
mysqli_close($link);
