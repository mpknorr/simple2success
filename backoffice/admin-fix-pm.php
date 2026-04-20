<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    die('Access denied.');
}
require_once "../includes/conn.php";

$adminid = (int)$_SESSION['userid'];
$message = '';

// ── Auto-ensure is_admin=1 for the current admin session user ────────────────
// (handles transition from old username="admin" detection to new is_admin column)
mysqli_query($link, "UPDATE users SET is_admin=1 WHERE leadid = $adminid");

// ── Find all rows with the old non-numeric username ───────────────────────────
$oldRows = [];
$res = mysqli_query($link, "SELECT leadid, username, email FROM users WHERE username = 'mpknorr1' OR (username != '' AND username NOT REGEXP '^[0-9]+$')");
while ($r = mysqli_fetch_assoc($res)) {
    $oldRows[] = $r;
}

// ── Current admin username ────────────────────────────────────────────────────
$row     = mysqli_fetch_assoc(mysqli_query($link, "SELECT username FROM users WHERE leadid = $adminid"));
$current = $row['username'] ?? '(not set)';

if (isset($_POST['newpm'])) {
    $newpm = trim($_POST['newpm']);
    if (!preg_match('/^\d+$/', $newpm)) {
        $message = '<div class="alert alert-danger">Invalid PM Partner ID — numbers only.</div>';
    } else {
        $safe = mysqli_real_escape_string($link, $newpm);
        // Update the admin's own record (PM number + ensure is_admin flag is set)
        mysqli_query($link, "UPDATE users SET username='$safe', is_admin=1 WHERE leadid = $adminid");
        // Also fix all rows that still have the old non-numeric value
        $affected = 0;
        foreach ($oldRows as $r) {
            if ((int)$r['leadid'] !== $adminid) {
                mysqli_query($link, "UPDATE users SET username='$safe' WHERE leadid = " . (int)$r['leadid']);
                $affected++;
            }
        }
        $current   = $newpm;
        $oldRows   = [];
        $extra     = $affected > 0 ? " Also updated <strong>$affected</strong> other record(s) with the old non-numeric username." : '';
        $message   = '<div class="alert alert-success">
            <strong>Done.</strong> PM Partner ID set to <strong>' . htmlspecialchars($newpm) . '</strong>.' . $extra . '<br>
            The Step 1 registration link will now use <code>?TP=' . htmlspecialchars($newpm) . '</code>.<br>
            <small>You can delete this file from the server when finished.</small>
        </div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Admin: Fix PM Partner ID</title>
<link rel="stylesheet" href="app-assets/css/vendors.min.css">
<link rel="stylesheet" href="app-assets/css/app.css">
<style>
  body { padding: 2.5rem; max-width: 640px; margin: 0 auto; }
  code { background: rgba(0,0,0,.07); padding: .1rem .4rem; border-radius: 3px; }
</style>
</head>
<body>
<h3>Admin: Fix PM Partner ID</h3>
<hr>

<p><strong>Your current username value:</strong> <code><?= htmlspecialchars($current) ?></code>
<?php if (!preg_match('/^\d+$/', $current)): ?>
  &nbsp;<span class="badge badge-warning">Needs update</span>
<?php else: ?>
  &nbsp;<span class="badge badge-success">Looks good</span>
<?php endif; ?>
</p>

<?php if (!empty($oldRows)): ?>
<div class="alert alert-warning">
  <strong>Found <?= count($oldRows) ?> record(s) with a non-numeric username that need updating:</strong>
  <ul style="margin:.5rem 0 0;padding-left:1.25rem;">
    <?php foreach ($oldRows as $r): ?>
      <li>leadid <strong><?= (int)$r['leadid'] ?></strong> — username: <code><?= htmlspecialchars($r['username']) ?></code> (<?= htmlspecialchars($r['email']) ?>)</li>
    <?php endforeach; ?>
  </ul>
  <p class="mb-0 mt-2">Entering your PM Partner ID below will update <strong>all</strong> of these to the new value.</p>
</div>
<?php endif; ?>

<?= $message ?>

<form method="post" style="margin-top:1rem;">
    <div class="form-group">
        <label><strong>PM Partner ID</strong> (numbers only)</label>
        <input type="text" name="newpm" class="form-control form-control-lg"
               placeholder="e.g. 6304013" pattern="\d+" required
               value="<?= preg_match('/^\d+$/', $current) ? htmlspecialchars($current) : '' ?>">
        <small class="form-text text-muted">Used as <code>?TP=</code> in the Step 1 PM registration link for all your referrals.</small>
    </div>
    <button type="submit" class="btn btn-primary btn-lg">Save &amp; Fix All Records</button>
    <a href="start.php" class="btn btn-secondary btn-lg ml-1">Back to Start</a>
</form>
<hr>
<p class="text-muted" style="font-size:.85rem;">
    After saving, delete <code>admin-fix-pm.php</code> from the server.
</p>
</body>
</html>
