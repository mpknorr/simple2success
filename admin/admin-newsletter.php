<?php
session_start();
if (empty($_SESSION["userid"]) || empty($_SESSION["is_admin"])) {
    require_once '../includes/conn.php';
    header("Location: " . $baseurl . "/backoffice/index.php");
    exit();
}
require_once '../includes/conn.php';
require_once '../includes/sendNewsletter.php';

$userid = $_SESSION["userid"];
$getuserdetails = mysqli_query($link, "SELECT * FROM users WHERE leadid = $userid");
foreach ($getuserdetails as $userData) {
    $name        = $userData["name"];
    $username    = $userData["username"];
    $useremail   = $userData["email"];
    $paidstatus  = $userData["paidstatus"];
    $profile_pic = $userData["profile_pic"];
}

// Create tables
mysqli_query($link, "CREATE TABLE IF NOT EXISTS newsletters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    target ENUM('all','members','leads') NOT NULL,
    total_sent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL
)");
mysqli_query($link, "CREATE TABLE IF NOT EXISTS newsletter_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Auto-insert default template if none exist
$tpl_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM newsletter_templates"))['c'];
if ($tpl_count == 0) {
    $bannerUrl  = 'https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';
    $siteUrl    = 'https://www.simple2success.com';
    $defaultBody = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;">
  <tr><td align="center" style="padding:20px 0;">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <tr><td><img src="' . $bannerUrl . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>
      <tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.6;">
        <h2 style="color:#cb2ebc;margin-top:0;">Hello {{name}}!</h2>
        <p>Your newsletter content goes here. Use {{name}} and {{email}} as personalization placeholders.</p>
        <p>Add your message, announcements, or updates here.</p>
        <div style="text-align:center;margin:30px 0;">
          <a href="' . $siteUrl . '" style="background:#cb2ebc;color:white;text-decoration:none;padding:14px 32px;border-radius:6px;font-size:16px;font-weight:bold;display:inline-block;">
            Visit Simple2Success
          </a>
        </div>
      </td></tr>
      <tr><td style="background:#1a1a2e;padding:20px 40px;text-align:center;">
        <p style="color:#aaa;font-size:12px;margin:0;">
          &copy; ' . date('Y') . ' Simple2Success. All rights reserved.<br>
          <a href="' . $siteUrl . '" style="color:#cb2ebc;text-decoration:none;">' . $siteUrl . '</a>
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>';
    $esc_name    = mysqli_real_escape_string($link, 'Standard Newsletter');
    $esc_subject = mysqli_real_escape_string($link, 'Important Update from Simple2Success');
    $esc_body    = mysqli_real_escape_string($link, $defaultBody);
    mysqli_query($link, "INSERT INTO newsletter_templates (name, subject, body) VALUES ('$esc_name','$esc_subject','$esc_body')");
}

$success  = '';
$error    = '';
$result   = null;
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['compose','templates','log']) ? $_GET['tab'] : 'compose';

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_newsletter') {
        $subject = trim($_POST['subject'] ?? '');
        $body    = $_POST['body'] ?? '';
        $target  = in_array($_POST['target'] ?? '', ['all','members','leads']) ? $_POST['target'] : 'all';
        if (!$subject || !$body) {
            $error = 'Subject and body are required.';
        } else {
            $result  = sendNewsletter($link, $subject, $body, $target);
            $success = "Newsletter sent to {$result['sent']} recipient(s).";
            if (!empty($result['errors'])) {
                $error = 'Some errors: ' . implode(', ', array_slice($result['errors'], 0, 3));
            }
        }
        $activeTab = 'compose';
    }

    if ($action === 'save_template') {
        $tname   = mysqli_real_escape_string($link, trim($_POST['tpl_name'] ?? ''));
        $subject = mysqli_real_escape_string($link, trim($_POST['subject'] ?? ''));
        $body    = mysqli_real_escape_string($link, $_POST['body'] ?? '');
        $tid     = (int)($_POST['tpl_id'] ?? 0);
        if (!$tname || !$subject || !$body) {
            $error = 'Name, subject and body are required.';
        } elseif ($tid > 0) {
            mysqli_query($link, "UPDATE newsletter_templates SET name='$tname',subject='$subject',body='$body' WHERE id=$tid");
            $success = 'Template updated.';
        } else {
            mysqli_query($link, "INSERT INTO newsletter_templates (name,subject,body) VALUES ('$tname','$subject','$body')");
            $success = 'Template saved.';
        }
        $activeTab = 'templates';
    }

    if ($action === 'delete_template') {
        $tid = (int)($_POST['tpl_id'] ?? 0);
        mysqli_query($link, "DELETE FROM newsletter_templates WHERE id=$tid");
        $success   = 'Template deleted.';
        $activeTab = 'templates';
    }

    if ($action === 'test_newsletter_tpl') {
        $tid    = (int)($_POST['tpl_id'] ?? 0);
        $testTo = trim($_POST['test_email'] ?? '');
        $tplRow = $tid > 0 ? mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM newsletter_templates WHERE id=$tid")) : null;
        if (!$tplRow) {
            $error = 'Template nicht gefunden.';
        } elseif (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben.';
        } else {
            $testBody    = str_replace(['{{name}}','{{email}}'], ['Max Mustermann', $testTo], $tplRow['body']);
            $testSubject = '[TEST] ' . $tplRow['subject'];
            // Direct single send via PHPMailer (classes already loaded via sendNewsletter.php)
            $smtpHost  = getSmtpSettingNL($link, 'smtp_host');
            $smtpUser  = getSmtpSettingNL($link, 'smtp_user');
            $smtpPass  = getSmtpSettingNL($link, 'smtp_password');
            $smtpPort  = (int) getSmtpSettingNL($link, 'smtp_port');
            $fromEmail = getSmtpSettingNL($link, 'smtp_from_email') ?: 'info@simple2success.com';
            $fromName  = getSmtpSettingNL($link, 'smtp_from_name')  ?: 'Simple2Success';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP(); $mail->CharSet = 'UTF-8';
                $mail->Host = $smtpHost; $mail->SMTPAuth = true;
                $mail->Username = $smtpUser; $mail->Password = $smtpPass;
                $mail->Port = $smtpPort;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->isHTML(true);
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($testTo);
                $mail->Subject = $testSubject;
                $mail->Body    = $testBody;
                $mail->send();
                $success = 'Test-E-Mail gesendet an <strong>' . htmlspecialchars($testTo) . '</strong>.';
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                $error = 'Fehler: ' . htmlspecialchars($mail->ErrorInfo);
            }
        }
        $activeTab = 'templates';
    }
}

// Preload template if requested
$preload_tpl = null;
if (isset($_GET['load_tpl'])) {
    $tid         = (int)$_GET['load_tpl'];
    $preload_tpl = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM newsletter_templates WHERE id=$tid"));
    $activeTab   = 'compose';
}

// Data
$templates = mysqli_query($link, "SELECT * FROM newsletter_templates ORDER BY updated_at DESC");
$sent_log  = mysqli_query($link, "SELECT * FROM newsletters ORDER BY sent_at DESC LIMIT 20");

$tpl_to_edit = null;
if (isset($_GET['edit_tpl'])) {
    $tid         = (int)$_GET['edit_tpl'];
    $tpl_to_edit = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM newsletter_templates WHERE id=$tid"));
    $activeTab   = 'templates';
}

$cnt_all     = (int)mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM users"))['c'];
$cnt_members = (int)mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM users WHERE username IS NOT NULL AND username!=''"))['c'];
$cnt_leads   = (int)mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM users WHERE username IS NULL OR username=''"))['c'];

$bannerUrlPreview = $baseurl . '/backoffice/app-assets/img/banner/newleademailheader.jpg';
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<style>
.tpl-tab { cursor:pointer; padding:10px 14px; border-left:3px solid transparent; margin-bottom:3px; border-radius:4px; }
.tpl-tab:hover { background:rgba(255,255,255,0.05); }
.tpl-tab.active-tpl { border-left-color:#cb2ebc; background:rgba(203,46,188,0.1); }
.placeholder-badge { display:inline-block; background:#cb2ebc; color:white; font-size:11px; padding:2px 8px; border-radius:12px; margin:2px; cursor:pointer; }
</style>
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
            <h3 class="content-header-title mb-0"><i class="ft-send" style="color:#b700e0;"></i> Newsletter</h3>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tabs via GET param for reliable switching -->
    <ul class="nav nav-tabs mb-3" id="nlTabs">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'compose' ? 'active' : '' ?>" href="admin-newsletter.php?tab=compose">
                <i class="ft-send"></i> Compose &amp; Send
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'templates' ? 'active' : '' ?>" href="admin-newsletter.php?tab=templates">
                <i class="ft-file-text"></i> Templates
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'log' ? 'active' : '' ?>" href="admin-newsletter.php?tab=log">
                <i class="ft-clock"></i> Sent Log
            </a>
        </li>
    </ul>

    <!-- ── TAB: COMPOSE ─────────────────────────────────────────────────────── -->
    <?php if ($activeTab === 'compose'): ?>
    <div class="row">
        <!-- Left: editor -->
        <div class="col-lg-8 col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">New Newsletter</h4>
                    <small class="text-muted">
                        Platzhalter:
                        <span class="placeholder-badge">{{name}}</span>
                        <span class="placeholder-badge">{{email}}</span>
                    </small>
                </div>
                <div class="card-body">

                    <!-- Load template -->
                    <div class="form-group">
                        <label><strong>Template laden</strong> <small class="text-muted">(optional — befüllt Betreff &amp; Inhalt)</small></label>
                        <div class="input-group">
                            <select id="tplSelect" class="form-control">
                                <option value="">— Template wählen —</option>
                                <?php
                                mysqli_data_seek($templates, 0);
                                while ($t = mysqli_fetch_assoc($templates)):
                                ?>
                                <option value="<?= $t['id'] ?>">
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="loadTemplate()">
                                    <i class="ft-download"></i> Laden
                                </button>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="nlForm">
                        <input type="hidden" name="action" value="send_newsletter">
                        <input type="hidden" name="body" id="bodyInput">

                        <div class="form-group">
                            <label><strong>Betreff</strong></label>
                            <input type="text" name="subject" id="nlSubject" class="form-control"
                                placeholder="Newsletter Betreff…"
                                value="<?= htmlspecialchars($preload_tpl['subject'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label><strong>Zielgruppe</strong></label>
                            <div class="d-flex flex-wrap" style="gap:16px;">
                                <?php foreach (['all' => ["Alle User ($cnt_all)", 'secondary'], 'members' => ["Nur Member ($cnt_members)", 'success'], 'leads' => ["Nur Leads ($cnt_leads)", 'info']] as $val => [$label, $color]): ?>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="tgt_<?= $val ?>" name="target" value="<?= $val ?>" class="custom-control-input" <?= $val === 'all' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="tgt_<?= $val ?>">
                                        <span class="badge badge-<?= $color ?>" style="font-size:13px;padding:5px 10px;"><?= $label ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><strong>Inhalt (HTML)</strong></label>
                            <!-- Editor Tabs: HTML Source | Preview -->
                            <ul class="nav nav-tabs mb-0" id="composeTabs" style="border-bottom:1px solid #444;">
                                <li class="nav-item"><a class="nav-link active" data-tab="compose-source" href="#" onclick="switchComposeTab('source',this);return false;"><i class="ft-code"></i> HTML Source</a></li>
                                <li class="nav-item"><a class="nav-link" data-tab="compose-preview" href="#" onclick="switchComposeTab('preview',this);return false;"><i class="ft-eye"></i> Vorschau</a></li>
                            </ul>
                            <div id="composeTabSource" style="border:1px solid #444;border-top:none;">
                                <textarea id="composeBodyTA" name="body_source" rows="18"
                                    style="width:100%;font-family:monospace;font-size:12px;background:#1a1a2e;color:#e0e0e0;border:none;padding:12px;resize:vertical;"
                                    placeholder="HTML-Code des Newsletters…"><?= htmlspecialchars($preload_tpl ? $preload_tpl['body'] : '') ?></textarea>
                            </div>
                            <div id="composeTabPreview" style="display:none;border:1px solid #444;border-top:none;">
                                <div id="composePreviewEditHint" style="display:none;background:#1a3a5c;color:#7ec8f7;font-size:12px;padding:5px 12px;border-radius:4px 4px 0 0;border:1px solid #3a7abf;border-bottom:none;">
                                    <i class="ft-edit-2"></i>&nbsp; Klicke auf Text zum Bearbeiten &mdash; &Auml;nderungen werden beim Zur&uuml;ckwechseln &uuml;bernommen
                                </div>
                                <iframe id="composePreviewFrame" style="width:100%;height:440px;border:none;display:block;" frameborder="0"></iframe>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between flex-wrap" style="gap:8px;margin-top:12px;">
                            <button type="button" class="btn btn-outline-secondary" onclick="openComposePreview()">
                                <i class="ft-eye"></i> Vollbild-Vorschau
                            </button>
                            <div class="d-flex" style="gap:8px;">
                                <button type="button" class="btn btn-info" onclick="document.getElementById('saveAsTemplateModal').style.display='flex'">
                                    <i class="ft-save"></i> Als Template speichern
                                </button>
                                <button type="submit" class="btn btn-primary" onclick="return prepareSend()">
                                    <i class="ft-send"></i> Newsletter senden
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: info -->
        <div class="col-lg-4 col-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Zielgruppe</h4></div>
                <div class="card-body">
                    <?php foreach (['Alle User' => [$cnt_all, 'secondary'], 'Member (Step 2)' => [$cnt_members, 'success'], 'Leads' => [$cnt_leads, 'info']] as $label => [$cnt, $color]): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?= $label ?></span>
                        <span class="badge badge-<?= $color ?>" style="font-size:14px;padding:6px 12px;"><?= $cnt ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card" style="border-left:3px solid #b700e0;">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">
                        <strong style="color:#ccc;">Tipps:</strong><br>
                        <code>{{name}}</code> → Name des Empfängers<br>
                        <code>{{email}}</code> → E-Mail des Empfängers<br>
                        E-Mails werden einzeln versendet.<br>
                        Immer vorher Vorschau prüfen!
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── TAB: TEMPLATES ────────────────────────────────────────────────────── -->
    <?php if ($activeTab === 'templates'): ?>
    <div class="row">
        <!-- Template list (left sidebar) -->
        <div class="col-lg-3 col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ft-file-text mr-1"></i> Templates</h5>
                    <a href="admin-newsletter.php?tab=templates&new_tpl=1" class="btn btn-primary btn-sm"><i class="ft-plus"></i></a>
                </div>
                <div class="card-content">
                    <div class="card-body p-2">
                        <?php
                        mysqli_data_seek($templates, 0);
                        $has_tpl = false;
                        while ($t = mysqli_fetch_assoc($templates)):
                            $has_tpl = true;
                            $is_edit = ($tpl_to_edit && (int)$tpl_to_edit['id'] === (int)$t['id']);
                        ?>
                        <div class="tpl-tab <?= $is_edit ? 'active-tpl' : '' ?>">
                            <div style="font-weight:600;color:<?= $is_edit ? '#cb2ebc' : '#ccc' ?>;font-size:13px;">
                                <?= htmlspecialchars($t['name']) ?>
                            </div>
                            <div style="font-size:11px;color:#888;margin-top:2px;">
                                <?= htmlspecialchars(substr($t['subject'], 0, 35)) ?>…
                            </div>
                            <div style="font-size:10px;color:#555;margin-top:4px;display:flex;gap:6px;">
                                <a href="admin-newsletter.php?tab=templates&edit_tpl=<?= $t['id'] ?>" class="text-info"><i class="ft-edit-2"></i> Edit</a>
                                <a href="admin-newsletter.php?tab=compose&load_tpl=<?= $t['id'] ?>" class="text-success"><i class="ft-send"></i> Use</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="action" value="delete_template">
                                    <input type="hidden" name="tpl_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-link p-0 text-danger" style="font-size:11px;"><i class="ft-trash-2"></i> Del</button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php if (!$has_tpl): ?>
                        <p class="text-muted text-center p-3" style="font-size:12px;">No templates yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor (right) -->
        <div class="col-lg-9 col-12">
            <?php if ($tpl_to_edit || isset($_GET['new_tpl'])): ?>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><?= $tpl_to_edit ? 'Edit Template: ' . htmlspecialchars($tpl_to_edit['name']) : 'New Template' ?></h4>
                    <small class="text-muted">
                        Placeholders:
                        <span class="placeholder-badge">{{name}}</span>
                        <span class="placeholder-badge">{{email}}</span>
                    </small>
                </div>
                <div class="card-body">
                    <form method="POST" id="tplForm">
                        <input type="hidden" name="action" value="save_template">
                        <input type="hidden" name="tpl_id" value="<?= $tpl_to_edit ? $tpl_to_edit['id'] : 0 ?>">

                        <div class="form-group">
                            <label>Template Name</label>
                            <input type="text" name="tpl_name" id="tplName" class="form-control"
                                value="<?= htmlspecialchars($tpl_to_edit['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" id="tplSubject" class="form-control"
                                value="<?= htmlspecialchars($tpl_to_edit['subject'] ?? '') ?>" required>
                        </div>

                        <?php
                        $editorCfg = [
                            'textarea_name' => 'body',
                            'textarea_id'   => 'tplSourceEditor',
                            'quill_id'      => 'tplQuillEditor',
                            'preview_id'    => 'tplPreviewFrame',
                            'height'        => '480px',
                            'initial_html'  => $tpl_to_edit['body'] ?? '',
                            'instance_key'  => 'newsletter_tpl',
                        ];
                        include 'parts/editor-quill.php';
                        ?>
                        <small class="text-muted d-block mt-1">Vollständiges HTML der E-Mail. Platzhalter: <code>{{name}}</code>, <code>{{email}}</code></small>
                        <div class="mb-3"></div>

                        <div class="d-flex justify-content-end" style="gap:8px;">
                            <a href="admin-newsletter.php?tab=templates" class="btn btn-outline-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ft-save"></i> Template speichern
                            </button>
                        </div>
                    </form>

                    <?php if ($tpl_to_edit): ?>
                    <hr style="border-color:#333;margin-top:24px;">
                    <h6 class="mb-2"><i class="ft-send mr-1"></i> Test-E-Mail senden</h6>
                    <form method="POST" class="d-flex align-items-center" style="gap:10px;" onsubmit="return confirm('Test-E-Mail jetzt senden?');">
                        <input type="hidden" name="action" value="test_newsletter_tpl">
                        <input type="hidden" name="tpl_id" value="<?= $tpl_to_edit['id'] ?>">
                        <input type="email" name="test_email" class="form-control" placeholder="Empfänger E-Mail"
                            value="<?= htmlspecialchars($useremail) ?>" required style="max-width:280px;">
                        <button type="submit" class="btn btn-warning" style="white-space:nowrap;">
                            <i class="ft-send mr-1"></i> Testmail senden
                        </button>
                    </form>
                    <small class="text-muted">{{name}} und {{email}} werden mit Beispieldaten gefüllt. Betreff erhält Prefix <code>[TEST]</code>.</small>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center" style="padding:60px;">
                    <i class="ft-file-text" style="font-size:48px;color:#555;"></i>
                    <p class="text-muted mt-3">Select a template from the left to edit, or create a new one.</p>
                    <a href="admin-newsletter.php?tab=templates&new_tpl=1" class="btn btn-primary">
                        <i class="ft-plus"></i> New Template
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── TAB: LOG ──────────────────────────────────────────────────────────── -->
    <?php if ($activeTab === 'log'): ?>
    <div class="card">
        <div class="card-header"><h4 class="card-title">Last 20 Sent Newsletters</h4></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>Subject</th><th>Target</th><th>Recipients</th><th>Sent At</th></tr></thead>
                <tbody>
                <?php
                $has_log = false;
                while ($l = mysqli_fetch_assoc($sent_log)):
                    $has_log = true;
                ?>
                <tr>
                    <td><?= htmlspecialchars($l['subject']) ?></td>
                    <td><span class="badge badge-secondary"><?= $l['target'] ?></span></td>
                    <td><span class="badge badge-info" style="font-size:13px;padding:4px 10px;"><?= $l['total_sent'] ?></span></td>
                    <td><small><?= $l['sent_at'] ? date('Y-m-d H:i', strtotime($l['sent_at'])) : '—' ?></small></td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$has_log): ?>
                <tr><td colspan="4" class="text-center text-muted p-4">No newsletters sent yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- Preview Modal (Compose) — outside wrapper divs for correct stacking -->
<div id="previewModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;width:90%;max-width:660px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #ddd;background:#222;">
            <strong style="color:#fff;font-size:15px;"><i class="ft-eye" style="color:#cb2ebc;"></i> Newsletter Preview</strong>
            <button onclick="closePreviewModal()" style="background:none;border:none;color:#fff;font-size:22px;line-height:1;cursor:pointer;padding:0 4px;">&times;</button>
        </div>
        <div style="flex:1;overflow:auto;">
            <iframe id="previewFrame" style="width:100%;height:520px;border:none;" frameborder="0"></iframe>
        </div>
    </div>
</div>

<!-- Save as Template Modal -->
<div id="saveAsTemplateModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:99998;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;">
    <div style="background:#2a2a3e;border-radius:8px;width:90%;max-width:440px;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #444;">
            <strong style="color:#fff;font-size:15px;"><i class="ft-save" style="color:#cb2ebc;"></i> Als Template speichern</strong>
            <button onclick="document.getElementById('saveAsTemplateModal').style.display='none'" style="background:none;border:none;color:#fff;font-size:22px;line-height:1;cursor:pointer;padding:0 4px;">&times;</button>
        </div>
        <div style="padding:20px;">
            <div class="form-group mb-3">
                <label style="color:#ccc;">Template Name</label>
                <input type="text" id="saveAsTplName" class="form-control" placeholder="z.B. Monatlicher Newsletter">
            </div>
            <div class="d-flex justify-content-end" style="gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('saveAsTemplateModal').style.display='none'">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="saveAsTemplate()"><i class="ft-save"></i> Speichern</button>
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

<script>
var bannerUrlPreview = <?= json_encode($bannerUrlPreview) ?>;
var BANNER_PROD = 'https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';

function localizeHtml(html) {
    html = html.replace(
        /https:\/\/www\.simple2success\.com\/backoffice\/app-assets\/img\/banner\/newleademailheader\.jpg/g,
        bannerUrlPreview
    );
    html = html.replace(
        /https:\/\/simple2success\.com\/backoffice\/app-assets\/img\/banner\/newleademailheader\.jpg/g,
        bannerUrlPreview
    );
    return html;
}

// ── Editable Preview Helpers ────────────────────────────────────────────────
function delocalizeHtml(html) {
    var escaped = bannerUrlPreview.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return html.replace(new RegExp(escaped, 'g'), BANNER_PROD);
}

// Call BEFORE setting frame.srcdoc — arms the onload to enable designMode
function enablePreviewEditing(frame, hintEl) {
    frame.onload = function() {
        try {
            var doc = frame.contentDocument;
            if (!doc) return;
            var old = doc.getElementById('edit-mode-style');
            if (old) old.parentNode.removeChild(old);
            var style = doc.createElement('style');
            style.id = 'edit-mode-style';
            style.textContent = 'body { outline:2px solid #3a7abf !important; outline-offset:-2px; cursor:text; }';
            if (doc.head) doc.head.appendChild(style);
            doc.designMode = 'on';
        } catch(e) { console.warn('designMode failed:', e); }
        if (hintEl) hintEl.style.display = 'block';
    };
}

// Sync edited iframe content back to textarea (strips edit-mode style + restores prod URL)
function syncIframeToTextarea(frame, textarea) {
    try {
        var doc = frame.contentDocument;
        if (!doc || doc.designMode !== 'on') return;
        var s = doc.getElementById('edit-mode-style');
        if (s) s.parentNode.removeChild(s);
        var bodyHtml = doc.body ? doc.body.innerHTML : '';
        textarea.value = delocalizeHtml(bodyHtml);
    } catch(e) { console.warn('syncIframeToTextarea failed:', e); }
}

// ── Full-screen Preview Modal (read-only, no designMode) ───────────────────
function openPreviewModal(html) {
    document.getElementById('previewFrame').srcdoc = localizeHtml(html);
    document.getElementById('previewModal').style.display = 'flex';
}
function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
    document.getElementById('previewFrame').srcdoc = '';
}
document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) closePreviewModal();
});

<?php if ($activeTab === 'compose'): ?>
// ── COMPOSE TAB ────────────────────────────────────────────────────────────
var composeBodyTA = document.getElementById('composeBodyTA');

// Live preview update while typing in source textarea
composeBodyTA.addEventListener('input', function() {
    var frame = document.getElementById('composePreviewFrame');
    if (frame && document.getElementById('composeTabPreview').style.display !== 'none') {
        frame.srcdoc = localizeHtml(this.value);
    }
});

function switchComposeTab(tab, el) {
    document.querySelectorAll('#composeTabs .nav-link').forEach(function(a){ a.classList.remove('active'); });
    el.classList.add('active');
    var hintEl = document.getElementById('composePreviewEditHint');
    if (tab === 'source') {
        // Sync edits back before hiding the iframe
        syncIframeToTextarea(document.getElementById('composePreviewFrame'), composeBodyTA);
        if (hintEl) hintEl.style.display = 'none';
        document.getElementById('composeTabSource').style.display = 'block';
        document.getElementById('composeTabPreview').style.display = 'none';
    } else {
        document.getElementById('composeTabSource').style.display = 'none';
        document.getElementById('composeTabPreview').style.display = 'block';
        var frame = document.getElementById('composePreviewFrame');
        enablePreviewEditing(frame, hintEl);
        frame.srcdoc = localizeHtml(composeBodyTA.value);
    }
}

function loadTemplate() {
    var id = document.getElementById('tplSelect').value;
    if (!id) { alert('Bitte zuerst ein Template wählen.'); return; }
    window.location.href = 'admin-newsletter.php?tab=compose&load_tpl=' + encodeURIComponent(id);
}

function prepareSend() {
    // Safety net: sync preview edits back to textarea if preview tab is active
    var preview = document.getElementById('composeTabPreview');
    if (preview && preview.style.display !== 'none') {
        syncIframeToTextarea(document.getElementById('composePreviewFrame'), composeBodyTA);
    }
    document.getElementById('bodyInput').value = composeBodyTA.value;
    if (!document.getElementById('nlSubject').value.trim()) {
        alert('Bitte einen Betreff eingeben.'); return false;
    }
    if (!composeBodyTA.value.trim()) {
        alert('Bitte Inhalt eingeben.'); return false;
    }
    return confirm('Newsletter jetzt senden?');
}

function openComposePreview() {
    var html = composeBodyTA.value;
    if (!html.trim()) { alert('Kein Inhalt zum Anzeigen.'); return; }
    openPreviewModal(html);
}

function saveAsTemplate() {
    // Sync preview edits before saving as template
    var preview = document.getElementById('composeTabPreview');
    if (preview && preview.style.display !== 'none') {
        syncIframeToTextarea(document.getElementById('composePreviewFrame'), composeBodyTA);
    }
    var tname = document.getElementById('saveAsTplName').value.trim();
    if (!tname) { alert('Bitte einen Namen eingeben.'); return; }
    document.getElementById('saveAsTemplateModal').style.display = 'none';
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = 'admin-newsletter.php?tab=templates';
    [
        ['action', 'save_template'],
        ['tpl_name', tname],
        ['subject', document.getElementById('nlSubject').value],
        ['body', composeBodyTA.value],
        ['tpl_id', '0']
    ].forEach(function(p) {
        var i = document.createElement('input'); i.type='hidden'; i.name=p[0]; i.value=p[1]; f.appendChild(i);
    });
    document.body.appendChild(f); f.submit();
}
<?php endif; ?>

<?php /* Template editor JS is handled by parts/editor-quill.php partial */ ?>
</script>
</body>
</html>
