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

// ── Filters ──────────────────────────────────────────────────────────────────
$f_from    = isset($_GET['from'])    && $_GET['from']    !== '' ? $_GET['from']    : date('Y-m-d');
$f_to      = isset($_GET['to'])      && $_GET['to']      !== '' ? $_GET['to']      : date('Y-m-d');
$f_country = isset($_GET['country']) && $_GET['country'] !== '' ? $_GET['country'] : '';
$f_lang    = isset($_GET['lang'])    && $_GET['lang']    !== '' ? $_GET['lang']    : '';
$f_page    = isset($_GET['page'])    && $_GET['page']    !== '' ? $_GET['page']    : '';
$f_source  = isset($_GET['source'])  && $_GET['source']  !== '' ? $_GET['source']  : '';

// Sanitise
$sf_from    = mysqli_real_escape_string($link, $f_from);
$sf_to      = mysqli_real_escape_string($link, $f_to);
$sf_country = mysqli_real_escape_string($link, $f_country);
$sf_lang    = mysqli_real_escape_string($link, $f_lang);
$sf_page    = mysqli_real_escape_string($link, $f_page);
$sf_source  = mysqli_real_escape_string($link, $f_source);

// Build WHERE clause for all queries
$where = "WHERE timestamp BETWEEN '$sf_from 00:00:00' AND '$sf_to 23:59:59'";
if ($sf_country !== '') $where .= " AND country_detected = '$sf_country'";
if ($sf_lang    !== '') $where .= " AND lang = '$sf_lang'";
if ($sf_page    !== '') $where .= " AND page = '$sf_page'";
if ($sf_source  !== '') $where .= " AND source = '$sf_source'";

// ── Filter dropdown options ───────────────────────────────────────────────────
$opt_countries = mysqli_query($link, "SELECT DISTINCT country_detected FROM users WHERE country_detected != '' AND country_detected IS NOT NULL ORDER BY country_detected");
$opt_langs     = mysqli_query($link, "SELECT DISTINCT lang FROM users WHERE lang != '' AND lang IS NOT NULL ORDER BY lang");
$opt_pages     = mysqli_query($link, "SELECT DISTINCT page FROM users WHERE page != '' AND page IS NOT NULL ORDER BY page");
$opt_sources   = mysqli_query($link, "SELECT DISTINCT source FROM users WHERE source != '' AND source IS NOT NULL ORDER BY source");

// ── KPI queries ───────────────────────────────────────────────────────────────
$q = function($sql) use ($link) { return mysqli_fetch_assoc(mysqli_query($link, $sql))['c']; };

$kpi_signups  = $q("SELECT COUNT(*) as c FROM users $where");
$kpi_paid     = $q("SELECT COUNT(*) as c FROM users $where AND paidstatus = 'Paid'");
$kpi_step1    = $q("SELECT COUNT(*) as c FROM users $where AND step1_at IS NOT NULL");
$kpi_step2    = $q("SELECT COUNT(*) as c FROM users $where AND username IS NOT NULL AND username != ''");
$kpi_today    = $q("SELECT COUNT(*) as c FROM users WHERE DATE(timestamp) = CURDATE()");
$kpi_week     = $q("SELECT COUNT(*) as c FROM users WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

// ── Funnel ────────────────────────────────────────────────────────────────────
$funnel = [
    ['label' => 'Signups',         'count' => (int)$kpi_signups],
    ['label' => 'Step 1 clicked',  'count' => (int)$kpi_step1],
    ['label' => 'Step 2 complete', 'count' => (int)$kpi_step2],
    ['label' => 'Paid',            'count' => (int)$kpi_paid],
];

// ── Breakdown tables ──────────────────────────────────────────────────────────
function breakdownQuery($link, $dimension, $where, $limit = 20) {
    $sql = "SELECT $dimension as dim,
                COUNT(*) AS signups,
                SUM(step1_at IS NOT NULL) AS step1,
                SUM(username IS NOT NULL AND username != '') AS step2
            FROM users $where
            GROUP BY $dimension
            ORDER BY signups DESC
            LIMIT $limit";
    $res = mysqli_query($link, $sql);
    return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}

$bd_page    = breakdownQuery($link, 'page',             $where);
$bd_source  = breakdownQuery($link, 'source',           $where);
$bd_country = breakdownQuery($link, 'country_detected', $where, 30);
$bd_lang    = breakdownQuery($link, 'lang',             $where);

function renderLandingPageBreakdown(array $rows, int $totalSignups, bool $hasVisits, ?string $trackingStart = null, bool $uniqueClicks = false, string $dateFrom = '', string $dateTo = ''): void {
    if (empty($rows)) {
        echo '<p style="padding:.75rem 1.25rem;color:rgba(255,255,255,.3);font-size:.82rem;margin:0;">Keine Daten.</p>';
        return;
    }
    // Warning banner if tracking only started recently
    if ($hasVisits && $trackingStart !== null) {
        echo '<div style="background:rgba(255,159,67,.08);border-left:3px solid #ff9f43;padding:10px 14px;margin:8px 12px;border-radius:4px;font-size:.8rem;color:rgba(255,255,255,.65);">'
           . '⚠ Klick-Tracking aktiv seit <strong>' . htmlspecialchars($trackingStart) . '</strong>. '
           . 'Lead-Rate vor diesem Datum nicht vergleichbar (keine Klicks erfasst).'
           . '</div>';
    }
    $max = max(1, $rows[0]['signups'] ?? 1);
    echo '<div class="table-responsive"><table class="st-table"><thead><tr>';
    echo '<th>Page</th>';
    if ($hasVisits) echo '<th class="text-right">' . ($uniqueClicks ? 'Unique Klicks' : 'Klicks') . '</th>';
    echo '<th class="text-right">Leads</th>';
    if ($hasVisits) echo '<th class="text-right">Lead-Rate</th>';
    echo '<th class="text-right">Re-Signups</th>';
    echo '<th class="text-right">Step1</th><th class="text-right">Step2</th><th style="min-width:80px;">Anteil</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $visits    = (int)($r['visits'] ?? 0);
        $signups   = (int)$r['signups'];
        $resignups = (int)($r['resignups'] ?? 0);
        $ctr       = $visits > 0 ? round($signups / $visits * 100, 1) : null;
        $pct_total = $totalSignups > 0 ? round($signups / $totalSignups * 100) : 0;
        $bar_pct   = $max > 0 ? round($signups / $max * 100) : 0;
        $s1_pct    = $signups > 0 ? round($r['step1'] / $signups * 100) : 0;
        $s2_pct    = $signups > 0 ? round($r['step2'] / $signups * 100) : 0;
        // Lead-rate color: ≥20% green, 10-20% yellow, <10% red
        $ctrColor  = $ctr === null ? '' : ($ctr >= 20 ? '#28c76f' : ($ctr >= 10 ? '#ff9f43' : '#ea5455'));
        $dim_esc   = htmlspecialchars($r['dim'] ?: '', ENT_QUOTES);
        $df_esc    = htmlspecialchars($dateFrom, ENT_QUOTES);
        $dt_esc    = htmlspecialchars($dateTo, ENT_QUOTES);
        $drillBase = 'data-drill="1" data-dim-type="page" data-dim-value="' . $dim_esc . '" data-from="' . $df_esc . '" data-to="' . $dt_esc . '"';
        $drillStyle = 'cursor:pointer;text-decoration:underline dotted;text-underline-offset:3px;';

        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($r['dim'] ?: '—') . '</strong></td>';
        if ($hasVisits) {
            echo '<td class="text-right">' . ($visits > 0 ? number_format($visits) : '<span style="opacity:.3;">—</span>') . '</td>';
        }
        echo '<td class="text-right" ' . $drillBase . ' data-metric="signups" style="' . $drillStyle . '"><strong>' . $signups . '</strong> <small style="opacity:.4;">(' . $pct_total . '%)</small></td>';
        if ($hasVisits) {
            echo '<td class="text-right" style="font-weight:700;color:' . $ctrColor . ';">'
               . ($ctr !== null ? $ctr . '%' : '<span style="opacity:.3;">—</span>') . '</td>';
        }
        echo '<td class="text-right" ' . ($resignups > 0 ? $drillBase . ' data-metric="resignups" style="color:#ff9f43;' . $drillStyle . '"' : 'style="color:#ff9f43;"') . '>'
           . ($resignups > 0 ? $resignups : '<span style="opacity:.3;">—</span>') . '</td>';
        echo '<td class="text-right" ' . ($r['step1'] > 0 ? $drillBase . ' data-metric="step1" style="color:#00cfe8;' . $drillStyle . '"' : 'style="color:#00cfe8;"') . '>' . $r['step1'] . ' <small style="opacity:.4;">(' . $s1_pct . '%)</small></td>';
        echo '<td class="text-right" ' . ($r['step2'] > 0 ? $drillBase . ' data-metric="step2" style="color:#9c8bd4;' . $drillStyle . '"' : 'style="color:#9c8bd4;"') . '>' . $r['step2'] . ' <small style="opacity:.4;">(' . $s2_pct . '%)</small></td>';
        echo '<td><div class="st-bar-wrap"><div class="st-bar-fill" style="width:' . $bar_pct . '%;"></div></div></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

// ── Landing page breakdown with visits (click-to-lead) ────────────────────────
$_pvTableExists = mysqli_num_rows(mysqli_query($link, "SHOW TABLES LIKE 'page_visits'")) > 0;
$bd_page_visits = [];
$_pvTrackingStart = null; // earliest date in page_visits
$_pvUniqueClicks = isset($_GET['unique_clicks']) && $_GET['unique_clicks'] === '1';
if ($_pvTableExists) {
    $tsRow = mysqli_fetch_assoc(mysqli_query($link, "SELECT DATE(MIN(visited_at)) AS first_day FROM page_visits"));
    $_pvTrackingStart = $tsRow['first_day'] ?? null;

    $pvWhere = "visited_at BETWEEN '$sf_from 00:00:00' AND '$sf_to 23:59:59'";
    if ($sf_source !== '') $pvWhere .= " AND source = '$sf_source'";
    $clickExpr = $_pvUniqueClicks
        ? "COUNT(DISTINCT CONCAT(ip, '|', page, '|', DATE(visited_at)))"
        : "COUNT(*)";
    $pvSql = "SELECT u.page AS dim,
                     COUNT(DISTINCT u.leadid) AS signups,
                     SUM(u.step1_at IS NOT NULL) AS step1,
                     SUM(u.username IS NOT NULL AND u.username != '') AS step2,
                     COALESCE(MAX(v.visits), 0) AS visits,
                     COALESCE(MAX(re.resignups), 0) AS resignups
              FROM users u
              LEFT JOIN (
                  SELECT page, $clickExpr AS visits FROM page_visits WHERE $pvWhere GROUP BY page
              ) v ON v.page = u.page
              LEFT JOIN (
                  SELECT page, COUNT(*) AS resignups
                  FROM lead_events
                  WHERE event_type = 'signup_attempt'
                    AND created_at BETWEEN '$sf_from 00:00:00' AND '$sf_to 23:59:59'
                  GROUP BY page
              ) re ON re.page = u.page
              $where
              GROUP BY u.page
              ORDER BY signups DESC
              LIMIT 20";
    $pvRes = mysqli_query($link, $pvSql);
    if ($pvRes) $bd_page_visits = mysqli_fetch_all($pvRes, MYSQLI_ASSOC);
}

// ── Daily trend (respects date filter) ───────────────────────────────────────
$daily_res = mysqli_query($link, "SELECT DATE(timestamp) as day, COUNT(*) as c FROM users
    $where GROUP BY DATE(timestamp) ORDER BY day ASC");
$daily_labels = [];
$daily_data   = [];
while ($row = mysqli_fetch_assoc($daily_res)) {
    $daily_labels[] = $row['day'];
    $daily_data[]   = (int)$row['c'];
}

// ── Cron runs ─────────────────────────────────────────────────────────────────
$_cronTableExists = mysqli_num_rows(mysqli_query($link, "SHOW TABLES LIKE 'cron_runs'")) > 0;
$cron_runs = [];
if ($_cronTableExists) {
    $cr = mysqli_query($link, "SELECT * FROM cron_runs ORDER BY started_at DESC LIMIT 14");
    if ($cr) $cron_runs = mysqli_fetch_all($cr, MYSQLI_ASSOC);
}

// ── Follow-up performance (by sent_at in date range) ─────────────────────────
$_fup_click_exists = mysqli_num_rows(mysqli_query($link, "SHOW TABLES LIKE 'followup_clicks'")) > 0;
$_fup_click_join   = $_fup_click_exists
    ? "LEFT JOIN (SELECT sequence_id, COUNT(*) AS clicked FROM followup_clicks WHERE clicked_at BETWEEN '$sf_from 00:00:00' AND '$sf_to 23:59:59' GROUP BY sequence_id) fc ON fc.sequence_id = fs.id"
    : '';
$_fup_click_sel    = $_fup_click_exists ? 'COALESCE(fc.clicked, 0)' : '0';

$fup_perf_res = mysqli_query($link,
    "SELECT fs.id, fs.subject, fs.target, fs.day_offset,
            COUNT(fl.id)                                            AS sent,
            SUM(fl.status IN ('delivered','opened','clicked'))      AS delivered,
            SUM(fl.status IN ('opened','clicked'))                  AS opened,
            SUM(fl.status = 'bounced')                              AS bounced,
            SUM(fl.status = 'spam')                                 AS spam,
            SUM(fl.status = 'failed')                               AS failed,
            $_fup_click_sel                                         AS clicked
     FROM followup_sequences fs
     LEFT JOIN followup_log fl ON fl.sequence_id = fs.id
       AND fl.sent_at BETWEEN '$sf_from 00:00:00' AND '$sf_to 23:59:59'
     $_fup_click_join
     GROUP BY fs.id
     ORDER BY fs.target ASC, fs.day_offset ASC");
$fup_perf_rows = $fup_perf_res ? mysqli_fetch_all($fup_perf_res, MYSQLI_ASSOC) : [];

// ── Activity log summary ──────────────────────────────────────────────────────
$ev_where = "WHERE created_at BETWEEN '$sf_from 00:00:00' AND '$sf_to 23:59:59'";
$ev_res   = mysqli_query($link, "SELECT event_type, COUNT(*) AS cnt FROM lead_events $ev_where GROUP BY event_type ORDER BY cnt DESC");
$ev_rows  = $ev_res ? mysqli_fetch_all($ev_res, MYSQLI_ASSOC) : [];

$evLabels = [
    'login'              => '🔓 Logins',
    'login_failed'       => '⚠️ Fehlgeschlagene Logins',
    'signup_attempt'     => '🔁 Re-signup-Versuche',
    'start_step_click'   => '👆 "Nächste Schritte"-Klick (von Dashboard)',
    'video_play'         => '🎬 Präsentation gestartet',
    'video_25'           => '🎬 Präsentation 25% gesehen',
    'video_50'           => '🎬 Präsentation 50% gesehen',
    'video_75'           => '🎬 Präsentation 75% gesehen',
    'video_complete'     => '🎬 Präsentation vollständig gesehen',
    'video2_play'        => '🎬 Founder-Video gestartet',
    'video3_play'        => '🎬 Incentive-Video gestartet',
    'video4_play'        => '🎬 Direct-Cash-Video gestartet',
    'step1_button_click' => '🔗 Step 1 Registrierungslink geklickt',
    'email_sent'         => '📧 E-Mail gesendet',
    'email_hard_bounce'  => '⛔ E-Mail Hard Bounce',
    'email_spam'         => '🚫 E-Mail als Spam markiert',
];

// ── Flag helper ───────────────────────────────────────────────────────────────
function statsFlag(string $iso): string {
    if (strlen($iso) !== 2) return '';
    $iso = strtoupper($iso);
    return mb_chr(ord($iso[0]) - 65 + 0x1F1E6) . mb_chr(ord($iso[1]) - 65 + 0x1F1E6);
}

$langLabels = ['en'=>'English','de'=>'Deutsch','fr'=>'Français','es'=>'Español','it'=>'Italiano',
    'nl'=>'Nederlands','pt'=>'Português','pl'=>'Polski','ru'=>'Русский','tr'=>'Türkçe',
    'ar'=>'Arabic','zh'=>'中文','ja'=>'日本語','ko'=>'한국어'];
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<style>
/* ── Stats page local styles ───────────────────────────── */
.st-kpi {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.09);
    border-radius: var(--s2s-radius);
    padding: 1.4rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.1rem;
}
.st-kpi-icon {
    font-size: 1.7rem;
    opacity: .6;
    flex-shrink: 0;
    width: 2.4rem;
    text-align: center;
}
.st-kpi-num {
    font-size: 2rem;
    font-weight: 800;
    color: var(--s2s-text-100);
    line-height: 1;
}
.st-kpi-label {
    font-size: var(--s2s-size-body-sm);
    color: var(--s2s-text-42);
    margin-top: .2rem;
}
.st-kpi.brand  .st-kpi-num { color: var(--s2s-brand); }
.st-kpi.green  .st-kpi-num { color: #28c76f; }
.st-kpi.blue   .st-kpi-num { color: #00cfe8; }
.st-kpi.orange .st-kpi-num { color: #ff9f43; }
.st-kpi.purple .st-kpi-num { color: #9c8bd4; }
.st-kpi.teal   .st-kpi-num { color: #4dc9c9; }

.st-card-header {
    padding: .85rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex;
    align-items: center;
    gap: .5rem;
}
.st-card-header h5 {
    margin: 0;
    font-size: var(--s2s-size-body);
    font-weight: 700;
    color: var(--s2s-text-80);
}
.st-table { width: 100%; font-size: var(--s2s-size-body-sm); }
.st-table th {
    color: var(--s2s-text-42);
    font-weight: 600;
    font-size: var(--s2s-size-small);
    padding: .5rem .75rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    white-space: nowrap;
}
.st-table td {
    padding: .5rem .75rem;
    border-bottom: 1px solid rgba(255,255,255,.04);
    color: var(--s2s-text-65);
    vertical-align: middle;
}
.st-table tr:last-child td { border-bottom: none; }
.st-table td strong { color: var(--s2s-text-100); }

.st-bar-wrap {
    background: rgba(255,255,255,.07);
    border-radius: 3px;
    height: 5px;
    min-width: 60px;
}
.st-bar-fill {
    background: var(--s2s-brand);
    border-radius: 3px;
    height: 5px;
}

/* funnel */
.st-funnel-pct-main { color: var(--s2s-text-100); font-weight: 700; font-size: var(--s2s-size-body); }
.st-funnel-pct-prev { color: var(--s2s-text-42); font-size: var(--s2s-size-small); }

/* filter bar */
.st-filter-bar {
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: var(--s2s-radius-sm);
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
}
.st-filter-bar label {
    font-size: var(--s2s-size-small);
    color: var(--s2s-text-42);
    font-weight: 600;
    display: block;
    margin-bottom: .25rem;
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

<!-- ── Page header ── -->
<div class="content-header row mb-1">
    <div class="content-header-left col-12">
        <h3 class="content-header-title mb-0">Admin — Statistiken</h3>
        <small style="color:var(--s2s-text-42);">
            Gefiltert: <?= htmlspecialchars($f_from) ?> – <?= htmlspecialchars($f_to) ?>
            <?= $f_country ? ' · ' . htmlspecialchars(strtoupper($f_country)) : '' ?>
            <?= $f_lang    ? ' · ' . htmlspecialchars(strtoupper($f_lang))    : '' ?>
            <?= $f_page    ? ' · ' . htmlspecialchars($f_page)                : '' ?>
            <?= $f_source  ? ' · Source: <strong>' . htmlspecialchars($f_source) . '</strong>' : '' ?>
        </small>
    </div>
</div>

<!-- ══ FILTER BAR ═══════════════════════════════════════════════════════════ -->
<div class="st-filter-bar">
    <form method="GET" class="row" style="row-gap:.5rem;">
        <div class="col-6 col-sm-4 col-lg-2">
            <label>Von</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($f_from) ?>">
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label>Bis</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($f_to) ?>">
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label>Land</label>
            <select name="country" class="form-control form-control-sm">
                <option value="">Alle Länder</option>
                <?php while ($r = mysqli_fetch_assoc($opt_countries)): ?>
                <option value="<?= htmlspecialchars($r['country_detected']) ?>" <?= $f_country === $r['country_detected'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(strtoupper($r['country_detected'])) ?> — <?= htmlspecialchars(isoCodeToCountryName($r['country_detected'])) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label>Sprache</label>
            <select name="lang" class="form-control form-control-sm">
                <option value="">Alle Sprachen</option>
                <?php while ($r = mysqli_fetch_assoc($opt_langs)): ?>
                <option value="<?= htmlspecialchars($r['lang']) ?>" <?= $f_lang === $r['lang'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(strtoupper($r['lang'])) ?> — <?= htmlspecialchars($langLabels[$r['lang']] ?? $r['lang']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label>Landing Page</label>
            <select name="page" class="form-control form-control-sm">
                <option value="">Alle Pages</option>
                <?php while ($r = mysqli_fetch_assoc($opt_pages)): ?>
                <option value="<?= htmlspecialchars($r['page']) ?>" <?= $f_page === $r['page'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['page']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <label>Traffic-Quelle</label>
            <select name="source" class="form-control form-control-sm">
                <option value="">Alle Quellen</option>
                <?php while ($r = mysqli_fetch_assoc($opt_sources)): ?>
                <option value="<?= htmlspecialchars($r['source']) ?>" <?= $f_source === $r['source'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['source']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-12 col-sm-4 col-lg-2 d-flex align-items-end" style="gap:.4rem;">
            <button type="submit" class="btn btn-primary btn-sm s2s-btn-brand flex-grow-1">Filtern</button>
            <a href="admin-stats.php" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>


<!-- ══ KPI CARDS ═══════════════════════════════════════════════════════════ -->
<div class="row mb-1" style="row-gap:.5rem;">
    <div class="col-6 col-lg-4 col-xl-2 mb-1">
        <div class="st-kpi brand">
            <div class="st-kpi-icon"><i class="ft-user-plus"></i></div>
            <div><div class="st-kpi-num"><?= $kpi_signups ?></div><div class="st-kpi-label">Signups (Zeitraum)</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl-2 mb-1">
        <div class="st-kpi blue">
            <div class="st-kpi-icon"><i class="ft-mouse-pointer"></i></div>
            <div><div class="st-kpi-num"><?= $kpi_step1 ?></div><div class="st-kpi-label">Step 1 geklickt</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl-2 mb-1">
        <div class="st-kpi purple">
            <div class="st-kpi-icon"><i class="ft-check-circle"></i></div>
            <div><div class="st-kpi-num"><?= $kpi_step2 ?></div><div class="st-kpi-label">Step 2 abgeschlossen</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl-2 mb-1">
        <div class="st-kpi green">
            <div class="st-kpi-icon"><i class="ft-dollar-sign"></i></div>
            <div><div class="st-kpi-num"><?= $kpi_paid ?></div><div class="st-kpi-label">Paid</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl-2 mb-1">
        <div class="st-kpi orange">
            <div class="st-kpi-icon"><i class="ft-calendar"></i></div>
            <div><div class="st-kpi-num"><?= $kpi_today ?></div><div class="st-kpi-label">Heute (gesamt)</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl-2 mb-1">
        <div class="st-kpi teal">
            <div class="st-kpi-icon"><i class="ft-trending-up"></i></div>
            <div><div class="st-kpi-num"><?= $kpi_week ?></div><div class="st-kpi-label">Letzte 7 Tage (gesamt)</div></div>
        </div>
    </div>
</div>


<!-- ══ DAILY CHART + FUNNEL ════════════════════════════════════════════════ -->
<div class="row mb-1">

    <!-- Daily trend chart -->
    <div class="col-lg-8 col-12 mb-1">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header">
                <i class="ft-activity" style="color:var(--s2s-brand);"></i>
                <h5>Signups pro Tag (im gewählten Zeitraum)</h5>
            </div>
            <div class="card-content">
                <div class="card-body" style="padding:1.25rem;">
                    <?php if (empty($daily_labels)): ?>
                        <p style="color:var(--s2s-text-42);font-size:var(--s2s-size-body-sm);margin:0;">Keine Daten für diesen Zeitraum.</p>
                    <?php else: ?>
                    <canvas id="dailyChart" style="width:100%;max-height:260px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversion funnel -->
    <div class="col-lg-4 col-12 mb-1">
        <div class="card" style="margin-bottom:0;height:100%;">
            <div class="st-card-header">
                <i class="ft-filter" style="color:var(--s2s-brand);"></i>
                <h5>Conversion Funnel</h5>
            </div>
            <div class="card-content">
                <div class="card-body p-0">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th>Stufe</th>
                                <th style="text-align:right;">Anzahl</th>
                                <th style="text-align:right;">% Signups</th>
                                <th style="text-align:right;">% vorher</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $base = max(1, $funnel[0]['count']);
                        $funnelMetrics = ['signups', 'step1', 'step2', 'paid'];
                        foreach ($funnel as $i => $stage):
                            $pct_signup = $base > 0 ? round($stage['count'] / $base * 100) : 0;
                            $prev_cnt   = $i > 0 ? max(1, $funnel[$i-1]['count']) : $base;
                            $pct_prev   = $prev_cnt > 0 ? round($stage['count'] / $prev_cnt * 100) : 0;
                            $fm = $funnelMetrics[$i] ?? 'signups';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($stage['label']) ?></td>
                            <td style="text-align:right;">
                                <?php if ($stage['count'] > 0): ?>
                                <strong data-drill="1" data-dim-type="funnel" data-dim-value="" data-metric="<?= $fm ?>" data-from="<?= htmlspecialchars($f_from, ENT_QUOTES) ?>" data-to="<?= htmlspecialchars($f_to, ENT_QUOTES) ?>" style="cursor:pointer;text-decoration:underline dotted;text-underline-offset:3px;"><?= $stage['count'] ?></strong>
                                <?php else: ?>
                                <strong><?= $stage['count'] ?></strong>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;" class="st-funnel-pct-main"><?= $pct_signup ?>%</td>
                            <td style="text-align:right;" class="st-funnel-pct-prev">
                                <?= $i === 0 ? '—' : $pct_prev . '%' ?>
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


<!-- ══ BREAKDOWN TABLES ════════════════════════════════════════════════════ -->
<?php
function renderBreakdown(string $title, string $icon, array $rows, int $totalSignups, ?callable $labelFn = null, string $dimField = '', string $dateFrom = '', string $dateTo = ''): void {
    if (empty($rows)) {
        echo '<p style="padding:.75rem 1.25rem;color:rgba(255,255,255,.3);font-size:.82rem;margin:0;">Keine Daten.</p>';
        return;
    }
    $hasDrill  = $dimField !== '' && $dateFrom !== '';
    $drillCss  = 'cursor:pointer;text-decoration:underline dotted;text-underline-offset:3px;';
    $max = max(1, $rows[0]['signups'] ?? 1);
    echo '<div class="table-responsive"><table class="st-table">';
    echo '<thead><tr><th>' . htmlspecialchars($title) . '</th><th>Signups</th><th>Step1</th><th>Step2</th><th style="min-width:80px;">Anteil</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $dim   = $r['dim'] ?? '';
        $label = $labelFn ? $labelFn($dim) : (htmlspecialchars($dim ?: '—'));
        $pct_total = $totalSignups > 0 ? round($r['signups'] / $totalSignups * 100) : 0;
        $bar_pct   = $max > 0 ? round($r['signups'] / $max * 100) : 0;
        $s1_pct    = $r['signups'] > 0 ? round($r['step1'] / $r['signups'] * 100) : 0;
        $s2_pct    = $r['signups'] > 0 ? round($r['step2'] / $r['signups'] * 100) : 0;

        $da = '';
        if ($hasDrill) {
            $dv  = htmlspecialchars($dim, ENT_QUOTES);
            $df  = htmlspecialchars($dateFrom, ENT_QUOTES);
            $dt  = htmlspecialchars($dateTo, ENT_QUOTES);
            $da  = "data-drill=\"1\" data-dim-type=\"$dimField\" data-dim-value=\"$dv\" data-from=\"$df\" data-to=\"$dt\"";
        }

        echo '<tr>';
        echo '<td>' . $label . '</td>';
        echo '<td ' . ($hasDrill ? $da . ' data-metric="signups" style="' . $drillCss . '"' : '') . '><strong>' . $r['signups'] . '</strong> <small style="opacity:.4;">(' . $pct_total . '%)</small></td>';
        echo '<td style="color:#00cfe8;' . ($hasDrill && $r['step1'] > 0 ? $drillCss : '') . '" ' . ($hasDrill && $r['step1'] > 0 ? $da . ' data-metric="step1"' : '') . '>' . $r['step1'] . ' <small style="opacity:.4;">(' . $s1_pct . '%)</small></td>';
        echo '<td style="color:#9c8bd4;' . ($hasDrill && $r['step2'] > 0 ? $drillCss : '') . '" ' . ($hasDrill && $r['step2'] > 0 ? $da . ' data-metric="step2"' : '') . '>' . $r['step2'] . ' <small style="opacity:.4;">(' . $s2_pct . '%)</small></td>';
        echo '<td><div class="st-bar-wrap"><div class="st-bar-fill" style="width:' . $bar_pct . '%;"></div></div></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
?>

<div class="row mb-1">
    <div class="col-lg-6 col-12 mb-1">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header">
                <i class="ft-layout" style="color:var(--s2s-brand);"></i>
                <h5>Landing Page</h5>
                <?php if ($_pvTableExists): ?>
                <?php
                $ucToggleUrl = '?' . http_build_query(array_merge($_GET, ['unique_clicks' => $_pvUniqueClicks ? '0' : '1']));
                ?>
                <a href="<?= htmlspecialchars($ucToggleUrl) ?>" style="margin-left:auto;font-size:.75rem;padding:2px 8px;border:1px solid rgba(255,255,255,.15);border-radius:4px;color:<?= $_pvUniqueClicks ? '#28c76f' : 'var(--s2s-text-42)' ?>;text-decoration:none;" title="Klicks nach IP+Tag deduplizieren">
                    <?= $_pvUniqueClicks ? '✓ Unique Klicks' : 'Unique Klicks' ?>
                </a>
                <?php else: ?>
                <span style="margin-left:auto;font-size:.75rem;color:var(--s2s-text-42);opacity:.5;" title="Klick-Tracking startet beim nächsten Seitenbesuch">⚠ Tracking noch nicht aktiv</span>
                <?php endif; ?>
            </div>
            <div class="card-content">
                <?php
                if ($_pvTableExists && !empty($bd_page_visits)) {
                    renderLandingPageBreakdown($bd_page_visits, (int)$kpi_signups, true, $_pvTrackingStart, $_pvUniqueClicks, $f_from, $f_to);
                } else {
                    renderBreakdown('Page', 'ft-layout', $bd_page, (int)$kpi_signups, null, 'page', $f_from, $f_to);
                }
                ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-12 mb-1">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header"><i class="ft-cpu" style="color:var(--s2s-brand);"></i><h5>Traffic-Quelle</h5></div>
            <div class="card-content">
                <?php renderBreakdown('Source', 'ft-cpu', $bd_source, (int)$kpi_signups, null, 'source', $f_from, $f_to); ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-1">
    <div class="col-lg-6 col-12 mb-1">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header"><i class="ft-globe" style="color:var(--s2s-brand);"></i><h5>Land</h5></div>
            <div class="card-content">
                <?php renderBreakdown('Land', 'ft-globe', $bd_country, (int)$kpi_signups, function($iso) {
                    if (!$iso) return '<span style="opacity:.4;">—</span>';
                    $flag = strlen($iso) === 2 ? mb_chr(ord(strtoupper($iso)[0]) - 65 + 0x1F1E6) . mb_chr(ord(strtoupper($iso)[1]) - 65 + 0x1F1E6) : '';
                    return $flag . ' ' . htmlspecialchars(isoCodeToCountryName($iso));
                }, 'country_detected', $f_from, $f_to); ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-12 mb-1">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header"><i class="ft-message-square" style="color:var(--s2s-brand);"></i><h5>Sprache</h5></div>
            <div class="card-content">
                <?php renderBreakdown('Sprache', 'ft-message-square', $bd_lang, (int)$kpi_signups, function($lang) use ($langLabels) {
                    if (!$lang) return '<span style="opacity:.4;">—</span>';
                    return htmlspecialchars(strtoupper($lang)) . ' — ' . htmlspecialchars($langLabels[$lang] ?? $lang);
                }, 'lang', $f_from, $f_to); ?>
            </div>
        </div>
    </div>
</div>


<!-- ══ CRON RUNS ════════════════════════════════════════════════════════════ -->
<?php if ($_cronTableExists): ?>
<div class="row mb-2">
    <div class="col-12">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header">
                <i class="ft-clock" style="color:var(--s2s-brand);"></i>
                <h5>Letzte Cron-Ausführungen</h5>
                <span style="margin-left:auto;font-size:.75rem;color:var(--s2s-text-42);">letzte 14 Einträge</span>
            </div>
            <div class="card-content">
                <div class="card-body p-0">
                    <?php if (empty($cron_runs)): ?>
                        <p style="padding:.75rem 1.25rem;color:rgba(255,255,255,.3);font-size:.82rem;margin:0;">Noch keine Ausführungen protokolliert.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                    <table class="st-table">
                        <thead><tr>
                            <th>Job</th>
                            <th>Gestartet</th>
                            <th>Beendet</th>
                            <th class="text-right">Laufzeit</th>
                            <th class="text-right">Versendet</th>
                            <th>Status</th>
                            <th>Fehler</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($cron_runs as $cr): ?>
                        <?php
                            $dur = (strtotime($cr['ended_at']) && strtotime($cr['started_at']))
                                ? (strtotime($cr['ended_at']) - strtotime($cr['started_at'])) . 's'
                                : '—';
                            $statusColor = $cr['status'] === 'ok' ? '#28c76f' : ($cr['status'] === 'empty' ? '#ff9f43' : '#ea5455');
                            $statusLabel = $cr['status'] === 'ok' ? 'OK' : ($cr['status'] === 'empty' ? 'Leer' : 'Fehler');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cr['job_name']) ?></strong></td>
                            <td><?= htmlspecialchars($cr['started_at']) ?></td>
                            <td><?= htmlspecialchars($cr['ended_at'] ?? '—') ?></td>
                            <td class="text-right"><?= $dur ?></td>
                            <td class="text-right"><strong><?= (int)$cr['sent'] ?></strong></td>
                            <td><span style="color:<?= $statusColor ?>;font-weight:700;"><?= $statusLabel ?></span></td>
                            <td style="font-size:.78rem;color:var(--s2s-text-42);max-width:300px;word-break:break-all;">
                                <?= $cr['errors'] ? htmlspecialchars(mb_substr($cr['errors'], 0, 120)) . (mb_strlen($cr['errors']) > 120 ? '…' : '') : '<span style="opacity:.3;">—</span>' ?>
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
</div>
<?php endif; ?>

<!-- ══ FOLLOW-UP PERFORMANCE ════════════════════════════════════════════════ -->
<?php if (!empty($fup_perf_rows)): ?>
<div class="row mb-2">
    <div class="col-12">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header"><i class="ft-mail" style="color:var(--s2s-brand);"></i><h5>Follow-up Performance <small style="font-weight:400;font-size:.75rem;opacity:.5;">(Versanddatum im gewählten Zeitraum)</small></h5></div>
            <div class="card-content">
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="st-table" style="font-size:.8rem;">
                        <thead><tr>
                            <th>Tag</th>
                            <th>Template</th>
                            <th>Sequenz</th>
                            <th class="text-center">Gesendet</th>
                            <th class="text-center">Zugestellt</th>
                            <th class="text-center">Geöffnet</th>
                            <th class="text-center">Geklickt</th>
                            <th class="text-center">Bounce</th>
                            <th class="text-center">Spam</th>
                            <th class="text-center">Delivery%</th>
                            <th class="text-center">Open%</th>
                            <th class="text-center">CTR%</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($fup_perf_rows as $fp):
                            $fpSent  = max(1, (int)$fp['sent']);
                            $fpDel   = (int)$fp['delivered'];
                            $fpOpen  = (int)$fp['opened'];
                            $fpClick = (int)$fp['clicked'];
                            $fpBnc   = (int)$fp['bounced'];
                            $fpSpam  = (int)$fp['spam'];
                            $delivPct = $fpSent > 1 ? round($fpDel  / $fpSent * 100) : 0;
                            $openPct  = $fpSent > 1 ? round($fpOpen / $fpSent * 100) : 0;
                            $ctrPct   = $fpSent > 1 ? round($fpClick/ $fpSent * 100) : 0;
                        ?>
                        <tr>
                            <td><span class="badge badge-secondary" style="font-size:.7rem;">Tag <?= (int)$fp['day_offset'] ?></span></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($fp['subject']) ?>"><?= htmlspecialchars($fp['subject']) ?></td>
                            <td><?= $fp['target'] === 'member' ? '<span style="color:#1877F2;">Member</span>' : '<span style="color:#cb2ebc;">Lead</span>' ?></td>
                            <td class="text-center"><?= (int)$fp['sent'] ?: '<span style="opacity:.3;">0</span>' ?></td>
                            <td class="text-center" style="color:#28c76f;"><?= $fpDel ?: '<span style="opacity:.3;">0</span>' ?></td>
                            <td class="text-center" style="color:#00cfe8;"><?= $fpOpen ?: '<span style="opacity:.3;">0</span>' ?></td>
                            <td class="text-center" style="color:#00cfe8;"><?= $fpClick ?: '<span style="opacity:.3;">0</span>' ?></td>
                            <td class="text-center" style="color:<?= $fpBnc > 0 ? '#ff9800' : 'inherit' ?>;"><?= $fpBnc ?: '<span style="opacity:.3;">0</span>' ?></td>
                            <td class="text-center" style="color:<?= $fpSpam > 0 ? '#ea5455' : 'inherit' ?>;"><?= $fpSpam ?: '<span style="opacity:.3;">0</span>' ?></td>
                            <td class="text-center"><?php if ((int)$fp['sent'] > 0): ?><span style="color:<?= $delivPct >= 90 ? '#28c76f' : ($delivPct >= 70 ? '#ff9800' : '#ea5455') ?>;"><?= $delivPct ?>%</span><?php else: ?><span style="opacity:.3;">—</span><?php endif; ?></td>
                            <td class="text-center"><?php if ((int)$fp['sent'] > 0): ?><span style="color:<?= $openPct >= 20 ? '#00cfe8' : 'rgba(255,255,255,.4)' ?>;"><?= $openPct ?>%</span><?php else: ?><span style="opacity:.3;">—</span><?php endif; ?></td>
                            <td class="text-center"><?php if ((int)$fp['sent'] > 0): ?><span style="color:<?= $ctrPct >= 5 ? '#28c76f' : 'rgba(255,255,255,.4)' ?>;"><?= $ctrPct ?>%</span><?php else: ?><span style="opacity:.3;">—</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <p class="text-muted px-3 py-2 mb-0" style="font-size:.72rem;">⚠️ Öffnungsrate kann durch Apple Mail Privacy Protection überhöht sein. Klickrate ist zuverlässiger.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══ ACTIVITY LOG ════════════════════════════════════════════════════════ -->
<?php if (!empty($ev_rows)): ?>
<div class="row mb-2">
    <div class="col-12">
        <div class="card" style="margin-bottom:0;">
            <div class="st-card-header"><i class="ft-activity" style="color:var(--s2s-brand);"></i><h5>Aktivitäts-Zusammenfassung (lead_events)</h5></div>
            <div class="card-content">
                <div class="card-body p-0">
                    <table class="st-table">
                        <thead><tr><th>Ereignis</th><th>Anzahl</th></tr></thead>
                        <tbody>
                        <?php foreach ($ev_rows as $ev): ?>
                        <tr>
                            <td><?= htmlspecialchars($evLabels[$ev['event_type']] ?? $ev['event_type']) ?></td>
                            <td><strong><?= $ev['cnt'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>



<!-- ══ DRILL-DOWN MODAL ═══════════════════════════════════════════════════════ -->
<div id="ddOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9000;" onclick="ddClose()"></div>
<div id="ddPanel" style="display:none;position:fixed;top:0;right:0;height:100vh;width:min(640px,100vw);background:#1a1040;border-left:1px solid rgba(203,46,188,.25);z-index:9001;flex-direction:column;overflow:hidden;">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:.75rem;">
        <span id="ddTitle" style="font-weight:700;font-size:1rem;color:#fff;flex:1;"></span>
        <button onclick="ddClose()" style="background:none;border:none;color:rgba(255,255,255,.5);font-size:1.3rem;cursor:pointer;padding:0;line-height:1;">&times;</button>
    </div>
    <div id="ddBody" style="overflow-y:auto;flex:1;padding:.25rem 0;"></div>
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

<script>
// ── Stats Drill-Down ──────────────────────────────────────────────────────────
(function () {
    const metricLabels = {
        signups:  'Leads',
        step1:    'Step 1 geklickt',
        step2:    'Step 2 abgeschlossen',
        resignups:'Re-Signup-Versuche',
    };

    const dimTypeLabels = {
        page: 'Seite', source: 'Quelle', country_detected: 'Land',
        lang: 'Sprache', funnel: 'Gesamt',
    };

    document.addEventListener('click', function (e) {
        const cell = e.target.closest('[data-drill="1"]');
        if (!cell) return;
        ddOpen(
            cell.dataset.dimType  || '',
            cell.dataset.dimValue || '',
            cell.dataset.metric   || 'signups',
            cell.dataset.from     || '',
            cell.dataset.to       || ''
        );
    });

    window.ddOpen = function (dimType, dimValue, metric, from, to) {
        const overlay = document.getElementById('ddOverlay');
        const panel   = document.getElementById('ddPanel');
        const title   = document.getElementById('ddTitle');
        const body    = document.getElementById('ddBody');

        const dimLabel = dimValue || (dimTypeLabels[dimType] || dimType);
        title.textContent = (metricLabels[metric] || metric) + (dimLabel ? ' — ' + dimLabel : '');
        body.innerHTML    = '<p style="padding:1.5rem;color:rgba(255,255,255,.4);font-size:.85rem;">Lade …</p>';
        overlay.style.display = 'block';
        panel.style.display   = 'flex';

        const url = 'stats-detail-ajax.php?dim_type=' + encodeURIComponent(dimType)
                  + '&dim_value=' + encodeURIComponent(dimValue)
                  + '&metric='    + encodeURIComponent(metric)
                  + '&from='      + encodeURIComponent(from)
                  + '&to='        + encodeURIComponent(to);

        fetch(url)
            .then(r => r.json())
            .then(data => ddRender(data, body))
            .catch(() => { body.innerHTML = '<p style="padding:1.5rem;color:#ea5455;">Fehler beim Laden.</p>'; });
    };

    window.ddClose = function () {
        document.getElementById('ddOverlay').style.display = 'none';
        document.getElementById('ddPanel').style.display   = 'none';
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') ddClose();
    });

    function ddRender(data, container) {
        if (!data.rows || data.rows.length === 0) {
            container.innerHTML = '<p style="padding:1.5rem;color:rgba(255,255,255,.35);font-size:.85rem;">Keine Einträge gefunden.</p>';
            return;
        }
        const flagOf = iso => {
            if (!iso || iso.length !== 2) return '';
            return String.fromCodePoint(
                0x1F1E6 + iso.toUpperCase().charCodeAt(0) - 65,
                0x1F1E6 + iso.toUpperCase().charCodeAt(1) - 65
            );
        };

        let html = '<div style="padding:.5rem 1.25rem .25rem;font-size:.75rem;color:rgba(255,255,255,.35);">'
                 + data.total + ' Einträge</div>';

        data.rows.forEach(r => {
            const dt   = (r.signup_at || '').replace('T', ' ').substring(0, 16);
            const flag = flagOf(r.country_detected || '');
            const paid = r.paidstatus === 'Paid'
                       ? '<span style="background:#28c76f22;color:#28c76f;border-radius:3px;padding:1px 5px;font-size:.72rem;">Paid</span>' : '';
            const s1   = r.step1_at
                       ? '<span style="background:#00cfe822;color:#00cfe8;border-radius:3px;padding:1px 5px;font-size:.72rem;">Step1 ✓</span>' : '';
            const s2   = (r.username && r.username !== '')
                       ? '<span style="background:#9c8bd422;color:#9c8bd4;border-radius:3px;padding:1px 5px;font-size:.72rem;">Step2 ✓</span>' : '';
            const src  = r.source ? '<span style="background:rgba(255,255,255,.07);border-radius:3px;padding:1px 5px;font-size:.72rem;">' + esc(r.source) + '</span>' : '';
            const pg   = r.page   ? '<span style="background:rgba(203,46,188,.15);color:#cb2ebc;border-radius:3px;padding:1px 5px;font-size:.72rem;">' + esc(r.page) + '</span>' : '';
            const adminUrl = r.leadid
                ? 'admin-user-edit.php?id=' + r.leadid
                : 'admin-users.php?search=' + encodeURIComponent(r.email || '');

            html += '<div style="padding:.65rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.05);display:flex;flex-direction:column;gap:.2rem;">'
                  + '<div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;">'
                  + '  <a href="' + adminUrl + '" target="_blank" style="color:#fff;font-weight:700;font-size:.88rem;text-decoration:none;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + esc(r.name || '') + '">'
                  + (r.name ? esc(r.name) : '<span style="opacity:.4;">—</span>')
                  + '  </a>'
                  + '  <span style="font-size:.75rem;color:rgba(255,255,255,.35);white-space:nowrap;">' + esc(dt) + '</span>'
                  + '</div>'
                  + '<div style="font-size:.78rem;color:rgba(255,255,255,.5);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + esc(r.email || '') + '</div>'
                  + '<div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;margin-top:.1rem;">'
                  + (flag ? '<span style="font-size:.9rem;" title="' + esc((r.country_detected||'').toUpperCase()) + '">' + flag + '</span>' : '')
                  + (r.lang ? '<span style="opacity:.4;font-size:.72rem;">' + esc(r.lang.toUpperCase()) + '</span>' : '')
                  + src + pg + paid + s1 + s2
                  + '</div>'
                  + '</div>';
        });

        container.innerHTML = html;
    }

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>

<?php if (!empty($daily_labels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($daily_labels) ?>,
            datasets: [{
                label: 'Signups',
                data: <?= json_encode($daily_data) ?>,
                borderColor: '#b700e0',
                backgroundColor: 'rgba(183,0,224,.12)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#b700e0',
                fill: true,
                tension: 0.35,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1040',
                    borderColor: 'rgba(183,0,224,.4)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,.7)',
                }
            },
            scales: {
                x: {
                    ticks: { color: 'rgba(255,255,255,.4)', font: { size: 11 } },
                    grid:  { color: 'rgba(255,255,255,.06)' },
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: 'rgba(255,255,255,.4)', font: { size: 11 }, stepSize: 1 },
                    grid:  { color: 'rgba(255,255,255,.06)' },
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
