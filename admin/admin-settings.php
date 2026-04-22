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
    $allowed_targets = ['link1', 'link2', 'link3', 'linkp1', 'linkp2', 'linkp3'];

    $mode   = in_array($_POST['homepage_mode'] ?? '', $allowed_modes, true) ? $_POST['homepage_mode'] : 'default';
    $target = in_array($_POST['homepage_target'] ?? '', $allowed_targets, true) ? $_POST['homepage_target'] : '';

    saveSetting($link, 'homepage_mode', $mode);
    saveSetting($link, 'homepage_target', $target);
    $success = "Startseiten-Einstellungen gespeichert.";
}

// ── POST: Manual daily mail trigger ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['trigger_mail'])) {
    $ch = curl_init($baseurl . "/cron/daily_leads.php?token=CHANGE_ME_DAILY_TOKEN");
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

$pages = [
    'Capture Pages' => [
        'link1'  => 'Capture Page 1',
        'link2'  => 'Capture Page 2',
        'link3'  => 'Capture Page 3',
    ],
    'Premium Pages' => [
        'linkp1' => 'Premium Page 1',
        'linkp2' => 'Premium Page 2',
        'linkp3' => 'Premium Page 3',
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
                                    <code style="display:block;padding:4px 6px;background:rgba(0,0,0,.2);border-radius:3px;word-break:break-all;">0 20 * * * curl "<?= $baseurl ?>/cron/daily_leads.php?token=CHANGE_ME_DAILY_TOKEN"</code>
                                </li>
                                <li class="mb-2">
                                    <strong>Follow-UP Leads-Sequenz</strong> &mdash; Step-2-Conversion (alle 15 Min.)<br>
                                    <code style="display:block;padding:4px 6px;background:rgba(0,0,0,.2);border-radius:3px;word-break:break-all;">*/15 * * * * curl "<?= $baseurl ?>/cron/followup.php?token=CHANGE_ME_FOLLOWUP_TOKEN"</code>
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
