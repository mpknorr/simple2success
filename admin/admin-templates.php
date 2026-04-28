<?php
require_once '../includes/BrevoMailer.php';
require_once '../includes/emailFooter.php';

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

// Migration: replace www.simple2success.com with simple2success.com in all bodies
mysqli_query($link, "UPDATE email_templates SET body = REPLACE(body, 'https://www.simple2success.com/', 'https://simple2success.com/') WHERE body LIKE '%www.simple2success.com%'");
mysqli_query($link, "UPDATE followup_sequences SET body = REPLACE(body, 'https://www.simple2success.com/', 'https://simple2success.com/') WHERE body LIKE '%www.simple2success.com%'");

// Migration: decode HTML entities in subject lines (plain text field — entities must not appear)
foreach (['email_templates', 'followup_sequences'] as $_tbl) {
    foreach (['&mdash;'=>'—','&ndash;'=>'–','&rarr;'=>'→','&rsquo;'=>"\u{2019}",'&lsquo;'=>"\u{2018}",'&ldquo;'=>"\u{201C}",'&rdquo;'=>"\u{201D}",'&hellip;'=>'…','&amp;'=>'&','&nbsp;'=>' '] as $_ent => $_char) {
        $_e = mysqli_real_escape_string($link, $_ent);
        $_c = mysqli_real_escape_string($link, $_char);
        mysqli_query($link, "UPDATE {$_tbl} SET subject = REPLACE(subject, '$_e', '$_c') WHERE subject LIKE '%$_e%'");
    }
}

// Template speichern
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_template'])) {
    $id      = (int)$_POST['id'];
    $subject = mysqli_real_escape_string($link, $_POST['subject']);
    $body    = mysqli_real_escape_string($link, $_POST['body']);
    if (mysqli_query($link, "UPDATE email_templates SET subject='$subject', body='$body', updated_at=NOW() WHERE id=$id")) {
        $success = "Template gespeichert.";
    } else {
        $error = "Fehler: " . mysqli_error($link);
    }
}

// Test-E-Mail senden
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['send_test'])) {
    $id       = (int)$_POST['id'];
    $testTo   = trim($_POST['test_email'] ?? '');
    $tplRow   = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM email_templates WHERE id=$id"));
    if (!$tplRow) {
        $error = "Template nicht gefunden.";
    } elseif (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $error = "Bitte eine gültige E-Mail-Adresse angeben.";
    } else {
        // Platzhalter mit Beispieldaten füllen
        $testBody = $tplRow['body'];
        $testBody = str_replace('{{leads}}', '<ul><li>max.mustermann@example.com</li><li>anna.beispiel@example.com</li></ul>', $testBody);
        $testBody = str_replace('{{customer_email}}', 'max.mustermann@example.com', $testBody);
        $testBody = str_replace('{{member_email}}', 'max.mustermann@example.com', $testBody);
        $testBody = str_replace('{{name}}', 'Max Mustermann', $testBody);
        $testBody = str_replace('{{email}}', 'max.mustermann@example.com', $testBody);
        $testBody = str_replace('{{password}}', 'TestPw123!', $testBody);
        $testBody = str_replace('{{login_url}}', $baseurl . '/backoffice/login.php', $testBody);
        $testBody = str_replace('{{cta_url}}', $baseurl . '/backoffice/start.php', $testBody);
        $testBody = str_replace('{{magic_link}}', $baseurl . '/backoffice/autologin.php?token=DEMO_TOKEN_PREVIEW', $testBody);
        $testBody = str_replace('{{reset_link}}', $baseurl . '/backoffice/reset-password.php?token=DEMO_RESET_PREVIEW', $testBody);
        $testBody .= renderEmailFooter($link, $tplRow['template_key'] ?? '', $userid);

        try {
            $tpl_mailer = new BrevoMailer($link);
            $tpl_mailer->sendEmail($testTo, $testTo,
                '[TEST] ' . html_entity_decode($tplRow['subject'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                $testBody,
                ['test', 'admin-template-test'],
                ['template_id' => $id, 'email_type' => 'template_test']);
            $success = "Test-E-Mail erfolgreich gesendet an <strong>" . htmlspecialchars($testTo) . "</strong>.";
        } catch (\Exception $e) {
            $error = "Fehler beim Senden: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Alle Templates laden
$templates = mysqli_query($link, "SELECT * FROM email_templates ORDER BY id ASC");
$tpl_list = [];
while ($row = mysqli_fetch_assoc($templates)) {
    $tpl_list[] = $row;
}

// Aktives Template (per GET oder erstes)
$active_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($tpl_list[0]['id'] ?? 0);
$active = null;
foreach ($tpl_list as $t) {
    if ((int)$t['id'] === $active_id) { $active = $t; break; }
}
if (!$active && !empty($tpl_list)) { $active = $tpl_list[0]; $active_id = (int)$active['id']; }
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<style>
.tpl-tab { cursor:pointer; padding:12px 16px; border-left:3px solid transparent; margin-bottom:4px; border-radius:4px; }
.tpl-tab:hover { background:rgba(255,255,255,0.05); }
.tpl-tab.active { border-left-color:#cb2ebc; background:rgba(203,46,188,0.1); }
.preview-frame { width:100%; min-height:400px; border:1px solid #444; border-radius:6px; background:white; padding:20px; overflow-y:auto; }
.placeholder-badge { display:inline-block; background:#cb2ebc; color:white; font-size:11px; padding:2px 8px; border-radius:12px; margin:2px; }
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
                <div class="content-header-left col-8">
                    <h3 class="content-header-title">Admin — E-Mail Templates</h3>
                </div>
                <div class="content-header-right col-4 text-right"></div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Template-Liste links -->
                <div class="col-lg-3 col-12">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0"><i class="ft-mail mr-1"></i> Templates</h5></div>
                        <div class="card-content">
                            <div class="card-body p-2">
                                <?php foreach ($tpl_list as $t): ?>
                                <a href="admin-templates.php?id=<?= $t['id'] ?>" class="tpl-tab d-block text-decoration-none <?= (int)$t['id'] === $active_id ? 'active' : '' ?>">
                                    <div style="font-weight:600;color:<?= (int)$t['id'] === $active_id ? '#cb2ebc' : '#ccc' ?>">
                                        <i class="ft-mail mr-1"></i> <?= htmlspecialchars($t['name']) ?>
                                    </div>
                                    <div style="font-size:11px;color:#888;margin-top:2px;">
                                        Betreff: <?= htmlspecialchars(substr($t['subject'], 0, 40)) ?>...
                                    </div>
                                    <div style="font-size:10px;color:#666;margin-top:2px;">
                                        Aktualisiert: <?= date('d.m.Y H:i', strtotime($t['updated_at'])) ?>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Editor + Vorschau rechts -->
                <div class="col-lg-9 col-12">
                    <?php if ($active): ?>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="tplTabs">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-edit"><i class="ft-edit-2 mr-1"></i> Bearbeiten</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-preview"><i class="ft-eye mr-1"></i> Vorschau</a></li>
                    </ul>

                    <div class="tab-content">
                        <!-- Bearbeiten Tab -->
                        <div class="tab-pane active" id="tab-edit">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title"><?= htmlspecialchars($active['name']) ?></h4>
                                    <small class="text-muted">
                                        Platzhalter:
                                        <?php if ($active['template_key'] === 'daily_leads'): ?>
                                        <span class="placeholder-badge">{{leads}}</span> — Liste der neuen Lead-E-Mails
                                        <?php elseif ($active['template_key'] === 'new_customer'): ?>
                                        <span class="placeholder-badge">{{customer_email}}</span> — E-Mail des neuen Kunden
                                        <?php elseif ($active['template_key'] === 'password_reset'): ?>
                                        <span class="placeholder-badge">{{name}}</span> — First name &nbsp;
                                        <span class="placeholder-badge">{{reset_link}}</span> — Password reset link
                                        <?php elseif ($active['template_key'] === 'password_changed'): ?>
                                        <span class="placeholder-badge">{{name}}</span> — First name &nbsp;
                                        <span class="placeholder-badge">{{email}}</span> — Username (login email) &nbsp;
                                        <span class="placeholder-badge">{{password}}</span> — New password &nbsp;
                                        <span class="placeholder-badge">{{login_url}}</span> — Login URL &nbsp;
                                        <span class="placeholder-badge" style="background:#cb2ebc;">{{magic_link}}</span> — Auto-Login-Link (1h)
                                        <?php else: ?>
                                        <span class="placeholder-badge">{{name}}</span> — First name &nbsp;
                                        <span class="placeholder-badge">{{email}}</span> — Email address &nbsp;
                                        <span class="placeholder-badge">{{magic_link}}</span> — Auto-login link (one-time, time-limited) &nbsp;
                                        <?php if ($active['template_key'] === 'welcome_user'): ?>
                                        <span class="placeholder-badge">{{password}}</span> — Initial password &nbsp;
                                        <span class="placeholder-badge">{{login_url}}</span> — Login URL &nbsp;
                                        <?php else: ?>
                                        <span class="placeholder-badge">{{cta_url}}</span> — Call-to-action URL &nbsp;
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $active['id'] ?>">
                                            <input type="hidden" name="template_key" value="<?= htmlspecialchars($active['template_key']) ?>">
                                            <div class="form-group">
                                                <label>Betreff</label>
                                                <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($active['subject']) ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>E-Mail Body (HTML)</label>
                                                <!-- Visual / Source sub-tabs -->
                                                <div style="display:flex;gap:2px;border-bottom:2px solid rgba(255,255,255,.1);margin-bottom:0;padding:0 0 0 0;">
                                                    <button type="button" id="tplSubVisual" class="eq-tab-btn active" onclick="tplSubSwitch('visual')"><i class="ft-edit-2" style="margin-right:.25rem;"></i>Visual</button>
                                                    <button type="button" id="tplSubSource" class="eq-tab-btn" onclick="tplSubSwitch('source')"><i class="ft-code" style="margin-right:.25rem;"></i>HTML Source</button>
                                                </div>
                                                <!-- Quill Visual editor -->
                                                <div id="tplQuillWrap">
                                                    <div id="tplQuillEditor" style="height:380px;"></div>
                                                </div>
                                                <!-- HTML Source textarea -->
                                                <div id="tplSourceWrap" style="display:none;">
                                                    <textarea name="body" id="bodyEditor" class="eq-source-ta" style="height:400px;"><?= htmlspecialchars($active['body']) ?></textarea>
                                                </div>
                                                <!-- body textarea always POSTs; Quill syncs to it on submit -->
                                                <small class="text-muted" style="display:block;margin-top:.35rem;">Vorschau mit Platzhalter-Befüllung im Tab "Vorschau" sichtbar.</small>
                                            </div>
                                            <button type="submit" name="save_template" class="btn btn-primary">
                                                <i class="ft-save mr-1"></i> Template speichern
                                            </button>
                                        </form>

                                        <hr style="border-color:#333;margin-top:24px;">
                                        <h6 class="mb-2"><i class="ft-send mr-1"></i> Test-E-Mail senden</h6>
                                        <form method="POST" class="d-flex align-items-center" style="gap:10px;">
                                            <input type="hidden" name="id" value="<?= $active['id'] ?>">
                                            <input type="email" name="test_email" class="form-control" placeholder="Empfänger E-Mail" value="<?= htmlspecialchars($useremail) ?>" required style="max-width:280px;">
                                            <button type="submit" name="send_test" class="btn btn-warning" style="white-space:nowrap;">
                                                <i class="ft-send mr-1"></i> Testmail senden
                                            </button>
                                        </form>
                                        <small class="text-muted">Platzhalter werden mit Beispieldaten befüllt. Betreff erhält das Prefix <code>[TEST]</code>.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vorschau Tab -->
                        <div class="tab-pane" id="tab-preview">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Vorschau: <?= htmlspecialchars($active['name']) ?></h4>
                                    <small class="text-muted">Betreff: <strong><?= htmlspecialchars($active['subject']) ?></strong></small>
                                </div>
                                <div class="card-content">
                                    <div class="card-body p-0">
                                        <?php
                                        $preview = $active['body'];
                                        // Banner-URL für lokale Vorschau auf $baseurl umbiegen
                                        $preview = str_replace('https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg', $baseurl . '/backoffice/app-assets/img/banner/newleademailheader.jpg', $preview);
                                        $preview = str_replace('{{leads}}', '<ul style="padding-left:20px;"><li>max.mustermann@example.com</li><li>anna.beispiel@example.com</li></ul>', $preview);
                                        $preview = str_replace('{{customer_email}}', 'max.mustermann@example.com', $preview);
                                        $preview = str_replace('{{name}}', 'Max Mustermann', $preview);
                                        $preview = str_replace('{{email}}', 'max.mustermann@example.com', $preview);
                                        $preview = str_replace('{{password}}', 'TestPw123!', $preview);
                                        $preview = str_replace('{{login_url}}', $baseurl . '/backoffice/login.php', $preview);
                                        $preview = str_replace('{{cta_url}}', $baseurl . '/backoffice/start.php', $preview);
                                        $preview = str_replace('{{magic_link}}', $baseurl . '/backoffice/autologin.php?token=DEMO_TOKEN_PREVIEW', $preview);
                                        $preview = str_replace('{{reset_link}}', $baseurl . '/backoffice/reset-password.php?token=DEMO_RESET_PREVIEW', $preview);
                                        ?>
                                        <div id="tplPreviewEditHint" style="display:none;background:#1a3a5c;color:#7ec8f7;font-size:12px;padding:5px 12px;border-radius:4px 4px 0 0;border:1px solid #3a7abf;border-bottom:none;">
                                            <i class="ft-edit-2"></i>&nbsp; Klicke auf Text zum Bearbeiten &mdash; &Auml;nderungen werden beim Zur&uuml;ckwechseln &uuml;bernommen
                                        </div>
                                        <iframe id="previewFrame" srcdoc="<?= htmlspecialchars($preview) ?>" style="width:100%;min-height:500px;border:none;display:block;" frameborder="0" scrolling="yes"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>
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
<link rel="stylesheet" href="../backoffice/app-assets/vendors/css/quill.snow.css">
<style>
.eq-tab-btn { padding:.45rem .9rem;font-size:.78rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:rgba(255,255,255,.4);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s,border-color .15s; }
.eq-tab-btn.active { color:#b700e0;border-bottom-color:#b700e0; }
.eq-tab-btn:hover:not(.active) { color:rgba(255,255,255,.75); }
.eq-source-ta { width:100%;font-family:'Fira Mono','Courier New',monospace;font-size:12px;background:#0d0d1a;color:#c8e6c9;border:1px solid rgba(255,255,255,.1);border-radius:0 0 6px 6px;padding:.75rem 1rem;resize:vertical;line-height:1.6; }
.eq-source-ta:focus { outline:none;border-color:rgba(183,0,224,.4); }
.ql-toolbar.ql-snow { background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1)!important;border-radius:6px 6px 0 0; }
.ql-container.ql-snow { border-color:rgba(255,255,255,.1)!important;border-radius:0 0 6px 6px;background:rgba(255,255,255,.03); }
.ql-editor { color:#e0e0e0;font-size:.93rem;line-height:1.65; }
.ql-editor.ql-blank::before { color:rgba(255,255,255,.3)!important; }
.ql-snow .ql-stroke { stroke:rgba(255,255,255,.6)!important; }
.ql-snow .ql-fill { fill:rgba(255,255,255,.6)!important; }
.ql-snow .ql-picker-label { color:rgba(255,255,255,.6)!important; }
.ql-snow .ql-picker-options { background:#1a1040!important;border-color:rgba(255,255,255,.15)!important; }
</style>
<script src="../backoffice/app-assets/vendors/js/vendors.min.js"></script>
<script src="../backoffice/app-assets/vendors/js/quill.min.js"></script>
<script src="../backoffice/app-assets/js/core/app-menu.js"></script>
<script src="../backoffice/app-assets/js/core/app.js"></script>
<script src="../backoffice/app-assets/js/notification-sidebar.js"></script>
<script src="../backoffice/app-assets/js/scroll-top.js"></script>
<script src="../backoffice/assets/js/scripts.js"></script>

<script>
var bannerUrlPreview = '<?= htmlspecialchars($baseurl) ?>/backoffice/app-assets/img/banner/newleademailheader.jpg';
var BANNER_PROD = 'https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';

function delocalizeHtml(html) {
    var escaped = bannerUrlPreview.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return html.replace(new RegExp(escaped, 'g'), BANNER_PROD);
}
function applyEditMode(frame, hintEl) {
    try {
        var doc = frame.contentDocument;
        if (!doc) return;
        var old = doc.getElementById('edit-mode-style');
        if (old) old.parentNode.removeChild(old);
        var style = doc.createElement('style');
        style.id = 'edit-mode-style';
        style.textContent = 'body{outline:2px solid #3a7abf!important;outline-offset:-2px;cursor:text;}';
        if (doc.head) doc.head.appendChild(style);
        doc.designMode = 'on';
    } catch(e) {}
    if (hintEl) hintEl.style.display = 'block';
}
function enablePreviewEditing(frame, hintEl) {
    // Must be set BEFORE assigning srcdoc (caller ordering)
    frame.onload = function() { applyEditMode(frame, hintEl); };
}
function syncIframeToTextarea(frame, textarea) {
    try {
        var doc = frame.contentDocument;
        if (!doc || doc.designMode !== 'on') return;
        var s = doc.getElementById('edit-mode-style');
        if (s) s.parentNode.removeChild(s);
        // Only body.innerHTML — outerHTML would re-wrap the next render
        var bodyHtml = doc.body ? doc.body.innerHTML : '';
        textarea.value = delocalizeHtml(bodyHtml);
    } catch(e) {}
}
function localizeAndReplace(html) {
    html = html.replace(/https:\/\/www\.simple2success\.com\/backoffice\/app-assets\/img\/banner\/newleademailheader\.jpg/g, bannerUrlPreview);
    html = html.replace(/https:\/\/simple2success\.com\/backoffice\/app-assets\/img\/banner\/newleademailheader\.jpg/g, bannerUrlPreview);
    html = html.replace(/\{\{leads\}\}/g, '<ul style="padding-left:20px;"><li>max.mustermann@example.com</li><li>anna.beispiel@example.com</li></ul>');
    html = html.replace(/\{\{customer_email\}\}/g, 'max.mustermann@example.com');
    html = html.replace(/\{\{name\}\}/g, 'Max Mustermann');
    html = html.replace(/\{\{email\}\}/g, 'max.mustermann@example.com');
    html = html.replace(/\{\{password\}\}/g, 'TestPw123!');
    html = html.replace(/\{\{login_url\}\}/g, '<?= htmlspecialchars($baseurl) ?>/backoffice/login.php');
    return html;
}

// ── Quill setup ───────────────────────────────────────────────
var tplSubActive = 'visual';
var tplQuill = null;
var bodyEditor   = document.getElementById('bodyEditor');
var previewFrame = document.getElementById('previewFrame');
var previewHint  = document.getElementById('tplPreviewEditHint');

var tplQuillDirty = false;
if (document.getElementById('tplQuillEditor')) {
    tplQuill = new Quill('#tplQuillEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [1,2,3,false] }],
                ['bold','italic','underline','strike'],
                [{ color:[] },{ background:[] }],
                [{ align:[] }],
                [{ list:'ordered' },{ list:'bullet' }],
                ['link','image'],
                ['blockquote','code-block'],
                ['clean']
            ]
        }
    });
    // Load initial content
    if (bodyEditor && bodyEditor.value.trim()) {
        tplQuill.clipboard.dangerouslyPasteHTML(bodyEditor.value);
    }
    // Dirty flag: only sync Quill → textarea nach echter User-Eingabe
    // (Quill strippt <html>/<body>/<style> — wir dürfen Original-HTML nicht zerstören)
    tplQuill.on('text-change', function(_, __, source) {
        if (source === 'user') tplQuillDirty = true;
    });
}

// ── Visual / Source sub-tab switcher ─────────────────────────
window.tplSubSwitch = function(tab) {
    if (tab === tplSubActive) return;
    if (tplSubActive === 'visual' && tplQuill && tplQuillDirty) {
        bodyEditor.value = tplQuill.root.innerHTML;
    } else if (tplSubActive === 'source' && tplQuill) {
        tplQuill.clipboard.dangerouslyPasteHTML(bodyEditor.value);
        tplQuillDirty = false;
    }
    document.getElementById('tplQuillWrap').style.display  = tab === 'visual' ? '' : 'none';
    document.getElementById('tplSourceWrap').style.display = tab === 'source' ? '' : 'none';
    document.getElementById('tplSubVisual').classList.toggle('active', tab === 'visual');
    document.getElementById('tplSubSource').classList.toggle('active', tab === 'source');
    tplSubActive = tab;
};

// ── Sync Quill → textarea before any form submit ─────────────
document.querySelectorAll('form').forEach(function(f) {
    f.addEventListener('submit', function() {
        if (tplQuill && tplSubActive === 'visual' && tplQuillDirty) {
            bodyEditor.value = tplQuill.root.innerHTML;
        }
    });
});

// ── Bootstrap tab events — Vorschau ──────────────────────────
$('a[href="#tab-preview"]').on('shown.bs.tab', function() {
    if (tplQuill && tplSubActive === 'visual' && tplQuillDirty) {
        bodyEditor.value = tplQuill.root.innerHTML;
    }
    var html = bodyEditor ? localizeAndReplace(bodyEditor.value) : '';
    // onload handler MUST be set before srcdoc assignment
    enablePreviewEditing(previewFrame, previewHint);
    previewFrame.srcdoc = html;
});
$('a[href="#tab-edit"]').on('show.bs.tab', function() {
    if (previewHint) previewHint.style.display = 'none';
    if (bodyEditor) {
        syncIframeToTextarea(previewFrame, bodyEditor);
        if (tplQuill && tplSubActive === 'visual') {
            tplQuill.clipboard.dangerouslyPasteHTML(bodyEditor.value);
        }
    }
});
</script>
</body>
</html>
