<?php
session_start();
if (empty($_SESSION["userid"]) || empty($_SESSION["is_admin"])) {
    require_once '../includes/conn.php';
    header("Location: " . $baseurl . "/backoffice/index.php");
    exit();
}
require_once '../includes/conn.php';
require_once '../includes/legal.php';

$userid = $_SESSION["userid"];
$getuserdetails = mysqli_query($link, "SELECT * FROM users WHERE leadid = $userid");
foreach ($getuserdetails as $userData) {
    $name        = $userData["name"];
    $username    = $userData["username"];
    $useremail   = $userData["email"];
    $paidstatus  = $userData["paidstatus"];
    $profile_pic = $userData["profile_pic"];
}

// Ensure table + seed defaults
legalEnsureTable($link);

$success = '';
$error   = '';

// ── Save document ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_legal'])) {
    $id           = (int)($_POST['id'] ?? 0);
    $title        = mysqli_real_escape_string($link, trim($_POST['title'] ?? ''));
    $content_html = mysqli_real_escape_string($link, $_POST['content_html'] ?? '');
    $content_text = mysqli_real_escape_string($link, trim($_POST['content_text'] ?? ''));
    $footer_snip  = mysqli_real_escape_string($link, trim($_POST['footer_snippet'] ?? ''));
    $status       = ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published';
    $show_footer  = isset($_POST['show_in_footer'])        ? 1 : 0;
    $show_prem    = isset($_POST['show_on_premium_pages']) ? 1 : 0;
    $show_reg     = isset($_POST['show_on_registration'])  ? 1 : 0;
    $show_check   = isset($_POST['show_on_checkout'])      ? 1 : 0;
    $pub_at       = ($status === 'published') ? 'NOW()' : 'NULL';

    if ($id > 0 && !empty($title)) {
        $ok = mysqli_query($link,
            "UPDATE legal_documents SET
                title='$title',
                content_html='$content_html',
                content_text='$content_text',
                footer_snippet='$footer_snip',
                status='$status',
                show_in_footer=$show_footer,
                show_on_premium_pages=$show_prem,
                show_on_registration=$show_reg,
                show_on_checkout=$show_check,
                published_at=$pub_at,
                version_number = version_number + 1
             WHERE id=$id"
        );
        if ($ok) {
            $success = "Document saved successfully.";
        } else {
            $error = "Database error: " . mysqli_error($link);
        }
    } else {
        $error = "Title is required.";
    }
}

// ── Load document list ────────────────────────────────────────────────────────
$docs = [];
$res = mysqli_query($link,
    "SELECT id, slug, document_type, title, status, language_code, market_code,
            show_in_footer, show_on_premium_pages, show_on_registration, show_on_checkout,
            updated_at
     FROM legal_documents
     ORDER BY id ASC"
);
while ($row = mysqli_fetch_assoc($res)) {
    $docs[] = $row;
}

// ── Active document for editing ──────────────────────────────────────────────
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($docs[0]['id'] ?? 0);
// After save redirect keeps us on same doc
if (!empty($success) && isset($_POST['id'])) {
    $edit_id = (int)$_POST['id'];
}
$active = null;
foreach ($docs as $d) {
    if ((int)$d['id'] === $edit_id) { $active = $d; break; }
}
if (!$active && !empty($docs)) { $active = $docs[0]; $edit_id = (int)$active['id']; }

// Full row for editor
if ($active) {
    $fullRow = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT * FROM legal_documents WHERE id=" . (int)$active['id']
    ));
}
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<style>
.legal-tab { cursor:pointer; padding:12px 16px; border-left:3px solid transparent; margin-bottom:4px; border-radius:4px; text-decoration:none; display:block; }
.legal-tab:hover { background:rgba(255,255,255,0.05); text-decoration:none; }
.legal-tab.active { border-left-color:#cb2ebc; background:rgba(203,46,188,0.1); }
.badge-published { background:#28a745; color:#fff; font-size:10px; padding:2px 7px; border-radius:10px; }
.badge-draft     { background:#6c757d; color:#fff; font-size:10px; padding:2px 7px; border-radius:10px; }
.visibility-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; }
.form-section-label { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#888; margin-bottom:8px; margin-top:20px; }
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
                    <h3 class="content-header-title">Admin — Legal Pages</h3>
                    <p style="color:#888;font-size:13px;margin-top:4px;">Manage Privacy Policy, Terms of Use, Imprint, and Income Disclaimer.</p>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- ── Document list (left column) ── -->
                <div class="col-lg-3 col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="ft-file-text mr-1"></i> Documents</h5>
                        </div>
                        <div class="card-content">
                            <div class="card-body p-2">
                                <?php foreach ($docs as $d): ?>
                                <a href="admin-legal.php?id=<?= $d['id'] ?>"
                                   class="legal-tab <?= (int)$d['id'] === $edit_id ? 'active' : '' ?>">
                                    <div style="font-weight:600;color:<?= (int)$d['id'] === $edit_id ? '#cb2ebc' : '#ccc' ?>;font-size:13px;">
                                        <i class="ft-file-text mr-1"></i> <?= htmlspecialchars($d['title']) ?>
                                    </div>
                                    <div style="font-size:11px;margin-top:3px;">
                                        <span class="badge-<?= $d['status'] ?>"><?= $d['status'] ?></span>
                                        <span style="color:#666;margin-left:6px;"><?= htmlspecialchars($d['slug']) ?></span>
                                    </div>
                                    <div style="font-size:10px;color:#555;margin-top:2px;">
                                        Updated: <?= date('d.m.Y H:i', strtotime($d['updated_at'])) ?>
                                    </div>
                                    <div style="font-size:10px;color:#555;margin-top:1px;">
                                        <?php
                                        $vis = [];
                                        if ($d['show_in_footer'])        $vis[] = 'Footer';
                                        if ($d['show_on_premium_pages']) $vis[] = 'Premium Pages';
                                        if ($d['show_on_registration'])  $vis[] = 'Registration';
                                        if ($d['show_on_checkout'])      $vis[] = 'Checkout';
                                        echo empty($vis) ? '<span style="color:#444;">No placements</span>' : implode(', ', $vis);
                                        ?>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Info box -->
                    <div class="card">
                        <div class="card-header"><h6 class="card-title mb-0"><i class="ft-info mr-1"></i> Public URLs</h6></div>
                        <div class="card-content">
                            <div class="card-body" style="font-size:12px;">
                                <p style="color:#888;margin-bottom:8px;">Live pages:</p>
                                <a href="<?= $baseurl ?>/impress.php" target="_blank" style="color:#cb2ebc;display:block;margin-bottom:4px;">/impress.php</a>
                                <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy" target="_blank" style="color:#cb2ebc;display:block;margin-bottom:4px;">/legal.php?doc=privacy-policy</a>
                                <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use" target="_blank" style="color:#cb2ebc;display:block;margin-bottom:4px;">/legal.php?doc=terms-of-use</a>
                                <a href="<?= $baseurl ?>/legal.php?doc=income-disclaimer" target="_blank" style="color:#cb2ebc;display:block;">/legal.php?doc=income-disclaimer</a>
                                <hr style="border-color:#333;">
                                <p style="color:#555;font-size:11px;">Landing pages (linkp1/2/3) link to Impressum, Privacy Policy, and Terms of Use. Income Disclaimer is shown inline as a text snippet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Editor (right column) ── -->
                <div class="col-lg-9 col-12">
                    <?php if ($active && $fullRow): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="ft-edit-2 mr-1"></i>
                                Edit: <?= htmlspecialchars($fullRow['title']) ?>
                                <span class="badge-<?= $fullRow['status'] ?>" style="margin-left:8px;"><?= $fullRow['status'] ?></span>
                            </h4>
                            <div style="font-size:12px;color:#666;margin-top:4px;">
                                Slug: <code><?= htmlspecialchars($fullRow['slug']) ?></code> &nbsp;|&nbsp;
                                Lang: <code><?= htmlspecialchars($fullRow['language_code']) ?></code> &nbsp;|&nbsp;
                                Market: <code><?= htmlspecialchars($fullRow['market_code']) ?></code> &nbsp;|&nbsp;
                                Version: <code><?= (int)$fullRow['version_number'] ?></code>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <form method="POST" action="admin-legal.php?id=<?= $edit_id ?>">
                                    <input type="hidden" name="save_legal" value="1">
                                    <input type="hidden" name="id" value="<?= $edit_id ?>">

                                    <!-- Title -->
                                    <div class="form-group">
                                        <label class="form-section-label">Page Title</label>
                                        <input type="text" name="title" class="form-control"
                                               value="<?= htmlspecialchars($fullRow['title']) ?>" required>
                                    </div>

                                    <!-- HTML Content -->
                                    <div class="form-group">
                                        <label class="form-section-label">Content (HTML)</label>
                                        <small class="text-muted d-block mb-2">HTML is allowed. This content is rendered on the public page. Only admins can edit this.</small>
                                        <?php
                                        $editorCfg = [
                                            'textarea_name' => 'content_html',
                                            'textarea_id'   => 'legalBodyEditor',
                                            'quill_id'      => 'legalQuillEditor',
                                            'preview_id'    => 'legalPreviewFrame',
                                            'height'        => '420px',
                                            'initial_html'  => $fullRow['content_html'] ?? '',
                                            'instance_key'  => 'legal',
                                        ];
                                        include 'parts/editor-quill.php';
                                        ?>
                                    </div>

                                    <!-- Footer Snippet (Income Disclaimer) -->
                                    <div class="form-group">
                                        <label class="form-section-label">Footer Snippet <small style="text-transform:none;font-size:11px;">(plain text — used in premium page footers)</small></label>
                                        <textarea name="footer_snippet" class="form-control" rows="4"
                                                  placeholder="Short plain-text version for footers on landing pages..."><?= htmlspecialchars($fullRow['footer_snippet']) ?></textarea>
                                        <small class="text-muted">If left empty, the footer on premium pages will show the hardcoded fallback text.</small>
                                    </div>

                                    <!-- Status -->
                                    <div class="form-group">
                                        <label class="form-section-label">Status</label>
                                        <select name="status" class="form-control" style="max-width:220px;">
                                            <option value="published" <?= $fullRow['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                            <option value="draft"     <?= $fullRow['status'] === 'draft'     ? 'selected' : '' ?>>Draft (hidden from public)</option>
                                        </select>
                                    </div>

                                    <!-- Visibility -->
                                    <div class="form-group">
                                        <label class="form-section-label">Visibility / Placement</label>
                                        <div class="visibility-grid">
                                            <div class="d-flex align-items-center" style="gap:10px;">
                                                <input type="checkbox" name="show_in_footer" id="chk_footer"
                                                       <?= $fullRow['show_in_footer'] ? 'checked' : '' ?>>
                                                <label for="chk_footer" style="margin:0;cursor:pointer;">
                                                    Show in footer links
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center" style="gap:10px;">
                                                <input type="checkbox" name="show_on_premium_pages" id="chk_prem"
                                                       <?= $fullRow['show_on_premium_pages'] ? 'checked' : '' ?>>
                                                <label for="chk_prem" style="margin:0;cursor:pointer;">
                                                    Show on premium pages (footer snippet)
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center" style="gap:10px;">
                                                <input type="checkbox" name="show_on_registration" id="chk_reg"
                                                       <?= $fullRow['show_on_registration'] ? 'checked' : '' ?>>
                                                <label for="chk_reg" style="margin:0;cursor:pointer;">
                                                    Show on registration
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center" style="gap:10px;">
                                                <input type="checkbox" name="show_on_checkout" id="chk_check"
                                                       <?= $fullRow['show_on_checkout'] ? 'checked' : '' ?>>
                                                <label for="chk_check" style="margin:0;cursor:pointer;">
                                                    Show on checkout
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <hr style="border-color:#333;margin:24px 0;">

                                    <button type="submit" class="btn btn-primary">
                                        <i class="ft-save mr-1"></i> Save Document
                                    </button>
                                    <a href="<?= $fullRow['slug'] === 'impress'
                                        ? $baseurl . '/impress.php'
                                        : $baseurl . '/legal.php?doc=' . urlencode($fullRow['slug'])
                                    ?>" target="_blank" class="btn btn-outline-secondary ml-2">
                                        <i class="ft-external-link mr-1"></i> View Live
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted">No documents found. They will be created automatically on next page load.</p>
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
<script src="../backoffice/app-assets/vendors/js/vendors.min.js"></script>
<script src="../backoffice/app-assets/js/core/app-menu.js"></script>
<script src="../backoffice/app-assets/js/core/app.js"></script>
<script src="../backoffice/app-assets/js/notification-sidebar.js"></script>
<script src="../backoffice/app-assets/js/scroll-top.js"></script>
<script src="../backoffice/assets/js/scripts.js"></script>
</body>
</html>
