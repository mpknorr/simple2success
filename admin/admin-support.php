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

// Ensure table exists
mysqli_query($link, "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    fname VARCHAR(100),
    lname VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(50),
    message TEXT,
    attachment VARCHAR(255),
    status ENUM('open','closed') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Status toggle
if (!empty($_GET['toggle_id'])) {
    $tid = (int)$_GET['toggle_id'];
    mysqli_query($link, "UPDATE support_tickets SET status = IF(status='open','closed','open') WHERE id = $tid");
    header("Location: admin-support.php");
    exit();
}

$tickets = mysqli_query($link, "SELECT * FROM support_tickets ORDER BY created_at DESC");
$tpl_list = [];
while ($row = mysqli_fetch_assoc($tickets)) {
    $tpl_list[] = $row;
}

$openCount   = array_reduce($tpl_list, fn($c, $t) => $c + ($t['status'] === 'open' ? 1 : 0), 0);
$closedCount = count($tpl_list) - $openCount;
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<style>
.badge-open   { background:#28a745; color:white; padding:3px 10px; border-radius:12px; font-size:12px; }
.badge-closed { background:#6c757d; color:white; padding:3px 10px; border-radius:12px; font-size:12px; }
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
                    <h3 class="content-header-title">Admin — Support Tickets</h3>
                </div>
            </div>

            <!-- Stats -->
            <div class="row mb-3">
                <div class="col-md-3 col-6">
                    <div class="card text-center py-3">
                        <div style="font-size:28px;font-weight:700;color:#cb2ebc;"><?= count($tpl_list) ?></div>
                        <div style="color:#aaa;font-size:13px;">Tickets gesamt</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card text-center py-3">
                        <div style="font-size:28px;font-weight:700;color:#28a745;"><?= $openCount ?></div>
                        <div style="color:#aaa;font-size:13px;">Offen</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card text-center py-3">
                        <div style="font-size:28px;font-weight:700;color:#6c757d;"><?= $closedCount ?></div>
                        <div style="color:#aaa;font-size:13px;">Geschlossen</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><i class="ft-inbox mr-1"></i> Alle Support Tickets</h4>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="supportTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Lead ID</th>
                                        <th>Name</th>
                                        <th>E-Mail</th>
                                        <th>Nachricht</th>
                                        <th>Anhang</th>
                                        <th>Status</th>
                                        <th>Datum</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tpl_list as $t): ?>
                                    <tr>
                                        <td><?= $t['id'] ?></td>
                                        <td><strong>#<?= $t['lead_id'] ?></strong></td>
                                        <td><?= htmlspecialchars($t['fname'] . ' ' . $t['lname']) ?></td>
                                        <td><?= htmlspecialchars($t['email']) ?></td>
                                        <td>
                                            <span title="<?= htmlspecialchars($t['message']) ?>">
                                                <?= htmlspecialchars(mb_substr($t['message'], 0, 70)) ?><?= mb_strlen($t['message']) > 70 ? '…' : '' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($t['attachment']): ?>
                                            <a href="<?= $baseurl ?>/<?= htmlspecialchars($t['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="ft-paperclip"></i> Download
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td>
                                            <a href="admin-support.php?toggle_id=<?= $t['id'] ?>" class="btn btn-sm <?= $t['status'] === 'open' ? 'btn-secondary' : 'btn-success' ?>">
                                                <?= $t['status'] === 'open' ? '<i class="ft-check"></i> Schließen' : '<i class="ft-refresh-cw"></i> Öffnen' ?>
                                            </a>
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
<link rel="stylesheet" href="../backoffice/app-assets/vendors/css/tables/datatable/datatables.min.css">
<script src="../backoffice/app-assets/vendors/js/tables/datatable/datatables.min.js"></script>
<script>
$(document).ready(function(){ $('#supportTable').DataTable({ order: [[0,'desc']] }); });
</script>
</body>
</html>
