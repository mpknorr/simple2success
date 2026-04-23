<?php
session_start();
if (empty($_SESSION["userid"]) || empty($_SESSION["is_admin"])) {
    require_once '../includes/conn.php';
    header("Location: " . $baseurl . "/backoffice/index.php");
    exit();
}
require_once '../includes/conn.php';

$userid = $_SESSION["userid"];
$getuserdetails = mysqli_query($link, "SELECT * FROM users WHERE leadid = $userid");
foreach ($getuserdetails as $userData) {
    $name        = $userData["name"];
    $username    = $userData["username"];
    $useremail   = $userData["email"];
    $paidstatus  = $userData["paidstatus"];
    $profile_pic = $userData["profile_pic"];
}

$success       = '';
$error         = '';
$trigger_result = '';
$success_tracking = '';
$success_leaderboard = '';

// ── Ensure tracking_legal_links table exists (multilingual-ready) ─────────────
mysqli_query($link, "CREATE TABLE IF NOT EXISTS tracking_legal_links (
    lang        VARCHAR(10)  NOT NULL DEFAULT 'en',
    privacy_url VARCHAR(500) NOT NULL DEFAULT '',
    terms_url   VARCHAR(500) NOT NULL DEFAULT '',
    cookie_url  VARCHAR(500) NOT NULL DEFAULT '',
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (lang)
)");

function getSetting($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key = '$k'"));
    return $r ? $r['setting_value'] : '';
}

function saveSetting($link, $key, $value) {
    $k = mysqli_real_escape_string($link, $key);
    $v = mysqli_real_escape_string($link, $value);
    mysqli_query($link, "INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value = '$v'");
}

// ── POST: Save tracking & consent settings ───────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_tracking'])) {
    $allowed_modes = ['auto', 'manual'];

    saveSetting($link, 'cookiebot_enabled',
        isset($_POST['cookiebot_enabled']) ? '1' : '0');
    saveSetting($link, 'cookiebot_cbid',
        preg_replace('/[^a-f0-9\-]/i', '', trim($_POST['cookiebot_cbid'] ?? '')));
    $bm = in_array($_POST['cookiebot_blocking_mode'] ?? '', $allowed_modes, true)
        ? $_POST['cookiebot_blocking_mode'] : 'auto';
    saveSetting($link, 'cookiebot_blocking_mode', $bm);

    saveSetting($link, 'meta_pixel_enabled',
        isset($_POST['meta_pixel_enabled']) ? '1' : '0');
    saveSetting($link, 'meta_pixel_id',
        preg_replace('/\D/', '', trim($_POST['meta_pixel_id'] ?? '')));

    saveSetting($link, 'tiktok_pixel_enabled',
        isset($_POST['tiktok_pixel_enabled']) ? '1' : '0');
    saveSetting($link, 'tiktok_pixel_id',
        preg_replace('/[^A-Z0-9]/i', '', trim($_POST['tiktok_pixel_id'] ?? '')));

    saveSetting($link, 'default_language',
        preg_replace('/[^a-z]/', '', strtolower(trim($_POST['default_language'] ?? 'en'))));

    $priv   = filter_var(trim($_POST['privacy_policy_url_default'] ?? ''), FILTER_SANITIZE_URL);
    $terms  = filter_var(trim($_POST['terms_url_default']          ?? ''), FILTER_SANITIZE_URL);
    $cookie = filter_var(trim($_POST['cookie_policy_url_default']  ?? ''), FILTER_SANITIZE_URL);
    saveSetting($link, 'privacy_policy_url_default', $priv);
    saveSetting($link, 'terms_url_default',          $terms);
    saveSetting($link, 'cookie_policy_url_default',  $cookie);

    // Upsert English row in multilingual legal links table
    $ep = mysqli_real_escape_string($link, $priv);
    $et = mysqli_real_escape_string($link, $terms);
    $ec = mysqli_real_escape_string($link, $cookie);
    mysqli_query($link, "INSERT INTO tracking_legal_links (lang, privacy_url, terms_url, cookie_url)
        VALUES ('en', '$ep', '$et', '$ec')
        ON DUPLICATE KEY UPDATE privacy_url='$ep', terms_url='$et', cookie_url='$ec'");

    $success_tracking = 'Tracking &amp; Consent settings saved.';
}

// ── POST: Save leaderboard (fake-padding) settings ────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_leaderboard'])) {
    saveSetting($link, 'fake_leaderboard_enabled', isset($_POST['fake_leaderboard_enabled']) ? '1' : '0');
    $target = (int)($_POST['fake_leaderboard_target'] ?? 500);
    $target = max(10, min(2000, $target));
    saveSetting($link, 'fake_leaderboard_target', (string)$target);
    $success_leaderboard = 'Leaderboard-Einstellungen gespeichert.';
}

// ── POST: Save SMTP settings ──────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_smtp'])) {
    $fields = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];
    foreach ($fields as $field) {
        saveSetting($link, $field, $_POST[$field] ?? '');
    }
    $success = "E-Mail Einstellungen gespeichert.";
}

// ── POST: Save frontend settings ──────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_frontend'])) {
    $allowed_modes   = ['default', 'maintenance', 'page'];
    $allowed_targets = ['link1', 'link2', 'link3', 'link4', 'linkp1', 'linkp2', 'linkp3', 'linkp4'];

    $mode   = in_array($_POST['homepage_mode'] ?? '', $allowed_modes, true) ? $_POST['homepage_mode'] : 'default';
    $target = in_array($_POST['homepage_target'] ?? '', $allowed_targets, true) ? $_POST['homepage_target'] : '';

    saveSetting($link, 'homepage_mode', $mode);
    saveSetting($link, 'homepage_target', $target);
    $success = "Startseiten-Einstellungen gespeichert.";
}

// ── POST: Manual daily mail trigger ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['trigger_mail'])) {
    $ch = curl_init($baseurl . "/cron/daily_leads.php?token=7ed168b24e108db9a53682af3645256049c2a46b25a9ab43");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code === 200) {
        $trigger_result = "success:" . (empty($response) ? "E-Mails wurden versendet (keine Ausgabe = OK)." : htmlspecialchars($response));
    } else {
        $trigger_result = "error:Fehler beim Aufruf (HTTP $http_code).";
    }
}

// ── Load current values ───────────────────────────────────────────────────────
$smtp_host        = getSetting($link, 'smtp_host');
$smtp_port        = getSetting($link, 'smtp_port');
$smtp_user        = getSetting($link, 'smtp_user');
$smtp_password    = getSetting($link, 'smtp_password');
$smtp_from_email  = getSetting($link, 'smtp_from_email');
$smtp_from_name   = getSetting($link, 'smtp_from_name');
$homepage_mode    = getSetting($link, 'homepage_mode') ?: 'default';
$homepage_target  = getSetting($link, 'homepage_target');

$tr = [
    'cookiebot_enabled'          => getSetting($link, 'cookiebot_enabled'),
    'cookiebot_cbid'             => getSetting($link, 'cookiebot_cbid'),
    'cookiebot_blocking_mode'    => getSetting($link, 'cookiebot_blocking_mode') ?: 'auto',
    'meta_pixel_enabled'         => getSetting($link, 'meta_pixel_enabled'),
    'meta_pixel_id'              => getSetting($link, 'meta_pixel_id'),
    'tiktok_pixel_enabled'       => getSetting($link, 'tiktok_pixel_enabled'),
    'tiktok_pixel_id'            => getSetting($link, 'tiktok_pixel_id'),
    'default_language'           => getSetting($link, 'default_language') ?: 'en',
    'privacy_policy_url_default' => getSetting($link, 'privacy_policy_url_default'),
    'terms_url_default'          => getSetting($link, 'terms_url_default'),
    'cookie_policy_url_default'  => getSetting($link, 'cookie_policy_url_default'),
];

$pages = [
    'Capture Pages' => [
        'link1'  => 'Capture Page 1',
        'link2'  => 'Capture Page 2',
        'link3'  => 'Capture Page 3',
        'link4'  => 'Capture Page 4',
    ],
    'Premium Pages' => [
        'linkp1' => 'Premium Page 1',
        'linkp2' => 'Premium Page 2',
        'linkp3' => 'Premium Page 3',
        'linkp4' => 'Premium Page 4',
    ],
];
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">
<?php require_once "parts/navbar.php"; ?>

<div class="wrapper">
<?php require_once "parts/sidebar.php"; ?>

<div class="main-panel">
<div class="main-content">
<div class="content-overlay"></div>
<div class="content-wrapper">

    <div class="content-header row mb-2">
        <div class="content-header-left col-12">
            <h3 class="content-header-title"><i class="ft-settings mr-1"></i> System Einstellungen</h3>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ft-check-circle mr-1"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($success_tracking): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ft-check-circle mr-1"></i> <?= $success_tracking ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($success_leaderboard): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ft-check-circle mr-1"></i> <?= $success_leaderboard ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ft-alert-circle mr-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- Section A: E-Mail / SMTP                                              -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-left:4px solid #cb2ebc;">
                    <h4 class="card-title m-0"><i class="ft-mail mr-1" style="color:#cb2ebc;"></i> E-Mail / SMTP Einstellungen (Brevo)</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-7 col-12">
            <div class="card">
                <div class="card-content">
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">SMTP Host</label>
                                <div class="col-sm-8">
                                    <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($smtp_host) ?>" placeholder="smtp-relay.brevo.com">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">SMTP Port</label>
                                <div class="col-sm-8">
                                    <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($smtp_port) ?>" placeholder="587">
                                    <small class="text-muted">587 = STARTTLS (empfohlen für Brevo)</small>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">SMTP Username</label>
                                <div class="col-sm-8">
                                    <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($smtp_user) ?>" placeholder="deine@email.com">
                                    <small class="text-muted">Dein Brevo-Login (E-Mail Adresse)</small>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">SMTP Passwort / API Key</label>
                                <div class="col-sm-8">
                                    <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($smtp_password) ?>" placeholder="Brevo API Key">
                                    <small class="text-muted">In Brevo unter: SMTP &amp; API → API Keys</small>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Absender E-Mail</label>
                                <div class="col-sm-8">
                                    <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($smtp_from_email) ?>" placeholder="info@simple2success.com">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Absender Name</label>
                                <div class="col-sm-8">
                                    <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($smtp_from_name) ?>" placeholder="Simple2Success">
                                </div>
                            </div>
                            <button type="submit" name="save_smtp" class="btn btn-primary">
                                <i class="ft-save mr-1"></i> SMTP Einstellungen speichern
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <!-- Manual Trigger -->
            <div class="card">
                <div class="card-header"><h4 class="card-title"><i class="ft-send mr-1"></i> E-Mail manuell auslösen</h4></div>
                <div class="card-content">
                    <div class="card-body">
                        <p>Sendet die tägliche Benachrichtigungs-E-Mail an alle Sponsoren, die heute neue Leads erhalten haben.</p>

                        <?php if ($trigger_result):
                            $t_type = strpos($trigger_result, 'success:') === 0 ? 'success' : 'danger';
                            $t_msg  = substr($trigger_result, strpos($trigger_result, ':') + 1);
                        ?>
                        <div class="alert alert-<?= $t_type ?>"><?= $t_msg ?></div>
                        <?php endif; ?>

                        <form method="POST" onsubmit="return confirm('Jetzt E-Mails versenden?');">
                            <button type="submit" name="trigger_mail" class="btn btn-warning btn-block">
                                <i class="ft-send mr-1"></i> Tages-E-Mails jetzt senden
                            </button>
                        </form>
                        <hr>
                        <div class="font-small-3">
                            <strong>Cronjobs (auf dem Produktiv-Server einrichten):</strong>
                            <ol class="pl-3 mt-2 mb-0" style="list-style:decimal;">
                                <li class="mb-2">
                                    <strong>Follow-UP Daily Leads</strong> &mdash; tägliche Sponsor-Benachrichtigung um 20:00<br>
                                    <code style="display:block;padding:4px 6px;background:rgba(0,0,0,.2);border-radius:3px;word-break:break-all;">0 20 * * * curl -L "<?= $baseurl ?>/cron/daily_leads.php?token=7ed168b24e108db9a53682af3645256049c2a46b25a9ab43"</code>
                                </li>
                                <li class="mb-2">
                                    <strong>Follow-UP Leads-Sequenz</strong> &mdash; Tag-X-Sequenzen + Verhaltens-Trigger (täglich 09:00)<br>
                                    <code style="display:block;padding:4px 6px;background:rgba(0,0,0,.2);border-radius:3px;word-break:break-all;">0 9 * * * curl -L "<?= $baseurl ?>/cron/followup.php?token=ca246a9152344e988c9eb30c9f05460e60c518b01949351e"</code>
                                </li>
                                <li class="mb-0">
                                    <strong>Follow-UP Member-Sequenz</strong> &mdash; Step-4-Conversion<br>
                                    <small class="text-muted">Wird vom selben <code>followup.php</code>-Endpoint mitverarbeitet (Target=member). Kein separater Cron nötig.</small>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brevo Info -->
            <div class="card">
                <div class="card-header"><h4 class="card-title"><i class="ft-info mr-1"></i> Brevo Setup</h4></div>
                <div class="card-content">
                    <div class="card-body font-small-3">
                        <ol>
                            <li>Anmelden auf <strong>brevo.com</strong></li>
                            <li>Gehe zu: <em>SMTP &amp; API → API Keys</em></li>
                            <li>API Key kopieren → oben als Passwort eintragen</li>
                            <li>SMTP Host: <code>smtp-relay.brevo.com</code></li>
                            <li>Port: <code>587</code></li>
                            <li>Absender-E-Mail muss in Brevo verifiziert sein</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /row SMTP -->

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- Section B: Frontend / Startseite                                      -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-left:4px solid #1877F2;">
                    <h4 class="card-title m-0"><i class="ft-globe mr-1" style="color:#1877F2;"></i> Startseiten-Einstellung (Frontend)</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-7 col-12">
            <div class="card">
                <div class="card-content">
                    <div class="card-body">
                        <p class="text-muted">Steuert, was beim direkten Aufruf der Startseite (<code><?= htmlspecialchars($baseurl) ?>/</code>) angezeigt wird.</p>
                        <form method="POST" id="frontendForm">
                            <div class="form-group">
                                <label><strong>Anzeigemodus</strong></label>
                                <div class="custom-control custom-radio mt-1">
                                    <input type="radio" class="custom-control-input" id="mode_default" name="homepage_mode" value="default" <?= $homepage_mode === 'default' ? 'checked' : '' ?> onchange="updateTargetVisibility()">
                                    <label class="custom-control-label" for="mode_default">
                                        <strong>Standard</strong> — bisheriges Verhalten (index.html.bak)
                                    </label>
                                </div>
                                <div class="custom-control custom-radio mt-1">
                                    <input type="radio" class="custom-control-input" id="mode_maintenance" name="homepage_mode" value="maintenance" <?= $homepage_mode === 'maintenance' ? 'checked' : '' ?> onchange="updateTargetVisibility()">
                                    <label class="custom-control-label" for="mode_maintenance">
                                        <strong>Maintenance-Seite</strong> — zeigt eine "Wir sind gleich zurück"-Seite
                                    </label>
                                </div>
                                <div class="custom-control custom-radio mt-1">
                                    <input type="radio" class="custom-control-input" id="mode_page" name="homepage_mode" value="page" <?= $homepage_mode === 'page' ? 'checked' : '' ?> onchange="updateTargetVisibility()">
                                    <label class="custom-control-label" for="mode_page">
                                        <strong>Bestimmte Seite anzeigen</strong> — leite auf eine Capture / Premium Page weiter
                                    </label>
                                </div>
                            </div>

                            <div class="form-group" id="targetSelector" style="display:<?= $homepage_mode === 'page' ? 'block' : 'none' ?>;">
                                <label><strong>Zielseite auswählen</strong> <span class="text-danger">*</span></label>
                                <select name="homepage_target" class="form-control" id="homepage_target">
                                    <option value="">— bitte wählen —</option>
                                    <?php foreach ($pages as $group => $items): ?>
                                    <optgroup label="<?= htmlspecialchars($group) ?>">
                                        <?php foreach ($items as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $homepage_target === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Es wird <strong>kein Ref-Link</strong> verwendet. Kommt ein Lead über diese Seite, greift der Rotator.</small>
                            </div>

                            <button type="submit" name="save_frontend" class="btn btn-primary">
                                <i class="ft-save mr-1"></i> Startseiten-Einstellung speichern
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="card" style="background:#1a1a2e;">
                <div class="card-body">
                    <h5 style="color:#cb2ebc;"><i class="ft-info"></i> Verhalten der Modi</h5>
                    <table class="table table-sm mb-0" style="color:#ccc;font-size:13px;">
                        <tbody>
                            <tr>
                                <td><span class="badge" style="background:#555;">Standard</span></td>
                                <td>Zeigt die Standard-Startseite (index.html.bak)</td>
                            </tr>
                            <tr>
                                <td><span class="badge" style="background:#e67e22;">Maintenance</span></td>
                                <td>Zeigt die Wartungsseite — Besucher sehen keinen Content</td>
                            </tr>
                            <tr>
                                <td><span class="badge" style="background:#1877F2;">Seite</span></td>
                                <td>Weiterleitung auf gewählte Capture/Premium Page ohne Ref-Link. Neuer Lead wird automatisch via Rotator zugewiesen.</td>
                            </tr>
                        </tbody>
                    </table>
                    <hr style="border-color:#333;">
                    <p style="color:#aaa;font-size:12px;">
                        <strong style="color:#fff;">Aktuell aktiv:</strong><br>
                        Modus: <span style="color:#cb2ebc;"><?= htmlspecialchars($homepage_mode) ?></span><br>
                        <?php if ($homepage_mode === 'page' && $homepage_target): ?>
                        Ziel: <span style="color:#cb2ebc;"><?= htmlspecialchars($pages['Capture Pages'][$homepage_target] ?? $pages['Premium Pages'][$homepage_target] ?? $homepage_target) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div><!-- /row Frontend -->

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- Section C: Tracking & Consent                                          -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-left:4px solid #00c8e0;">
                    <h4 class="card-title m-0">
                        <i class="ft-shield mr-1" style="color:#00c8e0;"></i>
                        Tracking &amp; Consent
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <form method="POST">
    <div class="row">

        <!-- LEFT: Cookiebot + Meta + TikTok -->
        <div class="col-lg-8 col-12">

            <!-- Cookiebot -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="ft-shield mr-1" style="color:#00c8e0;"></i>
                        Cookiebot — GDPR Cookie Consent
                    </h5>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="alert" style="background:rgba(0,200,224,.08);border:1px solid rgba(0,200,224,.3);color:#ccc;font-size:12px;">
                            <i class="ft-info mr-1" style="color:#00c8e0;"></i>
                            Cookiebot is loaded <strong>before</strong> all other tracking scripts to ensure legal compliance.
                            Language detection is automatic — fully compatible with the planned multilingual expansion.
                        </div>
                        <div class="form-group row align-items-center">
                            <label class="col-sm-4 col-form-label">Enable Cookiebot</label>
                            <div class="col-sm-8">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="cookiebot_enabled"
                                           name="cookiebot_enabled" value="1"
                                           <?= $tr['cookiebot_enabled'] === '1' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="cookiebot_enabled">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Cookiebot CBID</label>
                            <div class="col-sm-8">
                                <input type="text" name="cookiebot_cbid" class="form-control"
                                       value="<?= htmlspecialchars($tr['cookiebot_cbid'], ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. 98933ef8-ebc1-453e-a76e-f5984919d07c"
                                       maxlength="36">
                                <small class="text-muted">Found in your Cookiebot dashboard under "Domains".</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Blocking Mode</label>
                            <div class="col-sm-8">
                                <select name="cookiebot_blocking_mode" class="form-control">
                                    <option value="auto"   <?= $tr['cookiebot_blocking_mode'] === 'auto'   ? 'selected' : '' ?>>auto (recommended)</option>
                                    <option value="manual" <?= $tr['cookiebot_blocking_mode'] === 'manual' ? 'selected' : '' ?>>manual</option>
                                </select>
                                <small class="text-muted"><strong>auto</strong> = Cookiebot automatically blocks scripts until consent is given.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Meta Pixel -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="ft-facebook mr-1" style="color:#1877F2;"></i>
                        Meta Pixel — Facebook &amp; Instagram
                    </h5>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="alert" style="background:rgba(24,119,242,.08);border:1px solid rgba(24,119,242,.3);color:#ccc;font-size:12px;">
                            <i class="ft-info mr-1" style="color:#1877F2;"></i>
                            Loaded only on public landing pages. Fires <strong>PageView</strong> automatically.
                            A <strong>Lead</strong> event can be added on successful registration.
                        </div>
                        <div class="form-group row align-items-center">
                            <label class="col-sm-4 col-form-label">Enable Meta Pixel</label>
                            <div class="col-sm-8">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="meta_pixel_enabled"
                                           name="meta_pixel_enabled" value="1"
                                           <?= $tr['meta_pixel_enabled'] === '1' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="meta_pixel_enabled">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Meta Pixel ID</label>
                            <div class="col-sm-8">
                                <input type="text" name="meta_pixel_id" class="form-control"
                                       value="<?= htmlspecialchars($tr['meta_pixel_id'], ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. 123456789012345"
                                       maxlength="20" pattern="\d*">
                                <small class="text-muted">Found in Meta Business Manager → Events Manager → Pixels.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TikTok Pixel -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="ft-tv mr-1" style="color:#ff0050;"></i>
                        TikTok Pixel
                    </h5>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="alert" style="background:rgba(255,0,80,.08);border:1px solid rgba(255,0,80,.3);color:#ccc;font-size:12px;">
                            <i class="ft-info mr-1" style="color:#ff0050;"></i>
                            Loaded only on public landing pages. Fires <strong>PageView</strong> automatically.
                            A <strong>CompleteRegistration</strong> event can be added on successful registration.
                        </div>
                        <div class="form-group row align-items-center">
                            <label class="col-sm-4 col-form-label">Enable TikTok Pixel</label>
                            <div class="col-sm-8">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="tiktok_pixel_enabled"
                                           name="tiktok_pixel_enabled" value="1"
                                           <?= $tr['tiktok_pixel_enabled'] === '1' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="tiktok_pixel_enabled">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">TikTok Pixel ID</label>
                            <div class="col-sm-8">
                                <input type="text" name="tiktok_pixel_id" class="form-control"
                                       value="<?= htmlspecialchars($tr['tiktok_pixel_id'], ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. C8A1B2C3D4E5F6G7H8I9"
                                       maxlength="40">
                                <small class="text-muted">Found in TikTok Ads Manager → Assets → Events → Web Events.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-8 -->

        <!-- RIGHT: Legal / Multilingual Prep + Save -->
        <div class="col-lg-4 col-12">

            <!-- Legal / Multilingual -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="ft-globe mr-1" style="color:#cb2ebc;"></i>
                        Legal &amp; Multilingual Prep
                    </h5>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="alert" style="background:rgba(203,46,188,.08);border:1px solid rgba(203,46,188,.3);color:#ccc;font-size:12px;">
                            <i class="ft-globe mr-1" style="color:#cb2ebc;"></i>
                            These are the default (English) legal URLs. When the multilingual system is activated,
                            each language will have its own URLs stored in the <code>tracking_legal_links</code> table.
                        </div>
                        <div class="form-group">
                            <label>Default Language</label>
                            <select name="default_language" class="form-control">
                                <option value="en" <?= $tr['default_language'] === 'en' ? 'selected' : '' ?>>English (en)</option>
                                <option value="de" <?= $tr['default_language'] === 'de' ? 'selected' : '' ?>>Deutsch (de)</option>
                                <option value="fr" <?= $tr['default_language'] === 'fr' ? 'selected' : '' ?>>Français (fr)</option>
                                <option value="es" <?= $tr['default_language'] === 'es' ? 'selected' : '' ?>>Español (es)</option>
                            </select>
                            <small class="text-muted">Fallback language when no match found.</small>
                        </div>
                        <div class="form-group">
                            <label>Privacy Policy URL <small class="text-muted">(Default/EN)</small></label>
                            <input type="url" name="privacy_policy_url_default" class="form-control"
                                   value="<?= htmlspecialchars($tr['privacy_policy_url_default'], ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="https://simple2success.com/privacy">
                        </div>
                        <div class="form-group">
                            <label>Terms of Service URL <small class="text-muted">(Default/EN)</small></label>
                            <input type="url" name="terms_url_default" class="form-control"
                                   value="<?= htmlspecialchars($tr['terms_url_default'], ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="https://simple2success.com/terms">
                        </div>
                        <div class="form-group">
                            <label>Cookie Policy URL <small class="text-muted">(Default/EN)</small></label>
                            <input type="url" name="cookie_policy_url_default" class="form-control"
                                   value="<?= htmlspecialchars($tr['cookie_policy_url_default'], ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="https://simple2success.com/cookies">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save button -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" name="save_tracking" class="btn btn-primary btn-block">
                        <i class="ft-save mr-1"></i> Save Tracking &amp; Consent
                    </button>
                    <small class="text-muted d-block mt-2 text-center">
                        Tracking scripts load only on public landing pages — never in Admin or Backoffice.
                    </small>
                </div>
            </div>

        </div><!-- /col-lg-4 -->
    </div><!-- /row Tracking -->
    </form>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- Section D: Leaderboard Gamification (Fake-Padding)                     -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <?php
    $lbRealCount = (int)(mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS c FROM users WHERE username IS NOT NULL AND username != ''"))['c'] ?? 0);
    $lbEnabledVal = getSetting($link, 'fake_leaderboard_enabled');
    $lbTargetVal  = (int)(getSetting($link, 'fake_leaderboard_target') ?: 500);
    ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-left:4px solid #b700e0;">
                    <h4 class="card-title m-0"><i class="ft-award mr-1" style="color:#b700e0;"></i> Leaderboard Gamification</h4>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="fake_leaderboard_enabled" name="fake_leaderboard_enabled" value="1" <?= $lbEnabledVal === '1' ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="fake_leaderboard_enabled">Fake-Leaderboard aktivieren</label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Füllt das Leaderboard mit simulierten Nutzern auf, bis die Zielgröße an echten Step-2-Membern erreicht ist. Simulierte Nutzer werden <strong>nicht</strong> in der Datenbank gespeichert.
                                        </small>
                                    </div>
                                    <div class="form-group">
                                        <label>Zielgröße (Minimale Anzahl angezeigter Member)</label>
                                        <input type="number" name="fake_leaderboard_target" class="form-control" value="<?= $lbTargetVal ?>" min="10" max="2000">
                                        <small class="text-muted">Erlaubt: 10–2000. Default: 500.</small>
                                    </div>
                                    <button type="submit" name="save_leaderboard" class="btn btn-primary">
                                        <i class="ft-save mr-1"></i> Speichern
                                    </button>
                                </div>
                                <div class="col-lg-6">
                                    <div class="alert" style="background:rgba(183,0,224,0.08); border-left:3px solid #b700e0;">
                                        <h5 style="color:#b700e0; margin-top:0;"><i class="ft-info mr-1"></i> Status</h5>
                                        <p class="mb-1"><strong>Echte Step-2-Member:</strong> <?= $lbRealCount ?></p>
                                        <p class="mb-1"><strong>Aktuelle Zielgröße:</strong> <?= $lbTargetVal ?></p>
                                        <p class="mb-0"><strong>Fakes werden angezeigt:</strong>
                                            <?php if ($lbEnabledVal === '1' && $lbRealCount < $lbTargetVal): ?>
                                                <span style="color:#2ecc71;">Ja — <?= max(0, $lbTargetVal - $lbRealCount) ?> Fakes</span>
                                            <?php else: ?>
                                                <span style="color:#888;">Nein</span>
                                            <?php endif; ?>
                                        </p>
                                        <hr style="border-color:rgba(183,0,224,0.2);">
                                        <small class="text-muted d-block">
                                            Ab <strong><?= $lbTargetVal ?></strong> echten Membern schaltet sich die Fake-Anzeige automatisch ab — unabhängig vom Toggle.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /row Leaderboard -->

</div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php require_once "../backoffice/parts/footer.php"; ?>
<button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
</div><!-- /main-panel -->
</div><!-- /wrapper -->

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<script src="../backoffice/app-assets/vendors/js/vendors.min.js"></script>
<script src="../backoffice/app-assets/js/core/app-menu.js"></script>
<script src="../backoffice/app-assets/js/core/app.js"></script>
<script src="../backoffice/app-assets/js/notification-sidebar.js"></script>
<script src="../backoffice/app-assets/js/scroll-top.js"></script>
<script src="../backoffice/assets/js/scripts.js"></script>

<script>
function updateTargetVisibility() {
    var mode = document.querySelector('input[name="homepage_mode"]:checked').value;
    var sel  = document.getElementById('targetSelector');
    var tgt  = document.getElementById('homepage_target');
    if (mode === 'page') {
        sel.style.display = 'block';
        tgt.setAttribute('required', 'required');
    } else {
        sel.style.display = 'none';
        tgt.removeAttribute('required');
    }
}
</script>
</body>
</html>
