<?php
/** Member sequence age starts with saved Partner ID; legacy unknown dates are not guessed. */
function s2sFollowupPhaseDateSql(string $target): string {
    return $target === 'member' ? 'step2_at' : 'timestamp';
}

/** Only the current offset is due; old messages are deliberately not caught up. */
function s2sDueFollowupRecipientsSql(string $target, int $sequenceId, int $dayOffset): string {
    $filter = $target === 'lead'
        ? "(username IS NULL OR username = '')"
        : "(username IS NOT NULL AND username != '')";
    $phaseDate = s2sFollowupPhaseDateSql($target);
    return "SELECT leadid, name, email FROM users WHERE $filter
        AND TIMESTAMPDIFF(DAY, $phaseDate, NOW()) = $dayOffset
        AND leadid NOT IN (SELECT user_id FROM followup_log WHERE sequence_id = $sequenceId)";
}

/**
 * One follow-up attempt per user per rolling 24 hours across sequences and triggers.
 * Reserve before network I/O: an ambiguous provider timeout must not cause a burst.
 * Existing logs cover messages sent before deployment. Failed attempts may retry after 24h.
 */
function s2sReserveFollowupContact($link, int $uid): bool {
    if ($uid <= 0) return false;
    $recent = mysqli_query($link, "SELECT user_id FROM followup_log
        WHERE user_id=$uid AND sent_at > NOW() - INTERVAL 24 HOUR
        UNION ALL SELECT user_id FROM followup_trigger_log
        WHERE user_id=$uid AND sent_at > NOW() - INTERVAL 24 HOUR LIMIT 1");
    if (!$recent || mysqli_num_rows($recent) > 0) return false;
    $updated = mysqli_query($link, "UPDATE followup_contact_limits SET last_attempt_at=NOW()
        WHERE user_id=$uid AND last_attempt_at <= NOW() - INTERVAL 24 HOUR");
    if (!$updated) return false;
    if (mysqli_affected_rows($link) === 1) return true;
    $inserted = mysqli_query($link, "INSERT IGNORE INTO followup_contact_limits (user_id, last_attempt_at)
        VALUES ($uid, NOW())");
    return $inserted && mysqli_affected_rows($link) === 1;
}
