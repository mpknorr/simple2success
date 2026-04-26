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

// Ensure tables exist
mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_sequences (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    target       ENUM('lead','member') NOT NULL DEFAULT 'lead',
    day_offset   INT NOT NULL DEFAULT 1,
    subject      VARCHAR(255) NOT NULL,
    subject_b    VARCHAR(255) NOT NULL DEFAULT '',
    body         LONGTEXT NOT NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    sequence_id  INT NOT NULL,
    sent_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_seq (user_id, sequence_id)
)");

$success = '';
$error   = '';

// ── POST handling ────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'save_sequence') {
    $edit_id    = (int)($_POST['edit_id'] ?? 0);
    $target     = in_array($_POST['target'] ?? '', ['lead','member']) ? $_POST['target'] : 'lead';
    $day_offset = max(1, (int)($_POST['day_offset'] ?? 1));
    $subj       = mysqli_real_escape_string($link, $_POST['subject'] ?? '');
    $subj_b     = mysqli_real_escape_string($link, $_POST['subject_b'] ?? '');
    $body       = mysqli_real_escape_string($link, $_POST['body'] ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (empty($subj) || empty($_POST['body'])) {
        $error = 'Betreff und E-Mail-Text dürfen nicht leer sein.';
    } elseif ($edit_id > 0) {
        mysqli_query($link, "UPDATE followup_sequences SET target='$target', day_offset=$day_offset, subject='$subj', subject_b='$subj_b', body='$body', is_active=$is_active WHERE id=$edit_id");
        $success = 'E-Mail aktualisiert.';
    } else {
        mysqli_query($link, "INSERT INTO followup_sequences (target, day_offset, subject, subject_b, body, is_active) VALUES ('$target', $day_offset, '$subj', '$subj_b', '$body', $is_active)");
        $success = 'Neue Follow-Up E-Mail gespeichert.';
    }
}

if ($action === 'delete_sequence') {
    $del_id = (int)($_POST['del_id'] ?? 0);
    if ($del_id > 0) {
        mysqli_query($link, "DELETE FROM followup_sequences WHERE id=$del_id");
        mysqli_query($link, "DELETE FROM followup_log WHERE sequence_id=$del_id");
        $success = 'E-Mail gelöscht.';
    }
}

if ($action === 'toggle_active') {
    $tog_id  = (int)($_POST['tog_id'] ?? 0);
    $tog_val = (int)($_POST['tog_val'] ?? 0);
    if ($tog_id > 0) {
        mysqli_query($link, "UPDATE followup_sequences SET is_active=$tog_val WHERE id=$tog_id");
    }
    header("Location: admin-followup.php");
    exit();
}

if ($action === 'reset_log') {
    $reset_id = (int)($_POST['reset_id'] ?? 0);
    if ($reset_id > 0) {
        mysqli_query($link, "DELETE FROM followup_log WHERE sequence_id=$reset_id");
        $success = 'Versandprotokoll für diese E-Mail zurückgesetzt.';
    }
}

// Load sequence for editing
$edit_seq = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_seq = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM followup_sequences WHERE id=$eid"));
}

// Load all sequences as arrays (needed for JS preview data + table loop)
$_res_lead = mysqli_query($link, "SELECT f.*, (SELECT COUNT(*) FROM followup_log WHERE sequence_id=f.id) AS sent_count FROM followup_sequences f WHERE target='lead' ORDER BY day_offset ASC");
$_res_member = mysqli_query($link, "SELECT f.*, (SELECT COUNT(*) FROM followup_log WHERE sequence_id=f.id) AS sent_count FROM followup_sequences f WHERE target='member' ORDER BY day_offset ASC");
$sequences_lead   = [];
$sequences_member = [];
$preview_data     = [];
while ($r = mysqli_fetch_assoc($_res_lead))   { $sequences_lead[]   = $r; $preview_data[$r['id']] = ['subject' => $r['subject'], 'body' => $r['body']]; }
while ($r = mysqli_fetch_assoc($_res_member)) { $sequences_member[] = $r; $preview_data[$r['id']] = ['subject' => $r['subject'], 'body' => $r['body']]; }

// Stats
$total_leads   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM users WHERE (username IS NULL OR username = '')"))['c'];
$total_members = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM users WHERE username IS NOT NULL AND username != ''"))['c'];
$total_sent    = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_log"))['c'];

// A/B + Click Tracking Stats (tables may not exist yet — safe fallback)
$ab_a_count   = 0; $ab_b_count   = 0;
$click_count  = 0; $trigger_count = 0;
$ab_table = mysqli_query($link, "SHOW TABLES LIKE 'followup_ab_assignments'");
if ($ab_table && mysqli_num_rows($ab_table) > 0) {
    $ab_a_count  = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_ab_assignments WHERE variant='A'"))['c'];
    $ab_b_count  = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_ab_assignments WHERE variant='B'"))['c'];
}
$click_table = mysqli_query($link, "SHOW TABLES LIKE 'followup_clicks'");
if ($click_table && mysqli_num_rows($click_table) > 0) {
    $click_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_clicks"))['c'];
}
$trigger_table = mysqli_query($link, "SHOW TABLES LIKE 'followup_trigger_log'");
if ($trigger_table && mysqli_num_rows($trigger_table) > 0) {
    $trigger_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_trigger_log"))['c'];
}
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
<div class="content-wrapper">

<!-- Page Header -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title m-0">Follow-Up E-Mail Sequenzen</h4>
        <p class="text-muted mb-0">Automatische E-Mails an Leads (Step-2-Conversion) und Member (Step-4-Conversion)</p>
      </div>
    </div>
  </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show mx-2" role="alert">
  <i class="ft-check-circle"></i> <?= htmlspecialchars($success) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show mx-2" role="alert">
  <i class="ft-alert-circle"></i> <?= htmlspecialchars($error) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="row px-2 mb-1">
  <div class="col-lg-4 col-md-4 col-sm-12">
    <div class="card" style="border-left:4px solid #cb2ebc;">
      <div class="card-body py-2">
        <div class="d-flex align-items-center">
          <i class="ft-users" style="font-size:28px;color:#cb2ebc;margin-right:12px;"></i>
          <div>
            <div style="font-size:22px;font-weight:bold;"><?= $total_leads ?></div>
            <div class="text-muted" style="font-size:12px;">Aktive Leads (kein Step 2)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-4 col-sm-12">
    <div class="card" style="border-left:4px solid #1877F2;">
      <div class="card-body py-2">
        <div class="d-flex align-items-center">
          <i class="ft-star" style="font-size:28px;color:#1877F2;margin-right:12px;"></i>
          <div>
            <div style="font-size:22px;font-weight:bold;"><?= $total_members ?></div>
            <div class="text-muted" style="font-size:12px;">Member (Step 2 abgeschlossen)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-4 col-sm-12">
    <div class="card" style="border-left:4px solid #25D366;">
      <div class="card-body py-2">
        <div class="d-flex align-items-center">
          <i class="ft-send" style="font-size:28px;color:#25D366;margin-right:12px;"></i>
          <div>
            <div style="font-size:22px;font-weight:bold;"><?= $total_sent ?></div>
            <div class="text-muted" style="font-size:12px;">E-Mails gesendet (gesamt)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- A/B Test + Click Tracking Stats Row -->
<details class="mb-2 mx-2">
  <summary style="cursor:pointer;color:#ff9800;font-weight:600;padding:6px 2px;list-style:none;outline:none;">
    <i class="ft-bar-chart-2 mr-1"></i> A/B-Test &amp; Verhaltens-Tracking <span style="font-size:11px;font-weight:400;color:#aaa;margin-left:6px;">▸ Details anzeigen</span>
  </summary>
  <div class="row mt-1">
    <div class="col-12">
      <div class="card" style="border-left:4px solid #ff9800;">
        <div class="card-body py-2">
          <div class="row">
            <div class="col-lg-3 col-sm-6 mb-2">
              <div class="d-flex align-items-center">
                <span style="font-size:22px;font-weight:bold;color:#cb2ebc;margin-right:8px;"><?= $ab_a_count ?></span>
                <div><div style="font-size:12px;font-weight:600;">Variante A</div><div class="text-muted" style="font-size:11px;">Original-Betreff</div></div>
              </div>
            </div>
            <div class="col-lg-3 col-sm-6 mb-2">
              <div class="d-flex align-items-center">
                <span style="font-size:22px;font-weight:bold;color:#1877F2;margin-right:8px;"><?= $ab_b_count ?></span>
                <div><div style="font-size:12px;font-weight:600;">Variante B</div><div class="text-muted" style="font-size:11px;">Curiosity-Gap-Betreff</div></div>
              </div>
            </div>
            <div class="col-lg-3 col-sm-6 mb-2">
              <div class="d-flex align-items-center">
                <span style="font-size:22px;font-weight:bold;color:#25D366;margin-right:8px;"><?= $click_count ?></span>
                <div><div style="font-size:12px;font-weight:600;">Link-Klicks</div><div class="text-muted" style="font-size:11px;">Getrackte E-Mail-Klicks</div></div>
              </div>
            </div>
            <div class="col-lg-3 col-sm-6 mb-2">
              <div class="d-flex align-items-center">
                <span style="font-size:22px;font-weight:bold;color:#ff9800;margin-right:8px;"><?= $trigger_count ?></span>
                <div><div style="font-size:12px;font-weight:600;">Trigger-E-Mails</div><div class="text-muted" style="font-size:11px;">Verhaltensbasiert gesendet</div></div>
              </div>
            </div>
          </div>
          <p class="text-muted mb-0" style="font-size:11px;">A/B-Test läuft automatisch (50/50 Split). Trigger-E-Mails werden durch Klick-Verhalten ausgelöst. Klick-Tracking ist in allen neuen Follow-up-E-Mails aktiv.</p>
        </div>
      </div>
    </div>
  </div>
</details>

<style>
.followup-table { margin-bottom: 0; }
.followup-table thead th {
  padding: .45rem .6rem;
  font-size: 11px;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: rgba(255,255,255,.55);
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.followup-table tbody td {
  padding: .45rem .6rem;
  vertical-align: middle;
  border-top: 1px solid rgba(255,255,255,.05);
}
.followup-table tbody tr:hover { background: rgba(255,255,255,.02); }
.followup-table .btn-xs {
  padding: 2px 8px;
  font-size: 11px;
  line-height: 1.5;
  border-radius: 3px;
}
.followup-table .actions-cell { white-space: nowrap; text-align: right; }
.followup-table .actions-cell .btn-xs + form { margin-left: 4px; }
.followup-table .col-day { width: 70px; }
.followup-table .col-sent,
.followup-table .col-status { width: 90px; text-align: center; }
.followup-table .col-actions { width: 215px; text-align: right; white-space: nowrap; }
.followup-table .col-subject {
  max-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
}
</style>

<!-- Lead Sequences -->
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h5 class="card-title m-0"><i class="ft-mail mr-1" style="color:#cb2ebc;"></i> Lead-Sequenz &mdash; Step-2-Conversion</h5>
          <small class="text-muted"><?= count($sequences_lead) ?> E-Mails &middot; Empfänger: Nutzer ohne Step 2</small>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table id="followup-lead-datatable" class="table followup-table">
          <thead><tr>
            <th class="col-day">Tag</th>
            <th>Betreff</th>
            <th class="col-sent">Gesendet</th>
            <th class="col-status">Status</th>
            <th class="col-actions">Aktionen</th>
          </tr></thead>
          <tbody>
          <?php foreach ($sequences_lead as $seq): ?>
          <tr>
            <td data-order="<?= (int)$seq['day_offset'] ?>"><span class="badge badge-secondary">Tag <?= $seq['day_offset'] ?></span></td>
            <td class="col-subject" title="<?= htmlspecialchars($seq['subject']) ?>"><?= htmlspecialchars($seq['subject']) ?></td>
            <td class="text-center"><span class="badge badge-info"><?= $seq['sent_count'] ?></span></td>
            <td class="text-center">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="tog_id" value="<?= $seq['id'] ?>">
                <input type="hidden" name="tog_val" value="<?= $seq['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="btn btn-xs <?= $seq['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                  <?= $seq['is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                </button>
              </form>
            </td>
            <td class="actions-cell">
              <button type="button" class="btn btn-xs btn-outline-secondary" title="Vorschau" onclick="showFollowupPreview(<?= $seq['id'] ?>)"><i class="ft-eye"></i></button>
              <a href="admin-followup.php?edit=<?= $seq['id'] ?>" class="btn btn-xs btn-outline-primary" title="Bearbeiten"><i class="ft-edit-2"></i> Bearbeiten</a>
              <form method="POST" style="display:inline;margin-left:2px;" onsubmit="return confirm('E-Mail löschen?');">
                <input type="hidden" name="action" value="delete_sequence">
                <input type="hidden" name="del_id" value="<?= $seq['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger" title="Löschen"><i class="ft-trash-2"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Member Sequences -->
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h5 class="card-title m-0"><i class="ft-star mr-1" style="color:#1877F2;"></i> Member-Sequenz &mdash; Step-4-Conversion</h5>
          <small class="text-muted"><?= count($sequences_member) ?> E-Mails &middot; Empfänger: Nutzer mit Step 2 &mdash; Ziel: Step 4</small>
        </div>
      </div>
      <div class="card-body p-0">
        <?php if (count($sequences_member) === 0): ?>
        <div class="p-3 text-muted">
          Noch keine Member-Sequenz vorhanden. Klicke oben auf <strong>"Member-Sequenz erstellen (Step 4)"</strong> um die optimierte Step-4-Conversion-Sequenz zu aktivieren.
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table id="followup-member-datatable" class="table followup-table">
          <thead><tr>
            <th class="col-day">Tag</th>
            <th>Betreff</th>
            <th class="col-sent">Gesendet</th>
            <th class="col-status">Status</th>
            <th class="col-actions">Aktionen</th>
          </tr></thead>
          <tbody>
          <?php foreach ($sequences_member as $seq): ?>
          <tr>
            <td data-order="<?= (int)$seq['day_offset'] ?>"><span class="badge badge-primary">Tag <?= $seq['day_offset'] ?></span></td>
            <td class="col-subject" title="<?= htmlspecialchars($seq['subject']) ?>"><?= htmlspecialchars($seq['subject']) ?></td>
            <td class="text-center"><span class="badge badge-info"><?= $seq['sent_count'] ?></span></td>
            <td class="text-center">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="tog_id" value="<?= $seq['id'] ?>">
                <input type="hidden" name="tog_val" value="<?= $seq['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="btn btn-xs <?= $seq['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                  <?= $seq['is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                </button>
              </form>
            </td>
            <td class="actions-cell">
              <button type="button" class="btn btn-xs btn-outline-secondary" title="Vorschau" onclick="showFollowupPreview(<?= $seq['id'] ?>)"><i class="ft-eye"></i></button>
              <a href="admin-followup.php?edit=<?= $seq['id'] ?>" class="btn btn-xs btn-outline-primary" title="Bearbeiten"><i class="ft-edit-2"></i> Bearbeiten</a>
              <form method="POST" style="display:inline;margin-left:2px;" onsubmit="return confirm('E-Mail löschen?');">
                <input type="hidden" name="action" value="delete_sequence">
                <input type="hidden" name="del_id" value="<?= $seq['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger" title="Löschen"><i class="ft-trash-2"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit Form -->
<?php if ($edit_seq): ?>
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title m-0">E-Mail bearbeiten &mdash; Tag <?= $edit_seq['day_offset'] ?> (<?= $edit_seq['target'] ?>)</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="save_sequence">
          <input type="hidden" name="edit_id" value="<?= $edit_seq['id'] ?>">
          <div class="form-group">
            <label>Zielgruppe</label>
            <select name="target" class="form-control">
              <option value="lead" <?= $edit_seq['target']==='lead' ? 'selected' : '' ?>>Lead (kein Step 2)</option>
              <option value="member" <?= $edit_seq['target']==='member' ? 'selected' : '' ?>>Member (Step 2 abgeschlossen)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tag-Offset (Tage nach Registrierung)</label>
            <input type="number" name="day_offset" class="form-control" value="<?= $edit_seq['day_offset'] ?>" min="1">
          </div>
          <div class="form-group">
            <label>Betreff (Variante A)</label>
            <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($edit_seq['subject']) ?>">
          </div>
          <div class="form-group">
            <label>Betreff (Variante B — A/B-Test) <small class="text-muted">Optional. Leer lassen um A/B-Test zu deaktivieren.</small></label>
            <input type="text" name="subject_b" class="form-control" value="<?= htmlspecialchars($edit_seq['subject_b'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>E-Mail-Body</label>
            <?php
            $editorCfg = [
                'textarea_name' => 'body',
                'textarea_id'   => 'bodyEditTa',
                'quill_id'      => 'bodyEditQuill',
                'preview_id'    => 'bodyEditPreview',
                'height'        => '500px',
                'initial_html'  => $edit_seq['body'],
                'instance_key'  => 'edit',
            ];
            require_once "parts/editor-quill.php";
            ?>
          </div>
          <div class="form-group">
            <label class="d-flex align-items-center">
              <input type="checkbox" name="is_active" value="1" <?= $edit_seq['is_active'] ? 'checked' : '' ?> class="mr-2"> Aktiv
            </label>
          </div>
          <button type="submit" class="btn btn-primary">Speichern</button>
          <a href="admin-followup.php" class="btn btn-secondary ml-2">Abbrechen</a>
        </form>
        <form method="POST" style="display:inline;margin-left:8px;" onsubmit="return confirm('Versandprotokoll zurücksetzen?');">
          <input type="hidden" name="action" value="reset_log">
          <input type="hidden" name="reset_id" value="<?= $edit_seq['id'] ?>">
          <button type="submit" class="btn btn-outline-warning btn-sm">Versandlog zurücksetzen</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Add New -->
<div class="row px-2 mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title m-0"><i class="ft-plus mr-1"></i> Neue Follow-Up E-Mail hinzufügen</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="save_sequence">
          <input type="hidden" name="edit_id" value="0">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Zielgruppe</label>
              <select name="target" class="form-control">
                <option value="lead">Lead (kein Step 2)</option>
                <option value="member">Member (Step 2 abgeschlossen)</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Tag-Offset</label>
              <input type="number" name="day_offset" class="form-control" value="1" min="1">
            </div>
            <div class="form-group col-md-6">
              <label>Betreff</label>
              <input type="text" name="subject" class="form-control" placeholder="E-Mail-Betreff">
            </div>
          </div>
          <div class="form-group">
            <label>E-Mail-Body</label>
            <?php
            $editorCfg = [
                'textarea_name' => 'body',
                'textarea_id'   => 'bodyNewTa',
                'quill_id'      => 'bodyNewQuill',
                'preview_id'    => 'bodyNewPreview',
                'height'        => '380px',
                'initial_html'  => '',
                'instance_key'  => 'new',
            ];
            require_once "parts/editor-quill.php";
            ?>
          </div>
          <div class="form-group">
            <label class="d-flex align-items-center">
              <input type="checkbox" name="is_active" value="1" checked class="mr-2"> Aktiv
            </label>
          </div>
          <button type="submit" class="btn btn-success">E-Mail hinzufügen</button>
        </form>
      </div>
    </div>
  </div>
</div>

</div></div></div></div>

<!-- Followup Preview Modal -->
<div id="followupPreviewModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;background:rgba(0,0,0,0.75);align-items:center;justify-content:center;">
  <div style="background:#1a1a2e;border-radius:8px;width:92%;max-width:680px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.6);">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid #333;background:#111;flex-shrink:0;">
      <strong style="color:#fff;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:560px;"><i class="ft-eye" style="color:#cb2ebc;margin-right:6px;"></i><span id="fpModalTitle"></span></strong>
      <button onclick="closeFollowupPreview()" style="background:none;border:none;color:#fff;font-size:22px;line-height:1;cursor:pointer;padding:0 4px;flex-shrink:0;">&times;</button>
    </div>
    <div style="flex:1;overflow:auto;">
      <iframe id="fpModalFrame" style="width:100%;height:560px;border:none;display:block;" frameborder="0"></iframe>
    </div>
  </div>
</div>

<?php require_once "../backoffice/parts/footer.php"; ?>
<button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>
<script src="../backoffice/app-assets/vendors/js/vendors.min.js"></script>
<script src="../backoffice/app-assets/vendors/js/datatable/jquery.dataTables.min.js"></script>
<script src="../backoffice/app-assets/vendors/js/datatable/dataTables.bootstrap4.min.js"></script>
<script src="../backoffice/app-assets/js/core/app-menu.js"></script>
<script src="../backoffice/app-assets/js/core/app.js"></script>
<script src="../backoffice/app-assets/js/notification-sidebar.js"></script>
<script src="../backoffice/app-assets/js/scroll-top.js"></script>
<script src="../backoffice/assets/js/scripts.js"></script>

<script>
var fpData = <?= json_encode($preview_data) ?>;
var fpLocalBanner = '<?= htmlspecialchars($baseurl) ?>/backoffice/app-assets/img/banner/newleademailheader.jpg';
var fpProdBanner  = 'https://simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';

function showFollowupPreview(id) {
    var d = fpData[id];
    if (!d) return;
    document.getElementById('fpModalTitle').textContent = d.subject;
    var html = d.body.replace(new RegExp(fpProdBanner.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'g'), fpLocalBanner);
    html = html.replace(/\{\{name\}\}/g, 'Max Mustermann')
               .replace(/\{\{email\}\}/g, 'max@beispiel.de')
               .replace(/\{\{username\}\}/g, 'maxmustermann');
    document.getElementById('fpModalFrame').srcdoc = html;
    var m = document.getElementById('followupPreviewModal');
    m.style.display = 'flex';
    document.addEventListener('keydown', fpEscClose);
}
function closeFollowupPreview() {
    document.getElementById('followupPreviewModal').style.display = 'none';
    document.getElementById('fpModalFrame').srcdoc = '';
    document.removeEventListener('keydown', fpEscClose);
}
function fpEscClose(e) { if (e.key === 'Escape') closeFollowupPreview(); }
document.getElementById('followupPreviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeFollowupPreview();
});

// DataTables für beide Followup-Tabellen (Sort an Headern, Actions-Spalte nicht sortierbar)
jQuery(function($) {
    var dtOpts = {
        paging: false,
        searching: false,
        info: false,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [-1, 3] },
            { type: 'num', targets: [0] }
        ]
    };
    if ($('#followup-lead-datatable').length)   $('#followup-lead-datatable').DataTable(dtOpts);
    if ($('#followup-member-datatable').length) $('#followup-member-datatable').DataTable(dtOpts);
});
</script>
</body>
</html>
