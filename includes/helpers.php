<?php
/**
 * generateMagicLink — creates a secure one-time auto-login token and returns the full URL.
 *
 * @param mysqli $link        DB connection
 * @param int    $userId      users.leadid
 * @param string $emailType   label stored with token (welcome / followup_seq_X / trigger_clicked / etc.)
 * @param int    $expiryHours token validity in hours
 * @return string             full URL to backoffice/autologin.php?token=...
 */
function generateMagicLink($link, $userId, $emailType = 'followup', $expiryHours = 72, $redirect = 'start.php') {
    // Auto-create table on first use
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS login_tokens (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        token      VARCHAR(64) NOT NULL,
        email_type VARCHAR(64) NOT NULL DEFAULT 'followup',
        expires_at DATETIME NOT NULL,
        used_at    DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_token (token),
        INDEX idx_user    (user_id),
        INDEX idx_expires (expires_at)
    )");

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));
    $uid     = (int)$userId;
    $typEsc  = mysqli_real_escape_string($link, $emailType);
    $tokEsc  = mysqli_real_escape_string($link, $token);

    mysqli_query($link, "INSERT INTO login_tokens (user_id, token, email_type, expires_at)
        VALUES ($uid, '$tokEsc', '$typEsc', '$expires')");

    $base = rtrim($GLOBALS['baseurl'] ?? 'https://simple2success.com', '/');
    return $base . '/backoffice/autologin.php?token=' . $token . '&redirect=' . urlencode($redirect);
}
