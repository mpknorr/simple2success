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
    $name = $userData["name"];
    $username = $userData["username"];
    $useremail = $userData["email"];
    $paidstatus = $userData["paidstatus"];
    $profile_pic = $userData["profile_pic"];
}
$users = mysqli_query($link, "SELECT u.*, s.fb_link, s.country FROM users u LEFT JOIN user_socialmedia s ON u.leadid = s.user_id ORDER BY u.timestamp DESC");
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
                    <h3 class="content-header-title">Admin — Alle User</h3>
                </div>
            </div>
            <section class="users-list-wrapper">
                <div class="users-list-table">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="users-list-datatable" class="table">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>E-Mail</th>
                                                        <th>Username</th>
                                                        <th>Sponsor-ID</th>
                                                        <th>Source</th>
                                                        <th>Page</th>
                                                        <th>Status</th>
                                                        <th>Datum</th>
                                                        <th>Bearbeiten</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($u = mysqli_fetch_assoc($users)) { ?>
                                                    <tr>
                                                        <td><?= $u['leadid'] ?></td>
                                                        <td><?= htmlspecialchars($u['name']) ?></td>
                                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                                        <td><?= htmlspecialchars($u['referer']) ?></td>
                                                        <td><?= htmlspecialchars($u['source']) ?></td>
                                                        <td><?= htmlspecialchars($u['page']) ?></td>
                                                        <td>
                                                            <span class="badge badge-<?= $u['paidstatus'] === 'Paid' ? 'success' : 'secondary' ?>">
                                                                <?= htmlspecialchars($u['paidstatus']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('d.m.Y', strtotime($u['timestamp'])) ?></td>
                                                        <td>
                                                            <a href="<?= $baseurl ?>/admin/admin-user-edit.php?id=<?= $u['leadid'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="ft-edit"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <?php require_once "../backoffice/parts/footer.php"; ?>
    <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
</div>
</div>
<div class="sidenav-overlay"></div>
<div class="drag-target"></div>
<script src="../backoffice/app-assets/vendors/js/vendors.min.js"></script>
<script src="../backoffice/app-assets/vendors/js/datatable/jquery.dataTables.min.js"></script>
<script src="../backoffice/app-assets/vendors/js/datatable/dataTables.bootstrap4.min.js"></script>
<script src="../backoffice/app-assets/js/core/app-menu.js"></script>
<script src="../backoffice/app-assets/js/core/app.js"></script>
<script src="../backoffice/app-assets/js/notification-sidebar.js"></script>
<script src="../backoffice/app-assets/js/scroll-top.js"></script>
<script src="../backoffice/app-assets/js/page-users.js"></script>
<script src="../backoffice/assets/js/scripts.js"></script>
</body>
</html>
