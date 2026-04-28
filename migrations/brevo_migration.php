<?php
/**
 * Brevo Migration — einmalig ausführen
 * CLI:  php migrations/brevo_migration.php
 * HTTP: https://yourdomain.com/migrations/brevo_migration.php?token=<admin_token>
 *
 * Rollback-SQL (bei Bedarf manuell ausführen):
 *   ALTER TABLE followup_log
 *     DROP INDEX idx_brevo_msg_id,
 *     DROP COLUMN brevo_message_id,
 *     DROP COLUMN status,
 *     DROP COLUMN delivered_at,
 *     DROP COLUMN opened_at,
 *     DROP COLUMN bounced_at,
 *     DROP COLUMN failed_at;
 *   ALTER TABLE lead_events
 *     DROP INDEX idx_brevo_evt,
 *     DROP COLUMN brevo_message_id;
 *   ALTER TABLE followup_trigger_log
 *     DROP COLUMN brevo_message_id,
 *     DROP COLUMN status;
 *   DELETE FROM settings WHERE setting_key IN ('brevo_api_key','brevo_webhook_token');
 */

// ── Zugriffsschutz ────────────────────────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    // Im Browser nur mit Admin-Session erreichbar
    session_start();
    if (empty($_SESSION['userid']) || empty($_SESSION['is_admin'])) {
        http_response_code(403);
        echo 'Unauthorized';
        exit();
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../includes/conn.php';

$log = [];

function migrate_log(string $msg): void {
    global $log;
    $log[] = $msg;
    echo $msg . "\n";
    flush();
}

function column_exists($link, string $table, string $column): bool {
    $t = mysqli_real_escape_string($link, $table);
    $c = mysqli_real_escape_string($link, $column);
    $res = mysqli_query($link, "SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $res && mysqli_num_rows($res) > 0;
}

function index_exists($link, string $table, string $index): bool {
    $t = mysqli_real_escape_string($link, $table);
    $i = mysqli_real_escape_string($link, $index);
    $res = mysqli_query($link, "SHOW INDEX FROM `$t` WHERE Key_name = '$i'");
    return $res && mysqli_num_rows($res) > 0;
}

function run_sql($link, string $sql, string $description): void {
    if (mysqli_query($link, $sql)) {
        migrate_log("  ✓ $description");
    } else {
        migrate_log("  ✗ $description — " . mysqli_error($link));
    }
}

migrate_log("=== Brevo Migration Start ===");
migrate_log(date('Y-m-d H:i:s'));
migrate_log('');

// ── 1. settings: brevo_api_key ────────────────────────────────────────────────
migrate_log("--- settings ---");
run_sql($link,
    "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('brevo_api_key', '')",
    "brevo_api_key hinzugefügt (leer — bitte im Admin eintragen)"
);

// Webhook-Token generieren falls noch nicht vorhanden
$existing = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT setting_value FROM settings WHERE setting_key = 'brevo_webhook_token'"));
if (!$existing) {
    $token = bin2hex(random_bytes(32));
    run_sql($link,
        "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('brevo_webhook_token', '$token')",
        "brevo_webhook_token generiert: $token"
    );
    migrate_log("  !! Webhook-URL: https://simple2success.com/includes/brevo-webhook.php?token=$token");
} else {
    migrate_log("  ✓ brevo_webhook_token bereits vorhanden");
}

migrate_log('');

// ── 2. followup_log erweitern ─────────────────────────────────────────────────
migrate_log("--- followup_log ---");

if (!column_exists($link, 'followup_log', 'brevo_message_id')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN brevo_message_id VARCHAR(255) NULL AFTER sent_at",
        "brevo_message_id hinzugefügt"
    );
} else {
    migrate_log("  ✓ brevo_message_id bereits vorhanden");
}

if (!column_exists($link, 'followup_log', 'status')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'sent' AFTER brevo_message_id",
        "status hinzugefügt"
    );
} else {
    migrate_log("  ✓ status bereits vorhanden");
}

if (!column_exists($link, 'followup_log', 'delivered_at')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN delivered_at TIMESTAMP NULL AFTER status",
        "delivered_at hinzugefügt"
    );
} else {
    migrate_log("  ✓ delivered_at bereits vorhanden");
}

if (!column_exists($link, 'followup_log', 'opened_at')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN opened_at TIMESTAMP NULL AFTER delivered_at",
        "opened_at hinzugefügt"
    );
} else {
    migrate_log("  ✓ opened_at bereits vorhanden");
}

if (!column_exists($link, 'followup_log', 'bounced_at')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN bounced_at TIMESTAMP NULL AFTER opened_at",
        "bounced_at hinzugefügt"
    );
} else {
    migrate_log("  ✓ bounced_at bereits vorhanden");
}

if (!column_exists($link, 'followup_log', 'failed_at')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN failed_at TIMESTAMP NULL AFTER bounced_at",
        "failed_at hinzugefügt"
    );
} else {
    migrate_log("  ✓ failed_at bereits vorhanden");
}

if (!index_exists($link, 'followup_log', 'idx_brevo_msg_id')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD INDEX idx_brevo_msg_id (brevo_message_id)",
        "Index idx_brevo_msg_id hinzugefügt"
    );
} else {
    migrate_log("  ✓ Index idx_brevo_msg_id bereits vorhanden");
}

migrate_log('');

// ── 3. lead_events erweitern ──────────────────────────────────────────────────
migrate_log("--- lead_events ---");

if (!column_exists($link, 'lead_events', 'brevo_message_id')) {
    run_sql($link,
        "ALTER TABLE lead_events ADD COLUMN brevo_message_id VARCHAR(255) NULL AFTER meta",
        "brevo_message_id hinzugefügt"
    );
} else {
    migrate_log("  ✓ brevo_message_id bereits vorhanden");
}

if (!index_exists($link, 'lead_events', 'idx_brevo_evt')) {
    run_sql($link,
        "ALTER TABLE lead_events ADD INDEX idx_brevo_evt (brevo_message_id)",
        "Index idx_brevo_evt hinzugefügt"
    );
} else {
    migrate_log("  ✓ Index idx_brevo_evt bereits vorhanden");
}

migrate_log('');

// ── 4. followup_trigger_log erweitern ─────────────────────────────────────────
migrate_log("--- followup_trigger_log ---");

if (!column_exists($link, 'followup_trigger_log', 'brevo_message_id')) {
    run_sql($link,
        "ALTER TABLE followup_trigger_log ADD COLUMN brevo_message_id VARCHAR(255) NULL AFTER sent_at",
        "brevo_message_id hinzugefügt"
    );
} else {
    migrate_log("  ✓ brevo_message_id bereits vorhanden");
}

if (!column_exists($link, 'followup_trigger_log', 'status')) {
    run_sql($link,
        "ALTER TABLE followup_trigger_log ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'sent' AFTER brevo_message_id",
        "status hinzugefügt"
    );
} else {
    migrate_log("  ✓ status bereits vorhanden");
}

migrate_log('');

// ── 5. followup_log: spam_at + bounce_type ────────────────────────────────────
migrate_log("--- followup_log: spam_at + bounce_type ---");

if (!column_exists($link, 'followup_log', 'spam_at')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN spam_at TIMESTAMP NULL AFTER bounced_at",
        "spam_at hinzugefügt"
    );
} else {
    migrate_log("  ✓ spam_at bereits vorhanden");
}

if (!column_exists($link, 'followup_log', 'bounce_type')) {
    run_sql($link,
        "ALTER TABLE followup_log ADD COLUMN bounce_type VARCHAR(10) NULL AFTER spam_at",
        "bounce_type hinzugefügt"
    );
} else {
    migrate_log("  ✓ bounce_type bereits vorhanden");
}

migrate_log('');
migrate_log("=== Migration abgeschlossen ===");
