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

// Prevent parallel runs (time-based fallback: ignore stale locks older than 10 min)
$lockFile = sys_get_temp_dir() . '/followup_cron.lock';
$lockFp   = fopen($lockFile, 'c');
$isLocked = $lockFp && !flock($lockFp, LOCK_EX | LOCK_NB);
if ($isLocked) {
    $mtime = filemtime($lockFile);
    if ($mtime !== false && (time() - $mtime) > 600) {
        // Stale lock — force-release by re-creating the file
        fclose($lockFp);
        @unlink($lockFile);
        $lockFp   = fopen($lockFile, 'c');
        $isLocked = $lockFp && !flock($lockFp, LOCK_EX | LOCK_NB);
    }
}
if ($isLocked) {
    echo date('Y-m-d H:i:s') . " | Skipped: another instance is running\n";
    exit(0);
}

// Bootstrap — conn.php sets $link and $baseurl
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/../includes/sendFollowupEmails.php';

mysqli_query($link, "CREATE TABLE IF NOT EXISTS cron_runs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    job_name   VARCHAR(64) NOT NULL,
    started_at DATETIME NOT NULL,
    ended_at   DATETIME DEFAULT NULL,
    sent       INT DEFAULT 0,
    errors     TEXT,
    status     ENUM('ok','error','empty') DEFAULT 'ok'
)");

$startedAt = date('Y-m-d H:i:s');
try {
    $result = sendFollowupEmails($link);
} catch (\Throwable $e) {
    $result = ['sent' => 0, 'errors' => ['FATAL: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()]];
}
$endedAt = date('Y-m-d H:i:s');

$errCount  = count($result['errors']);
$status    = $errCount > 0 ? 'error' : ($result['sent'] === 0 ? 'empty' : 'ok');
$errText   = mysqli_real_escape_string($link, implode("\n", $result['errors']));
mysqli_query($link, "INSERT INTO cron_runs (job_name, started_at, ended_at, sent, errors, status)
    VALUES ('followup', '$startedAt', '$endedAt', {$result['sent']}, '$errText', '$status')");

$msg = $startedAt . " | Sent: {$result['sent']} | Errors: $errCount | Status: $status";
if (!empty($result['errors'])) {
    $msg .= "\n  " . implode("\n  ", $result['errors']);
}
echo $msg . "\n";
mysqli_close($link);

// Release lock
flock($lockFp, LOCK_UN);
fclose($lockFp);
