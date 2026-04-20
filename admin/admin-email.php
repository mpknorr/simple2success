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
    $name = $userData["name"];
    $username = $userData["username"];
    $useremail = $userData["email"];
    $paidstatus = $userData["paidstatus"];
    $profile_pic = $userData["profile_pic"];
}

$success = '';
$error = '';
$trigger_result = '';

// Einstellungen laden
function getSetting($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key = '$k'"));
    return $r ? $r['setting_value'] : '';
}

// Einstellungen speichern
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_settings'])) {
    $fields = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];
    foreach ($fields as $field) {
        $val = mysqli_real_escape_string($link, $_POST[$field]);
        mysqli_query($link, "INSERT INTO settings (setting_key, setting_value) VALUES ('$field', '$val') ON DUPLICATE KEY UPDATE setting_value = '$val'");
    }
    $success = "E-Mail Einstellungen gespeichert.";
}

// Manueller Trigger
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['trigger_mail'])) {
    $ch = curl_init($baseurl . "/cron/daily_leads.php?token=CHANGE_ME_DAILY_TOKEN");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code === 200) {
        $trigger_result = "success:" . (empty($response) ? "E-Mails wurden versendet (keine Ausgabe = OK)." : htmlspecialchars($response));
    } else {
        $trigger_result = "error:Fehler beim Aufruf (HTTP $http_code).";
    }
}

// Werte laden
$smtp_host       = getSetting($link, 'smtp_host');
$smtp_port       = getSetting($link, 'smtp_port');
$smtp_user       = getSetting($link, 'smtp_user');
$smtp_password   = getSetting($link, 'smtp_password');
$smtp_from_email = getSetting($link, 'smtp_from_email');
$smtp_from_name  = getSetting($link, 'smtp_from_name');
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
                    <h3 class="content-header-title">Admin — E-Mail Einstellungen</h3>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- SMTP Einstellungen -->
                <div class="col-lg-7 col-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title"><i class="ft-settings mr-1"></i> Brevo SMTP Einstellungen</h4></div>
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
                                            <small class="text-muted">In Brevo unter: SMTP & API → API Keys</small>
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
                                    <button type="submit" name="save_settings" class="btn btn-primary">
                                        <i class="ft-save mr-1"></i> Einstellungen speichern
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manueller Trigger -->
                <div class="col-lg-5 col-12">
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
                                <p class="text-muted font-small-3">
                                    <strong>Hinweis:</strong> Normalerweise wird diese Funktion automatisch per Cron-Job ausgeführt:<br>
                                    <code>0 20 * * * curl "<?= $baseurl ?>/cron/daily_leads.php?token=CHANGE_ME_DAILY_TOKEN"</code>
                                </p>
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
                                    <li>Gehe zu: <em>SMTP & API → API Keys</em></li>
                                    <li>API Key kopieren → oben als Passwort eintragen</li>
                                    <li>SMTP Host: <code>smtp-relay.brevo.com</code></li>
                                    <li>Port: <code>587</code></li>
                                    <li>Absender-E-Mail muss in Brevo verifiziert sein</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php require_once "../backoffice/parts/footer.php"; ?>
    <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
</div>
</div>
<div class="sidenav-overlay"></div>
<div class="drag-target"></div>
<script src="../backoffice/app-assets/vendors/js/vendors.min.js"></script>
<script src="../backoffice/app-assets/js/core/app-menu.js"></script>
<script src="../backoffice/app-assets/js/core/app.js"></script>
<script src="../backoffice/app-assets/js/notification-sidebar.js"></script>
<script src="../backoffice/app-assets/js/scroll-top.js"></script>
<script src="../backoffice/assets/js/scripts.js"></script>
</body>
</html>
