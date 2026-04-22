<?php
/**
 * Follow-up email cron script.
 * Schedule this to run hourly (or every 15 min) via cPanel / server cron:
 *
 *   /usr/bin/php /path/to/cron/followup.php >> /path/to/cron/followup.log 2>&1
 *
 * Or call it via HTTP with a secret token:
 *   https://yoursite.com/cron/followup.php?token=ca246a9152344e988c9eb30c9f05460e60c518b01949351e
 */

// Security: allow CLI or HTTP call with a token
$cli  = (php_sapi_name() === 'cli');
$token_ok = isset($_GET['token']) && $_GET['token'] === 'ca246a9152344e988c9eb30c9f05460e60c518b01949351e';

if (!$cli && !$token_ok) {
    http_response_code(403);
    die('Forbidden');
}

define('CRON_RUN', true);

// Bootstrap — conn.php sets $link and $baseurl
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/../includes/sendFollowupEmails.php';

$result = sendFollowupEmails($link);

$msg = date('Y-m-d H:i:s') . " | Sent: {$result['sent']} | Errors: " . count($result['errors']);
if (!empty($result['errors'])) {
    $msg .= "\n  " . implode("\n  ", $result['errors']);
}
echo $msg . "\n";
mysqli_close($link);
