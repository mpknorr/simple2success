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

// ── Create tables if missing ─────────────────────────────────────────────────
mysqli_query($link, "CREATE TABLE IF NOT EXISTS rotator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($link, "CREATE TABLE IF NOT EXISTS rotator_settings (
    setting_key VARCHAR(64) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL DEFAULT ''
)");
// Insert defaults if empty
foreach (['is_active' => '0', 'include_type' => 'members', 'current_position' => '0'] as $k => $v) {
    $ek = mysqli_real_escape_string($link, $k);
    $ev = mysqli_real_escape_string($link, $v);
    mysqli_query($link, "INSERT IGNORE INTO rotator_settings (setting_key, setting_value) VALUES ('$ek', '$ev')");
}

// Ensure users table has rotator_assigned column
$cols = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'rotator_assigned'");
if (mysqli_num_rows($cols) === 0) {
    mysqli_query($link, "ALTER TABLE users ADD COLUMN rotator_assigned TINYINT(1) DEFAULT 0");
}

function rot_setting($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM rotator_settings WHERE setting_key='$k'"));
    return $r ? $r['setting_value'] : '';
}
function set_rot_setting($link, $key, $val) {
    $k = mysqli_real_escape_string($link, $key);
    $v = mysqli_real_escape_string($link, $val);
    mysqli_query($link, "INSERT INTO rotator_settings (setting_key, setting_value) VALUES ('$k','$v')
        ON DUPLICATE KEY UPDATE setting_value='$v'");
}

$success = '';
$error   = '';

// ── POST actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $is_active    = isset($_POST['is_active']) ? '1' : '0';
        $include_type = in_array($_POST['include_type'] ?? '', ['members','paid','all']) ? $_POST['include_type'] : 'members';
        set_rot_setting($link, 'is_active', $is_active);
        set_rot_setting($link, 'include_type', $include_type);
        $success = 'Settings saved.';
    }

    if ($action === 'auto_populate') {
        $include_type = rot_setting($link, 'include_type');
        if ($include_type === 'members') {
            $sql = "SELECT leadid FROM users WHERE username IS NOT NULL AND username != ''";
        } elseif ($include_type === 'paid') {
            $sql = "SELECT leadid FROM users WHERE paidstatus = 'Paid'";
        } else {
            $sql = "SELECT leadid FROM users";
        }
        $res = mysqli_query($link, $sql);
        $pos = 0;
        while ($row = mysqli_fetch_assoc($res)) {
            $uid = (int)$row['leadid'];
            $existing = mysqli_fetch_assoc(mysqli_query($link, "SELECT id FROM rotator WHERE user_id=$uid"));
            if (!$existing) {
                mysqli_query($link, "INSERT INTO rotator (user_id, is_active, position) VALUES ($uid, 1, $pos)");
            }
            $pos++;
        }
        // Renumber positions
        $all = mysqli_query($link, "SELECT id FROM rotator ORDER BY position ASC, id ASC");
        $p = 0;
        while ($r = mysqli_fetch_assoc($all)) {
            mysqli_query($link, "UPDATE rotator SET position=$p WHERE id={$r['id']}");
            $p++;
        }
        $success = 'Rotator populated with ' . $p . ' entries.';
    }

    if ($action === 'add_user') {
        $search = mysqli_real_escape_string($link, trim($_POST['search_user'] ?? ''));
        $found  = mysqli_fetch_assoc(mysqli_query($link, "SELECT leadid FROM users WHERE email='$search' OR leadid='$search' LIMIT 1"));
        if ($found) {
            $uid = (int)$found['leadid'];
            $ex  = mysqli_fetch_assoc(mysqli_query($link, "SELECT id FROM rotator WHERE user_id=$uid"));
            if (!$ex) {
                $maxpos = mysqli_fetch_assoc(mysqli_query($link, "SELECT COALESCE(MAX(position),0)+1 AS mp FROM rotator"));
                $pos = (int)$maxpos['mp'];
                mysqli_query($link, "INSERT INTO rotator (user_id, is_active, position) VALUES ($uid, 1, $pos)");
                $success = 'User added to rotator.';
            } else {
                $error = 'User is already in the rotator.';
            }
        } else {
            $error = 'User not found.';
        }
    }

    if ($action === 'toggle_entry') {
        $id  = (int)($_POST['entry_id'] ?? 0);
        $cur = mysqli_fetch_assoc(mysqli_query($link, "SELECT is_active FROM rotator WHERE id=$id"));
        if ($cur) {
            $new = $cur['is_active'] ? 0 : 1;
            mysqli_query($link, "UPDATE rotator SET is_active=$new WHERE id=$id");
        }
    }

    if ($action === 'remove_entry') {
        $id = (int)($_POST['entry_id'] ?? 0);
        mysqli_query($link, "DELETE FROM rotator WHERE id=$id");
        $success = 'Entry removed.';
    }

    if ($action === 'move_up' || $action === 'move_down') {
        $id  = (int)($_POST['entry_id'] ?? 0);
        $cur = mysqli_fetch_assoc(mysqli_query($link, "SELECT position FROM rotator WHERE id=$id"));
        if ($cur) {
            $curPos = (int)$cur['position'];
            if ($action === 'move_up') {
                $swap = mysqli_fetch_assoc(mysqli_query($link, "SELECT id, position FROM rotator WHERE position < $curPos ORDER BY position DESC LIMIT 1"));
            } else {
                $swap = mysqli_fetch_assoc(mysqli_query($link, "SELECT id, position FROM rotator WHERE position > $curPos ORDER BY position ASC LIMIT 1"));
            }
            if ($swap) {
                $swapPos = (int)$swap['position'];
                $swapId  = (int)$swap['id'];
                mysqli_query($link, "UPDATE rotator SET position=$swapPos WHERE id=$id");
                mysqli_query($link, "UPDATE rotator SET position=$curPos WHERE id=$swapId");
            }
        }
    }

    if ($action === 'reset_pointer') {
        set_rot_setting($link, 'current_position', '0');
        $success = 'Pointer reset to beginning.';
    }
}

// ── Load current data ─────────────────────────────────────────────────────────
$rot_active    = rot_setting($link, 'is_active');
$include_type  = rot_setting($link, 'include_type');
$cur_pos       = (int) rot_setting($link, 'current_position');
$rotator_list  = mysqli_query($link, "SELECT r.*, u.name, u.email, u.username, u.paidstatus FROM rotator r LEFT JOIN users u ON r.user_id=u.leadid ORDER BY r.position ASC");
$total_entries = mysqli_num_rows(mysqli_query($link, "SELECT id FROM rotator"));
$total_active  = mysqli_num_rows(mysqli_query($link, "SELECT id FROM rotator WHERE is_active=1"));

// Next-up user
$next_user = mysqli_fetch_assoc(mysqli_query($link, "SELECT r.*, u.name, u.email FROM rotator r LEFT JOIN users u ON r.user_id=u.leadid WHERE r.is_active=1 AND r.position > $cur_pos ORDER BY r.position ASC LIMIT 1"));
if (!$next_user) {
    $next_user = mysqli_fetch_assoc(mysqli_query($link, "SELECT r.*, u.name, u.email FROM rotator r LEFT JOIN users u ON r.user_id=u.leadid WHERE r.is_active=1 ORDER BY r.position ASC LIMIT 1"));
}

// Rotator stats
$rot_stats = mysqli_query($link, "SELECT u.name, u.email, COUNT(*) as cnt FROM users leads LEFT JOIN users u ON leads.referer=u.leadid WHERE leads.rotator_assigned=1 GROUP BY leads.referer ORDER BY cnt DESC");
$total_rotator_leads = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS cnt FROM users WHERE rotator_assigned=1"));
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
        <div class="content-header-left col-12 d-flex align-items-center justify-content-between">
            <h3 class="content-header-title mb-0"><i class="ft-refresh-cw" style="color:#b700e0;"></i> Lead Rotator</h3>
            <span class="badge <?= $rot_active === '1' ? 'badge-success' : 'badge-secondary' ?>" style="font-size:14px; padding:8px 16px;">
                <?= $rot_active === '1' ? 'ACTIVE' : 'INACTIVE' ?>
            </span>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row match-height">

        <!-- Settings card -->
        <div class="col-lg-4 col-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Settings</h4></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_settings">

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="rotSwitch" name="is_active" <?= $rot_active === '1' ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="rotSwitch"><strong>Rotator Active</strong></label>
                            </div>
                            <small class="text-muted">When active, leads without a referrer are automatically assigned to the next person in the queue.</small>
                        </div>

                        <div class="form-group mt-2">
                            <label><strong>Auto-populate includes:</strong></label>
                            <?php foreach (['members' => 'Members only (Step 2 done)', 'paid' => 'Paid Members only', 'all' => 'All users'] as $val => $label): ?>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="inc_<?= $val ?>" name="include_type" value="<?= $val ?>" class="custom-control-input" <?= $include_type === $val ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="inc_<?= $val ?>"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block mt-2">Save Settings</button>
                    </form>

                    <hr>

                    <form method="POST">
                        <input type="hidden" name="action" value="auto_populate">
                        <button type="submit" class="btn btn-warning btn-block">
                            <i class="ft-users"></i> Auto-Populate Queue
                        </button>
                        <small class="text-muted d-block mt-1">Adds all matching users to the queue (skips duplicates).</small>
                    </form>
                </div>
            </div>

            <!-- Stats -->
            <div class="card">
                <div class="card-header"><h4 class="card-title">Stats</h4></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div style="font-size:28px; font-weight:700; color:#b700e0;"><?= $total_entries ?></div>
                            <small class="text-muted">In Queue</small>
                        </div>
                        <div class="col-6">
                            <div style="font-size:28px; font-weight:700; color:#28a745;"><?= $total_active ?></div>
                            <small class="text-muted">Active</small>
                        </div>
                    </div>
                    <div class="row text-center mt-2">
                        <div class="col-12">
                            <div style="font-size:28px; font-weight:700; color:#17a2b8;"><?= (int)$total_rotator_leads['cnt'] ?></div>
                            <small class="text-muted">Total Leads Assigned via Rotator</small>
                        </div>
                    </div>
                    <?php if ($next_user): ?>
                    <hr>
                    <p class="mb-1"><strong>Next up:</strong></p>
                    <span class="badge badge-info"><?= htmlspecialchars($next_user['name'] ?: $next_user['email']) ?></span>
                    <?php endif; ?>
                    <hr>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_pointer">
                        <button type="submit" class="btn btn-sm btn-outline-secondary btn-block">Reset Pointer to Start</button>
                    </form>
                </div>
            </div>

            <!-- Add user -->
            <div class="card">
                <div class="card-header"><h4 class="card-title">Add User to Queue</h4></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_user">
                        <div class="form-group">
                            <input type="text" name="search_user" class="form-control" placeholder="Email or User ID" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-block"><i class="ft-plus"></i> Add</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Queue list -->
        <div class="col-lg-8 col-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Rotation Queue</h4></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px;">Pos</th>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th style="width:150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            mysqli_data_seek($rotator_list, 0);
                            while ($row = mysqli_fetch_assoc($rotator_list)):
                                $isNext = $next_user && $next_user['id'] == $row['id'];
                                $rowStyle = $isNext ? 'background:rgba(183,0,224,0.12);' : '';
                            ?>
                            <tr style="<?= $rowStyle ?>">
                                <td><strong><?= (int)$row['position'] ?></strong><?= $isNext ? ' <span class="badge badge-primary" title="Next">▶</span>' : '' ?></td>
                                <td><?= htmlspecialchars($row['name'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($row['email'] ?: '—') ?></td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">Paused</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="move_up">
                                        <input type="hidden" name="entry_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Move Up">▲</button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="move_down">
                                        <input type="hidden" name="entry_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Move Down">▼</button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_entry">
                                        <input type="hidden" name="entry_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $row['is_active'] ? 'btn-warning' : 'btn-success' ?>" title="<?= $row['is_active'] ? 'Pause' : 'Activate' ?>">
                                            <?= $row['is_active'] ? '⏸' : '▶' ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Remove from rotator?')">
                                        <input type="hidden" name="action" value="remove_entry">
                                        <input type="hidden" name="entry_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Remove">✕</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($total_entries === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted p-4">Queue is empty. Use Auto-Populate or add users manually.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Per-member stats -->
            <?php
            $has_stats = false;
            $stats_rows = [];
            while ($s = mysqli_fetch_assoc($rot_stats)) { $stats_rows[] = $s; $has_stats = true; }
            if ($has_stats):
            ?>
            <div class="card">
                <div class="card-header"><h4 class="card-title">Leads Assigned per Member (via Rotator)</h4></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Member</th><th>Email</th><th class="text-center">Rotator Leads</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats_rows as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['name'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($s['email'] ?: '—') ?></td>
                            <td class="text-center"><span class="badge badge-info"><?= (int)$s['cnt'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
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
