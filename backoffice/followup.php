<?php
// Include your 'conn.php' file
include '../includes/conn.php';
// 
if(isset($_POST["click"])){
    $clickTime = $_POST["click"];
    $userid = $_POST["userid"];
    mysqli_query($link, "UPDATE users SET signuproot = '$clickTime' WHERE leadid = $userid");
die();
}
// Start the PHP session
if(!isset($_SESSION)){
    session_start();
}
// Check if the 'userid' session variable is not set or empty
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    // Redirect to the login page
    header('Location: login.php');
    exit(); // Make sure to exit to prevent further execution
}



// Retrieve the 'userid' from the session
$userid = $_SESSION['userid'];

$user_detailsdata = mysqli_query($link, "SELECT referer FROM users WHERE leadid = $userid");
foreach($user_detailsdata as $userData){
    $user_referer = $userData["referer"];
}

$get_refererdata = mysqli_query($link, "SELECT username FROM users WHERE leadid = $user_referer");
foreach($get_refererdata as $referData){
    $referer_username = $referData["username"];
}
if(empty($referer_username)){
    header("Location: " . $baseurl . "/backoffice/");
}
// Now you can use the $userid variable in your code
// ...
?>

<!DOCTYPE html>
<html class="loading" lang="en">
<!-- BEGIN : Head-->

<?php
require_once "parts/head.php";
?>
<!-- END : Head-->

<!-- BEGIN : Body-->
 <link rel="stylesheet" href="app-assets/css/pages/ex-component-media-player.css">
 <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/plyr.css">

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

    <!-- ////////////////////////////////////////////////////////////////////////////-->


            <div class="main-content">
                <div class="content-overlay"></div>
                <div class="content-wrapper">

                    <!--Follow Up Lead starts-->
                    <div class="row">
                        <div class="col-12">
                            <div class="content-header">List of Scheduled Follow-Ups</div>
                        </div>
                    </div>


                      <!-- Add Follow Up start -->
<section id="basic-input">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Add Follow-Up</h4>
                    <!-- Trigger button for collapsible area -->
                    <button type="button" class="btn btn-primary mr-2" data-toggle="collapse" data-target="#collapseForm" aria-expanded="false" aria-controls="collapseForm">
                        <i class="ft-check-square mr-1"></i>Add Schedule
                    </button>
                </div>
<br>
                <!-- Collapsible form content -->
                <div class="collapse" id="collapseForm">
                    <div class="card-content">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <fieldset class="form-group">
                                        <label for="basicInput">* Follow-Up NAME</label>
                                        <input type="text" class="form-control" id="basicInput" placeholder="Enter Name of Follow Up">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="helpInputTop">* AUTO SEND REMINDER AFTER (DAYS)</label>
                                        <input type="number" id="striped-form-5" class="form-control" name="autosendday">
                                    </fieldset>
                                </div>
                                <div class="col-md-6">
                                    <fieldset class="form-group">
                                        <label for="basicInput">* ADD TO STATUS</label>
                                        <select class="form-control" id="basicSelect">
                                            <option>Lead</option>
                                            <option>Active Member</option>
                                        </select>
                                    </fieldset>
                                </div>
                            </div>
                            <div id="full-container">
                                <label for="basicInput">* Email Template</label>
                                <div class="editor">
                                   <img src="https://www.simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg" style="max-width:100%"><br><br>
    <h1>CONGRATULATIONS! You Have a New Personal Customer!</h1><br>
    <p><strong>Hey {lead_name},</strong><br><br>
    <em>"Success is not something that just happens." - George Halas</em><br><br>
    We have great news! <strong>Dedication and support have paid off</strong> - a <strong>new personal customer</strong> has been added!<br>
    Here is the info for the new customer:</p>
    <?php echo htmlspecialchars($user_email); ?>
    <br>
    <p>This is proof of excellent work in attracting leads and bringing them to completion. This success belongs to all of us.<br><br>
    <strong>But now, it's about staying committed and continuing</strong>. Remember that the path to success consists of multiple steps. <strong>It's time to repeat Step 3</strong> to achieve even more success.<br><br>
    <strong>Don't forget!</strong><br>To ensure commissions in the future, it's crucial to <strong>activate RPS (ROOT Prime Subscription)</strong>. ROOT Prime Subscription (RPS) is a loyalty membership program designed to provide special rewards and services to customers and ambassadors who receive their selected products automatically shipped every 30 days. ROOT Prime membership is activated upon the first ROOT Prime Subscription (RPS) order.<br><br>
    We are confident that with determination and commitment, even greater success is ahead. If you need more information or assistance, please feel free to reach out.<br><br>
    Congratulations once again on this fantastic achievement!<br><br>
    Your Eagle Team</p>
                            </div>
                                <br>
                                <label for="basicInput">Available merge fields</label>
                                <br>
                                <small class="text-muted">{lead_name}, {lead_email}, {lead_id}, {lead_country}, {lead_phone}, {lead_whatsapp}, {lead_memberstatus}, {lead_datetimeregistered}, {lead_datetimelatestactivity}</small><br>
                            </div>
                            <br>
                            <button class="btn btn-primary" type="submit">Save</button>
                        </div>
                    </div>
                </div>
                <!-- End of collapsible form content -->
            </div>
        </div>
    </div>
</section>
<!-- Add Follow Up end -->

                    <section id="extended">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Follow-Up for Leads</h4>
                                    </div>
                                    <div class="card-content">
                                        <div class="card-body table-responsive">
                                            <table class="table text-center m-0">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Days</th>
                                                        <th>Schedule Name</th>
                                                        <th>Email</th>
                                                      <!--   <th>Schedule Hours</th> -->
                                                        <th>Createt at</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>Day 1</td>
                                                        <td>Email Follow Up 1</td>
                                                        <td>
                                                            <i class="ft-check"></i>
                                                        </td>
                                                        <!-- <td>12 PM</td> -->
                                                        <td>25-03-2024 7:01 PM</td>
                                                        <td class="text-truncate">
                                                            <a href="javascript:;" class="success p-0">
                                                                <i class="ft-edit-2 font-medium-3 mr-2"></i>
                                                            </a>
                                                            <a href="javascript:;" class="danger p-0">
                                                                <i class="ft-x font-medium-3"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>2</td>
                                                        <td>Day 2</td>
                                                        <td>Email Follow Up 2</td>
                                                        <td>
                                                            <i class="ft-check"></i>
                                                        </td>
                                                        <!-- <td>12 PM</td> -->
                                                        <td>25-03-2024 7:01 PM</td>
                                                        <td class="text-truncate">
                                                            <a href="javascript:;" class="success p-0">
                                                                <i class="ft-edit-2 font-medium-3 mr-2"></i>
                                                            </a>
                                                            <a href="javascript:;" class="danger p-0">
                                                                <i class="ft-x font-medium-3"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>3</td>
                                                        <td>Day 3</td>
                                                        <td>Email Follow Up 3</td>
                                                        <td>
                                                            <i class="ft-check"></i>
                                                        </td>
                                                        <!-- <td>12 PM</td> -->
                                                        <td>25-03-2024 7:01 PM</td>
                                                        <td class="text-truncate">
                                                            <a href="javascript:;" class="success p-0">
                                                                <i class="ft-edit-2 font-medium-3 mr-2"></i>
                                                            </a>
                                                            <a href="javascript:;" class="danger p-0">
                                                                <i class="ft-x font-medium-3"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!--Follow Up Lead Ends-->




                    <!--Follow Up Members starts-->
                    
                    <section id="extended">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Follow-Up for active Members (Where have finish Step 2)</h4>
                                    </div>
                                    <div class="card-content">
                                        <div class="card-body table-responsive">
                                            <table class="table text-center m-0">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Days</th>
                                                        <th>Schedule Name</th>
                                                        <th>Email</th>
                                                        <!-- <th>Schedule Hours</th> -->
                                                        <th>Createt at</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>Day 1</td>
                                                        <td>Email Follow Up 1</td>
                                                        <td>
                                                            <i class="ft-check"></i>
                                                        </td>
                                                        <!-- <td>12 PM</td> -->
                                                        <td>25-03-2024 7:01 PM</td>
                                                        <td class="text-truncate">
                                                            <a href="javascript:;" class="success p-0">
                                                                <i class="ft-edit-2 font-medium-3 mr-2"></i>
                                                            </a>
                                                            <a href="javascript:;" class="danger p-0">
                                                                <i class="ft-x font-medium-3"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>2</td>
                                                        <td>Day 2</td>
                                                        <td>Email Follow Up 2</td>
                                                        <td>
                                                            <i class="ft-check"></i>
                                                        </td>
                                                        <!-- <td>12 PM</td> -->
                                                        <td>25-03-2024 7:01 PM</td>
                                                        <td class="text-truncate">
                                                            <a href="javascript:;" class="success p-0">
                                                                <i class="ft-edit-2 font-medium-3 mr-2"></i>
                                                            </a>
                                                            <a href="javascript:;" class="danger p-0">
                                                                <i class="ft-x font-medium-3"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>3</td>
                                                        <td>Day 3</td>
                                                        <td>Email Follow Up 3</td>
                                                        <td>
                                                            <i class="ft-check"></i>
                                                        </td>
                                                        <!-- <td>12 PM</td> -->
                                                        <td>25-03-2024 7:01 PM</td>
                                                        <td class="text-truncate">
                                                            <a href="javascript:;" class="success p-0">
                                                                <i class="ft-edit-2 font-medium-3 mr-2"></i>
                                                            </a>
                                                            <a href="javascript:;" class="danger p-0">
                                                                <i class="ft-x font-medium-3"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-3 mt-sm-2">
                                                                <button type="submit" class="btn btn-primary mb-2 mb-sm-0 mr-sm-2">Add Schedule</button>
                                                            </div> -->


                        </div>
                    </section>
                    <!--Follow Up Members Ends-->



                   





                </div>
            </div>
 <!-- END : End Main Content-->


    <!-- ////////////////////////////////////////////////////////////////////////////-->


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
    <script src="app-assets/vendors/js/katex.min.js"></script>
    <script src="app-assets/vendors/js/highlight.min.js"></script>
    <script src="app-assets/vendors/js/quill.min.js"></script>
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN APEX JS-->
    <script src="app-assets/js/core/app-menu.js"></script>
    <script src="app-assets/js/core/app.js"></script>
    <script src="app-assets/js/notification-sidebar.js"></script>
    <script src="app-assets/js/customizer.js"></script>
    <script src="app-assets/js/scroll-top.js"></script>
    <!-- END APEX JS-->
    <!-- BEGIN PAGE LEVEL JS-->
    <script src="app-assets/js/page-users.js"></script>
    <script src="app-assets/js/form-editor.js"></script>
    <!-- END PAGE LEVEL JS-->
    <!-- BEGIN: Custom CSS-->
    <script src="assets/js/scripts.js"></script>

  

    <!-- END: Custom CSS-->
</body>
<!-- END : Body-->

</html>