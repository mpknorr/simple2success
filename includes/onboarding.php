<?php
/**
 * Shared activation/onboarding helpers.
 *
 * The onboarding profile deliberately lives in its own table so the feature can
 * be deployed without changing the legacy users table. The table is created on
 * first use and stores only the member's declared goal and next-step intention.
 */

function s2sEnsureOnboardingTable($link): void
{
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS onboarding_profiles (
        user_id          INT NOT NULL PRIMARY KEY,
        goal_code        VARCHAR(40) NOT NULL DEFAULT '',
        commitment_code  VARCHAR(20) NOT NULL DEFAULT '',
        created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_goal (goal_code),
        INDEX idx_commitment (commitment_code)
    )");
}

function s2sCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['s2s_csrf_token'])) {
        $_SESSION['s2s_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['s2s_csrf_token'];
}

function s2sVerifyCsrf(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return !empty($_SESSION['s2s_csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['s2s_csrf_token'], $token);
}

function s2sGoalOptions(): array
{
    return [
        'income'  => ['label' => 'Build additional income', 'icon' => 'ft-trending-up'],
        'family'  => ['label' => 'Create more family time', 'icon' => 'ft-heart'],
        'travel'  => ['label' => 'Create more flexibility', 'icon' => 'ft-globe'],
        'debt'    => ['label' => 'Reduce financial pressure', 'icon' => 'ft-shield'],
        'business'=> ['label' => 'Build my own business skills', 'icon' => 'ft-briefcase'],
    ];
}

function s2sCommitmentOptions(): array
{
    return [
        'now'      => 'I will continue now',
        'today'    => 'I will finish it today',
        'tomorrow' => 'I will finish it tomorrow',
    ];
}

function s2sGetOnboardingProfile($link, int $userId): array
{
    s2sEnsureOnboardingTable($link);
    $result = mysqli_query($link,
        "SELECT goal_code, commitment_code FROM onboarding_profiles WHERE user_id = $userId LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;

    return $row ?: ['goal_code' => '', 'commitment_code' => ''];
}

function s2sActivationState(array $user): array
{
    $step1Started = !empty($user['step1_at']);
    $step2Complete = !empty($user['step2_at'])
        || (!empty($user['username']) && preg_match('/^\d+$/', trim((string)$user['username'])));

    if ($step2Complete) {
        return [
            'key' => 'activated',
            'completed' => 3,
            'percent' => 100,
            'eyebrow' => 'Activation complete',
            'title' => 'Your Simple2Success foundation is ready.',
            'body' => 'You have completed the account and partner connection. Your next goal is a simple 90-day routine you can repeat.',
            'cta_label' => 'Open My Next-Phase Plan',
            'cta_url' => 'start.php#after-activation',
        ];
    }

    if ($step1Started) {
        return [
            'key' => 'step2',
            'completed' => 2,
            'percent' => 67,
            'eyebrow' => 'One setup step remains',
            'title' => 'Add your Partner ID to complete activation.',
            'body' => 'Once PM-International confirms your registration, copy the Partner ID from your confirmation and save it in Step 2.',
            'cta_label' => 'Complete Step 2',
            'cta_url' => 'start.php#step2',
        ];
    }

    return [
        'key' => 'step1',
        'completed' => 1,
        'percent' => 33,
        'eyebrow' => 'Your account is ready',
        'title' => 'Review Step 1 and make your decision.',
        'body' => 'See the partner, the current terms and exactly what happens next. You stay in control at every point.',
        'cta_label' => 'Review Step 1',
        'cta_url' => 'start.php#step1',
    ];
}

function s2sLogLeadEvent($link, int $userId, string $eventType, string $page = '', string $meta = ''): void
{
    $event = mysqli_real_escape_string($link, substr($eventType, 0, 64));
    $pageEsc = mysqli_real_escape_string($link, substr($page, 0, 100));
    $metaEsc = mysqli_real_escape_string($link, substr($meta, 0, 500));
    $ip = mysqli_real_escape_string($link, function_exists('getClientIp')
        ? getClientIp()
        : ($_SERVER['REMOTE_ADDR'] ?? ''));

    @mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, page, meta, ip)
        VALUES ($userId, '$event', '$pageEsc', '$metaEsc', '$ip')");
}
