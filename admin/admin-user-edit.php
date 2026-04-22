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

$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$edit_id) {
    header("Location: admin-leads.php");
    exit();
}

$success = '';
$error   = '';

// ── Save ─────────────────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fields = [
        'name'             => mysqli_real_escape_string($link, $_POST['name']       ?? ''),
        'email'            => mysqli_real_escape_string($link, $_POST['email']      ?? ''),
        'username'         => mysqli_real_escape_string($link, $_POST['username']   ?? ''),
        'paidstatus'       => mysqli_real_escape_string($link, $_POST['paidstatus'] ?? 'Free'),
        'referer'          => mysqli_real_escape_string($link, $_POST['referer']    ?? ''),
        'lang'             => mysqli_real_escape_string($link, $_POST['lang']       ?? ''),
        'country_detected' => mysqli_real_escape_string($link, $_POST['country']    ?? ''),
        'is_admin'         => isset($_POST['is_admin']) ? 1 : 0,
    ];

    $setParts = [];
    foreach ($fields as $col => $val) {
        $setParts[] = "$col='$val'";
    }
    // is_admin is int, not quoted
    $setParts[array_search("is_admin='{$fields['is_admin']}'", $setParts)] = "is_admin={$fields['is_admin']}";

    // Optional: new password
    if (!empty($_POST['new_password'])) {
        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $hashEsc = mysqli_real_escape_string($link, $hash);
        $setParts[] = "password='$hashEsc'";
    }

    $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE leadid=$edit_id";
    if (mysqli_query($link, $sql)) {
        $success = "User #$edit_id wurde gespeichert.";
    } else {
        $error = "Fehler: " . mysqli_error($link);
    }
}

// ── Load user ─────────────────────────────────────────────────────────────────
$res = mysqli_query($link, "SELECT * FROM users WHERE leadid = $edit_id");
$u   = mysqli_fetch_assoc($res);
if (!$u) {
    header("Location: admin-leads.php");
    exit();
}

// ── Lead events ───────────────────────────────────────────────────────────────
$evRes  = mysqli_query($link, "SELECT * FROM lead_events WHERE lead_id = $edit_id ORDER BY created_at DESC");
$events = $evRes ? mysqli_fetch_all($evRes, MYSQLI_ASSOC) : [];

// ── Team count (direct referrals) ─────────────────────────────────────────────
$teamRow   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS cnt FROM users WHERE referer = $edit_id"));
$teamCount = (int)($teamRow['cnt'] ?? 0);

// ── Sponsor name ─────────────────────────────────────────────────────────────
$sponsorName = '';
if (!empty($u['referer'])) {
    $sRow = mysqli_fetch_assoc(mysqli_query($link, "SELECT name, email FROM users WHERE leadid = " . (int)$u['referer']));
    if ($sRow) $sponsorName = $sRow['name'] ?: $sRow['email'];
}

// ── Flag helper ───────────────────────────────────────────────────────────────
function adminIsoFlag(string $iso): string {
    if (strlen($iso) !== 2) return '';
    $iso = strtoupper($iso);
    return mb_chr(ord($iso[0]) - 65 + 0x1F1E6) . mb_chr(ord($iso[1]) - 65 + 0x1F1E6);
}

$langLabels = [
    'en'=>'English','de'=>'Deutsch','fr'=>'Français','es'=>'Español','it'=>'Italiano',
    'nl'=>'Nederlands','pt'=>'Português','pl'=>'Polski','ru'=>'Русский','tr'=>'Türkçe',
    'ar'=>'العربية','zh'=>'中文','ja'=>'日本語','ko'=>'한국어',
];
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<style>
/* ── Admin User Edit — local styles ─────────────────────────── */
.aue-meta-table th {
    width: 38%;
    font-weight: 600;
    color: var(--s2s-text-50);
    font-size: var(--s2s-size-body-sm);
    vertical-align: top;
    padding: .55rem .75rem;
}
.aue-meta-table td {
    font-size: var(--s2s-size-body-sm);
    color: var(--s2s-text-80);
    padding: .55rem .75rem;
}
.aue-meta-table tr { border-bottom: 1px solid rgba(255,255,255,.05); }
.aue-meta-table tr:last-child { border-bottom: none; }

.aue-stat {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: var(--s2s-radius-sm);
    padding: 1rem 1.25rem;
    text-align: center;
}
.aue-stat .aue-stat-num {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--s2s-text-100);
    line-height: 1;
}
.aue-stat .aue-stat-label {
    font-size: var(--s2s-size-body-sm);
    color: var(--s2s-text-42);
    margin-top: .3rem;
}
.aue-stat.aue-stat-brand .aue-stat-num { color: var(--s2s-brand); }
.aue-stat.aue-stat-green  .aue-stat-num { color: #28c76f; }

.aue-event-row td {
    font-size: var(--s2s-size-body-sm);
    color: var(--s2s-text-65);
    vertical-align: top;
    padding: .5rem .75rem;
    border-bottom: 1px solid rgba(255,255,255,.05);
}
.aue-event-row:last-child td { border-bottom: none; }
.aue-event-type { font-weight: 600; }

.aue-step-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
    flex-shrink: 0;
}
.aue-step-done  { background: #28c76f; box-shadow: 0 0 6px rgba(40,199,111,.5); }
.aue-step-pending { background: rgba(255,255,255,.2); }

.card-header-sm {
    padding: .85rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.card-header-sm h5 {
    margin: 0;
    font-size: var(--s2s-size-body);
    font-weight: 700;
    color: var(--s2s-text-80);
}
.card-header-sm small {
    font-size: var(--s2s-size-small);
    color: var(--s2s-text-42);
    margin-left: .4rem;
}
</style>
<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">
<?php require_once "parts/navbar.php"; ?>
<div class="wrapper">
<?php require_once "parts/sidebar.php"; ?>
<div class="main-panel">
<div class="main-content">
<div class="content-overlay"></div>
<div class="content-wrapper">

<!-- ── Page header ───────────────────────────────────────────────────────── -->
<div class="content-header row mb-2">
    <div class="content-header-left col-12 d-flex align-items-center gap-2" style="gap:.75rem;">
        <a href="<?= $baseurl ?>/admin/admin-leads.php" class="btn btn-secondary btn-sm">
            <i class="ft-arrow-left"></i> Zurück
        </a>
        <div>
            <h3 class="content-header-title mb-0">
                User bearbeiten
                <small class="text-muted" style="font-size:.85rem;font-weight:400;">#<?= $edit_id ?> — <?= htmlspecialchars($u['name'] ?: $u['email']) ?></small>
            </h3>
        </div>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible mb-2" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <i class="ft-check-circle"></i> <?= $success ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible mb-2" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <?= $error ?>
</div>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════════
     ROW 1 — Stats strip
═══════════════════════════════════════════════════════════════════ -->
<div class="row mb-1">
    <div class="col-6 col-sm-3 mb-1">
        <div class="aue-stat aue-stat-brand">
            <div class="aue-stat-num"><?= $teamCount ?></div>
            <div class="aue-stat-label">Direct Team Members</div>
        </div>
    </div>
    <div class="col-6 col-sm-3 mb-1">
        <div class="aue-stat <?= !empty($u['username']) ? 'aue-stat-green' : '' ?>">
            <div class="aue-stat-num"><?= !empty($u['username']) ? '✓' : '–' ?></div>
            <div class="aue-stat-label">Step 2 (PM Partner ID)</div>
        </div>
    </div>
    <div class="col-6 col-sm-3 mb-1">
        <div class="aue-stat">
            <div class="aue-stat-num" style="font-size:1.3rem;">
                <?php
                $cc = $u['country_detected'] ?? '';
                echo $cc ? adminIsoFlag($cc) . ' ' . htmlspecialchars($cc) : '—';
                ?>
            </div>
            <div class="aue-stat-label">Country</div>
        </div>
    </div>
    <div class="col-6 col-sm-3 mb-1">
        <div class="aue-stat">
            <div class="aue-stat-num" style="font-size:1.3rem;">
                <?php
                $lang = $u['lang'] ?? '';
                echo $lang ? strtoupper($lang) : '—';
                ?>
            </div>
            <div class="aue-stat-label">Language</div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════
     ROW 2 — Edit form (left) + Info read-only (right)
═══════════════════════════════════════════════════════════════════ -->
<div class="row">

    <!-- ── LEFT: editable fields ──────────────────────────────────── -->
    <div class="col-lg-6 col-12 mb-1">
        <div class="card" style="margin-bottom:0;">
            <div class="card-header-sm">
                <h5><i class="ft-edit-2" style="color:var(--s2s-brand);margin-right:.4rem;"></i> Stammdaten bearbeiten</h5>
            </div>
            <div class="card-content">
                <div class="card-body">
                    <form method="POST" autocomplete="off">

                        <div class="form-group">
                            <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>">
                        </div>

                        <div class="form-group">
                            <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">E-Mail</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">PM Partner ID (username)</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" placeholder="z.B. 6304013">
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">Paid Status</label>
                                    <select name="paidstatus" class="form-control">
                                        <option value="Free" <?= $u['paidstatus'] === 'Free' ? 'selected' : '' ?>>Free</option>
                                        <option value="Paid" <?= $u['paidstatus'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">Sprache</label>
                                    <select name="lang" class="form-control">
                                        <option value="">— unbekannt —</option>
                                        <?php foreach ($langLabels as $code => $label): ?>
                                        <option value="<?= $code ?>" <?= ($u['lang'] ?? '') === $code ? 'selected' : '' ?>>
                                            <?= strtoupper($code) ?> — <?= $label ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">Land (ISO, z.B. DE)</label>
                                    <input type="text" name="country" class="form-control" maxlength="2"
                                           value="<?= htmlspecialchars(strtoupper($u['country_detected'] ?? '')) ?>"
                                           placeholder="DE">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">Sponsor-ID (Referer)</label>
                                    <input type="text" name="referer" class="form-control" value="<?= htmlspecialchars($u['referer']) ?>"
                                           placeholder="Lead-ID des Sponsors">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">Neues Passwort setzen <small style="opacity:.5;">(leer = nicht ändern)</small></label>
                            <input type="password" name="new_password" class="form-control" autocomplete="new-password" placeholder="••••••••••">
                        </div>

                        <div class="form-group d-flex align-items-center" style="gap:.65rem;">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="isAdminToggle"
                                       name="is_admin" <?= !empty($u['is_admin']) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="isAdminToggle"
                                       style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-50);">
                                    Admin-Rechte
                                </label>
                            </div>
                        </div>

                        <div class="mt-1">
                            <button type="submit" class="btn btn-primary s2s-btn-brand">
                                <i class="ft-save"></i> Speichern
                            </button>
                            <a href="<?= $baseurl ?>/admin/admin-leads.php" class="btn btn-secondary ml-1">Abbrechen</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT: read-only info ───────────────────────────────────── -->
    <div class="col-lg-6 col-12 mb-1">

        <!-- Tracking & Herkunft -->
        <div class="card mb-1" style="margin-bottom:.5rem!important;">
            <div class="card-header-sm">
                <h5><i class="ft-map-pin" style="color:var(--s2s-brand);margin-right:.4rem;"></i> Herkunft & Tracking</h5>
            </div>
            <div class="card-content">
                <div class="card-body p-0">
                    <table class="table aue-meta-table mb-0">
                        <tr><th>Lead-ID</th><td>#<?= $u['leadid'] ?></td></tr>
                        <tr>
                            <th>Land</th>
                            <td>
                                <?php if (!empty($u['country_detected'])): ?>
                                    <?= adminIsoFlag($u['country_detected']) ?>
                                    <?= htmlspecialchars(isoCodeToCountryName($u['country_detected'])) ?>
                                    <small style="opacity:.45;">(<?= htmlspecialchars(strtoupper($u['country_detected'])) ?>)</small>
                                <?php else: ?>
                                    <span style="opacity:.4;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Sprache</th>
                            <td>
                                <?php if (!empty($u['lang'])): ?>
                                    <?= htmlspecialchars(strtoupper($u['lang'])) ?>
                                    — <?= htmlspecialchars($langLabels[$u['lang']] ?? $u['lang']) ?>
                                <?php else: ?>
                                    <span style="opacity:.4;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr><th>IP-Adresse</th><td><?= htmlspecialchars($u['user_ip'] ?: '—') ?></td></tr>
                        <tr><th>Landing Page</th><td><?= htmlspecialchars($u['page'] ?: '—') ?></td></tr>
                        <tr><th>Source</th><td><?= htmlspecialchars($u['source'] ?: '—') ?></td></tr>
                        <?php if (!empty($u['utm_source'])): ?>
                        <tr><th>utm_source</th><td><?= htmlspecialchars($u['utm_source']) ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($u['utm_medium'])): ?>
                        <tr><th>utm_medium</th><td><?= htmlspecialchars($u['utm_medium']) ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($u['utm_campaign'])): ?>
                        <tr><th>utm_campaign</th><td><?= htmlspecialchars($u['utm_campaign']) ?></td></tr>
                        <?php endif; ?>
                        <tr>
                            <th>Sponsor</th>
                            <td>
                                <?php if ($sponsorName): ?>
                                    <a href="?id=<?= (int)$u['referer'] ?>"><?= htmlspecialchars($sponsorName) ?></a>
                                    <small style="opacity:.45;">#<?= (int)$u['referer'] ?></small>
                                <?php else: ?>
                                    <span style="opacity:.4;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr><th>Rotator</th><td><?= $u['rotator_assigned'] ? 'Ja (Rotator)' : 'Nein' ?></td></tr>
                        <tr><th>Registriert</th><td><?= date('d.m.Y H:i', strtotime($u['timestamp'])) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Step Status -->
        <div class="card" style="margin-bottom:0;">
            <div class="card-header-sm">
                <h5><i class="ft-check-square" style="color:var(--s2s-brand);margin-right:.4rem;"></i> Step-Status</h5>
            </div>
            <div class="card-content">
                <div class="card-body p-0">
                    <table class="table aue-meta-table mb-0">
                        <tr>
                            <th>Step 1 (PM-Link)</th>
                            <td>
                                <?php if (!empty($u['step1_at'])): ?>
                                    <span class="aue-step-dot aue-step-done"></span>
                                    <?= htmlspecialchars($u['step1_at']) ?>
                                    <?= !empty($u['step1_ip']) ? '<br><small style="opacity:.45;">' . htmlspecialchars($u['step1_ip']) . '</small>' : '' ?>
                                <?php else: ?>
                                    <span class="aue-step-dot aue-step-pending"></span>
                                    <span style="opacity:.4;">Nicht abgeschlossen</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Step 2 (PM Partner ID)</th>
                            <td>
                                <?php if (!empty($u['username'])): ?>
                                    <span class="aue-step-dot aue-step-done"></span>
                                    <strong style="color:#28c76f;"><?= htmlspecialchars($u['username']) ?></strong>
                                    <?php if (!empty($u['step2_at'])): ?>
                                        <br><small style="opacity:.45;"><?= htmlspecialchars($u['step2_at']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($u['step2_ip'])): ?>
                                        <br><small style="opacity:.45;"><?= htmlspecialchars($u['step2_ip']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="aue-step-dot aue-step-pending"></span>
                                    <span style="opacity:.4;">Noch nicht eingetragen</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Team-Members</th>
                            <td>
                                <?php if ($teamCount > 0): ?>
                                    <strong style="color:var(--s2s-brand);"><?= $teamCount ?></strong>
                                    direkte Mitglieder
                                    <a href="admin-leads.php" style="font-size:var(--s2s-size-small);margin-left:.4rem;opacity:.6;">→ Liste</a>
                                <?php else: ?>
                                    <span style="opacity:.4;">Noch keine</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════
     ROW 3 — Activity log
═══════════════════════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-12 mb-2">
        <div class="card" style="margin-bottom:0;">
            <div class="card-header-sm">
                <h5>
                    <i class="ft-activity" style="color:var(--s2s-brand);margin-right:.4rem;"></i>
                    Aktivitäts-Log
                    <small>(neueste zuerst)</small>
                </h5>
            </div>
            <div class="card-content">
                <div class="card-body p-0">
                    <?php if (empty($events)): ?>
                        <p style="padding:1rem 1.25rem;color:var(--s2s-text-42);font-size:var(--s2s-size-body-sm);margin:0;">
                            Keine Ereignisse vorhanden.
                        </p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-42);font-weight:600;">Zeitpunkt</th>
                                    <th style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-42);font-weight:600;">Ereignis</th>
                                    <th style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-42);font-weight:600;">Details</th>
                                    <th style="font-size:var(--s2s-size-body-sm);color:var(--s2s-text-42);font-weight:600;">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $evTypeMap = [
                                'signup_attempt'   => ['label' => 'Re-signup-Versuch auf Landing Page', 'color' => 'rgba(255,193,7,.8)'],
                                'login'            => ['label' => 'Login in Backoffice',                  'color' => 'rgba(40,199,111,.8)'],
                                'login_failed'     => ['label' => 'Fehlgeschlagener Login',               'color' => 'rgba(234,84,85,.8)'],
                                'start_step_click' => ['label' => 'Button "Next Step" geklickt',          'color' => 'rgba(0,207,232,.8)'],
                            ];
                            foreach ($events as $ev):
                                $type  = $ev['event_type'];
                                $label = $evTypeMap[$type]['label']  ?? htmlspecialchars($type);
                                $color = $evTypeMap[$type]['color']  ?? 'rgba(255,255,255,.5)';
                            ?>
                            <tr class="aue-event-row">
                                <td style="white-space:nowrap;"><?= htmlspecialchars($ev['created_at']) ?></td>
                                <td class="aue-event-type" style="color:<?= $color ?>;"><?= $label ?></td>
                                <td>
                                    <?php if (!empty($ev['page'])): ?>
                                        <small style="opacity:.6;">
                                            Page: <strong><?= htmlspecialchars($ev['page']) ?></strong>
                                            <?= !empty($ev['source'])       ? ' · ' . htmlspecialchars($ev['source'])       : '' ?>
                                            <?= !empty($ev['utm_source'])   ? ' · ' . htmlspecialchars($ev['utm_source'])   : '' ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($ev['meta'])): ?>
                                        <small style="opacity:.45;display:block;"><?= htmlspecialchars($ev['meta']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;"><?= htmlspecialchars($ev['ip'] ?? '—') ?></td>
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
</div>


</div><!-- /.content-wrapper -->
</div><!-- /.main-content -->
<?php require_once "../backoffice/parts/footer.php"; ?>
<button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
</div><!-- /.main-panel -->
</div><!-- /.wrapper -->

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
