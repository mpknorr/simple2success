<?php
/** A saved Partner ID completes Steps 1 + 2 in our funnel. No external verification implied. */
function s2sSetupComplete($partnerId): bool {
    return trim((string)$partnerId) !== '';
}
function s2sNextAction($partnerId, bool $clicked): array {
    if (s2sSetupComplete($partnerId)) return ['href' => 'start.php#next-steps', 'label' => 'Plan your next action'];
    if ($clicked) return ['href' => 'start.php#step2-action', 'label' => 'Already registered? Save your Partner ID'];
    return ['href' => 'start.php#step1-action', 'label' => 'Start Step 1: partner registration'];
}
