<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Start the PHP session

// Check if the 'userid' session variable is not set or empty
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    // Redirect to the login page
    header('Location: login.php');
    exit(); // Make sure to exit to prevent further execution
}

// Include your 'conn.php' file
include '../includes/conn.php';

// Retrieve the 'userid' from the session
// $userid = $_SESSION['userid'];

// Now you can use the $userid variable in your code
// ...
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php
require_once "parts/head.php";
$sql = "SELECT u.*,
    COALESCE(es.email_status, 'unknown') AS email_status,
    es.last_event_at
FROM users u
LEFT JOIN (
    SELECT user_id,
        CASE
            WHEN SUM(status = 'spam') > 0                              THEN 'spam'
            WHEN SUM(status = 'bounced' AND bounce_type = 'hard') > 0  THEN 'hard_bounce'
            WHEN SUM(status = 'bounced') > 0                           THEN 'soft_bounce'
            WHEN SUM(status IN ('opened','clicked')) > 0               THEN 'engaged'
            WHEN SUM(status = 'delivered') > 0                         THEN 'delivered'
            WHEN COUNT(*) > 0                                           THEN 'sent'
            ELSE 'unknown'
        END AS email_status,
        MAX(COALESCE(spam_at, bounced_at, opened_at, delivered_at, sent_at)) AS last_event_at
    FROM followup_log
    GROUP BY user_id
) es ON es.user_id = u.leadid
WHERE u.referer = $userid AND u.email != '$useremail'";
$leads = mysqli_query($link, $sql);
?>
<link rel="stylesheet" type="text/css" href="app-assets/vendors/css/datatables/dataTables.bootstrap4.min.css">
<?php
?>

<!-- BEGIN : Body-->

<body class="vertical-layout vertical-menu 2-columns  navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">

<?php
require_once "parts/navbar.php";
?>

    <!-- ////////////////////////////////////////////////////////////////////////////-->
    <div class="wrapper">


        <!-- main menu-->
        <!--.main-menu(class="#{menuColor} #{menuOpenType}", class=(menuShadow == true ? 'menu-shadow' : ''))-->
<?php
require_once "parts/sidebar.php";
?>





   <div class="main-panel">
            <!-- BEGIN : Main Content-->
            <div class="main-content">
                <div class="content-overlay"></div>
                <div class="content-wrapper">
                <?php
            if(empty($username)){
                echo "<p>You should complete STEP 1 & STEP 2 from <a href='" . $baseurl . "/backoffice/start.php'>here</a>.</p>";
            }else{
            ?>
                    <!--  -->
                    <section class="users-list-wrapper">


                        <!-- Filter starts -->
                        <div class="users-list-filter px-2">
                            <form>
                                <div class="row border rounded py-2 mb-2 mx-n2">
                                    <div class="col-12 col-sm-6 col-lg-3">
                                        <label for="users-list-verified">Page</label>
                                        <fieldset class="form-group">
                                            <select id="users-list-verified" class="form-control">
                                                <option value="Any">Any</option>
                                                <option value="Hoot1">Hoot1</option>
                                                <option value="Hoot4">Hoot2</option>
                                                <option value="Hoot3">Hoot3</option>
                                                <option value="Link1">Link1</option>
                                                <option value="Link2">Link2</option>
                                                <option value="Link3">Link3</option>
                                            </select>
                                        </fieldset>
                                    </div>

                                    <div class="col-12 col-sm-6 col-lg-3">
                                        <label for="users-list-role">PM Partner ID</label>
                                        <fieldset class="form-group">
                                            <select id="users-list-role" class="form-control">
                                                <option value="Any">Any</option>
                                                <option value="Aktive">Aktive</option>
                                        
                                            </select>
                                        </fieldset>
                                    </div>

                                    <div class="col-12 col-sm-6 col-lg-3">
                                        <label for="users-list-status">Paid</label>
                                        <fieldset class="form-group">
                                            <select id="users-list-status" class="form-control">
                                                <option value="">Any</option>
                                                <option value="Free">Free</option>
                                                <option value="Paid">Paid</option>
                                            </select>
                                        </fieldset>
                                    </div>

                                    <div class="col-12 col-sm-6 col-lg-3">
                                        <label for="users-list-email-status">E-Mail Status</label>
                                        <fieldset class="form-group">
                                            <select id="users-list-email-status" class="form-control">
                                                <option value="">Any</option>
                                                <option value="Spam">🚫 Spam</option>
                                                <option value="Hard Bounce">⛔ Hard Bounce</option>
                                                <option value="Soft Bounce">⚠️ Soft Bounce</option>
                                                <option value="Geöffnet">👁 Geöffnet</option>
                                                <option value="Zugestellt">✅ Zugestellt</option>
                                                <option value="Gesendet">→ Gesendet</option>
                                                <option value="—">— Kein Status</option>
                                            </select>
                                        </fieldset>
                                    </div>

                                    <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-center" style="padding-top:1.25rem;">
                                        <button type="reset" id="users-list-clear" class="btn btn-primary btn-block glow mb-0">Clear</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- Filter ends -->

                        <!-- Table starts -->
                        <div class="users-list-table">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-content">
                                            <div class="card-body">

                                                <!-- Datatable starts -->
                                                <div class="table-responsive">
                                                    <table id="users-list-datatable" class="table">
                                                        <thead>
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>E-Mail</th>
                                                                <th>Country</th>
                                                                <th>Source</th>
                                                                <th>Page</th>
                                                                <th>PM Partner ID</th>
                                                                <th>Paid</th>
                                                                <th>Date</th>
                                                                <th>E-Mail Status</th>
                                                                <th class="no-export">Details</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach($leads as $lead){ ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($lead['name']) ?></td>
                                                                <td><?= htmlspecialchars($lead['email']) ?></td>
                                                                <td><?php
                                                                    $cc = $lead['country_detected'] ?? '';
                                                                    if ($cc !== '') {
                                                                        // flag emoji from ISO code
                                                                        $flag = strlen($cc) === 2
                                                                            ? mb_chr(ord(strtoupper($cc)[0]) - 65 + 0x1F1E6) . mb_chr(ord(strtoupper($cc)[1]) - 65 + 0x1F1E6)
                                                                            : '';
                                                                        echo $flag . ' ' . htmlspecialchars(isoCodeToCountryName($cc));
                                                                    } else {
                                                                        echo '<span style="opacity:.4;">—</span>';
                                                                    }
                                                                ?></td>
                                                                <td><?= htmlspecialchars($lead['source']) ?></td>
                                                                <td><?= htmlspecialchars($lead['page']) ?></td>
                                                                <td><?= htmlspecialchars($lead['username']) ?></td>
                                                                <td><?= htmlspecialchars($lead['paidstatus']) ?></td>
                                                                <td><?= date('Y-m-d', strtotime($lead['timestamp'])) ?></td>
                                                                <td><?php
                                                                    $es = $lead['email_status'] ?? 'unknown';
                                                                    $badges = [
                                                                        'spam'         => ['🚫 Spam',        '#ea545533', '#ea5455'],
                                                                        'hard_bounce'  => ['⛔ Hard Bounce',  '#ea545533', '#ea5455'],
                                                                        'soft_bounce'  => ['⚠️ Soft Bounce',  '#ff980033', '#ff9800'],
                                                                        'engaged'      => ['👁 Geöffnet',     '#00cfe833', '#00cfe8'],
                                                                        'delivered'    => ['✅ Zugestellt',   '#28c76f33', '#28c76f'],
                                                                        'sent'         => ['→ Gesendet',     'rgba(255,255,255,.06)', 'rgba(255,255,255,.4)'],
                                                                        'unknown'      => ['—',              'transparent', 'rgba(255,255,255,.2)'],
                                                                    ];
                                                                    [$label, $bg, $color] = $badges[$es] ?? $badges['unknown'];
                                                                    echo '<span style="background:' . $bg . ';color:' . $color . ';border-radius:4px;padding:2px 7px;font-size:.75rem;white-space:nowrap;">' . $label . '</span>';
                                                                ?></td>
                                                                <td class="no-export">
                                                                    <a href="leads-view.php?id=<?= $lead['leadid'] ?>">
                                                                        <i class="ft-edit"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- Datatable ends -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Table ends -->
                    </section>
                    <!--  -->
                    <?php }?>
                </div>
            </div>
            <!-- END : End Main Content-->




       <!-- BEGIN : Footer-->
         
          <?php
    require_once "parts/footer.php";
    ?>
    
            <!-- End : Footer-->
            
            <!-- Scroll to top button -->
            <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>

        </div>
    </div>
    <!-- ////////////////////////////////////////////////////////////////////////////-->

    

    <!-- END Notification Sidebar-->
    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>
    <!-- BEGIN VENDOR JS-->
    <script src="app-assets/vendors/js/vendors.min.js"></script>
    <script src="app-assets/vendors/js/switchery.min.js"></script>
    <!-- BEGIN VENDOR JS-->
    <!-- BEGIN PAGE VENDOR JS-->
    <script src="app-assets/vendors/js/datatable/jquery.dataTables.min.js"></script>
    <script src="app-assets/vendors/js/datatable/dataTables.bootstrap4.min.js"></script>
    <script src="app-assets/vendors/js/datatable/dataTables.buttons.min.js"></script>
    <script src="app-assets/vendors/js/datatable/jszip.min.js"></script>
    <script src="app-assets/vendors/js/datatable/pdfmake.min.js"></script>
    <script src="app-assets/vendors/js/datatable/vfs_fonts.js"></script>
    <script src="app-assets/vendors/js/datatable/buttons.html5.min.js"></script>
    <script src="app-assets/vendors/js/datatable/buttons.print.min.js"></script>
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN APEX JS-->
    <script src="app-assets/js/core/app-menu.js"></script>
    <script src="app-assets/js/core/app.js"></script>
    <script src="app-assets/js/notification-sidebar.js"></script>
    <script src="app-assets/js/customizer.js"></script>
    <script src="app-assets/js/scroll-top.js"></script>
    <!-- END APEX JS-->
    <!-- BEGIN: Custom CSS-->
    <script src="assets/js/scripts.js"></script>
    <!-- END: Custom CSS-->
    <script>
    $(document).ready(function() {
        var table = $('#users-list-datatable').DataTable({
            dom: '<"d-flex justify-content-between align-items-center mb-2"lB>frtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="ft-download"></i> Excel',
                    className: 'btn btn-sm btn-success',
                    title: 'My Leads',
                    exportOptions: { columns: ':not(.no-export)' }
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="ft-download"></i> CSV',
                    className: 'btn btn-sm btn-info',
                    title: 'My Leads',
                    exportOptions: { columns: ':not(.no-export)' }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="ft-download"></i> PDF',
                    className: 'btn btn-sm btn-danger',
                    title: 'My Leads',
                    exportOptions: { columns: ':not(.no-export)' },
                    orientation: 'landscape',
                    pageSize: 'A4'
                },
                {
                    extend: 'print',
                    text: '<i class="ft-printer"></i> Print',
                    className: 'btn btn-sm btn-secondary',
                    exportOptions: { columns: ':not(.no-export)' }
                }
            ],
            pageLength: 25,
            language: {
                lengthMenu: 'Show _MENU_ entries',
                search: 'Search:',
                paginate: { previous: '&laquo;', next: '&raquo;' }
            }
        });

        // col indices: 0=Name,1=Email,2=Country,3=Source,4=Page,5=PM-ID,6=Paid,7=Date,8=EmailStatus,9=Details
        $('#users-list-verified').on('change', function() {
            table.column(4).search(this.value, false, false).draw();
        });
        $('#users-list-role').on('change', function() {
            table.column(5).search(this.value, false, false).draw();
        });
        $('#users-list-status').on('change', function() {
            table.column(6).search(this.value, false, false).draw();
        });
        $('#users-list-email-status').on('change', function() {
            table.column(8).search(this.value, false, false).draw();
        });

        $('#users-list-clear').on('click', function() {
            $('#users-list-verified, #users-list-role, #users-list-status, #users-list-email-status').val('');
            table.columns([4, 5, 6, 8]).search('').draw();
        });
    });
    </script>
</body>
<!-- END : Body-->

</html>