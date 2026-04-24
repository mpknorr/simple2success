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

// ── Link Rotator tables ───────────────────────────────────────────────────────
mysqli_query($link, "CREATE TABLE IF NOT EXISTS link_rotators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    rotation_mode ENUM('random','sequential','balanced') DEFAULT 'random',
    fallback_url VARCHAR(255) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_slug (slug)
)");
mysqli_query($link, "CREATE TABLE IF NOT EXISTS link_rotator_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rotator_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    weight INT DEFAULT 1,
    click_limit INT DEFAULT 0,
    clicks INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    position INT DEFAULT 0,
    INDEX idx_rotator (rotator_id),
    FOREIGN KEY (rotator_id) REFERENCES link_rotators(id) ON DELETE CASCADE
)");
mysqli_query($link, "CREATE TABLE IF NOT EXISTS link_rotator_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rotator_id INT NOT NULL,
    item_id INT DEFAULT NULL,
    source_param VARCHAR(100) DEFAULT '',
    ip_address VARCHAR(45) DEFAULT '',
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rotator (rotator_id),
    INDEX idx_clicked (clicked_at)
)");

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

function slugify($s) {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9-]+/', '-', $s);
    $s = trim(preg_replace('/-+/', '-', $s), '-');
    return $s ?: 'rotator-' . time();
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

    // ── Link Rotator handlers ────────────────────────────────────────────────
    if ($action === 'create_link_rotator') {
        $name      = mysqli_real_escape_string($link, trim($_POST['name'] ?? ''));
        $slugIn    = trim($_POST['slug'] ?? '');
        $slug      = mysqli_real_escape_string($link, slugify($slugIn ?: ($_POST['name'] ?? '')));
        $mode      = in_array($_POST['rotation_mode'] ?? '', ['random','sequential','balanced'], true) ? $_POST['rotation_mode'] : 'random';
        $fallback  = filter_var(trim($_POST['fallback_url'] ?? ''), FILTER_SANITIZE_URL);
        $fallbackE = mysqli_real_escape_string($link, $fallback);
        if (!$name || !$slug) {
            $error = 'Name und Slug erforderlich.';
        } else {
            $dup = mysqli_fetch_assoc(mysqli_query($link, "SELECT id FROM link_rotators WHERE slug='$slug' LIMIT 1"));
            if ($dup) {
                $error = 'Slug existiert bereits.';
            } else {
                mysqli_query($link, "INSERT INTO link_rotators (user_id, name, slug, rotation_mode, fallback_url)
                    VALUES ($userid, '$name', '$slug', '$mode', '$fallbackE')");
                $success = 'Rotator erstellt.';
            }
        }
    }

    if ($action === 'edit_link_rotator') {
        $rid       = (int)($_POST['rotator_id'] ?? 0);
        $name      = mysqli_real_escape_string($link, trim($_POST['name'] ?? ''));
        $mode      = in_array($_POST['rotation_mode'] ?? '', ['random','sequential','balanced'], true) ? $_POST['rotation_mode'] : 'random';
        $fallback  = filter_var(trim($_POST['fallback_url'] ?? ''), FILTER_SANITIZE_URL);
        $fallbackE = mysqli_real_escape_string($link, $fallback);
        $active    = isset($_POST['is_active']) ? 1 : 0;
        mysqli_query($link, "UPDATE link_rotators
            SET name='$name', rotation_mode='$mode', fallback_url='$fallbackE', is_active=$active
            WHERE id=$rid AND user_id=$userid");
        $success = 'Rotator aktualisiert.';
    }

    if ($action === 'delete_link_rotator') {
        $rid = (int)($_POST['rotator_id'] ?? 0);
        mysqli_query($link, "DELETE FROM link_rotators WHERE id=$rid AND user_id=$userid");
        $success = 'Rotator gelöscht.';
    }

    if ($action === 'add_rotator_item') {
        $rid = (int)($_POST['rotator_id'] ?? 0);
        $owner = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT id FROM link_rotators WHERE id=$rid AND user_id=$userid LIMIT 1"));
        if ($owner) {
            $iname  = mysqli_real_escape_string($link, trim($_POST['item_name'] ?? ''));
            $url    = filter_var(trim($_POST['item_url'] ?? ''), FILTER_VALIDATE_URL);
            $urlE   = $url ? mysqli_real_escape_string($link, $url) : '';
            $weight = max(1, (int)($_POST['weight'] ?? 1));
            $limit  = max(0, (int)($_POST['click_limit'] ?? 0));
            if (!$iname || !$urlE) {
                $error = 'Name und gültige URL erforderlich.';
            } else {
                $maxp = mysqli_fetch_assoc(mysqli_query($link,
                    "SELECT COALESCE(MAX(position),0)+1 AS mp FROM link_rotator_items WHERE rotator_id=$rid"));
                $pos = (int)$maxp['mp'];
                mysqli_query($link, "INSERT INTO link_rotator_items (rotator_id, name, url, weight, click_limit, position)
                    VALUES ($rid, '$iname', '$urlE', $weight, $limit, $pos)");
                $success = 'Item hinzugefügt.';
            }
        }
    }

    if ($action === 'edit_rotator_item') {
        $iid    = (int)($_POST['item_id'] ?? 0);
        $iname  = mysqli_real_escape_string($link, trim($_POST['item_name'] ?? ''));
        $url    = filter_var(trim($_POST['item_url'] ?? ''), FILTER_VALIDATE_URL);
        $urlE   = $url ? mysqli_real_escape_string($link, $url) : '';
        $weight = max(1, (int)($_POST['weight'] ?? 1));
        $limit  = max(0, (int)($_POST['click_limit'] ?? 0));
        if ($iname && $urlE) {
            mysqli_query($link, "UPDATE link_rotator_items i
                JOIN link_rotators r ON r.id=i.rotator_id AND r.user_id=$userid
                SET i.name='$iname', i.url='$urlE', i.weight=$weight, i.click_limit=$limit
                WHERE i.id=$iid");
            $success = 'Item aktualisiert.';
        } else {
            $error = 'Name und gültige URL erforderlich.';
        }
    }

    if ($action === 'delete_rotator_item') {
        $iid = (int)($_POST['item_id'] ?? 0);
        mysqli_query($link, "DELETE i FROM link_rotator_items i
            JOIN link_rotators r ON r.id=i.rotator_id AND r.user_id=$userid
            WHERE i.id=$iid");
        $success = 'Item gelöscht.';
    }

    if ($action === 'toggle_rotator_item') {
        $iid = (int)($_POST['item_id'] ?? 0);
        mysqli_query($link, "UPDATE link_rotator_items i
            JOIN link_rotators r ON r.id=i.rotator_id AND r.user_id=$userid
            SET i.is_active = 1 - i.is_active WHERE i.id=$iid");
        $success = 'Item Status geändert.';
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

// ── Link Rotator data ─────────────────────────────────────────────────────────
$link_rotators = [];
$lrRes = mysqli_query($link, "SELECT lr.*,
    (SELECT COUNT(*) FROM link_rotator_stats WHERE rotator_id=lr.id) AS total_clicks,
    (SELECT COUNT(*) FROM link_rotator_items WHERE rotator_id=lr.id) AS item_count
    FROM link_rotators lr WHERE lr.user_id=$userid ORDER BY lr.created_at DESC");
if ($lrRes) { while ($row = mysqli_fetch_assoc($lrRes)) $link_rotators[] = $row; }

$manage_rid = (int)($_GET['rotator_id'] ?? 0);
$manage_rotator = null;
$manage_items = [];
if ($manage_rid) {
    $manage_rotator = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT * FROM link_rotators WHERE id=$manage_rid AND user_id=$userid LIMIT 1"));
    if ($manage_rotator) {
        $itRes = mysqli_query($link,
            "SELECT * FROM link_rotator_items WHERE rotator_id=$manage_rid ORDER BY position ASC, id ASC");
        while ($r = mysqli_fetch_assoc($itRes)) $manage_items[] = $r;
    }
}

$active_tab = ($_GET['tab'] ?? '') === 'link' || $manage_rid ? 'link' : 'lead';
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
            <h3 class="content-header-title mb-0"><i class="ft-refresh-cw" style="color:#b700e0;"></i> Rotator Management</h3>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'lead' ? 'active' : '' ?>" data-toggle="tab" href="#tab-lead">
                <i class="ft-users mr-1"></i> Lead Rotator
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'link' ? 'active' : '' ?>" data-toggle="tab" href="#tab-link">
                <i class="ft-link mr-1"></i> Link Rotator
            </a>
        </li>
    </ul>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="tab-content">

    <!-- ═══════════════ TAB 1: LEAD ROTATOR ═══════════════ -->
    <div class="tab-pane fade <?= $active_tab === 'lead' ? 'show active' : '' ?>" id="tab-lead">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0"><i class="ft-users mr-1" style="color:#b700e0;"></i> Lead Rotator</h4>
        <span class="badge <?= $rot_active === '1' ? 'badge-success' : 'badge-secondary' ?>" style="font-size:14px; padding:8px 16px;">
            <?= $rot_active === '1' ? 'ACTIVE' : 'INACTIVE' ?>
        </span>
    </div>

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

    </div><!-- /#tab-lead -->

    <!-- ═══════════════ TAB 2: LINK ROTATOR ═══════════════ -->
    <div class="tab-pane fade <?= $active_tab === 'link' ? 'show active' : '' ?>" id="tab-link">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="ft-link mr-1" style="color:#b700e0;"></i> Link Rotators</h4>
        <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreateRotator">
            <i class="ft-plus mr-1"></i> Neuen Rotator erstellen
        </button>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Public Link</th>
                        <th>Modus</th>
                        <th class="text-center">Items</th>
                        <th class="text-center">Klicks</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$link_rotators): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Noch keine Rotatoren angelegt.</td></tr>
                <?php else: foreach ($link_rotators as $lr):
                    $pubUrl = $baseurl . '/r/' . $lr['slug']; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($lr['name']) ?></strong></td>
                        <td>
                            <code style="font-size:12px;"><?= htmlspecialchars($pubUrl) ?></code>
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1 copy-btn" data-url="<?= htmlspecialchars($pubUrl) ?>"><i class="ft-copy"></i></button>
                        </td>
                        <td><span class="badge badge-info"><?= ucfirst($lr['rotation_mode']) ?></span></td>
                        <td class="text-center"><?= (int)$lr['item_count'] ?></td>
                        <td class="text-center"><span class="badge badge-primary"><?= (int)$lr['total_clicks'] ?></span></td>
                        <td class="text-center">
                            <span class="badge <?= $lr['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                                <?= $lr['is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                            </span>
                        </td>
                        <td class="text-right" style="white-space:nowrap;">
                            <a href="?tab=link&rotator_id=<?= (int)$lr['id'] ?>" class="btn btn-sm btn-outline-primary" title="Links verwalten"><i class="ft-list"></i></a>
                            <button type="button" class="btn btn-sm btn-outline-warning edit-btn" data-toggle="modal" data-target="#modalEditRotator"
                                data-id="<?= (int)$lr['id'] ?>" data-name="<?= htmlspecialchars($lr['name']) ?>"
                                data-mode="<?= $lr['rotation_mode'] ?>" data-fallback="<?= htmlspecialchars($lr['fallback_url']) ?>"
                                data-active="<?= (int)$lr['is_active'] ?>" title="Bearbeiten"><i class="ft-edit"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Rotator wirklich löschen? Alle Items werden ebenfalls gelöscht.');">
                                <input type="hidden" name="action" value="delete_link_rotator">
                                <input type="hidden" name="rotator_id" value="<?= (int)$lr['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Löschen"><i class="ft-trash-2"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($manage_rotator): ?>
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0"><i class="ft-list mr-1" style="color:#b700e0;"></i> Items: <?= htmlspecialchars($manage_rotator['name']) ?></h4>
            <div>
                <a href="?tab=link" class="btn btn-sm btn-outline-secondary mr-2">&larr; Zurück</a>
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalAddItem">
                    <i class="ft-plus mr-1"></i> Link hinzufügen
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th class="text-center">Weight</th>
                        <th class="text-center">Klicks / Limit</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$manage_items): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Noch keine Links.</td></tr>
                <?php else: foreach ($manage_items as $it): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($it['name']) ?></strong></td>
                        <td><code style="font-size:11px;"><?= htmlspecialchars($it['url']) ?></code></td>
                        <td class="text-center"><?= (int)$it['weight'] ?></td>
                        <td class="text-center">
                            <?= (int)$it['clicks'] ?><?= $it['click_limit'] > 0 ? ' / ' . (int)$it['click_limit'] : '' ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle_rotator_item">
                                <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $it['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                    <?= $it['is_active'] ? 'On' : 'Off' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-right" style="white-space:nowrap;">
                            <button type="button" class="btn btn-sm btn-outline-warning edit-item-btn" data-toggle="modal" data-target="#modalEditItem"
                                data-id="<?= (int)$it['id'] ?>" data-name="<?= htmlspecialchars($it['name']) ?>"
                                data-url="<?= htmlspecialchars($it['url']) ?>" data-weight="<?= (int)$it['weight'] ?>"
                                data-limit="<?= (int)$it['click_limit'] ?>"><i class="ft-edit"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Item löschen?');">
                                <input type="hidden" name="action" value="delete_rotator_item">
                                <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ft-trash-2"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    </div><!-- /#tab-link -->

    </div><!-- /.tab-content -->

    <!-- ════════ Modals ════════ -->
    <div class="modal fade" id="modalCreateRotator" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="create_link_rotator">
          <div class="modal-header"><h5 class="modal-title">Neuen Rotator erstellen</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group">
              <label>Name *</label>
              <input type="text" name="name" class="form-control" placeholder="z.B. Summer Campaign" required>
            </div>
            <div class="form-group">
              <label>Slug (URL-Kürzel)</label>
              <input type="text" name="slug" class="form-control" placeholder="summer-sale (leer lassen = automatisch aus Name)">
              <small class="text-muted">Nur a–z, 0–9 und Bindestriche. Wird zu: <code><?= htmlspecialchars($baseurl) ?>/r/&lt;slug&gt;</code></small>
            </div>
            <div class="form-group">
              <label>Rotation Mode</label>
              <select name="rotation_mode" class="form-control">
                <option value="random">Random (gewichtet)</option>
                <option value="sequential">Sequential (erster aktiver im Pool)</option>
                <option value="balanced">Balanced (wenigste Klicks zuerst)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fallback URL</label>
              <input type="url" name="fallback_url" class="form-control" placeholder="https://example.com (wenn keine Items verfügbar)">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Erstellen</button>
          </div>
        </form>
      </div></div>
    </div>

    <div class="modal fade" id="modalEditRotator" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="edit_link_rotator">
          <input type="hidden" name="rotator_id" value="">
          <div class="modal-header"><h5 class="modal-title">Rotator bearbeiten</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group">
              <label>Name *</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Rotation Mode</label>
              <select name="rotation_mode" class="form-control">
                <option value="random">Random (gewichtet)</option>
                <option value="sequential">Sequential</option>
                <option value="balanced">Balanced</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fallback URL</label>
              <input type="url" name="fallback_url" class="form-control" placeholder="https://example.com">
            </div>
            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="editActive" name="is_active">
                <label class="custom-control-label" for="editActive">Aktiv</label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Speichern</button>
          </div>
        </form>
      </div></div>
    </div>

    <div class="modal fade" id="modalAddItem" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="add_rotator_item">
          <input type="hidden" name="rotator_id" value="<?= (int)$manage_rid ?>">
          <div class="modal-header"><h5 class="modal-title">Link hinzufügen</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group">
              <label>Name *</label>
              <input type="text" name="item_name" class="form-control" placeholder="z.B. Landing Page A" required>
            </div>
            <div class="form-group">
              <label>Ziel-URL *</label>
              <input type="url" name="item_url" class="form-control" placeholder="https://example.com/page" required>
            </div>
            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label>Weight (Gewichtung)</label>
                  <input type="number" name="weight" class="form-control" value="1" min="1" max="100">
                  <small class="text-muted">Höher = öfter ausgewählt</small>
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label>Click Limit</label>
                  <input type="number" name="click_limit" class="form-control" value="0" min="0">
                  <small class="text-muted">0 = unbegrenzt</small>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Hinzufügen</button>
          </div>
        </form>
      </div></div>
    </div>

    <div class="modal fade" id="modalEditItem" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="edit_rotator_item">
          <input type="hidden" name="item_id" value="">
          <div class="modal-header"><h5 class="modal-title">Link bearbeiten</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <div class="modal-body">
            <div class="form-group">
              <label>Name *</label>
              <input type="text" name="item_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Ziel-URL *</label>
              <input type="url" name="item_url" class="form-control" required>
            </div>
            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label>Weight</label>
                  <input type="number" name="weight" class="form-control" min="1" max="100">
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label>Click Limit</label>
                  <input type="number" name="click_limit" class="form-control" min="0">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Speichern</button>
          </div>
        </form>
      </div></div>
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
document.querySelectorAll('.edit-btn').forEach(function(b){
    b.addEventListener('click', function(){
        var m = document.getElementById('modalEditRotator');
        m.querySelector('[name=rotator_id]').value    = b.dataset.id;
        m.querySelector('[name=name]').value          = b.dataset.name;
        m.querySelector('[name=rotation_mode]').value = b.dataset.mode;
        m.querySelector('[name=fallback_url]').value  = b.dataset.fallback;
        m.querySelector('[name=is_active]').checked   = b.dataset.active === '1';
    });
});
document.querySelectorAll('.edit-item-btn').forEach(function(b){
    b.addEventListener('click', function(){
        var m = document.getElementById('modalEditItem');
        m.querySelector('[name=item_id]').value     = b.dataset.id;
        m.querySelector('[name=item_name]').value   = b.dataset.name;
        m.querySelector('[name=item_url]').value    = b.dataset.url;
        m.querySelector('[name=weight]').value      = b.dataset.weight;
        m.querySelector('[name=click_limit]').value = b.dataset.limit;
    });
});
document.querySelectorAll('.copy-btn').forEach(function(b){
    b.addEventListener('click', function(){
        navigator.clipboard.writeText(b.dataset.url).then(function(){
            var orig = b.innerHTML;
            b.innerHTML = '<i class="ft-check"></i>';
            setTimeout(function(){ b.innerHTML = orig; }, 1500);
        });
    });
});
</script>
</body>
</html>
