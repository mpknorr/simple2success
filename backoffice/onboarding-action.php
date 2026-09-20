<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/conn.php';
require_once '../includes/onboarding.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !s2sVerifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request. Please reload the page and try again.');
}

$userId = (int)$_SESSION['userid'];
$action = $_POST['action'] ?? '';
$redirect = ($_POST['redirect'] ?? '') === 'start' ? 'start.php' : 'index.php';

s2sEnsureOnboardingTable($link);

if ($action === 'set_goal') {
    $goal = $_POST['goal'] ?? '';
    if (!array_key_exists($goal, s2sGoalOptions())) {
        header('Location: ' . $redirect . '?profile=invalid');
        exit();
    }

    $goalEsc = mysqli_real_escape_string($link, $goal);
    $saved = mysqli_query($link, "INSERT INTO onboarding_profiles (user_id, goal_code)
        VALUES ($userId, '$goalEsc')
        ON DUPLICATE KEY UPDATE goal_code=VALUES(goal_code), updated_at=CURRENT_TIMESTAMP");
    if ($saved) {
        s2sLogLeadEvent($link, $userId, 'goal_selected', 'backoffice/' . $redirect, $goal);
    }
    header('Location: ' . $redirect . '?profile=' . ($saved ? 'saved' : 'error'));
    exit();
}

if ($action === 'set_commitment') {
    $commitment = $_POST['commitment'] ?? '';
    if (!array_key_exists($commitment, s2sCommitmentOptions())) {
        header('Location: ' . $redirect . '?plan=invalid');
        exit();
    }

    $commitmentEsc = mysqli_real_escape_string($link, $commitment);
    $saved = mysqli_query($link, "INSERT INTO onboarding_profiles (user_id, commitment_code)
        VALUES ($userId, '$commitmentEsc')
        ON DUPLICATE KEY UPDATE commitment_code=VALUES(commitment_code), updated_at=CURRENT_TIMESTAMP");
    if ($saved) {
        s2sLogLeadEvent($link, $userId, 'commitment_set', 'backoffice/' . $redirect, $commitment);
    }
    header('Location: ' . $redirect . '?plan=' . ($saved ? 'saved' : 'error'));
    exit();
}

http_response_code(400);
exit('Unknown action.');
