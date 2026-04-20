<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

include '../includes/conn.php';
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

                <div class="row">
                    <div class="col-12">
                        <h3 class="content-header-title" style="padding: 20px 0 5px;">
                            <i class="ft-award" style="color:#b700e0;"></i> Leaderboard
                        </h3>
                        <p style="color:#aaa; margin-bottom: 20px;">
                            Top performers ranked by new Members (Step 2 completed) and Leads generated.
                        </p>
                    </div>
                </div>

                <!-- Controls Row -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body py-2">
                                <form method="GET" action="leaderboard.php" class="d-flex flex-wrap align-items-center" style="gap: 12px;">

                                    <!-- Tab: Monthly / All Time -->
                                    <div class="btn-group" role="group">
                                        <a href="?mode=monthly&month=<?= isset($_GET['month']) ? htmlspecialchars($_GET['month']) : date('Y-m') ?>&top=<?= isset($_GET['top']) ? (int)$_GET['top'] : 25 ?>"
                                           class="btn <?= (!isset($_GET['mode']) || $_GET['mode'] === 'monthly') ? 'btn-primary' : 'btn-outline-primary' ?>">
                                           Monthly
                                        </a>
                                        <a href="?mode=alltime&top=<?= isset($_GET['top']) ? (int)$_GET['top'] : 25 ?>"
                                           class="btn <?= (isset($_GET['mode']) && $_GET['mode'] === 'alltime') ? 'btn-primary' : 'btn-outline-primary' ?>">
                                           All Time
                                        </a>
                                    </div>

                                    <!-- Month picker (only for monthly) -->
                                    <?php
                                    $mode     = (isset($_GET['mode']) && $_GET['mode'] === 'alltime') ? 'alltime' : 'monthly';
                                    $topLimit = (isset($_GET['top']) && in_array((int)$_GET['top'], [25, 50, 100])) ? (int)$_GET['top'] : 25;
                                    $page     = (isset($_GET['p']) && (int)$_GET['p'] > 0) ? (int)$_GET['p'] : 1;
                                    $selMonth = (isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month'])) ? $_GET['month'] : date('Y-m');

                                    // Build available months from DB (first signup to now)
                                    $monthsRes = mysqli_query($link, "SELECT DATE_FORMAT(timestamp, '%Y-%m') AS ym FROM users GROUP BY ym ORDER BY ym DESC");
                                    $months = [];
                                    while ($row = mysqli_fetch_assoc($monthsRes)) {
                                        $months[] = $row['ym'];
                                    }
                                    ?>
                                    <?php if ($mode === 'monthly'): ?>
                                    <div>
                                        <label class="mb-0 mr-1" style="color:#ccc; font-size:13px;">Month:</label>
                                        <select name="month" onchange="applyFilter()" id="monthSelect" class="form-control d-inline-block" style="width:auto;">
                                            <?php foreach ($months as $m):
                                                $label = date('F Y', strtotime($m . '-01'));
                                            ?>
                                            <option value="<?= $m ?>" <?= $m === $selMonth ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="mode" value="monthly">
                                        <input type="hidden" name="top" id="topHidden" value="<?= $topLimit ?>">
                                    </div>
                                    <?php endif; ?>

                                    <!-- TOP selector -->
                                    <div>
                                        <label class="mb-0 mr-1" style="color:#ccc; font-size:13px;">Show:</label>
                                        <?php foreach ([25, 50, 100] as $t): ?>
                                        <a href="?mode=<?= $mode ?>&month=<?= $selMonth ?>&top=<?= $t ?>&p=1"
                                           class="btn btn-sm <?= $topLimit === $t ? 'btn-primary' : 'btn-outline-secondary' ?> mr-1">
                                           TOP <?= $t ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // ── Build query ──────────────────────────────────────────────────
                $offset = ($page - 1) * $topLimit;

                if ($mode === 'monthly') {
                    $monthStart = $selMonth . '-01';
                    $monthEnd   = date('Y-m-01', strtotime($monthStart . ' +1 month'));

                    $countSql = "
                        SELECT COUNT(*) AS cnt FROM (
                            SELECT u.leadid
                            FROM users u
                            WHERE u.username IS NOT NULL AND u.username != ''
                            GROUP BY u.leadid
                        ) AS sub";

                    $sql = "
                        SELECT
                            u.leadid,
                            CASE WHEN u.name != '' AND u.name IS NOT NULL THEN u.name ELSE u.email END AS display_name,
                            COUNT(l.leadid)                                                         AS total_leads,
                            SUM(CASE WHEN l.username IS NOT NULL AND l.username != '' THEN 1 ELSE 0 END) AS step2_members,
                            SUM(CASE WHEN l.timestamp >= '$monthStart' AND l.timestamp < '$monthEnd' THEN 1 ELSE 0 END) AS month_leads,
                            SUM(CASE WHEN l.username IS NOT NULL AND l.username != ''
                                      AND l.timestamp >= '$monthStart' AND l.timestamp < '$monthEnd' THEN 1 ELSE 0 END) AS month_members
                        FROM users u
                        LEFT JOIN users l ON l.referer = u.leadid
                        WHERE u.username IS NOT NULL AND u.username != ''
                        GROUP BY u.leadid
                        ORDER BY month_members DESC, month_leads DESC
                        LIMIT $topLimit OFFSET $offset";

                    $colLeads   = 'month_leads';
                    $colMembers = 'month_members';
                    $heading    = 'Monthly Leaderboard — ' . date('F Y', strtotime($monthStart));

                } else {
                    $sql = "
                        SELECT
                            u.leadid,
                            CASE WHEN u.name != '' AND u.name IS NOT NULL THEN u.name ELSE u.email END AS display_name,
                            COUNT(l.leadid)                                                             AS total_leads,
                            SUM(CASE WHEN l.username IS NOT NULL AND l.username != '' THEN 1 ELSE 0 END) AS step2_members
                        FROM users u
                        LEFT JOIN users l ON l.referer = u.leadid
                        WHERE u.username IS NOT NULL AND u.username != ''
                        GROUP BY u.leadid
                        ORDER BY step2_members DESC, total_leads DESC
                        LIMIT $topLimit OFFSET $offset";

                    $colLeads   = 'total_leads';
                    $colMembers = 'step2_members';
                    $heading    = 'All Time Leaderboard';
                }

                // Total count for pagination
                $countSql = "
                    SELECT COUNT(*) AS cnt
                    FROM users
                    WHERE username IS NOT NULL AND username != ''";
                $cRes      = mysqli_query($link, $countSql);
                $cRow      = mysqli_fetch_assoc($cRes);
                $totalRows = (int)$cRow['cnt'];
                $totalPages = ceil($totalRows / $topLimit);

                $rows = mysqli_query($link, $sql);
                ?>

                <!-- Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">
                                    <i class="ft-award" style="color:#b700e0;"></i>
                                    <?= htmlspecialchars($heading) ?>
                                </h4>
                                <span style="color:#aaa; font-size:13px;">
                                    Showing <?= $offset + 1 ?>–<?= min($offset + $topLimit, $totalRows) ?> of <?= $totalRows ?> Members
                                </span>
                            </div>
                            <div class="card-content">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="lb-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:60px; text-align:center;">#</th>
                                                    <th>Member</th>
                                                    <th style="text-align:center;">
                                                        <?= $mode === 'monthly' ? 'Leads (Month)' : 'Leads (All Time)' ?>
                                                    </th>
                                                    <th style="text-align:center;">
                                                        <?= $mode === 'monthly' ? 'New Members (Month)' : 'New Members (All Time)' ?>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $rank = $offset + 1;
                                            $hasRows = false;
                                            while ($row = mysqli_fetch_assoc($rows)):
                                                $hasRows = true;
                                                $isMe    = ($row['leadid'] == $userid);
                                                $leads   = (int)$row[$colLeads];
                                                $members = (int)$row[$colMembers];

                                                // Medal for top 3
                                                if ($rank === 1)      $medal = '<span style="font-size:20px;">🥇</span>';
                                                elseif ($rank === 2)  $medal = '<span style="font-size:20px;">🥈</span>';
                                                elseif ($rank === 3)  $medal = '<span style="font-size:20px;">🥉</span>';
                                                else                  $medal = '<strong style="color:#888;">' . $rank . '</strong>';

                                                $rowStyle = $isMe ? 'background-color: rgba(183,0,224,0.12); font-weight:600;' : '';
                                            ?>
                                            <tr style="<?= $rowStyle ?>">
                                                <td style="text-align:center; vertical-align:middle;">
                                                    <?= $medal ?>
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    <?= htmlspecialchars($row['display_name']) ?>
                                                    <?php if ($isMe): ?>
                                                    <span class="badge badge-primary ml-1" style="font-size:11px;">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align:center; vertical-align:middle;">
                                                    <?php if ($leads > 0): ?>
                                                    <span class="badge badge-info" style="font-size:13px; padding:5px 10px;">
                                                        <?= $leads ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span style="color:#666;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align:center; vertical-align:middle;">
                                                    <?php if ($members > 0): ?>
                                                    <span class="badge badge-success" style="font-size:13px; padding:5px 10px;">
                                                        <?= $members ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span style="color:#666;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php $rank++; endwhile; ?>
                                            <?php if (!$hasRows): ?>
                                            <tr>
                                                <td colspan="4" style="text-align:center; padding:40px; color:#888;">
                                                    No members have completed Step 2 yet.
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                            <div class="card-footer d-flex justify-content-center">
                                <ul class="pagination mb-0">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?mode=<?= $mode ?>&month=<?= $selMonth ?>&top=<?= $topLimit ?>&p=<?= $page - 1 ?>">
                                            &laquo;
                                        </a>
                                    </li>
                                    <?php endif; ?>

                                    <?php
                                    $from = max(1, $page - 2);
                                    $to   = min($totalPages, $page + 2);
                                    if ($from > 1): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif;
                                    for ($i = $from; $i <= $to; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?mode=<?= $mode ?>&month=<?= $selMonth ?>&top=<?= $topLimit ?>&p=<?= $i ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                    <?php endfor;
                                    if ($to < $totalPages): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>

                                    <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?mode=<?= $mode ?>&month=<?= $selMonth ?>&top=<?= $topLimit ?>&p=<?= $page + 1 ?>">
                                            &raquo;
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <!-- Info box -->
                <div class="row">
                    <div class="col-12">
                        <div class="card" style="border-left: 3px solid #b700e0;">
                            <div class="card-body py-2 px-3">
                                <p class="mb-0" style="color:#aaa; font-size:13px;">
                                    <i class="ft-info" style="color:#b700e0;"></i>
                                    <strong style="color:#ccc;">How rankings work:</strong>
                                    Members are ranked by <em>New Members</em> (leads who completed Step 2) first, then by total Leads.
                                    Only members who have completed Step 2 themselves appear on the leaderboard.
                                    <strong style="color:#b700e0;">Your row is highlighted in purple.</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php require_once "parts/footer.php"; ?>
        <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
    </div>
</div>

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<script src="app-assets/vendors/js/vendors.min.js"></script>
<script src="app-assets/js/core/app-menu.js"></script>
<script src="app-assets/js/core/app.js"></script>
<script src="app-assets/js/notification-sidebar.js"></script>
<script src="app-assets/js/scroll-top.js"></script>
<script src="assets/js/scripts.js"></script>

<script>
function applyFilter() {
    var month = document.getElementById('monthSelect') ? document.getElementById('monthSelect').value : '<?= $selMonth ?>';
    var top   = <?= $topLimit ?>;
    var mode  = '<?= $mode ?>';
    window.location.href = 'leaderboard.php?mode=' + mode + '&month=' + month + '&top=' + top + '&p=1';
}
</script>

</body>
</html>
