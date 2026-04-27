<?php
/**
 * Brevo Transactional Email Webhook
 *
 * In Brevo konfigurieren: Transactional → Settings → Webhooks
 * URL: https://simple2success.com/includes/brevo-webhook.php?token=<brevo_webhook_token>
 * Events: delivered, hardBounce, softBounce, spam, opened
 */

require_once __DIR__ . '/conn.php';

// ── Token-Validierung ────────────────────────────────────────────────────────
$expectedToken = '';
$r = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT setting_value FROM settings WHERE setting_key = 'brevo_webhook_token' LIMIT 1"));
if ($r) $expectedToken = $r['setting_value'];

$receivedToken = $_GET['token'] ?? '';
if (empty($expectedToken) || !hash_equals($expectedToken, $receivedToken)) {
    http_response_code(403);
    exit();
}

// ── Payload lesen ────────────────────────────────────────────────────────────
http_response_code(200); // Immer 200 antworten — Brevo deaktiviert sonst den Webhook

$raw = file_get_contents('php://input');
if (empty($raw)) {
    exit();
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    error_log('brevo-webhook: ungültiges JSON — ' . substr($raw, 0, 200));
    exit();
}

$event     = $payload['event']      ?? '';
$messageId = $payload['message-id'] ?? $payload['messageId'] ?? ''; // Brevo sendet "message-id"
$email     = $payload['email']      ?? '';

if (empty($event)) {
    error_log('brevo-webhook: kein event-Feld im Payload');
    exit();
}

if (empty($messageId)) {
    // Einige Events (z.B. request/deferred) haben keine messageId — still ignorieren
    exit();
}

$mid = mysqli_real_escape_string($link, $messageId);

// ── followup_log aktualisieren ────────────────────────────────────────────────
function webhook_update_followup_log($link, string $mid, string $event): bool {
    switch ($event) {
        case 'delivered':
            $sql = "UPDATE followup_log
                    SET status = 'delivered', delivered_at = NOW()
                    WHERE brevo_message_id = '$mid'
                      AND status NOT IN ('opened','clicked','spam','bounced')";
            break;
        case 'opened':
            // Kein Downgrade von 'clicked'
            $sql = "UPDATE followup_log
                    SET status = 'opened', opened_at = NOW()
                    WHERE brevo_message_id = '$mid'
                      AND status NOT IN ('clicked','spam','bounced')";
            break;
        case 'hardBounce':
        case 'softBounce':
            $sql = "UPDATE followup_log
                    SET status = 'bounced', bounced_at = NOW()
                    WHERE brevo_message_id = '$mid'";
            break;
        case 'spam':
            $sql = "UPDATE followup_log
                    SET status = 'spam'
                    WHERE brevo_message_id = '$mid'";
            break;
        default:
            return false;
    }
    mysqli_query($link, $sql);
    return mysqli_affected_rows($link) > 0;
}

// ── followup_trigger_log aktualisieren ───────────────────────────────────────
function webhook_update_trigger_log($link, string $mid, string $event): bool {
    switch ($event) {
        case 'delivered':
            $sql = "UPDATE followup_trigger_log
                    SET status = 'delivered'
                    WHERE brevo_message_id = '$mid'
                      AND status NOT IN ('opened','clicked','spam','bounced')";
            break;
        case 'opened':
            $sql = "UPDATE followup_trigger_log
                    SET status = 'opened'
                    WHERE brevo_message_id = '$mid'
                      AND status NOT IN ('clicked','spam','bounced')";
            break;
        case 'hardBounce':
        case 'softBounce':
            $sql = "UPDATE followup_trigger_log
                    SET status = 'bounced'
                    WHERE brevo_message_id = '$mid'";
            break;
        case 'spam':
            $sql = "UPDATE followup_trigger_log
                    SET status = 'spam'
                    WHERE brevo_message_id = '$mid'";
            break;
        default:
            return false;
    }
    mysqli_query($link, $sql);
    return mysqli_affected_rows($link) > 0;
}

// ── lead_events für kritische Events ─────────────────────────────────────────
function webhook_write_lead_event($link, string $mid, string $eventType): void {
    // lead_id aus followup_log oder lead_events ermitteln
    $leadId = 0;
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT user_id FROM followup_log WHERE brevo_message_id = '$mid' LIMIT 1"));
    if ($r) {
        $leadId = (int)$r['user_id'];
    } else {
        $r2 = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT lead_id FROM lead_events WHERE brevo_message_id = '$mid' LIMIT 1"));
        if ($r2) $leadId = (int)$r2['lead_id'];
    }

    if ($leadId > 0) {
        $et  = mysqli_real_escape_string($link, $eventType);
        $meta = mysqli_real_escape_string($link, $mid);
        mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, meta, brevo_message_id)
            VALUES ($leadId, '$et', '$meta', '$mid')");
    }
}

// ── Event verarbeiten ─────────────────────────────────────────────────────────
$foundInLog     = webhook_update_followup_log($link, $mid, $event);
$foundInTrigger = webhook_update_trigger_log($link, $mid, $event);

if (!$foundInLog && !$foundInTrigger) {
    // messageId nicht in Follow-up-Tabellen — könnte Welcome/Transactional Mail sein
    // Trotzdem lead_events aktualisieren falls vorhanden
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT id FROM lead_events WHERE brevo_message_id = '$mid' LIMIT 1"));
    if (!$r) {
        // Wirklich unbekannt — nur loggen, kein 500
        error_log("brevo-webhook: unbekannte messageId '$mid' (event: $event, email: $email)");
    }
}

// Kritische Events als lead_events speichern
if ($event === 'hardBounce') {
    webhook_write_lead_event($link, $mid, 'email_hard_bounce');
}
if ($event === 'spam') {
    webhook_write_lead_event($link, $mid, 'email_spam');
}

// Immer exit ohne Fehler — HTTP 200 wurde oben bereits gesendet
exit();
