<?php
/** A saved Partner ID completes Steps 1 + 2 in our funnel. No external verification implied. */
function s2sSetupComplete($partnerId): bool {
    return trim((string)$partnerId) !== '';
}
function s2sNextAction($partnerId, bool $clicked): array {
    if (s2sSetupComplete($partnerId)) return ['href' => 'start.php#next-steps', 'label' => 'Continue with Step 3'];
    if ($clicked) return ['href' => 'start.php#step1-action', 'label' => 'Continue registration or save your PM Ref-ID'];
    return ['href' => 'start.php#step1-action', 'label' => 'Start Step 1: partner registration'];
}
