<?php
// Start the PHP session
session_start();

// Check if the 'userid' session variable is not set or empty
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    // Redirect to the login page
    header('Location: login.php');
    exit(); // Make sure to exit to prevent further execution
}

// Include your 'conn.php' file
include '../includes/conn.php';

// Retrieve the 'userid' from the session
$userid = $_SESSION['userid'];

// Prefill user data
$userData = mysqli_fetch_assoc(mysqli_query($link, "SELECT name, email FROM users WHERE leadid = $userid"));
$prefillName  = $userData ? $userData['name'] : '';
$prefillEmail = $userData ? $userData['email'] : '';
$prefillFname = '';
$prefillLname = '';
if ($prefillName) {
    $parts = explode(' ', $prefillName, 2);
    $prefillFname = $parts[0];
    $prefillLname = $parts[1] ?? '';
}
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php
require_once "parts/head.php";
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

<!-- BEGIN : Support Content-->
          

<div class="main-content">
          <div class="content-wrapper">


<!--Start Subscription content -->
    
           
 <div class="col-lg-12"> 
    <div class="card">
      <div class="card-header">
        <h4 class="card-title m-0 p-0">Support</h4>
      </div>
      <div class="card-content">
        <div class="card-body">
                                            <p>If you need help with anything in this marketing system, please contact us. Your Lead ID will be included automatically.</p>

                                            <?php if (!empty($_GET['success'])): ?>
                                            <div class="alert alert-success"><i class="ft-check-circle mr-1"></i> Your support request has been sent successfully! We will get back to you soon.</div>
                                            <?php elseif (!empty($_GET['error'])): ?>
                                            <div class="alert alert-danger"><i class="ft-alert-circle mr-1"></i> Please fill in all required fields (First Name, E-Mail, Message).</div>
                                            <?php endif; ?>

                                            <form method="POST" action="../includes/processSupportTicket.php" enctype="multipart/form-data">
                                                <div class="form-row">
                                                    <div class="col-md-6 col-12">
                                                        <div class="form-group mb-2">
                                                            <label for="basic-form-1">First Name <span class="text-danger">*</span></label>
                                                            <input type="text" id="basic-form-1" class="form-control" name="fname" value="<?= htmlspecialchars($prefillFname) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div class="form-group mb-2">
                                                            <label for="basic-form-2">Last Name</label>
                                                            <input type="text" id="basic-form-2" class="form-control" name="lname" value="<?= htmlspecialchars($prefillLname) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="col-md-6 col-12">
                                                        <div class="form-group mb-2">
                                                            <label for="basic-form-3">E-mail <span class="text-danger">*</span></label>
                                                            <input type="email" id="basic-form-3" class="form-control" name="email" value="<?= htmlspecialchars($prefillEmail) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div class="form-group mb-2">
                                                            <label for="basic-form-4">Contact Number</label>
                                                            <input type="text" id="basic-form-4" class="form-control" name="phone">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group mb-2">
                                                    <label for="basic-form-8">Attach File <small class="text-muted">(JPG, PNG, PDF, max 10MB)</small></label>
                                                    <input type="file" class="form-control-file" id="basic-form-8" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label for="basic-form-9">Your Message <span class="text-danger">*</span></label>
                                                    <textarea id="basic-form-9" rows="5" class="form-control" name="comment" required placeholder="Describe your issue..."></textarea>
                                                </div>
                                                <p class="text-muted" style="font-size:12px;"><i class="ft-info mr-1"></i>Your Lead ID #<?= $userid ?> will be included in the support request.</p>
                                                <button type="submit" class="btn btn-primary mr-2"><i class="ft-check-square mr-1"></i>Send Support Request</button>
                                            </form>
                                        </div>
                                    </div>
</div>

<!--- End Support Content ////////////////////////////////////////-->



    </div>
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

    
    <!-- START Notification Sidebar -->
    <!-- END Notification Sidebar-->


    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>
    <!-- BEGIN VENDOR JS-->
    <script src="app-assets/vendors/js/vendors.min.js"></script>
    <script src="app-assets/vendors/js/switchery.min.js"></script>
    <!-- BEGIN VENDOR JS-->
    <!-- BEGIN PAGE VENDOR JS-->
    <script src="app-assets/vendors/js/pickadate/picker.js"></script>
    <script src="app-assets/vendors/js/pickadate/picker.date.js"></script>
    <script src="app-assets/vendors/js/pickadate/picker.time.js"></script>
    <script src="app-assets/vendors/js/pickadate/legacy.js"></script>    
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN APEX JS-->
    <script src="app-assets/js/core/app-menu.js"></script>
    <script src="app-assets/js/core/app.js"></script>
    <script src="app-assets/js/notification-sidebar.js"></script>
    <script src="app-assets/js/customizer.js"></script>
    <script src="app-assets/js/scroll-top.js"></script>
    <!-- END APEX JS-->
    <!-- BEGIN PAGE LEVEL JS-->
  <script src="../../../app-assets/js/datetime-picker.js"></script>
    <!-- END PAGE LEVEL JS-->
    <!-- BEGIN: Custom CSS-->
    <script src="assets/js/scripts.js"></script>
    <!-- END: Custom CSS-->
</body>
<!-- END : Body-->

</html>